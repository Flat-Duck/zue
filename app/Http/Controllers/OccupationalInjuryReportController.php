<?php

namespace App\Http\Controllers;

use App\Models\OccupationalInjuryReport;
use App\Http\Requests\OccupationalInjuryReportStoreRequest;
use App\Http\Requests\OccupationalInjuryReportUpdateRequest;

class OccupationalInjuryReportController extends Controller
{
    public function index()
    {
        $reports = OccupationalInjuryReport::latest()->paginate(15);
        return view('app.clinic.injury_reports.index', compact('reports'));
    }

    public function create()
    {
        return view('app.clinic.injury_reports.create');
    }

    public function store(OccupationalInjuryReportStoreRequest $request)
    {
        $report = OccupationalInjuryReport::create($request->validated());
        return redirect()
            ->route('injury-reports.show', $report)
            ->with('ok', 'تم إنشاء التقرير بنجاح.');
    }

    public function show(OccupationalInjuryReport $injury_report)
    {
        return view('app.clinic.injury_reports.show', ['report' => $injury_report]);
    }

    public function edit(OccupationalInjuryReport $injury_report)
    {
        return view('app.clinic.injury_reports.edit', ['report' => $injury_report]);
    }

    public function update(OccupationalInjuryReportUpdateRequest $request, OccupationalInjuryReport $injury_report)
    {
        $injury_report->update($request->validated());
        return redirect()
            ->route('injury-reports.show', $injury_report)
            ->with('ok', 'تم تحديث التقرير.');
    }

    public function destroy(OccupationalInjuryReport $injury_report)
    {
        $injury_report->delete();
        return redirect()
            ->route('injury-reports.index')
            ->with('ok', 'تم الحذف.');
    }
}
