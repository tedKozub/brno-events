<?php

namespace App\Livewire;

use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class StudentPlaces extends Component
{
    public string $selectedCategory = 'all';
    public string $search = '';
    public ?int $selectedPlaceId = null;

    public function mount(): void
    {
        app(\App\Services\AnalyticsService::class)->trackPageView('/mista');
    }

    public function setCategory(string $category): void
    {
        $this->selectedCategory = $category;
        $this->dispatch('places-updated', places: $this->filteredPlaces);
    }

    public function updatedSearch(): void
    {
        $this->dispatch('places-updated', places: $this->filteredPlaces);
    }

    public function selectPlace(int $id): void
    {
        $this->selectedPlaceId = $id;
        $place = collect($this->allPlaces)->firstWhere('id', $id);
        if ($place) {
            $this->dispatch('place-selected', id: $id, lat: $place['lat'], lng: $place['lng']);
        }
    }

    public function getCategoriesProperty(): array
    {
        return [
            'all' => [
                'name' => 'Všechna místa',
                'short_name' => 'Vše',
                'color' => '#6366f1', // Indigo
            ],
            'study' => [
                'name' => 'Studovny',
                'short_name' => 'Studovna',
                'color' => '#8b5cf6', // Violet
            ],
            'cafes' => [
                'name' => 'Kavárny',
                'short_name' => 'Kavárna',
                'color' => '#f59e0b', // Amber
            ],
            'date' => [
                'name' => 'Rande & Výhledy',
                'short_name' => 'Rande',
                'color' => '#ec4899', // Pink / Rose
            ],
            'pubs' => [
                'name' => 'Hospody & Bary',
                'short_name' => 'Hospoda',
                'color' => '#10b981', // Emerald
            ],
            'parks' => [
                'name' => 'Parky & Příroda',
                'short_name' => 'Park',
                'color' => '#06b6d4', // Cyan
            ],
        ];
    }

    public function getAllPlacesProperty(): array
    {
        return [
            // STUDOVNY & KNIHOVNY
            [
                'id' => 1,
                'name' => 'Moravská zemská knihovna (MZK)',
                'category' => 'study',
                'address' => 'Kounicova 65a, Brno',
                'lat' => 49.2087,
                'lng' => 16.5947,
                'description' => 'Mekka brněnských studentů. Patra tichých i týmových studoven, stovky zásuvek, Wi-Fi Eduroam, kavárna a noční studovna pro zkouškové období.',
                'tags' => ['Eduroam', 'Zásuvky', 'Tiché zóny', 'Kavárna', 'Klimatizace'],
                'highlight' => 'Nejoblíbenější studovna v Brně',
            ],
            [
                'id' => 2,
                'name' => 'Knihovna Jiřího Mahena (Ústřední)',
                'category' => 'study',
                'address' => 'Kobližná 4, Brno-střed',
                'lat' => 49.1952,
                'lng' => 16.6108,
                'description' => 'Historické sály Schrattenbachova paláce přímo v srdci města. Skvělý klid na čtení, psaní seminárek a odpočinek v centru Brna.',
                'tags' => ['Centrum', 'Ticho', 'Historická budova', 'Wi-Fi'],
                'highlight' => 'Klid přímo v centru',
            ],
            [
                'id' => 3,
                'name' => 'Ústřední knihovna Filozofické fakulty MUNI',
                'category' => 'study',
                'address' => 'Arna Nováka 1, Brno',
                'lat' => 49.2001,
                'lng' => 16.5986,
                'description' => 'Moderní prosklená budova s týmovými studovnami, pohodlnými křesly a relaxačními zónami. Otevřeno všem studentům s kartou ISIC.',
                'tags' => ['MUNI', 'Eduroam', 'Týmové studovny', 'Moderní design'],
                'highlight' => 'Moderní univerzitní knihovna',
            ],
            [
                'id' => 4,
                'name' => 'Studovna Fakulty informatiky MUNI',
                'category' => 'study',
                'address' => 'Botanická 68a, Brno',
                'lat' => 49.2104,
                'lng' => 16.5989,
                'description' => 'Špičkové technologické zázemí, monitory k zapůjčení, zásuvky u každého stolu a rychlé připojení. Ideální pro programátory i týmové projekty.',
                'tags' => ['FI MUNI', 'Monitory', 'Zásuvky', 'Eduroam', 'Automaty na kávu'],
                'highlight' => 'Zázemí pro IT projekty',
            ],
            [
                'id' => 5,
                'name' => 'Knihovna a studovna FSI VUT',
                'category' => 'study',
                'address' => 'Technická 2896/2, Brno-Královo Pole',
                'lat' => 49.2238,
                'lng' => 16.5746,
                'description' => 'Prostorná knihovna v kampusu Pod Palackého vrchem. Velké pracovní stoly, klidné prostředí pro studenty VUT a technických oborů.',
                'tags' => ['VUT', 'Palackého vrch', 'Eduroam', 'Velké stoly'],
                'highlight' => 'Technický kampus VUT',
            ],

            // KAVÁRNY NA UČENÍ
            [
                'id' => 6,
                'name' => 'Skøg Urban Hub',
                'category' => 'cafes',
                'address' => 'Dominikánské nám. 187/5, Brno-střed',
                'lat' => 49.1936,
                'lng' => 16.6074,
                'description' => 'Ikonický skandinávský interiér, výběrová káva, vegetariánská jídla a večer bar. Přes den oblíbený studenty s laptopy.',
                'tags' => ['Výběrová káva', 'Laptopy', 'Zásuvky', 'Jídlo & Bar'],
                'highlight' => 'Ikonické brněnské místo',
            ],
            [
                'id' => 7,
                'name' => 'Kafec Brněnský',
                'category' => 'cafes',
                'address' => 'Orlí 16, Brno-střed',
                'lat' => 49.1932,
                'lng' => 16.6115,
                'description' => 'Proslulé čerstvé vafle na sladko i slano a výborná filtrovaná káva. Skvělé místo pro studijní snídaně nebo odpolední schůzky.',
                'tags' => ['Vafle', 'Snídaně', 'Výběrová káva', 'Zahrádka'],
                'highlight' => 'Nejlepší vafle ve městě',
            ],
            [
                'id' => 8,
                'name' => 'Punkt cafe',
                'category' => 'cafes',
                'address' => 'Bayerova 577/1, Brno-Veveří',
                'lat' => 49.2045,
                'lng' => 16.6021,
                'description' => 'Příjemná komorní studentská kavárna v univerzitní čtvrti Veveří. Klidná atmosféra vhodná pro čtení skript a soustředěnou práci.',
                'tags' => ['Veveří', 'Klid', 'Laptopy', 'Domácí koláče'],
                'highlight' => 'Komorní atmosféra u FF MUNI',
            ],
            [
                'id' => 9,
                'name' => 'Podnik café bar',
                'category' => 'cafes',
                'address' => 'Peroutková 2, Brno-střed',
                'lat' => 49.1921,
                'lng' => 16.6087,
                'description' => 'Prostorná kavárna v pasáži kousek od Zelného trhu. Zásuvky u většiny stolů, rychlý internet a velkorysé prostory na skupinové učení.',
                'tags' => ['Zásuvky', 'Skupinové projekty', 'Wi-Fi', 'Zelný trh'],
                'highlight' => 'Dostatek místa a zásuvek',
            ],
            [
                'id' => 10,
                'name' => 'V Melounovém cukru',
                'category' => 'cafes',
                'address' => 'Františkánská 17, Brno-střed',
                'lat' => 49.1916,
                'lng' => 16.6105,
                'description' => 'Útulná poetická kavárna se skvělými cheesecaky, výběrovým čajem i vínem. Ideální pro ranní přípravu na zkoušky nebo pauzu v centru.',
                'tags' => ['Útulné', 'Dezerty', 'Centrum', 'Víno'],
                'highlight' => 'Poetická atmosféra',
            ],
            [
                'id' => 11,
                'name' => 'Atlas Cafe',
                'category' => 'cafes',
                'address' => 'Žerotínovo nám. 6, Brno-střed',
                'lat' => 49.1983,
                'lng' => 16.6046,
                'description' => 'Přímo naproti FSS MUNI. Plno studentů, akademická atmosféra, dobré denní menu a rychlá obsluha mezi přednáškami.',
                'tags' => ['FSS MUNI', 'Studentský vibe', 'Obědy', 'Zahrádka'],
                'highlight' => 'Přímo u Fakulty sociálních studií',
            ],

            // MÍSTA NA RANDE & ROMANTIKU
            [
                'id' => 12,
                'name' => 'Denisovy sady & Kapucínské terasy',
                'category' => 'date',
                'address' => 'Denisovy sady, Brno-střed',
                'lat' => 49.1911,
                'lng' => 16.6062,
                'description' => 'Legendární brněnské rande místo. Panoramatická vyhlídka na Petrov, kolonáda s obeliskem, terasové zahrady a lavičky s výhledem na noční Brno.',
                'tags' => ['Výhled', 'Petrov', 'Západ slunce', 'Noční atmosféra', 'Lavičky'],
                'highlight' => 'Ikonická brněnská vyhlídka',
            ],
            [
                'id' => 13,
                'name' => 'Hradní park Špilberk',
                'category' => 'date',
                'address' => 'Špilberk 210/1, Brno-střed',
                'lat' => 49.1944,
                'lng' => 16.6001,
                'description' => 'Rozlehlý zalesněný kopec uprostřed města. Cestičky k procházkám, vyhlídkové bašty, záhony a kouzelně osvětlený hrad po setmění.',
                'tags' => ['Hrad', 'Příroda v centru', 'Romantika', 'Vyhlídky'],
                'highlight' => 'Romantická večerní procházka',
            ],
            [
                'id' => 14,
                'name' => 'Kraví hora & Hvězdárna a planetárium',
                'category' => 'date',
                'address' => 'Kraví hora, Brno-Žabovřesky',
                'lat' => 49.2048,
                'lng' => 16.5833,
                'description' => 'Travnatý kopec s jedinečným výhledem na Špilberk a Petrov. V létě slavné nafukovací modely vesmírných těles, deky na trávě a pozorování hvězd.',
                'tags' => ['Piknik', 'Hvězdy', 'Západ slunce', 'Tráva', 'Žabovřesky'],
                'highlight' => 'Západ slunce a vesmírné modely',
            ],
            [
                'id' => 15,
                'name' => 'Lom Hády & Růženin lom',
                'category' => 'date',
                'address' => 'Lom Hády, Brno-Líšeň',
                'lat' => 49.2185,
                'lng' => 16.6738,
                'description' => 'Dramatický vápencový útes s výhledem na celou jihomoravskou metropoli. Tyrkysová jezírka v lomu a dechberoucí západy slunce pro dobrodružné rande.',
                'tags' => ['Příroda', 'Výhled na celé Brno', 'Skály', 'Jezírko'],
                'highlight' => 'Nejlepší panorama nad městem',
            ],
            [
                'id' => 16,
                'name' => 'Kamenná kolonie (Kamenka)',
                'category' => 'date',
                'address' => 'Kamenná čtvrť, Brno-Štýřice',
                'lat' => 49.1825,
                'lng' => 16.5891,
                'description' => 'Unikátní bohémská čtvrť malých domků zasazených v bývalém lomu. Hospůdka Duck bar, pouliční kočky, umělecká atmosféra a Červený kopec.',
                'tags' => ['Bohémské', 'Duck bar', 'Klid', 'Unikátní architektura'],
                'highlight' => 'Kouzelná umělecká vesnička ve městě',
            ],

            // STUDENTSKÉ HOSPODY & VEČERNÍ ŽIVOT
            [
                'id' => 17,
                'name' => 'Na Božence',
                'category' => 'pubs',
                'address' => 'Božetěchova 1, Brno-Královo Pole',
                'lat' => 49.2269,
                'lng' => 16.5968,
                'description' => 'Legendární studentská hospoda přímo u Fakulty informačních technologií VUT. Poctivé levné pivo, dvorek se zahrádkou a debaty o kódu i životě.',
                'tags' => ['FIT VUT', 'Levné pivo', 'Zahrádka', 'Studentská legenda'],
                'highlight' => 'Kultovní hospoda studentů FIT VUT',
            ],
            [
                'id' => 18,
                'name' => 'Hostinec U Poutníka',
                'category' => 'pubs',
                'address' => 'Starobrněnská 18, Brno-střed',
                'lat' => 49.1925,
                'lng' => 16.6071,
                'description' => 'Jedna z nejosobitějších pivnic v Brně. Excelentní nefiltrovaný Poutník z Pelhřimova, skvělé nakládané tvarůžky a vždy plno studentů na stojáka na chodníku.',
                'tags' => ['Pelhřimovský Poutník', 'Centrum', 'Nefiltrované pivo', 'Živá atmosféra'],
                'highlight' => 'Vyhlášené řemeslné pivo',
            ],
            [
                'id' => 19,
                'name' => 'Kavárna a bar Trojka',
                'category' => 'pubs',
                'address' => 'Dominikánská 9, Brno-střed',
                'lat' => 49.1929,
                'lng' => 16.6059,
                'description' => 'V historickém Domě pánů z Kunštátu. Alternativní studentské doupě, koncerty, výstavy, kvízy a v létě kouzelné renesanční nádvoří pod arkádami.',
                'tags' => ['Alternativa', 'Renesanční nádvoří', 'Kulturní akce', 'Kvízy'],
                'highlight' => 'Kulturní centrum studentů v podloubí',
            ],
            [
                'id' => 20,
                'name' => 'Desert bar',
                'category' => 'pubs',
                'address' => 'Rooseveltova 4, Brno-střed',
                'lat' => 49.1974,
                'lng' => 16.6111,
                'description' => 'Legendární noční útočiště pro studenty divadla, filozofie a noční sovy. Otevřeno dlouho do noci, undergroundová hudba a nezaměnitelný genius loci.',
                'tags' => ['Noční sovy', 'Otevřeno do rána', 'Divadelníci', 'Centrum'],
                'highlight' => 'Útočiště otevřené do rána',
            ],
            [
                'id' => 21,
                'name' => 'Music Lab',
                'category' => 'pubs',
                'address' => 'Opletalova 1, Brno-střed',
                'lat' => 49.1989,
                'lng' => 16.6062,
                'description' => 'Studentský jazz & jam club u JAMU. Pravidelné otevřené jam sessions, stolní fotbálek, drinky a skvělá komunita mladých hudebníků.',
                'tags' => ['JAMU', 'Live music', 'Jam sessions', 'Drinky', 'Stolní fotbálek'],
                'highlight' => 'Živá muzika a jam sessions',
            ],

            // PARKY & CHILLOUT
            [
                'id' => 22,
                'name' => 'Park Lužánky',
                'category' => 'parks',
                'address' => 'Lužánky, Brno-Černá Pole',
                'lat' => 49.2066,
                'lng' => 16.6094,
                'description' => 'Největší a nejstarší park v Brně. Trávníky na piknik, frisbee, slackline, potůček, stánky s pivem a kávou i posezení u Lužáneckého skleníku.',
                'tags' => ['Piknik', 'Frisbee', 'Slackline', 'Největší park', 'Sport'],
                'highlight' => 'Srdce venkovního studentského chillu',
            ],
            [
                'id' => 23,
                'name' => 'Björnsonův sad',
                'category' => 'parks',
                'address' => 'Veveří / Kounicova, Brno',
                'lat' => 49.2072,
                'lng' => 16.5962,
                'description' => 'Park přímo za Moravskou zemskou knihovnou. Lehátka k půjčení zdarma, zahradní kavárna Piknik box a venkovní knihovnička pro pauzu od učení.',
                'tags' => ['U knihovny MZK', 'Piknik box', 'Lehátka', 'Chillout'],
                'highlight' => 'Relaxační oáza za knihovnou MZK',
            ],
            [
                'id' => 24,
                'name' => 'Obora Holedná & Rozhledna',
                'category' => 'parks',
                'address' => 'Brno-Jundrov / Kohoutovice',
                'lat' => 49.2040,
                'lng' => 16.5290,
                'description' => 'Rozsáhlý lesní park s volně žijícími daňky, muflony a divočáky. Na vrcholu stojí nová 35metrová ocelová rozhledna s parádním výhledem na celé Brno.',
                'tags' => ['Zvířata', 'Rozhledna', 'Les', 'Běhání & Výlet'],
                'highlight' => 'Lesní obora s rozhlednou a daňky',
            ],
        ];
    }

    public function getPlaceEmbedding(array $place): ?array
    {
        $id = $place['id'];
        return Cache::remember('student_place_emb_' . $id, 86400 * 30, function () use ($place) {
            $text = "{$place['name']}. {$place['address']}. {$place['description']}. Tagy: " . implode(', ', $place['tags']) . '. ' . ($place['highlight'] ?? '');
            return app(EmbeddingService::class)->getEmbedding($text);
        });
    }

    public function getFilteredPlacesProperty(): array
    {
        $places = $this->allPlaces;

        // Filtr kategorie
        if ($this->selectedCategory !== 'all') {
            $places = array_filter($places, fn ($p) => $p['category'] === $this->selectedCategory);
        }

        // Hybridní vyhledávání (vektorový sémantický embedding + fulltext shoda)
        if (!empty(trim($this->search))) {
            $searchTerm = trim($this->search);
            $normSearch = mb_strtolower($searchTerm);
            $keywords = array_filter(explode(' ', $normSearch), fn ($w) => mb_strlen($w) >= 2);

            $embeddingService = app(EmbeddingService::class);
            $queryVector = $embeddingService->getEmbedding($searchTerm);

            $scored = [];
            $hasAnyKeywordMatch = false;

            foreach ($places as $place) {
                $semanticSim = 0.0;
                if ($queryVector) {
                    $placeVector = $this->getPlaceEmbedding($place);
                    if ($placeVector && is_array($placeVector)) {
                        $semanticSim = EmbeddingService::cosineSimilarity($queryVector, $placeVector);
                    }
                }

                // Shody klíčových slov
                $nameNorm = mb_strtolower($place['name']);
                $descNorm = mb_strtolower($place['description']);
                $addrNorm = mb_strtolower($place['address']);
                $tagsNorm = mb_strtolower(implode(' ', $place['tags']));
                $highlightNorm = mb_strtolower($place['highlight'] ?? '');
                $combinedText = "{$nameNorm} {$descNorm} {$addrNorm} {$tagsNorm} {$highlightNorm}";

                $hasExactNameMatch = str_contains($nameNorm, $normSearch);
                $hasWordMatch = false;

                foreach ($keywords as $kw) {
                    if (str_contains($combinedText, $kw)) {
                        $hasWordMatch = true;
                        break;
                    }
                }

                $keywordBonus = 0.0;
                if ($hasExactNameMatch) {
                    $keywordBonus += 1.0;
                    $hasAnyKeywordMatch = true;
                } elseif (str_contains($tagsNorm, $normSearch) || str_contains($highlightNorm, $normSearch)) {
                    $keywordBonus += 0.6;
                    $hasAnyKeywordMatch = true;
                } elseif ($hasWordMatch) {
                    $keywordBonus += 0.35;
                    $hasAnyKeywordMatch = true;
                }

                $totalScore = $semanticSim + $keywordBonus;

                $scored[] = [
                    'place' => $place,
                    'score' => $totalScore,
                    'hasKeywordMatch' => $keywordBonus > 0,
                    'semanticSim' => $semanticSim,
                ];
            }

            // Pokud alespoň jedno místo přímo odpovídá klíčovým slovům (např. vyhledávání "Božence", "MZK"),
            // vyřadíme místa bez jakékoliv shody klíčových slov, pokud nemají extrémně vysokou sémantickou shodu (>= 0.55).
            // Pokud žádné místo nemá shodu klíčových slov (např. abstraktní dotaz "místo na učení"),
            // použijeme čistě sémantický filtr (>= 0.35).
            $filtered = [];
            foreach ($scored as $item) {
                if ($hasAnyKeywordMatch) {
                    if ($item['hasKeywordMatch'] || $item['semanticSim'] >= 0.55) {
                        $filtered[] = $item;
                    }
                } else {
                    if ($item['semanticSim'] >= 0.35) {
                        $filtered[] = $item;
                    }
                }
            }

            // Seřazení podle relevance skóre sestupně
            usort($filtered, fn ($a, $b) => $b['score'] <=> $a['score']);
            $places = array_column($filtered, 'place');
        }

        return array_values($places);
    }

    public function getCategoryCountsProperty(): array
    {
        $counts = [
            'all' => count($this->allPlaces),
            'study' => 0,
            'cafes' => 0,
            'date' => 0,
            'pubs' => 0,
            'parks' => 0,
        ];

        foreach ($this->allPlaces as $place) {
            $cat = $place['category'];
            if (isset($counts[$cat])) {
                $counts[$cat]++;
            }
        }

        return $counts;
    }

    public function render()
    {
        return view('livewire.student-places', [
            'places' => $this->filteredPlaces,
            'categoryCounts' => $this->categoryCounts,
            'categories' => $this->categories,
        ])->layout('components.layouts.app', [
            'title' => 'Mapa studentských míst v Brně | BrnoAkce',
            'description' => 'Průvodce nejlepšími místy pro studenty v Brně: oblíbené studovny, knihovny s Eduroamem, kavárny na učení s laptopy, romantická místa na rande a kultovní studentské hospody.',
        ]);
    }
}
