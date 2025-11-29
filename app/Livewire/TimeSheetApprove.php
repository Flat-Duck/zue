<?php

namespace App\Livewire;

use App\Helpers\TimeSheetBuilder;
use App\Models\TimeSheet;
use Carbon\Carbon;
use Livewire\Component;

class TimeSheetApprove extends Component
{
    public $employee_id = 0;
    public $level = 0;

    public function mount()
    {
        $this->level = TimeSheetBuilder::unApprovedTimeSheetLevel($this->employee_id);
    }

    public function render()
    {
        return view('livewire.time-sheet-approve');
    }

    // public function approve(){

    //     TimeSheet::where('employee_id', $this->employee_id)

    //     // ->where('created_at', '>=', Carbon::now()->subHour())
    //     ->whereNull('timekeeper_id')
    //     ->where('created_at', '<=', Carbon::now())->update(['timekeeper_id' => auth()->id()]);

    // }
     public function approveAsTimekeeper()
    {
        TimeSheet::where('employee_id', $this->employee_id)
            ->whereNull('timekeeper_id')
            ->where('created_at', '<=', Carbon::now())
            ->update(['timekeeper_id' => auth()->id()]);
    }

    public function approveAsSupervisor()
    {
        TimeSheet::where('employee_id', $this->employee_id)
            ->whereNull('supervisor_id')
            ->where('created_at', '<=', Carbon::now())
            ->update(['supervisor_id' => auth()->id()]);
    }

    public function approveAsSuperintendent()
    {
        TimeSheet::where('employee_id', $this->employee_id)
            ->whereNull('superintendent_id')
            ->where('created_at', '<=', Carbon::now())
            ->update(['superintendent_id' => auth()->id()]);
    }
}
