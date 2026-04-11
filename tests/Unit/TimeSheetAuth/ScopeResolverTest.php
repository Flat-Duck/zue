<?php

namespace Tests\Unit\TimeSheetAuth;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Models\User;
use App\Services\TimeSheetAuth\ScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_priority_winner_hides_employee_owned_by_another_policy(): void
    {
        $resolver = app(ScopeResolver::class);

        $location = Location::factory()->create();
        $department = Department::factory()->create();
        $center = Center::factory()->create();

        $actorUser = User::factory()->create(['number' => 8101]);
        $actorEmployee = Employee::factory()->create([
            'user_id' => $actorUser->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $otherManager = Employee::factory()->create([
            'user_id' => null,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $targetA = Employee::factory()->create([
            'user_id' => null,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);
        $targetB = Employee::factory()->create([
            'user_id' => null,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $locationPolicy = ScopePolicy::query()->create([
            'name' => 'Location',
            'context' => 'time_sheet',
            'match_type' => ScopePolicy::MATCH_LOCATION,
            'location_id' => $location->id,
            'department_id' => null,
            'center_id' => null,
            'target_employee_ids' => null,
            'priority' => 0,
            'is_active' => true,
            'settings' => null,
        ]);

        $employeePolicy = ScopePolicy::query()->create([
            'name' => 'Specific Employee',
            'context' => 'time_sheet',
            'match_type' => ScopePolicy::MATCH_EMPLOYEE,
            'location_id' => null,
            'department_id' => null,
            'center_id' => null,
            'target_employee_ids' => [$targetB->id],
            'priority' => 0,
            'is_active' => true,
            'settings' => null,
        ]);

        ScopePolicyActor::query()->create([
            'policy_id' => $locationPolicy->id,
            'actor_employee_id' => $actorEmployee->id,
            'can_fill' => true,
            'can_approve' => true,
            'can_revise' => false,
            'role_hint' => null,
        ]);

        ScopePolicyActor::query()->create([
            'policy_id' => $employeePolicy->id,
            'actor_employee_id' => $otherManager->id,
            'can_fill' => true,
            'can_approve' => true,
            'can_revise' => false,
            'role_hint' => null,
        ]);

        $visibleIds = $resolver->resolveVisibleEmployeeIds($actorUser, 'time_sheet')->all();

        $this->assertContains($targetA->id, $visibleIds);
        $this->assertNotContains($targetB->id, $visibleIds);
    }

    public function test_it_defaults_to_first_assigned_scope_and_filters_by_selected_scope(): void
    {
        $resolver = app(ScopeResolver::class);

        $location = Location::factory()->create();
        $department = Department::factory()->create();
        $center = Center::factory()->create();

        $actorUser = User::factory()->create(['number' => 8201]);
        $actorEmployee = Employee::factory()->create([
            'user_id' => $actorUser->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $targetA = Employee::factory()->create([
            'user_id' => null,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);
        $targetB = Employee::factory()->create([
            'user_id' => null,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $scope1 = ScopePolicy::query()->create([
            'name' => 'Scope One',
            'context' => 'time_sheet',
            'match_type' => ScopePolicy::MATCH_EMPLOYEE,
            'target_employee_ids' => [$targetA->id],
            'priority' => 0,
            'is_active' => true,
            'settings' => null,
        ]);

        $scope2 = ScopePolicy::query()->create([
            'name' => 'Scope Two',
            'context' => 'time_sheet',
            'match_type' => ScopePolicy::MATCH_EMPLOYEE,
            'target_employee_ids' => [$targetB->id],
            'priority' => 0,
            'is_active' => true,
            'settings' => null,
        ]);

        ScopePolicyActor::query()->create([
            'policy_id' => $scope1->id,
            'actor_employee_id' => $actorEmployee->id,
            'can_fill' => true,
            'can_approve' => true,
            'can_revise' => false,
            'role_hint' => null,
        ]);

        ScopePolicyActor::query()->create([
            'policy_id' => $scope2->id,
            'actor_employee_id' => $actorEmployee->id,
            'can_fill' => true,
            'can_approve' => true,
            'can_revise' => false,
            'role_hint' => null,
        ]);

        $defaultSelected = $resolver->resolveSelectedPolicyId($actorUser, 'time_sheet', null);
        $this->assertSame($scope1->id, $defaultSelected);

        $scope1Visible = $resolver->resolveVisibleEmployeeIds($actorUser, 'time_sheet', $scope1->id)->all();
        $scope2Visible = $resolver->resolveVisibleEmployeeIds($actorUser, 'time_sheet', $scope2->id)->all();

        $this->assertSame([$targetA->id], $scope1Visible);
        $this->assertSame([$targetB->id], $scope2Visible);
    }
}
