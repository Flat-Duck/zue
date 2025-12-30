<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appraisal_review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_review_id')->constrained()->cascadeOnDelete();

            // IMPORTANT: link to form_version_item, because same item may have different max score per form/version
            $table->foreignId('form_version_item_id')
                ->constrained('appraisal_form_version_items')
                ->cascadeOnDelete();

            $table->unsignedInteger('score')->nullable();
            $table->timestamps();

            $table->unique(['appraisal_review_id', 'form_version_item_id'], 'apr_rscore_uq');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_review_scores');
    }
};
