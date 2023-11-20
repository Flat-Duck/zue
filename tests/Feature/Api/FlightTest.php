<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Flight;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlightTest extends TestCase
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
    public function it_gets_flights_list(): void
    {
        $flights = Flight::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.flights.index'));

        $response->assertOk()->assertSee($flights[0]->date);
    }

    /**
     * @test
     */
    public function it_stores_the_flight(): void
    {
        $data = Flight::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.flights.store'), $data);

        $this->assertDatabaseHas('flights', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
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

        $response = $this->putJson(route('api.flights.update', $flight), $data);

        $data['id'] = $flight->id;

        $this->assertDatabaseHas('flights', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_flight(): void
    {
        $flight = Flight::factory()->create();

        $response = $this->deleteJson(route('api.flights.destroy', $flight));

        $this->assertModelMissing($flight);

        $response->assertNoContent();
    }
}
