<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeSheetCollection;
use App\Http\Resources\TimeSheetResource;
use App\Models\Employee;
use App\Models\TimeSheet;
use Illuminate\Http\Request;

class EmployeeTimeSheetsController extends Controller
{
    public function index(
        Request $request,
        Employee $employee
    ): TimeSheetCollection {
        $this->authorize('view', $employee);

        $search = $request->get('search', '');

        $timeSheets = $employee
            ->timeSheets()
            ->search($search)
            ->latest()
            ->paginate();

        return new TimeSheetCollection($timeSheets);
    }

    public function store(
        Request $request,
        Employee $employee
    ): TimeSheetResource {
        $this->authorize('create', TimeSheet::class);

        $validated = $request->validate([
            'value' => [
                'required',
                'in:a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z',
            ],
            'day' => ['required', 'date'],
            'revised_at' => ['nullable', 'date'],
            'old_value' => ['nullable', 'max:255', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $timeSheet = $employee->timeSheets()->create($validated);

        return new TimeSheetResource($timeSheet);
    }
}
