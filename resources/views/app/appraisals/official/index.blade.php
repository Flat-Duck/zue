@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">نتائج التقييم النهائية (Official)</h2>
                    <div class="text-muted">لعام {{ $year }}</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <form method="GET" class="d-flex gap-2">
                        <select name="year" class="form-select w-auto" onchange="this.form.submit()">
                            @foreach(range(now()->year, now()->year - 2) as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="بحث عن موظف...">
                        <button class="btn btn-primary">بحث</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>الفترة</th>
                            <th>النوع</th>
                            <th>الدرجة</th>
                            <th>التقدير</th>
                            <th>التاريخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($officialAppraisals as $appraisal)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">
                                                        {{ $appraisal->employee->full_name ?? $appraisal->employee->first_name }}
                                                    </div>
                                                    <div class="text-muted small">{{ $appraisal->employee->job_title ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    {{ $appraisal->period->label }}
                                                </td>
                                                <td>
                                                    @if($appraisal->period->type === 'yearly')
                                                        <span class="badge bg-purple text-purple-fg">سنوي</span>
                                                    @else
                                                        <span class="badge bg-blue text-blue-fg">ربعي</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $appraisal->total_score }} / {{ $appraisal->max_score }}</div>
                                                    <div class="text-muted small">{{ $appraisal->percentage }}%</div>
                                                </td>
                                                <td>
                                                    <span class="badge {{ match ($appraisal->grade) {
                                'ممتاز' => 'bg-green',
                                'جيد جداً' => 'bg-teal',
                                'جيد' => 'bg-azure',
                                'مقبول' => 'bg-yellow',
                                default => 'bg-red'
                            } }}">
                                                        {{ $appraisal->grade ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>{{ $appraisal->created_at->format('Y-m-d') }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('appraisals.official.show', ['period' => $appraisal->appraisal_period_id, 'employee' => $appraisal->employee_id]) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        عرض التفاصيل
                                                    </a>
                                                </td>
                                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">لا توجد نتائج لهذه الفترة</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                {{ $officialAppraisals->links() }}
            </div>
        </div>
    </div>
@endsection