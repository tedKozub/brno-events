<?php

namespace Tests\Feature;

use App\Helpers\BrandHelper;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StudyBrnoRebrandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_main_livebrno_host_renders_studybrno_livebrno_branding(): void
    {
        $response = $this->get('http://livebrno.tadeaskozub.com/');

        $response->assertStatus(200);
        $response->assertSee('studyBrno|liveBrno');
        $response->assertSee('<title>studyBrno|liveBrno | Studentský a městský život v Brně</title>', false);
        $response->assertSee('<meta property="og:site_name" content="studyBrno|liveBrno">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-title" content="studyBrno|liveBrno">', false);
        $response->assertSee('study</span>Brno', false);
        $response->assertSee('live</span>Brno', false);
    }

    public function test_navbar_has_no_calendar_icon(): void
    {
        $response = $this->get('http://livebrno.tadeaskozub.com/');

        $response->assertStatus(200);
        // Ensure calendar SVG icon in logo box was removed
        $response->assertDontSee('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>', false);
    }

    public function test_sslip_io_subdomains_render_studybrno_livebrno_branding(): void
    {
        $response = $this->get('http://livebrno.116.203.250.252.sslip.io/');

        $response->assertStatus(200);
        $response->assertSee('studyBrno|liveBrno');
    }

    public function test_places_page_renders_dynamic_title_for_studybrno(): void
    {
        $response = $this->get('http://livebrno.tadeaskozub.com/mista');

        $response->assertStatus(200);
        $response->assertSee('<title>Mapa studentských míst v Brně | studyBrno|liveBrno</title>', false);
    }

    public function test_event_detail_renders_dynamic_title_for_studybrno(): void
    {
        $category = Category::first() ?? Category::create([
            'name' => 'Párty',
            'slug' => 'party-' . uniqid(),
            'color' => '#ea580c',
            'icon' => 'sparkles',
        ]);

        $venue = Venue::first() ?? Venue::create([
            'name' => 'Test Venue',
            'slug' => 'test-venue-' . uniqid(),
            'address' => 'Štefánikova 24, Brno',
            'latitude' => 49.2098,
            'longitude' => 16.6025,
        ]);

        $org = Organization::first() ?? Organization::create([
            'name' => 'Studentská unie',
            'slug' => 'studentska-unie-' . uniqid(),
        ]);

        $event = Event::create([
            'title' => 'Velká Studentská Párty',
            'slug' => 'velka-studentska-party-' . uniqid(),
            'category_id' => $category->id,
            'venue_id' => $venue->id,
            'organization_id' => $org->id,
            'starts_at' => now()->addDays(2),
            'status' => 'published',
            'description' => 'Test event description',
        ]);

        $studyResponse = $this->get('http://livebrno.tadeaskozub.com/akce/' . $event->slug);

        $studyResponse->assertStatus(200);
        $studyResponse->assertSee('<title>Velká Studentská Párty • studyBrno|liveBrno</title>', false);
    }

    public function test_dynamic_manifest_json_for_livebrno(): void
    {
        $studyResponse = $this->get('http://livebrno.tadeaskozub.com/manifest.json');

        $studyResponse->assertStatus(200);
        $studyResponse->assertJsonFragment([
            'name' => 'studyBrno|liveBrno – Studentský a městský život v Brně',
            'short_name' => 'liveBrno',
        ]);
    }

    public function test_brand_helper_methods(): void
    {
        $this->assertTrue(BrandHelper::isStudyBrno());
        $this->assertEquals('studyBrno|liveBrno', BrandHelper::brandName());
        $this->assertEquals('liveBrno', BrandHelper::shortName());
        $this->assertEquals('Student & Life', BrandHelper::badgeText());
    }
}
