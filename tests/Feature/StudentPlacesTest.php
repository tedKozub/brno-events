<?php

namespace Tests\Feature;

use App\Livewire\StudentPlaces;
use Livewire\Livewire;
use Tests\TestCase;

class StudentPlacesTest extends TestCase
{
    public function test_student_places_page_renders_successfully(): void
    {
        $response = $this->get('/mista');

        $response->assertStatus(200);
        $response->assertSee('Studentská místa v Brně');
        $response->assertSee('Moravská zemská knihovna (MZK)');
        $response->assertSee('Na Božence');
        $response->assertSee('Denisovy sady');
    }

    public function test_can_filter_student_places_by_category(): void
    {
        Livewire::test(StudentPlaces::class)
            ->set('selectedCategory', 'study')
            ->assertSee('Moravská zemská knihovna (MZK)')
            ->assertDontSee('Na Božence')
            ->set('selectedCategory', 'pubs')
            ->assertSee('Na Božence')
            ->assertDontSee('Moravská zemská knihovna (MZK)');
    }

    public function test_can_search_student_places(): void
    {
        Livewire::test(StudentPlaces::class)
            ->set('search', 'Božence')
            ->assertSee('Na Božence')
            ->assertDontSee('Moravská zemská knihovna (MZK)');
    }

    public function test_search_for_coffee_finds_cafes_and_updates_category_counts(): void
    {
        $component = Livewire::test(StudentPlaces::class)
            ->set('search', 'káva');

        // Musí najít typické brněnské kavárny
        $component->assertSee('Skøg Urban Hub')
            ->assertSee('V Melounovém cukru')
            ->assertSee('Punkt cafe');

        // Počty kategorií se musí dynamicky aktualizovat dle výsledků hledání
        $counts = $component->get('categoryCounts');
        $this->assertGreaterThan(0, $counts['cafes']);
        $this->assertEquals(0, $counts['date']); // Rande & výhledy nemají kávu
    }
}
