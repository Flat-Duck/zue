<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            // Nullable: flights created before dispatching existed have no route.
            $table->unsignedBigInteger('flight_route_id')->nullable()->after('type');

            $table->foreign('flight_route_id')->references('id')->on('flight_routes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropForeign(['flight_route_id']);
            $table->dropColumn('flight_route_id');
        });
    }
};
