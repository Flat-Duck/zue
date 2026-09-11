<?php

namespace Tests\Feature\Controllers;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\BuildsScopes;
use Tests\TestCase;

class TimeSheetControllerTest extends TestCase
{
    use BuildsScopes;
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'timesheet_auth.v2_read_enabled' => false,
            'timesheet_auth.v2_write_enabled' => false,
        ]);

        $this->seed(PermissionsSeeder::class);

        $this->user = $this->managerUser();

        $this->actingAs($this->user);

        $this->withoutExceptionHandling();
    }

    /**
     * A user linked to an employee that holds a company-wide time_sheet
     * management scope, so every non-archived employee is manageable.
     */
    private function managerUser(): User
    {
        $user = User::factory()->create();

        $managerEmployee = $user->employee;

        $this->buildGlobalScope([$managerEmployee]);

        $user->givePermissionTo([
            'list timesheets',
            'view timesheets',
            'create timesheets',
            'update timesheets',
            'delete timesheets',
            'fill timesheets',
            'revise timesheets',
            'list employees',
            'view employees',
        ]);

        return $user;
    }

    #[Test]
    public function it_displays_index_view_with_managed_employees(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->get(route('time-sheets.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.time_sheets.index')
            ->assertViewHas('employees');
    }

    #[Test]
    public function it_displays_fill_view_for_an_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->get(route('time-sheets.fill', $employee));

        $response
            ->assertOk()
            ->assertViewIs('app.time_sheets.create')
            ->assertViewHas('employee');
    }

    #[Test]
    public function it_displays_revise_view_for_an_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->get(route('time-sheets.revise', $employee));

        $response
            ->assertOk()
            ->assertViewIs('app.time_sheets.edit')
            ->assertViewHas('employee');
    }

    #[Test]
    public function it_displays_show_view_for_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $response = $this->get(route('time-sheets.show', $timeSheet));

        $response
            ->assertOk()
            ->assertViewIs('app.time_sheets.show')
            ->assertViewHas('timeSheet');
    }

    /**
     * The show page links to the employee-scoped fill route. It previously
     * referenced a deleted route and returned 500 for privileged users only.
     */
    #[Test]
    public function it_links_show_view_to_the_employee_scoped_fill_route(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $this->get(route('time-sheets.show', $timeSheet))
            ->assertOk()
            ->assertSee(route('time-sheets.fill', $timeSheet->employee_id), false);
    }

    #[Test]
    public function it_stores_the_time_sheet(): void
    {
        $employee = Employee::factory()->create();

        $data = [
            'employee_id' => $employee->id,
            'day' => now()->startOfMonth()->format('Y-m-d'),
            'value' => 'F',
        ];

        $response = $this->post(route('time-sheets.store'), $data);

        $this->assertDatabaseHas('time_sheets', [
            'employee_id' => $employee->id,
            'day' => $data['day'],
            'value' => 'F',
        ]);

        $response->assertRedirect(route('time-sheets.revise', ['employee' => $employee->id]));
    }

    #[Test]
    public function it_updates_the_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create(['value' => 'F']);

        $data = [
            'day' => $timeSheet->day,
            'value' => 'M',
        ];

        $response = $this->put(route('time-sheets.update', $timeSheet), $data);

        $this->assertDatabaseHas('time_sheets', [
            'id' => $timeSheet->id,
            'value' => 'M',
        ]);

        $response->assertRedirect(
            route('time-sheets.revise', ['employee' => $timeSheet->employee_id])
        );
    }

    #[Test]
    public function it_deletes_the_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $response = $this->delete(route('time-sheets.destroy', $timeSheet));

        $response->assertRedirect(route('time-sheets.index'));

        $this->assertModelMissing($timeSheet);
    }
}
