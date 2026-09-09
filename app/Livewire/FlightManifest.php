<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightLeg;
use App\Models\Passenger;
use App\Services\Flights\FlightDispatchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use RuntimeException;

/**
 * The dispatcher's working screen for one flight.
 *
 * Every leg is shown with its own seated list and waiting list, because seats
 * are counted per leg: the aircraft empties at the field, so filling the
 * inbound leg from Tripoli does not consume seats on the inbound leg from
 * Benghazi.
 */
class FlightManifest extends Component
{
    use AuthorizesRequests;

    public Flight $flight;

    public ?int $selectedLegId = null;

    /** @var 'employee'|'passenger' */
    public string $travellerType = 'employee';

    public ?int $travellerId = null;

    public ?string $note = null;

    /**
     * Passengers are people from the companies working near the field, so they
     * are usually created at the moment they are seated rather than kept in a
     * directory beforehand.
     */
    public bool $showPassengerModal = false;

    public ?string $newPassengerName = null;

    public ?string $newPassengerCompany = null;

    public ?string $newPassengerNumber = null;

    public ?string $newPassengerNationality = null;

    public function mount(Flight $flight): void
    {
        $this->authorize('view', $flight);

        $this->flight = $flight;
        $this->selectedLegId = $flight->legs()->orderBy('sequence')->value('id');
    }

    /**
     * Seating a traveller is a privileged action in its own right, and is
     * checked on the server for every call rather than by hiding the button.
     */
    private function authorizeDispatch(): void
    {
        $this->authorize('dispatchTravellers', $this->flight);
    }

    private function legOrFail(int $legId): FlightLeg
    {
        // Scoped to this flight so a tampered id cannot reach another flight.
        $leg = $this->flight->legs()->whereKey($legId)->firstOrFail();

        assert($leg instanceof FlightLeg);

        return $leg;
    }

    private function bookingOrFail(int $bookingId): FlightBooking
    {
        return FlightBooking::query()
            ->whereKey($bookingId)
            ->whereIn('flight_leg_id', $this->flight->legs()->select('id'))
            ->firstOrFail();
    }

    public function selectLeg(int $legId): void
    {
        $this->selectedLegId = $this->legOrFail($legId)->id;
        $this->reset(['travellerId', 'note']);
    }

    public function addTraveller(): void
    {
        $this->authorizeDispatch();

        $validated = $this->validate([
            'selectedLegId' => ['required', 'integer'],
            'travellerType' => ['required', 'in:employee,passenger'],
            'travellerId' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $leg = $this->legOrFail((int) $validated['selectedLegId']);

        $traveller = $validated['travellerType'] === 'employee'
            ? Employee::query()->findOrFail($validated['travellerId'])
            : Passenger::query()->findOrFail($validated['travellerId']);

        try {
            $booking = app(FlightDispatchService::class)->book(
                $leg,
                $traveller,
                auth()->user(),
                $validated['note'] ?? null
            );
        } catch (RuntimeException $exception) {
            $this->addError('travellerId', $exception->getMessage());

            return;
        }

        $this->reset(['travellerId', 'note']);
        $this->resetValidation();

        session()->flash('manifest_status', $booking->isConfirmed()
            ? 'Seated on '.$leg->label().'.'
            : 'No seats left on '.$leg->label().' - added to the waiting list.');
    }

    public function promote(int $bookingId): void
    {
        $this->authorizeDispatch();

        try {
            app(FlightDispatchService::class)->promote($this->bookingOrFail($bookingId));
        } catch (RuntimeException $exception) {
            $this->addError('manifest', $exception->getMessage());
        }
    }

    public function demote(int $bookingId): void
    {
        $this->authorizeDispatch();

        app(FlightDispatchService::class)->demote($this->bookingOrFail($bookingId));
    }

    public function remove(int $bookingId): void
    {
        $this->authorizeDispatch();

        app(FlightDispatchService::class)->cancel($this->bookingOrFail($bookingId));
    }

    public function openPassengerModal(): void
    {
        $this->authorize('create', Passenger::class);

        $this->reset([
            'newPassengerName',
            'newPassengerCompany',
            'newPassengerNumber',
            'newPassengerNationality',
        ]);
        $this->resetValidation();

        $this->showPassengerModal = true;
    }

    public function closePassengerModal(): void
    {
        $this->showPassengerModal = false;
        $this->resetValidation();
    }

    /**
     * Create a passenger and select them, so the dispatcher can seat someone
     * who has just turned up without leaving the manifest.
     */
    public function createPassenger(): void
    {
        $this->authorize('create', Passenger::class);

        $validated = $this->validate([
            // The CRUD allows a blank name; an unnamed traveller on a manifest
            // is useless, so the quick-add form insists on one.
            'newPassengerName' => ['required', 'string', 'max:255'],
            'newPassengerCompany' => ['nullable', 'string', 'max:255'],
            'newPassengerNumber' => ['nullable', 'string', 'max:255'],
            'newPassengerNationality' => ['nullable', 'string', 'max:255'],
        ], [], [
            'newPassengerName' => 'name',
            'newPassengerCompany' => 'company',
            'newPassengerNumber' => 'number',
            'newPassengerNationality' => 'nationality',
        ]);

        $passenger = Passenger::query()->create([
            'name' => $validated['newPassengerName'],
            'company' => $validated['newPassengerCompany'] ?? null,
            'number' => $validated['newPassengerNumber'] ?? null,
            'nationality' => $validated['newPassengerNationality'] ?? null,
        ]);

        $this->travellerType = 'passenger';
        $this->travellerId = $passenger->id;
        $this->showPassengerModal = false;

        session()->flash('manifest_status', $passenger->name.' added and selected, ready to seat.');
    }

    public function render(): View
    {
        $legs = $this->flight->legs()
            ->with(['fromStation', 'toStation', 'bookings.bookable'])
            ->orderBy('sequence')
            ->get();

        $selectedLeg = $legs->firstWhere('id', $this->selectedLegId) ?? $legs->first();

        return view('livewire.flight-manifest', [
            'legs' => $legs,
            'selectedLeg' => $selectedLeg,
            'confirmed' => $selectedLeg instanceof FlightLeg
                ? $selectedLeg->bookings->where('status', FlightBooking::STATUS_CONFIRMED)->values()
                : collect(),
            'waitlisted' => $selectedLeg instanceof FlightLeg
                ? $selectedLeg->bookings->where('status', FlightBooking::STATUS_WAITLISTED)->values()
                : collect(),
            'employeeOptions' => Employee::query()
                ->whereNull('archived_at')
                ->orderBy('english_name')
                ->limit(500)
                ->get(['id', 'number', 'english_name']),
            'passengerOptions' => Passenger::query()
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'company']),
            'canDispatch' => auth()->user()?->can('dispatchTravellers', $this->flight) ?? false,
            'canCreatePassengers' => auth()->user()?->can('create', Passenger::class) ?? false,
        ]);
    }
}
