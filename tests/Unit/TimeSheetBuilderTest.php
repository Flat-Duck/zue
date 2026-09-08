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
}
