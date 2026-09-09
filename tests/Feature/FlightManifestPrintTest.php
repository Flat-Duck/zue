<?php

namespace Tests\Feature;

use App\Models\Administration;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightRoute;
use App\Models\Location;
use App\Models\Passenger;
use App\Models\Plane;
use App\Models\User;
use App\Services\Flights\FlightDispatchService;
use Database\Seeders\FlightRoutesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The printable manifest is the sheet carried to the airport and checked
 * against, so what it does and does not list matters.
 */
class FlightManifestPrintTest extends TestCase
{
    use RefreshDatabase;

    private Flight $flight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FlightRoutesSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view flights', 'list flights', 'dispatch flights'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $this->flight = app(FlightDispatchService::class)->buildLegsFromRoute(
            Flight::factory()->create([
                'plane_id' => Plane::factory()->seats(2)->create(['name' => 'Dash-8'])->id,
                'date' => '2026-09-08',
            ]),
            FlightRoute::query()->where('name', 'Tripoli - 103A - Benghazi - 103A - Tripoli')->firstOrFail()
        );
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view flights', 'list flights']);

        return $user;
    }

    #[Test]
    public function it_renders_the_official_column_headings(): void
    {
        $leg = $this->flight->legs->first();

        $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $leg]))
            ->assertOk()
            ->assertSee('ZUEITINA OIL COMPANY')
            ->assertSee('ت', false)
            ->assertSee('الرقم', false)
            ->assertSee('الاســـم', false)
            ->assertSee('الجنسية', false)
            ->assertSee('الشركة', false)
            ->assertSee('الموقع', false)
            ->assertSee('القسم', false)
            ->assertSee('الجهة', false);
    }

    #[Test]
    public function a_leaving_leg_is_titled_as_departing_and_a_coming_leg_as_arriving(): void
    {
        $coming = $this->flight->legs->firstWhere('direction', 'coming');
        $leaving = $this->flight->legs->firstWhere('direction', 'leaving');

        $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $coming]))
            ->assertSee('القادمون', false);

        $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $leaving]))
            ->assertSee('المغادرون', false);
    }

    #[Test]
    public function it_shows_the_date_day_and_aircraft(): void
    {
        $leg = $this->flight->legs->first();

        $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $leg]))
            ->assertSee('08/09/2026')
            // 8 September 2026 is a Tuesday.
            ->assertSee('الثلاثاء', false)
            ->assertSee('Dash-8');
    }

    #[Test]
    public function it_lists_confirmed_travellers_with_their_details(): void
    {
        $leg = $this->flight->legs->first();
        $service = app(FlightDispatchService::class);

        $department = Department::factory()->create(['name' => 'MAINT']);
        $employee = Employee::factory()->create([
            'english_name' => 'Ahmed Salem',
            'number' => 9812,
            'department_id' => $department->id,
        ]);

        $passenger = Passenger::factory()->create([
            'name' => 'Contractor Guest',
            'company' => 'Kamco',
            'number' => 'DP/26-1411',
            'nationality' => 'إيطالي',
        ]);

        $service->book($leg, $employee);
        $service->book($leg, $passenger);

        $response = $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $leg]))
            ->assertOk();

        $response->assertSee('Ahmed Salem')
            ->assertSee('9812')
            ->assertSee('MAINT')
            // Employees fly for the operator itself.
            ->assertSee('الزويتينة', false);

        $response->assertSee('Contractor Guest')
            ->assertSee('Kamco')
            ->assertSee('DP/26-1411')
            ->assertSee('إيطالي', false)
            ->assertSee('مقاولات', false);
    }

    /**
     * The whole point of the sheet: a waitlisted traveller has no seat and must
     * not appear on the list handed over at the airport.
     */
    /**
     * Once the personnel export has been imported the sheet should show the
     * Arabic detail it carries, which is what the official form displays.
     */
    #[Test]
    public function it_prefers_the_arabic_name_department_and_site(): void
    {
        $leg = $this->flight->legs->first();

        $administration = Administration::factory()->create(['arabic_name' => 'ادارة العمليات']);
        $department = Department::factory()->create([
            'name' => 'MAINT',
            'arabic_name' => 'قسم الصيانة',
            'administration_id' => $administration->id,
        ]);
        $location = Location::factory()->create([
            'name' => 'B099',
            'arabic_name' => 'ميناء الزويتينة',
        ]);

        $employee = Employee::factory()->create([
            'english_name' => 'Ahmed Salem',
            'arabic_name' => 'احمد سالم',
            'nationality' => 'ليبي',
            'department_id' => $department->id,
            'location_id' => $location->id,
        ]);

        app(FlightDispatchService::class)->book($leg, $employee);

        $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $leg]))
            ->assertOk()
            ->assertSee('احمد سالم', false)
            ->assertSee('ليبي', false)
            ->assertSee('قسم الصيانة', false)
            ->assertSee('ميناء الزويتينة', false)
            // The English name is not what the sheet shows.
            ->assertDontSee('Ahmed Salem');
    }

    #[Test]
    public function waitlisted_travellers_are_excluded(): void
    {
        $leg = $this->flight->legs->first();
        $service = app(FlightDispatchService::class);

        $service->book($leg, Employee::factory()->create(['english_name' => 'Seated One']));
        $service->book($leg, Employee::factory()->create(['english_name' => 'Seated Two']));
        $waiting = $service->book($leg, Employee::factory()->create(['english_name' => 'Still Waiting']));

        $this->assertTrue($waiting->isWaitlisted());

        $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $leg]))
            ->assertOk()
            ->assertSee('Seated One')
            ->assertSee('Seated Two')
            ->assertDontSee('Still Waiting');
    }

    #[Test]
    public function a_leg_from_another_flight_is_not_shown(): void
    {
        $otherFlight = app(FlightDispatchService::class)->buildLegsFromRoute(
            Flight::factory()->create(['plane_id' => Plane::factory()->seats(5)->create()->id]),
            FlightRoute::query()->where('name', 'Tripoli - 103A - Tripoli')->firstOrFail()
        );

        $this->actingAs($this->viewer())
            ->get(route('flights.manifest', [$this->flight, $otherFlight->legs->first()]))
            ->assertNotFound();
    }

    #[Test]
    public function a_user_who_cannot_view_flights_is_refused(): void
    {
        $leg = $this->flight->legs->first();

        $this->actingAs(User::factory()->create())
            ->get(route('flights.manifest', [$this->flight, $leg]))
            ->assertForbidden();
    }

    #[Test]
    public function a_guest_is_redirected_to_login(): void
    {
        $leg = $this->flight->legs->first();

        $this->get(route('flights.manifest', [$this->flight, $leg]))
            ->assertRedirect(route('login'));
    }
}
