<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Places a flight can call at: cities such as Tripoli and Benghazi, and field
 * sites such as 103A or a terminal. Stored as data because new field sites are
 * expected over time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_stations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_field')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_stations');
    }
};
