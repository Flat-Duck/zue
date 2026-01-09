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
        Schema::table('management_scopes', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('management_scopes', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable(false)->change();
        });
    }
};
