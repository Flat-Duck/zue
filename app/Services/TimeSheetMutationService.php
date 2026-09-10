<?php

namespace App\Services;

use App\Helpers\MomentsJs;
use App\Jobs\CalculateBalance;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use App\Services\TimeSheetAuth\ActorResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeSheetMutationService
{
    private const APPROVAL_LEVELS = ['timekeeper', 'supervisor', 'fieldcoordinator', 'superintendent'];

    private const ATTENDANCE_VALUES = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    ];

    public function __construct(
        private readonly ActorResolver $actorResolver,
        private readonly TimeSheetService $timeSheetService,
    ) {}

    /**
     * @return array<int, string>
     */
    public static function attendanceValues(): array
    {
        return self::ATTENDANCE_VALUES;
    }

    public function createForEmployee(Employee $employee, string|Carbon $day, string $value, int $overTime, ?User $actor = null): TimeSheet
    {
        $actor ??= auth()->user();
        $this->assertValidSchedule($employee);
        $value = $this->normalizeAttendanceValue($value);
        $overTime = $this->normalizeOverTime($overTime);
        $day = $this->normalizeDay($day);

        return DB::transaction(function () use ($employee, $day, $value, $overTime, $actor): TimeSheet {
            $timeSheet = TimeSheet::query()
                ->where('employee_id', $employee->id)
                ->where('day', $day->toDateString())
                ->lockForUpdate()
                ->first();

            if ($timeSheet) {
                return $timeSheet;
            }

            $timeSheet = TimeSheet::query()->create([
                'employee_id' => $employee->id,
                'day' => $day->toDateString(),
                'value' => $value,
                'over_time' => $overTime,
                'user_id' => $actor?->id,
            ]);

            return $timeSheet;
        });
    }

    public function revise(TimeSheet $timeSheet, string|Carbon $day, string $value, int $overTime, ?User $actor = null): TimeSheet
    {
        $actor ??= auth()->user();
        $this->assertValidSchedule($timeSheet->employee);
        $value = $this->normalizeAttendanceValue($value);
        $overTime = $this->normalizeOverTime($overTime);
        $day = $this->normalizeDay($day);

        return DB::transaction(function () use ($timeSheet, $day, $value, $overTime, $actor): TimeSheet {
            $lockedTimeSheet = TimeSheet::query()
                ->whereKey($timeSheet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldValue = $lockedTimeSheet->value;

            $lockedTimeSheet->update([
                'day' => $day->toDateString(),
                'value' => $value,
                'over_time' => $overTime,
                'user_id' => $actor?->id,
                'revised_at' => now(),
                'old_value' => $oldValue,
            ]);

            return $lockedTimeSheet;
        });
    }

    public function delete(TimeSheet $timeSheet): void
    {
        DB::transaction(function () use ($timeSheet): void {
            $lockedTimeSheet = TimeSheet::query()
                ->whereKey($timeSheet->id)
                ->with('employee')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedTimeSheet->delete();
        });
    }

    /**
     * Filling a month used to recalculate the employee's balance once per day —
     * thirty times, each a full pass over their attendance history, and on a `sync`
     * queue every one of them inline. The observer is suppressed for the batch and
     * the balance is worked out once at the end.
     */
    public function fillDateOrRange(Employee $employee, string $dateOrRange, string $value, int $overTime, ?User $actor = null): int
    {
        $days = $this->daysFromDateOrRange($dateOrRange);

        $count = TimeSheet::withoutEvents(function () use ($employee, $days, $value, $overTime, $actor): int {
            $written = 0;

            foreach ($days as $day) {
                $this->createForEmployee($employee, $day, $value, $overTime, $actor);
                $written++;
            }

            return $written;
        });

        if ($count > 0) {
            $this->dispatchBalanceCalculation($employee);
        }

        return $count;
    }

    public function deleteDateOrRange(Employee $employee, string $dateOrRange): int
    {
        $days = $this->daysFromDateOrRange($dateOrRange);
        $deleted = 0;

        DB::transaction(function () use ($employee, $days, &$deleted): void {
            foreach ($days as $day) {
                $deleted += TimeSheet::query()
                    ->where('employee_id', $employee->id)
                    ->where('day', $day->toDateString())
                    ->delete();
            }
        });

        if ($deleted > 0) {
            $this->dispatchBalanceCalculation($employee);
        }

        return $deleted;
    }

    public function approveEmployeeLegacy(User $user, Employee $employee, string $level): int
    {
        $level = $this->normalizeApprovalLevel($level);
        $actor = $this->actorResolver->resolveEmployee($user);

        if (! $actor || ! $user->hasRole($level)) {
            abort(403);
        }

        return DB::transaction(function () use ($employee, $level, $actor): int {
            $query = TimeSheet::query()
                ->where('employee_id', $employee->id)
                ->where('created_at', '<=', now());

            if ($level === 'timekeeper') {
                return (int) $query
                    ->whereNull('timekeeper_id')
                    ->update(['timekeeper_id' => $this->actorEmployeeId($actor)]);
            }

            if ($level === 'supervisor') {
                return (int) $query
                    ->whereNotNull('timekeeper_id')
                    ->whereNull('supervisor_id')
                    ->update(['supervisor_id' => $this->actorEmployeeId($actor)]);
            }

            return (int) $query
                ->whereNotNull('supervisor_id')
                ->whereNull('superintendent_id')
                ->update(['superintendent_id' => $this->actorEmployeeId($actor)]);
        });
    }

    public function approveLegacy(User $user, int $month, int $year, string $level, iterable $managedEmployeeIds): int
    {
        $level = $this->normalizeApprovalLevel($level);
        $actor = $this->actorResolver->resolveEmployee($user);

        if (! $actor || ! $user->hasRole($level)) {
            abort(403);
        }

        $managedEmployeeIds = collect($managedEmployeeIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($managedEmployeeIds->isEmpty()) {
            return 0;
        }

        $workflow = $this->timeSheetService->getManagedApprovalBuckets($managedEmployeeIds);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $until = $from->copy()->addMonth();

        return DB::transaction(function () use ($level, $managedEmployeeIds, $workflow, $from, $until, $actor): int {
            $query = TimeSheet::query()
                ->whereIn('employee_id', $managedEmployeeIds)
                ->where('day', '>=', $from)
                ->where('day', '<', $until);

            if ($level === 'timekeeper') {
                return (int) (clone $query)
                    ->whereNull('timekeeper_id')
                    ->update(['timekeeper_id' => $this->actorEmployeeId($actor)]);
            }

            if ($level === 'supervisor') {
                if (empty($workflow['needs_supervisor_ids'])) {
                    return 0;
                }

                return (int) (clone $query)
                    ->whereIn('employee_id', $workflow['needs_supervisor_ids'])
                    ->whereNotNull('timekeeper_id')
                    ->whereNull('supervisor_id')
                    ->update(['supervisor_id' => $this->actorEmployeeId($actor)]);
            }

            if ($level === 'fieldcoordinator') {
                if (empty($workflow['needs_fieldcoordinator_ids'])) {
                    return 0;
                }

                return (int) (clone $query)
                    ->whereIn('employee_id', $workflow['needs_fieldcoordinator_ids'])
                    ->whereNull('superintendent_id')
                    ->where(function ($builder) use ($workflow): void {
                        if (! empty($workflow['A2'])) {
                            $builder->orWhere(function ($q) use ($workflow): void {
                                $q->whereIn('employee_id', $workflow['A2'])
                                    ->whereNotNull('timekeeper_id');
                            });
                        }

                        if (! empty($workflow['A3'])) {
                            $builder->orWhere(function ($q) use ($workflow): void {
                                $q->whereIn('employee_id', $workflow['A3'])
                                    ->whereNotNull('supervisor_id');
                            });
                        }
                    })
                    ->update(['superintendent_id' => $this->actorEmployeeId($actor)]);
            }

            if (empty($workflow['needs_superintendent_ids'])) {
                return 0;
            }

            return (int) (clone $query)
                ->whereIn('employee_id', $workflow['needs_superintendent_ids'])
                ->whereNull('superintendent_id')
                ->where(function ($builder) use ($workflow): void {
                    if (! empty($workflow['A1'])) {
                        $builder->orWhere(function ($q) use ($workflow): void {
                            $q->whereIn('employee_id', $workflow['A1'])
                                ->whereNotNull('timekeeper_id');
                        });
                    }

                    if (! empty($workflow['LEGACY'])) {
                        $builder->orWhere(function ($q) use ($workflow): void {
                            $q->whereIn('employee_id', $workflow['LEGACY'])
                                ->whereNotNull('supervisor_id');
                        });
                    }
                })
                ->update(['superintendent_id' => $this->actorEmployeeId($actor)]);
        });
    }

    /**
     * The employee behind a signed-in user.
     *
     * Approver columns record the person, not the login: they are foreign keys
     * to `employees`, and the legacy system stored an employee number in the
     * equivalent column.
     */
    private function actorEmployeeId(User|Employee $actor): int
    {
        // Callers reach this having already resolved the employee in some
        // paths and holding the signed-in user in others.
        return $actor instanceof Employee ? $actor->id : $actor->employee_id;
    }

    public function normalizeAttendanceValue(string $value): string
    {
        $value = strtoupper(trim($value));

        if (! in_array($value, self::ATTENDANCE_VALUES, true)) {
            throw ValidationException::withMessages([
                'value' => 'A valid attendance value is required.',
            ]);
        }

        return $value;
    }

    public function normalizeApprovalLevel(string $level): string
    {
        $level = strtolower(trim($level));

        if ($level === 'coordinator') {
            $level = 'fieldcoordinator';
        }

        if (! in_array($level, self::APPROVAL_LEVELS, true)) {
            throw ValidationException::withMessages([
                'level' => 'A valid approval level is required.',
            ]);
        }

        return $level;
    }

    private function assertValidSchedule(Employee $employee): void
    {
        $schedule = trim((string) $employee->schedule);

        if (! preg_match('/^\d+\/\d+$/', $schedule)) {
            throw ValidationException::withMessages([
                'schedule' => 'Employee schedule must use a valid work/off ratio.',
            ]);
        }

        [$workDays, $cycleDays] = array_map('intval', explode('/', $schedule));

        if ($workDays < 1 || $cycleDays < 1) {
            throw ValidationException::withMessages([
                'schedule' => 'Employee schedule must use a valid work/off ratio.',
            ]);
        }
    }

    private function normalizeOverTime(int $overTime): int
    {
        if ($overTime < 0 || $overTime > 24) {
            throw ValidationException::withMessages([
                'over_time' => 'Overtime must be between 0 and 24 hours.',
            ]);
        }

        return $overTime;
    }

    private function normalizeDay(string|Carbon $day): Carbon
    {
        return $day instanceof Carbon
            ? $day->copy()->startOfDay()
            : Carbon::parse($day)->startOfDay();
    }

    /**
     * @return array<int, Carbon>
     */
    private function daysFromDateOrRange(string $dateOrRange): array
    {
        if (trim($dateOrRange) === '') {
            throw ValidationException::withMessages([
                'range' => 'A valid date or date range is required.',
            ]);
        }

        if (! str_contains($dateOrRange, 'to')) {
            return [$this->normalizeDay($dateOrRange)];
        }

        return collect(MomentsJs::getRange($dateOrRange))
            ->map(fn ($day): Carbon => $this->normalizeDay((string) $day))
            ->values()
            ->all();
    }

    private function dispatchBalanceCalculation(Employee $employee): void
    {
        CalculateBalance::dispatch($employee)->afterCommit();
    }
}
