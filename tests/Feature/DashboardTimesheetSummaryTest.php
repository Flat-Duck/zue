<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTimesheetSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function dashboard_shows_monthly_timesheet_completion_by_department(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 10:00:00'));

        $gasplant = Department::factory()->create(['name' => 'Gasplant']);
        $clinic = Department::factory()->create(['name' => 'Clinic']);

        $gasplantEmployees = Employee::factory()->count(3)->create(['department_id' => $gasplant->id]);
        $clinicEmployees = Employee::factory()->count(2)->create(['department_id' => $clinic->id]);
        Employee::factory()->create(['department_id' => $gasplant->id, 'archived_at' => now()]);

        TimeSheet::factory()->create(['employee_id' => $gasplantEmployees[0]->id, 'day' => '2026-09-01']);
        TimeSheet::factory()->create(['employee_id' => $gasplantEmployees[0]->id, 'day' => '2026-09-02']);
        TimeSheet::factory()->create(['employee_id' => $gasplantEmployees[1]->id, 'day' => '2026-09-03']);
        TimeSheet::factory()->create(['employee_id' => $gasplantEmployees[2]->id, 'day' => '2026-08-31']);
        TimeSheet::factory()->create(['employee_id' => $clinicEmployees[0]->id, 'day' => '2026-09-04']);

        $this->actingAs(User::factory()->create())
            ->get(route('home', ['month' => 9]))
            ->assertOk()
            ->assertSee('Gasplant')
            ->assertSee('Clinic')
            ->assertSeeInOrder(['Gasplant', '2', '1', '3', '67%'])
            ->assertSeeInOrder(['Clinic', '1', '1', '2', '50%']);
    }
}
