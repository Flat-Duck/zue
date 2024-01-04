<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Employee $employee;
    /**
     * Create a new job instance.
     */
    public function __construct($employee)
    {
        $this->employee = $employee;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->employee->calculateBalance();
        $this->employee->update(["last_date" => $this->employee->timeSheets()->max('day')]);
    }
}
