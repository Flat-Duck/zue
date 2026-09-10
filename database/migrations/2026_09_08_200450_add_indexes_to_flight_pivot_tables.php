<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_flight', function (Blueprint $table): void {
            $table->index(['flight_id', 'employee_id'], 'employee_flight_flight_employee_idx');
        });

        Schema::table('flight_passenger', function (Blueprint $table): void {
            $table->index(['flight_id', 'passenger_id'], 'flight_passenger_flight_passenger_idx');
        });
    }

    /**
     * These tables have no separate index on `flight_id`, so the composite added
     * above is the only one serving that foreign key — and MySQL refuses to drop an
     * index a constraint depends on. A single-column index is put back first, which
     * is what the table would have had if this migration had never run.
     */
    public function down(): void
    {
        Schema::table('employee_flight', function (Blueprint $table): void {
            $table->index('flight_id', 'employee_flight_flight_id_foreign');
        });

        Schema::table('employee_flight', function (Blueprint $table): void {
            $table->dropIndex('employee_flight_flight_employee_idx');
        });

        Schema::table('flight_passenger', function (Blueprint $table): void {
            $table->index('flight_id', 'flight_passenger_flight_id_foreign');
        });

        Schema::table('flight_passenger', function (Blueprint $table): void {
            $table->dropIndex('flight_passenger_flight_passenger_idx');
        });
    }
};
