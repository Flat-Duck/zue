<?php

namespace App\Console\Commands;

use App\Services\Appraisals\AppraisalAggregationService;
use Illuminate\Console\Command;

class GenerateYearlyAppraisals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appraisal:generate-yearly {year? : The year to aggregate (default: current year)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates quarterly appraisals into a final yearly appraisal';

    /**
     * Execute the console command.
     */
    public function handle(AppraisalAggregationService $service): int
    {
        $year = $this->argument('year') ?? now()->year;

        $this->info("Starting aggregation for year: {$year}...");

        $service->aggregateYearly((int) $year);

        $this->info("Yearly appraisals generated successfully.");

        return self::SUCCESS;
    }
}
