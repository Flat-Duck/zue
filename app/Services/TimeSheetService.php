<?php

namespace App\Services;

use App\Helpers\MomentsJs;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TimeSheetService
{
    public function __construct(private readonly TimeSheetAuthorizationService $authorizationService) {}

    private const A3_DEPARTMENT_KEYS = [
        'gaspant',
        'gp',
        'production',
        'prod',
        'prodnc163',
        'lab',
        'generalmaintenance',
        'genmaint',
        'esp',
        'campboss',
        'camboss',
    ];

    private const EMPLOYEES_PER_PAGE = 8;

    private const A4_DEPARTMENT_KEYS = [
        'admin',
        'accounting',
        'transportation',
        'transport',
        'transp',
    ];

    /**
     * Everything the monthly approval sheet needs: the paginated employee
     * pages, who has signed each stage, and which stages the current user may
     * still approve.
     */
    public function getApprovalData(int $month, int $year, ?int $scopePolicyId = null): array
    {
        if (config('timesheet_auth.v2_read_enabled', false)) {
            return $this->authorizationService->buildApprovalData($month, $year, $scopePolicyId);
        }

        $managedEmployeeIds = auth()->user()->managedEmployeesQuery('time_sheet')->pluck('id');
        $workflow = $this->getManagedApprovalBuckets($managedEmployeeIds);

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $baseQuery = TimeSheet::whereIn('employee_id', $managedEmployeeIds)
            ->where('day', '>=', $from)
            ->where('day', '<', $from->copy()->addMonth())
            ->with(['employee.department.administration', 'employee.center']);

        $sheets = $baseQuery->get();
        $firstRow = $sheets->first();

        return [
            'chunks' => $this->paginateForPrinting($sheets->groupBy('employee_id')),
            'department' => $firstRow?->employee?->department?->name ?? '',
            'center' => $firstRow?->employee?->center?->name ?? '',
            'administration' => $firstRow?->employee?->department?->administration?->name ?? '',
            'month_name' => MomentsJs::getMonthsInYear()->get($month),
            'selected_month' => $month,
            'selected_year' => $year,
            'month_days' => Carbon::create($year, $month, 1)->daysInMonth,
            'employees' => Employee::pluck('english_name', 'number'),
            'signatures' => $this->collectStageSignatures($baseQuery, $workflow),
        ] + $this->resolveApprovalAvailability($baseQuery, $workflow);
    }

    /**
     * Split employees across printed pages.
     *
     * Someone who worked most of the month, or a day with four hours of
     * overtime, needs the whole sheet to themselves; everyone else fits eight
     * to a page.
     *
     * @param  Collection<array-key, mixed>  $groupedByEmployee
     * @return Collection<array-key, mixed>
     */
    private function paginateForPrinting(Collection $groupedByEmployee): Collection
    {
        $threshold = defined('App\Models\Employee::SPECIAL_WORK_DAYS_THRESHOLD')
            ? Employee::SPECIAL_WORK_DAYS_THRESHOLD
            : 20;

        $normal = collect();
        $special = collect();

        foreach ($groupedByEmployee as $employeeId => $days) {
            $workDays = $days->whereIn('value', ['A', 'B', 'K', 'Y'])->count();
            $hasLongOvertimeDay = $days->contains(
                fn ($day): bool => $day->value === 'A' && (int) $day->over_time === 4
            );

            ($workDays >= $threshold || $hasLongOvertimeDay ? $special : $normal)->put($employeeId, $days);
        }

        $pages = $normal->chunk(self::EMPLOYEES_PER_PAGE)->values();

        foreach ($special as $employeeId => $days) {
            $pages->push(collect([$employeeId => $days]));
        }

        return $pages;
    }

    /**
     * Who has signed each approval stage, if anyone.
     *
     * @param  array<string, mixed>  $workflow
     * @return array<string, array{sign: string|null, name: string|null}>
     */
    private function collectStageSignatures(Builder $baseQuery, array $workflow): array
    {
        $signatures = [
            'time_keeper' => ['sign' => null, 'name' => null],
            'super_visor' => ['sign' => null, 'name' => null],
            'field_coordinator' => ['sign' => null, 'name' => null],
            'coordinator' => ['sign' => null, 'name' => null],
            'super_intendent' => ['sign' => null, 'name' => null],
        ];

        $stages = [
            ['time_keeper', null, 'timekeeper_id', fn (TimeSheet $s) => $s->time_keeper, 'time_keeper'],
            ['super_visor', 'needs_supervisor_ids', 'supervisor_id', fn (TimeSheet $s) => $s->super_visor, 'super_visor'],
            ['field_coordinator', 'needs_fieldcoordinator_ids', 'superintendent_id', fn (TimeSheet $s) => $s->super_intendent, 'super_intendent'],
            ['super_intendent', 'needs_superintendent_ids', 'superintendent_id', fn (TimeSheet $s) => $s->super_intendent, 'super_intendent'],
        ];

        foreach ($stages as [$key, $bucket, $column, $signerOf, $relation]) {
            $ids = $bucket === null ? null : $workflow[$bucket];

            if ($bucket !== null && empty($ids)) {
                continue;
            }

            $query = (clone $baseQuery)->whereNotNull($column)->with($relation.'.signature');

            if ($ids !== null) {
                $query->whereIn('employee_id', $ids);
            }

            $sheet = $query->first();
            $signer = $sheet instanceof TimeSheet ? $signerOf($sheet) : null;

            if ($signer) {
                $signatures[$key] = [
                    'sign' => $signer->signature?->image_path,
                    'name' => $signer->name,
                ];
            }
        }

        // The sheet prints the coordinator and field coordinator as one person.
        $signatures['coordinator'] = $signatures['field_coordinator'];

        return $signatures;
    }

    /**
     * Which approval stages the sheet is currently waiting on.
     *
     * A stage becomes available once the stage before it has signed, which is
     * why each check pairs an unsigned column with a signed predecessor.
     *
     * @param  array<string, mixed>  $workflow
     * @return array<string, bool>
     */
    private function resolveApprovalAvailability(Builder $baseQuery, array $workflow): array
    {
        $needsSupervisor = $workflow['needs_supervisor_ids'];
        $needsFieldCoordinator = $workflow['needs_fieldcoordinator_ids'];
        $needsSuperintendent = $workflow['needs_superintendent_ids'];

        $canTimekeeper = (clone $baseQuery)->whereNull('timekeeper_id')->exists();

        $canSupervisor = ! empty($needsSupervisor) && (clone $baseQuery)
            ->whereIn('employee_id', $needsSupervisor)
            ->whereNotNull('timekeeper_id')
            ->whereNull('supervisor_id')
            ->exists();

        $canFieldCoordinator = $this->stageIsWaiting($baseQuery, $needsFieldCoordinator, [
            [$workflow['A2'], 'timekeeper_id'],
            [$workflow['A3'], 'supervisor_id'],
        ]);

        $canSuperintendent = $this->stageIsWaiting($baseQuery, $needsSuperintendent, [
            [$workflow['A1'], 'timekeeper_id'],
            [$workflow['LEGACY'], 'supervisor_id'],
        ]);

        return [
            'canTimekeeperApprove' => $canTimekeeper,
            'canSupervisorApprove' => $canSupervisor,
            'canFieldCoordinatorApprove' => $canFieldCoordinator,
            'canCoordinatorApprove' => $canFieldCoordinator,
            'canSuperintendentApprove' => $canSuperintendent,
            'requiresSupervisorStage' => ! empty($needsSupervisor),
            'requiresFieldCoordinatorStage' => ! empty($needsFieldCoordinator),
            'requiresSuperintendentStage' => ! empty($needsSuperintendent),
        ];
    }

    /**
     * True when the superintendent column is still unsigned for a sheet whose
     * own predecessor stage has already signed.
     *
     * Each bucket carries its own predecessor: an A2 sheet waits on the
     * timekeeper, an A3 sheet waits on the supervisor.
     *
     * @param  list<int>  $stageIds
     * @param  list<array{0: list<int>, 1: string}>  $buckets  [employee ids, column that must already be signed]
     */
    private function stageIsWaiting(Builder $baseQuery, array $stageIds, array $buckets): bool
    {
        if (empty($stageIds)) {
            return false;
        }

        $applicable = array_values(array_filter($buckets, static fn (array $b): bool => ! empty($b[0])));

        if ($applicable === []) {
            return false;
        }

        return (clone $baseQuery)
            ->whereIn('employee_id', $stageIds)
            ->whereNull('superintendent_id')
            ->where(function (Builder $query) use ($applicable): void {
                foreach ($applicable as [$ids, $signedColumn]) {
                    $query->orWhere(function (Builder $inner) use ($ids, $signedColumn): void {
                        $inner->whereIn('employee_id', $ids)->whereNotNull($signedColumn);
                    });
                }
            })
            ->exists();
    }

    public function getManagedApprovalBuckets(?Collection $managedEmployeeIds = null): array
    {
        $managedEmployeeIds ??= auth()->user()->managedEmployeesQuery('time_sheet')->pluck('id');

        $employees = Employee::query()
            ->with('department:id,name')
            ->whereIn('id', $managedEmployeeIds)
            ->get(['id', 'department_id']);

        // Employees who hold a supervisor login. This used to match employee
        // ids against user ids, which only worked while the two id spaces were
        // forced to be equal.
        $supervisorLookup = User::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereHas('roles', function ($query) {
                $query->where('name', 'supervisor');
            })
            ->pluck('employee_id')
            ->flip();

        $buckets = [
            'A1' => [],
            'A2' => [],
            'A3' => [],
            'A4' => [],
            'LEGACY' => [],
        ];

        foreach ($employees as $employee) {
            $departmentKey = $this->normalizeDepartmentName($employee->department?->name);
            $isSupervisorEmployee = $supervisorLookup->has($employee->id);

            if ($this->isA3Department($departmentKey)) {
                $buckets[$isSupervisorEmployee ? 'A2' : 'A3'][] = $employee->id;

                continue;
            }

            if ($this->isA4Department($departmentKey)) {
                $buckets[$isSupervisorEmployee ? 'A1' : 'A4'][] = $employee->id;

                continue;
            }

            // Fallback to old 3-step chain for unknown departments.
            $buckets['LEGACY'][] = $employee->id;
        }

        foreach ($buckets as $key => $ids) {
            $buckets[$key] = array_values(array_unique($ids));
        }

        $buckets['needs_supervisor_ids'] = array_values(array_unique(array_merge(
            $buckets['A3'],
            $buckets['A4'],
            $buckets['LEGACY']
        )));

        $buckets['needs_fieldcoordinator_ids'] = array_values(array_unique(array_merge(
            $buckets['A2'],
            $buckets['A3']
        )));

        $buckets['needs_superintendent_ids'] = array_values(array_unique(array_merge(
            $buckets['A1'],
            $buckets['LEGACY']
        )));

        return $buckets;
    }

    private function normalizeDepartmentName(?string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));
    }

    private function isA3Department(string $normalizedDepartment): bool
    {
        return in_array($normalizedDepartment, self::A3_DEPARTMENT_KEYS, true);
    }

    private function isA4Department(string $normalizedDepartment): bool
    {
        return in_array($normalizedDepartment, self::A4_DEPARTMENT_KEYS, true);
    }
}
