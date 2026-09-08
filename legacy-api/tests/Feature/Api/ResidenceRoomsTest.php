<?php

namespace Tests\Feature\Api;

use App\Models\Residence;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidenceRoomsTest extends TestCase
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
    public function it_gets_residence_rooms(): void
    {
        $residence = Residence::factory()->create();
        $rooms = Room::factory()
            ->count(2)
            ->create([
                'residence_id' => $residence->id,
            ]);

        $response = $this->getJson(
            route('api.residences.rooms.index', $residence)
        );

        $response->assertOk()->assertSee($rooms[0]->number);
    }

    /**
     * @test
     */
    public function it_stores_the_residence_rooms(): void
    {
        $residence = Residence::factory()->create();
        $data = Room::factory()
            ->make([
                'residence_id' => $residence->id,
            ])
            ->getAttributes();

        $response = $this->postJson(
            route('api.residences.rooms.store', $residence),
            $data
        );

        $this->assertDatabaseHas('rooms', $data);

        $response->assertStatus(201)->assertJsonFragment([
            'number' => $data['number'],
            'residence_id' => $residence->id,
        ]);

        $room = Room::latest('id')->first();

        $this->assertEquals($residence->id, $room->residence_id);
    }
}
