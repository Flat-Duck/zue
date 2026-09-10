<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The HR profile lives in `employee_details`, apart from `employees`, so that the
 * queries this system runs constantly do not read salaries, bank accounts and
 * national ID numbers they will never show. These tests hold that boundary.
 */
class EmployeeProfileStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['email' => 'admin@admin.com']));
        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function listing_employees_never_touches_the_profile_table(): void
    {
        Employee::factory()
            ->count(5)
            ->withProfile(['basic_salary' => 9999, 'social_security_number' => '123-45-6789'])
            ->create();

        $statements = [];
        DB::listen(static function ($query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        $this->get(route('employees.index'))->assertOk();

        $touched = array_values(array_filter(
            $statements,
            static fn (string $sql): bool => str_contains($sql, 'employee_details')
        ));

        $this->assertSame([], $touched, 'The employee list read the HR profile table.');
    }

    #[Test]
    public function a_listed_employee_carries_no_salary_or_national_id(): void
    {
        Employee::factory()
            ->withProfile(['basic_salary' => 9999, 'social_security_number' => '123-45-6789'])
            ->create();

        $listed = Employee::query()->first()->toArray();

        $this->assertArrayNotHasKey('basic_salary', $listed);
        $this->assertArrayNotHasKey('social_security_number', $listed);
        $this->assertArrayNotHasKey('bank_account_number', $listed);
        $this->assertArrayNotHasKey('id_card', $listed);
    }

    /**
     * The split is only tolerable if reading a profile field still reads naturally.
     */
    #[Test]
    public function a_profile_field_is_still_readable_through_the_employee(): void
    {
        $employee = Employee::factory()
            ->withProfile(['nationality' => 'ليبي', 'basic_salary' => 5797])
            ->create();

        $this->assertSame('ليبي', $employee->fresh()->nationality);
        $this->assertSame('5797.00', $employee->fresh()->basic_salary);
    }

    #[Test]
    public function an_employee_with_no_profile_reads_as_empty_rather_than_failing(): void
    {
        $employee = Employee::factory()->create();
        $employee->details()->delete();
        $employee->unsetRelation('details');

        $this->assertNull($employee->nationality);
        $this->assertNull($employee->basic_salary);
    }

    #[Test]
    public function saving_a_flat_set_of_fields_splits_them_across_both_tables(): void
    {
        $employee = Employee::factory()->create();

        $employee->saveProfile([
            'english_name' => 'AHMED SALEM',
            'nationality' => 'ليبي',
            'basic_salary' => 5797,
        ]);

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'english_name' => 'AHMED SALEM']);
        $this->assertDatabaseHas('employee_details', [
            'employee_id' => $employee->id,
            'nationality' => 'ليبي',
            'basic_salary' => 5797,
        ]);
    }

    /**
     * A form that posts a handful of fields must not blank out the rest of someone's
     * record.
     */
    #[Test]
    public function saving_part_of_a_profile_leaves_the_rest_alone(): void
    {
        $employee = Employee::factory()
            ->withProfile(['nationality' => 'ليبي', 'bank_name' => 'مصرف الوحدة'])
            ->create();

        $employee->saveProfile(['basic_salary' => 100]);

        $details = $employee->fresh()->details;

        $this->assertSame('ليبي', $details->nationality);
        $this->assertSame('مصرف الوحدة', $details->bank_name);
        $this->assertSame('100.00', $details->basic_salary);
    }

    #[Test]
    public function deleting_an_employee_takes_their_profile_with_them(): void
    {
        $employee = Employee::factory()->create();
        $detailId = $employee->details->id;

        $employee->forceDelete();

        $this->assertDatabaseMissing('employee_details', ['id' => $detailId]);
    }

    #[Test]
    public function an_employee_has_at_most_one_profile(): void
    {
        $employee = Employee::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        EmployeeDetail::query()->forceCreate(['employee_id' => $employee->id]);
    }

    /**
     * Appending an accessor that walks a relation means every serialized employee
     * fetches their own department, administration, location and centre — 36 extra
     * queries for ten rows, when this was measured. The accessors stay; a caller
     * that wants a department name asks for it, and eager-loads if it is in a list.
     */
    #[Test]
    public function serialising_employees_costs_no_extra_queries(): void
    {
        Employee::factory()->count(10)->create();

        $employees = Employee::query()->get();

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        $employees->toArray();

        $this->assertSame(0, $queries, 'Serialising employees ran queries of its own.');
    }

    #[Test]
    public function the_relation_names_are_still_readable_on_an_employee(): void
    {
        $employee = Employee::factory()->create();

        $this->assertSame($employee->department->name, $employee->department_name);
        $this->assertSame($employee->location->name, $employee->location_name);
        $this->assertSame($employee->center->name, $employee->center_name);
    }
}
