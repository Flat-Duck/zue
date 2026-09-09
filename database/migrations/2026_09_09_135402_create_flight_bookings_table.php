<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One seat request on one leg.
 *
 * Employees and external passengers share this table rather than using the two
 * legacy pivots, because a seat is a seat: the confirmed count and the waiting
 * order have to be a single sequence across both kinds of traveller. Splitting
 * them would make "who gets the last seat" unanswerable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('flight_leg_id');

            // Employee or Passenger.
            $table->string('bookable_type');
            $table->unsignedBigInteger('bookable_id');

            $table->enum('status', ['confirmed', 'waitlisted'])->default('confirmed');

            // Registration order, used to decide who is next off the waiting list.
            $table->unsignedInteger('sequence');

            $table->unsignedBigInteger('booked_by_user_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // A traveller cannot hold two seats on the same leg.
            $table->unique(['flight_leg_id', 'bookable_type', 'bookable_id'], 'flight_bookings_leg_traveller_unique');

            $table->index(['flight_leg_id', 'status', 'sequence']);
            $table->index(['bookable_type', 'bookable_id']);

            $table->foreign('flight_leg_id')->references('id')->on('flight_legs')->cascadeOnDelete();
            $table->foreign('booked_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_bookings');
    }
};
