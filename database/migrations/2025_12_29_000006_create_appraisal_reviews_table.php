<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appraisal_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appraisal_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // appraiser = manager (employee)
            $table->foreignId('appraiser_id')->constrained('employees')->cascadeOnDelete();

            // version used (depends on employee form + active version at creation)
            $table->foreignId('appraisal_form_version_id')->constrained('appraisal_form_versions')->cascadeOnDelete();

            $table->enum('status', ['draft', 'submitted', 'locked'])->default('draft');

            $table->unsignedInteger('total_score')->nullable();
            $table->unsignedInteger('max_score')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();

            $table->timestamps();

            $table->unique(['appraisal_period_id', 'employee_id', 'appraiser_id'], 'apr_rev_period_emp_appr_uq');
            $table->index(['employee_id', 'appraiser_id', 'status'], 'apr_rev_emp_appr_status_idx');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_reviews');
    }
};
