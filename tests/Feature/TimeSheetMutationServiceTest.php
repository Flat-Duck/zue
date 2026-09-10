<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use App\Services\TimeSheetMutationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TimeSheetMutationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_create_for_employee_normalizes_value_and_is_idempotent_for_employee_day(): void
    {
        $service = app(TimeSheetMutationService::class);
        $actor = User::factory()->create();
        $employee = Employee::factory()->create(['schedule' => '5/5']);

        $first = $service->createForEmployee($employee, '2026-01-10', 'a', 2, $actor);
        $second = $service->createForEmployee($employee, '2026-01-10', 'B', 4, $actor);

        $this->assertTrue($first->is($second));
        $this->assertSame('A', $first->fresh()->value);
        $this->assertSame(1, TimeSheet::query()->where('employee_id', $employee->id)->where('day', '2026-01-10')->count());
    }

    public function test_invalid_schedule_blocks_timesheet_creation_before_balance_recalculation(): void
    {
        $service = app(TimeSheetMutationService::class);
        $employee = Employee::factory()->create(['schedule' => '5/0']);

        $this->expectException(ValidationException::class);

        $service->createForEmployee($employee, '2026-01-10', 'A', 2, User::factory()->create());
    }

    public function test_database_constraint_rejects_duplicate_employee_day_rows(): void
    {
        $employee = Employee::factory()->create(['schedule' => '5/5']);
        TimeSheet::factory()->create([
            'employee_id' => $employee->id,
            'day' => '2026-01-10',
        ]);

        $this->expectException(QueryException::class);

        TimeSheet::factory()->create([
            'employee_id' => $employee->id,
            'day' => '2026-01-10',
        ]);
    }

    public function test_revise_preserves_employee_id_and_records_audit_fields(): void
    {
        $service = app(TimeSheetMutationService::class);
        $actor = User::factory()->create();
        $employee = Employee::factory()->create(['schedule' => '5/5']);
        $otherEmployee = Employee::factory()->create(['schedule' => '5/5']);
        $timeSheet = TimeSheet::factory()->create([
            'employee_id' => $employee->id,
            'day' => '2026-01-10',
            'value' => 'A',
            'over_time' => 2,
        ]);

        $updated = $service->revise($timeSheet, '2026-01-11', 'b', 4, $actor);

        $this->assertSame($employee->id, $updated->fresh()->employee_id);
        $this->assertNotSame($otherEmployee->id, $updated->fresh()->employee_id);
        $this->assertSame('B', $updated->fresh()->value);
        $this->assertSame('A', $updated->fresh()->old_value);
        $this->assertSame($actor->id, $updated->fresh()->user_id);
        $this->assertNotNull($updated->fresh()->revised_at);
    }

    public function test_legacy_employee_approval_writes_actor_employee_id_not_user_id(): void
    {
        $service = app(TimeSheetMutationService::class);
        Role::create(['name' => 'timekeeper']);

        $actorUser = User::factory()->forEmployeeNumber(912345)->create();
        $actorUser->assignRole('timekeeper');

        // The actor's employee is the one their account is linked to.
        $actorEmployee = $actorUser->employee;
        $targetEmployee = Employee::factory()->create(['schedule' => '5/5']);

        TimeSheet::factory()->create([
            'employee_id' => $targetEmployee->id,
            'day' => '2026-01-10',
            'value' => 'A',
            'timekeeper_id' => null,
        ]);

        $updated = $service->approveEmployeeLegacy($actorUser, $targetEmployee, 'timekeeper');

        $this->assertSame(1, $updated);
        $this->assertDatabaseHas('time_sheets', [
            'employee_id' => $targetEmployee->id,
            'timekeeper_id' => $actorEmployee->id,
        ]);
    }
}
