<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appraisal_items', function (Blueprint $table) {
            $table->string('type')->default('score')->after('key');
            // 'score', 'text'
        });

        Schema::table('appraisal_review_scores', function (Blueprint $table) {
            $table->text('text_value')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('appraisal_items', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('appraisal_review_scores', function (Blueprint $table) {
            $table->dropColumn('text_value');
        });
    }
};
