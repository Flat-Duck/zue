@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('appraisals.appraisal_forms')</h2>
                    <div class="text-muted">@lang('appraisals.multiple_forms_by_job_type')</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.forms.create') }}" class="btn btn-primary">@lang('appraisals.form')</a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>@lang('appraisals.code')</th>
                            <th>@lang('appraisals.name')</th>
                            <th>@lang('appraisals.active_2')</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($forms as $f)
                            <tr>
                                <td class="fw-bold">{{ $f->code }}</td>
                                <td>{{ $f->name_ar }}</td>
                                <td>
                                    @if ($f->is_active)
                                        <span class="badge bg-green">@lang('appraisals.yes')</span>
                                    @else
                                        <span class="badge bg-secondary">@lang('appraisals.no')</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('appraisals.forms.edit', $f->id) }}">@lang('appraisals.edit')</a>
                                    <a class="btn btn-sm btn-outline-success"
                                        href="{{ route('appraisals.versions.index', $f->id) }}">@lang('appraisals.versions')</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('appraisals.items.index') }}" class="btn btn-outline-secondary">@lang('appraisals.go_to_items')</a>
        </div>
    </div>
@endsection