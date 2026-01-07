<?php

namespace App\Services\Appraisals;

use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalOfficialScore;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class AppraisalAggregationService
{
    public function __construct(
        protected AppraisalFinalizeService $finalizeService
    ) {
    }

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
            ->get();

        if ($quarterlyPeriods->isEmpty()) {
            return; // No quarters to aggregate
        }

        $quarterlyIds = $quarterlyPeriods->pluck('id');

        // 3. Process each employee who has at least one official appraisal in these quarters
        // We can chunk to avoid memory issues if thousands of employees
        Employee::query()->chunk(100, function ($employees) use ($yearlyPeriod, $quarterlyIds, $quarterlyPeriods) {
            foreach ($employees as $employee) {
                $this->aggregateForEmployee($employee, $yearlyPeriod, $quarterlyPeriods, $quarterlyIds);
            }
        });
    }

    private function aggregateForEmployee(Employee $employee, AppraisalPeriod $yearlyPeriod, $quarterlyPeriods, $quarterlyIds): void
    {
        // 0. Auto-Finalize any pending submitted reviews for these quarters
        foreach ($quarterlyPeriods as $p) {
            // This service method checks if there are submitted reviews and finalizes them
            // Passing null as finalizedByEmployeeId implies "System/Auto"
            // passing false as createIfEmpty to avoid creating empty official records for employees with no reviews
            $this->finalizeService->finalizeForEmployee($p, $employee->id, null, false);
        }

        // Fetch official results for this employee (Q1..Q4)
        $quarterlyResults = AppraisalOfficial::query()
            ->whereIn('appraisal_period_id', $quarterlyIds)
            ->where('employee_id', $employee->id)
            ->get();

        if ($quarterlyResults->isEmpty()) {
            return;
        }

        // Calculate Average Percentage
        // Note: You can choose to avg the raw scores or the percentage.
        // Averaging percentage is usually safer if form versions (max scores) changed between quarters.
        $avgPercentage = $quarterlyResults->avg('percentage');

        // Determine grade based on this average
        $grade = $this->gradeFromPercentage($avgPercentage);

        // Save Yearly Result
        $officialYearly = AppraisalOfficial::updateOrCreate(
            [
                'appraisal_period_id' => $yearlyPeriod->id,
                'employee_id' => $employee->id,
            ],
            [
                // We might not have a single "Form Version" for the year if they changed.
                // We can pick the latest one, or leave null.
                'appraisal_form_version_id' => $quarterlyResults->last()->appraisal_form_version_id,
                'reviews_count' => $quarterlyResults->count(), // How many quarters contributed
                'percentage' => round($avgPercentage, 2),
                'grade' => $grade,
                'finalized_at' => now(),
                'finalized_by' => null, // System generated
            ]
        );

        // Calculate and Save Item-Level Averages
        // We need to fetch the scores from the Q1-Q4 officials
        $quarterlyOfficialIds = $quarterlyResults->pluck('id');

        $avgScores = AppraisalOfficialScore::query()
            ->whereIn('appraisals_official_id', $quarterlyOfficialIds)
            ->select('form_version_item_id')
            ->selectRaw('AVG(avg_score) as final_avg')
            ->groupBy('form_version_item_id')
            ->get();

        // Clear old scores if any (for re-runs)
        AppraisalOfficialScore::where('appraisals_official_id', $officialYearly->id)->delete();

        foreach ($avgScores as $row) {
            AppraisalOfficialScore::create([
                'appraisals_official_id' => $officialYearly->id,
                'form_version_item_id' => $row->form_version_item_id,
                'avg_score' => round($row->final_avg, 2),
            ]);
        }
    }

    private function gradeFromPercentage(?float $p): ?string
    {
        if ($p === null)
            return null;
        if ($p >= 90)
            return 'ممتاز';
        if ($p >= 80)
            return 'جيد جداً';
        if ($p >= 70)
            return 'جيد';
        if ($p >= 60)
            return 'مقبول';
        return 'ضعيف';
    }
}
