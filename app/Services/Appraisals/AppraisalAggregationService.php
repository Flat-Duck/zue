<?php

namespace App\Services\Appraisals;

use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalOfficialScore;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class AppraisalAggregationService
{
    public function __construct(
        protected AppraisalFinalizeService $finalizeService
    ) {}

    public function aggregateYearly(int $year): void
    {
        // 1. Ensure/Find Yearly Period
        $yearlyPeriod = AppraisalPeriod::firstOrCreate(
            [
                'year' => $year,
                'type' => 'yearly',
            ],
            [
                // Defaults if creating new
                'status' => 'planned',
                'window_open_from' => "{$year}-12-01",
                'window_open_to' => "{$year}-12-31",
            ]
        );

        // 2. Find all quarterly periods for this year
        $quarterlyPeriods = AppraisalPeriod::query()
            ->where('year', $year)
            // The DB enum might be 'quarter' or 'quarterly'. Seeder seems to use 'quarter'.
            // Let's match what's in DB. based on debug it is 'quarter'
            ->whereIn('type', ['quarter', 'quarterly'])
            ->orderBy('quarter')
            ->orderBy('id')
            ->get();

        if ($quarterlyPeriods->isEmpty()) {
            return; // No quarters to aggregate
        }

        $quarterlyIds = $quarterlyPeriods->pluck('id');

        $employeeIds = AppraisalReview::query()
            ->whereIn('appraisal_period_id', $quarterlyIds)
            ->whereIn('status', ['submitted', 'locked'])
            ->select('employee_id')
            ->union(
                AppraisalOfficial::query()
                    ->whereIn('appraisal_period_id', $quarterlyIds)
                    ->select('employee_id')
            );

        Employee::query()
            ->whereIn('id', $employeeIds)
            ->orderBy('id')
            ->chunkById(100, function ($employees) use ($yearlyPeriod, $quarterlyIds, $quarterlyPeriods): void {
                foreach ($employees as $employee) {
                    $this->aggregateForEmployee($employee, $yearlyPeriod, $quarterlyPeriods, $quarterlyIds);
                }
            });
    }

    private function aggregateForEmployee(Employee $employee, AppraisalPeriod $yearlyPeriod, $quarterlyPeriods, $quarterlyIds): void
    {
        DB::transaction(function () use ($employee, $yearlyPeriod, $quarterlyPeriods, $quarterlyIds): void {
            foreach ($quarterlyPeriods as $period) {
                $this->finalizeService->finalizeForEmployee($period, $employee->id, null, false);
            }

            $quarterlyResults = AppraisalOfficial::query()
                ->whereIn('appraisal_period_id', $quarterlyIds)
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->get();

            if ($quarterlyResults->isEmpty()) {
                return;
            }

            $avgPercentage = $quarterlyResults->avg('percentage');

            $grade = AppraisalGrade::fromPercentage($avgPercentage);

            $officialYearly = AppraisalOfficial::updateOrCreate(
                [
                    'appraisal_period_id' => $yearlyPeriod->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'appraisal_form_version_id' => $quarterlyResults->sortBy(fn (AppraisalOfficial $official): int => (int) $quarterlyIds->search($official->appraisal_period_id))->last()->appraisal_form_version_id,
                    'reviews_count' => $quarterlyResults->count(),
                    'percentage' => round($avgPercentage, 2),
                    'grade' => $grade,
                    'finalized_at' => now(),
                    'finalized_by' => null,
                ]
            );

            $quarterlyOfficialIds = $quarterlyResults->pluck('id');

            $avgScores = AppraisalOfficialScore::query()
                ->whereIn('appraisals_official_id', $quarterlyOfficialIds)
                ->select('form_version_item_id')
                ->selectRaw('AVG(avg_score) as final_avg')
                ->groupBy('form_version_item_id')
                ->get();

            AppraisalOfficialScore::where('appraisals_official_id', $officialYearly->id)->delete();

            foreach ($avgScores as $row) {
                AppraisalOfficialScore::create([
                    'appraisals_official_id' => $officialYearly->id,
                    'form_version_item_id' => $row->form_version_item_id,
                    'avg_score' => round($row->final_avg, 2),
                ]);
            }
        });
    }
}
