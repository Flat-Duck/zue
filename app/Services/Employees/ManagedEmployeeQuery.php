<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\ManagementScope;
use App\Services\TimeSheetAuth\ScopeResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves who one employee manages, under the original management scope model.
 *
 * This is the `general` path — appraisals, employee lists — and is separate from
 * the time sheet workflow, which resolves the same question through scope policies
 * in {@see ScopeResolver}. The two have different
 * rules and are deliberately not shared.
 */
class ManagedEmployeeQuery
{
    /**
     * Query builder for all employees this employee can manage.
     * Use this if you want to paginate, eager-load, etc.
     */
    public function forEmployee(Employee $manager, ?string $context = 'general'): Builder
    {
        $scopes = $manager->managementScopes()
            ->where(function ($q) use ($context) {
                if ($context) {
                    $q->where('context', $context);
                }
            })
            ->get();

        // If no scope is defined, this manager manages nobody
        if ($scopes->isEmpty()) {
            return Employee::query()->whereRaw('0 = 1');
        }

        $query = Employee::query()
            ->whereNull('archived_at')
            ->where(function (Builder $q) use ($scopes) {
                foreach ($scopes as $scope) {
                    $settings = is_array($scope->settings) ? $scope->settings : [];
                    $jobTitle = $settings['job_title'] ?? null;

                    $applySettings = function (Builder $query) use ($jobTitle) {
                        if ($jobTitle) {
                            $query->where('job', 'like', trim($jobTitle));
                        }
                    };

                    switch ($scope->scope_type) {
                        case ManagementScope::TYPE_GLOBAL:
                            $q->orWhere(function (Builder $q2) use ($applySettings) {
                                $applySettings($q2);
                            });
                            break;

                        case ManagementScope::TYPE_LOCATION:
                            if ($scope->location_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2->where('location_id', $scope->location_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_DEPARTMENT:
                            if ($scope->location_id && $scope->department_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2
                                        ->where('location_id', $scope->location_id)
                                        ->where('department_id', $scope->department_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_CENTER:
                            if ($scope->center_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2->where('center_id', $scope->center_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_EMPLOYEE:
                            // 1. Direct subordinate
                            if ($scope->subordinate_employee_id) {
                                $q->orWhere('id', $scope->subordinate_employee_id);
                            }
                            // 2. Grouped subordinates
                            $targetIds = $settings['target_employee_ids'] ?? [];
                            if (! empty($targetIds)) {
                                $q->orWhereIn('id', $targetIds);
                            }
                            break;
                    }
                }
            });

        if ($context !== 'time_sheet') {
            return $query;
        }

        // Strict ownership for time-sheet context:
        // - hide employees assigned as subordinate/target in another manager's scope
        // - hide employees that are managers in any time_sheet scope
        $candidateIds = (clone $query)->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($candidateIds->isEmpty()) {
            return Employee::query()->whereRaw('0 = 1');
        }

        $timeSheetScopes = ManagementScope::query()
            ->where('context', 'time_sheet')
            ->with('managers:id')
            ->get([
                'id',
                'manager_id',
                'subordinate_employee_id',
                'settings',
            ]);

        $disallowedIds = [];
        $managerPoolIds = [];
        $candidateLookup = $candidateIds->flip();
        $myEmployeeScopeAllowedIds = [];

        foreach ($scopes as $myScope) {
            if ($myScope->scope_type !== ManagementScope::TYPE_EMPLOYEE) {
                continue;
            }

            if (! is_null($myScope->subordinate_employee_id)) {
                $myEmployeeScopeAllowedIds[] = (int) $myScope->subordinate_employee_id;
            }

            $myTargetIds = (array) ($myScope->settings['target_employee_ids'] ?? []);
            foreach ($myTargetIds as $myTargetId) {
                $myEmployeeScopeAllowedIds[] = (int) $myTargetId;
            }
        }

        $myEmployeeScopeAllowedLookup = collect(array_unique($myEmployeeScopeAllowedIds))->flip();

        foreach ($timeSheetScopes as $scope) {
            $managerIds = $scope->managers->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (empty($managerIds) && ! is_null($scope->manager_id)) {
                $managerIds = [(int) $scope->manager_id];
            }
            foreach ($managerIds as $managerId) {
                $managerPoolIds[] = (int) $managerId;
            }

            if (in_array((int) $manager->id, $managerIds, true)) {
                continue;
            }

            if (! is_null($scope->subordinate_employee_id)) {
                $subordinateId = (int) $scope->subordinate_employee_id;
                if ($candidateLookup->has($subordinateId)) {
                    $disallowedIds[] = $subordinateId;
                }
            }

            $targetIds = (array) ($scope->settings['target_employee_ids'] ?? []);
            foreach ($targetIds as $targetId) {
                $targetId = (int) $targetId;
                if ($candidateLookup->has($targetId)) {
                    $disallowedIds[] = $targetId;
                }
            }
        }

        foreach (array_unique($managerPoolIds) as $managerId) {
            if ($candidateLookup->has($managerId)) {
                // Manager-employees are only visible when explicitly assigned in
                // one of my own employee-type scopes.
                if ($myEmployeeScopeAllowedLookup->has((int) $managerId)) {
                    continue;
                }
                $disallowedIds[] = $managerId;
            }
        }

        $allowedIds = $candidateIds
            ->diff(array_unique($disallowedIds))
            ->values()
            ->all();

        if (empty($allowedIds)) {
            return Employee::query()->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $allowedIds);
    }
}
