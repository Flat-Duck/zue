<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_scopes', function (Blueprint $table) {
            $table->bigIncrements('id');

            // manager at employee level
            $table->unsignedBigInteger('manager_id');

            // 'global', 'location', 'department', 'center', 'employee'
            $table->string('scope_type');

            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('center_id')->nullable();

            // specific subordinate employee (when scope_type = 'employee')
            $table->unsignedBigInteger('subordinate_employee_id')->nullable();

            $table->timestamps();

            $table
                ->foreign('manager_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            $table
                ->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('set null');

            $table
                ->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('set null');

            $table
                ->foreign('subordinate_employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            // $table->index(['scope_type', 'location_id', 'department_id', 'center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_scopes');
    }
};
