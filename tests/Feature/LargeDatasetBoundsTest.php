<?php

namespace Tests\Feature;

use App\Imports\ArchivedEmployeesImport;
use App\Imports\RoomsImport;
use App\Imports\UsersImport;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Guards against a large dataset exhausting memory in a browser request.
 *
 * The protections here are bounds and chunking rather than raw speed: an
 * unbounded report that hydrates an entire table is the failure mode that takes
 * the whole application down, not a slow one.
 */
class LargeDatasetBoundsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Insert employees directly. The factory creates a user, department,
     * location and centre per employee, which is far too costly at this volume.
     */
    private function seedEmployees(int $count): void
    {
        $department = Department::factory()->create();
        $location = Location::factory()->create();
        $center = Center::factory()->create();

        $now = now();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'number' => 500000 + $i,
                'english_name' => 'Employee '.$i,
                'schedule' => '5/5',
                'start_date' => $now->copy()->subYear()->toDateString(),
                'total_balance' => -1 * $i,
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
    }

    private function reporter(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['list employees', 'view employees'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['list employees', 'view employees']);

        return $user;
    }

    #[Test]
    public function every_import_reads_in_chunks(): void
    {
        foreach ([ArchivedEmployeesImport::class, UsersImport::class, RoomsImport::class] as $class) {
            $import = new $class;

            $this->assertInstanceOf(
                WithChunkReading::class,
                $import,
                "[$class] must stream the file rather than load it whole."
            );

            $this->assertGreaterThan(0, $import->chunkSize());
            $this->assertLessThanOrEqual(1000, $import->chunkSize(), "[$class] chunk size defeats the purpose if it is huge.");
        }
    }

    #[Test]
    public function the_balance_report_is_capped_below_the_row_count(): void
    {
        $this->seedEmployees(5200);

        $this->assertSame(5200, Employee::query()->count());

        $response = $this->actingAs($this->reporter())
            ->post(route('reports.balances'), [
                'report_type' => 'all',
                // No print_type: render the HTML report rather than a download.
            ]);

        $response->assertOk();

        $departments = $response->original->getData()['departments'] ?? null;
        $this->assertNotNull($departments, 'The report should expose its grouped employee set.');

        // The controller caps at 5000; without that cap this would be 5200.
        $this->assertSame(5000, $departments->flatten()->count());
    }

    #[Test]
    public function the_balance_report_stays_within_a_memory_budget(): void
    {
        $this->seedEmployees(5200);

        gc_collect_cycles();
        $before = memory_get_usage(true);

        $response = $this->actingAs($this->reporter())
            ->post(route('reports.balances'), [
                'report_type' => 'all',
                // No print_type: render the HTML report rather than a download.
            ]);

        $response->assertOk();

        $usedMb = (memory_get_usage(true) - $before) / 1024 / 1024;

        // Generous, but it fails loudly if the cap or the eager loading is lost.
        $this->assertLessThan(
            192,
            $usedMb,
            sprintf('Balance report used %.1f MB; the row cap or eager loading may have been removed.', $usedMb)
        );
    }

    #[Test]
    public function the_balance_export_stays_within_a_memory_budget(): void
    {
        $this->seedEmployees(5200);

        gc_collect_cycles();
        $before = memory_get_usage(true);

        $response = $this->actingAs($this->reporter())
            ->post(route('reports.balances'), [
                'report_type' => 'all',
                'print_type' => 'excel',
            ]);

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $usedMb = (memory_get_usage(true) - $before) / 1024 / 1024;

        $this->assertLessThan(
            256,
            $usedMb,
            sprintf('Balance export used %.1f MB; the row cap or export shape may have regressed.', $usedMb)
        );
    }

    #[Test]
    public function the_balance_report_eager_loads_its_relations(): void
    {
        $this->seedEmployees(400);

        DB::enableQueryLog();

        $this->actingAs($this->reporter())
            ->post(route('reports.balances'), [
                'report_type' => 'all',
                // No print_type: render the HTML report rather than a download.
            ])->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Without eager loading this would be roughly 3 queries per employee.
        $this->assertLessThan(
            50,
            $queries,
            "Balance report ran {$queries} queries for 400 employees; relations are probably no longer eager loaded."
        );
    }
}
