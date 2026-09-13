@extends('layouts.app', ['page' => 'dashboard'])

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title"> @lang('ui.dashboard') </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <!-- Users Stats -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">@lang('ui.total_users')</div>
                            </div>
                            <div class="h1 mb-3">{{ $usersCount }}</div>
                            <div class="d-flex mb-2">
                                <div>@lang('ui.active_system_users')</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Employees Stats -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">@lang('ui.total_employees')</div>
                            </div>
                            <div class="h1 mb-3">{{ $employeesCount }}</div>
                            <div class="d-flex mb-2">
                                <div>@lang('ui.registered_employees')</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Timesheets Filled -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">@lang('ui.timesheets_this_month')</div>
                            </div>
                            <div class="d-flex align-items-baseline">
                                <div class="h1 mb-3 me-2">{{ $employeesWithTimesheets }}</div>
                            </div>
                            <div class="d-flex mb-2">
                                <span class="text-success me-2 fw-bold">
                                    {{ $employeesCount > 0 ? round(($employeesWithTimesheets / $employeesCount) * 100) : 0 }}%
                                </span>
                                <div>@lang('ui.completion_rate')</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Timesheets Filled -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">

                            <div class="d-flex align-items-center">
                                <div class="subheader">@lang('ui.pending_timesheets')</div>
                            </div>
                            <div class="d-flex align-items-baseline">
                                <div class="h1 mb-3 me-2">{{ $employeesMissingTimesheets }}</div>
                            </div>
                            <div class="d-flex mb-2">
                                <span class="text-warning me-2 fw-bold">
                                    {{ $employeesCount > 0 ? round(($employeesMissingTimesheets / $employeesCount) * 100) : 0 }}%
                                </span>
                                <div>@lang('ui.missing_records')</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader">@lang('ui.departments_complete')</div>
                            <div class="h1 mb-3">{{ $departmentCompletionRate }}%</div>
                            <div class="text-muted">@lang('ui.departments_with_no_missing_timesheets')</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    @livewire('dashboard-chart')
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">@lang('ui.department_timesheet_status')</h3>
                            <div class="card-actions">
                                <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2" autocomplete="off">
                                    <label class="form-label mb-0" for="dashboard_month">@lang('ui.month')</label>
                                    <select id="dashboard_month" name="month" class="form-select" autocomplete="off" onchange="this.form.requestSubmit()">
                                        @foreach ($availableMonths as $monthNumber => $monthName)
                                            <option value="{{ $monthNumber }}" @selected($selectedMonth === $monthNumber)>
                                                {{ $monthName }} {{ $dashboardYear }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-outline-primary">@lang('ui.apply')</button>
                                </form>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter table-striped card-table">
                                <thead>
                                    <tr>
                                        <th>@lang('ui.department')</th>
                                        <th class="text-end">@lang('ui.filled')</th>
                                        <th class="text-end">@lang('ui.not_filled')</th>
                                        <th class="text-end">@lang('ui.total')</th>
                                        <th class="text-end">@lang('ui.completion_rate')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($departmentTimesheetSummaries as $summary)
                                        <tr class="{{ $loop->odd ? 'table-light' : '' }}">
                                            <td>{{ $summary['department'] }}</td>
                                            <td class="text-end text-success fw-bold">{{ $summary['filled'] }}</td>
                                            <td class="text-end {{ $summary['not_filled'] > 0 ? 'text-warning fw-bold' : 'text-success' }}">{{ $summary['not_filled'] }}</td>
                                            <td class="text-end">{{ $summary['total'] }}</td>
                                            <td class="text-end">{{ $summary['completion_rate'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">@lang('ui.no_department_timesheet_data')</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection