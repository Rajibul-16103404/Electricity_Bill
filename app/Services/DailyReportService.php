<?php

namespace App\Services;

use App\Models\ConsumerId;
use App\Models\DailyReport;
use Carbon\Carbon;

class DailyReportService
{
    /**
     * Recalculate all daily reports for a given consumer.
     */
    public function recalculateAll(ConsumerId $consumerId): void
    {
        $reports = DailyReport::where('consumer_id_id', $consumerId->id)
            ->orderBy('date', 'asc')
            ->get();

        $recharges = $consumerId->recharges;

        $prevReport = null;

        foreach ($reports as $report) {
            $rechargeSum = 0.0;
            $reportDate = $report->date instanceof Carbon ? $report->date->toDateString() : $report->date;

            foreach ($recharges as $recharge) {
                if ($recharge->purchase_date) {
                    try {
                        $rechargeDate = Carbon::parse($recharge->purchase_date)->toDateString();

                        if ($prevReport) {
                            $prevDate = $prevReport->date instanceof Carbon ? $prevReport->date->toDateString() : $prevReport->date;
                            if ($rechargeDate > $prevDate && $rechargeDate <= $reportDate) {
                                $rechargeSum += (float) $recharge->total_amount;
                            }
                        } else {
                            if ($rechargeDate <= $reportDate) {
                                $rechargeSum += (float) $recharge->total_amount;
                            }
                        }
                    } catch (\Exception $e) {
                        // Ignore parsing errors for individual recharges
                    }
                }
            }

            $report->recharge_amount = $rechargeSum;

            if ($prevReport) {
                $usage = (float) $prevReport->remaining_balance + $rechargeSum - (float) $report->remaining_balance;
                $report->usage_taka = max(0.0, $usage);
            } else {
                $report->usage_taka = 0.0;
            }

            $report->save();
            $prevReport = $report;
        }
    }
}
