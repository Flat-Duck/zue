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
        Schema::table('backup_logs', function (Blueprint $table): void {
            $table->string('verification_status')->nullable()->after('status');
            $table->timestamp('verified_at')->nullable()->after('completed_at');
            $table->text('verification_error')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_logs', function (Blueprint $table): void {
            $table->dropColumn(['verification_status', 'verified_at', 'verification_error']);
        });
    }
};
