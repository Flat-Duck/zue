<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeFlightsTest extends TestCase
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
    public function it_gets_employee_flights(): void
    {
        $employee = Employee::factory()->create();
        $flight = Flight::factory()->create();

        $employee->flights()->attach($flight);

        $response = $this->getJson(
            route('api.employees.flights.index', $employee)
        );

        $response->assertOk()->assertSee($flight->date->toISOString());
    }

    /**
     * @test
     */
    public function it_can_attach_flights_to_employee(): void
    {
        $employee = Employee::factory()->create();
        $flight = Flight::factory()->create();

        $response = $this->postJson(
            route('api.employees.flights.store', [$employee, $flight])
        );

        $response->assertNoContent();

        $this->assertTrue(
            $employee
                ->flights()
                ->where('flights.id', $flight->id)
                ->exists()
        );
    }

    /**
     * @test
     */
    public function it_can_detach_flights_from_employee(): void
    {
        $employee = Employee::factory()->create();
        $flight = Flight::factory()->create();

        $response = $this->deleteJson(
            route('api.employees.flights.store', [$employee, $flight])
        );

        $response->assertNoContent();

        $this->assertFalse(
            $employee
                ->flights()
                ->where('flights.id', $flight->id)
                ->exists()
        );
    }
}
