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
        Schema::create('management_scope_manager', function (Blueprint $table) {
            $table->unsignedBigInteger('management_scope_id');
            $table->unsignedBigInteger('manager_id');

            $table->foreign('management_scope_id')
                ->references('id')
                ->on('management_scopes')
                ->onDelete('cascade');

            $table->foreign('manager_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            $table->primary(['management_scope_id', 'manager_id'], 'msm_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_scope_manager');
    }
};
