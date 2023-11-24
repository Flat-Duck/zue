<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Employee;

use App\Models\Center;
use App\Models\Location;
use App\Models\Department;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeTest extends TestCase
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
    public function it_gets_employees_list(): void
    {
        $employees = Employee::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.employees.index'));

        $response->assertOk()->assertSee($employees[0]->job);
    }

    /**
     * @test
     */
    public function it_stores_the_employee(): void
    {
        $data = Employee::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.employees.store'), $data);

        $this->assertDatabaseHas('employees', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_employee(): void
    {
        $employee = Employee::factory()->create();

        $user = User::factory()->create();
        $department = Department::factory()->create();
        $location = Location::factory()->create();
        $center = Center::factory()->create();

        $data = [
            'number' => $this->faker->randomNumber(),
            'job' => $this->faker->text(255),
            'english_name' => $this->faker->text(255),
            'id_card' => $this->faker->text(255),
            'id_card_issue_date' => $this->faker->date(),
            'passport' => $this->faker->text(255),
            'passport_issue_date' => $this->faker->date(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'transfered_balance' => $this->faker->randomNumber(0),
            'schedule' => $this->faker->text(255),
            'start_date' => $this->faker->date(),
            'last_date' => $this->faker->date(),
            'total_balance' => $this->faker->randomNumber(0),
            'archived_at' => $this->faker->dateTime(),
            'user_id' => $user->id,
            'department_id' => $department->id,
            'location_id' => $location->id,
            'center_id' => $center->id,
        ];

        $response = $this->putJson(
            route('api.employees.update', $employee),
            $data
        );

        $data['id'] = $employee->id;

        $this->assertDatabaseHas('employees', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->deleteJson(
            route('api.employees.destroy', $employee)
        );

        $this->assertSoftDeleted($employee);

        $response->assertNoContent();
    }
}
