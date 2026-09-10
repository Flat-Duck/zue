<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Services\TimeSheetAuth\ApprovalCommitter;
use App\Services\TimeSheetAuth\ApprovalSheetBuilder;
use App\Services\TimeSheetAuth\ApprovalStageResolver;
use App\Services\TimeSheetAuth\ScopeResolver;
use App\Services\TimeSheetAuth\WorkflowResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The way the rest of the application asks who may see and sign a time sheet.
 *
 * The work itself belongs to four collaborators — who is in scope, where the month
 * has reached, committing a signature, and laying out the printed sheet. This class
 * is the seam they are reached through, and it is deliberately thin.
 *
 * @phpstan-import-type ApprovalStage from ApprovalStageResolver
 */
class TimeSheetAuthorizationService
{
    public function __construct(
        private readonly ScopeResolver $scopeResolver,
        private readonly WorkflowResolver $workflowResolver,
        private readonly ApprovalStageResolver $stageResolver,
        private readonly ApprovalCommitter $committer,
        private readonly ApprovalSheetBuilder $sheetBuilder,
    ) {}

    /**
     * @return Builder<Employee>
     */
    public function managedEmployeesQuery(User $user, string $context = 'time_sheet'): Builder
    {
        return $this->managedEmployeesQueryForScope($user, $context);
    }

    public function managedEmployeeIds(User $user, string $context = 'time_sheet'): Collection
    {
        return $this->scopeResolver->resolveVisibleEmployeeIds($user, $context);
    }

    /**
     * @return Builder<Employee>
     */
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

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function selectableScopes(User $user, string $context = 'time_sheet'): Collection
    {
        return $this->scopeResolver->selectablePolicyOptions($user, $context);
    }

    /**
     * Supervisors are listed apart from the people they supervise, because the
     * printed sheet signs for them separately.
     *
     * @return array{
     *   supervisors:EloquentCollection<int,Employee>,
     *   normal_employees_by_department:Collection<int|string,EloquentCollection<int,Employee>>
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

        $isSupervisor = fn (Employee $employee): bool => $this->workflowResolver->hasRole($employee, 'supervisor');

        return [
            'supervisors' => $employees->filter($isSupervisor)->values(),
            'normal_employees_by_department' => $employees->reject($isSupervisor)->values()->groupBy(
                fn (Employee $employee): string => (string) ($employee->department?->name ?? 'Unknown')
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildApprovalData(int $month, int $year, ?int $requestedScopePolicyId = null): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return $this->sheetBuilder->build($user, $month, $year, $requestedScopePolicyId);
    }

    /**
     * @param  Collection<int, int>|null  $managedEmployeeIds
     * @return list<ApprovalStage>
     */
    public function approvalStages(
        User $user,
        int $month,
        int $year,
        ?Collection $managedEmployeeIds = null,
        ?int $selectedScopePolicyId = null
    ): array {
        return $this->stageResolver->resolve($user, $month, $year, $managedEmployeeIds, $selectedScopePolicyId);
    }

    public function approve(
        User $user,
        int $month,
        int $year,
        string $stepKey,
        ?int $selectedScopePolicyId = null
    ): int {
        return $this->committer->commit($user, $month, $year, $stepKey, $selectedScopePolicyId);
    }
}
