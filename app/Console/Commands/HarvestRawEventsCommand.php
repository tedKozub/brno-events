<?php

namespace App\Console\Commands;

use App\Models\ScrapeSource;
use App\Services\Scraping\RawHarvester;
use Illuminate\Console\Command;

class HarvestRawEventsCommand extends Command
{
    protected $signature = 'events:harvest-raw
                            {source_id? : ID konkrétního zdroje (nepovinné)}
                            {--prune-days=14 : Počet dní, po kterých se staré snapshoty promažou}';

    protected $description = 'Stáhne a archivuje surová data (HTML/JSON/ICS) ze všech aktivních zdrojů do storage/app/scraping_raw';

    public function handle(RawHarvester $harvester): int
    {
        $sourceId = $this->argument('source_id');
        $pruneDays = (int) $this->option('prune-days');

        $query = ScrapeSource::query();
        if ($sourceId) {
            $query->where('id', $sourceId);
        } else {
            $query->where('is_active', true);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->warn('Nebyly nalezeny žádné aktivní zdroje ke stažení.');
            return 0;
        }

        $this->info("Zahajuji stahování surových dat z {$sources->count()} zdrojů...");
        $totalBytes = 0;

        foreach ($sources as $source) {
            $this->line(" -> Stahuji: [{$source->id}] {$source->name}...");
            try {
                $result = $harvester->harvestSource($source);
                $kb = round($result['bytes'] / 1024, 1);
                $this->info("    Uloženo ({$kb} KB) do {$result['file_path']}");
                $totalBytes += $result['bytes'];
            } catch (\Throwable $e) {
                $this->error("    Chyba při stahování zdroje {$source->name}: {$e->getMessage()}");
            }
        }

        // Rotace a promazání starých složek
        $deleted = $harvester->pruneOldSnapshots($pruneDays);
        if ($deleted > 0) {
            $this->info("Promazáno {$deleted} starých snapshot adresářů (starší než {$pruneDays} dní).");
        }

        $totalMb = round($totalBytes / (1024 * 1024), 2);
        $this->info("Surový sběr dat dokončen. Celkem staženo {$totalMb} MB.");

        return 0;
    }
}
