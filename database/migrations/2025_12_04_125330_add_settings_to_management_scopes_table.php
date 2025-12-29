<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('management_scopes', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('subordinate_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('management_scopes', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
