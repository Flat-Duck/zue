<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Room;

use App\Models\Residence;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomControllerTest extends TestCase
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
    public function it_displays_index_view_with_rooms(): void
    {
        $rooms = Room::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('rooms.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.rooms.index')
            ->assertViewHas('rooms');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_room(): void
    {
        $response = $this->get(route('rooms.create'));

        $response->assertOk()->assertViewIs('app.rooms.create');
    }

    /**
     * @test
     */
    public function it_stores_the_room(): void
    {
        $data = Room::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('rooms.store'), $data);

        $this->assertDatabaseHas('rooms', $data);

        $room = Room::latest('id')->first();

        $response->assertRedirect(route('rooms.edit', $room));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_room(): void
    {
        $room = Room::factory()->create();

        $response = $this->get(route('rooms.show', $room));

        $response
            ->assertOk()
            ->assertViewIs('app.rooms.show')
            ->assertViewHas('room');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_room(): void
    {
        $room = Room::factory()->create();

        $response = $this->get(route('rooms.edit', $room));

        $response
            ->assertOk()
            ->assertViewIs('app.rooms.edit')
            ->assertViewHas('room');
    }

    /**
     * @test
     */
    public function it_updates_the_room(): void
    {
        $room = Room::factory()->create();

        $residence = Residence::factory()->create();

        $data = [
            'number' => $this->faker->name(),
            'beds' => $this->faker->randomNumber(0),
            'residence_id' => $residence->id,
        ];

        $response = $this->put(route('rooms.update', $room), $data);

        $data['id'] = $room->id;

        $this->assertDatabaseHas('rooms', $data);

        $response->assertRedirect(route('rooms.edit', $room));
    }

    /**
     * @test
     */
    public function it_deletes_the_room(): void
    {
        $room = Room::factory()->create();

        $response = $this->delete(route('rooms.destroy', $room));

        $response->assertRedirect(route('rooms.index'));

        $this->assertModelMissing($room);
    }
}
