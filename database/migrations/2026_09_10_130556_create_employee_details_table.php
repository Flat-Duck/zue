<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the HR profile off `employees` and into a record of its own.
 *
 * `employees` had grown to 87 columns, and Eloquent selects all of them. Listing
 * staff, filling a time sheet or printing a flight manifest was therefore reading
 * every salary, bank account and national ID number into memory — data none of
 * those screens show. Only what the rest of the system actually operates on stays
 * behind: who someone is, where they work, and their leave balance.
 *
 * The profile is a strict 1:1, keyed on the employee, so nothing here changes what
 * is recorded — only where it lives and when it is read.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const MOVED_COLUMNS = [
        'arabic_full_name',
        'nationality',
        'gender',
        'marital_status',
        'birth_date',
        'birth_place',
        'mother_name',
        'mother_name_insurance',
        'job_code',
        'job_start_date',
        'job_level',
        'old_grade',
        'grade',
        'job_number',
        'employment_type',
        'assignment_authority',
        'appointment_date',
        'appointment_job',
        'appointment_decision_number',
        'job_note',
        'salary_type',
        'basic_salary',
        'total_salary',
        'performance_incentive',
        'secondment_allowance',
        'living_allowance',
        'expatriation_allowance',
        'desert_allowance',
        'id_card',
        'id_card_issue_date',
        'passport',
        'passport_issue_date',
        'insurance_id',
        'file_number',
        'family_paper_number',
        'family_record_number',
        'full_record_number',
        'social_security_number',
        'phone',
        'email',
        'address',
        'city',
        'education_level',
        'specialization',
        'education_year',
        'study_country',
        'study_institution',
        'education_level_code',
        'clinic_name',
        'children_count',
        'family_members_count',
        'previous_employer',
        'previous_job',
        'previous_job_start',
        'previous_job_end',
        'years_of_experience',
        'military_status',
        'action_code',
        'action_date',
        'marriage_grant_date',
        'unpaid_leave_start',
        'unpaid_leave_end',
        'bank_name',
        'bank_account_number',
        'hr_notes',
    ];

    public function up(): void
    {
        Schema::create('employee_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('arabic_full_name')->nullable();
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_name_insurance')->nullable();
            $table->string('job_code')->nullable();
            $table->date('job_start_date')->nullable();
            $table->string('job_level')->nullable();
            $table->string('old_grade')->nullable();
            $table->string('grade')->nullable();
            $table->string('job_number')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('assignment_authority')->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('appointment_job')->nullable();
            $table->string('appointment_decision_number')->nullable();
            $table->string('job_note')->nullable();
            $table->string('salary_type')->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->decimal('total_salary', 12, 2)->nullable();
            $table->decimal('performance_incentive', 12, 2)->nullable();
            $table->decimal('secondment_allowance', 12, 2)->nullable();
            $table->decimal('living_allowance', 12, 2)->nullable();
            $table->decimal('expatriation_allowance', 12, 2)->nullable();
            $table->decimal('desert_allowance', 12, 2)->nullable();
            $table->string('id_card')->nullable();
            $table->date('id_card_issue_date')->nullable();
            $table->string('passport')->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->string('insurance_id')->nullable();
            $table->string('file_number')->nullable();
            $table->string('family_paper_number')->nullable();
            $table->string('family_record_number')->nullable();
            $table->string('full_record_number')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('education_level')->nullable();
            $table->string('specialization')->nullable();
            $table->string('education_year')->nullable();
            $table->string('study_country')->nullable();
            $table->string('study_institution')->nullable();
            $table->string('education_level_code')->nullable();
            $table->string('clinic_name')->nullable();
            $table->unsignedInteger('children_count')->nullable();
            $table->unsignedInteger('family_members_count')->nullable();
            $table->string('previous_employer')->nullable();
            $table->string('previous_job')->nullable();
            $table->string('previous_job_start')->nullable();
            $table->string('previous_job_end')->nullable();
            $table->string('years_of_experience')->nullable();
            $table->string('military_status')->nullable();
            $table->string('action_code')->nullable();
            $table->string('action_date')->nullable();
            $table->string('marriage_grant_date')->nullable();
            $table->date('unpaid_leave_start')->nullable();
            $table->date('unpaid_leave_end')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->text('hr_notes')->nullable();
            $table->timestamps();
        });

        $this->copyColumns('employees', 'employee_details', 'employee_id');

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn(self::MOVED_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('arabic_full_name')->nullable();
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_name_insurance')->nullable();
            $table->string('job_code')->nullable();
            $table->date('job_start_date')->nullable();
            $table->string('job_level')->nullable();
            $table->string('old_grade')->nullable();
            $table->string('grade')->nullable();
            $table->string('job_number')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('assignment_authority')->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('appointment_job')->nullable();
            $table->string('appointment_decision_number')->nullable();
            $table->string('job_note')->nullable();
            $table->string('salary_type')->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->decimal('total_salary', 12, 2)->nullable();
            $table->decimal('performance_incentive', 12, 2)->nullable();
            $table->decimal('secondment_allowance', 12, 2)->nullable();
            $table->decimal('living_allowance', 12, 2)->nullable();
            $table->decimal('expatriation_allowance', 12, 2)->nullable();
            $table->decimal('desert_allowance', 12, 2)->nullable();
            $table->string('id_card')->nullable();
            $table->date('id_card_issue_date')->nullable();
            $table->string('passport')->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->string('insurance_id')->nullable();
            $table->string('file_number')->nullable();
            $table->string('family_paper_number')->nullable();
            $table->string('family_record_number')->nullable();
            $table->string('full_record_number')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('education_level')->nullable();
            $table->string('specialization')->nullable();
            $table->string('education_year')->nullable();
            $table->string('study_country')->nullable();
            $table->string('study_institution')->nullable();
            $table->string('education_level_code')->nullable();
            $table->string('clinic_name')->nullable();
            $table->unsignedInteger('children_count')->nullable();
            $table->unsignedInteger('family_members_count')->nullable();
            $table->string('previous_employer')->nullable();
            $table->string('previous_job')->nullable();
            $table->string('previous_job_start')->nullable();
            $table->string('previous_job_end')->nullable();
            $table->string('years_of_experience')->nullable();
            $table->string('military_status')->nullable();
            $table->string('action_code')->nullable();
            $table->string('action_date')->nullable();
            $table->string('marriage_grant_date')->nullable();
            $table->date('unpaid_leave_start')->nullable();
            $table->date('unpaid_leave_end')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->text('hr_notes')->nullable();
        });

        $this->copyColumnsBack();

        Schema::dropIfExists('employee_details');
    }

    /**
     * A single INSERT ... SELECT rather than a row-by-row copy: there are thousands
     * of employees and the source columns are about to be dropped either way.
     */
    private function copyColumns(string $from, string $to, string $key): void
    {
        $columns = implode(', ', array_map(static fn (string $c): string => "`{$c}`", self::MOVED_COLUMNS));

        DB::statement(
            "INSERT INTO `{$to}` (`{$key}`, {$columns}, `created_at`, `updated_at`)
             SELECT `id`, {$columns}, NOW(), NOW() FROM `{$from}`"
        );
    }

    private function copyColumnsBack(): void
    {
        $assignments = implode(', ', array_map(
            static fn (string $c): string => "`e`.`{$c}` = `d`.`{$c}`",
            self::MOVED_COLUMNS,
        ));

        DB::statement(
            "UPDATE `employees` AS `e`
             JOIN `employee_details` AS `d` ON `d`.`employee_id` = `e`.`id`
             SET {$assignments}"
        );
    }
};
