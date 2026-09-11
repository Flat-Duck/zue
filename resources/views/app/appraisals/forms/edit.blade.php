@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">@lang('appraisals.edit_form')</h2>
            <div class="text-muted">{{ $form->code }}</div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.forms.update', $form->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">@lang('appraisals.code')</label>
                            <input name="code" class="form-control" value="{{ old('code', $form->code) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">@lang('appraisals.name')</label>
                            <input name="name_ar" class="form-control" value="{{ old('name_ar', $form->name_ar) }}"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $form->is_active) ? 'checked' : '' }}>
                                <span class="form-check-label">@lang('appraisals.active_2')</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">@lang('appraisals.update')</button>
                        <a href="{{ route('appraisals.versions.index', $form->id) }}" class="btn btn-outline-success">@lang('appraisals.manage_versions')</a>
                        <a href="{{ route('appraisals.forms.index') }}" class="btn btn-outline-secondary">@lang('appraisals.back')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection