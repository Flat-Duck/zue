<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Center;
use App\Models\Employee;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CenterEmployeesTest extends TestCase
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
    public function it_gets_center_employees(): void
    {
        $center = Center::factory()->create();
        $employees = Employee::factory()
            ->count(2)
            ->create([
                'center_id' => $center->id,
            ]);

        $response = $this->getJson(
            route('api.centers.employees.index', $center)
        );

        $response->assertOk()->assertSee($employees[0]->job);
    }

    /**
     * @test
     */
    public function it_stores_the_center_employees(): void
    {
        $center = Center::factory()->create();
        $data = Employee::factory()
            ->make([
                'center_id' => $center->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.centers.employees.store', $center),
            $data
        );

        $this->assertDatabaseHas('employees', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $employee = Employee::latest('id')->first();

        $this->assertEquals($center->id, $employee->center_id);
    }
}
