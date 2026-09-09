<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightRoute;
use App\Models\Passenger;
use App\Models\Plane;
use App\Services\Flights\FlightDispatchService;
use Database\Seeders\FlightRoutesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class FlightDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private FlightDispatchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FlightRoutesSeeder::class);
        $this->service = app(FlightDispatchService::class);
    }

    private function route(string $name): FlightRoute
    {
        return FlightRoute::query()->where('name', $name)->firstOrFail();
    }

    private function flightOn(string $routeName, int $seats = 20): Flight
    {
        $flight = Flight::factory()->create([
            'plane_id' => Plane::factory()->seats($seats)->create()->id,
        ]);

        return $this->service->buildLegsFromRoute($flight, $this->route($routeName));
    }

    #[Test]
    public function a_direct_flight_has_one_coming_and_one_leaving_leg(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli');

        $this->assertCount(2, $flight->legs);
        $this->assertSame(['coming', 'leaving'], $flight->legs->pluck('direction')->all());
        $this->assertSame(['TIP → 103A', '103A → TIP'], $flight->legs->map->label()->all());
    }

    #[Test]
    public function a_benghazi_transit_flight_alternates_coming_and_leaving(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Benghazi - 103A - Tripoli');

        $this->assertCount(4, $flight->legs);
        $this->assertSame(
            ['coming', 'leaving', 'coming', 'leaving'],
            $flight->legs->pluck('direction')->all()
        );
    }

    /**
     * A terminal is another field rather than a city, so flying out to it is
     * still "coming" and the middle pair inverts relative to the Benghazi route.
     */
    #[Test]
    public function a_terminal_transit_flight_inverts_the_middle_legs(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Terminal - 103A - Tripoli');

        $this->assertSame(
            ['coming', 'coming', 'leaving', 'leaving'],
            $flight->legs->pluck('direction')->all()
        );

        $this->assertSame(
            ['TIP → 103A', '103A → TERM', 'TERM → 103A', '103A → TIP'],
            $flight->legs->map->label()->all()
        );
    }

    #[Test]
    public function every_leg_takes_its_seat_capacity_from_the_plane(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Benghazi - 103A - Tripoli', seats: 12);

        foreach ($flight->legs as $leg) {
            $this->assertSame(12, $leg->seat_capacity);
        }
    }

    #[Test]
    public function travellers_are_confirmed_until_the_seats_run_out(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 3);
        $leg = $flight->legs->first();

        $bookings = [];
        for ($i = 0; $i < 5; $i++) {
            $bookings[] = $this->service->book($leg, Employee::factory()->create());
        }

        $this->assertSame(
            [
                FlightBooking::STATUS_CONFIRMED,
                FlightBooking::STATUS_CONFIRMED,
                FlightBooking::STATUS_CONFIRMED,
                FlightBooking::STATUS_WAITLISTED,
                FlightBooking::STATUS_WAITLISTED,
            ],
            array_map(fn (FlightBooking $b): string => $b->status, $bookings)
        );

        $this->assertSame(0, $leg->fresh()->seatsRemaining());
    }

    #[Test]
    public function employees_and_external_passengers_share_the_same_seat_count(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 2);
        $leg = $flight->legs->first();

        $first = $this->service->book($leg, Employee::factory()->create());
        $second = $this->service->book($leg, Passenger::factory()->create());
        $third = $this->service->book($leg, Passenger::factory()->create());

        $this->assertTrue($first->isConfirmed());
        $this->assertTrue($second->isConfirmed(), 'A seat is a seat regardless of traveller type.');
        $this->assertTrue($third->isWaitlisted());
    }

    /**
     * The aircraft empties at the field between legs, so filling the Tripoli
     * inbound leg must not consume seats on the Benghazi inbound leg.
     */
    #[Test]
    public function each_leg_has_its_own_independent_seat_count(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Benghazi - 103A - Tripoli', seats: 2);

        $tripoliInbound = $flight->legs[0];
        $benghaziInbound = $flight->legs[2];

        $this->service->book($tripoliInbound, Employee::factory()->create());
        $this->service->book($tripoliInbound, Employee::factory()->create());

        $this->assertSame(0, $tripoliInbound->fresh()->seatsRemaining());
        $this->assertSame(2, $benghaziInbound->fresh()->seatsRemaining());

        $booking = $this->service->book($benghaziInbound, Employee::factory()->create());
        $this->assertTrue($booking->isConfirmed());
    }

    #[Test]
    public function booking_the_same_traveller_twice_does_not_take_a_second_seat(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 5);
        $leg = $flight->legs->first();
        $employee = Employee::factory()->create();

        $first = $this->service->book($leg, $employee);
        $second = $this->service->book($leg, $employee);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, FlightBooking::query()->where('flight_leg_id', $leg->id)->count());
    }

    #[Test]
    public function cancelling_leaves_the_seat_empty_for_the_dispatcher_to_fill(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 1);
        $leg = $flight->legs->first();

        $confirmed = $this->service->book($leg, Employee::factory()->create());
        $waiting = $this->service->book($leg, Employee::factory()->create());

        $this->service->cancel($confirmed);

        // Deliberately not promoted: that is the dispatcher's call.
        $this->assertTrue($waiting->fresh()->isWaitlisted());
        $this->assertSame(1, $leg->fresh()->seatsRemaining());
    }

    #[Test]
    public function the_dispatcher_can_promote_someone_off_the_waiting_list(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 1);
        $leg = $flight->legs->first();

        $confirmed = $this->service->book($leg, Employee::factory()->create());
        $waiting = $this->service->book($leg, Employee::factory()->create());

        $this->service->cancel($confirmed);
        $promoted = $this->service->promote($waiting);

        $this->assertTrue($promoted->isConfirmed());
        $this->assertSame(0, $leg->fresh()->seatsRemaining());
    }

    #[Test]
    public function promotion_is_refused_when_the_leg_is_full(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 1);
        $leg = $flight->legs->first();

        $this->service->book($leg, Employee::factory()->create());
        $waiting = $this->service->book($leg, Employee::factory()->create());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('There is no free seat on this leg.');

        $this->service->promote($waiting);
    }

    #[Test]
    public function demoting_a_confirmed_traveller_frees_their_seat(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 1);
        $leg = $flight->legs->first();

        $confirmed = $this->service->book($leg, Employee::factory()->create());

        $this->service->demote($confirmed);

        $this->assertTrue($confirmed->fresh()->isWaitlisted());
        $this->assertSame(1, $leg->fresh()->seatsRemaining());
    }

    #[Test]
    public function an_archived_employee_cannot_be_booked(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli');
        $leg = $flight->legs->first();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('archived employee');

        $this->service->book($leg, Employee::factory()->create(['archived_at' => now()]));
    }

    #[Test]
    public function a_flight_route_cannot_be_rebuilt_once_travellers_are_booked(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli');
        $this->service->book($flight->legs->first(), Employee::factory()->create());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has booked passengers');

        $this->service->buildLegsFromRoute($flight->fresh(), $this->route('Tripoli - 103A - Benghazi - 103A - Tripoli'));
    }

    #[Test]
    public function a_flight_without_a_seated_plane_cannot_have_legs_built(): void
    {
        $flight = Flight::factory()->create([
            'plane_id' => Plane::factory()->seats(0)->create()->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('seat capacity');

        $this->service->buildLegsFromRoute($flight, $this->route('Tripoli - 103A - Tripoli'));
    }

    #[Test]
    public function the_manifest_separates_seated_and_waiting_travellers_in_order(): void
    {
        $flight = $this->flightOn('Tripoli - 103A - Tripoli', seats: 2);
        $leg = $flight->legs->first();

        $a = Employee::factory()->create(['english_name' => 'Alpha']);
        $b = Passenger::factory()->create(['name' => 'Bravo']);
        $c = Employee::factory()->create(['english_name' => 'Charlie']);

        foreach ([$a, $b, $c] as $traveller) {
            $this->service->book($leg, $traveller);
        }

        $manifest = $this->service->manifest($leg->fresh());

        $this->assertSame(['Alpha', 'Bravo'], $manifest['confirmed']->map->travellerName()->all());
        $this->assertSame(['Charlie'], $manifest['waitlisted']->map->travellerName()->all());
    }
}
