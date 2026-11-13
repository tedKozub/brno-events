<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Services\EventBannerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EventBannerScraperTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Helper to generate a dummy image as binary string (> 4KB).
     */
    protected function createDummyImageBytes(int $width = 600, int $height = 800): string
    {
        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 234, 88, 12);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Add some lines so the compressed JPEG exceeds 4096 bytes
        for ($i = 0; $i < 50; $i++) {
            $c = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $c);
        }

        ob_start();
        imagejpeg($image, null, 90);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    public function test_hierarchy_prioritizes_poster_over_banner_and_article(): void
    {
        $dummyImage = $this->createDummyImageBytes(600, 800);

        Http::fake([
            'https://example.com/events/101' => Http::response('
                <!DOCTYPE html>
                <html>
                <head>
                    <meta property="og:image" content="https://example.com/images/hero_banner.jpg" />
                </head>
                <body>
                    <h1>Koncert & Výstava</h1>
                    <img src="https://example.com/images/article_photo.jpg" alt="Foto z minula" width="400" height="300" />
                    <a href="https://example.com/files/plakat_a4_final.jpg">Stáhnout oficiální plakát akce</a>
                </body>
                </html>
            ', 200),
            'https://example.com/files/plakat_a4_final.jpg' => Http::response($dummyImage, 200, ['Content-Type' => 'image/jpeg']),
            'https://example.com/images/hero_banner.jpg' => Http::response($dummyImage, 200, ['Content-Type' => 'image/jpeg']),
            'https://example.com/images/article_photo.jpg' => Http::response($dummyImage, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $event = Event::create([
            'title' => 'Test Koncert s plakátem',
            'starts_at' => Carbon::tomorrow(),
            'source_url' => 'https://example.com/events/101',
            'status' => 'published',
        ]);

        $service = app(EventBannerService::class);
        $result = $service->enrichEvent($event);

        $this->assertTrue($result);
        $event->refresh();

        $this->assertEquals('poster', $event->cover_image_type);
        $this->assertTrue($event->isPoster());
        $this->assertEquals('Plakát akce', $event->cover_image_type_display);
        $this->assertStringContainsString('plakat_a4_final.jpg', $event->original_image_url);
        $this->assertStringStartsWith('events/covers/poster_', $event->cover_image);

        Storage::disk('public')->assertExists($event->cover_image);
    }

    public function test_hierarchy_selects_banner_when_poster_is_missing(): void
    {
        $dummyImage = $this->createDummyImageBytes(1200, 630);

        Http::fake([
            'https://example.com/events/102' => Http::response('
                <!DOCTYPE html>
                <html>
                <head>
                    <meta property="og:image" content="https://example.com/images/web_banner_main.jpg" />
                </head>
                <body>
                    <h1>Technologická konference</h1>
                    <img src="https://example.com/images/random_speaker.jpg" alt="Přednášející" width="400" height="300" />
                </body>
                </html>
            ', 200),
            'https://example.com/images/web_banner_main.jpg' => Http::response($dummyImage, 200, ['Content-Type' => 'image/jpeg']),
            'https://example.com/images/random_speaker.jpg' => Http::response($dummyImage, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $event = Event::create([
            'title' => 'Test Konference s bannerem',
            'starts_at' => Carbon::tomorrow(),
            'source_url' => 'https://example.com/events/102',
            'status' => 'published',
        ]);

        $service = app(EventBannerService::class);
        $result = $service->enrichEvent($event);

        $this->assertTrue($result);
        $event->refresh();

        $this->assertEquals('banner', $event->cover_image_type);
        $this->assertFalse($event->isPoster());
        $this->assertEquals('Webový banner', $event->cover_image_type_display);
        $this->assertStringContainsString('web_banner_main.jpg', $event->original_image_url);
        $this->assertStringStartsWith('events/covers/banner_', $event->cover_image);

        Storage::disk('public')->assertExists($event->cover_image);
    }

    public function test_hierarchy_selects_article_image_when_poster_and_banner_are_missing(): void
    {
        $dummyImage = $this->createDummyImageBytes(500, 350);

        Http::fake([
            'https://example.com/events/103' => Http::response('
                <!DOCTYPE html>
                <html>
                <head><title>Přednáška</title></head>
                <body>
                    <h1>Seminář o robotice</h1>
                    <img src="https://example.com/images/robot_photo.jpg" alt="Robot v laboratoři" width="500" height="350" />
                </body>
                </html>
            ', 200),
            'https://example.com/images/robot_photo.jpg' => Http::response($dummyImage, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $event = Event::create([
            'title' => 'Test Robotika',
            'starts_at' => Carbon::tomorrow(),
            'source_url' => 'https://example.com/events/103',
            'status' => 'published',
        ]);

        $service = app(EventBannerService::class);
        $result = $service->enrichEvent($event);

        $this->assertTrue($result);
        $event->refresh();

        $this->assertEquals('article', $event->cover_image_type);
        $this->assertEquals('Obrázek z článku', $event->cover_image_type_display);
        $this->assertStringContainsString('robot_photo.jpg', $event->original_image_url);
        $this->assertStringStartsWith('events/covers/article_', $event->cover_image);

        Storage::disk('public')->assertExists($event->cover_image);
    }

    public function test_hierarchy_falls_back_to_stock_when_no_valid_images_exist(): void
    {
        Http::fake([
            'https://example.com/events/104' => Http::response('
                <!DOCTYPE html>
                <html>
                <head><title>Čistě textová událost</title></head>
                <body>
                    <h1>Schůzka akademického senátu</h1>
                    <p>Program zasedání...</p>
                    <!-- Jen logo fakulty, které musí být vyřazeno -->
                    <img src="https://example.com/images/faculty_logo.png" alt="Logo fakulty" />
                </body>
                </html>
            ', 200),
        ]);

        $partyCategory = Category::firstOrCreate(['slug' => 'party-kluby'], ['name' => 'Party & Kluby']);

        $event = Event::create([
            'title' => 'Test Textová akce',
            'category_id' => $partyCategory->id,
            'starts_at' => Carbon::tomorrow(),
            'source_url' => 'https://example.com/events/104',
            'status' => 'published',
        ]);

        $service = app(EventBannerService::class);
        $result = $service->enrichEvent($event);

        $this->assertFalse($result);
        $event->refresh();

        $this->assertEquals('stock', $event->effective_cover_image_type);
        $this->assertTrue($event->isStockImage());
        $this->assertEquals('Ilustrační foto', $event->cover_image_type_display);
        $this->assertStringContainsString('images.unsplash.com', $event->cover_image_url);
    }

    public function test_clean_cdn_url_strips_muni_crop_parameters(): void
    {
        $service = app(EventBannerService::class);

        $croppedUrl = 'https://cdn.muni.cz/media/4254393/fogginess_plakat_a4_ver_tecl.png?mode=crop&width=544&height=306&rnd=13360492823';
        $cleaned = $service->cleanCdnUrl($croppedUrl);

        $this->assertEquals('https://cdn.muni.cz/media/4254393/fogginess_plakat_a4_ver_tecl.png', $cleaned);
    }

    public function test_detail_view_renders_poster_badge_and_modal(): void
    {
        $dummyImage = $this->createDummyImageBytes(600, 900);
        $storagePath = 'events/covers/poster_test_sample.jpg';
        Storage::disk('public')->put($storagePath, $dummyImage);

        $event = Event::create([
            'title' => 'Plakátová Výstava 2026',
            'starts_at' => Carbon::tomorrow()->setHour(18)->setMinute(0),
            'cover_image' => $storagePath,
            'cover_image_type' => 'poster',
            'original_image_url' => 'https://example.com/poster.jpg',
            'status' => 'published',
            'source_url' => 'https://example.com/vystava',
        ]);

        $response = $this->get('/akce/' . $event->slug);

        $response->assertStatus(200);
        $response->assertSee('Plakát akce');
        $response->assertSee('id="poster_modal"', false);
        $response->assertSee('Celý plakát');
        // Detail UI must remain clean without legal disclaimer clutter
        $response->assertDontSee('zpravodajské licence');

        // Licensing terms are properly maintained in Terms of Use (/podminky)
        $termsResponse = $this->get('/podminky');
        $termsResponse->assertStatus(200);
        $termsResponse->assertSee('zpravodajská licence');
        $termsResponse->assertSee('autorského zákona');
    }

    public function test_vertical_article_image_is_automatically_promoted_to_poster_by_aspect_ratio(): void
    {
        // 600x900 is vertical aspect ratio 1:1.5
        $dummyImage = $this->createDummyImageBytes(600, 900);

        Http::fake([
            'https://example.com/events/vertical-photo' => Http::response('
                <!DOCTYPE html>
                <html>
                <body>
                    <h1>Vernisáž studentů</h1>
                    <img src="https://example.com/uploads/fotka_z_atelieru.jpg" alt="Grafika výstavy" width="600" height="900" />
                </body>
                </html>
            ', 200),
            'https://example.com/uploads/fotka_z_atelieru.jpg' => Http::response($dummyImage, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $event = Event::create([
            'title' => 'Student Art Show',
            'starts_at' => Carbon::tomorrow(),
            'source_url' => 'https://example.com/events/vertical-photo',
            'status' => 'published',
        ]);

        $service = app(EventBannerService::class);
        $result = $service->enrichEvent($event);

        $this->assertTrue($result);
        $event->refresh();

        // Should be promoted to poster due to vertical aspect ratio!
        $this->assertEquals('poster', $event->cover_image_type);
        $this->assertTrue($event->isPoster());
        $this->assertStringStartsWith('events/covers/poster_', $event->cover_image);
    }

    public function test_event_board_renders_poster_tag_on_card(): void
    {
        $dummyImage = $this->createDummyImageBytes(600, 900);
        $storagePath = 'events/covers/poster_card_test.jpg';
        Storage::disk('public')->put($storagePath, $dummyImage);

        $event = Event::create([
            'title' => 'Unikátní Přednáška s plakátem',
            'starts_at' => Carbon::tomorrow()->setHour(14)->setMinute(0),
            'cover_image' => $storagePath,
            'cover_image_type' => 'poster',
            'status' => 'published',
        ]);

        Livewire::test(\App\Livewire\EventBoard::class)
            ->assertSee('Unikátní Přednáška s plakátem')
            ->assertSee('Plakát');
    }
}
