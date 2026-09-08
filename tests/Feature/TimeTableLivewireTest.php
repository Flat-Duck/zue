<?php

namespace Tests\Feature;

use App\Livewire\TimeTable;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TimeTableLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'view employees']);
    }

    public function test_component_loads_timesheets_on_mount_for_authorized_user(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('view employees');

        $this->actingAs($user);

        Livewire::test(TimeTable::class, ['employee' => $employee])
            ->assertSet('employee.id', $employee->id)
            ->assertSet('year', now()->year);
    }
}
