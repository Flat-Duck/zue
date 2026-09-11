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
                <div class="col-lg-6">
                    @livewire('dashboard-chart')
                </div>
            </div>
        </div>
    </div>
@endsection