<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimeSheetTest extends TestCase
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
    public function it_gets_time_sheets_list(): void
    {
        $timeSheets = TimeSheet::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.time-sheets.index'));

        $response->assertOk()->assertSee($timeSheets[0]->day->toISOString());
    }

    /**
     * @test
     */
    public function it_stores_the_time_sheet(): void
    {
        $data = TimeSheet::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.time-sheets.store'), $data);

        $this->assertDatabaseHas('time_sheets', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $user = User::factory()->create();
        $employee = Employee::factory()->create();

        $data = [
            'value' => 'F',
            'day' => $this->faker->date(),
            'revised_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'old_value' => $this->faker->text(255),
            'user_id' => $user->id,
            'employee_id' => $employee->id,
        ];

        $response = $this->putJson(
            route('api.time-sheets.update', $timeSheet),
            $data
        );

        $data['id'] = $timeSheet->id;

        $this->assertDatabaseHas('time_sheets', $data);

        $response->assertOk()->assertJsonFragment([
            'id' => $timeSheet->id,
            'day' => Carbon::parse($data['day'])->toISOString(),
            'value' => $data['value'],
        ]);
    }

    /**
     * @test
     */
    public function it_deletes_the_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $response = $this->deleteJson(
            route('api.time-sheets.destroy', $timeSheet)
        );

        $this->assertModelMissing($timeSheet);

        $response->assertNoContent();
    }
}
