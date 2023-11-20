@extends('layouts.app', ['page' => 'employees'])
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
                    href="{{ route('employees.create') }}"
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
                        @lang('crud.employees.inputs.id_card')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.id_card_issue_date')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.passport')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.passport_issue_date')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.address')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.phone')
                    </th>
                    <th class="text-left">
                        @lang('crud.employees.inputs.email')
                    </th>
                    <th class="text-left">
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
                    </th>
                    <th class="text-right">
                        @lang('crud.employees.inputs.transfered_balance')
                    </th>
                    <th class="text-center">@lang('crud.common.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->number ?? '-' }}</td>
                    <td>{{ $employee->job ?? '-' }}</td>
                    <td>{{ $employee->english_name ?? '-' }}</td>
                    <td>{{ $employee->id_card ?? '-' }}</td>
                    <td>{{ $employee->id_card_issue_date ?? '-' }}</td>
                    <td>{{ $employee->passport ?? '-' }}</td>
                    <td>{{ $employee->passport_issue_date ?? '-' }}</td>
                    <td>{{ $employee->address ?? '-' }}</td>
                    <td>{{ $employee->phone ?? '-' }}</td>
                    <td>{{ $employee->email ?? '-' }}</td>
                    <td>{{ optional($employee->user)->name ?? '-' }}</td>
                    <td>{{ optional($employee->location)->name ?? '-' }}</td>
                    <td>{{ optional($employee->department)->name ?? '-' }}</td>
                    <td>{{ optional($employee->center)->name ?? '-' }}</td>
                    <td>{{ $employee->transfered_balance ?? '-' }}</td>
                    <td class="text-center" style="width: 134px;">
                        <div
                            role="group"
                            aria-label="Row Actions"
                            class="btn-group"
                        >
                            @can('update', $employee)
                            <a
                                href="{{ route('employees.edit', $employee) }}"
                                class="btn btn-icon btn-outline-warinig ms-1"
                            >
                                <i class="ti ti-edit"></i>
                            </a>
                            @endcan @can('view', $employee)
                            <a
                                href="{{ route('employees.show', $employee) }}"
                                class="btn btn-icon btn-outline-info ms-1"
                            >
                                <i class="ti ti-eye"></i>
                            </a>
                            @endcan @can('delete', $employee)
                            <form
                                action="{{ route('employees.destroy', $employee) }}"
                                method="POST"
                                class="inline pointer ms-1"
                                onsubmit="return confirm('{{ __('crud.common.are_you_sure') }}')"
                            >
                                @csrf @method('DELETE')
                                <button
                                    type="submit"
                                    class="btn btn-icon btn-outline-danger"
                                >
                                    <i class="ti ti-trash-x"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="16">@lang('crud.common.no_items_found')</td>
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
