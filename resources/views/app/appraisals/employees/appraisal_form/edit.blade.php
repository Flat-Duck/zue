@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('appraisals.assign_form_to_employee')</h2>
                    <div class="text-muted">{{ $employee->name ?? ('#' . $employee->id) }}</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.reviews.create') }}" class="btn btn-outline-primary"> @lang('appraisals.create_appraisal_for') </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.department')</div>
                        <div class="fw-bold">{{ $employee->department->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.administration')</div>
                        <div class="fw-bold">{{ $employee->administration->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.location')</div>
                        <div class="fw-bold">{{ $employee->location->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.cost_center')</div>
                        <div class="fw-bold">{{ $employee->costCenter->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.employees.appraisal-form.update', $employee->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">@lang('appraisals.appraisal_form')</label>
                            <select name="appraisal_form_id" class="form-select">
                                <option value="">@lang('appraisals.none_unassigned')</option>
                                @foreach($forms as $f)
                                    <option value="{{ $f->id }}" {{ (string) $employee->appraisal_form_id === (string) $f->id ? 'selected' : '' }}>
                                        {{ $f->name_ar }} ({{ $f->code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint"> @lang('appraisals.form_choice_hint') </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">@lang('appraisals.current_status')</label>
                            @if($employee->appraisal_form_id)
                                <div class="p-2 rounded bg-green-lt"> @lang('appraisals.linked_to_form') <span
                                        class="fw-bold">{{ optional($forms->firstWhere('id', $employee->appraisal_form_id))->code }}</span>
                                </div>
                            @else
                                <div class="p-2 rounded bg-yellow-lt"> @lang('appraisals.not_linked_to_any_form') </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">@lang('appraisals.save')</button>
                        <a href="{{ route('appraisals.reviews.index') }}" class="btn btn-outline-secondary">@lang('appraisals.back')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection