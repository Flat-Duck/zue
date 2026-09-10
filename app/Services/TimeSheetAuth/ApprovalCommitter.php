<?php

namespace App\Services\TimeSheetAuth;

use App\Models\ApprovalFlowStep;
use App\Models\TimeSheet;
use App\Models\TimeSheetApprovalStep;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Signs off one stage for every employee in scope who has days that month.
 *
 * Each row is re-read under a lock inside the transaction before it is stamped, so
 * two approvers pressing the button at the same moment cannot both claim the same
 * signature. Rows the actor turns out not to be entitled to are stepped over rather
 * than failing the batch: partial authority over a scope is normal here.
 */
class ApprovalCommitter
{
    public function __construct(
        private readonly ActorResolver $actorResolver,
        private readonly ScopeResolver $scopeResolver,
        private readonly WorkflowResolver $workflowResolver,
        private readonly ApprovalEligibility $eligibility,
    ) {}

    /**
     * @return int the number of employees whose stage was signed
     */
    public function commit(
        User $user,
        int $month,
        int $year,
        string $stepKey,
        ?int $selectedScopePolicyId = null
    ): int {
        $step = ApprovalStep::fromKey($stepKey);

        if (! $step) {
            return 0;
        }

        $actor = $this->actorResolver->resolveEmployee($user);

        if (! $actor) {
            abort(403);
        }

        $managedEmployeeIds = $this->scopeResolver->resolveVisibleEmployeeIds($user, 'time_sheet', $selectedScopePolicyId);

        if ($managedEmployeeIds->isEmpty()) {
            return 0;
        }

        $this->workflowResolver->ensureMonthlyStepsForEmployees($managedEmployeeIds, $month, $year, 'time_sheet');

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $until = $from->copy()->addMonth();

        $employeeIdsWithSheets = TimeSheet::query()
            ->whereIn('employee_id', $managedEmployeeIds)
            ->where('day', '>=', $from)
            ->where('day', '<', $until)
            ->distinct()
            ->pluck('employee_id');

        if ($employeeIdsWithSheets->isEmpty()) {
            return 0;
        }

        $pendingSteps = TimeSheetApprovalStep::query()
            ->whereIn('employee_id', $employeeIdsWithSheets)
            ->where('month', $month)
            ->where('year', $year)
            ->where('step_key', $step->value)
            ->whereNull('approved_at')
            ->get();

        if ($pendingSteps->isEmpty()) {
            return 0;
        }

        $accessMap = $this->scopeResolver->resolveEmployeeAccessMap($user, 'time_sheet', $selectedScopePolicyId);
        $flowSteps = $this->eligibility->flowStepsFor($pendingSteps);

        return DB::transaction(function () use ($user, $actor, $step, $pendingSteps, $accessMap, $flowSteps, $from, $until): int {
            $approvedEmployeeIds = $this->stampSteps($user, (int) $actor->id, $pendingSteps, $accessMap, $flowSteps);

            if ($approvedEmployeeIds === []) {
                return 0;
            }

            $this->stampLegacyColumn($step, $approvedEmployeeIds, (int) $actor->id, $from, $until);

            return count($approvedEmployeeIds);
        });
    }

    /**
     * @param  Collection<int, TimeSheetApprovalStep>  $pendingSteps
     * @param  array<int, array{can_fill?: bool, can_approve?: bool, can_revise?: bool}>  $accessMap
     * @param  Collection<string, ApprovalFlowStep>  $flowSteps
     * @return list<int>
     */
    private function stampSteps(User $user, int $actorEmployeeId, Collection $pendingSteps, array $accessMap, Collection $flowSteps): array
    {
        $approvedEmployeeIds = [];

        foreach ($pendingSteps as $pendingStep) {
            $lockedStep = TimeSheetApprovalStep::query()
                ->whereKey($pendingStep->id)
                ->whereNull('approved_at')
                ->lockForUpdate()
                ->first();

            if (! $lockedStep || ! $this->eligibility->permits($user, $lockedStep, $accessMap, $flowSteps)) {
                continue;
            }

            $lockedStep->approved_by_employee_id = $actorEmployeeId;
            $lockedStep->approved_at = now();
            $lockedStep->save();

            $approvedEmployeeIds[(int) $lockedStep->employee_id] = true;
        }

        return array_keys($approvedEmployeeIds);
    }

    /**
     * @param  list<int>  $employeeIds
     */
    private function stampLegacyColumn(ApprovalStep $step, array $employeeIds, int $actorEmployeeId, Carbon $from, Carbon $until): void
    {
        $column = $step->legacyColumn();

        TimeSheet::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('day', '>=', $from)
            ->where('day', '<', $until)
            ->whereNull($column)
            ->update([$column => $actorEmployeeId]);
    }
}
