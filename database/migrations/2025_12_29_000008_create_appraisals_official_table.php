<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisals_official', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appraisal_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // For display & consistency
            $table->foreignId('appraisal_form_version_id')->constrained('appraisal_form_versions')->cascadeOnDelete();

            $table->unsignedInteger('reviews_count')->default(0);

            $table->unsignedInteger('total_score')->nullable();
            $table->unsignedInteger('max_score')->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('employees')->nullOnDelete();

            $table->timestamps();

            $table->unique(['appraisal_period_id', 'employee_id'], 'apr_off_period_emp_uq');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisals_official');
    }
};
