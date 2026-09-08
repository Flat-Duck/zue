<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateYearlyAppraisalsJob;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalAggregationService;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'type' => 'required|in:quarter,yearly',
            'quarter' => 'nullable|integer|min:1|max:4|required_if:type,quarter',
            'window_open_from' => 'required|date',
            'window_open_to' => 'required|date|after_or_equal:window_open_from',
            'status' => 'required|in:planned,open,closed,locked',
        ]);

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

    public function update(Request $request, AppraisalPeriod $period)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'type' => 'required|in:quarter,yearly',
            'quarter' => 'nullable|integer|min:1|max:4|required_if:type,quarter',
            'window_open_from' => 'required|date',
            'window_open_to' => 'required|date|after_or_equal:window_open_from',
            'status' => 'required|in:planned,open,closed,locked',
        ]);

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

    public function generateYearly(Request $request, AppraisalAggregationService $service)
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);

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
