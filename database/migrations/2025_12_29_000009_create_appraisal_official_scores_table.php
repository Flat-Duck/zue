<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appraisal_official_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appraisals_official_id')->constrained('appraisals_official')->cascadeOnDelete();
            $table->foreignId('form_version_item_id')
                ->constrained('appraisal_form_version_items')
                ->cascadeOnDelete();

            $table->decimal('avg_score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['appraisals_official_id', 'form_version_item_id'], 'apr_off_score_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_official_scores');
    }
};
