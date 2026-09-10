<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisal_form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_form_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version'); // 1,2,3...
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['appraisal_form_id', 'version'], 'apr_form_ver_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_form_versions');
    }
};
