<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manifests are printed in Arabic and handed over at the airport, so a station
 * needs the name that appears on that sheet as well as the one used in the
 * dispatcher's interface.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_stations', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('flight_stations', function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });
    }
};
