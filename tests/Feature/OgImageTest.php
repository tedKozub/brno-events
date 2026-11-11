<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OgImageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_og_image_endpoint_returns_valid_jpeg(): void
    {
        $event = Event::create([
            'title' => 'Testovací Studentská Párty',
            'slug' => 'testovaci-studentska-party',
            'status' => 'published',
            'starts_at' => now()->addDay(),
            'price_type' => 'free',
        ]);

        $response = $this->get(route('event.og-image', ['slug' => $event->slug]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_event_fallback_cover_image_points_to_dynamic_og_image(): void
    {
        $event = Event::create([
            'title' => 'Testovací Přednáška',
            'slug' => 'testovaci-prednaska',
            'status' => 'published',
            'starts_at' => now()->addDay(),
            'price_type' => 'free',
            'cover_image' => null,
        ]);

        $this->assertStringContainsString('og-image.jpg', $event->cover_image_url);
        $this->assertStringNotContainsString('unsplash.com', $event->cover_image_url);
    }
}
