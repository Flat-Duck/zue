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
            $table->unsignedBigInteger('timekeeper_id')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->unsignedBigInteger('superintendent_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('timekeeper_id')->references('id')->on('employees');
            $table->foreign('supervisor_id')->references('id')->on('employees');
            $table->foreign('superintendent_id')->references('id')->on('employees');
            $table->foreign('admin_id')->references('id')->on('employees');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_sheets', function (Blueprint $table) {
            //
        });
    }
};
