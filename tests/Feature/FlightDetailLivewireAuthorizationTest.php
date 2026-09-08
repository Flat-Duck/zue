<?php

namespace Tests\Feature;

use App\Livewire\FlightEmployeesDetail;
use App\Livewire\FlightPassengersDetail;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FlightDetailLivewireAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'view flights',
            'update flights',
            'view employees',
            'create employees',
            'delete employees',
            'view passengers',
            'create passengers',
            'delete passengers',
        ] as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    public function test_user_without_flight_view_permission_cannot_mount_employee_detail_component(): void
    {
        $flight = Flight::factory()->create();

        $this->actingAs(User::factory()->create());

        Livewire::test(FlightEmployeesDetail::class, ['flight' => $flight])
            ->assertForbidden();
    }

    public function test_employee_detail_requires_flight_update_permission_before_attaching_employee(): void
    {
        $flight = Flight::factory()->create();
        $employee = Employee::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo(['view flights', 'view employees']);

        $this->actingAs($user);

        Livewire::test(FlightEmployeesDetail::class, ['flight' => $flight])
            ->set('employee_id', $employee->id)
            ->call('save')
            ->assertForbidden();
    }

    public function test_employee_detail_does_not_create_duplicate_flight_employee_rows(): void
    {
        $flight = Flight::factory()->create();
        $employee = Employee::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo(['view flights', 'update flights', 'view employees']);

        $this->actingAs($user);

        Livewire::test(FlightEmployeesDetail::class, ['flight' => $flight])
            ->set('employee_id', $employee->id)
            ->call('save')
            ->call('save');

        $this->assertSame(1, $flight->employees()->whereKey($employee->id)->count());
    }

    public function test_passenger_detail_does_not_create_duplicate_flight_passenger_rows(): void
    {
        $flight = Flight::factory()->create();
        $passenger = Passenger::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo(['view flights', 'update flights', 'view passengers']);

        $this->actingAs($user);

        Livewire::test(FlightPassengersDetail::class, ['flight' => $flight])
            ->set('passenger_id', $passenger->id)
            ->call('save')
            ->call('save');

        $this->assertSame(1, $flight->passengers()->whereKey($passenger->id)->count());
    }
}
