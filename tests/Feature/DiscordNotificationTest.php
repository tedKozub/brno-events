<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventReport;
use App\Services\DiscordNotifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordNotificationTest extends TestCase
{
    public function test_discord_notifier_handles_missing_webhook_gracefully(): void
    {
        config(['services.discord.webhook_url' => null]);
        config(['services.discord.security_webhook_url' => null]);

        $result = DiscordNotifier::test('Test message');
        $this->assertFalse($result);

        $secResult = DiscordNotifier::test('Test security', 'security');
        $this->assertFalse($secResult);

        $scrapeResult = DiscordNotifier::notifyScrapeSummary([
            'imported' => 2,
            'skipped' => 10,
            'sources' => 5,
        ], [
            ['title' => 'Test akce', 'starts_at' => now()->toDateTimeString(), 'venue' => 'Fléda']
        ]);
        $this->assertFalse($scrapeResult);
    }

    public function test_discord_notifier_sends_payload_when_webhook_configured(): void
    {
        $fakeWebhook = 'https://discord.com/api/webhooks/fake/token';
        config(['services.discord.webhook_url' => $fakeWebhook]);

        Http::fake([
            $fakeWebhook => Http::response(['id' => '123'], 204),
        ]);

        $result = DiscordNotifier::test('Test call');
        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($fakeWebhook) {
            return $request->url() === $fakeWebhook &&
                   isset($request->data()['embeds']);
        });
    }

    public function test_discord_security_webhook_sends_to_separate_channel(): void
    {
        $generalWebhook = 'https://discord.com/api/webhooks/general/token';
        $securityWebhook = 'https://discord.com/api/webhooks/security/token';

        config(['services.discord.webhook_url' => $generalWebhook]);
        config(['services.discord.security_webhook_url' => $securityWebhook]);

        Http::fake([
            $generalWebhook => Http::response(['id' => '1'], 204),
            $securityWebhook => Http::response(['id' => '2'], 204),
        ]);

        // Odeslání do bezpečnostního kanálu
        $result = DiscordNotifier::notifySecurityAlert(
            '🛡️ Test Security Alert',
            'passed',
            [['name' => 'Scanner', 'value' => 'OWASP ZAP', 'inline' => true]]
        );
        $this->assertTrue($result);

        // Ověření, že zpráva šla POUZE na security webhook, nikoliv na general webhook
        Http::assertSent(function ($request) use ($securityWebhook) {
            return $request->url() === $securityWebhook &&
                   str_contains($request->body(), 'Security Alert');
        });

        Http::assertNotSent(function ($request) use ($generalWebhook) {
            return $request->url() === $generalWebhook &&
                   str_contains($request->body(), 'Security Alert');
        });
    }
}
