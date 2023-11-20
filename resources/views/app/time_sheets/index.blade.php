@extends('layouts.app', ['page' => 'time-sheets'])
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
                @can('create', App\Models\TimeSheet::class)
                <a
                    data-bs-original-title="إنشاء"
                    data-bs-placement="top"
                    data-bs-toggle="tooltip"
                    class="pull-right btn btn-primary"
                    href="{{ route('time-sheets.create') }}"
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
                    <th class="text-left">
                        @lang('crud.time_sheets.inputs.value')
                    </th>
                    <th class="text-left">
                        @lang('crud.time_sheets.inputs.day')
                    </th>
                    <th class="text-left">
                        @lang('crud.time_sheets.inputs.employee_id')
                    </th>
                    <th class="text-left">
                        @lang('crud.time_sheets.inputs.revised_at')
                    </th>
                    <th class="text-left">
                        @lang('crud.time_sheets.inputs.old_value')
                    </th>
                    <th class="text-left">
                        @lang('crud.time_sheets.inputs.user_id')
                    </th>
                    <th class="text-center">@lang('crud.common.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($timeSheets as $timeSheet)
                <tr>
                    <td>{{ $timeSheet->value ?? '-' }}</td>
                    <td>{{ $timeSheet->day ?? '-' }}</td>
                    <td>{{ optional($timeSheet->employee)->job ?? '-' }}</td>
                    <td>{{ $timeSheet->revised_at ?? '-' }}</td>
                    <td>{{ $timeSheet->old_value ?? '-' }}</td>
                    <td>{{ optional($timeSheet->revisedBy)->name ?? '-' }}</td>
                    <td class="text-center" style="width: 134px;">
                        <div
                            role="group"
                            aria-label="Row Actions"
                            class="btn-group"
                        >
                            @can('update', $timeSheet)
                            <a
                                href="{{ route('time-sheets.edit', $timeSheet) }}"
                                class="btn btn-icon btn-outline-warinig ms-1"
                            >
                                <i class="ti ti-edit"></i>
                            </a>
                            @endcan @can('view', $timeSheet)
                            <a
                                href="{{ route('time-sheets.show', $timeSheet) }}"
                                class="btn btn-icon btn-outline-info ms-1"
                            >
                                <i class="ti ti-eye"></i>
                            </a>
                            @endcan @can('delete', $timeSheet)
                            <form
                                action="{{ route('time-sheets.destroy', $timeSheet) }}"
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
                    <td colspan="7">@lang('crud.common.no_items_found')</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-left">
        {!! $timeSheets->render() !!}
    </div>
</div>
@endsection
