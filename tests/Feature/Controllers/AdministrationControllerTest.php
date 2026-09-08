<?php

namespace Tests\Feature\Controllers;

use App\Models\Administration;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministrationControllerTest extends TestCase
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
    public function it_displays_index_view_with_administrations(): void
    {
        $administrations = Administration::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('administrations.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.administrations.index')
            ->assertViewHas('administrations');
    }

    #[Test]
    public function it_displays_create_view_for_administration(): void
    {
        $response = $this->get(route('administrations.create'));

        $response->assertOk()->assertViewIs('app.administrations.create');
    }

    #[Test]
    public function it_stores_the_administration(): void
    {
        $data = Administration::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('administrations.store'), $data);

        $this->assertDatabaseHas('administrations', $data);

        $administration = Administration::latest('id')->first();

        $response->assertRedirect(
            route('administrations.edit', $administration)
        );
    }

    #[Test]
    public function it_displays_show_view_for_administration(): void
    {
        $administration = Administration::factory()->create();

        $response = $this->get(route('administrations.show', $administration));

        $response
            ->assertOk()
            ->assertViewIs('app.administrations.show')
            ->assertViewHas('administration');
    }

    #[Test]
    public function it_displays_edit_view_for_administration(): void
    {
        $administration = Administration::factory()->create();

        $response = $this->get(route('administrations.edit', $administration));

        $response
            ->assertOk()
            ->assertViewIs('app.administrations.edit')
            ->assertViewHas('administration');
    }

    #[Test]
    public function it_updates_the_administration(): void
    {
        $administration = Administration::factory()->create();

        $data = [
            'name' => $this->faker->name(),
        ];

        $response = $this->put(
            route('administrations.update', $administration),
            $data
        );

        $data['id'] = $administration->id;

        $this->assertDatabaseHas('administrations', $data);

        $response->assertRedirect(
            route('administrations.edit', $administration)
        );
    }

    #[Test]
    public function it_deletes_the_administration(): void
    {
        $administration = Administration::factory()->create();

        $response = $this->delete(
            route('administrations.destroy', $administration)
        );

        $response->assertRedirect(route('administrations.index'));

        $this->assertModelMissing($administration);
    }
}
