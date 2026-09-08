<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeSheetCollection;
use App\Http\Resources\TimeSheetResource;
use App\Models\TimeSheet;
use App\Models\User;
use Illuminate\Http\Request;

class UserTimeSheetsController extends Controller
{
    public function index(Request $request, User $user): TimeSheetCollection
    {
        $this->authorize('view', $user);

        $search = $request->get('search', '');

        $timeSheets = $user
            ->timeSheets()
            ->search($search)
            ->latest()
            ->paginate();

        return new TimeSheetCollection($timeSheets);
    }

    public function store(Request $request, User $user): TimeSheetResource
    {
        $this->authorize('create', TimeSheet::class);

        $validated = $request->validate([
            'value' => [
                'required',
                'in:a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z',
            ],
            'day' => ['required', 'date'],
            'employee_id' => ['required', 'exists:employees,id'],
            'revised_at' => ['nullable', 'date'],
            'old_value' => ['nullable', 'max:255', 'string'],
        ]);

        $timeSheet = $user->timeSheets()->create($validated);

        return new TimeSheetResource($timeSheet);
    }
}
