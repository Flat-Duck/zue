<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Center;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CenterTest extends TestCase
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
    public function it_gets_centers_list(): void
    {
        $centers = Center::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.centers.index'));

        $response->assertOk()->assertSee($centers[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_center(): void
    {
        $data = Center::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.centers.store'), $data);

        $this->assertDatabaseHas('centers', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_center(): void
    {
        $center = Center::factory()->create();

        $data = [
            'name' => $this->faker->name(),
        ];

        $response = $this->putJson(route('api.centers.update', $center), $data);

        $data['id'] = $center->id;

        $this->assertDatabaseHas('centers', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_center(): void
    {
        $center = Center::factory()->create();

        $response = $this->deleteJson(route('api.centers.destroy', $center));

        $this->assertModelMissing($center);

        $response->assertNoContent();
    }
}
