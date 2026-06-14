<?php

namespace App\Services;

use App\Models\ConsumerId;
use App\Models\DailyReport;
use App\Models\Recharge;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NescoScraperService
{
    /**
     * Scrap data from NESCO portal for a given ConsumerId model and update the database.
     */
    public function scrapeAndSync(ConsumerId $consumerId): bool
    {
        try {
            $cookies = new CookieJar;
            $client = Http::withOptions([
                'cookies' => $cookies,
                'verify' => false,
                'timeout' => 30,
            ]);

            // 1. GET page to populate session cookies and find CSRF token
            $response = $client->get('https://customer.nesco.gov.bd/pre/panel');
            if (! $response->successful()) {
                Log::error('NESCO GET Request failed with status: '.$response->status());

                return false;
            }

            $html = $response->body();
            preg_match('/meta name="csrf-token" content="([^"]+)"/', $html, $matches);
            $csrfToken = $matches[1] ?? null;

            if (! $csrfToken) {
                Log::error('NESCO CSRF Token not found in initial page load');

                return false;
            }

            // 2. POST to fetch recharge history and profile data
            $rechargeResponse = $client->asForm()->post('https://customer.nesco.gov.bd/pre/panel', [
                '_token' => $csrfToken,
                'cust_no' => $consumerId->consumer_id,
                'submit' => 'রিচার্জ হিস্ট্রি',
            ]);

            if (! $rechargeResponse->successful()) {
                Log::error('NESCO Recharge POST request failed with status: '.$rechargeResponse->status());

                return false;
            }

            // 3. POST to fetch monthly consumption data
            $consumptionResponse = $client->asForm()->post('https://customer.nesco.gov.bd/pre/panel', [
                '_token' => $csrfToken,
                'cust_no' => $consumerId->consumer_id,
                'submit' => 'মাসিক ব্যবহার',
            ]);

            if (! $consumptionResponse->successful()) {
                Log::error('NESCO Consumption POST request failed with status: '.$consumptionResponse->status());

                return false;
            }

            return $this->parseAndSave($consumerId, $rechargeResponse->body(), $consumptionResponse->body());
        } catch (\Exception $e) {
            Log::error('NESCO scraping exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Parse the response HTML and save results to DB.
     */
    protected function parseAndSave(ConsumerId $consumerId, string $rechargeHtml, ?string $consumptionHtml = null): bool
    {
        // Suppress warnings from invalid HTML structures parsed by DOMDocument
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$rechargeHtml);
        $xpath = new DOMXPath($dom);

        // --- A. Parse Profile Details ---
        // Profile details are located in input elements within the bfont_post form inside #con_info_div
        // We can find them using XPath queries looking for input value attributes next to labels
        $balanceTimeNodes = $xpath->query("//label[contains(normalize-space(.), 'অবশিষ্ট ব্যালেন্স')]//span");
        $balanceUpdatedAt = null;
        if ($balanceTimeNodes->length > 0) {
            $balanceUpdatedAt = trim($balanceTimeNodes->item(0)->textContent);
        }

        $profile = [
            'customer_name' => $this->getInputValueByLabel($xpath, 'গ্রাহকের নাম'),
            'father_husband_name' => $this->getInputValueByLabel($xpath, 'পিতা/স্বামীর নাম'),
            'address' => $this->getInputValueByLabel($xpath, 'ঠিকানা'),
            'mobile' => $this->getInputValueByLabel($xpath, 'মোবাইল'),
            'billing_office' => $this->getInputValueByLabel($xpath, 'সংশ্লিষ্ট বিদ্যুৎ অফিস'),
            'feeder_name' => $this->getInputValueByLabel($xpath, 'ফিডারের নাম'),
            'meter_no' => $this->getInputValueByLabel($xpath, 'মিটার নম্বর'),
            'sanction_load' => $this->getInputValueByLabel($xpath, 'অনুমোদিত লোড (কি.ও)'),
            'tariff' => $this->getInputValueByLabel($xpath, 'অনুমোদিত ট্যারিফ'),
            'meter_type' => $this->getInputValueByLabel($xpath, 'মিটারের ধরণ'),
            'meter_status' => $this->getInputValueByLabel($xpath, 'মিটার স্ট্যাটাস'),
            'installation_date' => $this->getInputValueByLabel($xpath, 'মিটার স্থাপনের তারিখ'),
            'min_recharge' => $this->getInputValueByLabel($xpath, 'মিনিমাম রিচার্জের পরিমাণ (টাকা)'),
            'remaining_balance' => (float) $this->getInputValueByLabel($xpath, 'অবশিষ্ট ব্যালেন্স (টাকা)'),
            'balance_updated_at' => $balanceUpdatedAt,
        ];

        // --- B. Parse Recharges ---
        // Find elements with class "consumerRechargeData" which have all values as data-* attributes
        $rechargeElements = $xpath->query("//a[contains(@class, 'consumerRechargeData')]");
        $rechargesData = [];

        foreach ($rechargeElements as $element) {
            /** @var \DOMElement $element */
            $tokenRaw = $element->getAttribute('data-token');
            // Clean up html tags inside token attribute e.g. <br> or &lt;br&gt;
            $tokenClean = trim(str_replace(['<br>', '<br/>', '&lt;br&gt;', '&lt;br/&gt;'], "\n", $tokenRaw));

            $rechargesData[] = [
                'order_no' => $element->getAttribute('data-order') ?: null,
                'token' => $tokenClean ?: null,
                'seq' => $element->getAttribute('data-seq') ?: null,
                'rent' => (float) ($element->getAttribute('data-rent') ?: 0),
                'demand_charge' => (float) ($element->getAttribute('data-demandcharge') ?: 0),
                'pfc' => (float) ($element->getAttribute('data-pfc') ?: 0),
                'tax' => (float) ($element->getAttribute('data-tax') ?: 0),
                'subsidy_amount' => (float) ($element->getAttribute('data-subsidyamount') ?: 0),
                'purchase_amount' => (float) ($element->getAttribute('data-purchaseamount') ?: 0),
                'total_amount' => (float) ($element->getAttribute('data-totalamount') ?: 0),
                'purchase_energy' => (float) ($element->getAttribute('data-purchaseenergy') ?: 0),
                'sale_name' => $element->getAttribute('data-salename') ?: null,
                'purchase_date' => $element->getAttribute('data-purchasedate') ?: null,
                'debt_amount' => (float) ($element->getAttribute('data-debtamount') ?: 0),
                'paid_amount' => (float) ($element->getAttribute('data-paidamount') ?: 0),
            ];
        }

        // --- C. Parse Monthly Usages ---
        $usagesData = [];
        if ($consumptionHtml) {
            $cDom = new DOMDocument;
            @$cDom->loadHTML('<?xml encoding="UTF-8">'.$consumptionHtml);
            $cXpath = new DOMXPath($cDom);

            $usageElements = $cXpath->query("//tbody[contains(@class, 'font_bill_summ')]/tr");
            foreach ($usageElements as $element) {
                /** @var \DOMElement $element */
                $tds = $element->getElementsByTagName('td');
                if ($tds->length >= 13) {
                    $usagesData[] = [
                        'year' => (int) trim($tds->item(0)->textContent),
                        'month' => trim($tds->item(1)->textContent),
                        'total_recharge' => (float) trim($tds->item(2)->textContent),
                        'rebate' => (float) trim($tds->item(3)->textContent),
                        'used_electricity_taka' => (float) trim($tds->item(4)->textContent),
                        'meter_rent' => (float) trim($tds->item(5)->textContent),
                        'demand_charge' => (float) trim($tds->item(6)->textContent),
                        'pfc_charge' => (float) trim($tds->item(7)->textContent),
                        'paid_arrear_penalty' => (float) trim($tds->item(8)->textContent),
                        'vat' => (float) trim($tds->item(9)->textContent),
                        'total_usage_deduction' => (float) trim($tds->item(10)->textContent),
                        'meter_balance' => (float) trim($tds->item(11)->textContent),
                        'used_electricity_kwh' => (float) trim($tds->item(12)->textContent),
                    ];
                }
            }
        }

        // --- D. Update Database in a Transaction ---
        DB::transaction(function () use ($consumerId, $profile, $rechargesData, $usagesData) {
            // Update profile info on the ConsumerId model
            $consumerId->update($profile);

            // Replaces all existing recharges for this consumer
            $consumerId->recharges()->delete();

            foreach ($rechargesData as $recharge) {
                $consumerId->recharges()->create($recharge);
            }

            // Replaces all existing monthly usages for this consumer
            $consumerId->monthlyUsages()->delete();

            foreach ($usagesData as $usage) {
                $consumerId->monthlyUsages()->create($usage);
            }

            // Create/update daily report snapshot for today's sync date
            $dateString = null;
            if (! empty($profile['balance_updated_at'])) {
                try {
                    $dateString = Carbon::parse($profile['balance_updated_at'])->toDateString();
                } catch (\Exception $e) {
                    // Ignore parsing errors
                }
            }
            if (! $dateString) {
                $dateString = Carbon::today()->toDateString();
            }

            DailyReport::updateOrCreate(
                [
                    'consumer_id_id' => $consumerId->id,
                    'date' => $dateString,
                ],
                [
                    'remaining_balance' => $profile['remaining_balance'] ?? 0,
                ]
            );

            // Run full recalculation for daily reports
            app(DailyReportService::class)->recalculateAll($consumerId);
        });

        return true;
    }

    protected function getInputValueByLabel(DOMXPath $xpath, string $labelText): ?string
    {
        // Find label starting with target text (which handles labels with child nodes like spans/small),
        // then lookup the sibling or sibling's sibling input
        $query = "//label[starts-with(normalize-space(.), '{$labelText}')]/following-sibling::div//input | //label[starts-with(normalize-space(.), '{$labelText}')]/following::input[1]";
        $nodes = $xpath->query($query);

        if ($nodes->length > 0) {
            /** @var \DOMElement $inputNode */
            $inputNode = $nodes->item(0);

            return trim($inputNode->getAttribute('value'));
        }

        return null;
    }
}
