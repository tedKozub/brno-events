<?php

namespace Tests\Feature;

use App\Models\StudentPlace;
use App\Models\StudentPlaceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class StudentPlacesAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin_test_' . uniqid() . '@brnoakce.cz',
            'role' => 'superadmin',
        ]);
    }

    public function test_admin_can_access_student_place_categories_resource(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/student-place-categories');
        $response->assertStatus(200);
        $response->assertSee('Kategorie míst');
    }

    public function test_admin_can_access_student_places_resource(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/student-places');
        $response->assertStatus(200);
        $response->assertSee('Místa v mapě');
    }

    public function test_frontend_renders_custom_database_place_and_category(): void
    {
        $customCat = StudentPlaceCategory::create([
            'name' => 'Coworking & Huby',
            'short_name' => 'Coworking',
            'slug' => 'coworking-test',
            'color' => '#14b8a6',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $customPlace = StudentPlace::create([
            'student_place_category_id' => $customCat->id,
            'name' => 'Impact Hub Brno Test',
            'address' => 'Cyrilská 7, Brno',
            'lat' => 49.1915,
            'lng' => 16.6210,
            'description' => 'Inovativní prostor pro práci, meetingy a startupy.',
            'tags' => ['Coworking', 'Káva', 'Eventy'],
            'highlight' => 'Největší coworking v Brně',
            'is_active' => true,
        ]);

        Livewire::test(\App\Livewire\StudentPlaces::class)
            ->assertSee('Coworking')
            ->assertSee('Impact Hub Brno Test')
            ->assertSee('Cyrilská 7, Brno');
    }

    public function test_inactive_places_are_hidden_from_frontend(): void
    {
        $cat = StudentPlaceCategory::firstOrCreate(
            ['slug' => 'study'],
            ['name' => 'Studovny', 'short_name' => 'Studovna', 'color' => '#8b5cf6']
        );

        $hiddenPlace = StudentPlace::create([
            'student_place_category_id' => $cat->id,
            'name' => 'Tajná neaktivní studovna',
            'address' => 'Neznámá 99, Brno',
            'lat' => 49.2000,
            'lng' => 16.6000,
            'description' => 'Tohle nikdo nesmí vidět.',
            'tags' => ['Tajné'],
            'is_active' => false,
        ]);

        Livewire::test(\App\Livewire\StudentPlaces::class)
            ->assertDontSee('Tajná neaktivní studovna');
    }
}
