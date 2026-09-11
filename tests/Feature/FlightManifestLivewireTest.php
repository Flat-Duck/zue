<?php

namespace Tests\Feature;

use App\Livewire\FlightManifest;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightRoute;
use App\Models\Location;
use App\Models\Passenger;
use App\Models\Plane;
use App\Models\ScopeContext;
use App\Models\User;
use App\Services\Flights\FlightDispatchService;
use Database\Seeders\FlightRoutesSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\BuildsScopes;
use Tests\TestCase;

class FlightManifestLivewireTest extends TestCase
{
    use BuildsScopes;
    use RefreshDatabase;

    private Flight $flight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FlightRoutesSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view flights', 'list flights', 'dispatch flights', 'create passengers'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $flight = Flight::factory()->create([
            'plane_id' => Plane::factory()->seats(2)->create()->id,
        ]);

        $this->flight = app(FlightDispatchService::class)->buildLegsFromRoute(
            $flight,
            FlightRoute::query()->where('name', 'Tripoli - 103A - Benghazi - 103A - Tripoli')->firstOrFail()
        );
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view flights', 'list flights']);

        return $user;
    }

    /**
     * A dispatcher books across fields, so who they may seat comes from a scope in
     * the dispatcher context rather than from the time sheet hierarchy.
     */
    private function dispatcher(): User
    {
        $user = $this->viewer();
        $user->givePermissionTo(['dispatch flights', 'create passengers']);

        $this->buildGlobalScope([$user->employee], ScopeContext::DISPATCHER);

        return $user;
    }

    /**
     * A dispatcher whose scope covers one field only.
     */
    private function dispatcherFor(Location $field): User
    {
        $user = $this->viewer();
        $user->givePermissionTo(['dispatch flights', 'create passengers']);

        $this->buildScope(
            name: 'One field',
            criteria: ['field' => [$field->id]],
            actors: [$user->employee],
            context: ScopeContext::DISPATCHER,
        );

        return $user;
    }

    #[Test]
    public function the_flight_show_page_renders_the_manifest(): void
    {
        $this->actingAs($this->dispatcher())
            ->get(route('flights.show', $this->flight))
            ->assertOk()
            ->assertSeeLivewire(FlightManifest::class)
            ->assertSee('Manifest')
            ->assertSee('Tripoli - 103A - Benghazi - 103A - Tripoli')
            // Both lists and the per-leg seat counter must be on the page.
            ->assertSee('Seated')
            ->assertSee('Waiting list')
            ->assertSee('Coming')
            ->assertSee('Leaving')
            ->assertSee('0/2')
            ->assertSee('seats free');
    }

    #[Test]
    public function a_dispatcher_can_create_a_passenger_without_leaving_the_manifest(): void
    {
        Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->call('openPassengerModal')
            ->assertSet('showPassengerModal', true)
            ->set('newPassengerName', 'Ali Contractor')
            ->set('newPassengerCompany', 'Field Services Ltd')
            ->set('newPassengerNumber', 'FS-9931')
            ->set('newPassengerNationality', 'Libyan')
            ->call('createPassenger')
            ->assertHasNoErrors()
            ->assertSet('showPassengerModal', false)
            // The new passenger is selected, ready to be seated immediately.
            ->assertSet('travellerType', 'passenger');

        $this->assertDatabaseHas('passengers', [
            'name' => 'Ali Contractor',
            'company' => 'Field Services Ltd',
            'number' => 'FS-9931',
            'nationality' => 'Libyan',
        ]);
    }

    #[Test]
    public function a_newly_created_passenger_can_be_seated_straight_away(): void
    {
        $leg = $this->flight->legs->first();

        Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('selectedLegId', $leg->id)
            ->call('openPassengerModal')
            ->set('newPassengerName', 'Walk-up Passenger')
            ->call('createPassenger')
            ->call('addTraveller')
            ->assertHasNoErrors();

        $passenger = Passenger::query()->where('name', 'Walk-up Passenger')->firstOrFail();

        $this->assertDatabaseHas('flight_bookings', [
            'flight_leg_id' => $leg->id,
            'bookable_type' => $passenger->getMorphClass(),
            'bookable_id' => $passenger->id,
            'status' => FlightBooking::STATUS_CONFIRMED,
        ]);
    }

    #[Test]
    public function a_passenger_needs_a_name(): void
    {
        Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->call('openPassengerModal')
            ->set('newPassengerName', '')
            ->call('createPassenger')
            ->assertHasErrors(['newPassengerName' => 'required']);

        $this->assertDatabaseCount('passengers', 0);
    }

    #[Test]
    public function a_user_without_permission_cannot_create_a_passenger(): void
    {
        Livewire::actingAs($this->viewer())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->call('openPassengerModal')
            ->assertForbidden();

        $this->assertDatabaseCount('passengers', 0);
    }

    #[Test]
    public function passenger_creation_itself_is_authorised_not_just_the_button(): void
    {
        // Skipping the modal and calling the action directly must still fail.
        Livewire::actingAs($this->viewer())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('newPassengerName', 'Should Not Exist')
            ->call('createPassenger')
            ->assertForbidden();

        $this->assertDatabaseMissing('passengers', ['name' => 'Should Not Exist']);
    }

    #[Test]
    public function the_quick_add_button_appears_only_for_passengers(): void
    {
        $component = Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight]);

        // Employees come from HR and are never created here.
        $component->set('travellerType', 'employee')->assertDontSee('New passenger');

        $component->set('travellerType', 'passenger')->assertSee('New passenger');
    }

    #[Test]
    public function the_traveller_select_is_a_searchable_advanced_select(): void
    {
        Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            // Tom Select is bound through Alpine inside wire:ignore so Livewire
            // does not fight it over the DOM.
            ->assertSee('searchableSelect', false)
            ->assertSee('wire:ignore', false)
            ->assertSee('data-model="travellerId"', false);
    }

    #[Test]
    public function it_shows_every_leg_of_the_flight(): void
    {
        Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->assertOk()
            ->assertSee('TIP → 103A')
            ->assertSee('103A → BEN')
            ->assertSee('BEN → 103A')
            ->assertSee('103A → TIP');
    }

    #[Test]
    public function a_dispatcher_can_seat_a_traveller(): void
    {
        $employee = Employee::factory()->create(['english_name' => 'Seated Person']);
        $leg = $this->flight->legs->first();

        Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('selectedLegId', $leg->id)
            ->set('travellerType', 'employee')
            ->set('travellerId', $employee->id)
            ->call('addTraveller')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('flight_bookings', [
            'flight_leg_id' => $leg->id,
            'bookable_id' => $employee->id,
            'status' => FlightBooking::STATUS_CONFIRMED,
        ]);
    }

    #[Test]
    public function travellers_beyond_capacity_go_to_the_waiting_list(): void
    {
        $leg = $this->flight->legs->first();
        $component = Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('selectedLegId', $leg->id);

        // The plane seats two.
        foreach (range(1, 3) as $i) {
            $component
                ->set('travellerType', 'employee')
                ->set('travellerId', Employee::factory()->create()->id)
                ->call('addTraveller');
        }

        $this->assertSame(2, FlightBooking::query()
            ->where('flight_leg_id', $leg->id)
            ->where('status', FlightBooking::STATUS_CONFIRMED)
            ->count());

        $this->assertSame(1, FlightBooking::query()
            ->where('flight_leg_id', $leg->id)
            ->where('status', FlightBooking::STATUS_WAITLISTED)
            ->count());
    }

    #[Test]
    public function a_user_without_dispatch_permission_cannot_seat_anyone(): void
    {
        $employee = Employee::factory()->create();
        $leg = $this->flight->legs->first();

        Livewire::actingAs($this->viewer())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('selectedLegId', $leg->id)
            ->set('travellerType', 'employee')
            ->set('travellerId', $employee->id)
            ->call('addTraveller')
            ->assertForbidden();

        $this->assertDatabaseCount('flight_bookings', 0);
    }

    #[Test]
    public function a_user_without_dispatch_permission_cannot_promote_or_remove(): void
    {
        $leg = $this->flight->legs->first();
        $booking = app(FlightDispatchService::class)->book($leg, Employee::factory()->create());

        Livewire::actingAs($this->viewer())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->call('remove', $booking->id)
            ->assertForbidden();

        $this->assertDatabaseHas('flight_bookings', ['id' => $booking->id]);
    }

    /**
     * The component must not act on a booking that belongs to another flight,
     * however the id arrives.
     */
    #[Test]
    public function a_booking_from_another_flight_cannot_be_touched(): void
    {
        $otherFlight = app(FlightDispatchService::class)->buildLegsFromRoute(
            Flight::factory()->create(['plane_id' => Plane::factory()->seats(5)->create()->id]),
            FlightRoute::query()->where('name', 'Tripoli - 103A - Tripoli')->firstOrFail()
        );

        $foreignBooking = app(FlightDispatchService::class)->book(
            $otherFlight->legs->first(),
            Employee::factory()->create()
        );

        $this->expectException(ModelNotFoundException::class);

        try {
            Livewire::actingAs($this->dispatcher())
                ->test(FlightManifest::class, ['flight' => $this->flight])
                ->call('remove', $foreignBooking->id);
        } finally {
            // The other flight's booking must survive regardless.
            $this->assertDatabaseHas('flight_bookings', ['id' => $foreignBooking->id]);
        }
    }

    #[Test]
    public function a_dispatcher_can_promote_and_remove(): void
    {
        $leg = $this->flight->legs->first();
        $service = app(FlightDispatchService::class);

        $first = $service->book($leg, Employee::factory()->create());
        $service->book($leg, Employee::factory()->create());
        $waiting = $service->book($leg, Employee::factory()->create());

        $this->assertTrue($waiting->isWaitlisted());

        $component = Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('selectedLegId', $leg->id);

        $component->call('remove', $first->id)->assertHasNoErrors();
        $component->call('promote', $waiting->id)->assertHasNoErrors();

        $this->assertTrue($waiting->fresh()->isConfirmed());
    }

    #[Test]
    public function promotion_is_refused_when_the_leg_is_still_full(): void
    {
        $leg = $this->flight->legs->first();
        $service = app(FlightDispatchService::class);

        $service->book($leg, Employee::factory()->create());
        $service->book($leg, Employee::factory()->create());
        $waiting = $service->book($leg, Employee::factory()->create());

        Livewire::actingAs($this->dispatcher())
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('selectedLegId', $leg->id)
            ->call('promote', $waiting->id)
            ->assertHasErrors('manifest');

        $this->assertTrue($waiting->fresh()->isWaitlisted());
    }

    /**
     * The picker used to list every employee in the company, capped at 500 with
     * nothing to say so. Booking is now limited to the dispatcher's own scope, and
     * the limit is enforced on the booking itself — a crafted request cannot seat
     * somebody the screen would not offer.
     */
    #[Test]
    public function a_dispatcher_may_only_book_employees_in_their_scope(): void
    {
        $mine = Location::factory()->create();
        $theirs = Location::factory()->create();

        $bookable = Employee::factory()->create(['location_id' => $mine->id, 'archived_at' => null]);
        $outOfScope = Employee::factory()->create(['location_id' => $theirs->id, 'archived_at' => null]);

        $dispatcher = $this->dispatcherFor($mine);
        $leg = $this->flight->legs->first();

        Livewire::actingAs($dispatcher)
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->assertSee($bookable->english_name)
            ->assertDontSee($outOfScope->english_name);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($dispatcher)
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->set('selectedLegId', $leg->id)
            ->set('travellerType', 'employee')
            ->set('travellerId', $outOfScope->id)
            ->call('addTraveller');
    }

    #[Test]
    public function a_dispatcher_with_no_scope_is_told_why_the_list_is_empty(): void
    {
        $user = $this->viewer();
        $user->givePermissionTo(['dispatch flights']);

        Livewire::actingAs($user)
            ->test(FlightManifest::class, ['flight' => $this->flight])
            ->assertSee(__('ui.no_dispatcher_scope'));
    }
}
