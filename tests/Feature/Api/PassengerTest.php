<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Passenger;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PassengerTest extends TestCase
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
    public function it_gets_passengers_list(): void
    {
        $passengers = Passenger::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.passengers.index'));

        $response->assertOk()->assertSee($passengers[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_passenger(): void
    {
        $data = Passenger::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.passengers.store'), $data);

        $this->assertDatabaseHas('passengers', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_passenger(): void
    {
        $passenger = Passenger::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'company' => $this->faker->text(255),
            'number' => $this->faker->text(255),
            'nationality' => $this->faker->text(255),
        ];

        $response = $this->putJson(
            route('api.passengers.update', $passenger),
            $data
        );

        $data['id'] = $passenger->id;

        $this->assertDatabaseHas('passengers', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_passenger(): void
    {
        $passenger = Passenger::factory()->create();

        $response = $this->deleteJson(
            route('api.passengers.destroy', $passenger)
        );

        $this->assertModelMissing($passenger);

        $response->assertNoContent();
    }
}
