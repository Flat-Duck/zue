<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightLeg;
use App\Models\FlightRoute;
use App\Models\Plane;
use App\Models\User;
use App\Services\Flights\FlightDispatchService;
use Database\Seeders\FlightRoutesSeeder;
use Database\Seeders\PermissionsSeeder;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The printed documents, checked in a real browser.
 *
 * These are the pages the company hands to people — the manifest is carried to the
 * airport — and their layout depends on CSS and on right-to-left rendering that no
 * server-side assertion can see. This is also the safety net phase 5.2 needs before
 * eleven `<style>` blocks can be lifted out of the views they are scoped to.
 */
class PrintLayoutTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_the_manifest_renders_right_to_left_and_lists_its_travellers(): void
    {
        $this->seed(PermissionsSeeder::class);
        $this->seed(FlightRoutesSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $flight = app(FlightDispatchService::class)->buildLegsFromRoute(
            Flight::factory()->create([
                'plane_id' => Plane::factory()->seats(4)->create(['name' => 'Dash-8'])->id,
                'date' => '2026-09-08',
            ]),
            FlightRoute::query()->where('name', 'Tripoli - 103A - Benghazi - 103A - Tripoli')->firstOrFail()
        );

        $leg = $flight->legs->first();

        $employee = Employee::factory()
            ->withProfile(['nationality' => 'ليبي'])
            ->create(['english_name' => 'AHMED SALEM', 'arabic_name' => 'احمد سالم', 'number' => 9812]);

        app(FlightDispatchService::class)->book($leg, $employee);

        $this->browse(function (Browser $browser) use ($admin, $flight, $leg): void {
            $browser->loginAs($admin->fresh())
                ->visit(route('flights.manifest', [$flight, $leg], false))
                ->assertSee('ZUEITINA OIL COMPANY')
                ->assertSee('احمد سالم')
                ->assertSee('ليبي')
                ->screenshot('manifest');

            $this->assertSame(
                'rtl',
                $browser->driver->findElement(WebDriverBy::tagName('html'))->getAttribute('dir'),
                'The manifest must render right to left.'
            );

            $this->assertNoBrowserErrors($browser);
        });
    }

    /**
     * The employee number is a Latin string inside a right-to-left document, which
     * is where bidirectional text goes wrong: `2025-A3614` once printed as
     * `A3614-2025`. Reading it back from the rendered page is the only way to know.
     */
    public function test_a_latin_identifier_keeps_its_order_inside_the_arabic_sheet(): void
    {
        $this->seed(PermissionsSeeder::class);
        $this->seed(FlightRoutesSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $flight = app(FlightDispatchService::class)->buildLegsFromRoute(
            Flight::factory()->create(['plane_id' => Plane::factory()->seats(4)->create()->id, 'date' => '2026-09-08']),
            FlightRoute::query()->where('name', 'Tripoli - 103A - Benghazi - 103A - Tripoli')->firstOrFail()
        );

        $leg = $flight->legs->first();
        $employee = Employee::factory()->create(['number' => 9812, 'arabic_name' => 'احمد سالم']);

        app(FlightDispatchService::class)->book($leg, $employee);

        $this->browse(function (Browser $browser) use ($admin, $flight, $leg): void {
            $browser->loginAs($admin->fresh())
                ->visit(route('flights.manifest', [$flight, $leg], false));

            $cells = collect($browser->driver->findElements(WebDriverBy::cssSelector('td')))
                ->map(fn ($cell): string => trim($cell->getText()));

            $this->assertTrue(
                $cells->contains('9812'),
                'The employee number was not rendered as written. Cells: '.$cells->implode(' | ')
            );
        });
    }

    /**
     * The manifest's layout, read back from the browser rather than from the source.
     *
     * These are the properties that make it the sheet the airport expects: an A4
     * page width, a fixed table layout with ruled cells, and right-to-left text. They
     * are asserted as *computed* values so that moving the CSS out of the view — which
     * is what phase 5.2 does — has to preserve them, not merely keep the file parsing.
     */
    public function test_the_manifest_keeps_its_printed_geometry(): void
    {
        [$flight, $leg] = $this->flightWithATraveller();

        $this->browse(function (Browser $browser) use ($flight, $leg): void {
            $browser->loginAs($this->superAdmin())
                ->visit(route('flights.manifest', [$flight, $leg], false));

            $computed = $browser->driver->executeScript(<<<'JS'
                const read = (selector, properties) => {
                    const el = document.querySelector(selector);
                    if (! el) return null;
                    const style = getComputedStyle(el);
                    return Object.fromEntries(properties.map((p) => [p, style.getPropertyValue(p)]));
                };

                return {
                    sheet: read('.sheet', ['width', 'background-color', 'box-sizing']),
                    table: read('table', ['table-layout', 'border-collapse', 'width']),
                    cell: read('td', ['border-top-width', 'border-top-style']),
                    body: read('body', ['direction']),
                };
            JS);

            $this->assertNotNull($computed['sheet'], 'The A4 sheet is missing.');
            // 210mm, as the browser resolves it.
            $this->assertEqualsWithDelta(
                793.688,
                (float) $computed['sheet']['width'],
                0.5,
                'The sheet is no longer A4 wide.'
            );
            $this->assertSame('rgb(255, 255, 255)', $computed['sheet']['background-color']);
            $this->assertSame('border-box', $computed['sheet']['box-sizing']);

            $this->assertSame('fixed', $computed['table']['table-layout']);
            $this->assertSame('collapse', $computed['table']['border-collapse']);

            $this->assertSame('1px', $computed['cell']['border-top-width'], 'The cells lost their rules.');
            $this->assertSame('solid', $computed['cell']['border-top-style']);

            $this->assertSame('rtl', $computed['body']['direction']);
        });
    }

    /**
     * @return array{0: Flight, 1: FlightLeg}
     */
    private function flightWithATraveller(): array
    {
        $this->seed(PermissionsSeeder::class);
        $this->seed(FlightRoutesSeeder::class);

        $flight = app(FlightDispatchService::class)->buildLegsFromRoute(
            Flight::factory()->create([
                'plane_id' => Plane::factory()->seats(4)->create(['name' => 'Dash-8'])->id,
                'date' => '2026-09-08',
            ]),
            FlightRoute::query()->where('name', 'Tripoli - 103A - Benghazi - 103A - Tripoli')->firstOrFail()
        );

        $leg = $flight->legs->first();

        app(FlightDispatchService::class)->book(
            $leg,
            Employee::factory()->create(['number' => 9812, 'arabic_name' => 'احمد سالم'])
        );

        return [$flight, $leg];
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin->fresh();
    }
}
