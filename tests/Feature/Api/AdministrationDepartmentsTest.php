<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Department;
use App\Models\Administration;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdministrationDepartmentsTest extends TestCase
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
    public function it_gets_administration_departments(): void
    {
        $administration = Administration::factory()->create();
        $departments = Department::factory()
            ->count(2)
            ->create([
                'administration_id' => $administration->id,
            ]);

        $response = $this->getJson(
            route('api.administrations.departments.index', $administration)
        );

        $response->assertOk()->assertSee($departments[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_administration_departments(): void
    {
        $administration = Administration::factory()->create();
        $data = Department::factory()
            ->make([
                'administration_id' => $administration->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.administrations.departments.store', $administration),
            $data
        );

        $this->assertDatabaseHas('departments', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $department = Department::latest('id')->first();

        $this->assertEquals(
            $administration->id,
            $department->administration_id
        );
    }
}
