<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Flight;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlightControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create(['email' => 'admin@admin.com'])
        );

        $this->seed(\Database\Seeders\PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function it_displays_create_view_for_flight(): void
    {
        $response = $this->get(route('flights.create'));

        $response->assertOk()->assertViewIs('app.flights.create');
    }

    /**
     * @test
     */
    public function it_stores_the_flight(): void
    {
        $data = Flight::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('flights.store'), $data);

        $this->assertDatabaseHas('flights', $data);

        $flight = Flight::latest('id')->first();

        $response->assertRedirect(route('flights.edit', $flight));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_flight(): void
    {
        $flight = Flight::factory()->create();

        $response = $this->get(route('flights.show', $flight));

        $response
            ->assertOk()
            ->assertViewIs('app.flights.show')
            ->assertViewHas('flight');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_flight(): void
    {
        $flight = Flight::factory()->create();

        $response = $this->get(route('flights.edit', $flight));

        $response
            ->assertOk()
            ->assertViewIs('app.flights.edit')
            ->assertViewHas('flight');
    }

    /**
     * @test
     */
    public function it_updates_the_flight(): void
    {
        $flight = Flight::factory()->create();

        $data = [
            'type' => 'Air',
            'date' => $this->faker->date(),
            'time' => $this->faker->time(),
        ];

        $response = $this->put(route('flights.update', $flight), $data);

        $data['id'] = $flight->id;

        $this->assertDatabaseHas('flights', $data);

        $response->assertRedirect(route('flights.edit', $flight));
    }

    /**
     * @test
     */
    public function it_deletes_the_flight(): void
    {
        $flight = Flight::factory()->create();

        $response = $this->delete(route('flights.destroy', $flight));

        $response->assertRedirect(route('flights.index'));

        $this->assertModelMissing($flight);
    }
}
