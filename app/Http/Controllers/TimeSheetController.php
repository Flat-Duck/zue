<?php

namespace App\Http\Controllers;

use App\Helpers\MomentsJs;
use App\Models\User;
use App\Models\Employee;
use App\Models\TimeSheet;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TimeSheetStoreRequest;
use App\Http\Requests\TimeSheetUpdateRequest;
use DateInterval;
use DatePeriod;
use DateTime;

class TimeSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        
        // $search = $request->get('search', '');
        
        // $timeSheets = TimeSheet::search($search)
        //     ->latest()
        //     ->paginate(5)
        //     ->withQueryString();
        
        $this->authorize('view-any', TimeSheet::class);
        $this->authorize('view-any', Employee::class);

        $search = $request->get('search', '');

        $employees = auth()->user()->managedEmployeesQuery()
        // = Employee::search($search)
        //     ->latest()
            ->paginate(20)
            ->withQueryString();

        //return view('app.employees.index', compact('employees', 'search'));

        return view('app.time_sheets.index', compact('employees', 'search'));
        //return view('app.time_sheets.index', compact('timeSheets', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, Employee $employee): View
    {
        // $this->authorize('create', TimeSheet::class);

        //     # code...
            // $employees = Employee::pluck('job', 'id');
            //     $users = User::pluck('name', 'id');
            // for ($i=2; $i < 10; $i++) {

            
        //     foreach ($period as $dt) {
        //         $timeSheet = new TimeSheet();
        //         $my_array = array('a','f');
        //         $random_key = array_rand($my_array);
        //         $val = $my_array[$random_key];
                
        //         $timeSheet->value = $val;
        //         $timeSheet->day = $dt->format('Y-m-d');
        //         $timeSheet->employee_id = $i;
        //         $timeSheet->save();
                
        //     }
        // }
        return view('app.time_sheets.create', compact('employee'));//, compact('employees', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimeSheetStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', TimeSheet::class);

        $validated = $request->validated();

        $timeSheet = TimeSheet::create($validated);






        return redirect()
            ->route('time-sheets.edit', $timeSheet)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, TimeSheet $timeSheet): View
    {
        $this->authorize('view', $timeSheet);

        return view('app.time_sheets.show', compact('timeSheet'));
    }

        /**
     * Show the form for editing the specified resource.
     */
    public function print_preview(Request $request): View
    {
        //$this->authorize('update', $timeSheet);

        //$employees = Employee::pluck('job', 'id');
        //$users = User::pluck('name', 'id');

        return view('app.time_sheets.print_preview');
    }

        /**
     * Show the form for editing the specified resource.
     */
    public function print(Request $request): View
    {
        $months = MomentsJs::getMonthsInYear();
        $month_name = $months->get($request->selected_month);
        
        $chunk = Timesheet::
        //whereHas('employee', function ($query) {
        //$query->whereNull('archived_at');
        //})//-
        whereMonth('day', $request->selected_month)->whereYear('day', '2025')->
        // limit(31)->
        get();
        // dd($chunk);
        $chunks = $chunk->groupBy('employee_id')->chunk(8);
        $first_chunk = $chunks->first()->first()->first();
        $signatures['time_keeper']['sign'] = $first_chunk->time_keeper?->signature->image_path;
        $signatures['time_keeper']['name'] = $first_chunk->time_keeper?->name;
        $signatures['super_visor']['sign'] = $first_chunk->super_visor?->signature->image_path;
        $signatures['super_visor']['name'] = $first_chunk->super_visor?->name;
        $signatures['super_intendent']['sign'] = $first_chunk->super_intendent?->signature->image_path;
        $signatures['super_intendent']['name'] = $first_chunk->super_intendent?->name;

        $month_days = Carbon::now()->month($month_name)->daysInMonth + 1;
        $employees = Employee::pluck('english_name','number');
        // return $chunks;
        // 
        return view('app.time_sheets.approve', compact('chunks', 'month_name', 'month_days','employees','signatures'));

        // return view('app.time_sheets.print_preview');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Employee $employee): View
    {
        //$this->authorize('update', $timeSheet);

        //$employees = Employee::pluck('job', 'id');
        //$users = User::pluck('name', 'id');

        return view('app.time_sheets.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        TimeSheetUpdateRequest $request,
        TimeSheet $timeSheet
    ): RedirectResponse
    {
        $this->authorize('update', $timeSheet);

        $validated = $request->validated();

        $timeSheet->update($validated);

        return redirect()
            ->route('time-sheets.edit', $timeSheet)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        TimeSheet $timeSheet
    ): RedirectResponse
    {
        $this->authorize('delete', $timeSheet);

        $timeSheet->delete();

        return redirect()
            ->route('time-sheets.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
