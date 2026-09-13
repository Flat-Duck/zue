<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightDepartmentRegistrationCancelRequest;
use App\Http\Requests\FlightDepartmentRegistrationRequest;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightLeg;
use App\Services\Flights\DepartmentFlightRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class FlightDepartmentRegistrationController extends Controller
{
    public function __construct(private readonly DepartmentFlightRegistrationService $registrationService) {}

    public function show(Request $request, Flight $flight): View
    {
        $this->authorize('registerDepartmentEmployees', $flight);

        $actor = $request->user();
        $flight->load(['plane', 'route', 'legs.fromStation', 'legs.toStation']);
        $employeeMorphClass = (new Employee)->getMorphClass();

        $registeredEmployeeIds = FlightBooking::query()
            ->join('flight_legs', 'flight_bookings.flight_leg_id', '=', 'flight_legs.id')
            ->where('flight_legs.flight_id', $flight->id)
            ->where('flight_bookings.bookable_type', $employeeMorphClass)
            ->pluck('flight_bookings.bookable_id')
            ->all();

        $employees = $this->registrationService
            ->bookableEmployeesFor($actor)
            ->whereNotIn('id', $registeredEmployeeIds)
            ->with(['department:id,name', 'location:id,name', 'center:id,name'])
            ->orderBy('english_name')
            ->orderBy('number')
            ->get(['id', 'english_name', 'arabic_name', 'number', 'department_id', 'location_id', 'center_id']);

        $registeredByLeg = $this->registeredEmployeesByLeg($flight, $actor->id);
        $legs = $flight->legs->map(function (FlightLeg $leg) use ($registeredByLeg): array {
            return [
                'id' => $leg->id,
                'label' => $leg->label(),
                'direction' => $leg->direction,
                'remaining' => max(0, $leg->seat_capacity - $this->registrationService->seatsUsedOnLeg($leg)),
                'capacity' => $leg->seat_capacity,
                'registered' => $registeredByLeg[$leg->id] ?? collect(),
            ];
        });

        $quota = $this->registrationService->quotaForActor($flight, $actor);
        $usedQuota = $this->registrationService->usedQuotaForActor($flight, $actor);

        return view('app.flights.department-registration', [
            'flight' => $flight,
            'employees' => $employees,
            'legs' => $legs,
            'quota' => $quota,
            'usedQuota' => $usedQuota,
            'remainingQuota' => max(0, $quota - $usedQuota),
        ]);
    }

    public function store(FlightDepartmentRegistrationRequest $request, Flight $flight): RedirectResponse
    {
        $this->authorize('registerDepartmentEmployees', $flight);

        try {
            $this->registrationService->bookEmployee(
                $flight,
                FlightLeg::query()->findOrFail($request->integer('flight_leg_id')),
                Employee::query()->findOrFail($request->integer('employee_id')),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['registration' => $exception->getMessage()]);
        }

        return redirect()
            ->route('flights.department-registration.show', ['flight' => $flight, 'leg' => $request->integer('flight_leg_id')])
            ->withSuccess(__('flights.registration_saved'));
    }

    public function destroy(FlightDepartmentRegistrationCancelRequest $request, Flight $flight, FlightBooking $booking): RedirectResponse
    {
        $this->authorize('registerDepartmentEmployees', $flight);

        $legId = (int) $booking->flight_leg_id;

        try {
            $this->registrationService->cancelBooking($flight, $booking, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['registration' => $exception->getMessage()]);
        }

        return redirect()
            ->route('flights.department-registration.show', ['flight' => $flight, 'leg' => $legId])
            ->withSuccess(__('flights.registration_cancelled'));
    }

    /**
     * @return array<int, Collection<int, FlightBooking>>
     */
    private function registeredEmployeesByLeg(Flight $flight, int $actorId): array
    {
        $bookings = FlightBooking::query()
            ->whereIn('flight_leg_id', $flight->legs->pluck('id')->all())
            ->where('bookable_type', (new Employee)->getMorphClass())
            ->where('booked_by_user_id', $actorId)
            ->with(['bookable'])
            ->orderBy('sequence')
            ->get();

        foreach ($bookings as $booking) {
            if ($booking->bookable instanceof Employee) {
                $booking->bookable->loadMissing(['department:id,name', 'location:id,name', 'center:id,name']);
            }
        }

        $registered = [];

        foreach ($bookings as $booking) {
            $legId = (int) $booking->flight_leg_id;
            $registered[$legId] ??= collect();
            $registered[$legId]->push($booking);
        }

        return $registered;
    }
}
