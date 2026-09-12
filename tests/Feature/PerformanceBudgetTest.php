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
        // Tightened after the HR profile moved to its own table: these pages had
        // slack in them that was hiding lazy loads. A budget only protects a page
        // while it sits close to what the page actually costs.
        $budgets = [
            // home1 sits closest to its budget of any page here, which makes it the
            // likeliest source of the intermittent suite failure recorded in the
            // checklist. If that is what it is, the message below will say so.
            // The DB-driven sidebar adds a bounded navigation lookup and keeps
            // authorization checks centralized instead of hard-coded in Blade.
            'home1' => 24,
            'employees.index' => 10,
            'time-sheets.index' => 10,
            'reports.index' => 6,
            'clinic.index' => 10,
        ];

        // The statements themselves are kept, not just a tally. A budget that fails
        // with a number tells you nothing; one that fails with the queries it ran
        // tells you which relation went unloaded.
        $statements = [];
        DB::listen(static function ($query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        foreach ($budgets as $route => $budget) {
            $before = count($statements);
            $startedAt = microtime(true);
            $memoryBefore = memory_get_usage(true);

            $response = $this->get(route($route));

            $response->assertOk();
            $requestStatements = array_slice($statements, $before);
            $elapsedMilliseconds = (microtime(true) - $startedAt) * 1000;
            $memoryDelta = max(0, memory_get_peak_usage(true) - $memoryBefore);

            fwrite(STDOUT, sprintf(
                "\n%s: %d queries, %.1f ms, %.2f MB peak delta",
                $route,
                count($requestStatements),
                $elapsedMilliseconds,
                $memoryDelta / 1024 / 1024
            ));

            $this->assertLessThanOrEqual(
                $budget,
                count($requestStatements),
                $route.' exceeded its query budget. The queries it ran were:'.PHP_EOL
                    .$this->summarise($requestStatements)
            );
        }
    }

    /**
     * Repeated statements are the interesting ones — a relation loaded per row shows
     * up as the same SQL twenty times — so they are counted rather than listed out.
     *
     * @param  list<string>  $statements
     */
    private function summarise(array $statements): string
    {
        $counts = array_count_values($statements);
        arsort($counts);

        return collect($counts)
            ->map(fn (int $times, string $sql): string => sprintf('  %3dx  %s', $times, $sql))
            ->implode(PHP_EOL);
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
