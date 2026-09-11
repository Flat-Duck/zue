<?php

namespace Tests;

use App\Models\Employee;
use App\Models\ScopeContext;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Models\ScopePolicyCriterion;

/**
 * Building a management scope in a test, the way the application builds one.
 *
 * A scope is a context, a set of criteria and the people who act under it, so
 * tests that only need "this person can see everybody" should not have to know
 * that shape by heart — and should not go stale when it changes again.
 */
trait BuildsScopes
{
    protected function scopeContext(string $key = ScopeContext::TIME_SHEET): ScopeContext
    {
        return ScopeContext::query()->firstOrCreate(
            ['key' => $key],
            [
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'carves_out_managers' => $key === ScopeContext::TIME_SHEET,
                'is_active' => true,
            ]
        );
    }

    /**
     * @param  array<string, list<int>>  $criteria  dimension => value ids
     * @param  list<Employee>  $actors
     */
    protected function buildScope(
        string $name,
        array $criteria = [],
        array $actors = [],
        string $context = ScopeContext::TIME_SHEET,
        bool $coversEveryone = false,
        ?bool $carvesOutManagers = null,
        array $attributes = [],
    ): ScopePolicy {
        $scopeContext = $this->scopeContext($context);

        $policy = ScopePolicy::query()->create($attributes + [
            'name' => $name,
            'context_id' => $scopeContext->id,
            'covers_everyone' => $coversEveryone,
            'carves_out_managers' => $carvesOutManagers ?? $scopeContext->carves_out_managers,
            'priority' => 0,
            'is_active' => true,
        ]);

        foreach ($criteria as $dimension => $values) {
            foreach ($values as $value) {
                ScopePolicyCriterion::query()->create([
                    'policy_id' => $policy->id,
                    'dimension' => $dimension,
                    'value_id' => (int) $value,
                ]);
            }
        }

        foreach ($actors as $actor) {
            $this->giveScopeTo($policy, $actor);
        }

        return $policy->load(['criteria', 'actors']);
    }

    /**
     * A scope covering the whole company, which is what a test that is not about
     * scoping usually wants.
     *
     * @param  list<Employee>  $actors
     */
    protected function buildGlobalScope(
        array $actors,
        string $context = ScopeContext::TIME_SHEET,
        ?bool $carvesOutManagers = false,
    ): ScopePolicy {
        return $this->buildScope(
            name: 'Global',
            actors: $actors,
            context: $context,
            coversEveryone: true,
            carvesOutManagers: $carvesOutManagers,
        );
    }

    protected function giveScopeTo(
        ScopePolicy $policy,
        Employee $employee,
        bool $canFill = true,
        bool $canApprove = true,
        bool $canRevise = true,
    ): ScopePolicyActor {
        return ScopePolicyActor::query()->updateOrCreate(
            ['policy_id' => $policy->id, 'actor_employee_id' => $employee->id],
            ['can_fill' => $canFill, 'can_approve' => $canApprove, 'can_revise' => $canRevise],
        );
    }
}
