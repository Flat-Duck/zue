<?php

use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalFinalizeService;

echo "--- DEBUG FINALIZATION ---\n";

$employee = Employee::find(8833);
if (!$employee) {
    die("Employee 8833 not found.\n");
}
echo "Employee: {$employee->id} \n";

$period = AppraisalPeriod::find(4);
if (!$period) {
    die("Period 4 not found.\n");
}
echo "Period: {$period->id}, Year: {$period->year}, Q: {$period->quarter}\n";

// Check Reviews
$reviews = AppraisalReview::where('appraisal_period_id', $period->id)
    ->where('employee_id', $employee->id)
    ->get();

echo "Found " . $reviews->count() . " reviews for this E+P.\n";
foreach ($reviews as $r) {
    echo " - ID: {$r->id}, Status: {$r->status}\n";
}

$submitted = $reviews->where('status', 'submitted');
echo "Submitted Count: " . $submitted->count() . "\n";

if ($submitted->count() > 0) {
    echo "Attempting to finalize...\n";
    $service = app(AppraisalFinalizeService::class);
    try {
        // createIfEmpty = false (simulating aggregation service)
        $official = $service->finalizeForEmployee($period, $employee->id, null, false);

        if ($official) {
            echo "SUCCESS: Created Official ID {$official->id}\n";
            echo "Total Score: {$official->total_score}\n";
        } else {
            echo "FAILURE: Returned null (and createIfEmpty false)\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString();
    }
} else {
    echo "No submitted reviews, nothing to finalize.\n";
}

// Check DB
echo "Official Count in DB: " . AppraisalOfficial::count() . "\n";
