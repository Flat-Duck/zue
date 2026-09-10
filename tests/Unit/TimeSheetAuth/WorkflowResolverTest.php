<?php

namespace Tests\Unit\TimeSheetAuth;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\TimeSheetApprovalStep;
use App\Services\TimeSheetAuth\WorkflowResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_step_is_blocked_until_dependency_is_approved(): void
    {
        $resolver = app(WorkflowResolver::class);

        $flow = ApprovalFlow::query()->create([
            'context' => 'time_sheet',
            'name' => 'Dependency Test',
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

        ApprovalFlowStep::query()->create([
            'flow_id' => $flow->id,
            'step_order' => 2,
            'step_key' => 'supervisor',
            'required_role' => null,
            'can_fill' => false,
            'can_approve' => true,
            'depends_on_step_order' => 1,
        ]);

        $location = Location::factory()->create();
        $department = Department::factory()->create();
        $center = Center::factory()->create();
        $employee = Employee::factory()->create([
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $resolver->ensureMonthlyStepsForEmployees(collect([$employee->id]), 4, 2026, 'time_sheet');

        $secondStep = TimeSheetApprovalStep::query()
            ->where('employee_id', $employee->id)
            ->where('month', 4)
            ->where('year', 2026)
            ->where('step_order', 2)
            ->firstOrFail();

        $this->assertFalse($resolver->dependencyIsSatisfied($secondStep));

        TimeSheetApprovalStep::query()
            ->where('employee_id', $employee->id)
            ->where('month', 4)
            ->where('year', 2026)
            ->where('step_order', 1)
            ->update(['approved_at' => now()]);

        $secondStep->refresh();
        $this->assertTrue($resolver->dependencyIsSatisfied($secondStep));
    }
}
