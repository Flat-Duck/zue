<?php

namespace App\Services\TimeSheetAuth;

use App\Models\Employee;
use App\Models\ScopeContext;
use App\Models\ScopePolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Who one person may see, in one context.
 *
 * The context is half the question. The same supervisor fills time sheets for
 * their own department and, as dispatcher, books travellers out of two fields —
 * two different answers for the same person, and nothing here may assume one
 * stands for the other.
 */
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
        string $context = ScopeContext::TIME_SHEET,
        ?int $selectedPolicyId = null
    ): array {
        $actorEmployee = $this->actorResolver->resolveEmployee($user);

        return $actorEmployee
            ? $this->accessMapForEmployee($actorEmployee, $context, $selectedPolicyId)
            : [];
    }

    /**
     * The same question asked of an employee rather than an account. Not everybody
     * who appears in a scope has a login, so the two are kept apart.
     *
     * @return array<int, array{policy_id:int,can_fill:bool,can_approve:bool,can_revise:bool}>
     */
    public function accessMapForEmployee(
        Employee $actorEmployee,
        string $context = ScopeContext::TIME_SHEET,
        ?int $selectedPolicyId = null
    ): array {
        $policies = $this->activePolicies($context);
        if ($policies->isEmpty()) {
            return [];
        }

        $mine = $this->policiesFor($policies, (int) $actorEmployee->id);

        if (! is_null($selectedPolicyId)) {
            $mine = $mine->where('id', (int) $selectedPolicyId)->values();
        }

        if ($mine->isEmpty()) {
            return [];
        }

        $result = [];

        foreach ($mine as $policy) {
            $capabilities = $this->capabilitiesOf($policy, (int) $actorEmployee->id);
            if ($capabilities === null) {
                continue;
            }

            $covered = $policy->coveredEmployeesQuery()->pluck('id');

            if ($policy->carves_out_managers) {
                $covered = $this->withoutEmployeesSignedForElsewhere(
                    $covered,
                    $policies,
                    $policy,
                    (int) $actorEmployee->id
                );
            }

            foreach ($covered as $employeeId) {
                $employeeId = (int) $employeeId;

                if ($employeeId === (int) $actorEmployee->id) {
                    continue;
                }

                // A more specific scope of my own wins, so the sheet says which
                // one a person is signed for under.
                if (isset($result[$employeeId])
                    && $this->specificity($policy) <= $this->specificity($mine->firstWhere('id', $result[$employeeId]['policy_id']))) {
                    continue;
                }

                $result[$employeeId] = ['policy_id' => (int) $policy->id] + $capabilities;
            }
        }

        ksort($result);

        return $result;
    }

    /**
     * @return Collection<int, ScopePolicy>
     */
    private function activePolicies(string $context): Collection
    {
        return ScopePolicy::query()
            ->where('is_active', true)
            ->whereHas('context', fn (Builder $query) => $query->where('key', $context)->where('is_active', true))
            ->with(['criteria', 'actors'])
            ->get();
    }

    /**
     * @param  Collection<int, ScopePolicy>  $policies
     * @return Collection<int, ScopePolicy>
     */
    private function policiesFor(Collection $policies, int $employeeId): Collection
    {
        return $policies
            ->filter(fn (ScopePolicy $policy): bool => $this->capabilitiesOf($policy, $employeeId) !== null)
            ->values();
    }

    /**
     * @return array{can_fill:bool,can_approve:bool,can_revise:bool}|null
     */
    private function capabilitiesOf(ScopePolicy $policy, int $employeeId): ?array
    {
        $actor = $policy->actors->firstWhere('actor_employee_id', $employeeId);

        if (! $actor) {
            return null;
        }

        $capabilities = [
            'can_fill' => (bool) $actor->can_fill,
            'can_approve' => (bool) $actor->can_approve,
            'can_revise' => (bool) $actor->can_revise,
        ];

        return in_array(true, $capabilities, true) ? $capabilities : null;
    }

    /**
     * How narrow a scope is, so the narrower of two of mine owns the employee.
     */
    private function specificity(?ScopePolicy $policy): int
    {
        if (! $policy) {
            return -1;
        }

        if ($policy->namedEmployeeIds() !== []) {
            return 100 + (int) $policy->priority;
        }

        return count($policy->filters()) * 10 + (int) $policy->priority;
    }

    /**
     * A pool of ordinary staff is exactly that. Two kinds of people drop out of it:
     *
     *  - anyone named on somebody else's scope, because that is where they are
     *    signed for;
     *  - anyone who is a manager themselves, on any scope in this context,
     *    including a co-manager of this one — a supervisor is not their own
     *    subordinate.
     *
     * Either exclusion is lifted when I am the one who named them, which is how a
     * superintendent comes to sign for the supervisors under him.
     *
     * Whether this applies at all is the scope's own decision. It is the rule that
     * makes the time sheet hierarchy work; a dispatcher booking a flight needs to
     * see supervisors too, because they fly like everybody else.
     *
     * @param  Collection<int, int>  $covered
     * @param  Collection<int, ScopePolicy>  $policies
     * @return Collection<int, int>
     */
    private function withoutEmployeesSignedForElsewhere(
        Collection $covered,
        Collection $policies,
        ScopePolicy $policy,
        int $actorId
    ): Collection {
        $namedByMe = [];
        $signedForElsewhere = [];

        foreach ($policies as $other) {
            $isMine = (bool) $other->actors->firstWhere('actor_employee_id', $actorId);

            foreach ($other->namedEmployeeIds() as $employeeId) {
                if ($isMine) {
                    $namedByMe[] = $employeeId;
                } else {
                    $signedForElsewhere[] = $employeeId;
                }
            }

            foreach ($other->actors as $actor) {
                if ((int) $actor->actor_employee_id !== $actorId) {
                    $signedForElsewhere[] = (int) $actor->actor_employee_id;
                }
            }
        }

        $namedByMe = array_fill_keys($namedByMe, true);
        $excluded = array_filter(
            array_unique($signedForElsewhere),
            fn (int $employeeId): bool => ! isset($namedByMe[$employeeId])
        );

        return $covered->reject(fn ($employeeId): bool => in_array((int) $employeeId, $excluded, true))->values();
    }

    public function resolveVisibleEmployeeIds(
        User $user,
        string $context = ScopeContext::TIME_SHEET,
        ?int $selectedPolicyId = null
    ): Collection {
        return collect(array_keys($this->resolveEmployeeAccessMap($user, $context, $selectedPolicyId)))
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    /**
     * @return Builder<Employee>
     */
    public function employeesManagedBy(
        Employee $actor,
        string $context = ScopeContext::TIME_SHEET,
        ?int $selectedPolicyId = null
    ): Builder {
        $ids = array_keys($this->accessMapForEmployee($actor, $context, $selectedPolicyId));

        if ($ids === []) {
            return Employee::query()->whereRaw('0 = 1');
        }

        return Employee::query()->whereIn('id', $ids);
    }

    /**
     * @return Builder<Employee>
     */
    public function managedEmployeesQuery(
        User $user,
        string $context = ScopeContext::TIME_SHEET,
        ?int $selectedPolicyId = null
    ): Builder {
        $ids = $this->resolveVisibleEmployeeIds($user, $context, $selectedPolicyId);

        if ($ids->isEmpty()) {
            return Employee::query()->whereRaw('0 = 1');
        }

        return Employee::query()->whereIn('id', $ids->all());
    }

    /**
     * @return Collection<int, ScopePolicy>
     */
    public function selectablePolicies(User $user, string $context = ScopeContext::TIME_SHEET): Collection
    {
        $actorEmployee = $this->actorResolver->resolveEmployee($user);

        if (! $actorEmployee) {
            return collect();
        }

        return $this->policiesFor($this->activePolicies($context), (int) $actorEmployee->id)
            ->sortBy('id')
            ->values();
    }

    /**
     * The same policies as {@see selectablePolicies}, reduced to what a picker needs.
     * A policy saved without a name still has to be choosable, so it falls back to
     * its id rather than rendering as a blank option.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function selectablePolicyOptions(User $user, string $context = ScopeContext::TIME_SHEET): Collection
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
        string $context = ScopeContext::TIME_SHEET,
        ?int $requestedPolicyId = null
    ): ?int {
        $selectable = $this->selectablePolicies($user, $context);

        if ($selectable->isEmpty()) {
            return null;
        }

        if (! is_null($requestedPolicyId) && $requestedPolicyId > 0) {
            $requested = $selectable->firstWhere('id', (int) $requestedPolicyId);

            if ($requested) {
                return (int) $requested->id;
            }
        }

        return (int) $selectable->first()->id;
    }
}
