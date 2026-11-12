<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventBannerService
{
    /**
     * Patterns for identifying high-priority event posters / flyers.
     */
    protected const POSTER_PATTERNS = [
        'plakat', 'plakát', 'poster', 'flyer', 'letak', 'leták',
        'pozvanka', 'pozvánka', 'invitation', 'a4', 'program_plakat',
        'vystava', 'výstava', 'grafika', 'vizual', 'vizuál', 'lineup', 'line-up'
    ];

    /**
     * Patterns for identifying event banners / hero images.
     */
    protected const BANNER_PATTERNS = [
        'banner', 'hero', 'header', 'cover', 'featured', 'lead-image',
        'box-event__img', 'b-term__img', 'event-image', 'headline'
    ];

    /**
     * Patterns for discarding unwanted images (logos, icons, trackers, social, site-wide templates).
     */
    protected const EXCLUDE_PATTERNS = [
        'logo', 'icon', 'ico', 'avatar', 'user', 'author', 'share',
        'social', 'facebook', 'instagram', 'twitter', 'linkedin',
        'arrow', 'chevron', 'pixel', 'tracker', 'ad-', 'banner-ad',
        'footer', 'cookie', 'flag', 'search', 'svg', '1x1',
        // Site-wide generic portal placeholders & fallback og images
        'dsc_1382_fb', 'adsc_7448', '3128009', 'default_og', 'default-og',
        'og-default', 'share-fb', 'muni_default', 'unob_default', 'vut_default',
        'mendelu_default', 'placeholder', 'hero_box_', 'openday', 'open-day',
        'site-header', 'campus', 'budova', 'layout', 'portal'
    ];

    /**
     * Enriches an event with an image scraped from its source_url using the priority:
     * poster > banner > article > stock
     */
    public function enrichEvent(Event $event, bool $force = false): bool
    {
        if (!$force && !empty($event->cover_image) && !empty($event->cover_image_type) && $event->cover_image_type !== 'stock') {
            return false;
        }

        if (empty($event->source_url)) {
            return false;
        }

        // Skip non-HTTP source URLs (like file fixtures in testing unless mock handled)
        if (!str_starts_with($event->source_url, 'http://') && !str_starts_with($event->source_url, 'https://')) {
            return false;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 BrnoEvents/1.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'cs,en;q=0.9',
                ])
                ->get($event->source_url);

            if (!$response->successful()) {
                return false;
            }

            return $this->enrichFromHtml($event, $response->body(), $event->source_url);
        } catch (\Throwable $e) {
            Log::warning("EventBannerService: Failed to fetch source_url for event {$event->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enriches an event using pre-fetched HTML content.
     */
    public function enrichFromHtml(Event $event, string $html, string $baseUrl): bool
    {
        $candidates = $this->extractCandidates($html, $baseUrl);

        // Priority 1: Poster
        foreach ($candidates['poster'] as $url) {
            $actualType = 'poster';
            $path = $this->downloadAndStore($url, 'poster', $event->id, $actualType);
            if ($path) {
                $event->update([
                    'cover_image' => $path,
                    'cover_image_type' => $actualType,
                    'original_image_url' => $url,
                ]);
                return true;
            }
        }

        // Priority 2: Banner
        foreach ($candidates['banner'] as $url) {
            $actualType = 'banner';
            $path = $this->downloadAndStore($url, 'banner', $event->id, $actualType);
            if ($path) {
                $event->update([
                    'cover_image' => $path,
                    'cover_image_type' => $actualType,
                    'original_image_url' => $url,
                ]);
                return true;
            }
        }

        // Priority 3: Article image
        foreach ($candidates['article'] as $url) {
            $actualType = 'article';
            $path = $this->downloadAndStore($url, 'article', $event->id, $actualType);
            if ($path) {
                $event->update([
                    'cover_image' => $path,
                    'cover_image_type' => $actualType,
                    'original_image_url' => $url,
                ]);
                return true;
            }
        }

        // Fallback: If event already has a remote cover_image from listing, attempt to download it
        if (!empty($event->cover_image) && (str_starts_with($event->cover_image, 'http://') || str_starts_with($event->cover_image, 'https://'))) {
            $fallbackUrl = $this->cleanCdnUrl($event->cover_image);
            $lower = strtolower($fallbackUrl);
            $fallbackType = $this->matchesAnyPattern($lower, self::POSTER_PATTERNS) ? 'poster' : 'banner';
            $actualType = $fallbackType;
            $path = $this->downloadAndStore($fallbackUrl, $fallbackType, $event->id, $actualType);
            if ($path) {
                $event->update([
                    'cover_image' => $path,
                    'cover_image_type' => $actualType,
                    'original_image_url' => $fallbackUrl,
                ]);
                return true;
            }
        }

        // Priority 4: Fallback to stock
        if (empty($event->cover_image) || str_starts_with($event->cover_image, 'http')) {
            $event->update([
                'cover_image' => null,
                'cover_image_type' => 'stock',
            ]);
        }

        return false;
    }

    /**
     * Extracts and categorizes image candidates from HTML by priority.
     *
     * @return array{poster: string[], banner: string[], article: string[]}
     */
    public function extractCandidates(string $html, string $baseUrl): array
    {
        $posters = [];
        $banners = [];
        $articles = [];

        if (empty(trim($html))) {
            return ['poster' => [], 'banner' => [], 'article' => []];
        }

        // 1. Check Open Graph & Twitter meta tags (Tier 2: Banner candidates)
        if (preg_match('/<meta[^>]+property=[\'"]og:image[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m) ||
            preg_match('/<meta[^>]+content=[\'"]([^\'"]+)[\'"][^>]+property=[\'"]og:image[\'"]/i', $html, $m)) {
            $ogUrl = $this->resolveUrl(html_entity_decode($m[1], ENT_QUOTES), $baseUrl);
            if ($this->isValidCandidateUrl($ogUrl)) {
                $banners[] = $ogUrl;
            }
        }

        if (preg_match('/<meta[^>]+name=[\'"]twitter:image[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m) ||
            preg_match('/<meta[^>]+content=[\'"]([^\'"]+)[\'"][^>]+name=[\'"]twitter:image[\'"]/i', $html, $m)) {
            $twUrl = $this->resolveUrl(html_entity_decode($m[1], ENT_QUOTES), $baseUrl);
            if ($this->isValidCandidateUrl($twUrl) && !in_array($twUrl, $banners)) {
                $banners[] = $twUrl;
            }
        }

        // 2. Check <a> links pointing directly to poster/flyer image files
        if (preg_match_all('/<a[^>]+href=[\'"]([^\'"]+\.(?:jpg|jpeg|png|webp)[^\'"]*)[\'"][^>]*>(.*?)<\/a>/is', $html, $linkMatches, PREG_SET_ORDER)) {
            foreach ($linkMatches as $lm) {
                $href = $lm[1];
                $linkText = strip_tags($lm[2]);
                $combined = mb_strtolower($href . ' ' . $linkText);

                if ($this->matchesAnyPattern($combined, self::POSTER_PATTERNS)) {
                    $fullUrl = $this->resolveUrl(html_entity_decode($href, ENT_QUOTES), $baseUrl);
                    if ($this->isValidCandidateUrl($fullUrl) && !in_array($fullUrl, $posters)) {
                        $posters[] = $fullUrl;
                    }
                }
            }
        }

        // 3. Parse DOM <img> tags
        $libxmlState = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlState);

        $xpath = new \DOMXPath($dom);
        $images = $xpath->query('//img');

        if ($images) {
            foreach ($images as $img) {
                /** @var \DOMElement $img */
                $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: '';
                if (empty($src)) {
                    // Try srcset first URL
                    $srcset = $img->getAttribute('srcset');
                    if ($srcset) {
                        $parts = explode(',', $srcset);
                        $lastPart = trim(end($parts));
                        $src = preg_split('/\s+/', $lastPart)[0] ?? '';
                    }
                }

                if (empty($src)) {
                    continue;
                }

                $fullUrl = $this->resolveUrl(html_entity_decode($src, ENT_QUOTES), $baseUrl);

                // If image is wrapped in <a> linking to full-size original graphic, prefer the full-size target
                if ($img->parentNode instanceof \DOMElement && strtolower($img->parentNode->tagName) === 'a') {
                    $parentHref = $img->parentNode->getAttribute('href');
                    if (!empty($parentHref) && preg_match('/\.(?:jpe?g|png|webp)(?:\?.*)?$/i', $parentHref)) {
                        $parentFullUrl = $this->resolveUrl(html_entity_decode($parentHref, ENT_QUOTES), $baseUrl);
                        if ($this->isValidCandidateUrl($parentFullUrl)) {
                            $fullUrl = $parentFullUrl;
                        }
                    }
                }

                if (!$this->isValidCandidateUrl($fullUrl)) {
                    continue;
                }

                $alt = $img->getAttribute('alt');
                $title = $img->getAttribute('title');
                $class = $img->getAttribute('class');
                $parentClass = $img->parentNode instanceof \DOMElement ? $img->parentNode->getAttribute('class') : '';

                $descriptor = mb_strtolower($src . ' ' . $alt . ' ' . $title . ' ' . $class . ' ' . $parentClass);

                // Ignore obvious logos, avatars, icons
                if ($this->matchesAnyPattern($descriptor, self::EXCLUDE_PATTERNS)) {
                    continue;
                }

                // Check dimensions if specified in attributes
                $w = (int) $img->getAttribute('width');
                $h = (int) $img->getAttribute('height');
                if (($w > 0 && $w < 150) || ($h > 0 && $h < 150)) {
                    continue;
                }

                // Tier 1: Poster
                if ($this->matchesAnyPattern($descriptor, self::POSTER_PATTERNS)) {
                    if (!in_array($fullUrl, $posters)) {
                        $posters[] = $fullUrl;
                    }
                    continue;
                }

                // Tier 2: Banner
                if ($this->matchesAnyPattern($descriptor, self::BANNER_PATTERNS)) {
                    if (!in_array($fullUrl, $banners)) {
                        $banners[] = $fullUrl;
                    }
                    continue;
                }

                // Tier 3: Article image
                if (!in_array($fullUrl, $articles) && !in_array($fullUrl, $banners) && !in_array($fullUrl, $posters)) {
                    $articles[] = $fullUrl;
                }
            }
        }

        // Clean & de-crop CDN URLs if applicable (e.g. cdn.muni.cz query params)
        $posters = array_values(array_unique(array_map([$this, 'cleanCdnUrl'], $posters)));
        $banners = array_values(array_unique(array_map([$this, 'cleanCdnUrl'], $banners)));
        $articles = array_values(array_unique(array_map([$this, 'cleanCdnUrl'], $articles)));

        return [
            'poster' => $posters,
            'banner' => $banners,
            'article' => $articles,
        ];
    }

    /**
     * Downloads an image from a URL, validates format & dimensions, and saves to public storage.
     *
     * @return string|null Relative storage path (e.g. 'events/covers/poster_12_abc.jpg')
     */
    public function downloadAndStore(string $imageUrl, string $type, ?int $eventId = null, ?string &$actualType = null): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 BrnoEvents/1.0',
                    'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                    'Referer' => parse_url($imageUrl, PHP_URL_SCHEME) . '://' . parse_url($imageUrl, PHP_URL_HOST),
                ])
                ->get($imageUrl);

            if (!$response->successful()) {
                return null;
            }

            $bytes = $response->body();
            $byteLength = strlen($bytes);

            // Must be at least 4 KB and at most 15 MB
            if ($byteLength < 4096 || $byteLength > 15 * 1024 * 1024) {
                return null;
            }

            // Inspect image dimensions and MIME type
            $imageInfo = @getimagesizefromstring($bytes);
            if (!$imageInfo) {
                return null;
            }

            [$width, $height, $imageType] = $imageInfo;
            $mime = $imageInfo['mime'] ?? '';

            // Reject SVGs and tiny tracking pixels
            if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                return null;
            }

            // Minimum dimensions: at least 180px width/height for general images
            if ($width < 180 || $height < 150) {
                return null;
            }

            // Aspect ratio classification:
            // If vertical (height > width * 1.15), classify as poster
            // If landscape banner (width >= height * 1.25) and originally categorized as poster, reclassify as banner
            if ($height > ($width * 1.15)) {
                $detectedType = 'poster';
            } elseif ($width >= ($height * 1.25) && $type === 'poster') {
                $detectedType = 'banner';
            } else {
                $detectedType = $type;
            }
            $actualType = $detectedType;

            $ext = match ($imageType) {
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_WEBP => 'webp',
                default => 'jpg',
            };

            $urlHash = substr(md5($imageUrl), 0, 8);
            $eventPrefix = $eventId ? "{$eventId}_" : "";
            $typePrefix = match ($detectedType) {
                'poster' => 'poster',
                'banner' => 'banner',
                'article' => 'article',
                default => 'cover',
            };

            $filename = "{$typePrefix}_{$eventPrefix}{$urlHash}.{$ext}";
            $relativePath = "events/covers/{$filename}";

            Storage::disk('public')->put($relativePath, $bytes);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::info("EventBannerService: Failed to download {$imageUrl}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Resolves relative URLs to absolute URLs.
     */
    public function resolveUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);
        if (empty($url)) {
            return '';
        }

        if (str_starts_with($url, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';

        if (str_starts_with($url, '/')) {
            return "{$scheme}://{$host}{$port}{$url}";
        }

        $basePath = $baseParts['path'] ?? '/';
        $dir = rtrim(dirname($basePath), '/');

        return "{$scheme}://{$host}{$port}{$dir}/{$url}";
    }

    /**
     * Cleans CDN URLs (like cdn.muni.cz) by stripping forced crop query params to get uncropped high-res originals.
     */
    public function cleanCdnUrl(string $url): string
    {
        if (str_contains($url, 'cdn.muni.cz/media/')) {
            // Strip mode=crop&width=... query params to download high-resolution uncropped original
            $clean = preg_replace('/\?(?:mode=crop|center=[^&]+|heightratio=[^&]+|rnd=[^&]+|width=\d+|height=\d+)(&(?:mode=crop|center=[^&]+|heightratio=[^&]+|rnd=[^&]+|width=\d+|height=\d+))*/i', '', $url);
            return rtrim($clean, '?&');
        }

        return $url;
    }

    /**
     * Checks if a candidate URL is valid and points to an image.
     */
    protected function isValidCandidateUrl(string $url): bool
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $lower = strtolower($url);

        // Reject data URLs, SVGs, or obvious tracking scripts
        if (str_starts_with($lower, 'data:') || str_ends_with($lower, '.svg') || str_contains($lower, '.svg?')) {
            return false;
        }

        // Reject excluded names in URL
        if ($this->matchesAnyPattern($lower, self::EXCLUDE_PATTERNS)) {
            return false;
        }

        return true;
    }

    /**
     * Helper to test if a string matches any word in a patterns array.
     */
    protected function matchesAnyPattern(string $haystack, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($haystack, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
