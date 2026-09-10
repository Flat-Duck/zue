<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'appraisal_form_id')) {
                $table->foreignId('appraisal_form_id')->nullable()
                    ->after('id')
                    ->constrained('appraisal_forms')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'appraisal_form_id')) {
                $table->dropConstrainedForeignId('appraisal_form_id');
            }
        });
    }
};
