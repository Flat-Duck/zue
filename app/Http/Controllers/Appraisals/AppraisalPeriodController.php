<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Appraisals\AppraisalPeriod;

class AppraisalPeriodController extends Controller
{
    public function index()
    {
        $periods = AppraisalPeriod::query()
            ->orderByDesc('year')
            ->orderByRaw("FIELD(type,'yearly','quarter')")
            ->orderBy('quarter')
            ->get();

        return view('app.appraisals.periods.index', compact('periods'));
    }
}
