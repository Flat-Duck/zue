<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasDuplicates = DB::table('time_sheets')
            ->select('employee_id', 'day')
            ->groupBy('employee_id', 'day')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Duplicate employee/day timesheets exist. Run php artisan timesheets:reconcile-duplicates before migrating.'
            );
        }

        Schema::table('time_sheets', function (Blueprint $table): void {
            $table->dropIndex('time_sheets_employee_day_idx');
            $table->unique(['employee_id', 'day'], 'time_sheets_employee_day_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_sheets', function (Blueprint $table): void {
            $table->dropUnique('time_sheets_employee_day_unique');
            $table->index(['employee_id', 'day'], 'time_sheets_employee_day_idx');
        });
    }
};
