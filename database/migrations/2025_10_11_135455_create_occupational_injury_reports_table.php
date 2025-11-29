<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('occupational_injury_reports', function (Blueprint $table) {
            $table->id();

            // Case Identification
            $table->string('case_no')->nullable();
            $table->string('location')->nullable();
            $table->string('department')->nullable();
            $table->string('section')->nullable();
            $table->string('clinic')->nullable();
            $table->string('company')->nullable();
            $table->string('company_co_no')->nullable();
            $table->string('injured_name');
            $table->string('nationality')->nullable();
            $table->string('job_title')->nullable();
            $table->date('incident_date')->nullable();
            $table->string('incident_time')->nullable();

            // Classification
            $table->enum('classification', ['fatal','serious','minor'])->nullable();
            $table->boolean('is_lost_time_case')->default(false);
            $table->unsignedInteger('total_days_lost')->nullable();

            // Injury nature & cause
            $table->text('injury_nature')->nullable();
            $table->text('case_of_injury')->nullable();

            // Reporting
            $table->boolean('reported_at_once')->default(false);
            $table->boolean('reported_next_day')->default(false);
            $table->string('reported_other_specify')->nullable();

            // Method of informing
            $table->boolean('method_by_tele')->default(false);
            $table->boolean('method_by_radio')->default(false);
            $table->boolean('method_in_person')->default(false);

            // Medical response
            $table->boolean('doctor_on_site')->default(false);
            $table->boolean('doctor_out_of_site')->default(false);
            $table->boolean('ambulance_dispatched')->default(false);
            $table->boolean('company_vehicle_used')->default(false);
            $table->boolean('bring_patient_to_clinic')->default(false);
            $table->boolean('transport_doctor_to_scene')->default(false);
            $table->boolean('twin_otter_dispatched')->default(false);

            // Patient records / circumstances
            $table->boolean('occurred_during_working_hours')->default(false);
            $table->boolean('occurred_outside_working_hours')->default(false);
            $table->boolean('single_case')->default(false);
            $table->boolean('multiple_case')->default(false);
            $table->text('past_medical_history')->nullable();

            // Diagnosis & Treatment
            $table->text('injuries_diagnose')->nullable();
            $table->text('medical_treatment')->nullable();
            $table->text('investigations')->nullable(); // e.g. x-ray & ultrasound

            // Employee report & advice
            $table->boolean('employee_report_attached')->default(false);
            $table->boolean('employee_report_under_completion')->default(false);
            $table->boolean('advised_light_duties')->default(false);
            $table->boolean('follow_up_on_site')->default(false);
            $table->boolean('referred_to_hospital')->default(false);
            $table->boolean('back_to_work')->default(false);
            $table->boolean('exempt_from_wearing_ppe')->default(false);
            $table->string('other_specify')->nullable();

            // Comments & signature
            $table->longText('comments')->nullable();
            $table->string('medical_officer_name')->nullable();
            $table->string('medical_officer_signature')->nullable();
            $table->date('medical_officer_signed_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('occupational_injury_reports');
    }
};
