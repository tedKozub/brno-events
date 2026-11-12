<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /**
     * Records a privacy-friendly, cookieless analytics event.
     */
    public function track(string $eventType, array $payload = []): ?AnalyticsEvent
    {
        try {
            $deviceType = $this->resolveDeviceType();
            $visitorHash = $this->generateDailyVisitorHash();
            $referrerHost = $this->resolveReferrerHost();

            return AnalyticsEvent::create([
                'event_type' => $eventType,
                'event_id' => $payload['event_id'] ?? null,
                'path' => $payload['path'] ?? request()->path(),
                'category_slug' => $payload['category_slug'] ?? null,
                'university' => $payload['university'] ?? null,
                'search_query' => isset($payload['search_query']) ? mb_substr(trim($payload['search_query']), 0, 128) : null,
                'device_type' => $deviceType,
                'visitor_hash' => $visitorHash,
                'referrer_host' => $referrerHost,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AnalyticsService::track failed: ' . $e->getMessage());
            return null;
        }
    }

    public function trackPageView(string $path, ?Event $event = null): ?AnalyticsEvent
    {
        return $this->track('pageview', [
            'path' => $path,
            'event_id' => $event?->id,
            'category_slug' => $event?->category?->slug,
            'university' => $event?->organization?->university,
        ]);
    }

    public function trackOutboundClick(Event $event, string $targetType = 'ticket'): ?AnalyticsEvent
    {
        return $this->track('outbound_click', [
            'path' => "/akce/{$event->slug}",
            'event_id' => $event->id,
            'category_slug' => $event->category?->slug,
            'university' => $event->organization?->university,
        ]);
    }

    public function trackIcalDownload(Event $event): ?AnalyticsEvent
    {
        return $this->track('ical_download', [
            'path' => "/akce/{$event->slug}",
            'event_id' => $event->id,
            'category_slug' => $event->category?->slug,
            'university' => $event->organization?->university,
        ]);
    }

    public function trackMapClick(Event $event, string $provider = 'gmaps'): ?AnalyticsEvent
    {
        return $this->track('map_click', [
            'path' => "/akce/{$event->slug}",
            'event_id' => $event->id,
            'category_slug' => $event->category?->slug,
            'university' => $event->organization?->university,
        ]);
    }

    public function trackSearch(string $query, int $resultsCount = 0): ?AnalyticsEvent
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return null;
        }

        return $this->track('search', [
            'path' => '/',
            'search_query' => $query,
        ]);
    }

    /**
     * High-level summary metrics for the given period.
     */
    public function getSummaryStats(int $days = 7): array
    {
        $since = now()->subDays($days);

        $pageviews = AnalyticsEvent::where('event_type', 'pageview')
            ->where('created_at', '>=', $since)
            ->count();

        $uniqueVisitors = AnalyticsEvent::where('event_type', 'pageview')
            ->where('created_at', '>=', $since)
            ->distinct('visitor_hash')
            ->count('visitor_hash');

        $outboundClicks = AnalyticsEvent::where('event_type', 'outbound_click')
            ->where('created_at', '>=', $since)
            ->count();

        $icalDownloads = AnalyticsEvent::where('event_type', 'ical_download')
            ->where('created_at', '>=', $since)
            ->count();

        return [
            'pageviews' => $pageviews,
            'unique_visitors' => $uniqueVisitors,
            'outbound_clicks' => $outboundClicks,
            'ical_downloads' => $icalDownloads,
        ];
    }

    /**
     * Daily trend of pageviews for charting.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function getDailyTrend(int $days = 7): array
    {
        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            $count = AnalyticsEvent::where('event_type', 'pageview')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $labels[] = $day->format('d. m.');
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Top events ranked by page views and engagement.
     */
    public function getTopEvents(int $limit = 5): Collection
    {
        return AnalyticsEvent::whereNotNull('event_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('event_id', DB::raw('count(*) as total_views'))
            ->groupBy('event_id')
            ->orderByDesc('total_views')
            ->with(['event.organization', 'event.category'])
            ->limit($limit)
            ->get();
    }

    /**
     * Top search queries students are searching for.
     */
    public function getTopSearches(int $limit = 6): Collection
    {
        return AnalyticsEvent::where('event_type', 'search')
            ->whereNotNull('search_query')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('search_query', DB::raw('count(*) as count'))
            ->groupBy('search_query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Generates a 16-char rotating anonymized visitor hash without persisting IP or UserAgent.
     * The salt changes every calendar day, making cross-day tracking mathematically impossible.
     */
    protected function generateDailyVisitorHash(): string
    {
        $dailySalt = config('app.key', 'brno-events-salt') . '-' . date('Y-m-d');
        $ip = request()->ip() ?? '127.0.0.1';
        $ua = request()->userAgent() ?? 'agent';

        return substr(hash('sha256', "{$dailySalt}:{$ip}:{$ua}"), 0, 16);
    }

    /**
     * Resolves simplified device type.
     */
    protected function resolveDeviceType(): string
    {
        $ua = strtolower(request()->userAgent() ?? '');

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Extracts safe referrer host without tracking parameters or query strings.
     */
    protected function resolveReferrerHost(): ?string
    {
        $referer = request()->header('referer');
        if (empty($referer)) {
            return 'direct';
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (!$host || $host === request()->getHost()) {
            return 'direct';
        }

        return mb_substr($host, 0, 128);
    }
}
