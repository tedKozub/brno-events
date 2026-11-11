<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotifier
{
    public static function getWebhookUrl(): ?string
    {
        return config('services.discord.webhook_url') ?? env('DISCORD_WEBHOOK_URL');
    }

    /**
     * Sends a raw Discord Webhook payload.
     */
    public static function send(array $payload): bool
    {
        $url = self::getWebhookUrl();
        if (empty($url)) {
            Log::info('Discord notification skipped: DISCORD_WEBHOOK_URL is not set.');
            return false;
        }

        try {
            $response = Http::timeout(10)->post($url, $payload);
            if (!$response->successful()) {
                Log::warning('Discord webhook failed: ' . $response->status() . ' - ' . $response->body());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('Discord webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends a scraping run summary with statistics, new events, missing data warnings, and errors.
     */
    public static function notifyScrapeSummary(array $stats, array $newEvents = [], array $errors = []): bool
    {
        $url = self::getWebhookUrl();
        if (empty($url)) {
            return false;
        }

        $imported = $stats['imported'] ?? 0;
        $skipped = $stats['skipped'] ?? 0;
        $totalSources = $stats['sources'] ?? 0;
        $hasErrors = count($errors) > 0;

        $color = $hasErrors ? 0xE02424 : ($imported > 0 ? 0x10B981 : 0x3B82F6); // Red, Green, Blue
        $title = $hasErrors
            ? '⚠️ BrnoAkce Scraping: Dokončeno s chybami'
            : ($imported > 0 ? "🎉 BrnoAkce Scraping: {$imported} nových akcí ke schválení" : 'ℹ️ BrnoAkce Scraping: Žádné nové akce');

        $adminUrl = config('app.url', 'https://brnoakce.tadeaskozub.com') . '/admin/events';

        $description = sprintf(
            "**Statistika běhu:**\n• Zpracováno zdrojů: **%d**\n• Nově vloženo (ke schválení): **%d**\n• Přeskočeno (již v DB): **%d**\n• Chybné zdroje: **%d**\n\n[Přejít do Administrace pro schválení](%s)",
            $totalSources,
            $imported,
            $skipped,
            count($errors),
            $adminUrl
        );

        $fields = [];

        // List newly imported events (up to 8)
        if (!empty($newEvents)) {
            $eventLines = [];
            foreach (array_slice($newEvents, 0, 8) as $ev) {
                $missingWarnings = '';
                if (!empty($ev['missing'])) {
                    $missingWarnings = ' `[' . implode(', ', array_keys($ev['missing'])) . ']`';
                }

                $evLink = !empty($ev['id']) ? "{$adminUrl}/{$ev['id']}/edit" : $adminUrl;
                $dateStr = !empty($ev['starts_at']) ? date('d.m. H:i', strtotime($ev['starts_at'])) : 'neuvedeno';
                $venueStr = $ev['venue'] ?? $ev['location'] ?? 'Brno';

                $eventLines[] = sprintf(
                    "• [%s](%s) — %s (%s, %s)%s",
                    mb_substr($ev['title'] ?? 'Bez názvu', 0, 40),
                    $evLink,
                    $dateStr,
                    $venueStr,
                    $ev['organization'] ?? 'Univerzita',
                    $missingWarnings
                );
            }

            if (count($newEvents) > 8) {
                $eventLines[] = sprintf("*...a dalších %d akcí*", count($newEvents) - 8);
            }

            $fields[] = [
                'name' => '📌 Nově nalezené akce',
                'value' => implode("\n", $eventLines),
                'inline' => false,
            ];
        }

        // List errors
        if (!empty($errors)) {
            $errLines = [];
            foreach (array_slice($errors, 0, 5) as $err) {
                $errLines[] = sprintf("• **%s**: %s", $err['source'] ?? 'Zdroj', mb_substr($err['message'] ?? 'Neznámá chyba', 0, 100));
            }
            $fields[] = [
                'name' => '⚠️ Chyby při scrapování',
                'value' => implode("\n", $errLines),
                'inline' => false,
            ];
        }

        $payload = [
            'embeds' => [
                [
                    'title' => $title,
                    'description' => $description,
                    'color' => $color,
                    'fields' => $fields,
                    'footer' => [
                        'text' => 'BrnoAkce Autonomní Scraper System',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        return self::send($payload);
    }

    /**
     * Sends an instant alert when a student reports an issue or broken event.
     */
    public static function notifyEventReport(EventReport $report): bool
    {
        $event = $report->event;
        $adminEventUrl = config('app.url', 'https://brnoakce.tadeaskozub.com') . "/admin/events/{$report->event_id}/edit";
        $publicEventUrl = config('app.url', 'https://brnoakce.tadeaskozub.com') . "/akce/{$event?->slug}";

        $payload = [
            'embeds' => [
                [
                    'title' => '🚨 Nahlášena chyba u akce',
                    'description' => sprintf(
                        "Uživatel nahlásil problém u akce **[%s](%s)**.\n\n**Důvod:** `%s`\n**Podrobnosti:** %s\n**Kontakt:** %s\n\n[Upravit akci v administraci](%s)",
                        $event?->title ?? 'Neznámá akce',
                        $publicEventUrl,
                        $report->reason_label,
                        $report->details ?: '*bez doplňujícího popisu*',
                        $report->contact_email ?: '*neuveden*',
                        $adminEventUrl
                    ),
                    'color' => 0xF59E0B, // Amber
                    'footer' => [
                        'text' => 'BrnoAkce User Feedback System',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        return self::send($payload);
    }

    /**
     * Sends a simple test notification to verify webhook configuration.
     */
    public static function test(string $customMessage = 'Testovací zpráva spojení s Discordem'): bool
    {
        return self::send([
            'embeds' => [
                [
                    'title' => '🔌 Test spojení BrnoAkce',
                    'description' => $customMessage,
                    'color' => 0x6366F1, // Indigo
                    'fields' => [
                        [
                            'name' => 'Server',
                            'value' => gethostname() . ' (Debian 12 Hetzner)',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Čas',
                            'value' => now()->toDateTimeString(),
                            'inline' => true,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
