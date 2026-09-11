@extends('layouts.app', ['page' => 'reports'])

@section('title', 'Reports Center')

@section('content')
    <div class="container-xl">
        <!-- Page title -->
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"> @lang('reports.reports_center') </h2>
                    <div class="text-muted mt-1">@lang('reports.generate_and_export_system_reports')</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">

                <!-- Timesheet Reports -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-status-top bg-blue"></div>
                        <div class="card-header">
                            <h3 class="card-title">@lang('reports.timesheet_reports')</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.timesheets') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">@lang('reports.employee_optional')</label>
                                    <select name="employee_id" class="form-select">
                                        <option value="">@lang('reports.all_employees')</option>
                                        @foreach($employees as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.department')</label>
                                            <select name="department_id" class="form-select">
                                                <option value="">@lang('reports.all_departments')</option>
                                                @foreach($departments as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.center')</label>
                                            <select name="center_id" class="form-select">
                                                <option value="">@lang('reports.all_centers')</option>
                                                @foreach($centers as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.start_date')</label>
                                            <input type="date" name="start_date" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.end_date')</label>
                                            <input type="date" name="end_date" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary w-100"> @lang('reports.generate_timesheet_report') </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Balance Reports -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-status-top bg-green"></div>
                        <div class="card-header">
                            <h3 class="card-title">@lang('reports.balance_reports')</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.balances') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">@lang('reports.report_type')</label>
                                    <select name="report_type" class="form-select" id="report_type_selector">
                                        <option value="all">@lang('reports.all_employees_balance')</option>
                                        <option value="minus">@lang('reports.negative_minus_balance_only')</option>
                                        <option value="threshold">@lang('reports.balance_threshold_more_less_than_x')</option>
                                    </select>
                                </div>

                                <div id="threshold_options" class="d-none">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">@lang('reports.condition')</label>
                                            <select name="threshold_type" class="form-select">
                                                <option value="more">@lang('reports.more_than')</option>
                                                <option value="less">@lang('reports.less_than')</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">@lang('reports.days')</label>
                                            <input type="number" name="threshold_value" class="form-control"
                                                placeholder="@lang('reports.x_days')">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">@lang('reports.filter_by_location')</label>
                                    <select name="location_id" class="form-select">
                                        <option value="">@lang('reports.all_locations')</option>
                                        @foreach($locations as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">@lang('reports.export_format')</label>
                                    <div class="form-selectgroup">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="print_type" value="pdf" class="form-selectgroup-input"
                                                checked>
                                            <span class="form-selectgroup-label">@lang('reports.pdf_print')</span>
                                        </label>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="print_type" value="excel"
                                                class="form-selectgroup-input">
                                            <span class="form-selectgroup-label">@lang('reports.excel')</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-footer">
                                    <button type="submit" class="btn btn-success w-100"> @lang('reports.generate_balance_report') </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Time Control Sheet (Monthly) -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-status-top bg-yellow"></div>
                        <div class="card-header">
                            <h3 class="card-title">@lang('reports.time_control_sheet_official_monthly')</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.monthly-attendance') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.select_month')</label>
                                            <select name="month" class="form-select" required>
                                                @foreach(range(1, 12) as $m)
                                                    <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.select_year')</label>
                                            <select name="year" class="form-select" required>
                                                @php
                                                    $currentYear = now()->year;
                                                @endphp
                                                @for($y = $currentYear + 1; $y >= $currentYear - 5; $y--)
                                                    <option value="{{ $y }}" {{ $y === $currentYear ? 'selected' : '' }}>
                                                        {{ $y }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-muted mb-3">
                                    <small>@lang('reports.this_generates_the_official_monthly')</small>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-yellow w-100"> @lang('reports.generate_official_sheet') </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Run Report -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-status-top bg-azure"></div>
                        <div class="card-header">
                            <h3 class="card-title">@lang('reports.run_report_employee_balances')</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.run') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">@lang('reports.department')</label>
                                    <select name="department_id" class="form-select">
                                        <option value="">@lang('reports.all_departments')</option>
                                        @foreach($departments as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.center')</label>
                                            <select name="center_id" class="form-select">
                                                <option value="">@lang('reports.all_centers')</option>
                                                @foreach($centers as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('reports.location')</label>
                                            <select name="location_id" class="form-select">
                                                <option value="">@lang('reports.all_locations')</option>
                                                @foreach($locations as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-azure w-100"> @lang('reports.generate_filtered_run_report') </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('report_type_selector').addEventListener('change', function () {
                var thresholdOptions = document.getElementById('threshold_options');
                if (this.value === 'threshold') {
                    thresholdOptions.style.display = 'block';
                } else {
                    thresholdOptions.style.display = 'none';
                }
            });
        </script>
    @endpush
@endsection
