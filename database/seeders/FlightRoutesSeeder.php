<?php

namespace Database\Seeders;

use App\Models\FlightRoute;
use App\Models\FlightRouteLeg;
use App\Models\FlightStation;
use Illuminate\Database\Seeder;

/**
 * The stations and routes currently flown.
 *
 * Both are ordinary data: new field sites and new routes are added through the
 * application, not by editing this file. It exists to give a fresh install the
 * itineraries that are already in operation.
 */
class FlightRoutesSeeder extends Seeder
{
    public function run(): void
    {
        // name_ar is what gets printed on the Arabic manifest handed over at
        // the airport; edit these if the wording on the official sheet differs.
        $stations = collect([
            ['name' => 'Tripoli', 'name_ar' => 'طرابلس', 'code' => 'TIP', 'is_field' => false],
            ['name' => 'Benghazi', 'name_ar' => 'بنغازي', 'code' => 'BEN', 'is_field' => false],
            ['name' => '103A', 'name_ar' => 'حقل 103A', 'code' => '103A', 'is_field' => true],
            ['name' => 'Terminal', 'name_ar' => 'الترمينال', 'code' => 'TERM', 'is_field' => true],
        ])->mapWithKeys(function (array $attributes): array {
            $station = FlightStation::query()->firstOrCreate(
                ['code' => $attributes['code']],
                $attributes
            );

            // Backfill the Arabic name for stations created before it existed.
            if (blank($station->name_ar) && filled($attributes['name_ar'])) {
                $station->update(['name_ar' => $attributes['name_ar']]);
            }

            return [$attributes['code'] => $station];
        });

        // Direction is explicit per leg: arriving at a field is "coming", which
        // is why 103A -> BEN is leaving but 103A -> TERM is coming.
        $routes = [
            'Tripoli - 103A - Tripoli' => [
                ['TIP', '103A', 'coming'],
                ['103A', 'TIP', 'leaving'],
            ],
            'Tripoli - 103A - Benghazi - 103A - Tripoli' => [
                ['TIP', '103A', 'coming'],
                ['103A', 'BEN', 'leaving'],
                ['BEN', '103A', 'coming'],
                ['103A', 'TIP', 'leaving'],
            ],
            'Tripoli - 103A - Terminal - 103A - Tripoli' => [
                ['TIP', '103A', 'coming'],
                ['103A', 'TERM', 'coming'],
                ['TERM', '103A', 'leaving'],
                ['103A', 'TIP', 'leaving'],
            ],
        ];

        foreach ($routes as $name => $legs) {
            $route = FlightRoute::query()->firstOrCreate(['name' => $name], ['is_active' => true]);

            foreach ($legs as $index => [$from, $to, $direction]) {
                FlightRouteLeg::query()->updateOrCreate(
                    [
                        'flight_route_id' => $route->id,
                        'sequence' => $index + 1,
                    ],
                    [
                        'from_station_id' => $stations[$from]->id,
                        'to_station_id' => $stations[$to]->id,
                        'direction' => $direction,
                    ]
                );
            }
        }
    }
}
