<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_orphans', function (Blueprint $table): void {
            $table->id();
            $table->string('source_table');
            $table->string('source_column');
            $table->unsignedBigInteger('missing_employee_id')->nullable();
            $table->string('legacy_key')->nullable();
            $table->string('row_hash', 64);
            $table->json('row_payload');
            $table->timestamps();

            $table->unique(['source_table', 'row_hash'], 'legacy_import_orphans_source_hash_unique');
            $table->index(['source_table', 'source_column'], 'legacy_import_orphans_source_column_index');
            $table->index('missing_employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_orphans');
    }
};
