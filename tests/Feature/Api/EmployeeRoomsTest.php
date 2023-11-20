<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Room;
use App\Models\Employee;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeRoomsTest extends TestCase
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
    public function it_gets_employee_rooms(): void
    {
        $employee = Employee::factory()->create();
        $room = Room::factory()->create();

        $employee->rooms()->attach($room);

        $response = $this->getJson(
            route('api.employees.rooms.index', $employee)
        );

        $response->assertOk()->assertSee($room->number);
    }

    /**
     * @test
     */
    public function it_can_attach_rooms_to_employee(): void
    {
        $employee = Employee::factory()->create();
        $room = Room::factory()->create();

        $response = $this->postJson(
            route('api.employees.rooms.store', [$employee, $room])
        );

        $response->assertNoContent();

        $this->assertTrue(
            $employee
                ->rooms()
                ->where('rooms.id', $room->id)
                ->exists()
        );
    }

    /**
     * @test
     */
    public function it_can_detach_rooms_from_employee(): void
    {
        $employee = Employee::factory()->create();
        $room = Room::factory()->create();

        $response = $this->deleteJson(
            route('api.employees.rooms.store', [$employee, $room])
        );

        $response->assertNoContent();

        $this->assertFalse(
            $employee
                ->rooms()
                ->where('rooms.id', $room->id)
                ->exists()
        );
    }
}
