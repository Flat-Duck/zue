<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalItem;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalPeriod;
use \DB;
class AppraisalSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Shared Item Library (global keys)
         * - default_label is unified (generic)
         * - each form overrides max + sort only
         */

        $itemLibrary = [
            // ========= job_performance =========
            'work_requirements_knowledge' => ['section' => 'job_performance', 'label' => 'الإلمام بمتطلبات وحاجة العمل'],
            'planning_time_management'    => ['section' => 'job_performance', 'label' => 'التخطيط وإدارة الوقت'],
            'decision_making'            => ['section' => 'job_performance', 'label' => 'اتخاذ القرار المناسب'],
            'unit_improved_results'      => ['section' => 'job_performance', 'label' => 'تحسين نتائج الوحدة التنظيمية'],
            'meeting_discussion_style'   => ['section' => 'job_performance', 'label' => 'أسلوبه في المناقشة خلال الاجتماعات'],
            'followup_task_distribution' => ['section' => 'job_performance', 'label' => 'متابعة المهام وتوزيع العمل'],
            'ideas_suggestions'          => ['section' => 'job_performance', 'label' => 'المبادرة وتقديم الأفكار والمقترحات'],
            'problem_solving_simplify'   => ['section' => 'job_performance', 'label' => 'معالجة المشاكل وتبسيط العمل'],
            'develop_gain_experience'    => ['section' => 'job_performance', 'label' => 'تطوير الأداء واكتساب الخبرات في العمل'],
            'rules_compliance'           => ['section' => 'job_performance', 'label' => 'الالتزام بالتعليمات والقواعد المنظمة للعمل'],
            'report_writing_skill'       => ['section' => 'job_performance', 'label' => 'المهارة في إعداد التقارير'],
            'safety'                     => ['section' => 'job_performance', 'label' => 'اتباع قواعد ونظم الأمن والسلامة'],

            'technical_knowledge'        => ['section' => 'job_performance', 'label' => 'معرفة العمل والإلمام بالجوانب الفنية المتعلقة به'],
            'details_understanding'      => ['section' => 'job_performance', 'label' => 'فهم واستيعاب التفاصيل المطلوبة لإنجاز الأعمال'],
            'work_knowledge'             => ['section' => 'job_performance', 'label' => 'معرفة العمل ودرجة الإحاطة به'],

            'set_priorities'             => ['section' => 'job_performance', 'label' => 'القدرة على وضع الأولويات في العمل'],
            'accuracy_speed'             => ['section' => 'job_performance', 'label' => 'الدقة والسرعة في إنجاز الأعمال وبأقل نسبة ممكنة من الأخطاء'],
            'work_without_supervision'   => ['section' => 'job_performance', 'label' => 'أداء العمل بدون رقابة أو متابعة'],
            'reliability'                => ['section' => 'job_performance', 'label' => 'درجة الاعتماد عليه'],
            'maintain_tools_equipment'   => ['section' => 'job_performance', 'label' => 'المحافظة على أدوات ومعدات العمل'],
            'overcome_difficulties'      => ['section' => 'job_performance', 'label' => 'التغلب على صعوبات العمل'],
            'accept_others_opinions'     => ['section' => 'job_performance', 'label' => 'تقبل آراء الآخرين ومناقشتها'],
            'attendance_punctuality'     => ['section' => 'job_performance', 'label' => 'المواظبة والمحافظة على مواعيد العمل'],
            'training_commitment'        => ['section' => 'job_performance', 'label' => 'القابلية للتدريب والالتزام بحضور الدورات التدريبية'],
            'training_benefit'           => ['section' => 'job_performance', 'label' => 'مدى الاستفادة من التدريب والالتزام بحضور الدورات التدريبية'],

            'review_audit'               => ['section' => 'job_performance', 'label' => 'القدرة على المراجعة والتدقيق'],
            'responsibility'             => ['section' => 'job_performance', 'label' => 'تحمل المسؤولية'],
            'knows_procedures'           => ['section' => 'job_performance', 'label' => 'الإلمام بنظم وإجراءات العمل'],
            'plan_steps'                 => ['section' => 'job_performance', 'label' => 'القدرة على تحديد خطوات العمل'],

            'execution_skill'            => ['section' => 'job_performance', 'label' => 'المهارة في التنفيذ'],
            'cooperation_extra_tasks'    => ['section' => 'job_performance', 'label' => 'التعاون في الأعمال الإضافية'],
            'protect_company_property'   => ['section' => 'job_performance', 'label' => 'المحافظة على ممتلكات الشركة'],

            // ========= personal_traits =========
            'confidentiality'            => ['section' => 'personal_traits', 'label' => 'المحافظة على أسرار العمل والمستندات السرية'],
            'accept_change'              => ['section' => 'personal_traits', 'label' => 'تقبل التجديد في أساليب العمل'],
            'work_under_pressure'        => ['section' => 'personal_traits', 'label' => 'القدرة على العمل تحت جملة من الضغوط'],
            'accept_guidance'            => ['section' => 'personal_traits', 'label' => 'تقبل التوجيهات والاستعداد لتنفيذها'],
            'behavior_appearance'        => ['section' => 'personal_traits', 'label' => 'السلوك العام والعناية بالمظهر بما يتناسب وطبيعة الوظيفة'],

            // ========= initiative =========
            'distinct_achievement'       => ['section' => 'initiative', 'label' => 'إنجاز أعمال مميزة على مستوى الشركة (تذكر في تقرير مرفق)'],
            'solve_complex_issues'       => ['section' => 'initiative', 'label' => 'حلحلة بعض المشاكل المعقدة بالإدارة (تذكر في تقرير مرفق)'],
        ];

        // 1) Upsert all shared items once
        $itemsByKey = [];
        foreach ($itemLibrary as $key => $meta) {
            $itemsByKey[$key] = AppraisalItem::updateOrCreate(
                ['key' => $key],
                [
                    'default_section' => $meta['section'],
                    'default_label'   => $meta['label'],
                ]
            );
        }

        // 2) Forms + per-form overrides (max + sort)
        $forms = [
            // FORM 1: Supervisory
            [
                'code' => 'FORM_1_SUPERVISORY',
                'name_ar' => 'نموذج تقييم الأداء للوظائف الإشرافية',
                'version' => 1,
                'items' => [
                    // job_performance
                    ['key' => 'work_requirements_knowledge', 'max' => 80, 'sort' => 10],
                    ['key' => 'planning_time_management',    'max' => 80, 'sort' => 20],
                    ['key' => 'decision_making',            'max' => 70, 'sort' => 30],
                    ['key' => 'unit_improved_results',      'max' => 60, 'sort' => 40],
                    ['key' => 'meeting_discussion_style',   'max' => 60, 'sort' => 50],
                    ['key' => 'followup_task_distribution', 'max' => 60, 'sort' => 60],
                    ['key' => 'ideas_suggestions',          'max' => 60, 'sort' => 70],
                    ['key' => 'problem_solving_simplify',   'max' => 50, 'sort' => 80],
                    ['key' => 'develop_gain_experience',    'max' => 50, 'sort' => 90],
                    ['key' => 'rules_compliance',           'max' => 50, 'sort' => 100],
                    ['key' => 'report_writing_skill',       'max' => 40, 'sort' => 110],
                    ['key' => 'safety',                     'max' => 40, 'sort' => 120],

                    // personal_traits
                    ['key' => 'confidentiality',            'max' => 40, 'sort' => 210],
                    ['key' => 'accept_change',              'max' => 40, 'sort' => 220],
                    ['key' => 'work_under_pressure',        'max' => 40, 'sort' => 230],
                    ['key' => 'accept_guidance',            'max' => 50, 'sort' => 240],
                    ['key' => 'behavior_appearance',        'max' => 20, 'sort' => 250],

                    // initiative
                    ['key' => 'distinct_achievement',       'max' => 60, 'sort' => 310],
                    ['key' => 'solve_complex_issues',       'max' => 50, 'sort' => 320],
                ],
            ],

            // FORM 2: Technical
            [
                'code' => 'FORM_2_TECHNICAL',
                'name_ar' => 'نموذج تقييم الأداء للوظائف الفنية',
                'version' => 1,
                'items' => [
                    // job_performance
                    ['key' => 'technical_knowledge',        'max' => 80, 'sort' => 10],
                    ['key' => 'set_priorities',             'max' => 70, 'sort' => 20],
                    ['key' => 'accuracy_speed',             'max' => 70, 'sort' => 30],
                    ['key' => 'work_without_supervision',   'max' => 70, 'sort' => 40],
                    ['key' => 'reliability',                'max' => 60, 'sort' => 50],
                    ['key' => 'ideas_suggestions',          'max' => 60, 'sort' => 60],
                    ['key' => 'maintain_tools_equipment',   'max' => 50, 'sort' => 70],
                    ['key' => 'overcome_difficulties',      'max' => 50, 'sort' => 80],
                    ['key' => 'safety',                     'max' => 50, 'sort' => 90],
                    ['key' => 'accept_others_opinions',     'max' => 50, 'sort' => 100],
                    ['key' => 'attendance_punctuality',     'max' => 50, 'sort' => 110],
                    ['key' => 'training_commitment',        'max' => 40, 'sort' => 120],

                    // personal_traits
                    ['key' => 'accept_guidance',            'max' => 50, 'sort' => 210],
                    ['key' => 'confidentiality',            'max' => 40, 'sort' => 220],
                    ['key' => 'work_under_pressure',        'max' => 40, 'sort' => 230],
                    ['key' => 'accept_change',              'max' => 40, 'sort' => 240],
                    ['key' => 'behavior_appearance',        'max' => 20, 'sort' => 250],

                    // initiative
                    ['key' => 'distinct_achievement',       'max' => 60, 'sort' => 310],
                    ['key' => 'solve_complex_issues',       'max' => 50, 'sort' => 320],
                ],
            ],

            // FORM 3: Administrative/Financial
            [
                'code' => 'FORM_3_ADMIN_FIN',
                'name_ar' => 'نموذج تقييم الأداء للوظائف الإدارية و المالية',
                'version' => 1,
                'items' => [
                    // job_performance
                    ['key' => 'details_understanding',      'max' => 80, 'sort' => 10],
                    ['key' => 'accuracy_speed',             'max' => 70, 'sort' => 20],
                    ['key' => 'develop_gain_experience',    'max' => 70, 'sort' => 30],
                    ['key' => 'review_audit',               'max' => 70, 'sort' => 40],
                    ['key' => 'work_without_supervision',   'max' => 60, 'sort' => 50],
                    ['key' => 'responsibility',             'max' => 60, 'sort' => 60],
                    ['key' => 'knows_procedures',           'max' => 60, 'sort' => 70],
                    ['key' => 'plan_steps',                 'max' => 50, 'sort' => 80],
                    ['key' => 'ideas_suggestions',          'max' => 50, 'sort' => 90],
                    ['key' => 'attendance_punctuality',     'max' => 50, 'sort' => 100],
                    ['key' => 'training_benefit',           'max' => 40, 'sort' => 110],
                    ['key' => 'safety',                     'max' => 40, 'sort' => 120],

                    // personal_traits
                    ['key' => 'accept_guidance',            'max' => 50, 'sort' => 210],
                    ['key' => 'confidentiality',            'max' => 40, 'sort' => 220],
                    ['key' => 'work_under_pressure',        'max' => 40, 'sort' => 230],
                    ['key' => 'accept_change',              'max' => 40, 'sort' => 240],
                    ['key' => 'behavior_appearance',        'max' => 20, 'sort' => 250],

                    // initiative
                    ['key' => 'distinct_achievement',       'max' => 60, 'sort' => 310],
                    ['key' => 'solve_complex_issues',       'max' => 50, 'sort' => 320],
                ],
            ],

            // FORM 4: Craft/Service
            [
                'code' => 'FORM_4_CRAFT_SERVICE',
                'name_ar' => 'نموذج تقييم الأداء للوظائف الحرفية والخدمية',
                'version' => 1,
                'items' => [
                    // job_performance
                    ['key' => 'work_knowledge',             'max' => 80, 'sort' => 10],
                    ['key' => 'accuracy_speed',             'max' => 80, 'sort' => 20],
                    ['key' => 'work_without_supervision',   'max' => 70, 'sort' => 30],
                    ['key' => 'safety',                     'max' => 70, 'sort' => 40],
                    ['key' => 'execution_skill',            'max' => 60, 'sort' => 50],
                    ['key' => 'attendance_punctuality',     'max' => 60, 'sort' => 60],
                    ['key' => 'training_commitment',        'max' => 50, 'sort' => 70],
                    ['key' => 'set_priorities',             'max' => 50, 'sort' => 80],
                    ['key' => 'cooperation_extra_tasks',    'max' => 50, 'sort' => 90],
                    ['key' => 'plan_steps',                 'max' => 50, 'sort' => 100],
                    ['key' => 'maintain_tools_equipment',   'max' => 40, 'sort' => 110],
                    ['key' => 'protect_company_property',   'max' => 40, 'sort' => 120],

                    // personal_traits
                    ['key' => 'accept_guidance',            'max' => 50, 'sort' => 210],
                    ['key' => 'work_under_pressure',        'max' => 40, 'sort' => 220],
                    ['key' => 'behavior_appearance',        'max' => 40, 'sort' => 230],
                    ['key' => 'accept_change',              'max' => 40, 'sort' => 240],
                    ['key' => 'confidentiality',            'max' => 20, 'sort' => 250],

                    // initiative
                    ['key' => 'distinct_achievement',       'max' => 60, 'sort' => 310],
                    ['key' => 'solve_complex_issues',       'max' => 50, 'sort' => 320],
                ],
            ],
        ];

        foreach ($forms as $f) {
            $form = AppraisalForm::updateOrCreate(
                ['code' => $f['code']],
                ['name_ar' => $f['name_ar'], 'is_active' => true]
            );

            $version = AppraisalFormVersion::updateOrCreate(
                ['appraisal_form_id' => $form->id, 'version' => $f['version']],
                ['is_active' => true]
            );

            foreach ($f['items'] as $row) {
                $key = $row['key'];

                if (!isset($itemsByKey[$key])) {
                    // Safety fallback لو صادفت key مش موجود في المكتبة
                    continue;
                }

                AppraisalFormVersionItem::updateOrCreate(
                    ['appraisal_form_version_id' => $version->id, 'item_id' => $itemsByKey[$key]->id],
                    [
                        'max_score_override' => $row['max'],
                        'sort_order'         => $row['sort'],
                        'is_required'        => true,
                        'is_active'          => true,
                    ]
                );
            }
        }

        // Periods (نفس المثال اللي عندك)
        AppraisalPeriod::updateOrCreate(
            ['year' => 2025, 'type' => 'quarter', 'quarter' => 1],
            ['window_open_from' => '2025-03-25', 'window_open_to' => '2025-04-05', 'status' => 'planned']
        );
        AppraisalPeriod::updateOrCreate(
            ['year' => 2025, 'type' => 'quarter', 'quarter' => 2],
            ['window_open_from' => '2025-06-25', 'window_open_to' => '2025-07-05', 'status' => 'planned']
        );
        AppraisalPeriod::updateOrCreate(
            ['year' => 2025, 'type' => 'quarter', 'quarter' => 3],
            ['window_open_from' => '2025-09-25', 'window_open_to' => '2025-10-05', 'status' => 'planned']
        );
        AppraisalPeriod::updateOrCreate(
            ['year' => 2025, 'type' => 'quarter', 'quarter' => 4],
            ['window_open_from' => '2025-12-25', 'window_open_to' => '2027-01-05', 'status' => 'planned']
        );
        AppraisalPeriod::updateOrCreate(
            ['year' => 2025, 'type' => 'yearly', 'quarter' => null],
            ['window_open_from' => '2025-12-25', 'window_open_to' => '2027-01-31', 'status' => 'planned']
        );


        AppraisalPeriod::updateOrCreate(
            ['year' => 2025, 'type' => 'yearly', 'quarter' => null],
            ['window_open_from' => '2025-12-25', 'window_open_to' => '2027-01-31', 'status' => 'planned']
        );
        $form1 = AppraisalForm::where('code', 'FORM_1_SUPERVISORY')->value('id');
        $form2 = AppraisalForm::where('code', 'FORM_2_TECHNICAL')->value('id');
        $form3 = AppraisalForm::where('code', 'FORM_3_ADMIN_FIN')->value('id');
        $form4 = AppraisalForm::where('code', 'FORM_4_CRAFT_SERVICE')->value('id');

        // DB::table('employees')->where('job_type', 'supervisory')->update(['appraisal_form_id' => $form1]);
        // DB::table('employees')->where('job_type', 'technical')->update(['appraisal_form_id' => $form2]);
        // DB::table('employees')->where('job_type', 'admin_fin')->update(['appraisal_form_id' => $form3]);
        // DB::table('employees')->where('job_type', 'craft_service')->update(['appraisal_form_id' => $form4]);

        // واللي باقي Null حطله default
        DB::table('employees')->whereNull('appraisal_form_id')->update(['appraisal_form_id' => $form3]);
    }
}
