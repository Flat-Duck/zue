@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">تقييم: {{ $review->employee->name ?? ('#' . $review->employee_id) }}</h2>
                    <div class="text-muted">
                        الفترة: {{ $review->period->label }} —
                        النموذج: {{ $review->formVersion->form->name_ar }} (v{{ $review->formVersion->version }})
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <span class="badge bg-secondary">{{ strtoupper($review->status) }}</span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-label">القسم</div>
                        <div class="fw-bold">{{ $review->employee->department->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">الإدارة</div>
                        <div class="fw-bold">{{ $review->employee->administration->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">الموقع</div>
                        <div class="fw-bold">{{ $review->employee->location->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">مركز التكلفة</div>
                        <div class="fw-bold">{{ $review->employee->costCenter->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="hr-text">النتيجة</div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted">المجموع</div>
                        <div class="h3">{{ $review->total_score ?? 0 }} / {{ $review->max_score ?? 0 }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">النسبة</div>
                        <div class="h3">{{ $review->percentage ?? 0 }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('appraisals.reviews.update', $review->id) }}">
            @csrf
            @method('PUT')

            @foreach($itemsBySection as $section => $items)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ match ($section) {
                    'job_performance' => 'الأداء الوظيفي',
                    'personal_traits' => 'الصفات الشخصية',
                    'initiative' => 'المبادرة والتميز',
                    default => $section
                } }}
                            </h3>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>البند</th>
                                        <th class="text-center">الحد الأعلى</th>
                                        <th class="text-center" style="width: 180px;">درجتك</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $vi)
                                        @php
                                            $scoreRow = $scoresMap[$vi->id] ?? null;
                                            $value = $scoreRow?->score;
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $vi->resolved_label }}</td>
                                            <td class="text-center">{{ $vi->resolved_max_score }}</td>
                                            <td class="text-center">
                                                <input type="number" class="form-control text-center" name="scores[{{ $vi->id }}]"
                                                    min="0" max="{{ $vi->resolved_max_score }}"
                                                    value="{{ old('scores.' . $vi->id, $value) }}" {{ $review->status !== 'draft' ? 'disabled' : '' }} />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
            @endforeach

            <div class="d-flex gap-2">
                <button class="btn btn-primary" {{ $review->status !== 'draft' ? 'disabled' : '' }}>حفظ</button>

                <form method="POST" action="{{ route('appraisals.reviews.submit', $review->id) }}">
                    @csrf
                    <button class="btn btn-success" {{ $review->status !== 'draft' ? 'disabled' : '' }}>
                        إرسال (Submit)
                    </button>
                </form>

                <a href="{{ route('appraisals.reviews.index') }}" class="btn btn-outline-secondary">رجوع</a>
            </div>
        </form>
    </div>
@endsection