@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">إنشاء تقييم</h2>
            <div class="text-muted">اختر الفترة والموظف</div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.reviews.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الفترة (Open فقط)</label>
                            <select name="appraisal_period_id" class="form-select" required>
                                <option value="">-- اختر --</option>
                                @foreach($periods as $p)
                                    <option value="{{ $p->id }}">{{ $p->label }}
                                        ({{ $p->window_open_from->format('m/d') }}→{{ $p->window_open_to->format('m/d') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">الموظف</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">-- اختر --</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->name ?? ('#' . $e->id) }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">لازم الموظف يكون مربوط بـ appraisal_form_id</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary">إنشاء</button>
                        <a href="{{ route('appraisals.reviews.index') }}" class="btn btn-outline-secondary">رجوع</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection