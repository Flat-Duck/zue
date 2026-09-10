<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->default(1);
            $table
                ->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The column carries a foreign key, and MySQL will not drop a column a
        // constraint still depends on.
        Schema::table('residences', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });
    }
};
