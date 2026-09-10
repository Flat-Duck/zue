<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The HR profile of one employee: everything personnel records, and nothing the
 * rest of the application operates on.
 *
 * It lives apart from `employees` so that listing staff, filling a time sheet or
 * printing a manifest does not read salaries, bank accounts and national ID
 * numbers into memory. Read these fields through the employee, which forwards
 * them; write them with Employee::saveProfile().
 */
class EmployeeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
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

    protected $casts = [
        'birth_date' => 'date',
        'job_start_date' => 'date',
        'appointment_date' => 'date',
        'id_card_issue_date' => 'date',
        'passport_issue_date' => 'date',
        'unpaid_leave_start' => 'date',
        'unpaid_leave_end' => 'date',
        'basic_salary' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'performance_incentive' => 'decimal:2',
        'secondment_allowance' => 'decimal:2',
        'living_allowance' => 'decimal:2',
        'expatriation_allowance' => 'decimal:2',
        'desert_allowance' => 'decimal:2',
        'children_count' => 'integer',
        'family_members_count' => 'integer',
    ];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
