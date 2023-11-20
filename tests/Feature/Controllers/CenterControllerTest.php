<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Center;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CenterControllerTest extends TestCase
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
    public function it_displays_index_view_with_centers(): void
    {
        $centers = Center::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('centers.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.centers.index')
            ->assertViewHas('centers');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_center(): void
    {
        $response = $this->get(route('centers.create'));

        $response->assertOk()->assertViewIs('app.centers.create');
    }

    /**
     * @test
     */
    public function it_stores_the_center(): void
    {
        $data = Center::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('centers.store'), $data);

        $this->assertDatabaseHas('centers', $data);

        $center = Center::latest('id')->first();

        $response->assertRedirect(route('centers.edit', $center));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_center(): void
    {
        $center = Center::factory()->create();

        $response = $this->get(route('centers.show', $center));

        $response
            ->assertOk()
            ->assertViewIs('app.centers.show')
            ->assertViewHas('center');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_center(): void
    {
        $center = Center::factory()->create();

        $response = $this->get(route('centers.edit', $center));

        $response
            ->assertOk()
            ->assertViewIs('app.centers.edit')
            ->assertViewHas('center');
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

        $response = $this->put(route('centers.update', $center), $data);

        $data['id'] = $center->id;

        $this->assertDatabaseHas('centers', $data);

        $response->assertRedirect(route('centers.edit', $center));
    }

    /**
     * @test
     */
    public function it_deletes_the_center(): void
    {
        $center = Center::factory()->create();

        $response = $this->delete(route('centers.destroy', $center));

        $response->assertRedirect(route('centers.index'));

        $this->assertModelMissing($center);
    }
}
