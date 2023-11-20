<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\TimeSheet;

use App\Models\Employee;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimeSheetControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create(['email' => 'admin@admin.com'])
        );

        $this->seed(\Database\Seeders\PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_displays_index_view_with_time_sheets(): void
    {
        $timeSheets = TimeSheet::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('time-sheets.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.time_sheets.index')
            ->assertViewHas('timeSheets');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_time_sheet(): void
    {
        $response = $this->get(route('time-sheets.create'));

        $response->assertOk()->assertViewIs('app.time_sheets.create');
    }

    /**
     * @test
     */
    public function it_stores_the_time_sheet(): void
    {
        $data = TimeSheet::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('time-sheets.store'), $data);

        $this->assertDatabaseHas('time_sheets', $data);

        $timeSheet = TimeSheet::latest('id')->first();

        $response->assertRedirect(route('time-sheets.edit', $timeSheet));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $response = $this->get(route('time-sheets.show', $timeSheet));

        $response
            ->assertOk()
            ->assertViewIs('app.time_sheets.show')
            ->assertViewHas('timeSheet');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $response = $this->get(route('time-sheets.edit', $timeSheet));

        $response
            ->assertOk()
            ->assertViewIs('app.time_sheets.edit')
            ->assertViewHas('timeSheet');
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
            'value' => 'A',
            'day' => $this->faker->date(),
            'revised_at' => $this->faker->dateTime(),
            'old_value' => $this->faker->text(255),
            'user_id' => $user->id,
            'employee_id' => $employee->id,
        ];

        $response = $this->put(route('time-sheets.update', $timeSheet), $data);

        $data['id'] = $timeSheet->id;

        $this->assertDatabaseHas('time_sheets', $data);

        $response->assertRedirect(route('time-sheets.edit', $timeSheet));
    }

    /**
     * @test
     */
    public function it_deletes_the_time_sheet(): void
    {
        $timeSheet = TimeSheet::factory()->create();

        $response = $this->delete(route('time-sheets.destroy', $timeSheet));

        $response->assertRedirect(route('time-sheets.index'));

        $this->assertModelMissing($timeSheet);
    }
}
