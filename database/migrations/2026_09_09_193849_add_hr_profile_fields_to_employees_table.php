<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The full HR profile that arrives in the personnel export.
 *
 * Every column is nullable: the export is partially populated, and a record
 * must never be rejected for missing a field the business has not captured yet.
 * Some of these are used today, the rest are carried for features still to come.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Identity
            $table->string('arabic_name')->nullable()->after('english_name');
            $table->string('arabic_full_name')->nullable()->after('arabic_name');
            $table->string('nationality')->nullable()->after('arabic_full_name');
            $table->string('gender')->nullable()->after('nationality');
            $table->string('marital_status')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('marital_status');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('mother_name')->nullable()->after('birth_place');
            $table->string('mother_name_insurance')->nullable()->after('mother_name');

            // Organisation, kept as text because the export carries names and
            // codes rather than the ids this application uses. These are
            // prefixed because Employee already exposes administration_name,
            // department_name and location_name as accessors derived from the
            // related records, and a column of the same name would be shadowed.
            $table->string('cost_center')->nullable();
            $table->string('hr_administration')->nullable();
            $table->string('hr_department')->nullable();
            $table->string('department_code')->nullable();
            $table->string('location_code')->nullable();
            $table->string('hr_location')->nullable();

            // Payroll
            $table->string('salary_type')->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->decimal('total_salary', 12, 2)->nullable();
            $table->decimal('performance_incentive', 12, 2)->nullable();
            $table->decimal('secondment_allowance', 12, 2)->nullable();
            $table->decimal('living_allowance', 12, 2)->nullable();
            $table->decimal('expatriation_allowance', 12, 2)->nullable();
            $table->decimal('desert_allowance', 12, 2)->nullable();

            // Job
            $table->string('job_code')->nullable();
            $table->string('job_title_en')->nullable();
            $table->date('job_start_date')->nullable();
            $table->string('job_note')->nullable();
            $table->string('job_level')->nullable();
            $table->string('old_grade')->nullable();
            $table->string('grade')->nullable();
            $table->string('job_number')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('assignment_authority')->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('appointment_job')->nullable();
            $table->string('appointment_decision_number')->nullable();

            // Documents and registration
            $table->string('insurance_id')->nullable();
            $table->string('file_number')->nullable();
            $table->string('family_paper_number')->nullable();
            $table->string('family_record_number')->nullable();
            $table->string('full_record_number')->nullable();
            $table->string('social_security_number')->nullable();

            // Contact
            $table->string('city')->nullable();

            // Education
            $table->string('education_level')->nullable();
            $table->string('specialization')->nullable();
            $table->string('education_year')->nullable();
            $table->string('study_country')->nullable();
            $table->string('study_institution')->nullable();
            $table->string('education_level_code')->nullable();

            // Welfare and history
            $table->string('clinic_name')->nullable();
            $table->unsignedInteger('children_count')->nullable();
            $table->unsignedInteger('family_members_count')->nullable();
            $table->string('previous_employer')->nullable();
            $table->string('previous_job')->nullable();
            $table->string('previous_job_start')->nullable();
            $table->string('previous_job_end')->nullable();
            $table->string('years_of_experience')->nullable();
            $table->string('action_code')->nullable();
            $table->string('action_date')->nullable();
            $table->string('military_status')->nullable();
            $table->string('marriage_grant_date')->nullable();
            $table->date('unpaid_leave_start')->nullable();
            $table->date('unpaid_leave_end')->nullable();

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();

            $table->text('hr_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'arabic_name', 'arabic_full_name', 'nationality', 'gender', 'marital_status',
                'birth_date', 'birth_place', 'mother_name', 'mother_name_insurance',
                'cost_center', 'hr_administration', 'hr_department', 'department_code',
                'location_code', 'hr_location',
                'salary_type', 'basic_salary', 'total_salary', 'performance_incentive',
                'secondment_allowance', 'living_allowance', 'expatriation_allowance', 'desert_allowance',
                'job_code', 'job_title_en', 'job_start_date', 'job_note', 'job_level', 'old_grade',
                'grade', 'job_number', 'employment_type', 'assignment_authority', 'appointment_date',
                'appointment_job', 'appointment_decision_number',
                'insurance_id', 'file_number', 'family_paper_number', 'family_record_number',
                'full_record_number', 'social_security_number', 'city',
                'education_level', 'specialization', 'education_year', 'study_country',
                'study_institution', 'education_level_code',
                'clinic_name', 'children_count', 'family_members_count', 'previous_employer',
                'previous_job', 'previous_job_start', 'previous_job_end', 'years_of_experience',
                'action_code', 'action_date', 'military_status', 'marriage_grant_date',
                'unpaid_leave_start', 'unpaid_leave_end',
                'bank_name', 'bank_account_number', 'hr_notes',
            ]);
        });
    }
};
