<?php

namespace Tests\Feature;

use App\Livewire\SearchEmployee;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SearchEmployeeLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'view employees']);
    }

    public function test_search_does_not_crash_when_employee_is_not_found(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SearchEmployee::class)
            ->set('number', 999999)
            ->call('searchEmployees')
            ->assertHasNoErrors();
    }

    public function test_search_requires_employee_view_permission_before_dispatching_employee(): void
    {
        $employee = Employee::factory()->create(['number' => 222333]);

        $this->actingAs(User::factory()->create());

        Livewire::test(SearchEmployee::class)
            ->set('number', $employee->number)
            ->call('searchEmployees')
            ->assertForbidden();
    }

    public function test_search_dispatches_employee_for_authorized_user(): void
    {
        $employee = Employee::factory()->create(['number' => 222334]);
        $user = User::factory()->create();
        $user->givePermissionTo('view employees');

        $this->actingAs($user);

        Livewire::test(SearchEmployee::class)
            ->set('number', $employee->number)
            ->call('searchEmployees')
            ->assertDispatched('employee-found');
    }
}
