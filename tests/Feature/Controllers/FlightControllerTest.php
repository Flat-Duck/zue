<?php

namespace Tests\Feature\Controllers;

use App\Models\Flight;
use App\Models\FlightRoute;
use App\Models\User;
use Database\Seeders\FlightRoutesSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlightControllerTest extends TestCase
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
    public function it_displays_index_view_with_flights(): void
    {
        $flights = Flight::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('flights.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.flights.index')
            ->assertViewHas('flights');
    }

    #[Test]
    public function it_displays_create_view_for_flight(): void
    {
        $response = $this->get(route('flights.create'));

        $response->assertOk()->assertViewIs('app.flights.create');
    }

    #[Test]
    public function it_stores_the_flight(): void
    {
        $this->seed(FlightRoutesSeeder::class);
        $route = FlightRoute::query()->firstOrFail();

        $data = Flight::factory()->make()->getAttributes();
        $data['time'] = $this->faker->time('H:i');
        $data['flight_route_id'] = $route->id;

        $response = $this->post(route('flights.store'), $data);

        $this->assertDatabaseHas('flights', $data);

        $flight = Flight::latest('id')->first();

        // The chosen route is copied onto the flight as its legs.
        $this->assertSame($route->legs()->count(), $flight->legs()->count());

        $response->assertRedirect(route('flights.show', $flight));
    }

    #[Test]
    public function it_displays_show_view_for_flight(): void
    {
        $flight = Flight::factory()->create();

        $response = $this->get(route('flights.show', $flight));

        $response
            ->assertOk()
            ->assertViewIs('app.flights.show')
            ->assertViewHas('flight');
    }

    #[Test]
    public function it_displays_edit_view_for_flight(): void
    {
        $flight = Flight::factory()->create();

        $response = $this->get(route('flights.edit', $flight));

        $response
            ->assertOk()
            ->assertViewIs('app.flights.edit')
            ->assertViewHas('flight');
    }

    #[Test]
    public function it_updates_the_flight(): void
    {
        $flight = Flight::factory()->create();

        $data = [
            'type' => 'Air',
            'date' => $this->faker->date(),
            'time' => $this->faker->time('H:i'),
        ];

        $response = $this->put(route('flights.update', $flight), $data);

        $data['id'] = $flight->id;

        $this->assertDatabaseHas('flights', $data);

        $response->assertRedirect(route('flights.show', $flight));
    }

    #[Test]
    public function it_deletes_the_flight(): void
    {
        $flight = Flight::factory()->create();

        $response = $this->delete(route('flights.destroy', $flight));

        $response->assertRedirect(route('flights.index'));

        $this->assertModelMissing($flight);
    }
}
