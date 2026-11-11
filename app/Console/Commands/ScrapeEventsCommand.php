<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\ScrapeSource;
use App\Models\Venue;
use App\Services\DiscordNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class ScrapeEventsCommand extends Command
{
    protected $signature = 'events:scrape {source_id? : ID konkrétního zdroje (nepovinné)} {--limit=20 : Maximální počet akcí z jednoho zdroje}';
    protected $description = 'Spustí scraping studentských akcí z webů, iCal a RSS zdrojů';

    public function handle(): int
    {
        // Automaticky synchronizovat zdroje z config/scrape_sources.yaml před během
        \App\Services\ScrapeSourceManager::syncFromConfig();

        $sourceId = $this->argument('source_id');
        $limit = (int) $this->option('limit');

        $query = ScrapeSource::query();
        if ($sourceId) {
            $query->where('id', $sourceId);
        } else {
            $query->where('is_active', true);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->warn('Nebyly nalezeny žádné aktivní zdroje pro scraping.');
            return 0;
        }

        $pythonBin = base_path('scrapers/.venv/bin/python');
        $scriptPath = base_path('scrapers/main.py');

        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $totalImported = 0;
        $totalSkipped = 0;
        $newEventsList = [];
        $errorsList = [];

        foreach ($sources as $index => $source) {
            if ($index > 0) {
                // Šetrná náhodná prodleva 1.5s - 3s mezi zdroji proti rate-limitům
                usleep(random_int(1500000, 3000000));
            }

            $this->info("Scrapuji zdroj: [{$source->id}] {$source->name} ({$source->url})...");

            $cmd = [
                $pythonBin,
                $scriptPath,
                '--url', $source->url,
                '--type', $source->type,
                '--university', $source->university ?? 'OTHER',
                '--limit', (string) $limit,
            ];

            $result = Process::path(base_path('scrapers'))->run($cmd);

            if ($result->failed()) {
                $err = $result->errorOutput() ?: $result->output();
                $this->error("Chyba při scrapování zdroje {$source->name}: {$err}");
                $source->update([
                    'last_scraped_at' => now(),
                    'last_status' => 'error',
                    'last_error' => Str::limit($err, 1000),
                ]);
                $errorsList[] = [
                    'source' => $source->name,
                    'message' => Str::limit($err, 120),
                ];
                continue;
            }

            $json = json_decode($result->output(), true);
            if (!$json || ($json['status'] ?? '') !== 'success') {
                $err = $json['error'] ?? 'Neplatná JSON odpověď ze scraperu.';
                $this->error("Chyba výstupu scraperu: {$err}");
                $source->update([
                    'last_scraped_at' => now(),
                    'last_status' => 'error',
                    'last_error' => $err,
                ]);
                $errorsList[] = [
                    'source' => $source->name,
                    'message' => Str::limit($err, 120),
                ];
                continue;
            }

            $eventsData = $json['events'] ?? [];
            $sourceImported = 0;
            $sourceSkipped = 0;

            foreach ($eventsData as $item) {
                $sourceUrl = $item['source_url'] ?? null;
                $title = trim($item['title'] ?? '');

                if (empty($title)) {
                    continue;
                }

                // Deduplikace podle source_url nebo unikátního titulku a data
                try {
                    $startsAt = Carbon::parse($item['starts_at'] ?? now()->addDay());
                } catch (\Throwable $e) {
                    $startsAt = now()->addDays(2)->setTime(18, 0);
                }

                $endsAt = null;
                if (!empty($item['ends_at'])) {
                    try {
                        $endsAt = Carbon::parse($item['ends_at']);
                    } catch (\Throwable $e) {
                        $endsAt = null;
                    }
                }
                // Deduplikace podle unikátního source_url nebo titulku a data
                $existing = Event::where(function ($q) use ($title, $startsAt, $sourceUrl, $item) {
                    if (!empty($sourceUrl)) {
                        $q->where('source_url', $sourceUrl);
                    }
                    $q->orWhere(function ($sub) use ($title, $startsAt) {
                        $sub->where('title', $title)
                            ->whereDate('starts_at', $startsAt->toDateString());
                    });
                    if (!empty($item['cover_image'])) {
                        $q->orWhere('cover_image', $item['cover_image']);
                    }
                })->first();

                if ($existing) {
                    $sourceSkipped++;
                    $totalSkipped++;
                    continue;
                }

                // Určení organizace: by default přímo fakultě nebo univerzitě (nikdy nepřiřazovat k náhodnému spolku)
                $orgId = $source->organization_id;
                $university = $item['university'] ?? $source->university ?? 'OTHER';

                if (!$orgId && $university !== 'OTHER') {
                    $textToCheck = mb_strtolower($title . ' ' . ($item['location_name'] ?? '') . ' ' . ($item['description'] ?? ''));
                    $facultySlug = null;

                    if ($university === 'MUNI') {
                        if (str_contains($textToCheck, 'pedagogick') || str_contains($textToCheck, 'pdf') || str_contains($textToCheck, 'poříčí')) {
                            $facultySlug = 'muni-pdf';
                        } elseif (str_contains($textToCheck, 'informatik') || str_contains($textToCheck, 'botanick') || str_contains($textToCheck, 'fi muni')) {
                            $facultySlug = 'muni-fi';
                        } elseif (str_contains($textToCheck, 'ekonomick') || str_contains($textToCheck, 'esf') || str_contains($textToCheck, 'lipov')) {
                            $facultySlug = 'muni-econ';
                        } elseif (str_contains($textToCheck, 'sociální') || str_contains($textToCheck, 'fss') || str_contains($textToCheck, 'joštov')) {
                            $facultySlug = 'muni-fss';
                        } elseif (str_contains($textToCheck, 'filozofick') || str_contains($textToCheck, 'arne novák')) {
                            $facultySlug = 'muni-phil';
                        } elseif (str_contains($textToCheck, 'právnick') || str_contains($textToCheck, 'veveří')) {
                            $facultySlug = 'muni-law';
                        } elseif (str_contains($textToCheck, 'lékařsk') || str_contains($textToCheck, 'bohunick') || str_contains($textToCheck, 'kamenice')) {
                            $facultySlug = 'muni-med';
                        }
                    } elseif ($university === 'VUT') {
                        if (str_contains($textToCheck, 'informačních technologií') || str_contains($textToCheck, 'fit') || str_contains($textToCheck, 'božetěchov')) {
                            $facultySlug = 'vut-fit';
                        }
                    }

                    if ($facultySlug) {
                        $orgId = Organization::where('slug', $facultySlug)->value('id');
                    }

                    if (!$orgId) {
                        $uniSlug = strtolower($university);
                        $orgId = Organization::where('slug', $uniSlug)->value('id');
                    }
                }

                // Detekce místa (Venue) podle klíčových slov
                $locName = $item['location_name'] ?? '';
                $venueId = $this->matchVenue($locName, $university);

                // Detekce kategorie podle specifikovaného slugu nebo textu
                $categoryId = null;
                if (!empty($item['category_slug'])) {
                    $categoryId = Category::where('slug', $item['category_slug'])->value('id');
                }
                if (empty($categoryId)) {
                    $categoryId = $this->detectCategory($title . ' ' . ($item['description'] ?? ''));
                }

                // Tvorba unikátního slugu
                $slug = Str::slug($title);
                $counter = 1;
                while (Event::where('slug', $slug)->exists()) {
                    $slug = Str::slug($title) . '-' . (++$counter);
                }

                $createdEvent = Event::create([
                    'organization_id' => $orgId,
                    'venue_id' => $venueId,
                    'category_id' => $categoryId,
                    'title' => $title,
                    'slug' => $slug,
                    'short_description' => $item['short_description'] ?? null,
                    'description' => $item['description'] ?? null,
                    'cover_image' => $item['cover_image'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'is_all_day' => !empty($item['is_all_day']),
                    'location_name' => $locName,
                    'price_type' => $item['price_type'] ?? 'free',
                    'price_amount' => $item['price_amount'] ?? null,
                    'ticket_url' => $item['ticket_url'] ?? null,
                    'source_type' => $item['source_type'] ?? ($source->type === 'web_facebook' ? 'scraper_facebook' : ($source->type === 'web_instagram' ? 'scraper_instagram' : 'scraper_web')),
                    'source_url' => $sourceUrl,
                    'instagram_post_url' => $item['instagram_post_url'] ?? null,
                    'facebook_event_url' => $item['facebook_event_url'] ?? null,
                    'status' => 'pending_review', // Vše jde do moderace ke schválení!
                    'is_featured' => false,
                ]);

                $newEventsList[] = [
                    'id' => $createdEvent->id,
                    'title' => $createdEvent->title,
                    'starts_at' => $startsAt->toDateTimeString(),
                    'venue' => $locName ?: ($createdEvent->venue?->name ?? 'Brno'),
                    'organization' => $createdEvent->organization?->name ?? 'Univerzita',
                    'missing' => $createdEvent->missing_fields,
                ];

                $sourceImported++;
                $totalImported++;
            }

            $source->update([
                'last_scraped_at' => now(),
                'last_status' => 'ok',
                'last_error' => null,
            ]);

            $this->line(" -> Načteno {$sourceImported} nových akcí, přeskočeno {$sourceSkipped} existujících.");
        }

        $this->info("Celkem úspěšně importováno {$totalImported} nových akcí ke schválení (přeskočeno {$totalSkipped} duplikátů).");

        // Odeslání souhrnu do Discord kanálu (pokud je nastaven webhook)
        DiscordNotifier::notifyScrapeSummary([
            'imported' => $totalImported,
            'skipped' => $totalSkipped,
            'sources' => $sources->count(),
        ], $newEventsList, $errorsList);

        return 0;
    }

    private function matchVenue(?string $location, string $university): ?int
    {
        if (empty($location)) {
            return null;
        }

        $locLower = mb_strtolower($location);

        if (str_contains($locLower, 'fléda') || str_contains($locLower, 'fleda')) {
            return Venue::where('slug', 'fleda-club')->value('id');
        }
        if (str_contains($locLower, 'artbar') || str_contains($locLower, 'druhý pád')) {
            return Venue::where('slug', 'artbar-druhy-pad')->value('id');
        }
        if (str_contains($locLower, 'vinařská') || str_contains($locLower, 'vinarska')) {
            return Venue::where('slug', 'koleje-vinarska')->value('id');
        }
        if (str_contains($locLower, 'palackého vrchem') || str_contains($locLower, 'ppv')) {
            return Venue::where('slug', 'koleje-ppv')->value('id');
        }
        if (str_contains($locLower, 'scala')) {
            return Venue::where('slug', 'univerzitni-kino-scala')->value('id');
        }
        if (str_contains($locLower, 'babylon')) {
            return Venue::where('slug', 'kc-babylon')->value('id');
        }
        if (str_contains($locLower, 'koma lounge') || str_contains($locLower, 'koma')) {
            return Venue::where('slug', 'koma-lounge-bar')->value('id');
        }
        if (str_contains($locLower, 'morgal')) {
            return Venue::where('slug', 'cafe-morgal')->value('id');
        }
        if (str_contains($locLower, 'poříčí') || str_contains($locLower, 'pdf muni')) {
            return Venue::where('slug', 'pdf-muni-porici')->value('id');
        }
        if (str_contains($locLower, 'lužánk') || str_contains($locLower, 'luzank')) {
            return Venue::where('slug', 'park-luzanky')->value('id');
        }
        if (str_contains($locLower, 'kasárna') || str_contains($locLower, 'černá pole') || str_contains($locLower, 'šumavská')) {
            return Venue::where('slug', 'kasarna-cerna-pole-unob')->value('id');
        }
        if (str_contains($locLower, 'vetuni') || str_contains($locLower, 'palackého tř')) {
            return Venue::where('slug', 'kampus-vetuni-palackeho')->value('id');
        }

        return null;
    }

    private function detectCategory(string $text): ?int
    {
        $lower = mb_strtolower($text);

        if (str_contains($lower, 'turnaj') || str_contains($lower, 'beerpong') || str_contains($lower, 'fotbal') || str_contains($lower, 'běh') || str_contains($lower, 'sport') || str_contains($lower, 'workout')) {
            return Category::where('slug', 'turnaje-sport')->value('id');
        }
        if (str_contains($lower, 'kvíz') || str_contains($lower, 'deskovk') || str_contains($lower, 'hry') || str_contains($lower, 'lan party')) {
            return Category::where('slug', 'kvizy-hry')->value('id');
        }
        if (str_contains($lower, 'párty') || str_contains($lower, 'party') || str_contains($lower, 'večírek') || str_contains($lower, 'grilovačk') || str_contains($lower, 'noc')) {
            return Category::where('slug', 'party-kluby')->value('id');
        }
        if (str_contains($lower, 'přednášk') || str_contains($lower, 'workshop') || str_contains($lower, 'seminář') || str_contains($lower, 'konferenc') || str_contains($lower, 'kurz') || str_contains($lower, 'veletrh')) {
            return Category::where('slug', 'prednasky-kariera')->value('id');
        }

        return Category::where('slug', 'kultura-divadlo')->value('id') ?? Category::first()?->id;
    }
}
