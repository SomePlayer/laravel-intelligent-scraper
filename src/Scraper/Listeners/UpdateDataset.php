<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;

class UpdateDataset implements ShouldQueue
{
    const DATASET_AMOUNT_LIMIT = 100;

    public function handle(Scraped $event)
    {
        $datasets = ScrapedDataset::where('url', $event->scrapeRequest->url)->get();

        if ($datasets->isEmpty()) {
            $this->addDataset($event);
        } else {
            $this->updateDataset($datasets->first(), $event);
        }
    }

    private function addDataset(Scraped $event)
    {
        $scraperDatasets = ScrapedDataset::withType($event->scrapeRequest->type);
        if (self::DATASET_AMOUNT_LIMIT <= $scraperDatasets->count()) {
            $scraperDatasets->orderBy('updated_at', 'desc')->first()->delete();
        }

        ScrapedDataset::create(
            [
                'url'  => $event->scrapeRequest->url,
                'type' => $event->scrapeRequest->type,
                'data' => $event->data,
            ]
        );
    }

    private function updateDataset(ScrapedDataset $dataset, Scraped $event)
    {
        $dataset->data = $event->data;

        $dataset->save();
    }
}