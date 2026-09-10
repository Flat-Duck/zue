<?php

namespace App\Models;

use App\Helpers\TimeSheetBuilder;
use App\Models\Scopes\DepartmentEmployees;
use App\Models\Scopes\Searchable;
use App\Models\Scopes\SoftArchives;
use App\Models\Scopes\SoftArchivingScope;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read User|null $user
 * @property-read Signature|null $signature
 */
class Employee extends Model
{
    use HasFactory;
    use Searchable;
    use SoftArchives;
    use SoftDeletes;

    public const SPECIAL_WORK_DAYS_THRESHOLD = 20;

    protected $fillable = [
        'number',
        'job',
        'english_name',
        'id_card',
        'id_card_issue_date',
        'passport',
        'passport_issue_date',
        'address',
        'phone',
        'email',
        'location_id',
        'department_id',
        'center_id',
        'transfered_balance',
        'schedule',
        'start_date',
        'last_date',
        'total_balance',
        'archived_at',
        'management_level',
        'employee_level',

        // HR profile fields imported from the personnel export.
        'arabic_name',
        'arabic_full_name',
        'nationality',
        'gender',
        'marital_status',
        'birth_date',
        'birth_place',
        'mother_name',
        'mother_name_insurance',
        'salary_type',
        'basic_salary',
        'total_salary',
        'performance_incentive',
        'secondment_allowance',
        'living_allowance',
        'expatriation_allowance',
        'desert_allowance',
        'job_code',
        'job_title_en',
        'job_start_date',
        'job_note',
        'job_level',
        'old_grade',
        'grade',
        'job_number',
        'employment_type',
        'assignment_authority',
        'appointment_date',
        'appointment_job',
        'appointment_decision_number',
        'insurance_id',
        'file_number',
        'family_paper_number',
        'family_record_number',
        'full_record_number',
        'social_security_number',
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
        'action_code',
        'action_date',
        'military_status',
        'marriage_grant_date',
        'unpaid_leave_start',
        'unpaid_leave_end',
        'bank_name',
        'bank_account_number',
        'hr_notes',
    ];

    protected $appends = [
        'administration_name',
        'department_name',
        'location_name',
        'center_name',
        'start_date',
        'last_date',
        'balance',
        'default_over_time_value',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'id_card_issue_date' => 'date',
        'passport_issue_date' => 'date',
        'start_date' => 'date',
        'last_date' => 'date',
        'archived_at' => 'datetime',
        'birth_date' => 'date',
        'appointment_date' => 'date',
        'job_start_date' => 'date',
        'unpaid_leave_start' => 'date',
        'unpaid_leave_end' => 'date',
        'children_count' => 'integer',
        'family_members_count' => 'integer',
        'basic_salary' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'performance_incentive' => 'decimal:2',
        'secondment_allowance' => 'decimal:2',
        'living_allowance' => 'decimal:2',
        'expatriation_allowance' => 'decimal:2',
        'desert_allowance' => 'decimal:2',
    ];

    /**
     * Every employee field, grouped for display and editing.
     *
     * Work details come first because they are what the rest of the system
     * uses. Defined once so the show page, the create and edit forms and the
     * validation rules cannot drift apart; the Arabic label is the heading the
     * field carries in the personnel export.
     *
     * @return array<string, array{arabic: string, fields: array<string, array{label: string, arabic: string, type: string}>}>
     */
    public static function profileSections(): array
    {
        return [
            'Work' => [
                'arabic' => 'بيانات العمل',
                'fields' => [
                    'number' => ['label' => 'Employee number', 'arabic' => 'الرقم', 'type' => 'number'],
                    'english_name' => ['label' => 'Name (English)', 'arabic' => 'الاسم بالإنجليزية', 'type' => 'text'],
                    'arabic_name' => ['label' => 'Name (Arabic)', 'arabic' => 'الاسم', 'type' => 'text'],
                    'location_id' => ['label' => 'Location', 'arabic' => 'الموقع', 'type' => 'select:locations'],
                    'administration_id' => ['label' => 'Administration', 'arabic' => 'الادارة', 'type' => 'administration'],
                    'department_id' => ['label' => 'Department', 'arabic' => 'القسم', 'type' => 'select:departments'],
                    'center_id' => ['label' => 'Center', 'arabic' => 'مركز التكلفة', 'type' => 'select:centers'],
                    'schedule' => ['label' => 'Schedule', 'arabic' => 'الدورية', 'type' => 'text'],
                ],
            ],
            'Assignment and status' => [
                'arabic' => 'التعيين والحالة',
                'fields' => [
                    'job' => ['label' => 'Job', 'arabic' => 'الوظيفة', 'type' => 'text'],
                    'job_title_en' => ['label' => 'Job title (English)', 'arabic' => 'الوظيفة بالإنجليزية', 'type' => 'text'],
                    'employee_level' => ['label' => 'Employment level', 'arabic' => 'مستوى الموظف', 'type' => 'choice:employee_level'],
                    'management_level' => ['label' => 'Management level', 'arabic' => 'المستوى الإداري', 'type' => 'choice:management_level'],
                    'start_date' => ['label' => 'Start date', 'arabic' => 'تاريخ البدء', 'type' => 'date'],
                    'last_date' => ['label' => 'Last date', 'arabic' => 'تاريخ الانتهاء', 'type' => 'date'],
                    'transfered_balance' => ['label' => 'Transferred balance', 'arabic' => 'الرصيد المرحل', 'type' => 'number'],
                    'total_balance' => ['label' => 'Total balance', 'arabic' => 'إجمالي الرصيد', 'type' => 'number'],
                    'archived_at' => ['label' => 'Archived at', 'arabic' => 'تاريخ الأرشفة', 'type' => 'date'],
                ],
            ],
            'Identity' => [
                'arabic' => 'الهوية',
                'fields' => [
                    'arabic_full_name' => ['label' => 'Full Arabic name', 'arabic' => 'الإسم رباعي', 'type' => 'text'],
                    'nationality' => ['label' => 'Nationality', 'arabic' => 'الجنسية', 'type' => 'text'],
                    'gender' => ['label' => 'Gender', 'arabic' => 'الجنس', 'type' => 'text'],
                    'marital_status' => ['label' => 'Marital status', 'arabic' => 'الحالة الاجتماعية', 'type' => 'text'],
                    'birth_date' => ['label' => 'Date of birth', 'arabic' => 'تاريخ الميلاد', 'type' => 'date'],
                    'birth_place' => ['label' => 'Place of birth', 'arabic' => 'مكان الميلاد', 'type' => 'text'],
                    'mother_name' => ['label' => 'Mother\'s name', 'arabic' => 'اسم الام', 'type' => 'text'],
                    'mother_name_insurance' => ['label' => 'Mother\'s name (insurance)', 'arabic' => 'إسم الأم من التآمين', 'type' => 'text'],
                ],
            ],
            'Job details' => [
                'arabic' => 'تفاصيل الوظيفة',
                'fields' => [
                    'job_code' => ['label' => 'Job code', 'arabic' => 'رمز الوظيفة', 'type' => 'text'],
                    'job_start_date' => ['label' => 'Job start date', 'arabic' => 'تاريخ شغل الوظيفة', 'type' => 'date'],
                    'job_level' => ['label' => 'Job level', 'arabic' => 'مستوى الوظيفة', 'type' => 'text'],
                    'old_grade' => ['label' => 'Old grade', 'arabic' => 'الدرجة القديمة', 'type' => 'text'],
                    'grade' => ['label' => 'Grade', 'arabic' => 'الدرجة او التصنيف', 'type' => 'text'],
                    'job_number' => ['label' => 'Job number', 'arabic' => 'رقم الوظيفة', 'type' => 'text'],
                    'employment_type' => ['label' => 'Employment type', 'arabic' => 'نوع التوظيف', 'type' => 'text'],
                    'assignment_authority' => ['label' => 'Assignment authority', 'arabic' => 'جهة التنسيب', 'type' => 'text'],
                    'appointment_date' => ['label' => 'Appointment date', 'arabic' => 'تاريخ التعيين', 'type' => 'date'],
                    'appointment_job' => ['label' => 'Job at appointment', 'arabic' => 'الوظيفة عند التعيين', 'type' => 'text'],
                    'appointment_decision_number' => ['label' => 'Appointment decision no.', 'arabic' => 'رقم قرار التعيين', 'type' => 'text'],
                    'job_note' => ['label' => 'Job note', 'arabic' => 'ملاحظة الوظيفة', 'type' => 'text'],
                ],
            ],
            'Payroll' => [
                'arabic' => 'المرتب',
                'fields' => [
                    'salary_type' => ['label' => 'Salary type', 'arabic' => 'نوع المرتب', 'type' => 'text'],
                    'basic_salary' => ['label' => 'Basic salary', 'arabic' => 'المرتب الاساسي', 'type' => 'number'],
                    'total_salary' => ['label' => 'Total salary', 'arabic' => 'اجمالي المرتب', 'type' => 'number'],
                    'performance_incentive' => ['label' => 'Performance incentive', 'arabic' => 'حافز الآداء', 'type' => 'number'],
                    'secondment_allowance' => ['label' => 'Secondment allowance', 'arabic' => 'علاوة الإنتذاب', 'type' => 'number'],
                    'living_allowance' => ['label' => 'Living allowance', 'arabic' => 'علاوة المعيشة', 'type' => 'number'],
                    'expatriation_allowance' => ['label' => 'Expatriation allowance', 'arabic' => 'علاوة الإغتراب', 'type' => 'number'],
                    'desert_allowance' => ['label' => 'Desert allowance', 'arabic' => 'علاوة الصحراء', 'type' => 'number'],
                ],
            ],
            'Documents' => [
                'arabic' => 'الوثائق',
                'fields' => [
                    'id_card' => ['label' => 'ID card', 'arabic' => 'رقم البطاقة الشخصية', 'type' => 'text'],
                    'id_card_issue_date' => ['label' => 'ID card issued', 'arabic' => 'تاريخ إصدار البطاقة', 'type' => 'date'],
                    'passport' => ['label' => 'Passport', 'arabic' => 'رقم الجواز', 'type' => 'text'],
                    'passport_issue_date' => ['label' => 'Passport issued', 'arabic' => 'تاريخ إصدار الجواز', 'type' => 'date'],
                    'insurance_id' => ['label' => 'Insurance ID', 'arabic' => 'رقم البطاقة / التآمين', 'type' => 'text'],
                    'file_number' => ['label' => 'File number', 'arabic' => 'رقم الملف', 'type' => 'text'],
                    'family_paper_number' => ['label' => 'Family paper number', 'arabic' => 'رقم ورقة العائلة', 'type' => 'text'],
                    'family_record_number' => ['label' => 'Family record number', 'arabic' => 'رقم قيد العائلة', 'type' => 'text'],
                    'full_record_number' => ['label' => 'Full record number', 'arabic' => 'رقم كامل', 'type' => 'text'],
                    'social_security_number' => ['label' => 'Social security number', 'arabic' => 'الرقم الضماني', 'type' => 'text'],
                ],
            ],
            'Contact' => [
                'arabic' => 'الاتصال',
                'fields' => [
                    'phone' => ['label' => 'Phone', 'arabic' => 'رقم الهاتف', 'type' => 'text'],
                    'email' => ['label' => 'Email', 'arabic' => 'البريد الإلكتروني', 'type' => 'text'],
                    'address' => ['label' => 'Address', 'arabic' => 'العنوان', 'type' => 'text'],
                    'city' => ['label' => 'City', 'arabic' => 'المدينة', 'type' => 'text'],
                ],
            ],
            'Education' => [
                'arabic' => 'التعليم',
                'fields' => [
                    'education_level' => ['label' => 'Education level', 'arabic' => 'المستوى التعليمي', 'type' => 'text'],
                    'specialization' => ['label' => 'Specialisation', 'arabic' => 'التخصص', 'type' => 'text'],
                    'education_year' => ['label' => 'Year obtained', 'arabic' => 'تاريخ الحصول عليه', 'type' => 'text'],
                    'study_country' => ['label' => 'Country of study', 'arabic' => 'مكان الدراسة', 'type' => 'text'],
                    'study_institution' => ['label' => 'Institution', 'arabic' => 'جهة الدراسة', 'type' => 'text'],
                    'education_level_code' => ['label' => 'Education level code', 'arabic' => 'رمز المستوى التعليمي', 'type' => 'text'],
                ],
            ],
            'Welfare and history' => [
                'arabic' => 'الخدمة والخبرة',
                'fields' => [
                    'clinic_name' => ['label' => 'Clinic', 'arabic' => 'اسم المصحة', 'type' => 'text'],
                    'children_count' => ['label' => 'Children', 'arabic' => 'عـدد الابناء', 'type' => 'number'],
                    'family_members_count' => ['label' => 'Family members', 'arabic' => 'عدد أفراد العائلة', 'type' => 'number'],
                    'previous_employer' => ['label' => 'Previous employer', 'arabic' => 'جهة الخبرة السابقة', 'type' => 'text'],
                    'previous_job' => ['label' => 'Previous job', 'arabic' => 'الوظيفة السابقة', 'type' => 'text'],
                    'previous_job_start' => ['label' => 'Previous job start', 'arabic' => 'البداية', 'type' => 'text'],
                    'previous_job_end' => ['label' => 'Previous job end', 'arabic' => 'النهاية', 'type' => 'text'],
                    'years_of_experience' => ['label' => 'Years of experience', 'arabic' => 'سنوات الخبرة', 'type' => 'text'],
                    'military_status' => ['label' => 'Military status', 'arabic' => 'الوضع العسكري', 'type' => 'text'],
                    'action_code' => ['label' => 'Action code', 'arabic' => 'رمز الإجراء', 'type' => 'text'],
                    'action_date' => ['label' => 'Action date', 'arabic' => 'تاريخ الإجرء', 'type' => 'text'],
                    'marriage_grant_date' => ['label' => 'Marriage grant date', 'arabic' => 'تاريخ منح إعانة الزواج', 'type' => 'text'],
                    'unpaid_leave_start' => ['label' => 'Unpaid leave from', 'arabic' => 'بدايةإجازة بدون مرتب', 'type' => 'date'],
                    'unpaid_leave_end' => ['label' => 'Unpaid leave to', 'arabic' => 'نهايةإجازة بدون مرتب', 'type' => 'date'],
                ],
            ],
            'Banking' => [
                'arabic' => 'المصرف',
                'fields' => [
                    'bank_name' => ['label' => 'Bank', 'arabic' => 'المصرف', 'type' => 'text'],
                    'bank_account_number' => ['label' => 'Account number', 'arabic' => 'رقم الحساب', 'type' => 'text'],
                ],
            ],
            'Notes' => [
                'arabic' => 'ملاحظات',
                'fields' => [
                    'hr_notes' => ['label' => 'Notes', 'arabic' => 'ملاحظة', 'type' => 'textarea'],
                ],
            ],
        ];
    }

    /**
     * Validation rules derived from the same definition that renders the form.
     *
     * The relation fields keep the stricter rules the application already had;
     * everything else is optional, because the personnel export is partially
     * populated and a record must not be rejected for a field the business has
     * not captured.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function profileValidationRules(): array
    {
        $rules = [];

        foreach (self::profileSections() as $section) {
            foreach ($section['fields'] as $name => $field) {
                $type = $field['type'];

                // Derived from the department rather than stored, so it is
                // never submitted.
                if ($type === 'administration') {
                    continue;
                }

                $rules[$name] = match (true) {
                    $name === 'email' => ['nullable', 'email'],
                    $name === 'number' => ['nullable', 'numeric'],
                    str_starts_with($type, 'select:') => self::relationRule($name),
                    str_starts_with($type, 'choice:') => ['nullable', 'numeric'],
                    $type === 'date' => ['nullable', 'date'],
                    $type === 'number' => ['nullable', 'numeric'],
                    $type === 'textarea' => ['nullable', 'string', 'max:65535'],
                    default => ['nullable', 'string', 'max:255'],
                };
            }
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private static function relationRule(string $name): array
    {
        $table = match ($name) {
            'location_id' => 'locations',
            'department_id' => 'departments',
            'center_id' => 'centers',
            default => null,
        };

        // These were required before this form was generated, and stay required.
        return $table === null
            ? ['nullable', 'integer']
            : ['required', 'exists:'.$table.',id'];
    }

    /**
     * A display name for the employee.
     *
     * Arabic first, because that is what the printed manifests and signature
     * lines show; the English name is the fallback.
     */
    public function getNameAttribute(): ?string
    {
        return $this->arabic_name ?: $this->english_name;
    }

    /**
     * The login account for this employee, if they have one.
     *
     * The link is owned by `users.employee_id`: every user is an employee, but
     * most employees have no login.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * The signature belongs to the login account, so it is reached through it.
     */
    public function signature(): HasOneThrough
    {
        return $this->hasOneThrough(
            Signature::class,
            User::class,
            'employee_id',
            'user_id',
            'id',
            'id'
        );
    }

    public function timeSheets()
    {
        return $this->hasMany(TimeSheet::class);
    }

    public function clinicApointments()
    {
        return $this->hasMany(ClinicApointment::class);
    }

    public function apointments()
    {
        $appointments = $this->clinicApointments
            // ->select(
            //     DB::raw('YEAR(created_at) as year'),
            //     DB::raw('MONTHNAME(created_at) as month'),
            //     DB::raw('COUNT(*) as count'))
            //     ->groupBy('year', 'month')
            //     ->get();

            ->map(function ($appointment) {
                $appointment->year = Carbon::parse($appointment->date)->format('Y');
                $appointment->month = Carbon::parse($appointment->date)->format('F');

                return $appointment;
            });

        // Group appointments by year and month
        return $appointments->groupBy(function ($appointment) {
            return $appointment->year.'-'.$appointment->month;
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class)->withPivot(['is_owner', 'is_here']);
    }

    public function sick_leaves()
    {
        return 10;
    }

    public function flights()
    {
        return $this->belongsToMany(Flight::class);
    }

    public function getOwnRoomAttribute()
    {
        return $this->rooms()->where('is_owner', true)->exists();
    }

    public function getAdministrationNameAttribute()
    {
        return $this->department?->administration?->name;
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department?->name;
    }

    public function getLocationNameAttribute()
    {
        return $this->location?->name;
    }

    public function getCenterNameAttribute()
    {
        return $this->center?->name;
    }

    public function getStartDateAttribute($date)
    {
        if (is_null($date)) {
            return null;
        }

        return date('Y/m/d', strtotime($date));
    }

    public function getLastDateAttribute($date)
    {
        if (is_null($date)) {
            return null;
        }

        return date('Y/m/d', strtotime($date));
    }

    public function getBalanceAttribute()
    {
        return $this->total_balance;
    }

    public function calculateBalance()
    {
        $balance = TimeSheetBuilder::calculateBalance($this->id, $this->schedule, $this->transfered_balance);
        $this->total_balance = $balance;
        $this->save();
    }

    public function getTotalWorkingDaysAttribute()
    {
        return $this->timeSheets()->whereIn('value', ['A', 'B', 'Y', 'K'])->count();
    }

    public function getTotalOffDaysAttribute()
    {
        return $this->timeSheets()->whereIn('value', ['F', 'X'])->count();
    }

    public function getDefaultOverTimeValueAttribute()
    {
        return 2;
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isFieldCoordinator(): bool
    {
        return $this->hasRole('fieldcoordinator');
    }

    public function isSuperintendent(): bool
    {
        return $this->hasRole('superintendent');
    }

    public function isTimekeeper(): bool
    {
        return $this->hasRole('timekeeper');
    }

    public function managementScopes(): BelongsToMany
    {
        return $this->belongsToMany(ManagementScope::class, 'management_scope_manager', 'manager_id', 'management_scope_id');
    }

    /**
     * Legacy owned scopes.
     */
    public function ownedManagementScopes(): HasMany
    {
        return $this->hasMany(ManagementScope::class, 'manager_id');
    }

    public function getFullNameAttribute(): string
    {
        return (string) $this->english_name;
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Query builder for all employees this employee can manage.
     * Use this if you want to paginate, eager-load, etc.
     */
    public function managedEmployeesQuery(?string $context = 'general'): Builder
    {
        $scopes = $this->managementScopes()
            ->where(function ($q) use ($context) {
                if ($context) {
                    $q->where('context', $context);
                }
            })
            ->get();

        // If no scope is defined, this manager manages nobody
        if ($scopes->isEmpty()) {
            return static::query()->whereRaw('0 = 1');
        }

        $query = static::query()
            ->whereNull('archived_at')
            ->where(function (Builder $q) use ($scopes) {
                foreach ($scopes as $scope) {
                    $settings = is_array($scope->settings) ? $scope->settings : [];
                    $jobTitle = $settings['job_title'] ?? null;

                    $applySettings = function (Builder $query) use ($jobTitle) {
                        if ($jobTitle) {
                            $query->where('job', 'like', trim($jobTitle));
                        }
                    };

                    switch ($scope->scope_type) {
                        case ManagementScope::TYPE_GLOBAL:
                            $q->orWhere(function (Builder $q2) use ($applySettings) {
                                $applySettings($q2);
                            });
                            break;

                        case ManagementScope::TYPE_LOCATION:
                            if ($scope->location_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2->where('location_id', $scope->location_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_DEPARTMENT:
                            if ($scope->location_id && $scope->department_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2
                                        ->where('location_id', $scope->location_id)
                                        ->where('department_id', $scope->department_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_CENTER:
                            if ($scope->center_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2->where('center_id', $scope->center_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_EMPLOYEE:
                            // 1. Direct subordinate
                            if ($scope->subordinate_employee_id) {
                                $q->orWhere('id', $scope->subordinate_employee_id);
                            }
                            // 2. Grouped subordinates
                            $targetIds = $settings['target_employee_ids'] ?? [];
                            if (! empty($targetIds)) {
                                $q->orWhereIn('id', $targetIds);
                            }
                            break;
                    }
                }
            });

        if ($context !== 'time_sheet') {
            return $query;
        }

        // Strict ownership for time-sheet context:
        // - hide employees assigned as subordinate/target in another manager's scope
        // - hide employees that are managers in any time_sheet scope
        $candidateIds = (clone $query)->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($candidateIds->isEmpty()) {
            return static::query()->whereRaw('0 = 1');
        }

        $timeSheetScopes = ManagementScope::query()
            ->where('context', 'time_sheet')
            ->with('managers:id')
            ->get([
                'id',
                'manager_id',
                'subordinate_employee_id',
                'settings',
            ]);

        $disallowedIds = [];
        $managerPoolIds = [];
        $candidateLookup = $candidateIds->flip();
        $myEmployeeScopeAllowedIds = [];

        foreach ($scopes as $myScope) {
            if ($myScope->scope_type !== ManagementScope::TYPE_EMPLOYEE) {
                continue;
            }

            if (! is_null($myScope->subordinate_employee_id)) {
                $myEmployeeScopeAllowedIds[] = (int) $myScope->subordinate_employee_id;
            }

            $myTargetIds = (array) ($myScope->settings['target_employee_ids'] ?? []);
            foreach ($myTargetIds as $myTargetId) {
                $myEmployeeScopeAllowedIds[] = (int) $myTargetId;
            }
        }

        $myEmployeeScopeAllowedLookup = collect(array_unique($myEmployeeScopeAllowedIds))->flip();

        foreach ($timeSheetScopes as $scope) {
            $managerIds = $scope->managers->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (empty($managerIds) && ! is_null($scope->manager_id)) {
                $managerIds = [(int) $scope->manager_id];
            }
            foreach ($managerIds as $managerId) {
                $managerPoolIds[] = (int) $managerId;
            }

            if (in_array((int) $this->id, $managerIds, true)) {
                continue;
            }

            if (! is_null($scope->subordinate_employee_id)) {
                $subordinateId = (int) $scope->subordinate_employee_id;
                if ($candidateLookup->has($subordinateId)) {
                    $disallowedIds[] = $subordinateId;
                }
            }

            $targetIds = (array) ($scope->settings['target_employee_ids'] ?? []);
            foreach ($targetIds as $targetId) {
                $targetId = (int) $targetId;
                if ($candidateLookup->has($targetId)) {
                    $disallowedIds[] = $targetId;
                }
            }
        }

        foreach (array_unique($managerPoolIds) as $managerId) {
            if ($candidateLookup->has($managerId)) {
                // Manager-employees are only visible when explicitly assigned in
                // one of my own employee-type scopes.
                if ($myEmployeeScopeAllowedLookup->has((int) $managerId)) {
                    continue;
                }
                $disallowedIds[] = $managerId;
            }
        }

        $allowedIds = $candidateIds
            ->diff(array_unique($disallowedIds))
            ->values()
            ->all();

        if (empty($allowedIds)) {
            return static::query()->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $allowedIds);
    }

    /**
     * Get all managed employees as a collection.
     */
    public function managedEmployees(?string $context = 'general')
    {
        return $this->managedEmployeesQuery($context)->get();
    }

    /**
     * True/false check if this employee can manage the target.
     */
    public function canManage(Employee $target): bool
    {
        if ($this->id === $target->id) {
            return false;
        }

        if ($this->trashed() || $target->trashed()) {
            return false;
        }

        if ($this->isArchived() || $target->isArchived()) {
            return false;
        }

        return $this
            ->managedEmployeesQuery()
            ->where('id', $target->id)
            ->exists();
    }

    /**
     * High-level check: can this employee manage the target employee?
     */
    // public function canManage(Employee $target): bool
    // {
    //     $scopes = $this->managementScopes()->get();

    //     foreach ($scopes as $scope) {
    //         if ($scope->matchesTargetEmployee($target)) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    protected static function boot()
    {
        parent::boot();
        // if (Auth::check() && auth()->user()->hasRole('supervisor'))
        // {
        //     static::addGlobalScope(new DepartmentEmployees(auth()->user()->center()));
        // }
        // if (Auth::check() && (auth()->user()->hasRole('supervisor')
        //     ||auth()->user()->hasRole('supervisor') ||auth()->user()->hasRole('supervisor')))
        // {
        //     static::addGlobalScope(new UnderSupervisionEmployees(
        //         auth()->user()->center(),
        //         auth()->user()->department(),
        //         auth()->user()->location()
        //         )
        //     );
        // }
        static::addGlobalScope(new SoftArchivingScope);
    }
}
