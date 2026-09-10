<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\ManagementScope;
use App\Models\TimeSheet;
use App\Models\User;
use App\Services\TimeSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CHARACTERIZATION TESTS for TimeSheetService::getApprovalData().
 *
 * That method is 225 lines feeding the timesheet approval screen, its print
 * preview and the monthly attendance report, and it had no test coverage at
 * all. These tests pin its current behaviour so it can be refactored safely;
 * they describe what it does today, not what it ought to do.
 */
class TimeSheetApprovalDataTest extends TestCase
{
    use RefreshDatabase;

    private const MONTH = 3;

    private const YEAR = 2026;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'timesheet_auth.v2_read_enabled' => false,
            'timesheet_auth.v2_write_enabled' => false,
        ]);
    }

    private ?Employee $managerEmployee = null;

    /**
     * A manager whose scope covers every non-archived employee.
     *
     * The factory creates the manager's employee, since every user is one.
     */
    private function actAsManager(): User
    {
        $user = User::factory()->create();
        $managerEmployee = $user->employee;

        $this->managerEmployee = $managerEmployee;

        $scope = ManagementScope::create([
            'manager_id' => $managerEmployee->id,
            'name' => 'Company wide',
            'scope_type' => ManagementScope::TYPE_GLOBAL,
            'context' => 'time_sheet',
        ]);
        $managerEmployee->managementScopes()->attach($scope->id);

        $this->actingAs($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $sheetAttributes
     */
    private function employeeWithMonth(string $departmentName, int $days, string $value = 'A', array $sheetAttributes = []): Employee
    {
        $employee = Employee::factory()->create([
            'department_id' => Department::factory()->create(['name' => $departmentName])->id,
        ]);

        for ($day = 1; $day <= $days; $day++) {
            TimeSheet::factory()->create(array_merge([
                'employee_id' => $employee->id,
                'day' => sprintf('%d-%02d-%02d', self::YEAR, self::MONTH, $day),
                'value' => $value,
                'over_time' => 0,
            ], $sheetAttributes));
        }

        return $employee;
    }

    private function data(): array
    {
        return app(TimeSheetService::class)->getApprovalData(self::MONTH, self::YEAR);
    }

    #[Test]
    public function it_returns_the_expected_payload_keys(): void
    {
        $this->actAsManager();
        $this->employeeWithMonth('PROD', 3);

        $data = $this->data();

        foreach ([
            'chunks', 'department', 'center', 'administration', 'month_name',
            'selected_month', 'selected_year', 'month_days', 'employees', 'signatures',
            'canTimekeeperApprove', 'canSupervisorApprove', 'canFieldCoordinatorApprove',
            'canCoordinatorApprove', 'canSuperintendentApprove',
            'requiresSupervisorStage', 'requiresFieldCoordinatorStage', 'requiresSuperintendentStage',
        ] as $key) {
            $this->assertArrayHasKey($key, $data, "[{$key}] is missing from the payload.");
        }

        $this->assertSame(self::MONTH, $data['selected_month']);
        $this->assertSame(self::YEAR, $data['selected_year']);
        $this->assertSame(31, $data['month_days']);
    }

    /**
     * Ordinary employees are paged eight to a sheet.
     */
    #[Test]
    public function normal_employees_are_paginated_eight_per_page(): void
    {
        $this->actAsManager();

        for ($i = 0; $i < 9; $i++) {
            $this->employeeWithMonth('PROD', 3);
        }

        $chunks = $this->data()['chunks'];

        $this->assertCount(2, $chunks, 'Nine employees should fill one page of eight plus a second page.');
        $this->assertCount(8, $chunks[0]);
        $this->assertCount(1, $chunks[1]);
    }

    /**
     * An employee with a full month gets a sheet of their own.
     */
    #[Test]
    public function a_heavily_worked_employee_gets_its_own_page(): void
    {
        $this->actAsManager();

        $this->employeeWithMonth('PROD', 3);
        $this->employeeWithMonth('PROD', 20);

        $chunks = $this->data()['chunks'];

        $this->assertCount(2, $chunks);
        $this->assertCount(1, $chunks[0], 'The ordinary employee is alone on the first page.');
        $this->assertCount(1, $chunks[1], 'The heavily worked employee is on a page of their own.');
    }

    #[Test]
    public function an_employee_with_four_hours_overtime_is_treated_as_special(): void
    {
        $this->actAsManager();

        $this->employeeWithMonth('PROD', 3);
        $this->employeeWithMonth('PROD', 2, 'A', ['over_time' => 4]);

        $this->assertCount(2, $this->data()['chunks']);
    }

    /**
     * Department name decides which approval stages a timesheet must pass.
     */
    #[Test]
    public function department_decides_which_approval_stages_are_required(): void
    {
        $this->actAsManager();
        $this->employeeWithMonth('PROD', 3);

        $data = $this->data();

        $this->assertTrue($data['requiresSupervisorStage']);
        $this->assertTrue($data['requiresFieldCoordinatorStage']);
        $this->assertFalse($data['requiresSuperintendentStage']);
    }

    #[Test]
    public function an_unknown_department_falls_back_to_the_legacy_chain(): void
    {
        $this->actAsManager();
        $this->employeeWithMonth('SOMETHING UNKNOWN', 3);

        $data = $this->data();

        $this->assertTrue($data['requiresSupervisorStage']);
        $this->assertFalse($data['requiresFieldCoordinatorStage']);
        $this->assertTrue($data['requiresSuperintendentStage']);
    }

    #[Test]
    public function the_timekeeper_may_approve_while_any_sheet_is_unsigned(): void
    {
        $this->actAsManager();
        $this->employeeWithMonth('PROD', 3);

        $this->assertTrue($this->data()['canTimekeeperApprove']);
    }

    #[Test]
    public function the_timekeeper_may_not_approve_once_every_sheet_is_signed(): void
    {
        $manager = $this->actAsManager();
        $this->employeeWithMonth('PROD', 3, 'A', ['timekeeper_id' => $manager->employee_id]);

        $this->assertFalse($this->data()['canTimekeeperApprove']);
    }

    /**
     * A supervisor can only act after the timekeeper has.
     */
    #[Test]
    public function the_supervisor_may_approve_only_after_the_timekeeper(): void
    {
        $manager = $this->actAsManager();
        $this->employeeWithMonth('PROD', 3);

        $this->assertFalse($this->data()['canSupervisorApprove'], 'Nothing is timekeeper-signed yet.');

        TimeSheet::query()->update(['timekeeper_id' => $manager->employee_id]);

        $this->assertTrue($this->data()['canSupervisorApprove']);
    }

    #[Test]
    public function signatures_start_empty_and_fill_in_as_stages_are_signed(): void
    {
        $manager = $this->actAsManager();
        $this->employeeWithMonth('PROD', 3);

        $signatures = $this->data()['signatures'];

        foreach (['time_keeper', 'super_visor', 'field_coordinator', 'coordinator', 'super_intendent'] as $stage) {
            $this->assertArrayHasKey($stage, $signatures);
            $this->assertNull($signatures[$stage]['name'], "[{$stage}] should start unsigned.");
        }

        TimeSheet::query()->update(['timekeeper_id' => $manager->employee_id]);

        $this->assertSame($manager->employee->name, $this->data()['signatures']['time_keeper']['name']);
    }

    #[Test]
    public function the_coordinator_signature_mirrors_the_field_coordinator(): void
    {
        $this->actAsManager();
        $this->employeeWithMonth('PROD', 3);

        $signatures = $this->data()['signatures'];

        $this->assertSame($signatures['field_coordinator'], $signatures['coordinator']);
    }

    #[Test]
    public function the_header_reports_the_first_employees_department_and_centre(): void
    {
        $this->actAsManager();
        $employee = $this->employeeWithMonth('PROD', 3);

        $data = $this->data();

        $this->assertSame($employee->department->name, $data['department']);
        $this->assertSame($employee->center->name, $data['center']);
    }

    #[Test]
    public function a_manager_with_no_employees_gets_an_empty_but_valid_payload(): void
    {
        $this->actAsManager();

        $data = $this->data();

        $this->assertCount(0, $data['chunks']);
        $this->assertSame('', $data['department']);
        $this->assertFalse($data['canTimekeeperApprove']);
    }
}
