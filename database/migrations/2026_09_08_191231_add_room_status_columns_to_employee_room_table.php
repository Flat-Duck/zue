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
        Schema::table('employee_room', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_room', 'is_owner')) {
                $table->boolean('is_owner')->default(false)->after('room_id');
            }

            if (! Schema::hasColumn('employee_room', 'is_here')) {
                $table->boolean('is_here')->default(true)->after('is_owner');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_room', function (Blueprint $table) {
            if (Schema::hasColumn('employee_room', 'is_here')) {
                $table->dropColumn('is_here');
            }

            if (Schema::hasColumn('employee_room', 'is_owner')) {
                $table->dropColumn('is_owner');
            }
        });
    }
};
