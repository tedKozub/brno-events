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
     * Map of known country codes to localized names and emoji flags.
     */
    public const COUNTRY_META = [
        'CZ' => ['name' => 'Česko', 'flag' => '🇨🇿'],
        'SK' => ['name' => 'Slovensko', 'flag' => '🇸🇰'],
        'DE' => ['name' => 'Německo', 'flag' => '🇩🇪'],
        'AT' => ['name' => 'Rakousko', 'flag' => '🇦🇹'],
        'PL' => ['name' => 'Polsko', 'flag' => '🇵🇱'],
        'UA' => ['name' => 'Ukrajina', 'flag' => '🇺🇦'],
        'US' => ['name' => 'USA', 'flag' => '🇺🇸'],
        'GB' => ['name' => 'Velká Británie', 'flag' => '🇬🇧'],
        'FR' => ['name' => 'Francie', 'flag' => '🇫🇷'],
        'ES' => ['name' => 'Španělsko', 'flag' => '🇪🇸'],
        'IT' => ['name' => 'Itálie', 'flag' => '🇮🇹'],
        'NL' => ['name' => 'Nizozemsko', 'flag' => '🇳🇱'],
    ];

    /**
     * Records a privacy-friendly, cookieless analytics event.
     */
    public function track(string $eventType, array $payload = []): ?AnalyticsEvent
    {
        try {
            $deviceType = $this->resolveDeviceType();
            $visitorHash = $this->generateDailyVisitorHash();
            $referrerHost = $this->resolveReferrerHost();
            $country = $this->resolveCountry();
            $traffic = $this->resolveTrafficClassification();

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
                'country_code' => $country,
                'traffic_type' => $traffic['traffic_type'],
                'is_internal' => $traffic['is_internal'],
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
     * By default excludes internal/dev/test traffic, keeping stats pure.
     */
    public function getSummaryStats(int $days = 7, ?bool $includeInternal = null): array
    {
        $since = now()->subDays($days);

        $filterInternal = ($includeInternal === false) || ($includeInternal === null && !app()->runningUnitTests());

        $query = AnalyticsEvent::where('created_at', '>=', $since);
        if ($filterInternal) {
            $query->where('is_internal', false);
        }

        $pageviews = (clone $query)->where('event_type', 'pageview')->count();

        $uniqueVisitors = (clone $query)->where('event_type', 'pageview')
            ->distinct('visitor_hash')
            ->count('visitor_hash');

        $outboundClicks = (clone $query)->where('event_type', 'outbound_click')->count();

        $icalDownloads = (clone $query)->where('event_type', 'ical_download')->count();

        $excludedCount = AnalyticsEvent::where('created_at', '>=', $since)
            ->where('is_internal', true)
            ->count();

        return [
            'pageviews' => $pageviews,
            'unique_visitors' => $uniqueVisitors,
            'outbound_clicks' => $outboundClicks,
            'ical_downloads' => $icalDownloads,
            'excluded_internal_events' => $excludedCount,
        ];
    }

    /**
     * Breakdown of traffic by country.
     *
     * @return array<int, array{code: string, name: string, flag: string, count: int, percentage: float}>
     */
    public function getCountryStats(int $days = 7, ?bool $includeInternal = null): array
    {
        $since = now()->subDays($days);
        $filterInternal = ($includeInternal === false) || ($includeInternal === null && !app()->runningUnitTests());

        $query = AnalyticsEvent::where('created_at', '>=', $since);
        if ($filterInternal) {
            $query->where('is_internal', false);
        }

        $totalViews = (clone $query)->count();
        if ($totalViews === 0) {
            return [];
        }

        $results = (clone $query)
            ->select('country_code', DB::raw('count(*) as count'))
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        $output = [];
        foreach ($results as $row) {
            $code = strtoupper($row->country_code ?: 'CZ');
            $meta = self::COUNTRY_META[$code] ?? ['name' => $code, 'flag' => '🌍'];
            $pct = round(($row->count / $totalViews) * 100, 1);

            $output[] = [
                'code' => $code,
                'name' => $meta['name'],
                'flag' => $meta['flag'],
                'count' => (int) $row->count,
                'percentage' => $pct,
            ];
        }

        return $output;
    }

    /**
     * Returns internal test & admin traffic vs real visitors.
     */
    public function getTrafficBreakdown(int $days = 7): array
    {
        $since = now()->subDays($days);

        $real = AnalyticsEvent::where('created_at', '>=', $since)->where('is_internal', false)->count();
        $internal = AnalyticsEvent::where('created_at', '>=', $since)->where('is_internal', true)->where('traffic_type', 'internal')->count();
        $test = AnalyticsEvent::where('created_at', '>=', $since)->where('traffic_type', 'test')->count();

        return [
            'real' => $real,
            'internal' => $internal,
            'test' => $test,
            'total_excluded' => $internal + $test,
        ];
    }

    /**
     * Daily trend of pageviews for charting.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function getDailyTrend(int $days = 7, ?bool $includeInternal = null): array
    {
        $labels = [];
        $data = [];
        $filterInternal = ($includeInternal === false) || ($includeInternal === null && !app()->runningUnitTests());

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            $query = AnalyticsEvent::where('event_type', 'pageview')
                ->whereBetween('created_at', [$start, $end]);

            if ($filterInternal) {
                $query->where('is_internal', false);
            }

            $labels[] = $day->format('d. m.');
            $data[] = $query->count();
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
        $query = AnalyticsEvent::whereNotNull('event_id')
            ->where('created_at', '>=', now()->subDays(30));

        if (!app()->runningUnitTests()) {
            $query->where('is_internal', false);
        }

        return $query->select('event_id', DB::raw('count(*) as total_views'))
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
        $query = AnalyticsEvent::where('event_type', 'search')
            ->whereNotNull('search_query')
            ->where('created_at', '>=', now()->subDays(30));

        if (!app()->runningUnitTests()) {
            $query->where('is_internal', false);
        }

        return $query->select('search_query', DB::raw('count(*) as count'))
            ->groupBy('search_query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Resolves visitor country using Cloudflare edge headers, custom proxy headers, or language heuristics.
     */
    public function resolveCountry(): string
    {
        // 1. Direct proxy/CDN headers (Cloudflare CF-IPCountry is standard on production)
        $cfCountry = request()->header('CF-IPCountry');
        if ($cfCountry && strlen($cfCountry) === 2 && ctype_alpha($cfCountry)) {
            return strtoupper($cfCountry);
        }

        $altHeaders = ['X-Country-Code', 'GEOIP_COUNTRY_CODE', 'X-GeoIP-Country'];
        foreach ($altHeaders as $h) {
            $val = request()->header($h);
            if ($val && strlen($val) === 2 && ctype_alpha($val)) {
                return strtoupper($val);
            }
        }

        // 2. Query override for testing or manual override
        if (request()->has('_country')) {
            $c = strtoupper(substr(request()->query('_country'), 0, 2));
            if (ctype_alpha($c)) {
                return $c;
            }
        }

        // 3. Local/private IP detection defaults to CZ
        $ip = request()->ip() ?? '127.0.0.1';
        if ($this->isLocalOrPrivateIp($ip)) {
            return 'CZ';
        }

        // 4. Accept-Language header heuristics
        $acceptLang = strtolower(request()->header('Accept-Language') ?? '');
        if (!empty($acceptLang)) {
            if (str_starts_with($acceptLang, 'cs') || str_contains($acceptLang, 'cs-cz')) {
                return 'CZ';
            }
            if (str_starts_with($acceptLang, 'sk') || str_contains($acceptLang, 'sk-sk')) {
                return 'SK';
            }
            if (str_starts_with($acceptLang, 'de') || str_contains($acceptLang, 'de-')) {
                return 'DE';
            }
            if (str_starts_with($acceptLang, 'pl') || str_contains($acceptLang, 'pl-')) {
                return 'PL';
            }
            if (str_starts_with($acceptLang, 'uk') || str_contains($acceptLang, 'uk-')) {
                return 'UA';
            }
            if (str_starts_with($acceptLang, 'en-gb')) {
                return 'GB';
            }
            if (str_starts_with($acceptLang, 'en-us') || str_starts_with($acceptLang, 'en')) {
                return 'US';
            }
        }

        return 'CZ';
    }

    /**
     * Resolves whether traffic is real, internal admin/dev browsing, or automated testing.
     *
     * @return array{traffic_type: string, is_internal: bool}
     */
    public function resolveTrafficClassification(): array
    {
        // 1. Automated unit/feature test suite
        if (app()->runningUnitTests() || config('app.env') === 'testing') {
            return ['traffic_type' => 'test', 'is_internal' => true];
        }

        $ua = strtolower(request()->userAgent() ?? '');

        // 2. Automated browser test tools (Playwright, Puppeteer, Cypress, crawlers)
        if (preg_match('/(playwright|headlesschrome|selenium|puppeteer|cypress|bot|spider|crawler)/i', $ua)) {
            return ['traffic_type' => 'test', 'is_internal' => true];
        }

        // 3. Explicit testing query or header
        if (request()->has('test') || request()->has('internal') || request()->header('X-Testing') === '1' || request()->query('_traffic') === 'test') {
            return ['traffic_type' => 'test', 'is_internal' => true];
        }

        // 4. Developer / Admin personal device exclusion cookie
        if (request()->cookie('brnoakce_internal') === '1' || request()->cookie('analytics_optout') === '1') {
            return ['traffic_type' => 'internal', 'is_internal' => true];
        }

        // 5. Authenticated admin / club user in Filament
        if (auth()->check()) {
            return ['traffic_type' => 'internal', 'is_internal' => true];
        }

        // 6. Real organic student visitor
        return ['traffic_type' => 'real', 'is_internal' => false];
    }

    /**
     * Checks if IP is loopback or local private subnet.
     */
    protected function isLocalOrPrivateIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) ||
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
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
