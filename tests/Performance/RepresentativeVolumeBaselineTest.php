<?php

namespace Tests\Performance;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\ManagementScope;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Performance baseline at representative production volume.
 *
 * The existing query-budget test runs against 25 rows, which cannot reveal the
 * behaviour that actually matters: whether a page's cost grows with the size of
 * the table. This one seeds volume comparable to the production database
 * described in the 2026-09-08 audit (~1,100 employees) and records query count,
 * wall time and peak memory for the core pages.
 *
 * Excluded from the default suite because of its seeding cost. Run with:
 *   php artisan test --testsuite=Performance
 */
#[Group('performance')]
class RepresentativeVolumeBaselineTest extends TestCase
{
    use RefreshDatabase;

    private const EMPLOYEES = 1100;

    private const TIMESHEETS_PER_EMPLOYEE = 90;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'timesheet_auth.v2_read_enabled' => false,
            'timesheet_auth.v2_write_enabled' => false,
        ]);

        $this->seed(PermissionsSeeder::class);
        $this->user = $this->manager();
        $this->actingAs($this->user);
    }

    private function manager(): User
    {
        $user = User::factory()->create();

        $user->givePermissionTo([
            'list employees', 'view employees',
            'list timesheets', 'view timesheets', 'fill timesheets',
            'list rooms', 'view rooms',
        ]);

        $employee = $user->employee;

        $scope = ManagementScope::create([
            'manager_id' => $employee->id,
            'name' => 'Company wide',
            'scope_type' => ManagementScope::TYPE_GLOBAL,
            'context' => 'time_sheet',
        ]);
        $employee->managementScopes()->attach($scope->id);

        return $user;
    }

    private int $seededEmployees = 0;

    private ?Department $department = null;

    private ?Location $location = null;

    private ?Center $center = null;

    /**
     * Add `$count` more employees (and their timesheets) to whatever is already
     * seeded, so the same page can be measured at growing table sizes.
     */
    private function seedVolume(int $count): void
    {
        $this->department ??= Department::factory()->create();
        $this->location ??= Location::factory()->create();
        $this->center ??= Center::factory()->create();

        $department = $this->department;
        $location = $this->location;
        $center = $this->center;

        $now = now();
        $rows = [];
        $offset = $this->seededEmployees;

        for ($i = $offset + 1; $i <= $offset + $count; $i++) {
            $rows[] = [
                'number' => 600000 + $i,
                'english_name' => 'Employee '.$i,
                'schedule' => '5/5',
                'start_date' => $now->copy()->subYears(2)->toDateString(),
                'total_balance' => 0,
                'transfered_balance' => 0,
                'department_id' => $department->id,
                'location_id' => $location->id,
                'center_id' => $center->id,
                'employee_level' => 1,
                'management_level' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === 500) {
                Employee::insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            Employee::insert($rows);
        }

        // Only the employees inserted by this call: the acting manager's own
        // employee record is created by a factory and can fall in this range.
        $employeeIds = Employee::query()
            ->whereBetween('number', [600000 + $offset + 1, 600000 + $offset + $count])
            ->pluck('id')
            ->all();

        $sheets = [];
        $start = $now->copy()->startOfYear();

        foreach ($employeeIds as $employeeId) {
            for ($d = 0; $d < self::TIMESHEETS_PER_EMPLOYEE; $d++) {
                $sheets[] = [
                    'employee_id' => $employeeId,
                    'day' => $start->copy()->addDays($d)->toDateString(),
                    'value' => 'F',
                    'over_time' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($sheets) === 2000) {
                    DB::table('time_sheets')->insert($sheets);
                    $sheets = [];
                }
            }
        }
        if ($sheets !== []) {
            DB::table('time_sheets')->insert($sheets);
        }

        $this->seededEmployees += $count;
    }

    /**
     * @param  list<string>  $routes
     * @return array<string, array{queries: int, ms: float}>
     */
    private function measure(array $routes): array
    {
        $results = [];

        foreach ($routes as $route) {
            $queries = 0;
            DB::listen(static function () use (&$queries): void {
                $queries++;
            });

            gc_collect_cycles();
            $startedAt = microtime(true);

            $response = $this->get(route($route));

            $elapsedMs = (microtime(true) - $startedAt) * 1000;
            $response->assertOk();

            $results[$route] = ['queries' => $queries, 'ms' => $elapsedMs];
        }

        return $results;
    }

    #[Test]
    public function page_cost_does_not_grow_with_table_size(): void
    {
        $routes = ['home1', 'employees.index', 'time-sheets.index', 'reports.index'];

        $this->seedVolume(100);
        $small = $this->measure($routes);
        $smallEmployees = Employee::query()->count();
        $smallSheets = DB::table('time_sheets')->count();

        $this->seedVolume(self::EMPLOYEES - 100);
        $large = $this->measure($routes);
        $largeEmployees = Employee::query()->count();
        $largeSheets = DB::table('time_sheets')->count();

        fwrite(STDOUT, sprintf(
            "\n\n=== Performance baseline: does cost scale with table size? ===\n".
            "small: %d employees / %s timesheets\n".
            "large: %d employees / %s timesheets\n\n".
            "%-22s %18s %18s %10s\n%s\n",
            $smallEmployees, number_format($smallSheets),
            $largeEmployees, number_format($largeSheets),
            'page', 'small (q / ms)', 'large (q / ms)', 'q growth',
            str_repeat('-', 72)
        ));

        foreach ($routes as $route) {
            $growth = $large[$route]['queries'] - $small[$route]['queries'];

            fwrite(STDOUT, sprintf(
                "%-22s %10d / %5.1f %10d / %5.1f %10s\n",
                $route,
                $small[$route]['queries'], $small[$route]['ms'],
                $large[$route]['queries'], $large[$route]['ms'],
                ($growth > 0 ? '+' : '').$growth
            ));
        }

        fwrite(STDOUT, "\n");

        // An eleven-fold increase in rows must not increase the number of
        // queries: that is the definition of an N+1 having been eliminated.
        foreach ($routes as $route) {
            $this->assertLessThanOrEqual(
                $small[$route]['queries'],
                $large[$route]['queries'],
                sprintf(
                    '%s issued %d queries at %d employees but only %d at %d - cost is scaling with table size.',
                    $route,
                    $large[$route]['queries'],
                    $largeEmployees,
                    $small[$route]['queries'],
                    $smallEmployees
                )
            );
        }
    }
}
