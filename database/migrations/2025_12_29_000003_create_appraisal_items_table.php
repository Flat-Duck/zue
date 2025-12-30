<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appraisal_items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();        // attendance, teamwork, ...
            $table->string('default_section');      // job_performance/personal_traits/initiative
            $table->string('default_label');        // Arabic label
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_items');
    }
};
