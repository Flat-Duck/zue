@extends('layouts.app', ['page' => 'time-sheets'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('time-sheets.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.time_sheets.show_title')</h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.time_sheets.inputs.value')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $timeSheet->value ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.time_sheets.inputs.day')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $timeSheet->day ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.time_sheets.inputs.employee_id')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($timeSheet->employee)->job ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.time_sheets.inputs.revised_at')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $timeSheet->revised_at ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.time_sheets.inputs.old_value')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $timeSheet->old_value ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.time_sheets.inputs.user_id')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($timeSheet->revisedBy)->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('time-sheets.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >

            @can('create', App\Models\TimeSheet::class)
            <a href="{{ route('time-sheets.create') }}" class="btn btn-primary">
                @lang('crud.common.create')
            </a>
            @endcan
        </div>
    </div>
</div>

@endsection
