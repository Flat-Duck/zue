<?php

namespace App\Services\TimeSheetAuth;

use App\Models\ApprovalFlowStep;
use App\Models\Employee;
use App\Models\TimeSheetApprovalStep;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Describes where a month's sheet has reached in its approval flow.
 *
 * One stage covers every employee in scope, so a stage is only finished once all of
 * them are, and a stage stays out of sight until the one before it is finished —
 * that ordering is what stops a sheet being signed off from the top down.
 *
 * @phpstan-type ApprovalStage array{
 *   key: string,
 *   label: string,
 *   order: int,
 *   can_approve: bool,
 *   completed: bool,
 *   visible: bool,
 *   signature: array{name: string|null, path: string|null}
 * }
 */
class ApprovalStageResolver
{
    public function __construct(
        private readonly ActorResolver $actorResolver,
        private readonly ScopeResolver $scopeResolver,
        private readonly WorkflowResolver $workflowResolver,
        private readonly ApprovalEligibility $eligibility,
    ) {}

    /**
     * @param  Collection<int, int>|null  $managedEmployeeIds
     * @return list<ApprovalStage>
     */
    public function resolve(
        User $user,
        int $month,
        int $year,
        ?Collection $managedEmployeeIds = null,
        ?int $selectedScopePolicyId = null
    ): array {
        $managedEmployeeIds ??= $this->scopeResolver->resolveVisibleEmployeeIds($user, 'time_sheet', $selectedScopePolicyId);

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

        $flowSteps = $this->eligibility->flowStepsFor($steps);
        $accessMap = $this->scopeResolver->resolveEmployeeAccessMap($user, 'time_sheet', $selectedScopePolicyId);

        $stages = $steps->groupBy('step_key')
            ->map(fn (Collection $rows, string $stepKey): array => $this->describeStage(
                $user,
                $stepKey,
                $rows->values(),
                $accessMap,
                $flowSteps,
            ))
            ->values()
            ->all();

        usort($stages, static fn (array $a, array $b): int => [$a['order'], $a['key']] <=> [$b['order'], $b['key']]);

        return $this->hideStagesBeyondTheFirstUnfinishedOne($stages);
    }

    /**
     * @param  Collection<int, TimeSheetApprovalStep>  $rows
     * @param  array<int, array{can_fill?: bool, can_approve?: bool, can_revise?: bool}>  $accessMap
     * @param  Collection<string, ApprovalFlowStep>  $flowSteps
     * @return array<string, mixed>
     */
    private function describeStage(User $user, string $stepKey, Collection $rows, array $accessMap, Collection $flowSteps): array
    {
        $canApprove = $rows->contains(
            fn (TimeSheetApprovalStep $row): bool => $this->eligibility->permits($user, $row, $accessMap, $flowSteps)
        );

        return [
            'key' => $stepKey,
            'label' => ApprovalStep::fromKey($stepKey)?->label() ?? ucfirst($stepKey),
            'order' => (int) $rows->min('step_order'),
            'can_approve' => $canApprove,
            'completed' => $rows->every(fn (TimeSheetApprovalStep $row): bool => ! is_null($row->approved_at)),
            'visible' => true,
            'signature' => $this->signatureForLatestApprover($rows),
        ];
    }

    /**
     * @param  Collection<int, TimeSheetApprovalStep>  $rows
     * @return array{name: ?string, path: ?string}
     */
    private function signatureForLatestApprover(Collection $rows): array
    {
        $latest = $rows->filter(fn (TimeSheetApprovalStep $row): bool => ! is_null($row->approved_at))
            ->sortByDesc('approved_at')
            ->first();

        if (! $latest) {
            return ['name' => null, 'path' => null];
        }

        return $this->signatureFor((int) $latest->approved_by_employee_id);
    }

    /**
     * @return array{name: ?string, path: ?string}
     */
    private function signatureFor(int $employeeId): array
    {
        $employee = Employee::query()->find($employeeId);
        $user = $employee ? $this->actorResolver->resolveUserForEmployee($employee) : null;

        if (! $user) {
            return ['name' => null, 'path' => null];
        }

        return ['name' => $user->name, 'path' => $user->signature?->image_path];
    }

    /**
     * @param  list<ApprovalStage>  $stages
     * @return list<ApprovalStage>
     */
    private function hideStagesBeyondTheFirstUnfinishedOne(array $stages): array
    {
        $reached = true;

        foreach ($stages as $index => $stage) {
            $stages[$index]['visible'] = $reached;

            if ($reached && ! $stage['completed']) {
                $reached = false;
            }
        }

        return $stages;
    }
}
