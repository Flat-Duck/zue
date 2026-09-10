<?php

namespace App\Jobs;

use App\Models\Flight;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClearRoomVacant implements ShouldQueue
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
        // $employees = Employee::with('rooms')->where('number','>',2000)->get();
        $employees = $this->flight->employees;
        foreach ($employees as $employee) {
            // if($employee->rooms->first()->pivot->is_owner)
            //     {
            //         $employee->rooms->first()->pivot->is_here = false;
            //     }else{
            //         $employee->rooms->first()->pivot->delete();
            //     }

            foreach ($employee->rooms as $room) {
                // return dd($room);
                if ($room->pivot->is_owner) {
                    $room->pivot->is_here = false;
                    $room->pivot->save();
                } else {
                    $room->pivot->delete();
                }
            }
        }

        // foreach ($employees as $employee)
        // {
        //     // if($employee->rooms->first()->pivot->is_owner)
        //     //     {
        //     //         $employee->rooms->first()->pivot->is_here = false;
        //     //     }else{
        //     //         $employee->rooms->first()->pivot->delete();
        //     //     }

        //     foreach($employee->rooms as $room)
        //     {
        //         if($room->pivot->is_owner)
        //         {
        //             $room->pivot->is_here = false;
        //         }else{
        //             $room->pivot->delete();
        //         }
        //     }
        // }
    }
}
