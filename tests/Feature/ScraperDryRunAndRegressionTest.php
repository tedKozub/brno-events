<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ScraperDryRunAndRegressionTest extends TestCase
{
    use DatabaseTransactions;

    private string $pythonBin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($this->pythonBin)) {
            $this->pythonBin = 'python3';
        }
    }

    public function test_python_scraper_unittest_suite_passes(): void
    {
        $result = Process::path(base_path())->run([
            $this->pythonBin,
            '-m', 'unittest',
            'discover',
            '-s', 'scrapers/tests',
            '-v',
        ]);

        $this->assertEquals(
            0,
            $result->exitCode(),
            "Python unittest suite failed:\nSTDOUT:\n{$result->output()}\nSTDERR:\n{$result->errorOutput()}"
        );
        $this->assertStringContainsString('OK', $result->errorOutput() ?: $result->output());
    }

    public function test_python_modules_importable_from_different_working_directories(): void
    {
        $code = <<<'PY'
import sys
# Test importing all scraper parsers
from parsers.ical_parser import parse_ical
from parsers.rss_parser import parse_rss
from parsers.web_parsers import parse_muni, parse_fit_vut, parse_mendelu
from parsers.instagram_parser import extract_event_from_caption
from parsers.facebook_parser import extract_event_from_text
import main
print("IMPORT_SUCCESS")
PY;

        // Test 1: From project root
        $resRoot = Process::path(base_path())->run([
            $this->pythonBin,
            '-c',
            $code,
        ]);
        $this->assertEquals(0, $resRoot->exitCode(), "Failed import from project root:\n{$resRoot->errorOutput()}");
        $this->assertStringContainsString('IMPORT_SUCCESS', $resRoot->output());

        // Test 2: From scrapers subfolder (where main.py is located)
        $resScrapers = Process::path(base_path('scrapers'))->run([
            $this->pythonBin,
            '-c',
            $code,
        ]);
        $this->assertEquals(0, $resScrapers->exitCode(), "Failed import from scrapers folder:\n{$resScrapers->errorOutput()}");
        $this->assertStringContainsString('IMPORT_SUCCESS', $resScrapers->output());
    }

    public function test_main_py_cli_dry_run_for_all_scraper_types(): void
    {
        $types = [
            ['type' => 'web_ical', 'url' => 'sample_student_events.ics'],
            ['type' => 'web_rss', 'url' => 'https://example.com/rss'],
            ['type' => 'web_mendelu', 'url' => 'https://mendelu.cz/akce/'],
            ['type' => 'web_fit_vut', 'url' => 'https://fit.vut.cz/calendar'],
            ['type' => 'web_unob', 'url' => 'https://unob.cz/akce'],
            ['type' => 'web_muni', 'url' => 'https://muni.cz/kalendar'],
            ['type' => 'web_instagram', 'url' => 'sppdf_muni'],
            ['type' => 'web_facebook', 'url' => 'sppdfmuni'],
        ];

        foreach ($types as $t) {
            $res = Process::path(base_path('scrapers'))->run([
                $this->pythonBin,
                base_path('scrapers/main.py'),
                '--url', $t['url'],
                '--type', $t['type'],
                '--dry-run',
            ]);

            $this->assertEquals(
                0,
                $res->exitCode(),
                "Dry-run CLI failed for type {$t['type']}:\n{$res->errorOutput()}"
            );

            $data = json_decode($res->output(), true);
            $this->assertIsArray($data, "Invalid JSON output for {$t['type']}: {$res->output()}");
            $this->assertEquals('success', $data['status'] ?? null);
            $this->assertIsArray($data['events'] ?? null);
            $this->assertNotEmpty($data['events'], "Expected at least 1 mock event in dry-run for {$t['type']}");
        }
    }

    public function test_artisan_events_scrape_dry_run_does_not_mutate_database(): void
    {
        $eventsBefore = Event::count();

        $this->artisan('events:scrape', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN dokončen')
            ->assertSuccessful();

        $eventsAfter = Event::count();
        $this->assertEquals(
            $eventsBefore,
            $eventsAfter,
            "Dry-run must not create or modify events in database!"
        );
    }
}
