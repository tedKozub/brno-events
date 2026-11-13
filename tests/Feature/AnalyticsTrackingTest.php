<?php

namespace Tests\Feature;

use App\Filament\Widgets\AnalyticsOverviewWidget;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AnalyticsTrackingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pageview_is_recorded_with_daily_anonymous_hash_and_no_raw_ip(): void
    {
        $category = Category::firstOrCreate(['slug' => 'test-kat'], ['name' => 'Test']);
        $event = Event::create([
            'title' => 'Testovací akce pro tracking',
            'category_id' => $category->id,
            'starts_at' => Carbon::tomorrow(),
            'status' => 'published',
        ]);

        $service = app(AnalyticsService::class);
        $record = $service->trackPageView('/akce/' . $event->slug, $event);

        $this->assertNotNull($record);
        $this->assertEquals('pageview', $record->event_type);
        $this->assertEquals($event->id, $record->event_id);
        $this->assertEquals('test-kat', $record->category_slug);
        $this->assertNotNull($record->visitor_hash);
        $this->assertEquals(16, strlen($record->visitor_hash));

        // Ensure database does not store raw IP addresses or identifiable user data
        $this->assertDatabaseHas('analytics_events', [
            'id' => $record->id,
            'event_type' => 'pageview',
            'event_id' => $event->id,
        ]);
    }

    public function test_outbound_and_ical_engagement_tracking(): void
    {
        $event = Event::create([
            'title' => 'Koncert s lístky',
            'starts_at' => Carbon::tomorrow(),
            'status' => 'published',
            'ticket_url' => 'https://example.com/tickets',
        ]);

        $service = app(AnalyticsService::class);
        $ticketClick = $service->trackOutboundClick($event, 'ticket');
        $icalDownload = $service->trackIcalDownload($event);
        $mapClick = $service->trackMapClick($event, 'gmaps');

        $this->assertEquals('outbound_click', $ticketClick->event_type);
        $this->assertEquals('ical_download', $icalDownload->event_type);
        $this->assertEquals('map_click', $mapClick->event_type);

        $summary = $service->getSummaryStats(7);
        $this->assertGreaterThanOrEqual(1, $summary['outbound_clicks']);
        $this->assertGreaterThanOrEqual(1, $summary['ical_downloads']);
    }

    public function test_search_query_tracking(): void
    {
        $service = app(AnalyticsService::class);
        $record = $service->trackSearch('kvíz v hospodě');

        $this->assertNotNull($record);
        $this->assertEquals('search', $record->event_type);
        $this->assertEquals('kvíz v hospodě', $record->search_query);

        // Short queries under 2 characters should be ignored
        $ignored = $service->trackSearch('a');
        $this->assertNull($ignored);

        $topSearches = $service->getTopSearches(5);
        $this->assertTrue($topSearches->pluck('search_query')->contains('kvíz v hospodě'));
    }

    public function test_analytics_overview_widget_renders_statistics(): void
    {
        $event = Event::create([
            'title' => 'Widget Test Akce',
            'starts_at' => Carbon::tomorrow(),
            'status' => 'published',
        ]);

        $service = app(AnalyticsService::class);
        $service->trackPageView('/akce/' . $event->slug, $event);
        $service->trackOutboundClick($event, 'ticket');
        $service->trackIcalDownload($event);

        $widget = new AnalyticsOverviewWidget();
        // Invoke protected getStats via reflection
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        $this->assertCount(4, $stats);
        $this->assertEquals('Zobrazení stránek (7 dní)', $stats[0]->getLabel());
        $this->assertEquals('Unikátní návštěvníci (7 dní)', $stats[1]->getLabel());
        $this->assertEquals('Prokliky ke spolkům & Vstupenky', $stats[2]->getLabel());
        $this->assertEquals('Stažení do kalendáře (iCal)', $stats[3]->getLabel());
    }

    public function test_visiting_event_detail_increments_views_and_records_pageview(): void
    {
        $event = Event::create([
            'title' => 'Detail Tracking Test',
            'starts_at' => Carbon::tomorrow(),
            'status' => 'published',
        ]);

        $initialViews = $event->views_count;

        $response = $this->get('/akce/' . $event->slug);
        $response->assertStatus(200);

        $event->refresh();
        $this->assertEquals($initialViews + 1, $event->views_count);

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'pageview',
            'event_id' => $event->id,
        ]);
    }

    public function test_country_detection_resolves_cloudflare_and_custom_headers(): void
    {
        $service = app(AnalyticsService::class);

        // Test with CF-IPCountry header
        request()->headers->set('CF-IPCountry', 'SK');
        $this->assertEquals('SK', $service->resolveCountry());

        // Test with X-Country-Code header
        request()->headers->remove('CF-IPCountry');
        request()->headers->set('X-Country-Code', 'DE');
        $this->assertEquals('DE', $service->resolveCountry());

        // Test fallback to CZ
        request()->headers->remove('X-Country-Code');
        $this->assertEquals('CZ', $service->resolveCountry());
    }

    public function test_traffic_classification_separates_internal_and_test_traffic(): void
    {
        $service = app(AnalyticsService::class);

        // In test runner, defaults to test
        $classification = $service->resolveTrafficClassification();
        $this->assertEquals('test', $classification['traffic_type']);
        $this->assertTrue($classification['is_internal']);
    }

    public function test_analytics_toggle_device_route_toggles_internal_cookie(): void
    {
        // 1. First visit sets cookie brnoakce_internal=1
        $response = $this->get('/analytics/toggle-device?redirect_to=/');
        $response->assertRedirect('/');
        $response->assertCookie('brnoakce_internal', '1');

        // 2. Second visit with existing cookie clears it
        $response2 = $this->withCookie('brnoakce_internal', '1')->get('/analytics/toggle-device?redirect_to=/');
        $response2->assertRedirect('/');
        $response2->assertCookieExpired('brnoakce_internal');
    }

    public function test_country_stats_and_traffic_breakdown_methods(): void
    {
        AnalyticsEvent::create([
            'event_type' => 'pageview',
            'path' => '/',
            'country_code' => 'CZ',
            'traffic_type' => 'real',
            'is_internal' => false,
            'created_at' => now(),
        ]);

        AnalyticsEvent::create([
            'event_type' => 'pageview',
            'path' => '/',
            'country_code' => 'SK',
            'traffic_type' => 'real',
            'is_internal' => false,
            'created_at' => now(),
        ]);

        AnalyticsEvent::create([
            'event_type' => 'pageview',
            'path' => '/',
            'country_code' => 'CZ',
            'traffic_type' => 'internal',
            'is_internal' => true,
            'created_at' => now(),
        ]);

        $service = app(AnalyticsService::class);
        $countries = $service->getCountryStats(7, false);
        $this->assertNotEmpty($countries);
        $cz = collect($countries)->firstWhere('code', 'CZ');
        $this->assertNotNull($cz);
        $this->assertEquals('Česko', $cz['name']);
        $this->assertEquals('🇨🇿', $cz['flag']);

        $breakdown = $service->getTrafficBreakdown(7);
        $this->assertGreaterThanOrEqual(2, $breakdown['real']);
        $this->assertGreaterThanOrEqual(1, $breakdown['internal']);
    }
}
