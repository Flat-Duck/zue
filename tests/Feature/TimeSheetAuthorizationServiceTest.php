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
use App\Models\TimeSheet;
use App\Models\TimeSheetApprovalStep;
use App\Models\User;
use App\Services\TimeSheetAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSheetAuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_updates_v2_step_and_legacy_timesheet_column(): void
    {
        $service = app(TimeSheetAuthorizationService::class);

        $location = Location::factory()->create();
        $department = Department::factory()->create();
        $center = Center::factory()->create();

        $actorEmployee = Employee::factory()->create([

            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $actorUser = User::factory()->forEmployee($actorEmployee)->create();

        $targetEmployee = Employee::factory()->create([
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $policy = ScopePolicy::query()->create([
            'name' => 'Global',
            'context' => 'time_sheet',
            'match_type' => ScopePolicy::MATCH_GLOBAL,
            'location_id' => null,
            'department_id' => null,
            'center_id' => null,
            'target_employee_ids' => null,
            'priority' => 0,
            'is_active' => true,
            'settings' => null,
        ]);

        ScopePolicyActor::query()->create([
            'policy_id' => $policy->id,
            'actor_employee_id' => $actorEmployee->id,
            'can_fill' => true,
            'can_approve' => true,
            'can_revise' => true,
            'role_hint' => null,
        ]);

        $flow = ApprovalFlow::query()->create([
            'context' => 'time_sheet',
            'name' => 'Simple',
            'is_active' => true,
            'applies_to' => [],
        ]);

        ApprovalFlowStep::query()->create([
            'flow_id' => $flow->id,
            'step_order' => 1,
            'step_key' => 'timekeeper',
            'required_role' => null,
            'can_fill' => true,
            'can_approve' => true,
            'depends_on_step_order' => null,
        ]);

        TimeSheet::query()->create([
            'value' => 'A',
            'day' => '2026-03-01',
            'employee_id' => $targetEmployee->id,
            'revised_at' => null,
            'old_value' => null,
            'over_time' => 0,
        ]);

        $service->approvalStages($actorUser, 3, 2026, collect([$targetEmployee->id]), $policy->id);
        $service->approvalStages($actorUser, 3, 2026, collect([$targetEmployee->id]), $policy->id);

        $this->assertSame(1, TimeSheetApprovalStep::query()
            ->where('employee_id', $targetEmployee->id)
            ->where('month', 3)
            ->where('year', 2026)
            ->where('step_key', 'timekeeper')
            ->count());

        $updated = $service->approve($actorUser, 3, 2026, 'timekeeper');

        $this->assertSame(1, $updated);
        $this->assertDatabaseHas('time_sheets', [
            'employee_id' => $targetEmployee->id,
            'timekeeper_id' => $actorEmployee->id,
        ]);
        $this->assertDatabaseHas('timesheet_approval_steps', [
            'employee_id' => $targetEmployee->id,
            'month' => 3,
            'year' => 2026,
            'step_key' => 'timekeeper',
            'approved_by_employee_id' => $actorEmployee->id,
        ]);

        $step = TimeSheetApprovalStep::query()
            ->where('employee_id', $targetEmployee->id)
            ->where('month', 3)
            ->where('year', 2026)
            ->where('step_key', 'timekeeper')
            ->first();

        $this->assertNotNull($step?->approved_at);
    }
}
