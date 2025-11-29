<?php

namespace App\Jobs;

use App\Models\Flight;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FillRoomVacant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Flight $flight;
    /**
     * Create a new job instance.
     */
    public function __construct($flight)
    {
        $this->flight = $flight;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $employees  = $this->flight->employees;
        foreach ($employees as $employee)
        {
            if($employee->ownRoom)
            {
                foreach($employee->rooms as $room)
                {
                    if($room->pivot->is_owner)
                    {
                        $room->pivot->is_here = true;
                        $room->pivot->save();
                    }
                }
            }
        }
    }
}