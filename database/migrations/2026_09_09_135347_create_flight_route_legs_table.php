<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ordered hops of a route.
 *
 * `direction` is stored per leg rather than derived, because whether a hop
 * counts as coming or leaving depends on the site: flying 103A -> Benghazi is
 * leaving (Benghazi is a city), while 103A -> Terminal is coming (a terminal is
 * another field). Keeping the rule in data means a new site needs no code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_route_legs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('flight_route_id');
            $table->unsignedInteger('sequence');
            $table->unsignedBigInteger('from_station_id');
            $table->unsignedBigInteger('to_station_id');
            $table->enum('direction', ['coming', 'leaving']);
            $table->timestamps();

            $table->unique(['flight_route_id', 'sequence']);

            $table->foreign('flight_route_id')->references('id')->on('flight_routes')->cascadeOnDelete();
            $table->foreign('from_station_id')->references('id')->on('flight_stations')->restrictOnDelete();
            $table->foreign('to_station_id')->references('id')->on('flight_stations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_route_legs');
    }
};
