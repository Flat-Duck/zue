@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">@lang('appraisals.create_appraisal')</h2>
            <div class="text-muted">@lang('appraisals.choose_period_and_employee')</div>
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
                            <label class="form-label">@lang('appraisals.period_open_only')</label>
                            <select name="appraisal_period_id" class="form-select" required>
                                <option value="">@lang('appraisals.choose_placeholder')</option>
                                @foreach($periods as $p)
                                    <option value="{{ $p->id }}">{{ $p->label }}
                                        ({{ $p->window_open_from->format('m/d') }}→{{ $p->window_open_to->format('m/d') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">@lang('appraisals.employee')</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">@lang('appraisals.choose_placeholder')</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->name ?? ('#' . $e->id) }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">@lang('appraisals.employee_needs_a_form')</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary">@lang('appraisals.create')</button>
                        <a href="{{ route('appraisals.reviews.index') }}" class="btn btn-outline-secondary">@lang('appraisals.back')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection