<?php

namespace App\Console\Commands;

use App\Services\DiscordNotifier;
use Illuminate\Console\Command;

class TestDiscordNotificationCommand extends Command
{
    protected $signature = 'discord:test {message? : Vlastní testovací zpráva} {--channel=general : Cílový kanál: general (scraping) nebo security (bezpečnostní audity)}';
    protected $description = 'Odešle testovací zprávu na Discord webhook (DISCORD_WEBHOOK_URL nebo DISCORD_SECURITY_WEBHOOK_URL)';

    public function handle(): int
    {
        $channel = strtolower($this->option('channel') ?? 'general');
        $isSecurity = $channel === 'security';

        $url = $isSecurity ? DiscordNotifier::getSecurityWebhookUrl() : DiscordNotifier::getWebhookUrl();
        $envKey = $isSecurity ? 'DISCORD_SECURITY_WEBHOOK_URL' : 'DISCORD_WEBHOOK_URL';

        if (empty($url)) {
            $this->warn("{$envKey} není nastavena v souboru .env.");
            $this->line("Přidejte do .env řádek:");
            $this->line("{$envKey}=https://discord.com/api/webhooks/...");
            return 1;
        }

        $defaultMessage = $isSecurity
            ? 'BrnoAkce testovací zpráva pro dedikovaný BEZPEČNOSTNÍ kanál.'
            : 'BrnoAkce testovací zpráva pro kanál scrapingu a uživatelského feedbacku.';

        $message = $this->argument('message') ?? $defaultMessage;
        $this->info("Odesílám testovací notifikaci do kanálu [{$channel}]...");

        $ok = DiscordNotifier::test($message, $channel);

        if ($ok) {
            $this->info("Notifikace byla úspěšně odeslána do kanálu [{$channel}]!");
            return 0;
        } else {
            $this->error("Odeslání selhalo. Zkontrolujte logy (storage/logs/laravel.log).");
            return 1;
        }
    }
}
