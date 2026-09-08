<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OperationsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_operations(): void
    {
        $response = $this->get(route('operations.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_operations_permission_is_denied(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('operations.index'));

        $response->assertForbidden();
    }

    public function test_user_with_operations_permission_can_archive_by_employee_number(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage operations']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage operations');

        $employee = Employee::factory()->create(['number' => 345678, 'archived_at' => null]);

        $response = $this->actingAs($user)->post(route('operations.archive-by-number'), [
            'employee_numbers' => "345678\nnot-a-number\n345678",
        ]);

        $response->assertRedirect();
        $this->assertNotNull($employee->fresh()->archived_at);
    }

    public function test_archive_by_number_requires_at_least_one_numeric_employee_number(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage operations']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage operations');

        $response = $this->actingAs($user)->post(route('operations.archive-by-number'), [
            'employee_numbers' => 'abc, def',
        ]);

        $response->assertUnprocessable();
    }
}
