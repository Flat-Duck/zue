<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('number')->nullable();
            $table->string('job')->nullable();
            $table->string('english_name')->nullable();
            $table->string('id_card')->nullable();
            $table->date('id_card_issue_date')->nullable();
            $table->string('passport')->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('center_id');
            $table
                ->integer('transfered_balance')
                ->default(0)
                ->nullable();
            $table->string('schedule')->nullable();
            $table->date('start_date')->nullable();
            $table->date('last_date')->nullable();
            $table->integer('total_balance')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->index('number');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
