<?php

namespace Tests\Feature;

use App\Models\ScrapeSource;
use App\Services\Scraping\RawHarvester;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RawHarvesterTest extends TestCase
{
    use DatabaseTransactions;

    protected string $testStoragePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testStoragePath = storage_path('app/scraping_raw_test_' . uniqid());
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testStoragePath)) {
            File::deleteDirectory($this->testStoragePath);
        }
        parent::tearDown();
    }

    public function test_raw_harvester_creates_snapshot_and_meta(): void
    {
        $harvester = new RawHarvester($this->testStoragePath);

        // Dummy local source
        $source = ScrapeSource::create([
            'name' => 'Test Uni Source',
            'url' => 'file://' . base_path('scrapers/fixtures/sample_mendelu.html'),
            'type' => 'web_mendelu',
            'university' => 'MENDELU',
            'is_active' => true,
        ]);

        $res = $harvester->harvestSource($source, '2026-11-19');

        $this->assertFileExists($res['file_path']);
        $this->assertFileExists($res['meta_path']);
        $this->assertGreaterThan(0, $res['bytes']);

        $meta = json_decode(File::get($res['meta_path']), true);
        $this->assertEquals($source->id, $meta['source_id']);
        $this->assertEquals('Test Uni Source', $meta['source_name']);
        $this->assertNotEmpty($meta['sha256']);
    }

    public function test_raw_harvester_prunes_snapshots_older_than_14_days(): void
    {
        $harvester = new RawHarvester($this->testStoragePath);

        $oldFolder = "{$this->testStoragePath}/2026-10-01";
        $freshFolder = "{$this->testStoragePath}/" . Carbon::now()->format('Y-m-d');

        File::makeDirectory($oldFolder, 0755, true);
        File::put("{$oldFolder}/sample.html", 'test old content');

        File::makeDirectory($freshFolder, 0755, true);
        File::put("{$freshFolder}/sample.html", 'test fresh content');

        $this->assertDirectoryExists($oldFolder);
        $this->assertDirectoryExists($freshFolder);

        $pruned = $harvester->pruneOldSnapshots(14);

        $this->assertEquals(1, $pruned);
        $this->assertDirectoryDoesNotExist($oldFolder);
        $this->assertDirectoryExists($freshFolder);
    }

    public function test_artisan_harvest_raw_command(): void
    {
        $source = ScrapeSource::firstOrCreate(
            ['name' => 'Test Fixture iCal Source'],
            [
                'url' => 'file://' . base_path('scrapers/fixtures/sample_student_events.ics'),
                'type' => 'web_ical',
                'university' => 'OTHER',
                'is_active' => true,
            ]
        );

        $this->artisan('events:harvest-raw', ['source_id' => $source->id])
            ->expectsOutputToContain('Surový sběr dat dokončen')
            ->assertSuccessful();
    }
}
