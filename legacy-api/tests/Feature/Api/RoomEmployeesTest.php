<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Room;
use App\Models\Employee;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomEmployeesTest extends TestCase
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
    public function it_gets_room_employees(): void
    {
        $room = Room::factory()->create();
        $employee = Employee::factory()->create();

        $room->employees()->attach($employee);

        $response = $this->getJson(route('api.rooms.employees.index', $room));

        $response->assertOk()->assertSee($employee->job);
    }

    /**
     * @test
     */
    public function it_can_attach_employees_to_room(): void
    {
        $room = Room::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->postJson(
            route('api.rooms.employees.store', [$room, $employee])
        );

        $response->assertNoContent();

        $this->assertTrue(
            $room
                ->employees()
                ->where('employees.id', $employee->id)
                ->exists()
        );
    }

    /**
     * @test
     */
    public function it_can_detach_employees_from_room(): void
    {
        $room = Room::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->deleteJson(
            route('api.rooms.employees.store', [$room, $employee])
        );

        $response->assertNoContent();

        $this->assertFalse(
            $room
                ->employees()
                ->where('employees.id', $employee->id)
                ->exists()
        );
    }
}
