<?php

namespace Tests\Unit\TimeSheetAuth;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\ScopeContext;
use App\Models\User;
use App\Services\TimeSheetAuth\ScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsScopes;
use Tests\Feature\ScopeVisibilityTest;
use Tests\TestCase;

/**
 * Choosing between the scopes one person holds.
 *
 * Someone with two scopes in the same context sees the union of both by default,
 * and one of them at a time when they pick one — which is how a time sheet gets
 * printed under the right heading. What each scope covers is settled in
 * {@see ScopeVisibilityTest}; this is about picking.
 */
class ScopeResolverTest extends TestCase
{
    use BuildsScopes;
    use RefreshDatabase;

    private Location $location;

    private Department $department;

    private Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::factory()->create();
        $this->department = Department::factory()->create();
        $this->center = Center::factory()->create();
    }

    private function employee(): Employee
    {
        return Employee::factory()->create([
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'center_id' => $this->center->id,
            'archived_at' => null,
        ]);
    }

    public function test_it_defaults_to_every_scope_and_filters_by_the_selected_one(): void
    {
        $resolver = app(ScopeResolver::class);

        $actorEmployee = $this->employee();
        $actorUser = User::factory()->forEmployee($actorEmployee)->create();

        $targetA = $this->employee();
        $targetB = $this->employee();

        $scopeOne = $this->buildScope('Scope One', ['employee' => [$targetA->id]], [$actorEmployee]);
        $scopeTwo = $this->buildScope('Scope Two', ['employee' => [$targetB->id]], [$actorEmployee]);

        $this->assertSame(
            $scopeOne->id,
            $resolver->resolveSelectedPolicyId($actorUser, ScopeContext::TIME_SHEET, null)
        );

        $this->assertEqualsCanonicalizing(
            [$targetA->id, $targetB->id],
            $resolver->resolveVisibleEmployeeIds($actorUser, ScopeContext::TIME_SHEET)->all(),
            'With no scope chosen, both of mine count.'
        );

        $this->assertSame(
            [$targetA->id],
            $resolver->resolveVisibleEmployeeIds($actorUser, ScopeContext::TIME_SHEET, $scopeOne->id)->all()
        );

        $this->assertSame(
            [$targetB->id],
            $resolver->resolveVisibleEmployeeIds($actorUser, ScopeContext::TIME_SHEET, $scopeTwo->id)->all()
        );
    }

    public function test_a_scope_that_is_not_mine_cannot_be_selected(): void
    {
        $resolver = app(ScopeResolver::class);

        $actorEmployee = $this->employee();
        $actorUser = User::factory()->forEmployee($actorEmployee)->create();
        $target = $this->employee();

        $mine = $this->buildScope('Mine', ['employee' => [$target->id]], [$actorEmployee]);
        $theirs = $this->buildScope('Theirs', ['employee' => [$target->id]], [$this->employee()]);

        $this->assertSame(
            $mine->id,
            $resolver->resolveSelectedPolicyId($actorUser, ScopeContext::TIME_SHEET, $theirs->id),
            'Asking for someone else\'s scope falls back to one of my own.'
        );

        $this->assertSame(
            [],
            $resolver->resolveVisibleEmployeeIds($actorUser, ScopeContext::TIME_SHEET, $theirs->id)->all()
        );
    }

    public function test_an_inactive_scope_is_not_offered_and_shows_nobody(): void
    {
        $resolver = app(ScopeResolver::class);

        $actorEmployee = $this->employee();
        $actorUser = User::factory()->forEmployee($actorEmployee)->create();
        $target = $this->employee();

        $scope = $this->buildScope('Retired', ['employee' => [$target->id]], [$actorEmployee]);
        $scope->update(['is_active' => false]);

        $this->assertSame([], $resolver->selectablePolicyOptions($actorUser)->all());
        $this->assertSame([], $resolver->resolveVisibleEmployeeIds($actorUser)->all());
    }

    public function test_a_scope_whose_context_is_switched_off_shows_nobody(): void
    {
        $resolver = app(ScopeResolver::class);

        $actorEmployee = $this->employee();
        $actorUser = User::factory()->forEmployee($actorEmployee)->create();
        $target = $this->employee();

        $this->buildScope('Dispatcher', ['employee' => [$target->id]], [$actorEmployee], ScopeContext::DISPATCHER);
        $this->scopeContext(ScopeContext::DISPATCHER)->update(['is_active' => false]);

        $this->assertSame(
            [],
            $resolver->resolveVisibleEmployeeIds($actorUser, ScopeContext::DISPATCHER)->all()
        );
    }

    public function test_an_actor_with_no_capabilities_is_not_a_manager(): void
    {
        $resolver = app(ScopeResolver::class);

        $actorEmployee = $this->employee();
        $actorUser = User::factory()->forEmployee($actorEmployee)->create();
        $target = $this->employee();

        $scope = $this->buildScope('Read nothing', ['employee' => [$target->id]]);
        $this->giveScopeTo($scope, $actorEmployee, canFill: false, canApprove: false, canRevise: false);

        $this->assertSame([], $resolver->resolveVisibleEmployeeIds($actorUser)->all());
        $this->assertSame([], $resolver->selectablePolicyOptions($actorUser)->all());
    }

    public function test_a_user_with_no_employee_record_manages_nobody(): void
    {
        $resolver = app(ScopeResolver::class);

        $this->buildGlobalScope([$this->employee()]);

        $this->assertSame([], $resolver->resolveVisibleEmployeeIds(User::factory()->create())->all());
    }
}
