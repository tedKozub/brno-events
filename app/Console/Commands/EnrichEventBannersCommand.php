<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\EventBannerService;
use Illuminate\Console\Command;

class EnrichEventBannersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:enrich-banners
                            {--force : Force re-enrichment of events that already have an image}
                            {--limit=50 : Maximum number of events to process}
                            {--event= : Specific event ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enriches scraped events with downloaded banners, posters or article images from source websites (priority: poster > banner > article > stock)';

    /**
     * Execute the console command.
     */
    public function handle(EventBannerService $bannerService): int
    {
        $force = (bool) $this->option('force');
        $limit = (int) $this->option('limit');
        $eventId = $this->option('event');

        $query = Event::query()->whereNotNull('source_url');

        if ($eventId) {
            $query->where('id', $eventId);
        } else {
            if (!$force) {
                // Focus on events with missing image, un-downloaded URLs, or stock fallback
                $query->where(function ($q) {
                    $q->whereNull('cover_image')
                      ->orWhereNull('cover_image_type')
                      ->orWhere('cover_image_type', 'stock')
                      ->orWhere('cover_image', 'like', 'http%');
                });
            }
            $query->latest('id')->limit($limit);
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            $this->info('Žádné akce k obohacení nebyly nalezeny.');
            return 0;
        }

        $this->info("Zpracovávám {$events->count()} akcí...");

        $stats = [
            'poster' => 0,
            'banner' => 0,
            'article' => 0,
            'stock' => 0,
            'failed' => 0,
        ];

        $bar = $this->output->createProgressBar($events->count());
        $bar->start();

        foreach ($events as $event) {
            $success = $bannerService->enrichEvent($event, $force);

            if ($success) {
                $type = $event->fresh()->cover_image_type ?: 'article';
                $stats[$type] = ($stats[$type] ?? 0) + 1;
            } else {
                $stats['stock']++;
            }

            $bar->advance();
            // Short delay to avoid aggressive hammering
            usleep(200000); // 0.2s
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Typ obrázku (Priorita)', 'Počet stažených'],
            [
                ['1. 🖼️ Plakát akce (poster)', $stats['poster']],
                ['2. 📷 Webový banner / OG (banner)', $stats['banner']],
                ['3. 🖼️ Obrázek z článku (article)', $stats['article']],
                ['4. 🎨 Ponechán generický stock (stock)', $stats['stock']],
            ]
        );

        $this->info('Obohacení bannerů bylo úspěšně dokončeno.');

        return 0;
    }
}
