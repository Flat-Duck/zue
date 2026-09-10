<?php

namespace App\Services\TimeSheetAuth;

use App\Models\ApprovalFlowStep;
use App\Models\TimeSheetApprovalStep;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Decides whether one person may approve one employee's stage.
 *
 * Listing the stages and committing an approval ask the same four questions in the
 * same order, so they ask them here: the answer a screen shows and the answer the
 * write path enforces cannot drift apart.
 */
class ApprovalEligibility
{
    public function __construct(private readonly WorkflowResolver $workflowResolver) {}

    /**
     * @param  Collection<int, TimeSheetApprovalStep>  $steps
     * @return Collection<string, ApprovalFlowStep>
     */
    public function flowStepsFor(Collection $steps): Collection
    {
        return ApprovalFlowStep::query()
            ->whereIn('flow_id', $steps->pluck('flow_id')->unique())
            ->get()
            ->keyBy(fn (ApprovalFlowStep $step) => $this->flowStepKey((int) $step->flow_id, (int) $step->step_order));
    }

    /**
     * @param  array<int, array{can_fill?: bool, can_approve?: bool, can_revise?: bool}>  $accessMap
     * @param  Collection<string, ApprovalFlowStep>  $flowSteps
     */
    public function permits(User $user, TimeSheetApprovalStep $step, array $accessMap, Collection $flowSteps): bool
    {
        if (! is_null($step->approved_at)) {
            return false;
        }

        $capabilities = $accessMap[(int) $step->employee_id] ?? null;

        if (! $capabilities || ! ($capabilities['can_approve'] ?? false)) {
            return false;
        }

        $flowStep = $flowSteps->get($this->flowStepKey((int) $step->flow_id, (int) $step->step_order));

        if (! $flowStep || ! $flowStep->can_approve) {
            return false;
        }

        if (! $this->userHasRole($user, $flowStep->required_role)) {
            return false;
        }

        return $this->workflowResolver->dependencyIsSatisfied($step);
    }

    private function userHasRole(User $user, ?string $requiredRole): bool
    {
        if (is_null($requiredRole) || trim($requiredRole) === '') {
            return true;
        }

        return $user->hasRole(ApprovalStep::normalizeKey($requiredRole));
    }

    private function flowStepKey(int $flowId, int $order): string
    {
        return $flowId.':'.$order;
    }
}
