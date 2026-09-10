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

    /**
     * The date picker's upper bound was the literal `2026-10-10`, written into an
     * inline script. A ceiling written as a date expires: past it, the fill screen's
     * picker refuses every day and attendance cannot be entered at all. It is worked
     * out from today now, and this is what stops it being pinned again.
     */
    public function test_the_date_picker_bound_moves_with_the_calendar(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('view employees');

        $this->actingAs($user);

        // Today's derived bound happens to equal the literal that used to be there,
        // so the only honest test is that it moves when the calendar does.
        $this->travelTo('2027-06-15');

        $html = Livewire::test(TimeTable::class, ['employee' => $employee])->html();

        $this->assertStringContainsString('2027-07-15', $html, 'The picker bound did not follow the calendar.');
        $this->assertStringNotContainsString('2026-10-10', $html, 'The picker has a hard-coded date ceiling again.');
    }

    /**
     * Alpine sets the picker up from `x-init`. A second, inline script used to
     * re-initialise the same input afterwards with different options, so what a user
     * got was whichever ran last.
     */
    public function test_the_picker_is_set_up_once(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('view employees');

        $this->actingAs($user);

        $html = Livewire::test(TimeTable::class, ['employee' => $employee])->html();

        $this->assertSame(1, substr_count($html, 'flatpickr('), 'The picker is initialised more than once.');
        $this->assertStringNotContainsString('console.log', $html);
    }
}
