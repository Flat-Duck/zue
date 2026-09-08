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

    public function test_user_employee_identity_uses_employee_user_id(): void
    {
        $user = User::factory()->create(['number' => 70001]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertSame($employee->id, $user->employee?->id);

        $user->update(['number' => 70002]);

        $this->assertSame(70001, $user->fresh()->id);
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
        $user = User::factory()->create(['number' => 70003]);
        $manager = Employee::factory()->create(['user_id' => $user->id]);
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
