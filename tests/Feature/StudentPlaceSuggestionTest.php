<?php

namespace Tests\Feature;

use App\Filament\Resources\StudentPlaceSuggestionResource;
use App\Livewire\ProposeStudentPlaceModal;
use App\Models\StudentPlace;
use App\Models\StudentPlaceCategory;
use App\Models\StudentPlaceSuggestion;
use App\Models\User;
use App\Services\DiscordNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StudentPlaceSuggestionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_propose_place_button_renders_on_places_page(): void
    {
        $response = $this->get('/mista');

        $response->assertStatus(200);
        $response->assertSee('Přidat místo');
        $response->assertSeeLivewire('propose-student-place-modal');
    }

    public function test_student_can_open_modal_and_submit_place_suggestion(): void
    {
        $category = StudentPlaceCategory::first();

        $testName = 'Testovací Studentská Kavárna ' . uniqid();

        Livewire::test(ProposeStudentPlaceModal::class)
            ->call('openModal')
            ->assertSet('isOpen', true)
            ->set('name', $testName)
            ->set('categoryId', $category?->id)
            ->set('address', 'Úvoz 50, Brno')
            ->set('highlight', 'Spousta zásuvek u oken')
            ->set('description', 'Klidné místo na psaní bakalářky se skvělou kávou a rychlou Wi-Fi.')
            ->set('websiteUrl', 'https://kavarna-priklad.cz')
            ->set('instagramHandle', 'kavarnauvoz')
            ->set('submitterName', 'Tomáš z VUT')
            ->set('submitterEmail', 'tomas@vut.cz')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('student_place_suggestions', [
            'name' => $testName,
            'address' => 'Úvoz 50, Brno',
            'highlight' => 'Spousta zásuvek u oken',
            'instagram_handle' => '@kavarnauvoz',
            'submitter_name' => 'Tomáš z VUT',
            'status' => 'pending',
        ]);
    }

    public function test_suggestion_validation_requires_mandatory_fields(): void
    {
        Livewire::test(ProposeStudentPlaceModal::class)
            ->call('openModal')
            ->set('name', '')
            ->set('address', '')
            ->set('description', '')
            ->call('submit')
            ->assertHasErrors(['name', 'address', 'description'])
            ->assertSet('submitted', false);
    }

    public function test_honeypot_silently_discards_bot_submissions(): void
    {
        $botName = 'Bot Spam Place ' . uniqid();

        Livewire::test(ProposeStudentPlaceModal::class)
            ->call('openModal')
            ->set('name', $botName)
            ->set('address', 'Spam Street 1, Brno')
            ->set('description', 'Spam link casino https://spam.com')
            ->set('honeypot', 'I am a spam bot')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseMissing('student_place_suggestions', [
            'name' => $botName,
        ]);
    }

    public function test_suggestion_approval_creates_active_student_place(): void
    {
        $category = StudentPlaceCategory::first();

        $suggestion = StudentPlaceSuggestion::create([
            'name' => 'Bistro Mezi Bloky ' . uniqid(),
            'student_place_category_id' => $category?->id,
            'address' => 'Kolejní 2, Brno',
            'highlight' => 'Studentská menza s terasou',
            'description' => 'Super jídlo a chillout po přednáškách přímo u kolejí PPV.',
            'website_url' => 'https://mezibloky.cz',
            'instagram_handle' => '@mezibloky',
            'status' => 'pending',
        ]);

        $createdPlace = $suggestion->approveAndCreatePlace([
            'lat' => 49.2275,
            'lng' => 16.5742,
        ]);

        $this->assertInstanceOf(StudentPlace::class, $createdPlace);
        $this->assertEquals('approved', $suggestion->fresh()->status);
        $this->assertEquals($createdPlace->id, $suggestion->fresh()->approved_student_place_id);
        $this->assertTrue($createdPlace->is_active);
        $this->assertEquals(49.2275, $createdPlace->lat);
        $this->assertEquals(16.5742, $createdPlace->lng);
        $this->assertEquals('Kolejní 2, Brno', $createdPlace->address);

        // Ověříme, že se místo propíše do výpisu všech aktivních míst
        $this->assertTrue(StudentPlace::where('id', $createdPlace->id)->where('is_active', true)->exists());
    }

    public function test_suggestion_rejection_marks_status_rejected(): void
    {
        $suggestion = StudentPlaceSuggestion::create([
            'name' => 'Nerelevantní místo ' . uniqid(),
            'address' => 'Neznámá 12',
            'description' => 'Místo, které už nefunguje.',
            'status' => 'pending',
        ]);

        $suggestion->update(['status' => 'rejected']);

        $this->assertEquals('rejected', $suggestion->fresh()->status);
        $this->assertNull($suggestion->fresh()->approved_student_place_id);
    }

    public function test_discord_notifier_handles_place_suggestion(): void
    {
        Http::fake();

        $category = StudentPlaceCategory::first();
        $suggestion = StudentPlaceSuggestion::create([
            'name' => 'Kavárna Trojka',
            'student_place_category_id' => $category?->id,
            'address' => 'Dominikánská 9, Brno',
            'description' => 'Kultovní studentská kavárna v Domě pánů z Kunštátu.',
            'status' => 'pending',
        ]);

        $sent = DiscordNotifier::notifyPlaceSuggestion($suggestion);

        // V testu s faked Http by měla metoda proběhnout nebo korektně vrátit bool bez výjimky
        $this->assertIsBool($sent);
    }
}
