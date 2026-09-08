<?php

namespace Tests\Feature\Controllers;

use App\Models\Location;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create(['email' => 'admin@admin.com'])
        );

        $this->seed(PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    #[Test]
    public function it_displays_index_view_with_locations(): void
    {
        $locations = Location::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('locations.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.locations.index')
            ->assertViewHas('locations');
    }

    #[Test]
    public function it_displays_create_view_for_location(): void
    {
        $response = $this->get(route('locations.create'));

        $response->assertOk()->assertViewIs('app.locations.create');
    }

    #[Test]
    public function it_stores_the_location(): void
    {
        $data = Location::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('locations.store'), $data);

        $this->assertDatabaseHas('locations', $data);

        $location = Location::latest('id')->first();

        $response->assertRedirect(route('locations.edit', $location));
    }

    #[Test]
    public function it_displays_show_view_for_location(): void
    {
        $location = Location::factory()->create();

        $response = $this->get(route('locations.show', $location));

        $response
            ->assertOk()
            ->assertViewIs('app.locations.show')
            ->assertViewHas('location');
    }

    #[Test]
    public function it_displays_edit_view_for_location(): void
    {
        $location = Location::factory()->create();

        $response = $this->get(route('locations.edit', $location));

        $response
            ->assertOk()
            ->assertViewIs('app.locations.edit')
            ->assertViewHas('location');
    }

    #[Test]
    public function it_updates_the_location(): void
    {
        $location = Location::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(15),
        ];

        $response = $this->put(route('locations.update', $location), $data);

        $data['id'] = $location->id;

        $this->assertDatabaseHas('locations', $data);

        $response->assertRedirect(route('locations.edit', $location));
    }

    #[Test]
    public function it_deletes_the_location(): void
    {
        $location = Location::factory()->create();

        $response = $this->delete(route('locations.destroy', $location));

        $response->assertRedirect(route('locations.index'));

        $this->assertModelMissing($location);
    }
}
