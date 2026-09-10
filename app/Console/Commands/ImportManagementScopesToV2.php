<?php

namespace App\Console\Commands;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Employee;
use App\Models\ManagementScope;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Services\TimeSheetAuth\WorkflowResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportManagementScopesToV2 extends Command
{
    protected $signature = 'timesheet-auth-v2:import {--seed-flows : Seed default A1/A2/A3/A4/LEGACY flows} {--dry-run : Show audit only without DB writes}';

    protected $description = 'Import legacy management_scopes into scope_policies/scope_policy_actors and generate an audit report.';

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

    public function handle(WorkflowResolver $resolver): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $seedFlows = (bool) $this->option('seed-flows');

        $legacyScopes = ManagementScope::query()
            ->with('managers:id')
            ->get();

        $this->info('Legacy scopes found: '.$legacyScopes->count());

        $importedPolicies = 0;
        $importedActors = 0;

        $importScopes = function () use ($legacyScopes, $dryRun, &$importedPolicies, &$importedActors): void {
            foreach ($legacyScopes as $scope) {
                $settings = is_array($scope->settings) ? $scope->settings : [];
                $targetIds = collect($settings['target_employee_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0);

                if (! is_null($scope->subordinate_employee_id)) {
                    $targetIds->push((int) $scope->subordinate_employee_id);
                }

                $targetIds = $targetIds->unique()->values()->all();

                $policyData = [
                    'name' => $scope->name ?: ('Legacy Scope #'.$scope->id),
                    'context' => $scope->context ?: 'general',
                    'match_type' => (string) $scope->scope_type,
                    'location_id' => $scope->location_id,
                    'department_id' => $scope->department_id,
                    'center_id' => $scope->center_id,
                    'target_employee_ids' => $scope->scope_type === ManagementScope::TYPE_EMPLOYEE ? $targetIds : null,
                    'priority' => 0,
                    'is_active' => true,
                    'settings' => array_merge($settings, ['legacy_scope_id' => (int) $scope->id]),
                ];

                $existingPolicy = ScopePolicy::query()
                    ->where('context', $policyData['context'])
                    ->where('settings->legacy_scope_id', (int) $scope->id)
                    ->first();

                if ($dryRun) {
                    $importedPolicies++;
                } else {
                    if ($existingPolicy) {
                        $existingPolicy->update($policyData);
                        $policy = $existingPolicy;
                    } else {
                        $policy = ScopePolicy::query()->create($policyData);
                    }
                    $importedPolicies++;

                    $managerIds = $scope->managers->pluck('id')->map(fn ($id) => (int) $id)->all();
                    if (empty($managerIds) && ! is_null($scope->manager_id)) {
                        $managerIds = [(int) $scope->manager_id];
                    }

                    foreach (array_unique($managerIds) as $managerId) {
                        ScopePolicyActor::query()->updateOrCreate(
                            [
                                'policy_id' => (int) $policy->id,
                                'actor_employee_id' => (int) $managerId,
                            ],
                            [
                                'can_fill' => true,
                                'can_approve' => true,
                                'can_revise' => true,
                                'role_hint' => null,
                            ]
                        );
                        $importedActors++;
                    }
                }
            }
        };

        if ($dryRun) {
            $importScopes();
        } else {
            DB::transaction($importScopes);
        }

        $this->line('Imported/updated scope policies: '.$importedPolicies);
        $this->line('Imported/updated scope actors: '.$importedActors);

        if ($seedFlows) {
            $seeded = $this->seedDefaultFlows($dryRun);
            $this->line('Seeded/updated flow definitions: '.$seeded);
        }

        $this->runAudit($resolver);

        return self::SUCCESS;
    }

    private function seedDefaultFlows(bool $dryRun): int
    {
        $definitions = [
            [
                'name' => 'A1',
                'context' => 'time_sheet',
                'applies_to' => [
                    'department_keys' => self::A4_DEPARTMENT_KEYS,
                    'employee_roles_any' => ['supervisor'],
                ],
                'steps' => [
                    ['step_order' => 1, 'step_key' => 'timekeeper', 'required_role' => 'timekeeper', 'can_fill' => true, 'can_approve' => true, 'depends_on_step_order' => null],
                    ['step_order' => 2, 'step_key' => 'superintendent', 'required_role' => 'superintendent', 'can_fill' => false, 'can_approve' => true, 'depends_on_step_order' => 1],
                ],
            ],
            [
                'name' => 'A2',
                'context' => 'time_sheet',
                'applies_to' => [
                    'department_keys' => self::A3_DEPARTMENT_KEYS,
                    'employee_roles_any' => ['supervisor'],
                ],
                'steps' => [
                    ['step_order' => 1, 'step_key' => 'timekeeper', 'required_role' => 'timekeeper', 'can_fill' => true, 'can_approve' => true, 'depends_on_step_order' => null],
                    ['step_order' => 2, 'step_key' => 'fieldcoordinator', 'required_role' => 'fieldcoordinator', 'can_fill' => false, 'can_approve' => true, 'depends_on_step_order' => 1],
                ],
            ],
            [
                'name' => 'A3',
                'context' => 'time_sheet',
                'applies_to' => [
                    'department_keys' => self::A3_DEPARTMENT_KEYS,
                    'employee_roles_none' => ['supervisor'],
                ],
                'steps' => [
                    ['step_order' => 1, 'step_key' => 'timekeeper', 'required_role' => 'timekeeper', 'can_fill' => true, 'can_approve' => true, 'depends_on_step_order' => null],
                    ['step_order' => 2, 'step_key' => 'supervisor', 'required_role' => 'supervisor', 'can_fill' => false, 'can_approve' => true, 'depends_on_step_order' => 1],
                    ['step_order' => 3, 'step_key' => 'fieldcoordinator', 'required_role' => 'fieldcoordinator', 'can_fill' => false, 'can_approve' => true, 'depends_on_step_order' => 2],
                ],
            ],
            [
                'name' => 'A4',
                'context' => 'time_sheet',
                'applies_to' => [
                    'department_keys' => self::A4_DEPARTMENT_KEYS,
                    'employee_roles_none' => ['supervisor'],
                ],
                'steps' => [
                    ['step_order' => 1, 'step_key' => 'timekeeper', 'required_role' => 'timekeeper', 'can_fill' => true, 'can_approve' => true, 'depends_on_step_order' => null],
                    ['step_order' => 2, 'step_key' => 'supervisor', 'required_role' => 'supervisor', 'can_fill' => false, 'can_approve' => true, 'depends_on_step_order' => 1],
                ],
            ],
            [
                'name' => 'LEGACY',
                'context' => 'time_sheet',
                'applies_to' => [],
                'steps' => [
                    ['step_order' => 1, 'step_key' => 'timekeeper', 'required_role' => 'timekeeper', 'can_fill' => true, 'can_approve' => true, 'depends_on_step_order' => null],
                    ['step_order' => 2, 'step_key' => 'supervisor', 'required_role' => 'supervisor', 'can_fill' => false, 'can_approve' => true, 'depends_on_step_order' => 1],
                    ['step_order' => 3, 'step_key' => 'superintendent', 'required_role' => 'superintendent', 'can_fill' => false, 'can_approve' => true, 'depends_on_step_order' => 2],
                ],
            ],
        ];

        $seeded = 0;

        $seed = function () use ($definitions, $dryRun, &$seeded): void {
            foreach ($definitions as $flowDef) {
                if ($dryRun) {
                    $seeded++;

                    continue;
                }

                $flow = ApprovalFlow::query()->updateOrCreate(
                    ['context' => $flowDef['context'], 'name' => $flowDef['name']],
                    [
                        'is_active' => true,
                        'applies_to' => $flowDef['applies_to'],
                    ]
                );

                foreach ($flowDef['steps'] as $stepDef) {
                    ApprovalFlowStep::query()->updateOrCreate(
                        [
                            'flow_id' => (int) $flow->id,
                            'step_key' => $stepDef['step_key'],
                        ],
                        [
                            'step_order' => $stepDef['step_order'],
                            'required_role' => $stepDef['required_role'],
                            'can_fill' => $stepDef['can_fill'],
                            'can_approve' => $stepDef['can_approve'],
                            'depends_on_step_order' => $stepDef['depends_on_step_order'],
                        ]
                    );
                }

                $seeded++;
            }
        };

        if ($dryRun) {
            $seed();
        } else {
            DB::transaction($seed);
        }

        return $seeded;
    }

    private function runAudit(WorkflowResolver $resolver): void
    {
        $this->line('');
        $this->info('Audit Report');

        $policies = ScopePolicy::query()->where('is_active', true)->get();
        $employees = Employee::query()->whereNull('archived_at')->get(['id', 'location_id', 'department_id', 'center_id']);

        $overlapCounter = [];
        foreach ($policies as $policy) {
            foreach ($employees as $employee) {
                if (! $this->policyMatchesEmployee($policy, $employee)) {
                    continue;
                }

                $key = $policy->context.':'.$employee->id;
                $overlapCounter[$key] = ($overlapCounter[$key] ?? 0) + 1;
            }
        }

        $overlaps = collect($overlapCounter)->filter(fn ($count) => $count > 1);
        $this->line('Overlap map entries (>1 policy for same employee/context): '.$overlaps->count());

        $coverageGaps = 0;
        foreach ($employees as $employee) {
            $flow = $resolver->resolveFlowForEmployee($employee, 'time_sheet');
            if (! $flow) {
                $coverageGaps++;
            }
        }
        $this->line('Flow coverage gaps (no matching active flow): '.$coverageGaps);
    }

    private function policyMatchesEmployee(ScopePolicy $policy, Employee $employee): bool
    {
        return match ($policy->match_type) {
            ScopePolicy::MATCH_GLOBAL => true,
            ScopePolicy::MATCH_LOCATION => (int) $policy->location_id === (int) $employee->location_id,
            ScopePolicy::MATCH_DEPARTMENT => (
                (! $policy->location_id || (int) $policy->location_id === (int) $employee->location_id)
                && (int) $policy->department_id === (int) $employee->department_id
            ),
            ScopePolicy::MATCH_CENTER => (int) $policy->center_id === (int) $employee->center_id,
            ScopePolicy::MATCH_EMPLOYEE => collect($policy->target_employee_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->contains((int) $employee->id),
            default => false,
        };
    }
}
