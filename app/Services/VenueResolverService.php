<?php

namespace App\Services;

use App\Models\GeocodingCache;
use App\Models\Venue;
use App\Models\VenueAlias;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VenueResolverService
{
    /**
     * Bounding box pro Brno a bezprostřední okolí:
     * Min/Max Latitude: 49.10 to 49.27
     * Min/Max Longitude: 16.50 to 16.73
     */
    public const BRNO_BBOX = [
        'min_lat' => 49.1000,
        'max_lat' => 49.2800,
        'min_lng' => 16.4800,
        'max_lng' => 16.7400,
    ];

    /**
     * Lokální in-memory cache aliasů pro 0ms režii během hromadného scrapování.
     * @var array<string, int> [normalized_alias => venue_id]
     */
    protected static ?array $aliasDictionary = null;

    /**
     * Poslední čas volání geocoding API pro dodržení OSM rate-limitu (1.5 sekundy).
     */
    protected static float $lastGeocodeTime = 0.0;

    /**
     * Separuje specifickou učebnu/místnost/budovu od obecné adresy místa.
     *
     * @param string|null $rawLocation
     * @return array{room: ?string, clean_location: string}
     */
    public function extractRoomAndLocation(?string $rawLocation): array
    {
        if (empty($rawLocation)) {
            return ['room' => null, 'clean_location' => ''];
        }

        $roomSegments = [];
        $workingRaw = trim($rawLocation);

        $roomRegex = '/(učebn|ucebn|místnost|mistnost|posluchárn|poslucharn|budov|sál|sal|aula|patro|\bNP\b|\b[A-Z]\d{1,3}(?:\.\d{1,3})?\b|laboratoř|lab\b)/ui';

        // 1. Zkontrolujeme závorky, např. "Recepce u hlavního vstupu UKB (B22)" -> room: B22, loc: Recepce u hlavního vstupu UKB
        if (preg_match_all('/\(([^)]+)\)/u', $workingRaw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $parenContent = trim($match[1]);
                if (preg_match($roomRegex, $parenContent)) {
                    $roomSegments[] = $parenContent;
                    $workingRaw = str_replace($match[0], '', $workingRaw);
                }
            }
            $workingRaw = trim(preg_replace('/\s+/', ' ', $workingRaw));
        }

        // 2. Pokud lokace obsahuje čárku, např. "FIT VUT, Božetěchova 2, učebna A112"
        $parts = array_map('trim', explode(',', $workingRaw));
        $cleanParts = [];

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }
            if (preg_match($roomRegex, $part)) {
                $roomSegments[] = $part;
            } else {
                $cleanParts[] = $part;
            }
        }

        $roomDetail = !empty($roomSegments) ? implode(', ', array_unique($roomSegments)) : null;
        $cleanLocation = !empty($cleanParts) ? implode(', ', $cleanParts) : $workingRaw;

        return [
            'room' => $roomDetail,
            'clean_location' => trim($cleanLocation),
        ];
    }

    /**
     * Normalizuje vyhledávací řetězec (malá písmena, odstranění diakritiky, interpunkce).
     */
    public function normalizeText(string $text): string
    {
        $ascii = Str::ascii($text);
        $lower = mb_strtolower($ascii);
        return trim(preg_replace('/[^\w\s]/u', ' ', $lower));
    }

    /**
     * Načte a zaindexuje celou slovníkovou databázi aliasů do RAM.
     * @return array<string, int>
     */
    public function getAliasDictionary(): array
    {
        if (self::$aliasDictionary !== null) {
            return self::$aliasDictionary;
        }

        $dict = [];

        // 1. Primární názvy venues a adresy
        $venues = Venue::all(['id', 'name', 'address', 'slug']);
        foreach ($venues as $venue) {
            $dict[$this->normalizeText($venue->name)] = $venue->id;
            $dict[$venue->slug] = $venue->id;
            if ($venue->address) {
                $dict[$this->normalizeText($venue->address)] = $venue->id;
                // Bez čísla popisného nebo orientačního
                $streetOnly = preg_replace('/\s*\d+.*$/u', '', $venue->address);
                if (mb_strlen($streetOnly) >= 5) {
                    $dict[$this->normalizeText($streetOnly)] = $venue->id;
                }
            }
        }

        // 2. Explicitní aliasy z tabulky venue_aliases
        $aliases = VenueAlias::all(['venue_id', 'alias_normalized']);
        foreach ($aliases as $alias) {
            $dict[$alias->alias_normalized] = $alias->venue_id;
        }

        self::$aliasDictionary = $dict;
        return self::$aliasDictionary;
    }

    /**
     * Vyprázdní in-memory cache slovníku (např. po přidání nového místa).
     */
    public static function flushDictionary(): void
    {
        self::$aliasDictionary = null;
    }

    /**
     * Hlavní metoda pro nalezení nebo vytvoření Venue.
     *
     * @param string|null $rawLocation Surový text lokace (např. ze scraperu)
     * @param string|null $university Zkratka univerzity (MUNI, VUT, MENDELU, VETUNI, UNOB)
     * @param string|null $contextText Doplňkový text (název akce, popis pro kontext)
     * @return Venue|null
     */
    public function resolve(?string $rawLocation, ?string $university = null, ?string $contextText = null): ?Venue
    {
        if (empty($rawLocation) && empty($contextText)) {
            return null;
        }

        $combined = trim(($rawLocation ?? '') . ' ' . ($contextText ?? ''));
        $normCombined = $this->normalizeText($combined);

        // 1. Zkontrolujeme, zda se nejedná o místo mimo Brno (např. Lednice, Telč, Praha)
        if ($this->isExplicitlyOutsideBrno($rawLocation, $contextText)) {
            return null;
        }

        // 2. Extrahujeme místnost a očistíme lokaci
        $extracted = $this->extractRoomAndLocation($rawLocation);
        $cleanLoc = $extracted['clean_location'];
        $normClean = $this->normalizeText($cleanLoc);

        $dictionary = $this->getAliasDictionary();

        // 3. Stupeň A: Přesná shoda s aliasem nebo názvem (0ms)
        if (isset($dictionary[$normClean])) {
            return Venue::find($dictionary[$normClean]);
        }

        // 4. Stupeň B: Podřetězcová shoda v aliasovém slovníku
        // Nejprve prohledáme samotnou očistěnou lokaci (normClean), poté doplňkový kontext (normCombined)
        foreach ([$normClean, $normCombined] as $searchTarget) {
            if (empty($searchTarget)) {
                continue;
            }
            $matchedVenueId = null;
            $longestMatchLen = 0;

            foreach ($dictionary as $alias => $vId) {
                $len = mb_strlen($alias);
                if ($len < 3) {
                    continue; // Zkratky kratší než 3 znaky ignorujeme pro volnou podřetězcovou shodu
                }
                if ($len > $longestMatchLen) {
                    $matched = false;
                    if ($len === 3) {
                        // Pro 3-písmenné zkratky (např. ukb, fit, fsi, fss, mzk) striktně vyžadujeme hranici celých slov
                        $pattern = '/\b' . preg_quote($alias, '/') . '\b/u';
                        if (preg_match($pattern, $searchTarget)) {
                            $matched = true;
                        }
                    } else {
                        if (str_contains($searchTarget, $alias)) {
                            $matched = true;
                        }
                    }

                    if ($matched) {
                        $matchedVenueId = $vId;
                        $longestMatchLen = $len;
                    }
                }
            }

            if ($matchedVenueId) {
                return Venue::find($matchedVenueId);
            }
        }

        // 5. Stupeň C: Univerzitní kontextové zálohy
        if ($university) {
            $uniFallback = $this->resolveUniversityFallback($university, $normCombined);
            if ($uniFallback) {
                return $uniFallback;
            }
        }

        // 6. Stupeň D: Geocoding nového místa přes OpenStreetMap / Nominatim
        // Pouze pokud máme reálnou adresu (alespoň 6 znaků a ne obecné slovo jako "Brno")
        if (mb_strlen($cleanLoc) >= 6 && !$this->isGenericBrnoLocation($normClean)) {
            $venue = $this->geocodeAndCreateVenue($cleanLoc);
            if ($venue) {
                // Přidáme původní raw název jako alias pro okamžité budoucí spárování
                $this->registerAlias($venue, $cleanLoc);
                return $venue;
            }
        }

        return null;
    }

    /**
     * Ověří, zda se akce koná prokazatelně mimo Brno.
     */
    public function isExplicitlyOutsideBrno(?string $location, ?string $context): bool
    {
        $text = mb_strtolower(trim(($location ?? '') . ' ' . ($context ?? '')));

        $outsidePatterns = [
            '/\b(lednic[ie]|valtic[ie]|zf\s*mendelu)\b/u',
            '/\b(telč|telci|v\s*telči)\b/u',
            '/\b(praha|v\s*praze|prague)\b/u',
            '/\b(olomouc|ostrava|zlin|zlín|plzen|plzeň)\b/u',
            '/\b(vyškov|vyskov)\b/u',
        ];

        foreach ($outsidePatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                // Výjimka: pokud je zmíněna např. "Pražská" ulice v Brně
                if (str_contains($text, 'pražská') || str_contains($text, 'prazska')) {
                    continue;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Určí, zda jde o příliš obecné označení ("Brno", "Brno centrum", "Online").
     */
    protected function isGenericBrnoLocation(string $normalized): bool
    {
        $generic = [
            'brno', 'brno centrum', 'brno stred', 'brno mesto',
            'online', 'webinar', 'zoom', 'teams', 'youtube',
            'ceska republika', 'univerzita', 'fakulta',
        ];

        return in_array($normalized, $generic, true);
    }

    /**
     * Konkrétní zálohy dle univerzity pro případy, kdy chybí přesná budova.
     */
    protected function resolveUniversityFallback(string $university, string $normCombined): ?Venue
    {
        $uni = strtoupper(trim($university));

        return match ($uni) {
            'MENDELU' => Venue::where('slug', 'areal-mendelu-zemedelska')->first(),
            'VETUNI' => Venue::where('slug', 'kampus-vetuni-palackeho')->first(),
            'UNOB' => Venue::where('slug', 'kasarna-cerna-pole-unob')->first(),
            default => null,
        };
    }

    /**
     * Vyhledá souřadnice adresy přes OpenStreetMap Nominatim s rate-limitingem a trvalou cache.
     *
     * @param string $address
     * @return array{lat: float, lng: float, osm_place_id: int, address: string, display_name: string}|null
     */
    public function geocode(string $address): ?array
    {
        $queryHash = GeocodingCache::hashQuery($address);

        // 1. Trvalá cache z DB
        $cached = GeocodingCache::find($queryHash);
        if ($cached) {
            if ($cached->latitude && $cached->longitude) {
                return [
                    'lat' => $cached->latitude,
                    'lng' => $cached->longitude,
                    'osm_place_id' => $cached->osm_place_id,
                    'address' => $cached->formatted_address,
                    'display_name' => $cached->raw_query,
                ];
            }
            return null; // Dříve ověřeno jako nenalezené
        }

        // 2. Dodržení Nominatim rate-limitu (minimálně 1.5 s mezi dotazy)
        $now = microtime(true);
        $timeSinceLast = $now - self::$lastGeocodeTime;
        if ($timeSinceLast < 1.5) {
            usleep((int) ((1.5 - $timeSinceLast) * 1_000_000));
        }
        self::$lastGeocodeTime = microtime(true);

        // 3. Volání Nominatim API
        try {
            $cleanQuery = $address;
            if (!str_contains(mb_strtolower($cleanQuery), 'brno')) {
                $cleanQuery .= ', Brno';
            }

            $response = Http::withHeaders([
                'User-Agent' => 'BrnoEventsBot/1.0 (admin@livebrno.tadeaskozub.com)',
                'Accept' => 'application/json',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $cleanQuery,
                'format' => 'jsonv2',
                'limit' => 1,
                'addressdetails' => 1,
                'viewbox' => sprintf('%f,%f,%f,%f', self::BRNO_BBOX['min_lng'], self::BRNO_BBOX['max_lat'], self::BRNO_BBOX['max_lng'], self::BRNO_BBOX['min_lat']),
                'bounded' => 1,
            ]);

            if (!$response->successful()) {
                Log::warning("Nominatim API error ({$response->status()}): {$response->body()}");
                return null;
            }

            $data = $response->json();
            if (empty($data) || !isset($data[0])) {
                // Uložíme negativní výsledek do cache, ať nezkoušíme znovu
                GeocodingCache::create([
                    'query_hash' => $queryHash,
                    'raw_query' => $address,
                    'latitude' => null,
                    'longitude' => null,
                ]);
                return null;
            }

            $first = $data[0];
            $lat = (float) $first['lat'];
            $lng = (float) $first['lon'];
            $placeId = isset($first['place_id']) ? (int) $first['place_id'] : null;
            $displayName = $first['display_name'] ?? $address;

            // Ověříme, že výsledek skutečně spadá do Brna
            if (!$this->isWithinBrnoBounds($lat, $lng)) {
                GeocodingCache::create([
                    'query_hash' => $queryHash,
                    'raw_query' => $address,
                    'latitude' => null,
                    'longitude' => null,
                ]);
                return null;
            }

            // Uložíme úspěšný výsledek do cache
            GeocodingCache::create([
                'query_hash' => $queryHash,
                'raw_query' => $address,
                'latitude' => $lat,
                'longitude' => $lng,
                'osm_place_id' => $placeId,
                'osm_type' => $first['osm_type'] ?? null,
                'formatted_address' => $displayName,
                'raw_response' => json_encode($first),
            ]);

            return [
                'lat' => $lat,
                'lng' => $lng,
                'osm_place_id' => $placeId,
                'address' => $displayName,
                'display_name' => $displayName,
            ];
        } catch (\Throwable $e) {
            Log::error("Geocoding exception for '{$address}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Zkontroluje, zda jsou souřadnice uvnitř Brna.
     */
    public function isWithinBrnoBounds(float $lat, float $lng): bool
    {
        return $lat >= self::BRNO_BBOX['min_lat']
            && $lat <= self::BRNO_BBOX['max_lat']
            && $lng >= self::BRNO_BBOX['min_lng']
            && $lng <= self::BRNO_BBOX['max_lng'];
    }

    /**
     * Provede geocoding a pokud je adresa platná, vytvoří nebo spáruje Venue.
     */
    protected function geocodeAndCreateVenue(string $cleanLocation): ?Venue
    {
        $geo = $this->geocode($cleanLocation);
        if (!$geo) {
            return null;
        }

        // 1. Zkontrolujeme, zda už nemáme Venue se stejným osm_place_id
        if ($geo['osm_place_id']) {
            $existing = Venue::where('osm_place_id', $geo['osm_place_id'])->first();
            if ($existing) {
                return $existing;
            }
        }

        // 2. Zkontrolujeme, zda nemáme Venue na velmi blízkých souřadnicích (radius ~30 metrů)
        $nearby = Venue::whereBetween('latitude', [$geo['lat'] - 0.0003, $geo['lat'] + 0.0003])
            ->whereBetween('longitude', [$geo['lng'] - 0.0003, $geo['lng'] + 0.0003])
            ->first();

        if ($nearby) {
            if (!$nearby->osm_place_id && $geo['osm_place_id']) {
                $nearby->update(['osm_place_id' => $geo['osm_place_id']]);
            }
            return $nearby;
        }

        // 3. Vytvoříme nové Venue
        $name = Str::limit($cleanLocation, 100, '');
        $venue = Venue::create([
            'name' => $name,
            'address' => $cleanLocation,
            'city' => 'Brno',
            'latitude' => $geo['lat'],
            'longitude' => $geo['lng'],
            'osm_place_id' => $geo['osm_place_id'],
            'place_type' => 'other',
            'is_verified' => false, // Nově objevené místo čeká na kontrolu
        ]);

        self::flushDictionary();
        return $venue;
    }

    /**
     * Zaregistruje nový textový alias pro dané Venue.
     */
    public function registerAlias(Venue $venue, string $alias): VenueAlias
    {
        $normalized = $this->normalizeText($alias);

        $record = VenueAlias::firstOrCreate([
            'venue_id' => $venue->id,
            'alias_normalized' => $normalized,
        ], [
            'alias' => trim($alias),
        ]);

        self::flushDictionary();
        return $record;
    }
}
