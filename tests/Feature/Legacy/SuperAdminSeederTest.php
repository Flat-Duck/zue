<?php

namespace Tests\Feature\Legacy;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'legacy.super_admin.employee_number' => 9094,
            'legacy.super_admin.name' => 'Abdulrahman Mahidwei',
            'legacy.super_admin.email' => 'admin@admin.com',
            'legacy.super_admin.password' => 'secret-for-the-test',
        ]);
    }

    public function test_it_bootstraps_an_empty_database_into_a_signable_system(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', 'admin@admin.com')->firstOrFail();

        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue(Hash::check('secret-for-the-test', $user->password));
        $this->assertSame(9094, $user->employee->number);
    }

    /**
     * The placeholder deliberately takes the employee number as its id, so a later
     * `legacy:import` updates this row instead of inserting the same person twice.
     */
    public function test_the_placeholder_employee_lands_on_the_id_the_dump_will_use(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(9094, Employee::query()->where('number', 9094)->value('id'));
    }

    public function test_it_promotes_the_real_employee_when_the_data_is_already_imported(): void
    {
        $this->seed(PermissionsSeeder::class);

        $employee = Employee::factory()->create(['number' => 9094, 'english_name' => 'ABDURAHMAN A. ALMHADWI']);

        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, Employee::query()->where('number', 9094)->count());
        $this->assertSame($employee->id, User::query()->where('email', 'admin@admin.com')->value('employee_id'));
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $this->seed(SuperAdminSeeder::class);
        $firstUserId = User::query()->where('email', 'admin@admin.com')->value('id');

        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@admin.com')->count());
        $this->assertSame(1, Employee::query()->where('number', 9094)->count());
        $this->assertSame($firstUserId, User::query()->where('email', 'admin@admin.com')->value('id'));
    }
}
