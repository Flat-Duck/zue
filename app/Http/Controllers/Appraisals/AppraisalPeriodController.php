<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appraisals\AppraisalPeriodRequest;
use App\Http\Requests\Appraisals\GenerateYearlyAppraisalsRequest;
use App\Jobs\GenerateYearlyAppraisalsJob;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalAggregationService;

class AppraisalPeriodController extends Controller
{
    public function index()
    {
        $periods = AppraisalPeriod::orderByDesc('year')
            ->orderByRaw("FIELD(type,'yearly','quarter')")
            ->orderBy('quarter')
            ->paginate(20);

        return view('app.appraisals.periods.index', compact('periods'));
    }

    public function create()
    {
        return view('app.appraisals.periods.create');
    }

    public function store(AppraisalPeriodRequest $request)
    {
        $validated = $request->validated();

        $exists = AppraisalPeriod::where('year', $validated['year'])
            ->where('type', $validated['type'])
            ->where('quarter', $validated['quarter'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Appraisal Period already exists for this timeframe.'])->withInput();
        }

        AppraisalPeriod::create($validated);

        return redirect()->route('appraisals.periods.index')->with('success', 'Period created successfully.');
    }

    public function edit(AppraisalPeriod $period)
    {
        return view('app.appraisals.periods.edit', compact('period'));
    }

    public function update(AppraisalPeriodRequest $request, AppraisalPeriod $period)
    {
        $validated = $request->validated();

        $exists = AppraisalPeriod::where('year', $validated['year'])
            ->where('type', $validated['type'])
            ->where('quarter', $validated['quarter'])
            ->where('id', '!=', $period->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Appraisal Period already exists for this timeframe.'])->withInput();
        }

        $period->update($validated);

        return redirect()->route('appraisals.periods.index')->with('success', 'Period updated successfully.');
    }

    public function destroy(AppraisalPeriod $period)
    {
        $period->delete();

        return redirect()->route('appraisals.periods.index')->with('success', 'Period deleted successfully.');
    }

    public function generateYearly(GenerateYearlyAppraisalsRequest $request, AppraisalAggregationService $service)
    {
        $validated = $request->validated();

        $year = (int) ($validated['year'] ?? now()->year);

        $employeeCount = Employee::query()->whereNull('archived_at')->count();
        $queueThreshold = (int) config('appraisals.yearly_queue_employee_threshold', 500);

        if ($employeeCount >= $queueThreshold && config('queue.default') !== 'sync') {
            GenerateYearlyAppraisalsJob::dispatch($year);

            return back()->with('success', "تم وضع توليد التقييم السنوي لعام {$year} في قائمة الانتظار.");
        }

        $service->aggregateYearly($year);

        return back()->with('success', "تم توليد التقييم السنوي لعام {$year} بنجاح.");
    }
}
