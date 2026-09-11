@extends('layouts.app')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title"> @lang('operations.administrative_operations') </h2>
                    <div class="text-muted mt-1">@lang('operations.execute_bulk_maintenance_tasks_and_system')</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">

                <!-- Archive Employees by Timesheet -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">@lang('operations.archive_employees_by_last_timesheet')</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted"> @lang('operations.this_operation_will_archive_all_employees') <strong>@lang('operations.latest')</strong> @lang('operations.timesheet_record_is_older_than_or_equal_to') </p>
                            <form action="{{ route('operations.archive-by-timesheet') }}" method="POST"
                                data-confirm="{{ __('operations.confirm_archive_by_date') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">@lang('operations.threshold_date')</label>
                                    <input type="date" name="date" class="form-control" required
                                        value="{{ date('Y-06-30') }}">
                                    <small class="form-hint"> @lang('operations.employees_with_no_timesheets_after_this_date') </small>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="ti ti-archive me-2"></i> @lang('operations.archive_employees') </button>
                                </div>
                            </form>

                            <hr class="my-4">

                            <p class="text-muted mb-2"> @lang('operations.or_archive_by_entering_one_or_more_employee') </p>
                            <form id="archiveByNumberForm" action="{{ route('operations.archive-by-number') }}" method="POST"
                                data-confirm="{{ __('operations.confirm_archive_by_number') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">@lang('operations.employee_numbers')</label>
                                    <textarea name="employee_numbers" class="form-control" rows="3"
                                        placeholder="@lang('operations.enter_numbers_separated_by_space_comma_or')"
                                        required></textarea>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-outline-warning w-100">
                                        <i class="ti ti-archive me-2"></i> @lang('operations.archive_by_numbers') </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Un-archive Employees by Number -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">@lang('operations.un_archive_employees_by_number')</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted"> @lang('operations.enter_one_or_more_employee_numbers_to') </p>
                            <form id="unarchiveByNumberForm" action="{{ route('operations.unarchive-by-number') }}"
                                method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">@lang('operations.employee_numbers')</label>
                                    <textarea name="employee_numbers" class="form-control" rows="3"
                                        placeholder="@lang('operations.enter_numbers_separated_by_space_comma_or')"
                                        required></textarea>
                                </div>
                            </form>
                            <div class="form-footer d-flex gap-2">
                                <button type="submit" form="unarchiveByNumberForm" class="btn btn-primary w-100">
                                    <i class="ti ti-rotate-2 me-2"></i> @lang('operations.un_archive_employees') </button>
                                <form action="{{ route('operations.unarchive-all') }}" method="POST" class="w-100"
                                    data-confirm="{{ __('operations.confirm_unarchive_all') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="ti ti-rotate-clockwise-2 me-2"></i> @lang('operations.un_archive_all') </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-4">
                <!-- Archived Employees List -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">@lang('operations.archived_employees_count', ['count' => $archivedEmployees->total()])</h3>
                        </div>
                        <div class="card-table table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>@lang('operations.number')</th>
                                        <th>@lang('operations.name')</th>
                                        <th>@lang('operations.job')</th>
                                        <th>@lang('operations.archived_at')</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($archivedEmployees as $employee)
                                        <tr>
                                            <td>{{ $employee->number }}</td>
                                            <td>{{ $employee->english_name }}</td>
                                            <td class="text-muted">{{ $employee->job }}</td>
                                            <td>{{ $employee->archived_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <form action="{{ route('operations.unarchive-by-number') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="employee_numbers"
                                                        value="{{ $employee->number }}">
                                                    <button type="submit" class="btn btn-sm btn-ghost-primary"> @lang('operations.un_archive') </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">@lang('operations.no_archived_employees_found') </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($archivedEmployees->hasPages())
                            <div class="card-footer d-flex align-items-center">
                                {{ $archivedEmployees->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
