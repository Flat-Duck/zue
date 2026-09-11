<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ScopeContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The chains a time sheet is signed through.
 *
 * Which chain applies depends on the department and whether the employee is a
 * supervisor: field departments route through the field coordinator, office ones
 * through the superintendent, and a supervisor's own sheet skips the supervisor
 * step because they would be signing for themselves.
 *
 * These definitions previously existed only inside a one-off import command, so
 * a fresh database came up with no approval chain at all and every sheet stalled.
 */
class ApprovalFlowSeeder extends Seeder
{
    /**
     * The field departments, matched loosely because the names were typed by
     * hand over many years: "GasPlant", "GP", "PROD .NC163".
     *
     * @var list<string>
     */
    private const FIELD_DEPARTMENTS = [
        'gaspant', 'gp', 'production', 'prod', 'prodnc163',
        'lab', 'generalmaintenance', 'genmaint', 'esp', 'campboss', 'camboss',
    ];

    /**
     * @var list<string>
     */
    private const OFFICE_DEPARTMENTS = [
        'admin', 'accounting', 'transportation', 'transport', 'transp',
    ];

    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            DB::transaction(function () use ($definition): void {
                $flow = ApprovalFlow::query()->updateOrCreate(
                    ['context' => $definition['context'], 'name' => $definition['name']],
                    ['is_active' => true, 'applies_to' => $definition['applies_to']],
                );

                foreach ($definition['steps'] as $step) {
                    ApprovalFlowStep::query()->updateOrCreate(
                        ['flow_id' => $flow->id, 'step_key' => $step['step_key']],
                        [
                            'step_order' => $step['step_order'],
                            'required_role' => $step['required_role'],
                            'can_fill' => $step['can_fill'],
                            'can_approve' => $step['can_approve'],
                            'depends_on_step_order' => $step['depends_on_step_order'],
                        ],
                    );
                }
            });
        }
    }

    /**
     * @return list<array{name: string, context: string, applies_to: array<string, mixed>, steps: list<array<string, mixed>>}>
     */
    private function definitions(): array
    {
        $step = fn (int $order, string $key, ?string $role, bool $canFill = false): array => [
            'step_order' => $order,
            'step_key' => $key,
            'required_role' => $role,
            'can_fill' => $canFill,
            'can_approve' => true,
            'depends_on_step_order' => $order === 1 ? null : $order - 1,
        ];

        $timekeeper = $step(1, 'timekeeper', 'timekeeper', canFill: true);

        return [
            [
                'name' => 'A1',
                'context' => ScopeContext::TIME_SHEET,
                'applies_to' => ['department_keys' => self::OFFICE_DEPARTMENTS, 'employee_roles_any' => ['supervisor']],
                'steps' => [$timekeeper, $step(2, 'superintendent', 'superintendent')],
            ],
            [
                'name' => 'A2',
                'context' => ScopeContext::TIME_SHEET,
                'applies_to' => ['department_keys' => self::FIELD_DEPARTMENTS, 'employee_roles_any' => ['supervisor']],
                'steps' => [$timekeeper, $step(2, 'fieldcoordinator', 'fieldcoordinator')],
            ],
            [
                'name' => 'A3',
                'context' => ScopeContext::TIME_SHEET,
                'applies_to' => ['department_keys' => self::FIELD_DEPARTMENTS, 'employee_roles_none' => ['supervisor']],
                'steps' => [$timekeeper, $step(2, 'supervisor', 'supervisor'), $step(3, 'fieldcoordinator', 'fieldcoordinator')],
            ],
            [
                'name' => 'A4',
                'context' => ScopeContext::TIME_SHEET,
                'applies_to' => ['department_keys' => self::OFFICE_DEPARTMENTS, 'employee_roles_none' => ['supervisor']],
                'steps' => [$timekeeper, $step(2, 'supervisor', 'supervisor')],
            ],
            [
                // Nothing else matched, so the longest chain applies.
                'name' => 'LEGACY',
                'context' => ScopeContext::TIME_SHEET,
                'applies_to' => [],
                'steps' => [
                    $timekeeper,
                    $step(2, 'supervisor', 'supervisor'),
                    $step(3, 'superintendent', 'superintendent'),
                ],
            ],
        ];
    }
}
