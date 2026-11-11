<?php

namespace App\Console\Commands;

use App\Services\DiscordNotifier;
use Illuminate\Console\Command;

class TestDiscordNotificationCommand extends Command
{
    protected $signature = 'discord:test {message? : Vlastní testovací zpráva}';
    protected $description = 'Odešle testovací zprávu na Discord webhook definovaný v DISCORD_WEBHOOK_URL';

    public function handle(): int
    {
        $url = DiscordNotifier::getWebhookUrl();
        if (empty($url)) {
            $this->warn('DISCORD_WEBHOOK_URL není nastavena v souboru .env.');
            $this->line('Přidejte do .env řádek:');
            $this->line('DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...');
            return 1;
        }

        $message = $this->argument('message') ?? 'BrnoAkce scraper testovací hlášení z Hetzner serveru.';
        $this->info("Odesílám testovací notifikaci na Discord webhook...");

        $ok = DiscordNotifier::test($message);

        if ($ok) {
            $this->info('Notifikace byla úspěšně odeslána do Discord kanálu!');
            return 0;
        } else {
            $this->error('Odeslání selhalo. Zkontrolujte logy (storage/logs/laravel.log).');
            return 1;
        }
    }
}
