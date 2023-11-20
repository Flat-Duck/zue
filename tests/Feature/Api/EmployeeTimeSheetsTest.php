<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Employee;
use App\Models\TimeSheet;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

        $response->assertOk()->assertSee($timeSheets[0]->day);
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
