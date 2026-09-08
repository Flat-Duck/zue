<?php

namespace App\Http\Controllers;

use App\Http\Requests\OccupationalInjuryReportStoreRequest;
use App\Http\Requests\OccupationalInjuryReportUpdateRequest;
use App\Models\OccupationalInjuryReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OccupationalInjuryReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-clinic');
    }

    public function index(): View
    {
        $reports = OccupationalInjuryReport::query()
            ->latest()
            ->paginate(15);

        return view('app.clinic.injury_reports.index', compact('reports'));
    }

    public function create(): View
    {
        return view('app.clinic.injury_reports.create');
    }

    public function store(OccupationalInjuryReportStoreRequest $request): RedirectResponse
    {
        $report = OccupationalInjuryReport::query()->create($request->validated());

        return redirect()
            ->route('injury-reports.show', $report)
            ->with('ok', 'تم إنشاء التقرير بنجاح.');
    }

    public function show(OccupationalInjuryReport $injury_report): View
    {
        return view('app.clinic.injury_reports.show', ['report' => $injury_report]);
    }

    public function edit(OccupationalInjuryReport $injury_report): View
    {
        return view('app.clinic.injury_reports.edit', ['report' => $injury_report]);
    }

    public function update(OccupationalInjuryReportUpdateRequest $request, OccupationalInjuryReport $injury_report): RedirectResponse
    {
        $injury_report->update($request->validated());

        return redirect()
            ->route('injury-reports.show', $injury_report)
            ->with('ok', 'تم تحديث التقرير.');
    }

    public function destroy(OccupationalInjuryReport $injury_report): RedirectResponse
    {
        $injury_report->delete();

        return redirect()
            ->route('injury-reports.index')
            ->with('ok', 'تم الحذف.');
    }
}
