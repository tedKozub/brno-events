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

    public function test_default_host_renders_brnoakce_branding(): void
    {
        $response = $this->get('http://brnoakce.tadeaskozub.com/');

        $response->assertStatus(200);
        $response->assertSee('BrnoAkce');
        $response->assertSee('AKCE<span class="text-primary">BRNO</span>', false);
        $response->assertSee('<meta property="og:site_name" content="BrnoAkce">', false);
    }

    public function test_studybrno_host_renders_studybrno_livebrno_branding(): void
    {
        $response = $this->get('http://studybrno.tadeaskozub.com/');

        $response->assertStatus(200);
        $response->assertSee('studyBrno|liveBrno');
        $response->assertSee('<title>studyBrno|liveBrno | Studentský a městský život v Brně</title>', false);
        $response->assertSee('<meta property="og:site_name" content="studyBrno|liveBrno">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-title" content="studyBrno|liveBrno">', false);
        $response->assertSee('study</span>Brno', false);
        $response->assertSee('live</span>Brno', false);
    }

    public function test_livebrno_host_renders_studybrno_livebrno_branding(): void
    {
        $response = $this->get('http://livebrno.tadeaskozub.com/');

        $response->assertStatus(200);
        $response->assertSee('studyBrno|liveBrno');
        $response->assertSee('<meta property="og:site_name" content="studyBrno|liveBrno">', false);
    }

    public function test_sslip_io_subdomains_render_studybrno_livebrno_branding(): void
    {
        $response = $this->get('http://studybrno.116.203.250.252.sslip.io/');

        $response->assertStatus(200);
        $response->assertSee('studyBrno|liveBrno');
    }

    public function test_places_page_renders_dynamic_title_for_studybrno(): void
    {
        $response = $this->get('http://studybrno.tadeaskozub.com/mista');

        $response->assertStatus(200);
        $response->assertSee('<title>Mapa studentských míst v Brně | studyBrno|liveBrno</title>', false);
    }

    public function test_event_detail_renders_dynamic_title_for_studybrno(): void
    {
        $category = Category::create([
            'name' => 'Párty',
            'slug' => 'party',
            'color' => '#ea580c',
            'icon' => 'sparkles',
        ]);

        $venue = Venue::create([
            'name' => 'Fléda Club',
            'slug' => 'fleda-club',
            'address' => 'Štefánikova 24, Brno',
            'latitude' => 49.2098,
            'longitude' => 16.6025,
        ]);

        $org = Organization::create([
            'name' => 'Studentská unie',
            'slug' => 'studentska-unie',
        ]);

        $event = Event::create([
            'title' => 'Velká Studentská Párty',
            'slug' => 'velka-studentska-party',
            'category_id' => $category->id,
            'venue_id' => $venue->id,
            'organization_id' => $org->id,
            'starts_at' => now()->addDays(2),
            'status' => 'published',
            'description' => 'Test event description',
        ]);

        $studyResponse = $this->get('http://studybrno.tadeaskozub.com/akce/' . $event->slug);

        $studyResponse->assertStatus(200);
        $studyResponse->assertSee('<title>Velká Studentská Párty • studyBrno|liveBrno</title>', false);

        $brnoAkceResponse = $this->get('http://brnoakce.tadeaskozub.com/akce/' . $event->slug);
        $brnoAkceResponse->assertStatus(200);
        $brnoAkceResponse->assertSee('<title>Velká Studentská Párty • BrnoAkce</title>', false);
    }

    public function test_dynamic_manifest_json_adapts_to_domain(): void
    {
        $studyResponse = $this->get('http://studybrno.tadeaskozub.com/manifest.json');

        $studyResponse->assertStatus(200);
        $studyResponse->assertJsonFragment([
            'name' => 'studyBrno|liveBrno – Studentský a městský život v Brně',
            'short_name' => 'studyBrno',
        ]);

        $defaultResponse = $this->get('http://brnoakce.tadeaskozub.com/manifest.json');
        $defaultResponse->assertStatus(200);
        $defaultResponse->assertJsonFragment([
            'name' => 'BrnoAkce – Studentské akce v Brně',
            'short_name' => 'BrnoAkce',
        ]);
    }

    public function test_query_param_override_activates_studybrno(): void
    {
        $response = $this->get('/?brand=studybrno');

        $response->assertStatus(200);
        $response->assertSee('studyBrno|liveBrno');
    }

    public function test_brand_helper_env_configuration(): void
    {
        config(['app.brand' => 'studybrno']);
        $this->assertTrue(BrandHelper::isStudyBrno());
        $this->assertEquals('studyBrno|liveBrno', BrandHelper::brandName());

        config(['app.brand' => 'auto']);
        $this->assertEquals('BrnoAkce', BrandHelper::brandName());
    }
}
