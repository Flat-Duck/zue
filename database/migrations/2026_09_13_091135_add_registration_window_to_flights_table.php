<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->timestamp('registration_opens_at')->nullable()->after('flight_route_id');
            $table->timestamp('registration_closes_at')->nullable()->after('registration_opens_at');
            $table->index(['registration_opens_at', 'registration_closes_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->timestamp('registration_opens_at')->nullable()->after('flight_route_id');
            $table->timestamp('registration_closes_at')->nullable()->after('registration_opens_at');
            $table->index(['registration_opens_at', 'registration_closes_at']);
        });
    }
};
