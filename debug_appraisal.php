<?php

use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Employee;

$year = 2025;

echo "--- DEBUGGING APPRAISAL AGGREGATION ($year) ---\n";

// 1. Check Periods
$periods = AppraisalPeriod::where('year', $year)->where('type', 'quarterly')->get();
echo "Found " . $periods->count() . " quarterly periods for $year.\n";
foreach ($periods as $p) {
    echo " - ID: {$p->id}, Name: {$p->label}, Status: {$p->status}\n";
}

// 2. Check Reviews
$reviews = AppraisalReview::whereHas('period', fn($q) => $q->where('year', $year))->get();
echo "Found " . $reviews->count() . " reviews for $year.\n";
foreach ($reviews as $r) {
    echo " - Review ID: {$r->id}, Employee: {$r->employee_id}, Status: {$r->status}, Period: {$r->appraisal_period_id}\n";
}

// 3. Check if FinalizeService would pick it up
// It looks for status='submitted'
$submitted = $reviews->where('status', 'submitted');
echo "Found " . $submitted->count() . " SUBMITTED reviews (ready for auto-finalize).\n";

$drafts = $reviews->where('status', 'draft');
echo "Found " . $drafts->count() . " DRAFT reviews (IGNORED by finalize).\n";
