<?php

namespace App\Contracts;

use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightLeg;
use App\Models\FlightRoute;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Seat allocation on a flight leg.
 *
 * The one place manifests change, so controllers and Livewire components depend
 * on this rather than on the implementation.
 */
interface FlightDispatchContract
{
    public function buildLegsFromRoute(Flight $flight, FlightRoute $route): Flight;

    public function book(FlightLeg $leg, Employee|Passenger $traveller, ?User $actor = null, ?string $note = null): FlightBooking;

    public function cancel(FlightBooking $booking): void;

    public function promote(FlightBooking $booking): FlightBooking;

    public function demote(FlightBooking $booking): FlightBooking;

    /**
     * @return array<string, Collection<int, FlightBooking>>
     */
    public function manifest(FlightLeg $leg): array;
}
