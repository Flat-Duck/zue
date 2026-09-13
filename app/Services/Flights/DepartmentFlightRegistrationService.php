<?php

namespace App\Services\Flights;

use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightLeg;
use App\Models\ScopeContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DepartmentFlightRegistrationService
{
    public function __construct(private readonly FlightDispatchService $dispatchService) {}

    public function quotaForActor(Flight $flight, User $actor): int
    {
        $totalEmployees = $this->bookableEmployeesFor($actor)->count();
        $seats = $this->seatLimit($flight);

        if ($seats < 1 || $totalEmployees < 1) {
            return 0;
        }

        $quota = (int) ceil((0.8 * $totalEmployees * $seats) / 1000);
        $quota = max(1, $quota);

        return min($quota, $seats);
    }

    public function usedQuotaForActor(Flight $flight, User $actor): int
    {
        $employeeIds = $this->bookableEmployeesFor($actor)->pluck('id');

        if ($employeeIds->isEmpty()) {
            return 0;
        }

        return FlightBooking::query()
            ->join('flight_legs', 'flight_bookings.flight_leg_id', '=', 'flight_legs.id')
            ->where('flight_legs.flight_id', $flight->id)
            ->where('flight_bookings.bookable_type', (new Employee)->getMorphClass())
            ->whereIn('flight_bookings.bookable_id', $employeeIds->all())
            ->distinct('flight_bookings.bookable_id')
            ->count('flight_bookings.bookable_id');
    }

    /**
     * @return Builder<Employee>
     */
    public function bookableEmployeesFor(User $actor): Builder
    {
        return $actor
            ->managedEmployeesQuery(ScopeContext::DISPATCHER)
            ->whereNull('archived_at');
    }

    public function seatsUsedOnLeg(FlightLeg $leg): int
    {
        return FlightBooking::query()
            ->where('flight_leg_id', $leg->id)
            ->where('status', FlightBooking::STATUS_CONFIRMED)
            ->count();
    }

    public function bookEmployee(Flight $flight, FlightLeg $leg, Employee $employee, User $actor): FlightBooking
    {
        return DB::transaction(function () use ($flight, $leg, $employee, $actor): FlightBooking {
            $lockedLeg = FlightLeg::query()
                ->where('flight_id', $flight->id)
                ->lockForUpdate()
                ->findOrFail($leg->id);

            $freshFlight = Flight::query()->with('plane')->findOrFail($flight->id);
            $freshEmployee = Employee::query()->findOrFail($employee->id);
            if (! $freshFlight->registrationIsOpen()) {
                throw new RuntimeException(__('flights.registration_closed'));
            }

            if ($freshEmployee->archived_at !== null) {
                throw new RuntimeException(__('flights.registration_employee_archived'));
            }

            $this->guardEmployeeIsInActorContext($freshEmployee, $actor);
            $this->guardEmployeeIsNotAlreadyRegistered($freshFlight, $freshEmployee);
            $this->guardQuota($freshFlight, $actor);
            $this->guardLegHasSeat($lockedLeg);

            $booking = $this->dispatchService->book(
                $lockedLeg,
                $freshEmployee,
                $actor,
                __('flights.department_registration_note')
            );

            if ($booking->isWaitlisted()) {
                $booking->delete();
                throw new RuntimeException(__('flights.registration_no_seats'));
            }

            return $booking;
        });
    }

    public function cancelBooking(Flight $flight, FlightBooking $booking, User $actor): void
    {
        $booking->loadMissing('leg');

        if ((int) $booking->booked_by_user_id !== (int) $actor->id) {
            throw new RuntimeException(__('flights.registration_cancel_not_owner'));
        }

        if (! $booking->leg || (int) $booking->leg->flight_id !== (int) $flight->id) {
            throw new RuntimeException(__('flights.registration_booking_not_on_flight'));
        }

        if (! $flight->registrationIsOpen()) {
            throw new RuntimeException(__('flights.registration_closed'));
        }

        $this->dispatchService->cancel($booking);
    }

    private function seatLimit(Flight $flight): int
    {
        if ($flight->relationLoaded('plane')) {
            return (int) $flight->plane->capacity;
        }

        return (int) ($flight->plane()->value('capacity') ?? 0);
    }

    private function guardEmployeeIsNotAlreadyRegistered(Flight $flight, Employee $employee): void
    {
        $employeeMorphClass = $employee->getMorphClass();
        $exists = FlightBooking::query()
            ->join('flight_legs', 'flight_bookings.flight_leg_id', '=', 'flight_legs.id')
            ->where('flight_legs.flight_id', $flight->id)
            ->where('flight_bookings.bookable_type', $employeeMorphClass)
            ->where('flight_bookings.bookable_id', $employee->id)
            ->exists();

        if ($exists) {
            throw new RuntimeException(__('flights.registration_employee_already_registered'));
        }
    }

    private function guardEmployeeIsInActorContext(Employee $employee, User $actor): void
    {
        if (! $this->bookableEmployeesFor($actor)->whereKey($employee->id)->exists()) {
            throw new RuntimeException(__('flights.registration_wrong_dispatcher_context'));
        }
    }

    private function guardQuota(Flight $flight, User $actor): void
    {
        if ($this->usedQuotaForActor($flight, $actor) >= $this->quotaForActor($flight, $actor)) {
            throw new RuntimeException(__('flights.registration_quota_reached'));
        }
    }

    private function guardLegHasSeat(FlightLeg $leg): void
    {
        if ($this->seatsUsedOnLeg($leg) >= $leg->seat_capacity) {
            throw new RuntimeException(__('flights.registration_no_seats'));
        }
    }
}
