<?php

namespace App\Services\TimeSheetAuth;

use App\Models\Employee;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ScopeResolver
{
    public function __construct(private readonly ActorResolver $actorResolver) {}

    /**
     * Returns map of visible employee IDs keyed by employee id.
     * Each value contains winning policy and actor capabilities.
     *
     * @return array<int, array{policy_id:int,can_fill:bool,can_approve:bool,can_revise:bool}>
     */
    public function resolveEmployeeAccessMap(
        User $user,
        string $context = 'time_sheet',
        ?int $selectedPolicyId = null
    ): array {
        $actorEmployee = $this->actorResolver->resolveEmployee($user);
        if (! $actorEmployee) {
            return [];
        }

        $policies = ScopePolicy::query()
            ->where('context', $context)
            ->where('is_active', true)
            ->with('actors')
            ->get();

        if ($policies->isEmpty()) {
            return [];
        }

        $actorPolicies = $policies->filter(function (ScopePolicy $policy) use ($actorEmployee) {
            $actor = $policy->actors->firstWhere('actor_employee_id', $actorEmployee->id);
            if (! $actor) {
                return false;
            }

            return $actor->can_fill || $actor->can_approve || $actor->can_revise;
        })->values();

        if (! is_null($selectedPolicyId)) {
            $actorPolicies = $actorPolicies
                ->where('id', (int) $selectedPolicyId)
                ->values();
        }

        if ($actorPolicies->isEmpty()) {
            return [];
        }

        $candidateIds = [];
        foreach ($actorPolicies as $policy) {
            foreach ($this->matchedEmployeeIdsForPolicy($policy) as $employeeId) {
                $candidateIds[(int) $employeeId] = true;
            }
        }

        if (empty($candidateIds)) {
            return [];
        }

        $employees = Employee::query()
            ->whereIn('id', array_keys($candidateIds))
            ->whereNull('archived_at')
            ->get(['id', 'location_id', 'department_id', 'center_id', 'job']);

        if ($employees->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($employees as $employee) {
            $matching = $policies
                ->filter(fn (ScopePolicy $policy) => $this->policyMatchesEmployee($policy, $employee))
                ->values();

            if ($matching->isEmpty()) {
                continue;
            }

            $winner = $matching->sort(function (ScopePolicy $a, ScopePolicy $b) {
                $aRank = $this->policyMatchRank($a->match_type);
                $bRank = $this->policyMatchRank($b->match_type);

                if ($aRank !== $bRank) {
                    return $bRank <=> $aRank;
                }

                if ((int) $a->priority !== (int) $b->priority) {
                    return ((int) $b->priority) <=> ((int) $a->priority);
                }

                return $b->id <=> $a->id;
            })->first();

            if (! $winner) {
                continue;
            }

            $actorRecord = $winner->actors->firstWhere('actor_employee_id', $actorEmployee->id);
            if (! $actorRecord) {
                continue;
            }

            $canFill = (bool) $actorRecord->can_fill;
            $canApprove = (bool) $actorRecord->can_approve;
            $canRevise = (bool) $actorRecord->can_revise;

            if (! $canFill && ! $canApprove && ! $canRevise) {
                continue;
            }

            $result[(int) $employee->id] = [
                'policy_id' => (int) $winner->id,
                'can_fill' => $canFill,
                'can_approve' => $canApprove,
                'can_revise' => $canRevise,
            ];
        }

        if ($context === 'time_sheet') {
            // Match the legacy ownership rule: an employee assigned to another
            // manager's active scope is not visible unless this actor explicitly
            // owns that employee through an employee-type policy.
            $explicitlyAllowedIds = [];
            $disallowedIds = [(int) $actorEmployee->id];
            foreach ($policies as $policy) {
                $actorRecord = $policy->actors->firstWhere('actor_employee_id', $actorEmployee->id);
                if ($actorRecord && $policy->match_type === ScopePolicy::MATCH_EMPLOYEE) {
                    $explicitlyAllowedIds = array_merge($explicitlyAllowedIds, $this->matchedEmployeeIdsForPolicy($policy));
                }

                foreach ($policy->actors as $policyActor) {
                    if ((int) $policyActor->actor_employee_id === (int) $actorEmployee->id) {
                        continue;
                    }

                    $disallowedIds[] = (int) $policyActor->actor_employee_id;
                    foreach ($employees as $employee) {
                        if ($this->policyMatchesEmployee($policy, $employee)) {
                            $disallowedIds[] = (int) $employee->id;
                        }
                    }
                }
            }

            $explicitlyAllowedLookup = array_fill_keys(array_map('intval', $explicitlyAllowedIds), true);
            foreach (array_unique(array_map('intval', $disallowedIds)) as $employeeId) {
                if (! isset($explicitlyAllowedLookup[$employeeId])) {
                    unset($result[$employeeId]);
                }
            }
        }

        ksort($result);

        return $result;
    }

    public function resolveVisibleEmployeeIds(
        User $user,
        string $context = 'time_sheet',
        ?int $selectedPolicyId = null
    ): Collection {
        return collect(array_keys($this->resolveEmployeeAccessMap($user, $context, $selectedPolicyId)))
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @return Builder<Employee>
     */
    public function managedEmployeesQuery(
        User $user,
        string $context = 'time_sheet',
        ?int $selectedPolicyId = null
    ): Builder {
        $ids = $this->resolveVisibleEmployeeIds($user, $context, $selectedPolicyId);
        if ($ids->isEmpty()) {
            return Employee::query()->whereRaw('0 = 1');
        }

        return Employee::query()->whereIn('id', $ids->all());
    }

    public function selectablePolicies(User $user, string $context = 'time_sheet'): Collection
    {
        $actorEmployee = $this->actorResolver->resolveEmployee($user);
        if (! $actorEmployee) {
            return collect();
        }

        $policyIds = ScopePolicyActor::query()
            ->where('actor_employee_id', $actorEmployee->id)
            ->where(function ($query) {
                $query->where('can_fill', true)
                    ->orWhere('can_approve', true)
                    ->orWhere('can_revise', true);
            })
            ->whereHas('policy', function ($query) use ($context) {
                $query->where('context', $context)
                    ->where('is_active', true);
            })
            ->orderBy('id')
            ->pluck('policy_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($policyIds->isEmpty()) {
            return collect();
        }

        $policiesById = ScopePolicy::query()
            ->whereIn('id', $policyIds->all())
            ->with('actors')
            ->get()
            ->keyBy('id');

        return $policyIds
            ->map(fn (int $policyId) => $policiesById->get($policyId))
            ->filter()
            ->values();
    }

    /**
     * The same policies as {@see selectablePolicies}, reduced to what a picker needs.
     * A policy saved without a name still has to be choosable, so it falls back to
     * its id rather than rendering as a blank option.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function selectablePolicyOptions(User $user, string $context = 'time_sheet'): Collection
    {
        return $this->selectablePolicies($user, $context)
            ->map(function (ScopePolicy $policy): array {
                $name = trim((string) ($policy->name ?? ''));

                return [
                    'id' => (int) $policy->id,
                    'name' => $name === '' ? 'Scope #'.$policy->id : $name,
                ];
            })
            ->values();
    }

    public function resolveSelectedPolicyId(
        User $user,
        string $context = 'time_sheet',
        ?int $requestedPolicyId = null
    ): ?int {
        $selectablePolicies = $this->selectablePolicies($user, $context)->values();
        if ($selectablePolicies->isEmpty()) {
            return null;
        }

        if (! is_null($requestedPolicyId) && $requestedPolicyId > 0) {
            $requested = $selectablePolicies->firstWhere('id', (int) $requestedPolicyId);
            if ($requested) {
                return (int) $requested->id;
            }
        }

        return (int) $selectablePolicies->first()->id;
    }

    private function matchedEmployeeIdsForPolicy(ScopePolicy $policy): array
    {
        $query = Employee::query()->whereNull('archived_at');

        $settings = is_array($policy->settings) ? $policy->settings : [];
        $jobTitle = trim((string) ($settings['job_title'] ?? ''));
        if ($jobTitle !== '') {
            $query->where('job', 'like', $jobTitle);
        }

        switch ($policy->match_type) {
            case ScopePolicy::MATCH_GLOBAL:
                break;

            case ScopePolicy::MATCH_LOCATION:
                if ($policy->location_id) {
                    $query->where('location_id', $policy->location_id);
                } else {
                    return [];
                }
                break;

            case ScopePolicy::MATCH_DEPARTMENT:
                if ($policy->location_id) {
                    $query->where('location_id', $policy->location_id);
                }
                if ($policy->department_id) {
                    $query->where('department_id', $policy->department_id);
                } else {
                    return [];
                }
                break;

            case ScopePolicy::MATCH_CENTER:
                if ($policy->center_id) {
                    $query->where('center_id', $policy->center_id);
                } else {
                    return [];
                }
                break;

            case ScopePolicy::MATCH_EMPLOYEE:
                $targetIds = collect($policy->target_employee_ids ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values();

                if ($targetIds->isEmpty()) {
                    return [];
                }
                $query->whereIn('id', $targetIds->all());
                break;

            default:
                return [];
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function policyMatchesEmployee(ScopePolicy $policy, Employee $employee): bool
    {
        if ($employee->archived_at) {
            return false;
        }

        $settings = is_array($policy->settings) ? $policy->settings : [];
        $jobTitle = trim((string) ($settings['job_title'] ?? ''));
        if ($jobTitle !== '' && trim((string) $employee->job) !== $jobTitle) {
            return false;
        }

        switch ($policy->match_type) {
            case ScopePolicy::MATCH_GLOBAL:
                return true;

            case ScopePolicy::MATCH_LOCATION:
                return (int) $policy->location_id === (int) $employee->location_id;

            case ScopePolicy::MATCH_DEPARTMENT:
                if ($policy->location_id && (int) $policy->location_id !== (int) $employee->location_id) {
                    return false;
                }

                return (int) $policy->department_id === (int) $employee->department_id;

            case ScopePolicy::MATCH_CENTER:
                return (int) $policy->center_id === (int) $employee->center_id;

            case ScopePolicy::MATCH_EMPLOYEE:
                $targetIds = collect($policy->target_employee_ids ?? [])->map(fn ($id) => (int) $id);

                return $targetIds->contains((int) $employee->id);

            default:
                return false;
        }
    }

    private function policyMatchRank(string $matchType): int
    {
        return match ($matchType) {
            ScopePolicy::MATCH_GLOBAL => 1,
            ScopePolicy::MATCH_LOCATION => 2,
            ScopePolicy::MATCH_DEPARTMENT => 3,
            ScopePolicy::MATCH_CENTER => 4,
            ScopePolicy::MATCH_EMPLOYEE => 5,
            default => 0,
        };
    }
}
