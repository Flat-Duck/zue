<?php

namespace Tests\Feature;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Models\Signature;
use App\Models\TimeSheet;
use App\Models\TimeSheetApprovalStep;
use App\Models\User;
use App\Services\TimeSheetAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pins the behaviour of the approval workflow before it is broken up.
 *
 * The three methods this covers — stage listing, approval, and assembling the
 * printable sheet — carried 380 of the service's 614 lines between them and one
 * happy-path test. These assertions describe what the code does today, so that a
 * refactor that changes it fails loudly.
 */
class TimeSheetApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private TimeSheetAuthorizationService $service;

    private Location $location;

    private Department $department;

    private Center $center;

    private Employee $actorEmployee;

    private User $actor;

    private ScopePolicy $policy;

    private ApprovalFlow $flow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TimeSheetAuthorizationService::class);

        $this->location = Location::factory()->create();
        $this->department = Department::factory()->create();
        $this->center = Center::factory()->create();

        $this->actorEmployee = $this->makeEmployee();
        $this->actor = User::factory()->forEmployee($this->actorEmployee)->create();

        $this->policy = ScopePolicy::query()->create([
            'name' => 'Global',
            'context' => 'time_sheet',
            'match_type' => ScopePolicy::MATCH_GLOBAL,
            'priority' => 0,
            'is_active' => true,
        ]);

        ScopePolicyActor::query()->create([
            'policy_id' => $this->policy->id,
            'actor_employee_id' => $this->actorEmployee->id,
            'can_fill' => true,
            'can_approve' => true,
            'can_revise' => true,
        ]);

        $this->flow = ApprovalFlow::query()->create([
            'context' => 'time_sheet',
            'name' => 'Simple',
            'is_active' => true,
            'applies_to' => [],
        ]);
    }

    // ---------------------------------------------------------------- stages

    public function test_there_are_no_stages_when_the_actor_manages_nobody(): void
    {
        $this->assertSame([], $this->service->approvalStages($this->actor, 3, 2026, collect(), $this->policy->id));
    }

    public function test_stages_come_back_in_flow_order_with_their_arabic_labels(): void
    {
        $this->defineFlow(['timekeeper', 'supervisor', 'superintendent']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);

        $stages = $this->stagesFor(collect([$employee->id]));

        $this->assertSame(['timekeeper', 'supervisor', 'superintendent'], array_column($stages, 'key'));
        $this->assertSame([1, 2, 3], array_column($stages, 'order'));
        $this->assertSame(['حافظ الوقت', 'مشرف القسم', 'مراقب الحقول'], array_column($stages, 'label'));
    }

    /**
     * A later stage stays hidden until the one before it is finished, so the sheet
     * cannot be signed out of order.
     */
    public function test_a_stage_is_hidden_until_the_one_before_it_is_complete(): void
    {
        $this->defineFlow(['timekeeper', 'supervisor']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);

        $stages = collect($this->stagesFor(collect([$employee->id])))->keyBy('key');
        $this->assertTrue($stages['timekeeper']['visible']);
        $this->assertFalse($stages['supervisor']['visible']);

        $this->service->approve($this->actor, 3, 2026, 'timekeeper', $this->policy->id);

        $stages = collect($this->stagesFor(collect([$employee->id])))->keyBy('key');
        $this->assertTrue($stages['timekeeper']['completed']);
        $this->assertTrue($stages['supervisor']['visible']);
    }

    public function test_a_stage_is_only_complete_when_every_employee_on_it_is_approved(): void
    {
        $this->defineFlow(['timekeeper']);
        $approved = $this->makeEmployee();
        $pending = $this->makeEmployee();
        $this->giveDays($approved, 1);

        $this->stagesFor(collect([$approved->id, $pending->id]));
        $this->service->approve($this->actor, 3, 2026, 'timekeeper', $this->policy->id);

        $stages = collect($this->stagesFor(collect([$approved->id, $pending->id])))->keyBy('key');
        $this->assertFalse($stages['timekeeper']['completed'], 'The employee with no days was never approved.');
    }

    public function test_a_stage_cannot_be_approved_without_the_role_the_flow_demands(): void
    {
        $this->defineFlow(['timekeeper'], requiredRole: 'timekeeper');
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);

        $stages = collect($this->stagesFor(collect([$employee->id])))->keyBy('key');
        $this->assertFalse($stages['timekeeper']['can_approve']);

        $this->givePermissionRole($this->actor, 'timekeeper');

        $stages = collect($this->stagesFor(collect([$employee->id])))->keyBy('key');
        $this->assertTrue($stages['timekeeper']['can_approve']);
    }

    public function test_a_stage_carries_the_signature_of_whoever_approved_it(): void
    {
        $this->defineFlow(['timekeeper']);
        Signature::query()->create(['user_id' => $this->actor->id, 'image_path' => 'signatures/actor.png']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);

        $this->stagesFor(collect([$employee->id]));
        $this->service->approve($this->actor, 3, 2026, 'timekeeper', $this->policy->id);

        $stages = collect($this->stagesFor(collect([$employee->id])))->keyBy('key');

        $this->assertSame($this->actor->name, $stages['timekeeper']['signature']['name']);
        $this->assertSame('signatures/actor.png', $stages['timekeeper']['signature']['path']);
    }

    // --------------------------------------------------------------- approve

    public function test_an_unknown_step_key_approves_nothing(): void
    {
        $this->defineFlow(['timekeeper']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);
        $this->stagesFor(collect([$employee->id]));

        $this->assertSame(0, $this->service->approve($this->actor, 3, 2026, 'manager', $this->policy->id));
    }

    public function test_coordinator_is_accepted_as_a_name_for_the_field_coordinator_step(): void
    {
        $this->defineFlow(['fieldcoordinator']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);
        $this->stagesFor(collect([$employee->id]));

        $this->assertSame(1, $this->service->approve($this->actor, 3, 2026, 'coordinator', $this->policy->id));
    }

    public function test_approving_writes_the_legacy_column_for_that_step(): void
    {
        $this->defineFlow(['timekeeper', 'supervisor', 'superintendent']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);
        $this->stagesFor(collect([$employee->id]));

        foreach (['timekeeper' => 'timekeeper_id', 'supervisor' => 'supervisor_id', 'superintendent' => 'superintendent_id'] as $step => $column) {
            $this->service->approve($this->actor, 3, 2026, $step, $this->policy->id);

            $this->assertDatabaseHas('time_sheets', [
                'employee_id' => $employee->id,
                $column => $this->actorEmployee->id,
            ]);
        }
    }

    public function test_the_field_coordinator_step_shares_the_superintendent_column(): void
    {
        $this->defineFlow(['fieldcoordinator']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);
        $this->stagesFor(collect([$employee->id]));

        $this->service->approve($this->actor, 3, 2026, 'fieldcoordinator', $this->policy->id);

        $this->assertDatabaseHas('time_sheets', [
            'employee_id' => $employee->id,
            'superintendent_id' => $this->actorEmployee->id,
        ]);
    }

    public function test_approving_a_second_time_changes_nothing(): void
    {
        $this->defineFlow(['timekeeper']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);
        $this->stagesFor(collect([$employee->id]));

        $this->assertSame(1, $this->service->approve($this->actor, 3, 2026, 'timekeeper', $this->policy->id));
        $this->assertSame(0, $this->service->approve($this->actor, 3, 2026, 'timekeeper', $this->policy->id));
    }

    public function test_only_employees_with_days_in_that_month_are_approved(): void
    {
        $this->defineFlow(['timekeeper']);
        $withDays = $this->makeEmployee();
        $withoutDays = $this->makeEmployee();
        $this->giveDays($withDays, 1);
        $this->stagesFor(collect([$withDays->id, $withoutDays->id]));

        $this->assertSame(1, $this->service->approve($this->actor, 3, 2026, 'timekeeper', $this->policy->id));

        $this->assertNull(TimeSheetApprovalStep::query()
            ->where('employee_id', $withoutDays->id)
            ->where('step_key', 'timekeeper')
            ->value('approved_at'));
    }

    public function test_a_step_whose_dependency_is_unmet_is_not_approved(): void
    {
        $this->defineFlow(['timekeeper', 'supervisor']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);
        $this->stagesFor(collect([$employee->id]));

        $this->assertSame(0, $this->service->approve($this->actor, 3, 2026, 'supervisor', $this->policy->id));
    }

    // ----------------------------------------------------- printable sheet

    public function test_the_sheet_is_empty_for_a_guest(): void
    {
        $this->assertSame([], $this->service->buildApprovalData(3, 2026));
    }

    public function test_the_sheet_pages_ordinary_employees_eight_to_a_page(): void
    {
        $this->defineFlow(['timekeeper']);

        for ($i = 0; $i < 9; $i++) {
            $this->giveDays($this->makeEmployee(), 1);
        }

        $data = $this->buildSheet();

        $this->assertCount(2, $data['chunks']);
        $this->assertCount(8, $data['chunks'][0]);
        $this->assertCount(1, $data['chunks'][1]);
    }

    /**
     * Someone who worked a full month needs the whole page to themselves, because
     * their row does not fit alongside anyone else's when printed.
     */
    public function test_an_employee_who_worked_a_full_month_gets_their_own_page(): void
    {
        $this->defineFlow(['timekeeper']);
        $this->giveDays($this->makeEmployee(), 1);
        $this->giveDays($this->makeEmployee(), Employee::SPECIAL_WORK_DAYS_THRESHOLD);

        $data = $this->buildSheet();

        $this->assertCount(2, $data['chunks']);
        $this->assertCount(1, $data['chunks'][0], 'The ordinary employee is alone on the first page.');
        $this->assertCount(1, $data['chunks'][1], 'The full-month employee has a page of their own.');
    }

    public function test_a_single_day_of_four_hours_overtime_also_earns_its_own_page(): void
    {
        $this->defineFlow(['timekeeper']);
        $ordinary = $this->makeEmployee();
        $this->giveDays($ordinary, 1);

        $special = $this->makeEmployee();
        TimeSheet::query()->create([
            'employee_id' => $special->id,
            'value' => 'A',
            'day' => '2026-03-01',
            'over_time' => 4,
        ]);

        $data = $this->buildSheet();

        $this->assertCount(2, $data['chunks']);
    }

    public function test_the_coordinator_signature_mirrors_the_field_coordinator(): void
    {
        $this->defineFlow(['fieldcoordinator']);
        Signature::query()->create(['user_id' => $this->actor->id, 'image_path' => 'signatures/actor.png']);
        $employee = $this->makeEmployee();
        $this->giveDays($employee, 1);
        $this->stagesFor(collect([$employee->id]));
        $this->service->approve($this->actor, 3, 2026, 'fieldcoordinator', $this->policy->id);

        $data = $this->buildSheet();

        $this->assertSame('signatures/actor.png', $data['signatures']['field_coordinator']['sign']);
        $this->assertSame($data['signatures']['field_coordinator'], $data['signatures']['coordinator']);
    }

    public function test_the_printed_header_comes_from_the_first_row_when_the_scope_says_nothing(): void
    {
        $this->defineFlow(['timekeeper']);
        $this->giveDays($this->makeEmployee(), 1);

        $data = $this->buildSheet();

        $this->assertSame($this->department->name, $data['department']);
        $this->assertSame($this->center->name, $data['center']);
    }

    /**
     * A scope that names its own print context overrides the employee's own
     * department, because one sheet can span several of them.
     */
    public function test_the_scope_print_settings_win_over_the_first_row(): void
    {
        $this->defineFlow(['timekeeper']);
        $this->giveDays($this->makeEmployee(), 1);

        $printDepartment = Department::factory()->create(['name' => 'Printed Department']);
        $printCenter = Center::factory()->create(['name' => 'Printed Centre']);

        $this->policy->update(['settings' => [
            'print_department_id' => $printDepartment->id,
            'print_center_id' => $printCenter->id,
        ]]);

        $data = $this->buildSheet();

        $this->assertSame('Printed Department', $data['department']);
        $this->assertSame('Printed Centre', $data['center']);
    }

    public function test_the_sheet_reports_which_stages_the_flow_requires(): void
    {
        $this->defineFlow(['timekeeper', 'supervisor']);
        $this->giveDays($this->makeEmployee(), 1);

        $data = $this->buildSheet();

        $this->assertTrue($data['requiresSupervisorStage']);
        $this->assertFalse($data['requiresFieldCoordinatorStage']);
        $this->assertFalse($data['requiresSuperintendentStage']);
        $this->assertSame(31, $data['month_days']);
        $this->assertSame(3, $data['selected_month']);
        $this->assertSame(2026, $data['selected_year']);
    }

    // ----------------------------------------------------------- fixtures

    private function makeEmployee(): Employee
    {
        return Employee::factory()->create([
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'center_id' => $this->center->id,
            'archived_at' => null,
        ]);
    }

    /**
     * @param  list<string>  $stepKeys
     */
    private function defineFlow(array $stepKeys, ?string $requiredRole = null): void
    {
        foreach ($stepKeys as $index => $stepKey) {
            ApprovalFlowStep::query()->create([
                'flow_id' => $this->flow->id,
                'step_order' => $index + 1,
                'step_key' => $stepKey,
                'required_role' => $requiredRole,
                'can_fill' => true,
                'can_approve' => true,
                'depends_on_step_order' => $index === 0 ? null : $index,
            ]);
        }
    }

    private function giveDays(Employee $employee, int $days): void
    {
        for ($day = 1; $day <= $days; $day++) {
            TimeSheet::query()->create([
                'employee_id' => $employee->id,
                'value' => 'A',
                'day' => sprintf('2026-03-%02d', $day),
                'over_time' => 0,
            ]);
        }
    }

    /**
     * @param  Collection<int, int>  $employeeIds
     * @return array<int, array<string, mixed>>
     */
    private function stagesFor(Collection $employeeIds): array
    {
        return $this->service->approvalStages($this->actor, 3, 2026, $employeeIds, $this->policy->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSheet(): array
    {
        $this->actingAs($this->actor);

        return $this->service->buildApprovalData(3, 2026, $this->policy->id);
    }

    private function givePermissionRole(User $user, string $role): void
    {
        Role::findOrCreate($role);
        $user->assignRole($role);
        $user->unsetRelation('roles');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
