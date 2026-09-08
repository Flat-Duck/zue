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

            $response = $this->get(route($route));

            $response->assertOk();
            $requestQueries = $queries - $queriesBeforeRequest;
            $this->assertLessThanOrEqual($budget, $requestQueries, $route.' exceeded its query budget.');
        }
    }
}
