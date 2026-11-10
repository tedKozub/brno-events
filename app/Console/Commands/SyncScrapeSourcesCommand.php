<?php

namespace App\Console\Commands;

use App\Services\ScrapeSourceManager;
use Illuminate\Console\Command;

class SyncScrapeSourcesCommand extends Command
{
    protected $signature = 'events:sync-sources';
    protected $description = 'Synchronizuje zdroje scrapingu z konfiguračního souboru config/scrape_sources.yaml (nebo .json)';

    public function handle(): int
    {
        $this->info('Načítám konfiguraci zdrojů ze souboru config/scrape_sources.yaml...');

        $result = ScrapeSourceManager::syncFromConfig();

        $this->info("Synchronizace dokončena:");
        $this->line(" - Přidáno nových zdrojů: {$result['added']}");
        $this->line(" - Aktualizováno existujících zdrojů: {$result['updated']}");
        $this->line(" - Deaktivováno odebraných zdrojů: {$result['deactivated']}");

        return 0;
    }
}
