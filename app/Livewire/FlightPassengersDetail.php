<?php

namespace App\Livewire;

use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class FlightPassengersDetail extends Component
{
    use AuthorizesRequests;

    public Flight $flight;

    public Passenger $passenger;

    public $passengersForSelect = [];

    public $passenger_id = null;

    public $showingModal = false;

    public $modalTitle = 'New Passenger';

    protected $rules = [
        'passenger_id' => ['required', 'exists:passengers,id'],
    ];

    public function mount(Flight $flight): void
    {
        $this->authorize('view', $flight);

        $this->flight = $flight;
        $this->passengersForSelect = Passenger::query()->orderBy('name')->pluck('name', 'id');
        $this->resetPassengerData();
    }

    public function resetPassengerData(): void
    {
        $this->passenger = new Passenger;

        $this->passenger_id = null;

        $this->dispatch('refresh');
    }

    public function newPassenger(): void
    {
        $this->modalTitle = trans('crud.flight_passengers.new_title');
        $this->resetPassengerData();

        $this->showModal();
    }

    public function showModal(): void
    {
        $this->resetErrorBag();
        $this->showingModal = true;
    }

    public function hideModal(): void
    {
        $this->showingModal = false;
    }

    public function save(): void
    {
        $this->validate();

        $this->authorize('update', $this->flight);
        $this->authorize('view', Passenger::query()->findOrFail($this->passenger_id));

        $this->flight->passengers()->syncWithoutDetaching([$this->passenger_id]);

        $this->hideModal();
    }

    public function detach($passenger): void
    {
        $this->authorize('update', $this->flight);

        $this->flight->passengers()->detach($passenger);

        $this->resetPassengerData();
    }

    public function render(): View
    {
        return view('livewire.flight-passengers-detail', [
            'flightPassengers' => $this->flight
                ->passengers()
                ->withPivot([])
                ->paginate(20),
        ]);
    }
}
