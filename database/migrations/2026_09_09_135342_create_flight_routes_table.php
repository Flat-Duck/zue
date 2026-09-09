<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A named, reusable itinerary such as "Tripoli - 103A - Benghazi - 103A -
 * Tripoli". Dispatchers pick one when creating a flight.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_routes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_routes');
    }
};
