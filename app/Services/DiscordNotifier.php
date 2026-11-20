<?php

namespace App\Services;

use App\Models\EventReport;
use App\Models\StudentPlaceSuggestion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotifier
{
    public static function isHttpFaked(): bool
    {
        try {
            $factory = app(\Illuminate\Http\Client\Factory::class);
            $ref = new \ReflectionProperty($factory, 'recording');
            $ref->setAccessible(true);
            return (bool) $ref->getValue($factory);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getWebhookUrl(): ?string
    {
        return config('services.discord.webhook_url');
    }

    public static function getSecurityWebhookUrl(): ?string
    {
        return config('services.discord.security_webhook_url');
    }

    /**
     * Escape Discord Markdown special characters from untrusted user input.
     */
    public static function escapeMarkdown(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        return preg_replace('/([\\\\`*_{}\\[\\]()#+\\-.!|~>])/u', '\\\\$1', $text);
    }

    /**
     * Sends a raw Discord Webhook payload to the general / scraping channel.
     */
    public static function send(array $payload): bool
    {
        if (app()->environment('testing') && !self::isHttpFaked()) {
            Log::info('Discord notification skipped: testing environment without Http::fake().');
            return false;
        }

        $url = self::getWebhookUrl();
        if (empty($url)) {
            Log::info('Discord notification skipped: DISCORD_WEBHOOK_URL is not set.');
            return false;
        }

        // Prevent @everyone / @here / role ping injection
        $payload['allowed_mentions'] = ['parse' => []];

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
     * Sends a raw Discord Webhook payload to the dedicated SECURITY channel.
     */
    public static function sendSecurity(array $payload): bool
    {
        if (app()->environment('testing') && !self::isHttpFaked()) {
            Log::info('Discord security notification skipped: testing environment without Http::fake().');
            return false;
        }

        $url = self::getSecurityWebhookUrl();
        if (empty($url)) {
            Log::info('Discord security notification skipped: DISCORD_SECURITY_WEBHOOK_URL is not set.');
            return false;
        }

        // Prevent @everyone / @here / role ping injection
        $payload['allowed_mentions'] = ['parse' => []];

        try {
            $response = Http::timeout(10)->post($url, $payload);
            if (!$response->successful()) {
                Log::warning('Discord security webhook failed: ' . $response->status() . ' - ' . $response->body());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('Discord security webhook exception: ' . $e->getMessage());
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

        $color = $hasErrors ? 0xE02424 : ($imported > 0 ? 0x10B981 : 0x3B82F6);
        $title = $hasErrors
            ? '[Chyba] BrnoAkce Scraping: Dokončeno s chybami'
            : ($imported > 0 ? "[Import] BrnoAkce Scraping: {$imported} nových akcí ke schválení" : '[Info] BrnoAkce Scraping: Žádné nové akce');

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

        if (!empty($newEvents)) {
            $eventLines = [];
            foreach (array_slice($newEvents, 0, 8) as $ev) {
                $missingWarnings = '';
                if (!empty($ev['missing'])) {
                    $missingWarnings = ' `[' . implode(', ', array_keys($ev['missing'])) . ']`';
                }

                $evLink = !empty($ev['id']) ? "{$adminUrl}/{$ev['id']}/edit" : $adminUrl;
                $dateStr = !empty($ev['starts_at']) ? date('d.m. H:i', strtotime($ev['starts_at'])) : 'neuvedeno';
                $venueStr = self::escapeMarkdown($ev['venue'] ?? $ev['location'] ?? 'Brno');
                $evTitle = self::escapeMarkdown(mb_substr($ev['title'] ?? 'Bez názvu', 0, 40));
                $orgName = self::escapeMarkdown($ev['organization'] ?? 'Univerzita');

                $eventLines[] = sprintf(
                    "• [%s](%s) — %s (%s, %s)%s",
                    $evTitle,
                    $evLink,
                    $dateStr,
                    $venueStr,
                    $orgName,
                    $missingWarnings
                );
            }

            if (count($newEvents) > 8) {
                $eventLines[] = sprintf("*...a dalších %d akcí*", count($newEvents) - 8);
            }

            $fields[] = [
                'name' => 'Nově nalezené akce',
                'value' => implode("\n", $eventLines),
                'inline' => false,
            ];
        }

        if (!empty($errors)) {
            $errLines = [];
            foreach (array_slice($errors, 0, 5) as $err) {
                $errSource = self::escapeMarkdown($err['source'] ?? 'Zdroj');
                $errMsg = self::escapeMarkdown(mb_substr($err['message'] ?? 'Neznámá chyba', 0, 100));
                $errLines[] = sprintf("• **%s**: %s", $errSource, $errMsg);
            }
            $fields[] = [
                'name' => 'Chyby při scrapování',
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
     * Sends an alert when a user reports an issue or broken event.
     */
    public static function notifyEventReport(EventReport $report): bool
    {
        if (app()->environment('testing') && !self::isHttpFaked()) {
            return false;
        }

        $event = $report->event;
        $adminEventUrl = config('app.url', 'https://brnoakce.tadeaskozub.com') . "/admin/events/{$report->event_id}/edit";
        $publicEventUrl = config('app.url', 'https://brnoakce.tadeaskozub.com') . "/akce/{$event?->slug}";

        $cleanTitle = self::escapeMarkdown($event?->title ?? 'Neznámá akce');
        $cleanReason = self::escapeMarkdown($report->reason_label);
        $cleanDetails = $report->details ? self::escapeMarkdown($report->details) : '*bez doplňujícího popisu*';
        $cleanContact = $report->contact_email ? self::escapeMarkdown($report->contact_email) : '*neuveden*';

        $payload = [
            'embeds' => [
                [
                    'title' => '[Hlášení] Nahlášena chyba u akce',
                    'description' => sprintf(
                        "Uživatel nahlásil problém u akce **[%s](%s)**.\n\n**Důvod:** `%s`\n**Podrobnosti:** %s\n**Kontakt:** %s\n\n[Upravit akci v administraci](%s)",
                        $cleanTitle,
                        $publicEventUrl,
                        $cleanReason,
                        $cleanDetails,
                        $cleanContact,
                        $adminEventUrl
                    ),
                    'color' => 0xF59E0B,
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
     * Sends an alert when a student submits a tip for a new student place in Brno.
     */
    public static function notifyPlaceSuggestion(StudentPlaceSuggestion $suggestion): bool
    {
        if (app()->environment('testing') && !self::isHttpFaked()) {
            return false;
        }

        $adminUrl = config('app.url', 'https://brnoakce.tadeaskozub.com') . "/admin/student-place-suggestions";

        $categoryName = self::escapeMarkdown($suggestion->category?->name ?? 'Místo');
        $submitterRaw = $suggestion->submitter_name ?: ($suggestion->submitter_email ?: 'Anonymní student');
        $submitter = self::escapeMarkdown($submitterRaw);

        $cleanName = self::escapeMarkdown($suggestion->name);
        $cleanAddress = self::escapeMarkdown($suggestion->address);
        $cleanDesc = self::escapeMarkdown($suggestion->description);

        $payload = [
            'embeds' => [
                [
                    'title' => '[Tip] Nový studentský tip na místo v Brně',
                    'description' => sprintf(
                        "Student navrhl nové místo: **%s** (%s)\n\n**Adresa:** %s\n**Proč doporučuje:** %s\n**Od:** %s\n\n[Zkontrolovat a schválit v administraci](%s)",
                        $cleanName,
                        $categoryName,
                        $cleanAddress,
                        $cleanDesc,
                        $submitter,
                        $adminUrl
                    ),
                    'color' => 0x6366F1,
                    'footer' => [
                        'text' => 'BrnoAkce Crowdsourcing System',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        return self::send($payload);
    }

    /**
     * Sends an alert to the dedicated Security Discord channel.
     */
    public static function notifySecurityAlert(string $title, string $status, array $fields = []): bool
    {
        $isSuccess = in_array(strtolower($status), ['success', 'passed', 'ok']);
        $color = $isSuccess ? 0x10B981 : 0xEF4444;

        $payload = [
            'embeds' => [
                [
                    'title' => self::escapeMarkdown($title),
                    'color' => $color,
                    'fields' => $fields,
                    'footer' => [
                        'text' => 'BrnoAkce DevSecOps Security System',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        return self::sendSecurity($payload);
    }

    /**
     * Sends a simple test notification to verify webhook configuration.
     */
    public static function test(string $customMessage = 'Testovací zpráva spojení s Discordem', string $channel = 'general'): bool
    {
        $isSecurity = strtolower($channel) === 'security';

        $payload = [
            'embeds' => [
                [
                    'title' => $isSecurity ? '[Security] Test spojení – Bezpečnostní kanál' : '[Webhook] Test spojení – Kanál pro scraping a hlášení',
                    'description' => self::escapeMarkdown($customMessage),
                    'color' => $isSecurity ? 0x10B981 : 0x6366F1,
                    'fields' => [
                        [
                            'name' => 'Server',
                            'value' => gethostname() . ' (Debian 12 Hetzner)',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Kanál',
                            'value' => $isSecurity ? 'Bezpečnostní audity a Pentesty' : 'Scraping a Feedback',
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
        ];

        return $isSecurity ? self::sendSecurity($payload) : self::send($payload);
    }
}
