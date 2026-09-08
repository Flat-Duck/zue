<?php

namespace App\Services;

use App\Helpers\MomentsJs;
use App\Models\ApprovalFlowStep;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ScopePolicy;
use App\Models\TimeSheet;
use App\Models\TimeSheetApprovalStep;
use App\Models\User;
use App\Services\TimeSheetAuth\ActorResolver;
use App\Services\TimeSheetAuth\ScopeResolver;
use App\Services\TimeSheetAuth\WorkflowResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TimeSheetAuthorizationService
{
    public function __construct(
        private readonly ActorResolver $actorResolver,
        private readonly ScopeResolver $scopeResolver,
        private readonly WorkflowResolver $workflowResolver
    ) {}

    public function managedEmployeesQuery(User $user, string $context = 'time_sheet'): Builder
    {
        return $this->scopeResolver->managedEmployeesQuery($user, $context)
            ->whereNull('archived_at');
    }

    public function managedEmployeeIds(User $user, string $context = 'time_sheet'): Collection
    {
        return $this->scopeResolver->resolveVisibleEmployeeIds($user, $context);
    }

    public function managedEmployeesQueryForScope(
        User $user,
        string $context = 'time_sheet',
        ?int $selectedScopePolicyId = null
    ): Builder {
        return $this->scopeResolver
            ->managedEmployeesQuery($user, $context, $selectedScopePolicyId)
            ->whereNull('archived_at');
    }

    public function managedEmployeeIdsForScope(
        User $user,
        string $context = 'time_sheet',
        ?int $selectedScopePolicyId = null
    ): Collection {
        return $this->scopeResolver->resolveVisibleEmployeeIds($user, $context, $selectedScopePolicyId);
    }

    public function resolveSelectedScopePolicyId(
        User $user,
        string $context = 'time_sheet',
        ?int $requestedScopePolicyId = null
    ): ?int {
        return $this->scopeResolver->resolveSelectedPolicyId($user, $context, $requestedScopePolicyId);
    }

    public function selectableScopes(User $user, string $context = 'time_sheet'): Collection
    {
        return $this->scopeResolver
            ->selectablePolicies($user, $context)
            ->map(function ($policy) {
                $name = trim((string) ($policy->name ?? ''));
                if ($name === '') {
                    $name = 'Scope #'.$policy->id;
                }

                return [
                    'id' => (int) $policy->id,
                    'name' => $name,
                ];
            })
            ->values();
    }

    /**
     * @return array{
     *   supervisors:\Illuminate\Support\Collection<int,\App\Models\Employee>,
     *   normal_employees_by_department:\Illuminate\Support\Collection<string,\Illuminate\Support\Collection<int,\App\Models\Employee>>
     * }
     */
    public function groupedManagedEmployees(
        User $user,
        string $context = 'time_sheet',
        ?int $selectedScopePolicyId = null
    ): array {
        $employees = $this->managedEmployeesQueryForScope($user, $context, $selectedScopePolicyId)
            ->with('department:id,name')
            ->orderBy('department_id')
            ->orderBy('english_name')
            ->get();

        $supervisors = $employees->filter(fn (Employee $employee) => $this->workflowResolver->hasRole($employee, 'supervisor'))
            ->values();

        $normal = $employees->reject(fn (Employee $employee) => $this->workflowResolver->hasRole($employee, 'supervisor'))
            ->values();

        $normalByDepartment = $normal->groupBy(fn (Employee $employee) => (string) ($employee->department?->name ?? 'Unknown'));

        return [
            'supervisors' => $supervisors,
            'normal_employees_by_department' => $normalByDepartment,
        ];
    }

    public function buildApprovalData(int $month, int $year, ?int $requestedScopePolicyId = null): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $selectedScopePolicyId = $this->resolveSelectedScopePolicyId($user, 'time_sheet', $requestedScopePolicyId);
        $scopeOptions = $this->selectableScopes($user, 'time_sheet');

        $months = MomentsJs::getMonthsInYear();
        $monthName = $months->get($month);

        $managedEmployeeIds = $this->managedEmployeeIdsForScope($user, 'time_sheet', $selectedScopePolicyId);
        $this->workflowResolver->ensureMonthlyStepsForEmployees($managedEmployeeIds, $month, $year, 'time_sheet');
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $until = $from->copy()->addMonth();

        $baseQuery = TimeSheet::query()
            ->whereIn('employee_id', $managedEmployeeIds)
            ->where('day', '>=', $from)
            ->where('day', '<', $until)
            ->with(['employee.department.administration', 'employee.center']);

        $chunk = $baseQuery->get();
        $groupedByEmployee = $chunk->groupBy('employee_id');

        $normalEmployees = collect();
        $specialEmployees = collect();

        foreach ($groupedByEmployee as $employeeId => $days) {
            $workDaysCount = $days->whereIn('value', ['A', 'B', 'K', 'Y'])->count();
            $hasAWithFourOT = $days->contains(fn ($day) => $day->value === 'A' && (int) $day->over_time === 4);

            $threshold = defined('App\Models\Employee::SPECIAL_WORK_DAYS_THRESHOLD')
                ? Employee::SPECIAL_WORK_DAYS_THRESHOLD
                : 20;

            $isSpecial = $workDaysCount >= $threshold || $hasAWithFourOT;

            if ($isSpecial) {
                $specialEmployees->put($employeeId, $days);
            } else {
                $normalEmployees->put($employeeId, $days);
            }
        }

        $pages = collect();
        $currentPage = collect();
        $maxPerPage = 8;

        foreach ($normalEmployees as $employeeId => $days) {
            $currentPage->put($employeeId, $days);

            if ($currentPage->count() >= $maxPerPage) {
                $pages->push($currentPage);
                $currentPage = collect();
            }
        }

        if ($currentPage->isNotEmpty()) {
            $pages->push($currentPage);
        }

        foreach ($specialEmployees as $employeeId => $days) {
            $pages->push(collect([
                $employeeId => $days,
            ]));
        }

        [$department, $center, $administration] = $this->resolveApproveHeaderContext(
            $selectedScopePolicyId,
            $chunk->first()
        );

        $stages = $this->approvalStages($user, $month, $year, $managedEmployeeIds, $selectedScopePolicyId);
        $stagesByKey = collect($stages)->keyBy('key');

        $signatures = [
            'time_keeper' => ['sign' => null, 'name' => null],
            'super_visor' => ['sign' => null, 'name' => null],
            'field_coordinator' => ['sign' => null, 'name' => null],
            'coordinator' => ['sign' => null, 'name' => null],
            'super_intendent' => ['sign' => null, 'name' => null],
        ];

        $timekeeperStage = $stagesByKey->get('timekeeper');
        $supervisorStage = $stagesByKey->get('supervisor');
        $fieldCoordinatorStage = $stagesByKey->get('fieldcoordinator');
        $superintendentStage = $stagesByKey->get('superintendent');

        if ($timekeeperStage && ! empty($timekeeperStage['signature']['path'])) {
            $signatures['time_keeper'] = [
                'sign' => $timekeeperStage['signature']['path'],
                'name' => $timekeeperStage['signature']['name'],
            ];
        }

        if ($supervisorStage && ! empty($supervisorStage['signature']['path'])) {
            $signatures['super_visor'] = [
                'sign' => $supervisorStage['signature']['path'],
                'name' => $supervisorStage['signature']['name'],
            ];
        }

        if ($fieldCoordinatorStage && ! empty($fieldCoordinatorStage['signature']['path'])) {
            $signatures['field_coordinator'] = [
                'sign' => $fieldCoordinatorStage['signature']['path'],
                'name' => $fieldCoordinatorStage['signature']['name'],
            ];
            $signatures['coordinator'] = $signatures['field_coordinator'];
        }

        if ($superintendentStage && ! empty($superintendentStage['signature']['path'])) {
            $signatures['super_intendent'] = [
                'sign' => $superintendentStage['signature']['path'],
                'name' => $superintendentStage['signature']['name'],
            ];
        }

        $monthDays = Carbon::create($year, $month, 1)->daysInMonth;
        $employees = Employee::pluck('english_name', 'id');

        return [
            'chunks' => $pages,
            'department' => $department,
            'center' => $center,
            'administration' => $administration,
            'month_name' => $monthName,
            'selected_month' => $month,
            'selected_year' => $year,
            'month_days' => $monthDays,
            'employees' => $employees,
            'signatures' => $signatures,
            'canTimekeeperApprove' => (bool) ($timekeeperStage['can_approve'] ?? false),
            'canSupervisorApprove' => (bool) ($supervisorStage['can_approve'] ?? false),
            'canFieldCoordinatorApprove' => (bool) ($fieldCoordinatorStage['can_approve'] ?? false),
            'canCoordinatorApprove' => (bool) ($fieldCoordinatorStage['can_approve'] ?? false),
            'canSuperintendentApprove' => (bool) ($superintendentStage['can_approve'] ?? false),
            'requiresSupervisorStage' => ! is_null($supervisorStage),
            'requiresFieldCoordinatorStage' => ! is_null($fieldCoordinatorStage),
            'requiresSuperintendentStage' => ! is_null($superintendentStage),
            'approvalStages' => $stages,
            'scopeOptions' => $scopeOptions,
            'selectedScopePolicyId' => $selectedScopePolicyId,
        ];
    }

    /**
     * @return array<int, array{
     *   key:string,
     *   label:string,
     *   order:int,
     *   can_approve:bool,
     *   completed:bool,
     *   visible:bool,
     *   signature:array{name:?string,path:?string}
     * }>
     */
    public function approvalStages(
        User $user,
        int $month,
        int $year,
        ?Collection $managedEmployeeIds = null,
        ?int $selectedScopePolicyId = null
    ): array {
        $managedEmployeeIds ??= $this->managedEmployeeIdsForScope($user, 'time_sheet', $selectedScopePolicyId);
        if ($managedEmployeeIds->isEmpty()) {
            return [];
        }

        $this->workflowResolver->ensureMonthlyStepsForEmployees($managedEmployeeIds, $month, $year, 'time_sheet');

        $steps = TimeSheetApprovalStep::query()
            ->whereIn('employee_id', $managedEmployeeIds)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        if ($steps->isEmpty()) {
            return [];
        }

        $flowStepMap = ApprovalFlowStep::query()
            ->whereIn('flow_id', $steps->pluck('flow_id')->unique())
            ->get()
            ->keyBy(fn (ApprovalFlowStep $step) => $this->flowStepKey((int) $step->flow_id, (int) $step->step_order));

        $accessMap = $this->scopeResolver->resolveEmployeeAccessMap($user, 'time_sheet', $selectedScopePolicyId);
        $grouped = $steps->groupBy('step_key');

        $stageRows = [];
        foreach ($grouped as $stepKey => $rows) {
            $rows = $rows->values();
            $order = (int) $rows->min('step_order');
            $approvedRows = $rows->filter(fn (TimeSheetApprovalStep $row) => ! is_null($row->approved_at))->values();

            $signature = ['name' => null, 'path' => null];
            if ($approvedRows->isNotEmpty()) {
                $latest = $approvedRows->sortByDesc('approved_at')->first();
                $signature = $this->resolveSignatureForApprover((int) $latest->approved_by_employee_id);
            }

            $canApprove = false;
            foreach ($rows as $row) {
                if (! is_null($row->approved_at)) {
                    continue;
                }

                $capabilities = $accessMap[(int) $row->employee_id] ?? null;
                if (! $capabilities || ! $capabilities['can_approve']) {
                    continue;
                }

                $flowStep = $flowStepMap->get($this->flowStepKey((int) $row->flow_id, (int) $row->step_order));
                if (! $flowStep || ! $flowStep->can_approve) {
                    continue;
                }

                if (! $this->userHasRoleForStep($user, $flowStep->required_role)) {
                    continue;
                }

                if (! $this->workflowResolver->dependencyIsSatisfied($row)) {
                    continue;
                }

                $canApprove = true;
                break;
            }

            $completed = $rows->isNotEmpty() && $rows->every(fn (TimeSheetApprovalStep $row) => ! is_null($row->approved_at));

            $stageRows[] = [
                'key' => (string) $stepKey,
                'label' => $this->stepLabel((string) $stepKey),
                'order' => $order,
                'can_approve' => $canApprove,
                'completed' => $completed,
                'visible' => true,
                'signature' => $signature,
            ];
        }

        usort($stageRows, function (array $a, array $b) {
            if ((int) $a['order'] !== (int) $b['order']) {
                return ((int) $a['order']) <=> ((int) $b['order']);
            }

            return strcmp((string) $a['key'], (string) $b['key']);
        });

        // Hide higher stages until lower stage has completed.
        $allowNext = true;
        foreach ($stageRows as $index => $stageRow) {
            $stageRows[$index]['visible'] = $allowNext;
            if ($allowNext && ! $stageRow['completed']) {
                $allowNext = false;
            }
        }

        return $stageRows;
    }

    public function approve(
        User $user,
        int $month,
        int $year,
        string $stepKey,
        ?int $selectedScopePolicyId = null
    ): int {
        $stepKey = $this->normalizeStepKey($stepKey);
        $allowed = ['timekeeper', 'supervisor', 'fieldcoordinator', 'superintendent'];
        if (! in_array($stepKey, $allowed, true)) {
            return 0;
        }

        $actor = $this->actorResolver->resolveEmployee($user);
        if (! $actor) {
            abort(403);
        }

        $managedEmployeeIds = $this->managedEmployeeIdsForScope($user, 'time_sheet', $selectedScopePolicyId);
        if ($managedEmployeeIds->isEmpty()) {
            return 0;
        }

        $this->workflowResolver->ensureMonthlyStepsForEmployees($managedEmployeeIds, $month, $year, 'time_sheet');

        $accessMap = $this->scopeResolver->resolveEmployeeAccessMap($user, 'time_sheet', $selectedScopePolicyId);

        $steps = TimeSheetApprovalStep::query()
            ->whereIn('employee_id', $managedEmployeeIds)
            ->where('month', $month)
            ->where('year', $year)
            ->where('step_key', $stepKey)
            ->whereNull('approved_at')
            ->get();

        if ($steps->isEmpty()) {
            return 0;
        }

        $flowStepMap = ApprovalFlowStep::query()
            ->whereIn('flow_id', $steps->pluck('flow_id')->unique())
            ->get()
            ->keyBy(fn (ApprovalFlowStep $step) => $this->flowStepKey((int) $step->flow_id, (int) $step->step_order));

        $approvedEmployeeIds = [];
        foreach ($steps as $step) {
            $capabilities = $accessMap[(int) $step->employee_id] ?? null;
            if (! $capabilities || ! $capabilities['can_approve']) {
                continue;
            }

            $flowStep = $flowStepMap->get($this->flowStepKey((int) $step->flow_id, (int) $step->step_order));
            if (! $flowStep || ! $flowStep->can_approve) {
                continue;
            }

            if (! $this->userHasRoleForStep($user, $flowStep->required_role)) {
                continue;
            }

            if (! $this->workflowResolver->dependencyIsSatisfied($step)) {
                continue;
            }

            $step->approved_by_employee_id = (int) $actor->id;
            $step->approved_at = now();
            $step->save();

            $approvedEmployeeIds[(int) $step->employee_id] = true;
        }

        if (empty($approvedEmployeeIds)) {
            return 0;
        }

        $legacyColumn = $this->legacyColumnForStep($stepKey);
        if (is_null($legacyColumn)) {
            return count($approvedEmployeeIds);
        }

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $until = $from->copy()->addMonth();

        return (int) TimeSheet::query()
            ->whereIn('employee_id', array_keys($approvedEmployeeIds))
            ->where('day', '>=', $from)
            ->where('day', '<', $until)
            ->whereNull($legacyColumn)
            ->update([$legacyColumn => (int) $actor->id]);
    }

    private function resolveSignatureForApprover(int $employeeId): array
    {
        $employee = Employee::query()->find($employeeId);
        if (! $employee) {
            return ['name' => null, 'path' => null];
        }

        $user = $this->actorResolver->resolveUserForEmployee($employee);
        if (! $user) {
            return ['name' => null, 'path' => null];
        }

        return [
            'name' => $user->name,
            'path' => $user->signature?->image_path,
        ];
    }

    private function userHasRoleForStep(User $user, ?string $requiredRole): bool
    {
        if (is_null($requiredRole) || trim($requiredRole) === '') {
            return true;
        }

        $requiredRole = strtolower(trim($requiredRole));
        if ($requiredRole === 'coordinator') {
            $requiredRole = 'fieldcoordinator';
        }

        return $user->hasRole($requiredRole);
    }

    private function normalizeStepKey(string $stepKey): string
    {
        $stepKey = strtolower(trim($stepKey));
        if ($stepKey === 'coordinator') {
            return 'fieldcoordinator';
        }

        return $stepKey;
    }

    private function stepLabel(string $stepKey): string
    {
        return match ($this->normalizeStepKey($stepKey)) {
            'timekeeper' => 'حافظ الوقت',
            'supervisor' => 'مشرف القسم',
            'fieldcoordinator' => 'منسق الحقول',
            'superintendent' => 'مراقب الحقول',
            default => ucfirst($stepKey),
        };
    }

    private function legacyColumnForStep(string $stepKey): ?string
    {
        return match ($this->normalizeStepKey($stepKey)) {
            'timekeeper' => 'timekeeper_id',
            'supervisor' => 'supervisor_id',
            'fieldcoordinator', 'superintendent' => 'superintendent_id',
            default => null,
        };
    }

    private function flowStepKey(int $flowId, int $order): string
    {
        return $flowId.':'.$order;
    }

    /**
     * Resolve print/header context from selected scope first.
     * Falls back to first timesheet row when scope has no explicit print context.
     */
    private function resolveApproveHeaderContext(?int $selectedScopePolicyId, ?TimeSheet $firstRow): array
    {
        $department = '';
        $center = '';
        $administration = '';

        if (! is_null($selectedScopePolicyId)) {
            $policy = ScopePolicy::query()->find($selectedScopePolicyId, ['id', 'department_id', 'center_id', 'settings']);
            if ($policy) {
                $settings = is_array($policy->settings) ? $policy->settings : [];

                $printDepartmentId = (int) ($settings['print_department_id'] ?? ($policy->department_id ?? 0));
                $printCenterId = (int) ($settings['print_center_id'] ?? ($policy->center_id ?? 0));

                if ($printDepartmentId > 0) {
                    $departmentModel = Department::query()
                        ->with('administration:id,name')
                        ->find($printDepartmentId, ['id', 'name', 'administration_id']);

                    if ($departmentModel) {
                        $department = (string) ($departmentModel->name ?? '');
                        $administration = (string) ($departmentModel->administration?->name ?? '');
                    }
                }

                if ($printCenterId > 0) {
                    $centerModel = Center::query()->find($printCenterId, ['id', 'name']);
                    if ($centerModel) {
                        $center = (string) ($centerModel->name ?? '');
                    }
                }
            }
        }

        if ($department !== '' || $center !== '' || $administration !== '') {
            return [$department, $center, $administration];
        }

        return [
            (string) ($firstRow?->employee?->department?->name ?? ''),
            (string) ($firstRow?->employee?->center?->name ?? ''),
            (string) ($firstRow?->employee?->department?->administration?->name ?? ''),
        ];
    }
}
