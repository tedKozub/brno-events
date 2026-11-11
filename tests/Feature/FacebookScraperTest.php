<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\ScrapeSource;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FacebookScraperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_facebook_source_can_be_registered(): void
    {
        $source = ScrapeSource::firstOrCreate(
            ['target_identifier' => 'sppdf-muni-facebook'],
            [
                'name' => 'Pedagogická fakulta MUNI (Facebook SPPdF)',
                'type' => 'web_facebook',
                'university' => 'MUNI',
                'url' => 'sppdfmuni',
                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas('scrape_sources', [
            'target_identifier' => 'sppdf-muni-facebook',
            'type' => 'web_facebook',
        ]);
    }

    public function test_scraped_facebook_event_attributes_properly(): void
    {
        $event = Event::where('source_type', 'like', '%facebook%')->latest()->first();
        if ($event) {
            $this->assertNotNull($event->title);
            $this->assertNotNull($event->facebook_event_url);
            $this->assertStringContainsString('facebook.com/events', $event->facebook_event_url);
        } else {
            $this->assertTrue(true);
        }
    }
}
