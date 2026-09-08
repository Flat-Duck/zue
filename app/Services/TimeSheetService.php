<?php

namespace App\Services;

use App\Helpers\MomentsJs;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;
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

    private const A4_DEPARTMENT_KEYS = [
        'admin',
        'accounting',
        'transportation',
        'transport',
        'transp',
    ];

    public function getApprovalData(int $month, int $year, ?int $scopePolicyId = null): array
    {
        if (config('timesheet_auth.v2_read_enabled', false)) {
            return $this->authorizationService->buildApprovalData($month, $year, $scopePolicyId);
        }

        $months = MomentsJs::getMonthsInYear();
        $monthName = $months->get($month);
        $managedEmployeeIds = auth()->user()->managedEmployeesQuery('time_sheet')->pluck('id');
        $workflow = $this->getManagedApprovalBuckets($managedEmployeeIds);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $until = $from->copy()->addMonth();

        $baseQuery = TimeSheet::whereIn('employee_id', $managedEmployeeIds)
            ->where('day', '>=', $from)
            ->where('day', '<', $until)
            ->with(['employee.department.administration', 'employee.center']);

        $chunk = $baseQuery->get();
        $groupedByEmployee = $chunk->groupBy('employee_id');

        $normalEmployees = collect();
        $specialEmployees = collect();

        foreach ($groupedByEmployee as $employeeId => $days) {
            $workDaysCount = $days->whereIn('value', ['A', 'B', 'K', 'Y'])->count();

            $hasAWithFourOT = $days->contains(function ($day) {
                return $day->value === 'A' && (int) $day->over_time === 4;
            });

            // Use constant if available, otherwise fallback to 19
            $threshold = defined('App\Models\Employee::SPECIAL_WORK_DAYS_THRESHOLD')
                ? Employee::SPECIAL_WORK_DAYS_THRESHOLD
                : 20;

            $isSpecial = $workDaysCount >= $threshold || $hasAWithFourOT;

            if ($isSpecial) {
                $specialEmployees->put($employeeId, $days);
            } else {
                $normalEmployees->put($employeeId, $days);
            }
        }

        $pages = collect();
        $currentPage = collect();
        $maxPerPage = 8;

        foreach ($normalEmployees as $employeeId => $days) {
            $currentPage->put($employeeId, $days);

            if ($currentPage->count() >= $maxPerPage) {
                $pages->push($currentPage);
                $currentPage = collect();
            }
        }

        if ($currentPage->isNotEmpty()) {
            $pages->push($currentPage);
        }

        foreach ($specialEmployees as $employeeId => $days) {
            $pages->push(collect([
                $employeeId => $days,
            ]));
        }

        $chunks = $pages;
        $firstRow = $chunk->first();
        $department = $firstRow?->employee?->department?->name ?? '';
        $center = $firstRow?->employee?->center?->name ?? '';
        $administration = $firstRow?->employee?->department?->administration?->name ?? '';

        $idsA1 = $workflow['A1'];
        $idsA2 = $workflow['A2'];
        $idsA3 = $workflow['A3'];
        $idsA4 = $workflow['A4'];
        $idsLegacy = $workflow['LEGACY'];

        $idsNeedSupervisor = $workflow['needs_supervisor_ids'];
        $idsNeedFieldCoordinator = $workflow['needs_fieldcoordinator_ids'];
        $idsNeedSuperintendent = $workflow['needs_superintendent_ids'];

        $signatures = [
            'time_keeper' => ['sign' => null, 'name' => null],
            'super_visor' => ['sign' => null, 'name' => null],
            'field_coordinator' => ['sign' => null, 'name' => null],
            'coordinator' => ['sign' => null, 'name' => null],
            'super_intendent' => ['sign' => null, 'name' => null],
        ];

        $timekeeperSignedSheet = (clone $baseQuery)
            ->whereNotNull('timekeeper_id')
            ->with('time_keeper.signature')
            ->first();
        if ($timekeeperSignedSheet?->time_keeper) {
            $signatures['time_keeper'] = [
                'sign' => $timekeeperSignedSheet->time_keeper?->signature?->image_path,
                'name' => $timekeeperSignedSheet->time_keeper?->name,
            ];
        }

        if (! empty($idsNeedSupervisor)) {
            $supervisorSignedSheet = (clone $baseQuery)
                ->whereIn('employee_id', $idsNeedSupervisor)
                ->whereNotNull('supervisor_id')
                ->with('super_visor.signature')
                ->first();
            if ($supervisorSignedSheet?->super_visor) {
                $signatures['super_visor'] = [
                    'sign' => $supervisorSignedSheet->super_visor?->signature?->image_path,
                    'name' => $supervisorSignedSheet->super_visor?->name,
                ];
            }
        }

        if (! empty($idsNeedFieldCoordinator)) {
            $fieldCoordinatorSignedSheet = (clone $baseQuery)
                ->whereIn('employee_id', $idsNeedFieldCoordinator)
                ->whereNotNull('superintendent_id')
                ->with('super_intendent.signature')
                ->first();
            if ($fieldCoordinatorSignedSheet?->super_intendent) {
                $signatures['field_coordinator'] = [
                    'sign' => $fieldCoordinatorSignedSheet->super_intendent?->signature?->image_path,
                    'name' => $fieldCoordinatorSignedSheet->super_intendent?->name,
                ];
                $signatures['coordinator'] = $signatures['field_coordinator'];
            }
        }

        if (! empty($idsNeedSuperintendent)) {
            $superintendentSignedSheet = (clone $baseQuery)
                ->whereIn('employee_id', $idsNeedSuperintendent)
                ->whereNotNull('superintendent_id')
                ->with('super_intendent.signature')
                ->first();
            if ($superintendentSignedSheet?->super_intendent) {
                $signatures['super_intendent'] = [
                    'sign' => $superintendentSignedSheet->super_intendent?->signature?->image_path,
                    'name' => $superintendentSignedSheet->super_intendent?->name,
                ];
            }
        }

        $canTimekeeperApprove = (clone $baseQuery)
            ->whereNull('timekeeper_id')
            ->exists();

        $canSupervisorApprove = ! empty($idsNeedSupervisor) && (clone $baseQuery)
            ->whereIn('employee_id', $idsNeedSupervisor)
            ->whereNotNull('timekeeper_id')
            ->whereNull('supervisor_id')
            ->exists();

        $canFieldCoordinatorApprove = false;
        if (! empty($idsNeedFieldCoordinator)) {
            $canFieldCoordinatorApprove = (clone $baseQuery)
                ->whereIn('employee_id', $idsNeedFieldCoordinator)
                ->whereNull('superintendent_id')
                ->where(function ($query) use ($idsA2, $idsA3) {
                    if (! empty($idsA2)) {
                        $query->orWhere(function ($q) use ($idsA2) {
                            $q->whereIn('employee_id', $idsA2)
                                ->whereNotNull('timekeeper_id');
                        });
                    }

                    if (! empty($idsA3)) {
                        $query->orWhere(function ($q) use ($idsA3) {
                            $q->whereIn('employee_id', $idsA3)
                                ->whereNotNull('supervisor_id');
                        });
                    }
                })
                ->exists();
        }

        $canSuperintendentApprove = false;
        if (! empty($idsNeedSuperintendent)) {
            $canSuperintendentApprove = (clone $baseQuery)
                ->whereIn('employee_id', $idsNeedSuperintendent)
                ->whereNull('superintendent_id')
                ->where(function ($query) use ($idsA1, $idsLegacy) {
                    if (! empty($idsA1)) {
                        $query->orWhere(function ($q) use ($idsA1) {
                            $q->whereIn('employee_id', $idsA1)
                                ->whereNotNull('timekeeper_id');
                        });
                    }

                    if (! empty($idsLegacy)) {
                        $query->orWhere(function ($q) use ($idsLegacy) {
                            $q->whereIn('employee_id', $idsLegacy)
                                ->whereNotNull('supervisor_id');
                        });
                    }
                })
                ->exists();
        }

        $month_days = Carbon::create($year, $month, 1)->daysInMonth;
        $employees = Employee::pluck('english_name', 'number');

        return [
            'chunks' => $chunks,
            'department' => $department,
            'center' => $center,
            'administration' => $administration,
            'month_name' => $monthName,
            'selected_month' => $month,
            'selected_year' => $year,
            'month_days' => $month_days,
            'employees' => $employees,
            'signatures' => $signatures,
            'canTimekeeperApprove' => $canTimekeeperApprove,
            'canSupervisorApprove' => $canSupervisorApprove,
            'canFieldCoordinatorApprove' => $canFieldCoordinatorApprove,
            'canCoordinatorApprove' => $canFieldCoordinatorApprove,
            'canSuperintendentApprove' => $canSuperintendentApprove,
            'requiresSupervisorStage' => ! empty($idsNeedSupervisor),
            'requiresFieldCoordinatorStage' => ! empty($idsNeedFieldCoordinator),
            'requiresSuperintendentStage' => ! empty($idsNeedSuperintendent),
        ];
    }

    public function getManagedApprovalBuckets(?Collection $managedEmployeeIds = null): array
    {
        $managedEmployeeIds ??= auth()->user()->managedEmployeesQuery('time_sheet')->pluck('id');

        $employees = Employee::query()
            ->with('department:id,name')
            ->whereIn('id', $managedEmployeeIds)
            ->get(['id', 'department_id']);

        $supervisorLookup = User::query()
            ->whereIn('id', $employees->pluck('id'))
            ->whereHas('roles', function ($query) {
                $query->where('name', 'supervisor');
            })
            ->pluck('id')
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
