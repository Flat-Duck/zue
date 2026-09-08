<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeTimeSheetsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['email' => 'admin@admin.com']);

        Sanctum::actingAs($user, [], 'web');

        $this->seed(\Database\Seeders\PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_gets_employee_time_sheets(): void
    {
        $employee = Employee::factory()->create();
        $timeSheets = TimeSheet::factory()
            ->count(2)
            ->create([
                'employee_id' => $employee->id,
            ]);

        $response = $this->getJson(
            route('api.employees.time-sheets.index', $employee)
        );

        $response->assertOk()->assertSee($timeSheets[0]->day->toISOString());
    }

    /**
     * @test
     */
    public function it_stores_the_employee_time_sheets(): void
    {
        $employee = Employee::factory()->create();
        $data = TimeSheet::factory()
            ->make([
                'employee_id' => $employee->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.employees.time-sheets.store', $employee),
            $data
        );

        $this->assertDatabaseHas('time_sheets', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $timeSheet = TimeSheet::latest('id')->first();

        $this->assertEquals($employee->id, $timeSheet->employee_id);
    }
}
