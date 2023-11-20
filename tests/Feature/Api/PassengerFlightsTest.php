<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Flight;
use App\Models\Passenger;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PassengerFlightsTest extends TestCase
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
    public function it_gets_passenger_flights(): void
    {
        $passenger = Passenger::factory()->create();
        $flight = Flight::factory()->create();

        $passenger->flights()->attach($flight);

        $response = $this->getJson(
            route('api.passengers.flights.index', $passenger)
        );

        $response->assertOk()->assertSee($flight->date);
    }

    /**
     * @test
     */
    public function it_can_attach_flights_to_passenger(): void
    {
        $passenger = Passenger::factory()->create();
        $flight = Flight::factory()->create();

        $response = $this->postJson(
            route('api.passengers.flights.store', [$passenger, $flight])
        );

        $response->assertNoContent();

        $this->assertTrue(
            $passenger
                ->flights()
                ->where('flights.id', $flight->id)
                ->exists()
        );
    }

    /**
     * @test
     */
    public function it_can_detach_flights_from_passenger(): void
    {
        $passenger = Passenger::factory()->create();
        $flight = Flight::factory()->create();

        $response = $this->deleteJson(
            route('api.passengers.flights.store', [$passenger, $flight])
        );

        $response->assertNoContent();

        $this->assertFalse(
            $passenger
                ->flights()
                ->where('flights.id', $flight->id)
                ->exists()
        );
    }
}
