<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
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

    /**
     * Everything the monthly approval sheet needs: the paginated employee
     * pages, who has signed each stage, and which stages the current user may
     * still approve.
     */
    public function getApprovalData(int $month, int $year, ?int $scopePolicyId = null): array
    {
        return $this->authorizationService->buildApprovalData($month, $year, $scopePolicyId);
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
