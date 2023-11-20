<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Flight;
use App\Models\Passenger;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlightPassengersTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['email' => 'admin@admin.com']);

        Sanctum::actingAs($user, [], 'web');

        $this->seed(\Database\Seeders\PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_gets_flight_passengers(): void
    {
        $flight = Flight::factory()->create();
        $passenger = Passenger::factory()->create();

        $flight->passengers()->attach($passenger);

        $response = $this->getJson(
            route('api.flights.passengers.index', $flight)
        );

        $response->assertOk()->assertSee($passenger->name);
    }

    /**
     * @test
     */
    public function it_can_attach_passengers_to_flight(): void
    {
        $flight = Flight::factory()->create();
        $passenger = Passenger::factory()->create();

        $response = $this->postJson(
            route('api.flights.passengers.store', [$flight, $passenger])
        );

        $response->assertNoContent();

        $this->assertTrue(
            $flight
                ->passengers()
                ->where('passengers.id', $passenger->id)
                ->exists()
        );
    }

    /**
     * @test
     */
    public function it_can_detach_passengers_from_flight(): void
    {
        $flight = Flight::factory()->create();
        $passenger = Passenger::factory()->create();

        $response = $this->deleteJson(
            route('api.flights.passengers.store', [$flight, $passenger])
        );

        $response->assertNoContent();

        $this->assertFalse(
            $flight
                ->passengers()
                ->where('passengers.id', $passenger->id)
                ->exists()
        );
    }
}
