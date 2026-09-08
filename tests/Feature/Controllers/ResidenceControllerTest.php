<?php

namespace Tests\Feature\Controllers;

use App\Models\Residence;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResidenceControllerTest extends TestCase
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
    public function it_displays_index_view_with_residences(): void
    {
        $residences = Residence::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('residences.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.residences.index')
            ->assertViewHas('residences');
    }

    #[Test]
    public function it_displays_create_view_for_residence(): void
    {
        $response = $this->get(route('residences.create'));

        $response->assertOk()->assertViewIs('app.residences.create');
    }

    #[Test]
    public function it_stores_the_residence(): void
    {
        $data = Residence::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('residences.store'), $data);

        $this->assertDatabaseHas('residences', $data);

        $residence = Residence::latest('id')->first();

        $response->assertRedirect(route('residences.edit', $residence));
    }

    #[Test]
    public function it_displays_show_view_for_residence(): void
    {
        $residence = Residence::factory()->create();

        $response = $this->get(route('residences.show', $residence));

        $response
            ->assertOk()
            ->assertViewIs('app.residences.show')
            ->assertViewHas('residence');
    }

    #[Test]
    public function it_displays_edit_view_for_residence(): void
    {
        $residence = Residence::factory()->create();

        $response = $this->get(route('residences.edit', $residence));

        $response
            ->assertOk()
            ->assertViewIs('app.residences.edit')
            ->assertViewHas('residence');
    }

    #[Test]
    public function it_updates_the_residence(): void
    {
        $residence = Residence::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'type' => 'Villa',
        ];

        $response = $this->put(route('residences.update', $residence), $data);

        $data['id'] = $residence->id;

        $this->assertDatabaseHas('residences', $data);

        $response->assertRedirect(route('residences.edit', $residence));
    }

    #[Test]
    public function it_deletes_the_residence(): void
    {
        $residence = Residence::factory()->create();

        $response = $this->delete(route('residences.destroy', $residence));

        $response->assertRedirect(route('residences.index'));

        $this->assertModelMissing($residence);
    }
}
