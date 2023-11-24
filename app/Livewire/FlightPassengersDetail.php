<?php

namespace App\Livewire;

use App\Models\Flight;
use Livewire\Component;
use Illuminate\View\View;
use App\Models\Passenger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        $this->flight = $flight;
        $this->passengersForSelect = Passenger::pluck('name', 'id');
        $this->resetPassengerData();
    }

    public function resetPassengerData(): void
    {
        $this->passenger = new Passenger();

        $this->passenger_id = null;

        $this->dispatch('refresh');
    }

    public function newPassenger(): void
    {

        $this->modalTitle = trans('crud.flight_passengers.new_title');
      //  $this->resetPassengerData();

        $this->showModal();
    }

    public function showModal(): void
    {
        //$this->resetErrorBag();
        $this->showingModal = true;
    }

    public function hideModal(): void
    {
        $this->showingModal = false;
    }

    public function save(): void
    {
        $this->validate();

        $this->authorize('create', Passenger::class);

        $this->flight->passengers()->attach($this->passenger_id, []);

        $this->hideModal();
    }

    public function detach($passenger): void
    {
        $this->authorize('delete-any', Passenger::class);

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
