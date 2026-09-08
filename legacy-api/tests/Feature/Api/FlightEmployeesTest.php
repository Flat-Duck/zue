<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Flight;
use App\Models\Employee;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlightEmployeesTest extends TestCase
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
    public function it_gets_flight_employees(): void
    {
        $flight = Flight::factory()->create();
        $employee = Employee::factory()->create();

        $flight->employees()->attach($employee);

        $response = $this->getJson(
            route('api.flights.employees.index', $flight)
        );

        $response->assertOk()->assertSee($employee->job);
    }

    /**
     * @test
     */
    public function it_can_attach_employees_to_flight(): void
    {
        $flight = Flight::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->postJson(
            route('api.flights.employees.store', [$flight, $employee])
        );

        $response->assertNoContent();

        $this->assertTrue(
            $flight
                ->employees()
                ->where('employees.id', $employee->id)
                ->exists()
        );
    }

    /**
     * @test
     */
    public function it_can_detach_employees_from_flight(): void
    {
        $flight = Flight::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->deleteJson(
            route('api.flights.employees.store', [$flight, $employee])
        );

        $response->assertNoContent();

        $this->assertFalse(
            $flight
                ->employees()
                ->where('employees.id', $employee->id)
                ->exists()
        );
    }
}
