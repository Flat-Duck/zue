<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightRoute;
use App\Models\Plane;
use App\Models\ScopeContext;
use App\Models\ScopePolicyCriterion;
use App\Models\User;
use App\Services\Flights\FlightDispatchService;
use Carbon\Carbon;
use Database\Seeders\FlightRoutesSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\BuildsScopes;
use Tests\TestCase;

class FlightDepartmentRegistrationTest extends TestCase
{
    use BuildsScopes;
    use RefreshDatabase;

    private FlightDispatchService $dispatchService;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-16 10:00:00'));
        $this->seed(PermissionsSeeder::class);
        $this->seed(FlightRoutesSeeder::class);
        $this->dispatchService = app(FlightDispatchService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function department_user_can_register_employee_from_dispatcher_context_during_open_window(): void
    {
        [$flight, $department, $actor] = $this->openFlightForDepartment(seats: 10, employeeCount: 20);
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $leg = $flight->legs->first();

        $this->actingAs($actor)
            ->post(route('flights.department-registration.store', $flight), [
                'flight_leg_id' => $leg->id,
                'employee_id' => $employee->id,
            ])
            ->assertRedirect(route('flights.department-registration.show', ['flight' => $flight, 'leg' => $leg->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('flight_bookings', [
            'flight_leg_id' => $leg->id,
            'bookable_type' => $employee->getMorphClass(),
            'bookable_id' => $employee->id,
            'status' => FlightBooking::STATUS_CONFIRMED,
            'booked_by_user_id' => $actor->id,
        ]);
    }

    #[Test]
    public function department_user_cannot_register_employee_outside_dispatcher_context(): void
    {
        [$flight, , $actor] = $this->openFlightForDepartment(buildGlobalDispatcherScope: false);
        $outsideDepartment = Department::factory()->create(['name' => 'Other']);
        $employee = Employee::factory()->create(['department_id' => $outsideDepartment->id]);

        $this->actingAs($actor)
            ->post(route('flights.department-registration.store', $flight), [
                'flight_leg_id' => $flight->legs->first()->id,
                'employee_id' => $employee->id,
            ])
            ->assertSessionHasErrors('registration');

        $this->assertDatabaseMissing('flight_bookings', [
            'bookable_id' => $employee->id,
        ]);
    }

    #[Test]
    public function department_registration_is_rejected_outside_the_window(): void
    {
        [$flight, $department, $actor] = $this->openFlightForDepartment();
        $flight->update([
            'registration_opens_at' => now()->addDay(),
            'registration_closes_at' => now()->addDays(2),
        ]);
        $employee = Employee::factory()->create(['department_id' => $department->id]);

        $this->actingAs($actor)
            ->post(route('flights.department-registration.store', $flight), [
                'flight_leg_id' => $flight->legs->first()->id,
                'employee_id' => $employee->id,
            ])
            ->assertSessionHasErrors('registration');

        $this->assertDatabaseMissing('flight_bookings', [
            'bookable_id' => $employee->id,
        ]);
    }

    #[Test]
    public function department_registration_stops_at_dispatcher_context_quota(): void
    {
        [$flight, $department, $actor] = $this->openFlightForDepartment(seats: 10, employeeCount: 200);
        $leg = $flight->legs->first();
        $employees = Employee::factory()->count(3)->create(['department_id' => $department->id]);

        foreach ($employees->take(2) as $employee) {
            $this->actingAs($actor)->post(route('flights.department-registration.store', $flight), [
                'flight_leg_id' => $leg->id,
                'employee_id' => $employee->id,
            ])->assertSessionHasNoErrors();
        }

        $this->actingAs($actor)
            ->post(route('flights.department-registration.store', $flight), [
                'flight_leg_id' => $leg->id,
                'employee_id' => $employees[2]->id,
            ])
            ->assertSessionHasErrors('registration');

        $this->assertSame(2, FlightBooking::query()->count());
    }

    #[Test]
    public function department_registration_stops_when_the_leg_has_no_seats(): void
    {
        [$flight, $department, $actor] = $this->openFlightForDepartment(seats: 1, employeeCount: 2000);
        $leg = $flight->legs->first();
        $employees = Employee::factory()->count(2)->create(['department_id' => $department->id]);

        $this->actingAs($actor)->post(route('flights.department-registration.store', $flight), [
            'flight_leg_id' => $leg->id,
            'employee_id' => $employees[0]->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($actor)
            ->post(route('flights.department-registration.store', $flight), [
                'flight_leg_id' => $leg->id,
                'employee_id' => $employees[1]->id,
            ])
            ->assertSessionHasErrors('registration');

        $this->assertSame(1, FlightBooking::query()->count());
    }

    #[Test]
    public function registration_page_groups_work_by_leg_and_lists_employees_registered_by_the_user(): void
    {
        [$flight, $department, $actor] = $this->openFlightForDepartment();
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $leg = $flight->legs->first();

        $this->actingAs($actor)->post(route('flights.department-registration.store', $flight), [
            'flight_leg_id' => $leg->id,
            'employee_id' => $employee->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($actor)
            ->get(route('flights.department-registration.show', ['flight' => $flight, 'leg' => $leg->id]))
            ->assertOk()
            ->assertSee($leg->label())
            ->assertSee(__('flights.registered_by_you'))
            ->assertSee((string) $employee->number)
            ->assertSee($employee->english_name);
    }

    #[Test]
    public function department_user_can_cancel_a_registration_they_created(): void
    {
        [$flight, $department, $actor] = $this->openFlightForDepartment();
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $leg = $flight->legs->first();

        $this->actingAs($actor)->post(route('flights.department-registration.store', $flight), [
            'flight_leg_id' => $leg->id,
            'employee_id' => $employee->id,
        ])->assertSessionHasNoErrors();

        $booking = FlightBooking::query()->firstOrFail();

        $this->actingAs($actor)
            ->delete(route('flights.department-registration.destroy', [$flight, $booking]))
            ->assertRedirect(route('flights.department-registration.show', ['flight' => $flight, 'leg' => $leg->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('flight_bookings', ['id' => $booking->id]);
    }

    #[Test]
    public function department_user_cannot_cancel_someone_elses_registration(): void
    {
        [$flight, $department, $actor] = $this->openFlightForDepartment();
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $leg = $flight->legs->first();
        $otherActor = User::factory()->create();

        FlightBooking::query()->create([
            'flight_leg_id' => $leg->id,
            'bookable_type' => $employee->getMorphClass(),
            'bookable_id' => $employee->id,
            'status' => FlightBooking::STATUS_CONFIRMED,
            'sequence' => 1,
            'booked_by_user_id' => $otherActor->id,
        ]);

        $this->actingAs($actor)
            ->delete(route('flights.department-registration.destroy', [$flight, FlightBooking::query()->firstOrFail()]))
            ->assertSessionHasErrors('registration');

        $this->assertSame(1, FlightBooking::query()->count());
    }

    #[Test]
    public function registration_window_uses_the_application_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 12:18:00', config('app.timezone')));

        [$flight] = $this->openFlightForDepartment();

        $flight->update([
            'registration_opens_at' => '2026-09-13 12:16:00',
            'registration_closes_at' => '2026-09-13 12:25:00',
        ]);

        $this->assertTrue($flight->fresh()->registrationIsOpen());
    }

    /**
     * @return array{0: Flight, 1: Department, 2: User}
     */
    private function openFlightForDepartment(int $seats = 20, int $employeeCount = 5, bool $buildGlobalDispatcherScope = true): array
    {
        $department = Department::factory()->create(['name' => 'Gasplant']);
        Employee::factory()->count($employeeCount)->create(['department_id' => $department->id]);
        $actorEmployee = Employee::factory()->create(['department_id' => $department->id]);
        $actor = User::factory()->forEmployee($actorEmployee)->create();
        $actor->assignRole('user');

        if ($buildGlobalDispatcherScope) {
            $this->buildGlobalScope([$actorEmployee], ScopeContext::DISPATCHER);
        } else {
            $this->buildScope(
                name: 'Gasplant dispatcher context',
                criteria: [ScopePolicyCriterion::DEPARTMENT => [$department->id]],
                actors: [$actorEmployee],
                context: ScopeContext::DISPATCHER,
            );
        }

        $flight = Flight::factory()->create([
            'plane_id' => Plane::factory()->seats($seats)->create()->id,
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDay(),
        ]);

        $route = FlightRoute::query()->where('name', 'Tripoli - 103A - Tripoli')->firstOrFail();
        $flight = $this->dispatchService->buildLegsFromRoute($flight->load('plane'), $route);

        return [$flight, $department, $actor];
    }
}
