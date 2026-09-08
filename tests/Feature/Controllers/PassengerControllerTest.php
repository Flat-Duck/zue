<?php

namespace Tests\Feature\Controllers;

use App\Models\Passenger;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PassengerControllerTest extends TestCase
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
    public function it_displays_index_view_with_passengers(): void
    {
        $passengers = Passenger::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('passengers.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.passengers.index')
            ->assertViewHas('passengers');
    }

    #[Test]
    public function it_displays_create_view_for_passenger(): void
    {
        $response = $this->get(route('passengers.create'));

        $response->assertOk()->assertViewIs('app.passengers.create');
    }

    #[Test]
    public function it_stores_the_passenger(): void
    {
        $data = Passenger::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('passengers.store'), $data);

        $this->assertDatabaseHas('passengers', $data);

        $passenger = Passenger::latest('id')->first();

        $response->assertRedirect(route('passengers.edit', $passenger));
    }

    #[Test]
    public function it_displays_show_view_for_passenger(): void
    {
        $passenger = Passenger::factory()->create();

        $response = $this->get(route('passengers.show', $passenger));

        $response
            ->assertOk()
            ->assertViewIs('app.passengers.show')
            ->assertViewHas('passenger');
    }

    #[Test]
    public function it_displays_edit_view_for_passenger(): void
    {
        $passenger = Passenger::factory()->create();

        $response = $this->get(route('passengers.edit', $passenger));

        $response
            ->assertOk()
            ->assertViewIs('app.passengers.edit')
            ->assertViewHas('passenger');
    }

    #[Test]
    public function it_updates_the_passenger(): void
    {
        $passenger = Passenger::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'company' => $this->faker->text(255),
            'number' => $this->faker->text(255),
            'nationality' => $this->faker->text(255),
        ];

        $response = $this->put(route('passengers.update', $passenger), $data);

        $data['id'] = $passenger->id;

        $this->assertDatabaseHas('passengers', $data);

        $response->assertRedirect(route('passengers.edit', $passenger));
    }

    #[Test]
    public function it_deletes_the_passenger(): void
    {
        $passenger = Passenger::factory()->create();

        $response = $this->delete(route('passengers.destroy', $passenger));

        $response->assertRedirect(route('passengers.index'));

        $this->assertModelMissing($passenger);
    }
}
