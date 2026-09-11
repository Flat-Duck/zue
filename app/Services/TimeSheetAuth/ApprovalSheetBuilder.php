<?php

namespace App\Services\TimeSheetAuth;

use App\Helpers\MomentsJs;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ScopePolicy;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Assembles the month's approval sheet: the thing that gets signed and printed.
 *
 * Its shape is dictated by the paper form. Eight ordinary employees fit on a page,
 * and anyone who worked a full month needs a page to themselves because their row
 * runs the width of it.
 *
 * @phpstan-import-type ApprovalStage from ApprovalStageResolver
 */
class ApprovalSheetBuilder
{
    private const EMPLOYEES_PER_PAGE = 8;

    /**
     * Days that count as worked when deciding whether an employee needs their own page.
     */
    private const WORKED_VALUES = ['A', 'B', 'K', 'Y'];

    public function __construct(
        private readonly ScopeResolver $scopeResolver,
        private readonly WorkflowResolver $workflowResolver,
        private readonly ApprovalStageResolver $stageResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, int $month, int $year, ?int $requestedScopePolicyId = null): array
    {
        $selectedScopePolicyId = $this->scopeResolver->resolveSelectedPolicyId($user, 'time_sheet', $requestedScopePolicyId);
        $managedEmployeeIds = $this->scopeResolver->resolveVisibleEmployeeIds($user, 'time_sheet', $selectedScopePolicyId);

        $this->workflowResolver->ensureMonthlyStepsForEmployees($managedEmployeeIds, $month, $year, 'time_sheet');

        $from = Carbon::create($year, $month, 1)->startOfMonth();

        $days = TimeSheet::query()
            ->whereIn('employee_id', $managedEmployeeIds)
            ->where('day', '>=', $from)
            ->where('day', '<', $from->copy()->addMonth())
            ->with(['employee.department.administration', 'employee.center'])
            ->get();

        [$department, $center, $administration] = $this->headerContext($selectedScopePolicyId, $days->first());

        $stages = $this->stageResolver->resolve($user, $month, $year, $managedEmployeeIds, $selectedScopePolicyId);
        $stagesByKey = array_column($stages, null, 'key');

        return [
            'chunks' => $this->paginate($days),
            'department' => $department,
            'center' => $center,
            'administration' => $administration,
            'month_name' => MomentsJs::getMonthsInYear()->get($month),
            'selected_month' => $month,
            'selected_year' => $year,
            'month_days' => $from->daysInMonth,
            'employees' => Employee::pluck('english_name', 'id'),
            'signatures' => $this->signatures($stagesByKey),
            'canTimekeeperApprove' => $this->canApprove($stagesByKey, ApprovalStep::Timekeeper),
            'canSupervisorApprove' => $this->canApprove($stagesByKey, ApprovalStep::Supervisor),
            'canFieldCoordinatorApprove' => $this->canApprove($stagesByKey, ApprovalStep::FieldCoordinator),
            'canCoordinatorApprove' => $this->canApprove($stagesByKey, ApprovalStep::FieldCoordinator),
            'canSuperintendentApprove' => $this->canApprove($stagesByKey, ApprovalStep::Superintendent),
            'requiresSupervisorStage' => isset($stagesByKey[ApprovalStep::Supervisor->value]),
            'requiresFieldCoordinatorStage' => isset($stagesByKey[ApprovalStep::FieldCoordinator->value]),
            'requiresSuperintendentStage' => isset($stagesByKey[ApprovalStep::Superintendent->value]),
            'approvalStages' => $stages,
            'scopeOptions' => $this->scopeResolver->selectablePolicyOptions($user, 'time_sheet'),
            'selectedScopePolicyId' => $selectedScopePolicyId,
        ];
    }

    /**
     * Ordinary employees fill pages eight at a time; the ones who need a full-width
     * row are appended afterwards, one page each.
     *
     * @param  Collection<int, TimeSheet>  $days
     * @return Collection<int, Collection<int|string, Collection<int, TimeSheet>>>
     */
    private function paginate(Collection $days): Collection
    {
        [$needFullPage, $ordinary] = $days->groupBy('employee_id')->partition(
            fn (Collection $days): bool => $this->needsAPageOfTheirOwn($days)
        );

        $pages = $ordinary->chunk(self::EMPLOYEES_PER_PAGE)->values();

        foreach ($needFullPage as $employeeId => $days) {
            $pages->push(collect([$employeeId => $days]));
        }

        return $pages;
    }

    /**
     * @param  Collection<int, TimeSheet>  $days
     */
    private function needsAPageOfTheirOwn(Collection $days): bool
    {
        if ($days->whereIn('value', self::WORKED_VALUES)->count() >= Employee::SPECIAL_WORK_DAYS_THRESHOLD) {
            return true;
        }

        return $days->contains(fn (TimeSheet $day): bool => $day->value === 'A' && (int) $day->over_time === 4);
    }

    /**
     * @param  array<string, ApprovalStage>  $stagesByKey
     * @return array<string, array{sign: ?string, name: ?string}>
     */
    private function signatures(array $stagesByKey): array
    {
        $signatures = [
            'time_keeper' => $this->signature($stagesByKey, ApprovalStep::Timekeeper),
            'super_visor' => $this->signature($stagesByKey, ApprovalStep::Supervisor),
            'field_coordinator' => $this->signature($stagesByKey, ApprovalStep::FieldCoordinator),
            'super_intendent' => $this->signature($stagesByKey, ApprovalStep::Superintendent),
        ];

        // The printed form names this slot twice.
        $signatures['coordinator'] = $signatures['field_coordinator'];

        return $signatures;
    }

    /**
     * @param  array<string, ApprovalStage>  $stagesByKey
     * @return array{sign: ?string, name: ?string}
     */
    private function signature(array $stagesByKey, ApprovalStep $step): array
    {
        $signature = $stagesByKey[$step->value]['signature'] ?? null;

        if (empty($signature['path'])) {
            return ['sign' => null, 'name' => null];
        }

        return ['sign' => $signature['path'], 'name' => $signature['name']];
    }

    /**
     * @param  array<string, ApprovalStage>  $stagesByKey
     */
    private function canApprove(array $stagesByKey, ApprovalStep $step): bool
    {
        return (bool) ($stagesByKey[$step->value]['can_approve'] ?? false);
    }

    /**
     * A scope that names its own print context speaks for the whole sheet, which may
     * span several departments. Only when it says nothing does the first employee's
     * own department stand in.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function headerContext(?int $selectedScopePolicyId, ?TimeSheet $firstRow): array
    {
        $fromScope = $this->headerContextFromScope($selectedScopePolicyId);

        if (array_filter($fromScope) !== []) {
            return $fromScope;
        }

        return [
            (string) ($firstRow?->employee?->department?->name ?? ''),
            (string) ($firstRow?->employee?->center?->name ?? ''),
            (string) ($firstRow?->employee?->department?->administration?->name ?? ''),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function headerContextFromScope(?int $selectedScopePolicyId): array
    {
        if (is_null($selectedScopePolicyId)) {
            return ['', '', ''];
        }

        $policy = ScopePolicy::query()->find(
            $selectedScopePolicyId,
            ['id', 'print_department_id', 'print_center_id', 'settings']
        );

        if (! $policy) {
            return ['', '', ''];
        }

        $settings = is_array($policy->settings) ? $policy->settings : [];
        $department = '';
        $administration = '';
        $center = '';

        $departmentId = (int) ($policy->print_department_id ?? ($settings['print_department_id'] ?? 0));

        if ($departmentId > 0) {
            $model = Department::query()
                ->with('administration:id,name')
                ->find($departmentId, ['id', 'name', 'administration_id']);

            $department = (string) ($model?->name ?? '');
            $administration = (string) ($model?->administration?->name ?? '');
        }

        $centerId = (int) ($policy->print_center_id ?? ($settings['print_center_id'] ?? 0));

        if ($centerId > 0) {
            $center = (string) (Center::query()->find($centerId, ['id', 'name'])?->name ?? '');
        }

        return [$department, $center, $administration];
    }
}
