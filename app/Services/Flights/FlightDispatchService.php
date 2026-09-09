<?php

namespace App\Services\Flights;

use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightLeg;
use App\Models\FlightRoute;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single place flight manifests are changed.
 *
 * Seat allocation is the whole point of this class: whether a traveller is
 * confirmed or waitlisted must be decided against a locked view of the leg, or
 * two dispatchers booking the last seat at the same moment will both be told
 * they got it.
 */
class FlightDispatchService
{
    /**
     * Copy a route onto a flight as its legs.
     *
     * The copy is deliberate. Routes stay editable, so a flight has to keep the
     * itinerary it actually flew rather than following later edits.
     */
    public function buildLegsFromRoute(Flight $flight, FlightRoute $route): Flight
    {
        return DB::transaction(function () use ($flight, $route): Flight {
            $capacity = (int) ($flight->plane->capacity ?? 0);

            if ($capacity < 1) {
                throw new RuntimeException('The flight needs a plane with a seat capacity before legs can be built.');
            }

            if ($flight->legs()->whereHas('bookings')->exists()) {
                throw new RuntimeException('This flight already has booked passengers, so its route cannot be rebuilt.');
            }

            $flight->legs()->delete();

            foreach ($route->legs as $routeLeg) {
                $flight->legs()->create([
                    'sequence' => $routeLeg->sequence,
                    'from_station_id' => $routeLeg->from_station_id,
                    'to_station_id' => $routeLeg->to_station_id,
                    'direction' => $routeLeg->direction,
                    'seat_capacity' => $capacity,
                ]);
            }

            $flight->flight_route_id = $route->id;
            $flight->save();

            return $flight->load('legs');
        });
    }

    /**
     * Put a traveller on a leg.
     *
     * Returns the booking. Its status says whether they have a seat or a place
     * in the queue; the caller should not assume they got one.
     */
    public function book(FlightLeg $leg, Employee|Passenger $traveller, ?User $actor = null, ?string $note = null): FlightBooking
    {
        $this->guardTravellerIsBookable($traveller);

        return DB::transaction(function () use ($leg, $traveller, $actor, $note): FlightBooking {
            // Lock the leg so concurrent bookings cannot both read the same
            // remaining-seat count and both be confirmed.
            $lockedLeg = FlightLeg::query()->lockForUpdate()->findOrFail($leg->id);

            $existing = FlightBooking::query()
                ->where('flight_leg_id', $lockedLeg->id)
                ->where('bookable_type', $traveller->getMorphClass())
                ->where('bookable_id', $traveller->getKey())
                ->first();

            if ($existing) {
                return $existing;
            }

            $confirmedCount = FlightBooking::query()
                ->where('flight_leg_id', $lockedLeg->id)
                ->where('status', FlightBooking::STATUS_CONFIRMED)
                ->count();

            $nextSequence = (int) FlightBooking::query()
                ->where('flight_leg_id', $lockedLeg->id)
                ->max('sequence') + 1;

            return FlightBooking::query()->create([
                'flight_leg_id' => $lockedLeg->id,
                'bookable_type' => $traveller->getMorphClass(),
                'bookable_id' => $traveller->getKey(),
                'status' => $confirmedCount < $lockedLeg->seat_capacity
                    ? FlightBooking::STATUS_CONFIRMED
                    : FlightBooking::STATUS_WAITLISTED,
                'sequence' => $nextSequence,
                'booked_by_user_id' => $actor?->getAuthIdentifier(),
                'note' => $note,
            ]);
        });
    }

    /**
     * Remove a traveller from a leg.
     *
     * A freed seat is deliberately left empty: promotion off the waiting list is
     * the dispatcher's decision, not an automatic one.
     */
    public function cancel(FlightBooking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $booking->delete();
        });
    }

    /**
     * Move a waiting traveller into a seat.
     */
    public function promote(FlightBooking $booking): FlightBooking
    {
        return DB::transaction(function () use ($booking): FlightBooking {
            $leg = FlightLeg::query()->lockForUpdate()->findOrFail($booking->flight_leg_id);

            $fresh = FlightBooking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($fresh->isConfirmed()) {
                return $fresh;
            }

            $confirmedCount = FlightBooking::query()
                ->where('flight_leg_id', $leg->id)
                ->where('status', FlightBooking::STATUS_CONFIRMED)
                ->count();

            if ($confirmedCount >= $leg->seat_capacity) {
                throw new RuntimeException('There is no free seat on this leg.');
            }

            $fresh->update(['status' => FlightBooking::STATUS_CONFIRMED]);

            return $fresh;
        });
    }

    /**
     * Move a confirmed traveller back to the waiting list, freeing their seat.
     */
    public function demote(FlightBooking $booking): FlightBooking
    {
        return DB::transaction(function () use ($booking): FlightBooking {
            $fresh = FlightBooking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($fresh->isWaitlisted()) {
                return $fresh;
            }

            $fresh->update(['status' => FlightBooking::STATUS_WAITLISTED]);

            return $fresh;
        });
    }

    /**
     * The manifest for one leg, in registration order.
     *
     * @return array<string, Collection<int, FlightBooking>>
     */
    public function manifest(FlightLeg $leg): array
    {
        $bookings = $leg->bookings()->with('bookable')->get();

        return [
            'confirmed' => $bookings->where('status', FlightBooking::STATUS_CONFIRMED)->values(),
            'waitlisted' => $bookings->where('status', FlightBooking::STATUS_WAITLISTED)->values(),
        ];
    }

    private function guardTravellerIsBookable(Model $traveller): void
    {
        if ($traveller instanceof Employee && $traveller->archived_at !== null) {
            throw new RuntimeException('An archived employee cannot be booked onto a flight.');
        }
    }
}
