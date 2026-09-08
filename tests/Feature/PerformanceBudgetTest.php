<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['email' => 'admin@admin.com']));
        $this->seed(PermissionsSeeder::class);
        Employee::factory()->count(25)->create();
        TimeSheet::factory()->count(25)->create();
    }

    public function test_core_mvc_pages_stay_within_query_budgets(): void
    {
        $budgets = [
            'home1' => 25,
            'employees.index' => 20,
            'time-sheets.index' => 30,
            'reports.index' => 20,
            'clinic.index' => 25,
        ];

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        foreach ($budgets as $route => $budget) {
            $queriesBeforeRequest = $queries;
            $startedAt = microtime(true);
            $memoryBefore = memory_get_usage(true);

            $response = $this->get(route($route));

            $response->assertOk();
            $requestQueries = $queries - $queriesBeforeRequest;
            $elapsedMilliseconds = (microtime(true) - $startedAt) * 1000;
            $memoryDelta = max(0, memory_get_peak_usage(true) - $memoryBefore);

            fwrite(STDOUT, sprintf(
                "\n%s: %d queries, %.1f ms, %.2f MB peak delta",
                $route,
                $requestQueries,
                $elapsedMilliseconds,
                $memoryDelta / 1024 / 1024
            ));

            $this->assertLessThanOrEqual($budget, $requestQueries, $route.' exceeded its query budget.');
        }
    }

    public function test_major_queries_have_explain_plans(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('EXPLAIN baseline uses the MySQL query planner.');
        }

        $plans = [
            DB::select('EXPLAIN SELECT id FROM employees WHERE archived_at IS NULL ORDER BY english_name LIMIT 50'),
            DB::select('EXPLAIN SELECT employee_id, day FROM time_sheets WHERE day >= ? AND day < ? ORDER BY day DESC LIMIT 100', [
                '2026-01-01',
                '2027-01-01',
            ]),
            DB::select('EXPLAIN SELECT employee_id, COUNT(*) FROM time_sheets GROUP BY employee_id'),
        ];

        foreach ($plans as $plan) {
            $this->assertNotEmpty($plan);
        }
    }
}
