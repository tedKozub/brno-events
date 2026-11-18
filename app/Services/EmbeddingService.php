<?php

namespace App\Services;

use App\Models\Event;
use App\Models\StudentPlace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class EmbeddingService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.embedding.url', 'http://127.0.0.1:8088');
    }

    /**
     * Získá embedding pro jeden text (s 24h cache pro identické vyhledávací dotazy).
     */
    public function getEmbedding(string $text): ?array
    {
        $clean = trim($text);
        if (empty($clean) || mb_strlen($clean) < 3) {
            return null;
        }

        if (mb_strlen($clean) > 300) {
            $clean = mb_substr($clean, 0, 300);
        }

        $cacheKey = 'emb_' . md5(mb_strtolower($clean));

        // Pokud je v cache, vrátíme ihned bez čerpání rate limitu
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Antispam ochrana: omezit počet nových embedding dotazů na IP (max 60 za minutu)
        $clientIp = request()->ip() ?? 'cli';
        $rateLimitKey = 'embed_rate:' . $clientIp;

        if ($clientIp !== 'cli' && RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
            Log::info("Embedding rate limit exceeded for IP: {$clientIp}");
            return null;
        }

        if ($clientIp !== 'cli') {
            RateLimiter::hit($rateLimitKey, 60);
        }

        return Cache::remember($cacheKey, 86400, function () use ($clean) {
            try {
                $response = Http::timeout(2.0)->post("{$this->endpoint}/embed", [
                    'text' => $clean,
                ]);

                if ($response->successful()) {
                    return $response->json('vector');
                }
            } catch (\Throwable $e) {
                Log::warning('Embedding service unavailable: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Dávkové vygenerování embeddingů pro více textů najednou.
     */
    public function getBatchEmbeddings(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        try {
            $response = Http::timeout(15.0)->post("{$this->endpoint}/embed-batch", [
                'texts' => array_values($texts),
            ]);

            if ($response->successful()) {
                return $response->json('vectors', []);
            }
        } catch (\Throwable $e) {
            Log::error('Batch embedding failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Sestaví reprezentativní sémantický text události pro embedding.
     */
    public function buildEventSearchText(Event $event): string
    {
        $parts = [
            $event->title,
        ];

        if ($event->category) {
            $parts[] = 'Kategorie: ' . $event->category->name;
        }

        if ($event->organization) {
            $parts[] = 'Pořadatel: ' . $event->organization->name . ' (' . $event->organization->university . ')';
        }

        $loc = $event->location_display;
        if (!empty($loc)) {
            $parts[] = 'Místo: ' . $loc;
        }

        $desc = $event->short_description ?: strip_tags($event->description ?? '');
        if (!empty($desc)) {
            $parts[] = mb_substr(trim($desc), 0, 500);
        }

        return implode('. ', array_filter($parts));
    }

    /**
     * Vygeneruje a uloží embedding pro konkrétní událost.
     */
    public function updateEventEmbedding(Event $event): bool
    {
        $text = $this->buildEventSearchText($event);
        $vector = $this->getEmbedding($text);

        if ($vector && is_array($vector)) {
            $event->updateQuietly(['embedding' => $vector]);
            return true;
        }

        return false;
    }

    /**
     * Sestaví reprezentativní sémantický text místa pro embedding.
     */
    public function buildPlaceSearchText(StudentPlace $place): string
    {
        $parts = [
            $place->name,
            $place->address,
            $place->description,
        ];

        if (!empty($place->highlight)) {
            $parts[] = $place->highlight;
        }

        $tags = is_array($place->tags) ? $place->tags : [];
        if (!empty($tags)) {
            $parts[] = 'Tagy: ' . implode(', ', $tags);
        }

        if ($place->category) {
            $parts[] = 'Kategorie: ' . $place->category->name;
        }

        return implode('. ', array_filter($parts));
    }

    /**
     * Vygeneruje a uloží embedding pro konkrétní místo.
     */
    public function updatePlaceEmbedding(StudentPlace $place): bool
    {
        $text = $this->buildPlaceSearchText($place);
        $vector = $this->getEmbedding($text);

        if ($vector && is_array($vector)) {
            $place->updateQuietly(['embedding' => json_encode($vector)]);
            Cache::forget('student_place_emb_' . $place->id);
            Cache::forget('student_places_all');
            return true;
        }

        return false;
    }

    /**
     * Bleskový výpočet kosinové podobnosti mezi dvěma vektory stejné délky.
     */
    public static function cosineSimilarity(array $vecA, array $vecB): float
    {
        $count = count($vecA);
        if ($count === 0 || $count !== count($vecB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $a = $vecA[$i];
            $b = $vecB[$i];
            $dotProduct += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
