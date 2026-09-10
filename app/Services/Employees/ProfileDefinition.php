<?php

namespace App\Services\Employees;

/**
 * What an employee record consists of.
 *
 * One definition drives the show page, the create and edit forms, the validation
 * rules and the personnel import, so those four cannot drift apart. Each section
 * also declares where it is stored: anything marked `details` lives on the
 * employee's HR profile rather than on the employee row itself.
 */
class ProfileDefinition
{
    /**
     * Every employee field, grouped for display and editing.
     *
     * Work details come first because they are what the rest of the system
     * uses. Defined once so the show page, the create and edit forms and the
     * validation rules cannot drift apart; the Arabic label is the heading the
     * field carries in the personnel export.
     *
     * Each section declares where it is stored. Anything marked `details` lives on
     * the employee's HR profile rather than on the employee itself.
     *
     * @return array<string, array{stored_in?: string, arabic: string, fields: array<string, array{label: string, arabic: string, type: string}>}>
     */
    public static function sections(): array
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
                'stored_in' => 'details',
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
                'stored_in' => 'details',
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
                'stored_in' => 'details',
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
                'stored_in' => 'details',
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
                'stored_in' => 'details',
                'arabic' => 'الاتصال',
                'fields' => [
                    'phone' => ['label' => 'Phone', 'arabic' => 'رقم الهاتف', 'type' => 'text'],
                    'email' => ['label' => 'Email', 'arabic' => 'البريد الإلكتروني', 'type' => 'text'],
                    'address' => ['label' => 'Address', 'arabic' => 'العنوان', 'type' => 'text'],
                    'city' => ['label' => 'City', 'arabic' => 'المدينة', 'type' => 'text'],
                ],
            ],
            'Education' => [
                'stored_in' => 'details',
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
                'stored_in' => 'details',
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
                'stored_in' => 'details',
                'arabic' => 'المصرف',
                'fields' => [
                    'bank_name' => ['label' => 'Bank', 'arabic' => 'المصرف', 'type' => 'text'],
                    'bank_account_number' => ['label' => 'Account number', 'arabic' => 'رقم الحساب', 'type' => 'text'],
                ],
            ],
            'Notes' => [
                'stored_in' => 'details',
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
    public static function validationRules(): array
    {
        $rules = [];

        foreach (static::sections() as $section) {
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
     * The fields kept on the HR profile rather than on the employee.
     *
     * @return list<string>
     */
    public static function detailFields(): array
    {
        static $fields = null;

        return $fields ??= collect(static::sections())
            ->filter(fn (array $section): bool => ($section['stored_in'] ?? null) === 'details')
            ->flatMap(fn (array $section): array => array_keys($section['fields']))
            ->values()
            ->all();
    }
}
