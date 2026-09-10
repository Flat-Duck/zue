@extends('layouts.app', ['page' => 'clinic'])
@section('content')
<div class="card">
    <div class="card-body border-bottom py-3">
        <div class="d-flex">
            <form>
                <div class="row g-2">
                    <div class="input-icon col">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input
                            id="indexSearch"
                            name="search"
                            type="text"
                            value=""
                            class="form-control"
                            placeholder="Search…"
                            aria-label="Search..."
                            spellcheck="false"
                            data-ms-editor="true"
                            autocomplete="off"
                        />
                    </div>
                    <div class="col-auto">
                        <button
                            class="btn btn-icon btn-primary"
                            aria-label="Button"
                        >
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </div>
            </form>
            <div class="col-auto ms-auto d-print-none">
                @can('create', App\Models\Employee::class)
                <a
                    data-bs-original-title="إنشاء"
                    data-bs-placement="top"
                    data-bs-toggle="tooltip"
                    class="pull-right btn btn-primary"
                    href="{{ route('clinic.create') }}"
                >
                    <i class="ti ti-plus"></i>
                    @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap datatable">
            <thead>
                <tr>
                    <th class="text-right">
                        @lang('crud.employees.inputs.number')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.job')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.english_name')
                    </th>                    
                    <th class="text-left">
                        @lang('crud.employees.inputs.phone')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.email')
                    </th>
                    {{-- <th class="text-left">
                        @lang('crud.employees.inputs.user_id')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.location_id')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.department_id')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.center_id')
                    </th> --}}
                    {{-- <th class="text-right">
                        @lang('crud.employees.inputs.transfered_balance')
                    </th> --}}
                    <th class="text-left">
                        @lang('crud.employees.inputs.schedule')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.start_date')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.last_date')
                    </th>
                    <th class="text-right">
                        @lang('crud.employees.inputs.balance')
                    </th>
                    {{-- <th class="text-left">
                        @lang('crud.employees.inputs.archived_at')
                    </th> --}}
                    <th class="text-center">@lang('crud.common.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->number ?? '-' }}</td>
                    <td>{{ $employee->job ?? '-' }}</td>
                    <td>{{ $employee->english_name ?? '-' }}</td>
                    <td>{{ $employee->phone ?? '-' }}</td>
                    <td>{{ $employee->email ?? '-' }}</td>
                    {{-- <td>{{ optional($employee->user)->name ?? '-' }}</td> --}}
                    {{-- <td>{{ optional($employee->location)->name ?? '-' }}</td> --}}
                    {{-- <td>{{ optional($employee->department)->name ?? '-' }}</td> --}}
                    {{-- <td>{{ optional($employee->center)->name ?? '-' }}</td> --}}
                    {{-- <td>{{ $employee->transfered_balance ?? '-' }}</td> --}}
                    <td>{{ $employee->schedule ?? '-' }}</td>
                    <td>{{ $employee->start_date ?? '-' }}</td>
                    <td>{{ $employee->last_date ?? '-' }}</td>
                    <td>{{ $employee->total_balance ?? '-' }}</td>
                    {{-- <td>{{ $employee->archived_at ?? '-' }}</td> --}}
                    <td class="text-center table-actions">
                        <div
                            role="group"
                            aria-label="Row Actions"
                            class="btn-group"
                        >
                            @can('view', $employee)
                            <a
                                href="{{ route('clinic.history', $employee) }}"
                                class="btn btn-icon btn-outline-info ms-1">
                            <i class="ti ti-file-time"></i>
                            </a>
                            @endcan
                            <a href="{{ route('clinic.diagnosis', $employee) }}"
                                    data-bs-original-title="New Diagnosis"
                                    data-bs-placement="top"
                                    data-bs-toggle="tooltip"
                                    class="btn btn-icon btn-outline-success ms-1" >
                                <i class="ti ti-heart-rate-monitor"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="21">@lang('crud.common.no_items_found')</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-left">
        {!! $employees->render() !!}
    </div>
</div>
@endsection