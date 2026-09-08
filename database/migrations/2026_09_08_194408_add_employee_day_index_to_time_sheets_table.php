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
        Schema::table('time_sheets', function (Blueprint $table) {
            $table->index(['employee_id', 'day'], 'time_sheets_employee_day_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_sheets', function (Blueprint $table) {
            $table->dropIndex('time_sheets_employee_day_idx');
        });
    }
};
