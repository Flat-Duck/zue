@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">@lang('appraisals.create_form')</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.forms.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">@lang('appraisals.code')</label>
                            <input name="code" class="form-control" value="{{ old('code') }}" placeholder="@lang('appraisals.form_3_admin_fin')"
                                required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">@lang('appraisals.name_arabic')</label>
                            <input name="name_ar" class="form-control" value="{{ old('name_ar') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                                <span class="form-check-label">@lang('appraisals.active_2')</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">@lang('appraisals.save')</button>
                        <a href="{{ route('appraisals.forms.index') }}" class="btn btn-outline-secondary">@lang('appraisals.back')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection