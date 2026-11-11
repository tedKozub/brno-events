<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeoAndPwaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sitemap_returns_valid_xml_with_events(): void
    {
        $org = Organization::first() ?? Organization::create(['name' => 'Org Test', 'slug' => 'org-test']);
        $venue = Venue::first() ?? Venue::create(['name' => 'Venue Test', 'slug' => 'venue-test', 'city' => 'Brno']);
        $cat = Category::first() ?? Category::create(['name' => 'Kultura', 'slug' => 'kultura', 'color' => '#f59e0b']);

        $event = Event::create([
            'title' => 'Testovaci Akce Pro Sitemap',
            'slug' => 'testovaci-akce-pro-sitemap-' . uniqid(),
            'starts_at' => now()->addDays(2),
            'organization_id' => $org->id,
            'venue_id' => $venue->id,
            'category_id' => $cat->id,
            'status' => 'published',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
        $response->assertSee(url('/akce/' . $event->slug), false);
        $response->assertSee(url('/'), false);
    }

    public function test_robots_txt_exists_and_references_sitemap(): void
    {
        $robotsPath = public_path('robots.txt');
        $this->assertTrue(File::exists($robotsPath), 'robots.txt should exist in public directory.');

        $content = File::get($robotsPath);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Sitemap: https://brnoakce.tadeaskozub.com/sitemap.xml', $content);
    }

    public function test_manifest_json_exists_and_has_valid_pwa_configuration(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertTrue(File::exists($manifestPath), 'manifest.json should exist in public directory.');

        $data = json_decode(File::get($manifestPath), true);
        $this->assertIsArray($data);
        $this->assertEquals('BrnoAkce – Studentské akce v Brně', $data['name']);
        $this->assertEquals('BrnoAkce', $data['short_name']);
        $this->assertEquals('/', $data['start_url']);
        $this->assertEquals('standalone', $data['display']);
        $this->assertEquals('#6366f1', $data['theme_color']);
        $this->assertNotEmpty($data['icons']);
    }

    public function test_pwa_icons_and_service_worker_exist(): void
    {
        $this->assertTrue(File::exists(public_path('sw.js')), 'sw.js service worker must exist');
        $this->assertTrue(File::exists(public_path('icons/icon-192x192.png')), '192x192 icon must exist');
        $this->assertTrue(File::exists(public_path('icons/icon-512x512.png')), '512x512 icon must exist');
        $this->assertTrue(File::exists(public_path('icons/apple-touch-icon.png')), 'apple-touch-icon must exist');
        $this->assertTrue(File::exists(public_path('images/og-brnoakce.jpg')), 'default og image must exist');
    }

    public function test_homepage_includes_opengraph_and_pwa_meta(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('rel="manifest" href="/manifest.json"', false);
        $response->assertSee('name="theme-color" content="#6366f1"', false);
        $response->assertSee('property="og:site_name" content="BrnoAkce"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
    }

    public function test_event_detail_includes_opengraph_and_schema_org_json_ld(): void
    {
        $org = Organization::first() ?? Organization::create(['name' => 'Spolek Studentu', 'slug' => 'spolek-studentu']);
        $venue = Venue::first() ?? Venue::create(['name' => 'Klub Valka', 'slug' => 'klub-valka', 'city' => 'Brno', 'address' => 'Veveri 15']);
        $cat = Category::first() ?? Category::create(['name' => 'Hudba', 'slug' => 'hudba', 'color' => '#10b981']);

        $event = Event::create([
            'title' => 'Mega Koncert v Brne',
            'slug' => 'mega-koncert-v-brne-' . uniqid(),
            'description' => 'Parádní večer plný muziky a zábavy.',
            'starts_at' => now()->addDays(5)->setTime(20, 0),
            'organization_id' => $org->id,
            'venue_id' => $venue->id,
            'category_id' => $cat->id,
            'status' => 'published',
            'price_type' => 'fixed',
            'price_amount' => 150,
            'cover_image' => 'https://example.com/poster.jpg',
        ]);

        $response = $this->get('/akce/' . $event->slug);

        $response->assertStatus(200);
        $response->assertSee('property="og:title" content="' . e($event->title) . ' • BrnoAkce"', false);
        $response->assertSee('property="og:image" content="https://example.com/poster.jpg"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type": "Event"', false);
        $response->assertSee('"name": "' . $event->title . '"', false);
        $response->assertSee('"startDate":', false);
        $response->assertSee($venue->name, false);
    }
}
