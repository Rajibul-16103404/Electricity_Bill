<?php

namespace App\Console\Commands;

use App\Models\ConsumerId;
use App\Services\NescoScraperService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('nesco:scrape')]
#[Description('Scrape and sync profile, recharges, monthly usages, and daily reports for all registered consumers')]
class ScrapeNescoData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NescoScraperService $scraperService): void
    {
        $consumers = ConsumerId::all();

        $this->info("Starting NESCO scrape and sync for {$consumers->count()} consumer(s)...");

        foreach ($consumers as $consumer) {
            $this->info("Scraping consumer ID: {$consumer->consumer_id}...");

            $success = $scraperService->scrapeAndSync($consumer);

            if ($success) {
                $this->info("Successfully synced consumer ID: {$consumer->consumer_id}");
            } else {
                $this->error("Failed to sync consumer ID: {$consumer->consumer_id}");
            }
        }

        $this->info('NESCO scrape and sync completed.');
    }
}
