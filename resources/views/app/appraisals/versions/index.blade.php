@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('appraisals.versions')</h2>
                    <div class="text-muted">{{ $form->name_ar }} — {{ $form->code }}</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.forms.edit', $form->id) }}" class="btn btn-outline-primary">@lang('appraisals.edit_form')</a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">@lang('appraisals.create_new_version')</h3>
            </div>
            <div class="card-body">
                <form class="row g-3" method="POST" action="{{ route('appraisals.versions.store', $form->id) }}">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label">@lang('appraisals.version')</label>
                        <input type="number" name="version" class="form-control" value="{{ old('version', $nextVersion) }}"
                            min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('appraisals.effective_from')</label>
                        <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('appraisals.effective_to')</label>
                        <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                            <span class="form-check-label">@lang('appraisals.active_2')</span>
                        </label>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">@lang('appraisals.create')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>@lang('appraisals.version')</th>
                            <th>@lang('appraisals.effective')</th>
                            <th>@lang('appraisals.active_2')</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($versions as $v)
                            <tr>
                                <td class="fw-bold">v{{ $v->version }}</td>
                                <td>{{ optional($v->effective_from)->format('Y-m-d') ?? '-' }} →
                                    {{ optional($v->effective_to)->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    @if ($v->is_active)
                                        <span class="badge bg-green">@lang('appraisals.yes')</span>
                                    @else
                                        <span class="badge bg-secondary">@lang('appraisals.no')</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-success"
                                        href="{{ route('appraisals.version-items.edit', $v->id) }}">@lang('appraisals.items')</a>
                                    @if(!$v->is_active)
                                        <form method="POST" action="{{ route('appraisals.versions.activate', $v->id) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary">@lang('appraisals.activate')</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('appraisals.forms.index') }}" class="btn btn-outline-secondary">@lang('appraisals.back')</a>
        </div>
    </div>
@endsection