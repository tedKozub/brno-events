<?php

namespace App\Console\Commands;

use App\Models\StudentPlace;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GeneratePlaceEmbeddingsCommand extends Command
{
    protected $signature = 'app:generate-place-embeddings
                            {--force : Regenerate even if embedding already exists}';

    protected $description = 'Pre-compute and store embeddings for all active student places';

    public function handle(EmbeddingService $embeddingService): int
    {
        $query = StudentPlace::where('is_active', true);

        if (! $this->option('force')) {
            $query->whereNull('embedding');
        }

        $places = $query->get();

        if ($places->isEmpty()) {
            $this->info('All places already have embeddings. Use --force to regenerate.');
            return 0;
        }

        $this->info("Generating embeddings for {$places->count()} places...");
        $bar = $this->output->createProgressBar($places->count());
        $bar->start();

        $ok = 0;
        $fail = 0;

        foreach ($places as $place) {
            $text = $embeddingService->buildPlaceSearchText($place);
            $vector = $embeddingService->getEmbedding($text);

            if ($vector && is_array($vector)) {
                $place->updateQuietly(['embedding' => json_encode($vector)]);
                // Bust the old cache key used by StudentPlaces component
                Cache::forget('student_place_emb_' . $place->id);
                $ok++;
            } else {
                $this->newLine();
                $this->warn("  Failed: [{$place->id}] {$place->name}");
                $fail++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Invalidate the allPlaces cache so next render picks up fresh embeddings
        Cache::forget('student_places_all');

        $this->info("Done. OK: {$ok}, Failed: {$fail}");
        return $fail > 0 ? 1 : 0;
    }
}
