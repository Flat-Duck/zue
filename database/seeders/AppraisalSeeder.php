<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalItem;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalPeriod;

class AppraisalSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Forms
        $formAdmin = AppraisalForm::updateOrCreate(
            ['code' => 'FORM_3_ADMIN_FIN'],
            ['name_ar' => 'أداء موظف (إداري/مالي)', 'is_active' => true]
        );

        // 2) Version
        $v1 = AppraisalFormVersion::updateOrCreate(
            ['appraisal_form_id' => $formAdmin->id, 'version' => 1],
            ['is_active' => true]
        );

        /**
         * 3) Items for Form #3 (Administrative/Financial)
         * - الأداء الوظيفي (700)
         * - الصفات الشخصية (190)
         * - المبادرة والتميز (110)
         */
        $items = [
            // =========================
            // 3) الأداء الوظيفي (max 700)
            // =========================
            ['key' => 'details_understanding', 'default_section' => 'job_performance', 'default_label' => 'مدى فهم وإستيعاب للتفاصيل المطلوبة لإنجاز الأعمال', 'max' => 80, 'sort' => 10],
            ['key' => 'accuracy_speed', 'default_section' => 'job_performance', 'default_label' => 'الدقة والسرعة في إنجاز الأعمال وبأقل نسبة ممكنة من الأخطاء', 'max' => 70, 'sort' => 20],
            ['key' => 'work_without_supervision', 'default_section' => 'job_performance', 'default_label' => 'المقدرة على أداء العمل بدون رقابة أو متابعة', 'max' => 70, 'sort' => 30],
            ['key' => 'review_audit', 'default_section' => 'job_performance', 'default_label' => 'القدرة على المراجعة والتدقيق', 'max' => 70, 'sort' => 40],
            ['key' => 'develop_gain_experience', 'default_section' => 'job_performance', 'default_label' => 'القدرة على التطوير واكتساب الخبرات في أداء العمل', 'max' => 60, 'sort' => 50],
            ['key' => 'ideas_suggestions', 'default_section' => 'job_performance', 'default_label' => 'المبادرة بتقديم الأفكار والمقترحات', 'max' => 60, 'sort' => 60],
            ['key' => 'plan_steps_timeline', 'default_section' => 'job_performance', 'default_label' => 'القدرة على تحديد خطوات العمل والبرامج الزمنية', 'max' => 60, 'sort' => 70],
            ['key' => 'knows_procedures', 'default_section' => 'job_performance', 'default_label' => 'الإلمام بنظم وإجراءات العمل', 'max' => 50, 'sort' => 80],
            ['key' => 'responsibility', 'default_section' => 'job_performance', 'default_label' => 'مدى تحمّل المسؤولية', 'max' => 50, 'sort' => 90],
            ['key' => 'attendance_punctuality', 'default_section' => 'job_performance', 'default_label' => 'المواظبة والمحافظة على مواعيد العمل', 'max' => 50, 'sort' => 100],
            ['key' => 'training_benefit_commit', 'default_section' => 'job_performance', 'default_label' => 'مدى الاستفادة من التدريب والالتزام بحضور الدورات التدريبية', 'max' => 40, 'sort' => 110],
            ['key' => 'safety_compliance', 'default_section' => 'job_performance', 'default_label' => 'التقيد بقواعد ونظم السلامة واتباع التعليمات المتعلقة بها', 'max' => 40, 'sort' => 120],

            // =========================
            // 4) الصفات الشخصية (max 190)
            // =========================
            ['key' => 'accept_guidance', 'default_section' => 'personal_traits', 'default_label' => 'تقبّل التوجيهات والاستعداد لتنفيذها', 'max' => 50, 'sort' => 210],
            ['key' => 'keep_confidentiality', 'default_section' => 'personal_traits', 'default_label' => 'المحافظة على أسرار العمل والمستندات السرية', 'max' => 40, 'sort' => 220],
            ['key' => 'work_under_pressure', 'default_section' => 'personal_traits', 'default_label' => 'القدرة على العمل تحت جملة من الضغوط', 'max' => 40, 'sort' => 230],
            ['key' => 'accept_change_methods', 'default_section' => 'personal_traits', 'default_label' => 'تقبّل التجديد في أساليب العمل', 'max' => 40, 'sort' => 240],
            ['key' => 'behavior_appearance', 'default_section' => 'personal_traits', 'default_label' => 'السلوك العام والعناية بالمظهر بما يتناسب وطبيعة الوظيفة', 'max' => 20, 'sort' => 250],

            // =========================
            // 5) المبادرة والتميز (max 110)
            // =========================
            ['key' => 'distinct_achievement', 'default_section' => 'initiative', 'default_label' => 'إنجاز أعمال مميزة على مستوى الشركة (تذكر في تقرير مرفق)', 'max' => 60, 'sort' => 310],
            ['key' => 'solve_complex_issues', 'default_section' => 'initiative', 'default_label' => 'حلحلة بعض المشاكل المعقدة بالإدارة (تذكر في تقرير مرفق)', 'max' => 50, 'sort' => 320],
        ];

        foreach ($items as $i) {
            $item = AppraisalItem::updateOrCreate(
                ['key' => $i['key']],
                [
                    'default_section' => $i['default_section'],
                    'default_label' => $i['default_label'],
                ]
            );

            AppraisalFormVersionItem::updateOrCreate(
                ['appraisal_form_version_id' => $v1->id, 'item_id' => $item->id],
                [
                    'max_score_override' => $i['max'],
                    'sort_order' => $i['sort'],
                    'is_required' => true,
                    'is_active' => true,
                ]
            );
        }

        // 4) Periods example for 2025 (عدّل التواريخ حسب قواعدك)
        // Q1: 25/03 -> 05/04
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
    }
}
