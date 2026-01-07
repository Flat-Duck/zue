@extends('layouts.app', ['page' => 'reports'])

@section('title', 'Reports Center')

@section('content')
    <div class="container-xl">
        <!-- Page title -->
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Reports Center
                    </h2>
                    <div class="text-muted mt-1">Generate and export system reports</div>
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
                            <h3 class="card-title">Timesheet Reports</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.timesheets') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Employee (Optional)</label>
                                    <select name="employee_id" class="form-select">
                                        <option value="">All Employees</option>
                                        @foreach($employees as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">Department</label>
                                            <select name="department_id" class="form-select">
                                                <option value="">All Departments</option>
                                                @foreach($departments as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">Center</label>
                                            <select name="center_id" class="form-select">
                                                <option value="">All Centers</option>
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
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="start_date" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="end_date" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        Generate Timesheet Report
                                    </button>
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
                            <h3 class="card-title">Balance Reports</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.balances') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Report Type</label>
                                    <select name="report_type" class="form-select" id="report_type_selector">
                                        <option value="all">All Employees Balance</option>
                                        <option value="minus">Negative (Minus) Balance Only</option>
                                        <option value="threshold">Balance Threshold (More/Less than X)</option>
                                    </select>
                                </div>

                                <div id="threshold_options" style="display: none;">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">Condition</label>
                                            <select name="threshold_type" class="form-select">
                                                <option value="more">More than</option>
                                                <option value="less">Less than</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Days</label>
                                            <input type="number" name="threshold_value" class="form-control"
                                                placeholder="X days">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Filter by Location</label>
                                    <select name="location_id" class="form-select">
                                        <option value="">All Locations</option>
                                        @foreach($locations as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Export Format</label>
                                    <div class="form-selectgroup">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="print_type" value="pdf" class="form-selectgroup-input"
                                                checked>
                                            <span class="form-selectgroup-label">PDF / Print</span>
                                        </label>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="print_type" value="excel"
                                                class="form-selectgroup-input">
                                            <span class="form-selectgroup-label">Excel</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-footer">
                                    <button type="submit" class="btn btn-success w-100">
                                        Generate Balance Report
                                    </button>
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
                            <h3 class="card-title">Time Control Sheet (Official Monthly)</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.monthly-attendance') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Select Month</label>
                                    <select name="month" class="form-select" required>
                                        @foreach(range(1, 12) as $m)
                                            <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="text-muted mb-3">
                                    <small>This generates the official monthly attendance grid with approval signatures for
                                        the current year ({{ now()->year }}).</small>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-yellow w-100">
                                        Generate Official Sheet
                                    </button>
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
                            <h3 class="card-title">Run Report (Employee Balances)</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reports.run') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Department</label>
                                    <select name="department_id" class="form-select">
                                        <option value="">All Departments</option>
                                        @foreach($departments as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">Center</label>
                                            <select name="center_id" class="form-select">
                                                <option value="">All Centers</option>
                                                @foreach($centers as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label">Location</label>
                                            <select name="location_id" class="form-select">
                                                <option value="">All Locations</option>
                                                @foreach($locations as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-azure w-100">
                                        Generate Filtered Run Report
                                    </button>
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