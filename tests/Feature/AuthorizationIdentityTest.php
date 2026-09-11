<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Location;
use App\Models\ScopeContext;
use App\Models\ScopePolicyCriterion;
use App\Models\User;
use App\Services\ManagementScopes\ScopeWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsScopes;
use Tests\TestCase;

class AuthorizationIdentityTest extends TestCase
{
    use BuildsScopes;
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

    /**
     * Saving a scope replaces what it covers, rather than adding to it. Dropping
     * a field from the form has to drop it from the scope, or people keep seeing
     * employees the screen no longer says they can see.
     */
    public function test_saving_a_scope_replaces_what_it_covers(): void
    {
        $writer = app(ScopeWriter::class);

        $manager = Employee::factory()->create();
        $fieldA = Location::factory()->create();
        $fieldD = Location::factory()->create();

        $scope = $writer->create([
            'name' => 'Dispatcher',
            'context_id' => $this->scopeContext(ScopeContext::DISPATCHER)->id,
            'manager_ids' => [$manager->id],
            'field_ids' => [$fieldA->id, $fieldD->id],
        ]);

        $this->assertEqualsCanonicalizing(
            [$fieldA->id, $fieldD->id],
            $scope->valuesFor(ScopePolicyCriterion::FIELD)
        );

        $writer->update($scope, [
            'name' => 'Dispatcher',
            'context_id' => $scope->context_id,
            'manager_ids' => [$manager->id],
            'field_ids' => [$fieldD->id],
        ]);

        $this->assertSame([$fieldD->id], $scope->fresh()->load('criteria')->valuesFor(ScopePolicyCriterion::FIELD));
    }

    public function test_removing_a_manager_takes_their_access_away(): void
    {
        $writer = app(ScopeWriter::class);

        $kept = Employee::factory()->create();
        $removed = Employee::factory()->create();
        $target = Employee::factory()->create();

        $scope = $writer->create([
            'name' => 'Shared',
            'context_id' => $this->scopeContext()->id,
            'manager_ids' => [$kept->id, $removed->id],
            'employee_ids' => [$target->id],
        ]);

        $this->assertCount(2, $scope->actors);

        $writer->update($scope, [
            'name' => 'Shared',
            'context_id' => $scope->context_id,
            'manager_ids' => [$kept->id],
            'employee_ids' => [$target->id],
        ]);

        $this->assertSame(
            [$kept->id],
            $scope->fresh()->actors->pluck('actor_employee_id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_deleting_a_scope_takes_its_criteria_and_actors_with_it(): void
    {
        $writer = app(ScopeWriter::class);

        $scope = $writer->create([
            'name' => 'Temporary',
            'context_id' => $this->scopeContext()->id,
            'manager_ids' => [Employee::factory()->create()->id],
            'employee_ids' => [Employee::factory()->create()->id],
        ]);

        $writer->delete($scope);

        $this->assertDatabaseMissing('scope_policies', ['id' => $scope->id]);
        $this->assertDatabaseMissing('scope_policy_criteria', ['policy_id' => $scope->id]);
        $this->assertDatabaseMissing('scope_policy_actors', ['policy_id' => $scope->id]);
    }
}
