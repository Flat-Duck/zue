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

    public function down(): void
    {
        Schema::table('employee_flight', function (Blueprint $table): void {
            $table->dropIndex('employee_flight_flight_employee_idx');
        });

        Schema::table('flight_passenger', function (Blueprint $table): void {
            $table->dropIndex('flight_passenger_flight_passenger_idx');
        });
    }
};
