@extends('layouts.app')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Administrative Operations
                    </h2>
                    <div class="text-muted mt-1">Execute bulk maintenance tasks and system operations</div>
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
                            <h3 class="card-title">Archive Employees (By Last Timesheet)</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                This operation will archive all employees whose <strong>latest</strong> timesheet record is
                                older than or equal to the selected date.
                            </p>
                            <form action="{{ route('operations.archive-by-timesheet') }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to archive these employees? This action will set their archived_at date to now.');">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Threshold Date</label>
                                    <input type="date" name="date" class="form-control" required
                                        value="{{ date('Y-06-30') }}">
                                    <small class="form-hint">
                                        Employees with no timesheets after this date will be moved to the archive.
                                    </small>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="ti ti-archive me-2"></i> Archive Employees
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4">

                            <p class="text-muted mb-2">
                                Or archive by entering one or more employee numbers.
                            </p>
                            <form id="archiveByNumberForm" action="{{ route('operations.archive-by-number') }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to archive these employees by number?');">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Employee Numbers</label>
                                    <textarea name="employee_numbers" class="form-control" rows="3"
                                        placeholder="Enter numbers separated by space, comma or newline..."
                                        required></textarea>
                                </div>
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-outline-warning w-100">
                                        <i class="ti ti-archive me-2"></i> Archive By Numbers
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Un-archive Employees by Number -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Un-archive Employees (By Number)</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Enter one or more employee numbers to restore them from the archive.
                            </p>
                            <form id="unarchiveByNumberForm" action="{{ route('operations.unarchive-by-number') }}"
                                method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Employee Numbers</label>
                                    <textarea name="employee_numbers" class="form-control" rows="3"
                                        placeholder="Enter numbers separated by space, comma or newline..."
                                        required></textarea>
                                </div>
                            </form>
                            <div class="form-footer d-flex gap-2">
                                <button type="submit" form="unarchiveByNumberForm" class="btn btn-primary w-100">
                                    <i class="ti ti-rotate-2 me-2"></i> Un-archive Employees
                                </button>
                                <form action="{{ route('operations.unarchive-all') }}" method="POST" class="w-100"
                                    onsubmit="return confirm('Are you sure you want to un-archive all employees?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="ti ti-rotate-clockwise-2 me-2"></i> Un-archive All
                                    </button>
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
                            <h3 class="card-title">Archived Employees ({{ $archivedEmployees->total() }})</h3>
                        </div>
                        <div class="card-table table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th>Name</th>
                                        <th>Job</th>
                                        <th>Archived At</th>
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
                                                    <button type="submit" class="btn btn-sm btn-ghost-primary">
                                                        Un-archive
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No archived employees found.
                                            </td>
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
