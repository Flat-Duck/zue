@extends('layouts.app', ['page' => 'employees'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('employees.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.employees.show_title')</h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.number')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->number ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.job')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->job ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.english_name')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->english_name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.id_card')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->id_card ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.id_card_issue_date')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->id_card_issue_date ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.passport')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->passport ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.passport_issue_date')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->passport_issue_date ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.address')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->address ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.phone')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->phone ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.email')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->email ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.user_id')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($employee->user)->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.location_id')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($employee->location)->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.department_id')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($employee->department)->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.center_id')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($employee->center)->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.transfered_balance')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->transfered_balance ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.schedule')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->schedule ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.start_date')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->start_date ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.last_date')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->last_date ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.total_balance')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->total_balance ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.employees.inputs.archived_at')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $employee->archived_at ?? '-' }}"
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
                href="{{ route('employees.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >

            @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                @lang('crud.common.create')
            </a>
            @endcan
        </div>
    </div>
</div>

@endsection
