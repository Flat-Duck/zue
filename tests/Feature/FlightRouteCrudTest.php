<?php

namespace Tests\Feature;

use App\Models\Flight;
use App\Models\FlightRoute;
use App\Models\FlightStation;
use App\Models\Plane;
use App\Models\User;
use App\Services\Flights\FlightDispatchService;
use Database\Seeders\FlightRoutesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FlightRouteCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage flight routes', 'web');
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage flight routes');

        return $user;
    }

    #[Test]
    public function the_dispatcher_menu_lists_the_new_screens(): void
    {
        $user = $this->manager();
        foreach (['list flights', 'view flights', 'list planes', 'view planes'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user->givePermissionTo(['list flights', 'view flights', 'list planes', 'view planes']);

        $this->actingAs($user)->get('/home')
            ->assertOk()
            ->assertSee('Dispatcher Management')
            ->assertSee(route('planes.index'), false)
            ->assertSee(route('flight-routes.index'), false)
            ->assertSee(route('flight-stations.index'), false);
    }

    #[Test]
    public function the_menu_hides_screens_the_user_cannot_reach(): void
    {
        // Route management only: no plane permissions granted.
        $this->actingAs($this->manager())->get('/home')
            ->assertOk()
            ->assertSee(route('flight-routes.index'), false)
            ->assertDontSee(route('planes.index'), false);
    }

    #[Test]
    public function a_guest_is_redirected_to_login(): void
    {
        $this->get(route('flight-routes.index'))->assertRedirect(route('login'));
        $this->get(route('flight-stations.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_user_without_permission_is_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('flight-routes.index'))->assertForbidden();
        $this->actingAs($user)->get(route('flight-routes.create'))->assertForbidden();
        $this->actingAs($user)->get(route('flight-stations.index'))->assertForbidden();
    }

    #[Test]
    public function a_route_can_be_created_with_its_legs(): void
    {
        $tripoli = FlightStation::factory()->create(['code' => 'TIP']);
        $field = FlightStation::factory()->field()->create(['code' => '103A']);

        $response = $this->actingAs($this->manager())->post(route('flight-routes.store'), [
            'name' => 'Tripoli - 103A - Tripoli',
            'is_active' => 1,
            'legs' => [
                ['from_station_id' => $tripoli->id, 'to_station_id' => $field->id, 'direction' => 'coming'],
                ['from_station_id' => $field->id, 'to_station_id' => $tripoli->id, 'direction' => 'leaving'],
            ],
        ]);

        $response->assertRedirect();

        $route = FlightRoute::query()->where('name', 'Tripoli - 103A - Tripoli')->firstOrFail();

        $this->assertCount(2, $route->legs);
        $this->assertSame([1, 2], $route->legs->pluck('sequence')->all());
        $this->assertSame(['coming', 'leaving'], $route->legs->pluck('direction')->all());
    }

    #[Test]
    public function a_route_needs_at_least_one_leg(): void
    {
        $this->actingAs($this->manager())
            ->from(route('flight-routes.create'))
            ->post(route('flight-routes.store'), ['name' => 'Empty route', 'legs' => []])
            ->assertRedirect(route('flight-routes.create'))
            ->assertSessionHasErrors('legs');

        $this->assertDatabaseMissing('flight_routes', ['name' => 'Empty route']);
    }

    #[Test]
    public function a_leg_cannot_start_and_finish_at_the_same_station(): void
    {
        $station = FlightStation::factory()->create();

        $this->actingAs($this->manager())
            ->from(route('flight-routes.create'))
            ->post(route('flight-routes.store'), [
                'name' => 'Nowhere route',
                'legs' => [
                    ['from_station_id' => $station->id, 'to_station_id' => $station->id, 'direction' => 'coming'],
                ],
            ])
            ->assertSessionHasErrors('legs.0.to_station_id');

        $this->assertDatabaseMissing('flight_routes', ['name' => 'Nowhere route']);
    }

    #[Test]
    public function editing_a_route_replaces_its_legs(): void
    {
        $this->seed(FlightRoutesSeeder::class);
        $route = FlightRoute::query()->where('name', 'Tripoli - 103A - Tripoli')->firstOrFail();

        $tripoli = FlightStation::query()->where('code', 'TIP')->firstOrFail();
        $field = FlightStation::query()->where('code', '103A')->firstOrFail();
        $benghazi = FlightStation::query()->where('code', 'BEN')->firstOrFail();

        $this->actingAs($this->manager())->put(route('flight-routes.update', $route), [
            'name' => 'Tripoli - 103A - Benghazi',
            'is_active' => 1,
            'legs' => [
                ['from_station_id' => $tripoli->id, 'to_station_id' => $field->id, 'direction' => 'coming'],
                ['from_station_id' => $field->id, 'to_station_id' => $benghazi->id, 'direction' => 'leaving'],
                ['from_station_id' => $benghazi->id, 'to_station_id' => $tripoli->id, 'direction' => 'leaving'],
            ],
        ])->assertRedirect();

        $route->refresh()->load('legs');

        $this->assertSame('Tripoli - 103A - Benghazi', $route->name);
        $this->assertCount(3, $route->legs);
        $this->assertSame([1, 2, 3], $route->legs->pluck('sequence')->all());
    }

    /**
     * The reason legs are copied onto a flight: editing the route afterwards
     * must not rewrite a manifest that has already been flown.
     */
    #[Test]
    public function editing_a_route_does_not_change_flights_already_built_from_it(): void
    {
        $this->seed(FlightRoutesSeeder::class);
        $route = FlightRoute::query()->where('name', 'Tripoli - 103A - Tripoli')->firstOrFail();

        $flight = app(FlightDispatchService::class)->buildLegsFromRoute(
            Flight::factory()->create(['plane_id' => Plane::factory()->seats(10)->create()->id]),
            $route
        );

        $this->assertCount(2, $flight->legs);

        $tripoli = FlightStation::query()->where('code', 'TIP')->firstOrFail();
        $field = FlightStation::query()->where('code', '103A')->firstOrFail();

        $this->actingAs($this->manager())->put(route('flight-routes.update', $route), [
            'name' => $route->name,
            'is_active' => 1,
            'legs' => [
                ['from_station_id' => $tripoli->id, 'to_station_id' => $field->id, 'direction' => 'coming'],
            ],
        ])->assertRedirect();

        // The route now has one leg; the flight keeps the two it was built with.
        $this->assertCount(1, $route->fresh()->legs);
        $this->assertCount(2, $flight->fresh()->legs);
    }

    #[Test]
    public function a_station_can_be_created_with_an_arabic_name(): void
    {
        $this->actingAs($this->manager())->post(route('flight-stations.store'), [
            'name' => 'Sirte',
            'name_ar' => 'سرت',
            'code' => 'SRT',
            'is_field' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('flight-stations.index'));

        $this->assertDatabaseHas('flight_stations', [
            'code' => 'SRT',
            'name_ar' => 'سرت',
            'is_field' => 1,
        ]);
    }

    #[Test]
    public function station_codes_are_unique(): void
    {
        FlightStation::factory()->create(['code' => 'TIP']);

        $this->actingAs($this->manager())
            ->from(route('flight-stations.create'))
            ->post(route('flight-stations.store'), ['name' => 'Duplicate', 'code' => 'TIP'])
            ->assertSessionHasErrors('code');
    }

    #[Test]
    public function a_station_in_use_cannot_be_deleted(): void
    {
        $this->seed(FlightRoutesSeeder::class);
        $station = FlightStation::query()->where('code', 'TIP')->firstOrFail();

        $this->actingAs($this->manager())
            ->delete(route('flight-stations.destroy', $station))
            ->assertSessionHasErrors('station');

        $this->assertDatabaseHas('flight_stations', ['id' => $station->id]);
    }

    #[Test]
    public function an_unused_station_can_be_deleted(): void
    {
        $station = FlightStation::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('flight-stations.destroy', $station))
            ->assertRedirect(route('flight-stations.index'));

        $this->assertDatabaseMissing('flight_stations', ['id' => $station->id]);
    }
}
