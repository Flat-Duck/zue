<?php

namespace App\Services\TimeSheetAuth;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Employee;
use App\Models\TimeSheetApprovalStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowResolver
{
    public function __construct(private readonly ActorResolver $actorResolver) {}

    public function ensureMonthlyStepsForEmployees(
        Collection $employeeIds,
        int $month,
        int $year,
        string $context = 'time_sheet'
    ): void {
        $ids = $employeeIds->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($ids, $month, $year, $context): void {
            $employees = Employee::query()
                ->with('department:id,name')
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get(['id', 'department_id', 'location_id', 'center_id', 'user_id']);

            if ($employees->isEmpty()) {
                return;
            }

            $existingByEmployee = TimeSheetApprovalStep::query()
                ->whereIn('employee_id', $ids)
                ->where('month', $month)
                ->where('year', $year)
                ->get(['employee_id', 'step_order'])
                ->groupBy('employee_id')
                ->map(fn (Collection $rows) => $rows->pluck('step_order')->map(fn ($n) => (int) $n)->flip());

            $flows = ApprovalFlow::query()
                ->where('context', $context)
                ->where('is_active', true)
                ->with('steps')
                ->get();

            foreach ($employees as $employee) {
                $flow = $this->resolveFlowForEmployee($employee, $context, $flows);
                if (! $flow || $flow->steps->isEmpty()) {
                    continue;
                }

                $existingOrders = $existingByEmployee->get($employee->id, collect());

                foreach ($flow->steps as $step) {
                    if ($existingOrders->has((int) $step->step_order)) {
                        continue;
                    }

                    TimeSheetApprovalStep::query()->create([
                        'employee_id' => (int) $employee->id,
                        'month' => $month,
                        'year' => $year,
                        'flow_id' => (int) $flow->id,
                        'step_order' => (int) $step->step_order,
                        'step_key' => $step->step_key,
                        'approved_by_employee_id' => null,
                        'approved_at' => null,
                        'meta' => null,
                    ]);
                }
            }
        });
    }

    public function resolveFlowForEmployee(
        Employee $employee,
        string $context = 'time_sheet',
        ?Collection $preloadedFlows = null
    ): ?ApprovalFlow {
        $flows = $preloadedFlows
            ? $preloadedFlows->where('context', $context)->where('is_active', true)->values()
            : ApprovalFlow::query()
                ->where('context', $context)
                ->where('is_active', true)
                ->with('steps')
                ->get();

        if ($flows->isEmpty()) {
            return null;
        }

        $matching = $flows
            ->filter(fn (ApprovalFlow $flow) => $this->flowMatchesEmployee($flow, $employee))
            ->sort(function (ApprovalFlow $a, ApprovalFlow $b) {
                $aScore = $this->flowSpecificityScore($a);
                $bScore = $this->flowSpecificityScore($b);

                if ($aScore !== $bScore) {
                    return $bScore <=> $aScore;
                }

                return $b->id <=> $a->id;
            });

        return $matching->first();
    }

    public function hasRole(Employee $employee, string $role): bool
    {
        $user = $this->actorResolver->resolveUserForEmployee($employee);
        if (! $user) {
            return false;
        }

        if ($role === 'coordinator') {
            $role = 'fieldcoordinator';
        }

        return $user->hasRole($role);
    }

    public function dependencyIsSatisfied(TimeSheetApprovalStep $step): bool
    {
        /** @var ApprovalFlowStep|null $flowStep */
        $flowStep = ApprovalFlowStep::query()
            ->where('flow_id', $step->flow_id)
            ->where('step_order', $step->step_order)
            ->first();

        if (! $flowStep) {
            return true;
        }

        $dependsOn = $flowStep->depends_on_step_order;
        if (is_null($dependsOn) && $step->step_order > 1) {
            $dependsOn = $step->step_order - 1;
        }

        if (is_null($dependsOn) || (int) $dependsOn < 1) {
            return true;
        }

        return TimeSheetApprovalStep::query()
            ->where('employee_id', $step->employee_id)
            ->where('month', $step->month)
            ->where('year', $step->year)
            ->where('step_order', (int) $dependsOn)
            ->whereNotNull('approved_at')
            ->exists();
    }

    private function flowMatchesEmployee(ApprovalFlow $flow, Employee $employee): bool
    {
        $rules = is_array($flow->applies_to) ? $flow->applies_to : [];
        if (empty($rules)) {
            return true;
        }

        $employeeIds = collect($rules['employee_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        if ($employeeIds->isNotEmpty() && ! $employeeIds->contains((int) $employee->id)) {
            return false;
        }

        $locationIds = collect($rules['location_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        if ($locationIds->isNotEmpty() && ! $locationIds->contains((int) $employee->location_id)) {
            return false;
        }

        $departmentIds = collect($rules['department_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        if ($departmentIds->isNotEmpty() && ! $departmentIds->contains((int) $employee->department_id)) {
            return false;
        }

        $centerIds = collect($rules['center_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        if ($centerIds->isNotEmpty() && ! $centerIds->contains((int) $employee->center_id)) {
            return false;
        }

        $departmentKeys = collect($rules['department_keys'] ?? [])->map(
            fn ($name) => $this->normalizeDepartmentName($name)
        );
        if ($departmentKeys->isNotEmpty()) {
            $employeeKey = $this->normalizeDepartmentName((string) $employee->department?->name);
            if (! $departmentKeys->contains($employeeKey)) {
                return false;
            }
        }

        $rolesAny = collect($rules['employee_roles_any'] ?? [])->map(fn ($name) => strtolower((string) $name));
        if ($rolesAny->isNotEmpty()) {
            $hasAny = $rolesAny->contains(fn ($role) => $this->hasRole($employee, $role));
            if (! $hasAny) {
                return false;
            }
        }

        $rolesNone = collect($rules['employee_roles_none'] ?? [])->map(fn ($name) => strtolower((string) $name));
        if ($rolesNone->isNotEmpty()) {
            $hasAnyForbidden = $rolesNone->contains(fn ($role) => $this->hasRole($employee, $role));
            if ($hasAnyForbidden) {
                return false;
            }
        }

        return true;
    }

    private function flowSpecificityScore(ApprovalFlow $flow): int
    {
        $rules = is_array($flow->applies_to) ? $flow->applies_to : [];

        if (! empty($rules['employee_ids'])) {
            return 500;
        }

        if (! empty($rules['center_ids'])) {
            return 400;
        }

        if (! empty($rules['department_ids']) || ! empty($rules['department_keys'])) {
            return 300;
        }

        if (! empty($rules['location_ids'])) {
            return 200;
        }

        return 100;
    }

    private function normalizeDepartmentName(?string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));
    }
}
