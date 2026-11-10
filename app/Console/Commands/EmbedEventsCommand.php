<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class EmbedEventsCommand extends Command
{
    protected $signature = 'events:embed {--all : Přegenerovat embeddingy pro všechny akce včetně existujících}';
    protected $description = 'Vygeneruje vektorové embeddingy pro události pomocí lokálního ONNX modelu';

    public function handle(EmbeddingService $service): int
    {
        $all = $this->option('all');

        $query = Event::with(['organization', 'venue', 'category']);
        if (!$all) {
            $query->whereNull('embedding');
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            $this->info('Všechny akce již mají vygenerovaný vektorový embedding.');
            return 0;
        }

        $this->info("Generuji embeddingy pro {$events->count()} událostí...");
        $bar = $this->output->createProgressBar($events->count());
        $bar->start();

        $chunks = $events->chunk(20);
        $totalSuccess = 0;

        foreach ($chunks as $chunk) {
            $texts = [];
            foreach ($chunk as $event) {
                $texts[$event->id] = $service->buildEventSearchText($event);
            }

            $vectors = $service->getBatchEmbeddings(array_values($texts));

            if (!empty($vectors)) {
                $idx = 0;
                foreach ($chunk as $event) {
                    if (isset($vectors[$idx])) {
                        $event->updateQuietly(['embedding' => $vectors[$idx]]);
                        $totalSuccess++;
                    }
                    $idx++;
                    $bar->advance();
                }
            } else {
                foreach ($chunk as $event) {
                    $bar->advance();
                }
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Úspěšně uloženo {$totalSuccess} vektorových embeddingů.");

        return 0;
    }
}
