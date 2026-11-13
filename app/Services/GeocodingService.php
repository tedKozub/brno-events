<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    public const DEFAULT_BRNO_LAT = 49.1951;
    public const DEFAULT_BRNO_LNG = 16.6068;

    /**
     * Geocodes an address to latitude and longitude coordinates.
     * Defaults to Brno city center if not resolvable.
     *
     * @return array{lat: float, lng: float}
     */
    public static function geocodeAddress(string $address): array
    {
        $address = trim($address);
        if (empty($address)) {
            return [
                'lat' => self::DEFAULT_BRNO_LAT,
                'lng' => self::DEFAULT_BRNO_LNG,
            ];
        }

        // Pokud adresa neobsahuje Brno, přidáme ho pro přesnější lokalizaci
        $searchQuery = $address;
        if (!str_contains(mb_strtolower($searchQuery), 'brno')) {
            $searchQuery .= ', Brno';
        }

        $cacheKey = 'geocode_' . md5(mb_strtolower($searchQuery));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($searchQuery) {
            if (app()->environment('testing') && !DiscordNotifier::isHttpFaked()) {
                return [
                    'lat' => self::DEFAULT_BRNO_LAT,
                    'lng' => self::DEFAULT_BRNO_LNG,
                ];
            }

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'BrnoAkce-Platform/1.0 (info@brnoakce.tadeaskozub.com)',
                ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $searchQuery,
                    'format' => 'json',
                    'limit' => 1,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                        return [
                            'lat' => round((float) $data[0]['lat'], 7),
                            'lng' => round((float) $data[0]['lon'], 7),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Geocoding failed for '{$searchQuery}': " . $e->getMessage());
            }

            return [
                'lat' => self::DEFAULT_BRNO_LAT,
                'lng' => self::DEFAULT_BRNO_LNG,
            ];
        });
    }
}
