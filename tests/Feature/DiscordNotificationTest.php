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

        $result = DiscordNotifier::test('Test message');
        $this->assertFalse($result);

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
}
