<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ManagementScope;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Models\User;
use App\Services\ManagementScopeService;
use App\Services\TimeSheetAuth\ScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationIdentityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Identity runs through `users.employee_id`, not through matching ids.
     *
     * The legacy design forced `users.id` to equal the employee number, so a
     * changed number rewrote the primary key. The number now lives only on the
     * employee, and the user's id is an ordinary surrogate that never moves.
     */
    public function test_user_identity_runs_through_the_employee_link(): void
    {
        $user = User::factory()->forEmployeeNumber(70001)->create();
        $employee = $user->employee;

        $this->assertSame($employee->id, $user->employee_id);
        $this->assertSame(70001, $user->number, 'The number is read from the employee.');

        $originalUserId = $user->id;

        // Changing the number is an employee-level edit and leaves the account alone.
        $employee->update(['number' => 70002]);

        $this->assertSame($originalUserId, $user->fresh()->id);
        $this->assertSame(70002, $user->fresh()->number);
    }

    public function test_management_scope_writes_authoritative_policy_and_deactivates_it_on_delete(): void
    {
        $manager = Employee::factory()->create();
        $service = app(ManagementScopeService::class);

        $service->createScopes([
            'manager_ids' => [$manager->id],
            'scope_type' => ManagementScope::TYPE_GLOBAL,
            'context' => 'time_sheet',
            'name' => 'All staff',
        ]);

        $scope = ManagementScope::query()->latest('id')->firstOrFail();
        $policy = ScopePolicy::query()
            ->where('settings->legacy_scope_id', $scope->id)
            ->firstOrFail();

        $this->assertTrue($policy->is_active);
        $this->assertDatabaseHas('scope_policy_actors', [
            'policy_id' => $policy->id,
            'actor_employee_id' => $manager->id,
            'can_fill' => 1,
            'can_approve' => 1,
            'can_revise' => 1,
        ]);

        $service->deleteScope($scope);

        $this->assertDatabaseMissing('management_scopes', ['id' => $scope->id]);
        $this->assertFalse($policy->fresh()->is_active);
        $this->assertSame(1, ScopePolicyActor::query()->where('policy_id', $policy->id)->count());
    }

    public function test_v2_scope_resolution_matches_legacy_scope_for_a_representative_manager(): void
    {
        $user = User::factory()->forEmployeeNumber(70003)->create();
        $manager = $user->employee;
        $target = Employee::factory()->create();
        $service = app(ManagementScopeService::class);

        $service->createScopes([
            'manager_ids' => [$manager->id],
            'scope_type' => ManagementScope::TYPE_GLOBAL,
            'context' => 'time_sheet',
        ]);

        $legacyIds = $manager->managedEmployeesQuery('time_sheet')->pluck('id')->sort()->values()->all();
        $v2Ids = app(ScopeResolver::class)
            ->resolveVisibleEmployeeIds($user, 'time_sheet')
            ->sort()
            ->values()
            ->all();

        $this->assertContains($target->id, $legacyIds);
        $this->assertSame($legacyIds, $v2Ids);
    }
}
