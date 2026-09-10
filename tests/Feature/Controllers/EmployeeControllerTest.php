<?php

namespace Tests\Feature\Controllers;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
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
    public function it_displays_index_view_with_employees(): void
    {
        $employees = Employee::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('employees.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.employees.index')
            ->assertViewHas('employees');
    }

    #[Test]
    public function it_displays_create_view_for_employee(): void
    {
        $response = $this->get(route('employees.create'));

        $response->assertOk()->assertViewIs('app.employees.create');
    }

    #[Test]
    public function it_stores_the_employee(): void
    {
        $data = Employee::factory()
            ->make()
            ->getAttributes();

        $response = $this->post(route('employees.store'), $data);

        $this->assertDatabaseHas('employees', $data);

        $employee = Employee::latest('id')->first();

        $response->assertRedirect(route('employees.edit', $employee));
    }

    #[Test]
    public function it_displays_show_view_for_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->get(route('employees.show', $employee));

        $response
            ->assertOk()
            ->assertViewIs('app.employees.show')
            ->assertViewHas('employee');
    }

    #[Test]
    public function it_displays_edit_view_for_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->get(route('employees.edit', $employee));

        $response
            ->assertOk()
            ->assertViewIs('app.employees.edit')
            ->assertViewHas('employee');
    }

    #[Test]
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
            'archived_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'department_id' => $department->id,
            'location_id' => $location->id,
            'center_id' => $center->id,
        ];

        $response = $this->put(route('employees.update', $employee), $data);

        // The form posts one flat set of fields; they land in the two tables that
        // between them hold an employee.
        $profileFields = array_intersect_key($data, array_flip(Employee::detailFields()));

        $this->assertDatabaseHas('employees', array_diff_key($data, $profileFields) + ['id' => $employee->id]);
        $this->assertDatabaseHas('employee_details', $profileFields + ['employee_id' => $employee->id]);

        $response->assertRedirect(route('employees.edit', $employee));
    }

    #[Test]
    public function it_deletes_the_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted($employee);
    }
}
