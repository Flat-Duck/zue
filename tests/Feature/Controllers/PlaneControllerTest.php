<?php

namespace Tests\Feature\Controllers;

use App\Models\Plane;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlaneControllerTest extends TestCase
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
    public function it_displays_index_view_with_planes(): void
    {
        Plane::factory()->count(3)->create();

        $this->get(route('planes.index'))
            ->assertOk()
            ->assertViewIs('app.planes.index')
            ->assertViewHas('planes');
    }

    #[Test]
    public function it_displays_create_view_for_plane(): void
    {
        $this->get(route('planes.create'))
            ->assertOk()
            ->assertViewIs('app.planes.create');
    }

    #[Test]
    public function it_stores_the_plane(): void
    {
        $data = Plane::factory()->make()->toArray();

        $response = $this->post(route('planes.store'), $data);

        $this->assertDatabaseHas('planes', $data);

        $response->assertRedirect(route('planes.edit', Plane::latest('id')->first()));
    }

    #[Test]
    public function it_displays_show_view_for_plane(): void
    {
        $plane = Plane::factory()->create();

        $this->get(route('planes.show', $plane))
            ->assertOk()
            ->assertViewIs('app.planes.show')
            ->assertViewHas('plane');
    }

    #[Test]
    public function it_displays_edit_view_for_plane(): void
    {
        $plane = Plane::factory()->create();

        $this->get(route('planes.edit', $plane))
            ->assertOk()
            ->assertViewIs('app.planes.edit')
            ->assertViewHas('plane');
    }

    #[Test]
    public function it_updates_the_plane(): void
    {
        $plane = Plane::factory()->create();

        $data = ['name' => 'Renamed', 'capacity' => 30, 'lines' => 'A-B'];

        $this->put(route('planes.update', $plane), $data)
            ->assertRedirect(route('planes.edit', $plane));

        $this->assertDatabaseHas('planes', $data + ['id' => $plane->id]);
    }

    #[Test]
    public function it_deletes_the_plane(): void
    {
        $plane = Plane::factory()->create();

        $this->delete(route('planes.destroy', $plane))
            ->assertRedirect(route('planes.index'));

        $this->assertDatabaseMissing('planes', ['id' => $plane->id]);
    }
}
