<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\ScrapeSource;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InstagramScraperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_instagram_source_can_be_registered(): void
    {
        $source = ScrapeSource::firstOrCreate(
            ['target_identifier' => 'sppdf-muni-instagram'],
            [
                'name' => 'Spolek přátel PdF MUNI (Instagram)',
                'type' => 'web_instagram',
                'university' => 'MUNI',
                'url' => 'sppdf_muni',
                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas('scrape_sources', [
            'target_identifier' => 'sppdf-muni-instagram',
            'type' => 'web_instagram',
        ]);
    }
}
