<?php

namespace Tests\Unit;

use App\Helpers\TimeSheetBuilder;
use App\Models\Employee;
use App\Models\TimeSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSheetBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_balance_to_date_excludes_the_cutoff_day(): void
    {
        $employee = Employee::factory()->create([
            'schedule' => '1/1',
            'transfered_balance' => 0,
        ]);

        TimeSheet::factory()->create([
            'employee_id' => $employee->id,
            'day' => '2026-01-01',
            'value' => 'A',
        ]);

        TimeSheet::factory()->create([
            'employee_id' => $employee->id,
            'day' => '2026-01-02',
            'value' => 'A',
        ]);

        $this->assertSame(1, (int) TimeSheetBuilder::calculateBalanceToDate($employee->id, '1/1', '2026-01-02'));
    }

    public function test_unapproved_timesheet_level_is_scoped_to_requested_employee(): void
    {
        $targetEmployee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();
        $approver = Employee::factory()->create();

        TimeSheet::factory()->create([
            'employee_id' => $targetEmployee->id,
            'timekeeper_id' => $approver->id,
            'supervisor_id' => $approver->id,
            'superintendent_id' => $approver->id,
        ]);

        TimeSheet::factory()->create([
            'employee_id' => $otherEmployee->id,
            'timekeeper_id' => null,
            'supervisor_id' => null,
            'superintendent_id' => null,
        ]);

        $this->assertSame(0, TimeSheetBuilder::unApprovedTimeSheetLevel($targetEmployee->id));
    }

    /**
     * The leave balance is what the whole office reads off an employee's record, and
     * it had no test of its own. The formula is: days worked, scaled by the on/off
     * ratio of the rotation, minus days taken off, plus whatever was carried over.
     */
    public function test_the_balance_is_worked_days_scaled_by_the_rotation_less_days_off(): void
    {
        $employee = Employee::factory()->create(['schedule' => '14/14', 'transfered_balance' => 0]);

        $this->giveDays($employee, 'A', 20);
        $this->giveDays($employee, 'F', 6, startDay: 21);

        // 20 worked at 14/14 earns 20 days, less 6 taken.
        $this->assertSame(14.0, (float) TimeSheetBuilder::calculateBalance($employee->id, '14/14'));
    }

    public function test_a_longer_rotation_earns_proportionally_less_leave(): void
    {
        $employee = Employee::factory()->create(['schedule' => '40/80']);

        $this->giveDays($employee, 'A', 20);

        // 40 on, 80 off: each day worked earns half a day.
        $this->assertSame(10.0, (float) TimeSheetBuilder::calculateBalance($employee->id, '40/80'));
    }

    public function test_every_kind_of_worked_day_counts_and_every_kind_of_day_off_deducts(): void
    {
        $employee = Employee::factory()->create(['schedule' => '1/1']);

        $day = 1;
        foreach (['A', 'B', 'K', 'Y'] as $worked) {
            $this->giveDays($employee, $worked, 1, startDay: $day++);
        }
        foreach (['F', 'X'] as $off) {
            $this->giveDays($employee, $off, 1, startDay: $day++);
        }

        $this->assertSame(2.0, (float) TimeSheetBuilder::calculateBalance($employee->id, '1/1'));
    }

    /**
     * Anything outside those two sets is neither worked nor taken — a day marked `P`
     * must not quietly earn or cost leave.
     */
    public function test_an_unrecognised_day_neither_earns_nor_deducts(): void
    {
        $employee = Employee::factory()->create(['schedule' => '1/1']);

        $this->giveDays($employee, 'P', 5);

        $this->assertSame(0.0, (float) TimeSheetBuilder::calculateBalance($employee->id, '1/1'));
    }

    public function test_the_carried_over_balance_is_added_on(): void
    {
        $employee = Employee::factory()->create(['schedule' => '1/1']);

        $this->giveDays($employee, 'A', 3);

        $this->assertSame(3.0, (float) TimeSheetBuilder::calculateBalance($employee->id, '1/1'));
        $this->assertSame(-97.0, (float) TimeSheetBuilder::calculateBalance($employee->id, '1/1', -100));
    }

    public function test_one_employees_days_never_count_towards_anothers_balance(): void
    {
        $employee = Employee::factory()->create(['schedule' => '1/1']);
        $colleague = Employee::factory()->create(['schedule' => '1/1']);

        $this->giveDays($employee, 'A', 4);
        $this->giveDays($colleague, 'A', 9);

        $this->assertSame(4.0, (float) TimeSheetBuilder::calculateBalance($employee->id, '1/1'));
    }

    /**
     * `calculateBalance` and `calculateBalanceToDate` must agree once the cutoff is
     * past every recorded day, or the balance shown on a report would differ from
     * the one stored on the employee.
     */
    public function test_the_running_balance_agrees_with_the_balance_to_a_later_date(): void
    {
        $employee = Employee::factory()->create(['schedule' => '14/14']);

        $this->giveDays($employee, 'A', 10);
        $this->giveDays($employee, 'F', 3, startDay: 11);

        $this->assertSame(
            (float) TimeSheetBuilder::calculateBalance($employee->id, '14/14', 5),
            (float) TimeSheetBuilder::calculateBalanceToDate($employee->id, '14/14', '2026-02-01', 5),
        );
    }

    /**
     * The employee's stored balance is what every screen reads, so recalculating has
     * to persist it.
     */
    public function test_recalculating_writes_the_balance_onto_the_employee(): void
    {
        $employee = Employee::factory()->create(['schedule' => '1/1', 'transfered_balance' => 2, 'total_balance' => 0]);

        $this->giveDays($employee, 'A', 6);

        $employee->calculateBalance();

        $this->assertSame(8, $employee->fresh()->total_balance);
    }

    private function giveDays(Employee $employee, string $value, int $count, int $startDay = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            TimeSheet::query()->create([
                'employee_id' => $employee->id,
                'value' => $value,
                'day' => sprintf('2026-01-%02d', $startDay + $i),
                'over_time' => 0,
            ]);
        }
    }
}
