<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appraisal_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->enum('type', ['quarter', 'yearly']);
            $table->unsignedTinyInteger('quarter')->nullable(); // 1..4
            $table->date('window_open_from');
            $table->date('window_open_to');
            $table->enum('status', ['planned', 'open', 'closed', 'locked'])->default('planned');
            $table->timestamps();

            $table->unique(['year', 'type', 'quarter'], 'apr_period_uq');

            $table->index(['status', 'window_open_from', 'window_open_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_periods');
    }
};
