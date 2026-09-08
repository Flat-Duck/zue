<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Residence;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResidenceTest extends TestCase
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
    public function it_gets_residences_list(): void
    {
        $residences = Residence::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.residences.index'));

        $response->assertOk()->assertSee($residences[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_residence(): void
    {
        $data = Residence::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.residences.store'), $data);

        $this->assertDatabaseHas('residences', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_residence(): void
    {
        $residence = Residence::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'type' => 'Villa',
        ];

        $response = $this->putJson(
            route('api.residences.update', $residence),
            $data
        );

        $data['id'] = $residence->id;

        $this->assertDatabaseHas('residences', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_residence(): void
    {
        $residence = Residence::factory()->create();

        $response = $this->deleteJson(
            route('api.residences.destroy', $residence)
        );

        $this->assertModelMissing($residence);

        $response->assertNoContent();
    }
}
