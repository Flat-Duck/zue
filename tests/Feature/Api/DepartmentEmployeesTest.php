<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DepartmentEmployeesTest extends TestCase
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
    public function it_gets_department_employees(): void
    {
        $department = Department::factory()->create();
        $employees = Employee::factory()
            ->count(2)
            ->create([
                'department_id' => $department->id,
            ]);

        $response = $this->getJson(
            route('api.departments.employees.index', $department)
        );

        $response->assertOk()->assertSee($employees[0]->job);
    }

    /**
     * @test
     */
    public function it_stores_the_department_employees(): void
    {
        $department = Department::factory()->create();
        $data = Employee::factory()
            ->make([
                'department_id' => $department->id,
            ])
            ->getAttributes();

        $response = $this->postJson(
            route('api.departments.employees.store', $department),
            $data
        );

        $this->assertDatabaseHas('employees', $data);

        $response->assertStatus(201)->assertJsonFragment([
            'number' => $data['number'],
            'department_id' => $department->id,
        ]);

        $employee = Employee::latest('id')->first();

        $this->assertEquals($department->id, $employee->department_id);
    }
}
