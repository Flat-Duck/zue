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
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->string('restore_verification_status')->default('pending');
            $table->timestamp('restore_verified_at')->nullable();
            $table->text('restore_verification_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->dropColumn(['restore_verification_status', 'restore_verified_at', 'restore_verification_error']);
        });
    }
};
