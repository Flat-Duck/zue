<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\ScopeContext;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Models\ScopePolicyCriterion;
use App\Models\User;
use App\Services\TimeSheetAuth\ScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who a manager can see, at the shape the company actually uses.
 *
 * Two things make that shape awkward, and both were getting the wrong answer.
 * Scopes are shared — every real one has between two and eight managers — and the
 * dimensions are independent: a department such as Gas Plant has staff at two
 * different fields, and cost centres cut across both.
 *
 * The context is the other half. The same person is a supervisor for time sheets
 * and a dispatcher for flights, and those are different answers.
 */
class ScopeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Location $fieldA;

    private Location $fieldD;

    private Department $gasPlant;

    private Department $production;

    private Center $anyCentre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldA = Location::factory()->create(['name' => 'D001']);
        $this->fieldD = Location::factory()->create(['name' => 'D002']);
        $this->gasPlant = Department::factory()->create(['name' => 'Gas Plant']);
        $this->production = Department::factory()->create(['name' => 'PROD']);
        $this->anyCentre = Center::factory()->create(['name' => '5M99']);
    }

    private function staff(string $name, ?Location $field = null, ?Department $department = null, ?Center $center = null): Employee
    {
        return Employee::factory()->create([
            'english_name' => $name,
            'location_id' => ($field ?? $this->fieldA)->id,
            'department_id' => ($department ?? $this->gasPlant)->id,
            'center_id' => ($center ?? $this->anyCentre)->id,
            'archived_at' => null,
        ]);
    }

    private function context(string $key): ScopeContext
    {
        return ScopeContext::query()->firstOrCreate(
            ['key' => $key],
            ['name' => ucfirst($key), 'carves_out_managers' => $key === ScopeContext::TIME_SHEET, 'is_active' => true]
        );
    }

    /**
     * @param  array<string, list<int>>  $criteria  dimension => value ids
     * @param  list<Employee>  $actors
     */
    private function scope(
        string $name,
        array $criteria,
        array $actors,
        string $context = ScopeContext::TIME_SHEET,
        ?bool $carvesOut = null,
    ): ScopePolicy {
        $scopeContext = $this->context($context);

        $policy = ScopePolicy::query()->create([
            'name' => $name,
            'context_id' => $scopeContext->id,
            'covers_everyone' => false,
            'carves_out_managers' => $carvesOut ?? $scopeContext->carves_out_managers,
            'priority' => 0,
            'is_active' => true,
        ]);

        foreach ($criteria as $dimension => $values) {
            foreach ($values as $value) {
                ScopePolicyCriterion::query()->create([
                    'policy_id' => $policy->id,
                    'dimension' => $dimension,
                    'value_id' => $value,
                ]);
            }
        }

        foreach ($actors as $actor) {
            ScopePolicyActor::query()->create([
                'policy_id' => $policy->id,
                'actor_employee_id' => $actor->id,
                'can_fill' => true,
                'can_approve' => true,
                'can_revise' => true,
            ]);
        }

        return $policy->load(['criteria', 'actors']);
    }

    /**
     * @return list<int>
     */
    private function visibleTo(Employee $employee, string $context = ScopeContext::TIME_SHEET): array
    {
        // One account per person: asking the same employee about two contexts is
        // the whole point of these tests.
        $user = User::query()->firstWhere('employee_id', $employee->id)
            ?? User::factory()->forEmployee($employee)->create();

        return app(ScopeResolver::class)
            ->resolveVisibleEmployeeIds($user, $context)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  list<Employee>  $employees
     * @return list<int>
     */
    private function ids(array $employees): array
    {
        return collect($employees)->pluck('id')->sort()->values()->all();
    }

    // ---------------------------------------------------------------- dimensions

    /**
     * The dispatcher case, and the one the old shape could not express at all: a
     * single scope covering two fields.
     */
    #[Test]
    public function a_scope_can_cover_several_fields_at_once(): void
    {
        $dispatcher = $this->staff('DISPATCHER');

        $atA = $this->staff('AT 103A', $this->fieldA);
        $atD = $this->staff('AT 103D', $this->fieldD);
        $elsewhere = $this->staff('SOMEWHERE ELSE', Location::factory()->create(['name' => 'D099']));

        $this->scope(
            'Dispatcher, both fields',
            ['field' => [$this->fieldA->id, $this->fieldD->id]],
            [$dispatcher],
            ScopeContext::DISPATCHER,
        );

        $visible = $this->visibleTo($dispatcher, ScopeContext::DISPATCHER);

        $this->assertSame($this->ids([$atA, $atD]), $visible);
        $this->assertNotContains($elsewhere->id, $visible);
    }

    /**
     * Gas Plant has staff at both fields. A scope naming the department *and* a
     * field covers only the overlap — which is what all eleven department scopes
     * in use mean.
     */
    #[Test]
    public function dimensions_narrow_one_another(): void
    {
        $supervisor = $this->staff('SUPERVISOR');

        $gasPlantAtA = $this->staff('GAS PLANT 103A', $this->fieldA, $this->gasPlant);
        $gasPlantAtD = $this->staff('GAS PLANT 103D', $this->fieldD, $this->gasPlant);
        $productionAtA = $this->staff('PRODUCTION 103A', $this->fieldA, $this->production);

        $this->scope(
            'Gas Plant at 103A',
            ['field' => [$this->fieldA->id], 'department' => [$this->gasPlant->id]],
            [$supervisor],
        );

        $visible = $this->visibleTo($supervisor);

        $this->assertContains($gasPlantAtA->id, $visible);
        $this->assertNotContains($gasPlantAtD->id, $visible, 'Same department, other field.');
        $this->assertNotContains($productionAtA->id, $visible, 'Same field, other department.');
    }

    /**
     * Cost centre is a third, independent dimension.
     */
    #[Test]
    public function a_cost_centre_cuts_across_fields_and_departments(): void
    {
        $centre = Center::factory()->create(['name' => '5M32']);
        $other = Center::factory()->create(['name' => '5M42']);

        $accountant = $this->staff('ACCOUNTANT');

        $inCentreAtA = $this->staff('IN 5M32 AT 103A', $this->fieldA, $this->production, $centre);
        $inCentreAtD = $this->staff('IN 5M32 AT 103D', $this->fieldD, $this->gasPlant, $centre);
        $otherCentre = $this->staff('IN 5M42', $this->fieldA, $this->production, $other);

        $this->scope('Cost centre 5M32', ['center' => [$centre->id]], [$accountant]);

        $visible = $this->visibleTo($accountant);

        $this->assertSame($this->ids([$inCentreAtA, $inCentreAtD]), $visible);
        $this->assertNotContains($otherCentre->id, $visible);
    }

    #[Test]
    public function naming_people_adds_them_on_top_of_the_filters(): void
    {
        $supervisor = $this->staff('SUPERVISOR');

        $inDepartment = $this->staff('IN GAS PLANT', $this->fieldA, $this->gasPlant);
        $borrowed = $this->staff('BORROWED FROM PROD', $this->fieldD, $this->production);

        $this->scope(
            'Gas Plant at 103A, plus one',
            [
                'field' => [$this->fieldA->id],
                'department' => [$this->gasPlant->id],
                'employee' => [$borrowed->id],
            ],
            [$supervisor],
        );

        $this->assertSame($this->ids([$inDepartment, $borrowed]), $this->visibleTo($supervisor));
    }

    /**
     * A half-filled form must not quietly expose the company.
     */
    #[Test]
    public function a_scope_with_no_criteria_covers_nobody(): void
    {
        $manager = $this->staff('MANAGER');
        $this->staff('STAFF');

        $this->scope('Empty', [], [$manager]);

        $this->assertSame([], $this->visibleTo($manager));
    }

    // ------------------------------------------------------------------- context

    /**
     * The point of the whole design: one person, two contexts, two answers.
     */
    #[Test]
    public function the_same_person_sees_different_people_in_different_contexts(): void
    {
        $person = $this->staff('SUPERVISOR AND DISPATCHER', $this->fieldA, $this->production);

        $ownDepartment = $this->staff('PROD AT 103A', $this->fieldA, $this->production);
        $otherFieldGasPlant = $this->staff('GAS PLANT AT 103D', $this->fieldD, $this->gasPlant);

        $this->scope(
            'Their own department',
            ['field' => [$this->fieldA->id], 'department' => [$this->production->id]],
            [$person],
            ScopeContext::TIME_SHEET,
        );

        $this->scope(
            'Dispatcher, both fields',
            ['field' => [$this->fieldA->id, $this->fieldD->id]],
            [$person],
            ScopeContext::DISPATCHER,
        );

        $this->assertSame(
            $this->ids([$ownDepartment]),
            $this->visibleTo($person, ScopeContext::TIME_SHEET),
            'For time sheets they see only their own department at their own field.'
        );

        $this->assertSame(
            $this->ids([$ownDepartment, $otherFieldGasPlant]),
            $this->visibleTo($person, ScopeContext::DISPATCHER),
            'As dispatcher they see everyone across both fields.'
        );
    }

    #[Test]
    public function a_scope_in_another_context_is_invisible_to_this_one(): void
    {
        $dispatcher = $this->staff('DISPATCHER');
        $this->staff('TRAVELLER', $this->fieldD);

        $this->scope('Dispatcher', ['field' => [$this->fieldD->id]], [$dispatcher], ScopeContext::DISPATCHER);

        $this->assertSame([], $this->visibleTo($dispatcher, ScopeContext::TIME_SHEET));
    }

    // ----------------------------------------------------------------- sharing

    /**
     * A shared scope is not divided between its managers. Each of them signs for
     * the whole department.
     */
    #[Test]
    public function every_manager_on_a_shared_scope_sees_all_of_it(): void
    {
        $first = $this->staff('FIRST MANAGER');
        $second = $this->staff('SECOND MANAGER');
        $third = $this->staff('THIRD MANAGER');

        $staff = [$this->staff('ONE'), $this->staff('TWO'), $this->staff('THREE')];

        $this->scope('Gas Plant at 103A', ['field' => [$this->fieldA->id], 'department' => [$this->gasPlant->id]], [$first, $second, $third]);

        $expected = $this->ids($staff);

        $this->assertSame($expected, $this->visibleTo($first));
        $this->assertSame($expected, $this->visibleTo($second));
        $this->assertSame($expected, $this->visibleTo($third));
    }

    #[Test]
    public function a_manager_never_sees_themselves(): void
    {
        $manager = $this->staff('MANAGER');
        $this->staff('STAFF');

        $this->scope('Department', ['field' => [$this->fieldA->id], 'department' => [$this->gasPlant->id]], [$manager]);

        $this->assertNotContains($manager->id, $this->visibleTo($manager));
    }

    // ---------------------------------------------------------------- carve-outs

    #[Test]
    public function someone_named_on_another_managers_scope_leaves_my_pool(): void
    {
        $supervisor = $this->staff('SUPERVISOR');
        $superintendent = $this->staff('SUPERINTENDENT');

        $ordinary = $this->staff('ORDINARY');
        $carvedOut = $this->staff('CARVED OUT');

        $this->scope('Department', ['field' => [$this->fieldA->id], 'department' => [$this->gasPlant->id]], [$supervisor]);
        $this->scope('Superintendent', ['employee' => [$carvedOut->id]], [$superintendent]);

        $visible = $this->visibleTo($supervisor);

        $this->assertContains($ordinary->id, $visible);
        $this->assertNotContains($carvedOut->id, $visible, 'The superintendent signs for this one.');
    }

    #[Test]
    public function a_manager_sitting_in_my_pool_leaves_it(): void
    {
        $superintendent = $this->staff('SUPERINTENDENT');
        $crossManager = $this->staff('RUNS ANOTHER DEPARTMENT');
        $ordinary = $this->staff('ORDINARY');

        $this->scope('My department', ['field' => [$this->fieldA->id], 'department' => [$this->gasPlant->id]], [$superintendent]);
        $this->scope('Another department', ['field' => [$this->fieldD->id], 'department' => [$this->production->id]], [$crossManager]);

        $visible = $this->visibleTo($superintendent);

        $this->assertContains($ordinary->id, $visible);
        $this->assertNotContains($crossManager->id, $visible, 'A manager is not ordinary staff.');
    }

    #[Test]
    public function naming_a_manager_on_my_own_scope_brings_them_back(): void
    {
        $superintendent = $this->staff('SUPERINTENDENT');
        $supervisor = $this->staff('SUPERVISOR');

        $this->scope('Their own department', ['field' => [$this->fieldD->id], 'department' => [$this->production->id]], [$supervisor]);
        $this->scope('Superintendent', ['employee' => [$supervisor->id]], [$superintendent]);

        $this->assertContains($supervisor->id, $this->visibleTo($superintendent));
    }

    /**
     * The carve-out belongs to the scope, not to the whole application. A
     * dispatcher books supervisors onto flights like anybody else.
     */
    #[Test]
    public function a_scope_that_does_not_carve_out_keeps_the_managers(): void
    {
        $dispatcher = $this->staff('DISPATCHER');
        $supervisor = $this->staff('SUPERVISOR', $this->fieldD);
        $ordinary = $this->staff('ORDINARY', $this->fieldD);

        $this->scope('Their own department', ['field' => [$this->fieldD->id], 'department' => [$this->gasPlant->id]], [$supervisor]);
        $this->scope('Dispatcher', ['field' => [$this->fieldD->id]], [$dispatcher], ScopeContext::DISPATCHER);

        $visible = $this->visibleTo($dispatcher, ScopeContext::DISPATCHER);

        $this->assertSame($this->ids([$supervisor, $ordinary]), $visible);
    }

    #[Test]
    public function the_supervisor_layer_works_end_to_end(): void
    {
        $supervisorA = $this->staff('SUPERVISOR A');
        $supervisorB = $this->staff('SUPERVISOR B');
        $superintendent = $this->staff('SUPERINTENDENT');

        $staff = [$this->staff('ONE'), $this->staff('TWO')];

        $this->scope('Gas Plant at 103A', ['field' => [$this->fieldA->id], 'department' => [$this->gasPlant->id]], [$supervisorA, $supervisorB]);
        $this->scope('Superintendent', ['employee' => [$supervisorA->id, $supervisorB->id]], [$superintendent]);

        $this->assertSame($this->ids($staff), $this->visibleTo($supervisorA));
        $this->assertSame($this->ids($staff), $this->visibleTo($supervisorB));
        $this->assertSame($this->ids([$supervisorA, $supervisorB]), $this->visibleTo($superintendent));
    }
}
