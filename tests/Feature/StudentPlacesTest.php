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
}
