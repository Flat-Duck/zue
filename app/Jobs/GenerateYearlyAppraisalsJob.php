<?php

namespace App\Jobs;

use App\Services\Appraisals\AppraisalAggregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateYearlyAppraisalsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Yearly aggregation walks every employee with a submitted review, so it is
     * long-running by nature rather than by accident.
     */
    public int $timeout = 1800;

    public int $tries = 3;

    /**
     * Escalating waits: a transient database problem clears quickly, a
     * saturated one does not.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * Stop the unique lock outliving a worker that died mid-aggregation.
     */
    public int $uniqueFor = 3600;

    public function __construct(public readonly int $year) {}

    /**
     * One aggregation per year at a time. The service is idempotent, so a
     * duplicate run is safe rather than corrupting — but it is still wasted
     * work against the same rows, and two runs racing each other is worth
     * preventing outright.
     */
    public function uniqueId(): string
    {
        return 'appraisals-yearly-'.$this->year;
    }

    public function handle(AppraisalAggregationService $service): void
    {
        $service->aggregateYearly($this->year);
    }
}
