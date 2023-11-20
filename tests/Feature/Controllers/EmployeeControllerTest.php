<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Employee;

use App\Models\Center;
use App\Models\Location;
use App\Models\Department;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeControllerTest extends TestCase
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

    /**
     * @test
     */
    public function it_displays_create_view_for_employee(): void
    {
        $response = $this->get(route('employees.create'));

        $response->assertOk()->assertViewIs('app.employees.create');
    }

    /**
     * @test
     */
    public function it_stores_the_employee(): void
    {
        $data = Employee::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('employees.store'), $data);

        $this->assertDatabaseHas('employees', $data);

        $employee = Employee::latest('id')->first();

        $response->assertRedirect(route('employees.edit', $employee));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->get(route('employees.show', $employee));

        $response
            ->assertOk()
            ->assertViewIs('app.employees.show')
            ->assertViewHas('employee');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->get(route('employees.edit', $employee));

        $response
            ->assertOk()
            ->assertViewIs('app.employees.edit')
            ->assertViewHas('employee');
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
            'user_id' => $user->id,
            'department_id' => $department->id,
            'location_id' => $location->id,
            'center_id' => $center->id,
        ];

        $response = $this->put(route('employees.update', $employee), $data);

        $data['id'] = $employee->id;

        $this->assertDatabaseHas('employees', $data);

        $response->assertRedirect(route('employees.edit', $employee));
    }

    /**
     * @test
     */
    public function it_deletes_the_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));

        $this->assertModelMissing($employee);
    }
}
