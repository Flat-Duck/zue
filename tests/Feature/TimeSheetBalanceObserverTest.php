<?php

namespace Tests\Feature;

use App\Jobs\CalculateBalance;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Services\TimeSheetMutationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The leave balance is recalculated by an observer on the time sheet rather than by
 * hand from the service.
 *
 * Two things follow. A write that does not go through `TimeSheetMutationService` no
 * longer leaves the balance quietly wrong. And filling a range recalculates once
 * instead of once per day — thirty full passes over an employee's attendance
 * history, each of them inline on a `sync` queue.
 */
class TimeSheetBalanceObserverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function writing_a_day_outside_the_service_still_recalculates_the_balance(): void
    {
        Bus::fake();
        $employee = Employee::factory()->create(['schedule' => '14/14']);

        TimeSheet::query()->create([
            'employee_id' => $employee->id,
            'day' => '2026-03-01',
            'value' => 'A',
            'over_time' => 0,
        ]);

        Bus::assertDispatched(CalculateBalance::class);
    }

    #[Test]
    public function deleting_a_day_recalculates_the_balance(): void
    {
        $employee = Employee::factory()->create(['schedule' => '14/14']);
        $day = TimeSheet::query()->create([
            'employee_id' => $employee->id,
            'day' => '2026-03-01',
            'value' => 'A',
            'over_time' => 0,
        ]);

        Bus::fake();
        $day->delete();

        Bus::assertDispatched(CalculateBalance::class);
    }

    #[Test]
    public function filling_a_month_recalculates_the_balance_once_not_thirty_times(): void
    {
        Bus::fake();
        $employee = Employee::factory()->create(['schedule' => '14/14']);

        app(TimeSheetMutationService::class)
            ->fillDateOrRange($employee, '2026-03-01 to 2026-03-30', 'A', 0);

        Bus::assertDispatchedTimes(CalculateBalance::class, 1);
    }

    #[Test]
    public function deleting_a_range_recalculates_the_balance_once(): void
    {
        $employee = Employee::factory()->create(['schedule' => '14/14']);
        $service = app(TimeSheetMutationService::class);
        $service->fillDateOrRange($employee, '2026-03-01 to 2026-03-10', 'A', 0);

        Bus::fake();
        $service->deleteDateOrRange($employee, '2026-03-01 to 2026-03-10');

        Bus::assertDispatchedTimes(CalculateBalance::class, 1);
    }

    /**
     * The balance must actually end up right, not merely be scheduled.
     */
    #[Test]
    public function the_balance_is_correct_after_filling_a_range(): void
    {
        $employee = Employee::factory()->create(['schedule' => '14/14', 'transfered_balance' => 0]);

        app(TimeSheetMutationService::class)
            ->fillDateOrRange($employee, '2026-03-01 to 2026-03-10', 'A', 0);

        $this->assertSame(10, $employee->fresh()->total_balance);
    }
}
