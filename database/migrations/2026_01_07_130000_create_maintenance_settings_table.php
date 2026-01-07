<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_settings', function (Blueprint $row) {
            $row->id();
            $row->string('key')->unique();
            $row->text('value')->nullable();
            $row->timestamps();
        });

        // Insert default settings
        DB::table('maintenance_settings')->insert([
            ['key' => 'auto_backup_enabled', 'value' => '0'],
            ['key' => 'backup_interval', 'value' => 'daily'],
            ['key' => 'backup_time', 'value' => '00:00'],
            ['key' => 'keep_backups_count', 'value' => '10'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_settings');
    }
};
