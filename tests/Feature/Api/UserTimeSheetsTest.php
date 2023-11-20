<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\TimeSheet;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTimeSheetsTest extends TestCase
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
    public function it_gets_user_time_sheets(): void
    {
        $user = User::factory()->create();
        $timeSheets = TimeSheet::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
            ]);

        $response = $this->getJson(route('api.users.time-sheets.index', $user));

        $response->assertOk()->assertSee($timeSheets[0]->day);
    }

    /**
     * @test
     */
    public function it_stores_the_user_time_sheets(): void
    {
        $user = User::factory()->create();
        $data = TimeSheet::factory()
            ->make([
                'user_id' => $user->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.users.time-sheets.store', $user),
            $data
        );

        $this->assertDatabaseHas('time_sheets', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $timeSheet = TimeSheet::latest('id')->first();

        $this->assertEquals($user->id, $timeSheet->user_id);
    }
}
