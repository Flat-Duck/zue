<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Administration;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdministrationTest extends TestCase
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
    public function it_gets_administrations_list(): void
    {
        $administrations = Administration::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.administrations.index'));

        $response->assertOk()->assertSee($administrations[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_administration(): void
    {
        $data = Administration::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.administrations.store'), $data);

        $this->assertDatabaseHas('administrations', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_administration(): void
    {
        $administration = Administration::factory()->create();

        $data = [
            'name' => $this->faker->name(),
        ];

        $response = $this->putJson(
            route('api.administrations.update', $administration),
            $data
        );

        $data['id'] = $administration->id;

        $this->assertDatabaseHas('administrations', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_administration(): void
    {
        $administration = Administration::factory()->create();

        $response = $this->deleteJson(
            route('api.administrations.destroy', $administration)
        );

        $this->assertModelMissing($administration);

        $response->assertNoContent();
    }
}
