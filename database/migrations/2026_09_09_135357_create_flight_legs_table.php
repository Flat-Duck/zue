<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A leg of one actual flight, copied from the route when the flight is created.
 *
 * This is a deliberate snapshot rather than a reference. Routes are editable, and
 * changing "Tripoli - 103A via Benghazi" next year must not silently rewrite the
 * manifests of flights that already operated.
 *
 * Each leg carries its own passenger list and its own seat limit, because the
 * aircraft empties at the field between legs: twenty people may fly
 * Tripoli -> 103A and a different twenty Benghazi -> 103A on the same flight.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_legs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('flight_id');
            $table->unsignedInteger('sequence');
            $table->unsignedBigInteger('from_station_id');
            $table->unsignedBigInteger('to_station_id');
            $table->enum('direction', ['coming', 'leaving']);

            // Copied from the plane when the flight is created, so swapping the
            // aircraft later is an explicit act with a visible consequence.
            $table->unsignedInteger('seat_capacity');

            $table->timestamps();

            $table->unique(['flight_id', 'sequence']);
            $table->index(['flight_id', 'direction']);

            $table->foreign('flight_id')->references('id')->on('flights')->cascadeOnDelete();
            $table->foreign('from_station_id')->references('id')->on('flight_stations')->restrictOnDelete();
            $table->foreign('to_station_id')->references('id')->on('flight_stations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_legs');
    }
};
