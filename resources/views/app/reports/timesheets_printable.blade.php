@extends('layouts.app', ['page' => 'reports'])

@section('title', 'Timesheet Report')

@section('styles')
    <style>
        @media print {
            .btn-print {
                display: none;
            }

            .card {
                border: none !important;
            }

            .card-header {
                display: none !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Timesheet Detailed Report</h2>
                </div>
                <div class="col-auto ms-auto">
                    <button onclick="window.print()" class="btn btn-primary btn-print">
                        <i class="ti ti-printer me-2"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <div class="header mb-4">
                        <div class="row align-items-center">
                            <div class="col-3">
                                <img src="/img/zue-logo.png" style="height: 80px" class="d-block">
                            </div>
                            <div class="col-6 text-center">
                                <h2 class="mb-1">ZUEITINA OIL COMPANY</h2>
                                <h3 class="mb-1">Timesheet Records Report</h3>
                                <p class="text-muted">Generated on {{ now()->format('Y-m-d H:i') }}</p>
                            </div>
                            <div class="col-3 text-end">
                                <strong>Status: Detailed</strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter table-bordered text-nowrap">
                            <thead>
                                <tr class="bg-light">
                                    <th>Date</th>
                                    <th>Emp #</th>
                                    <th>Name</th>
                                    <th>Job</th>
                                    <th>Value</th>
                                    <th>OT</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timesheets as $ts)
                                    <tr>
                                        <td>{{ $ts->day->format('Y-m-d') }}</td>
                                        <td>{{ $ts->employee->number }}</td>
                                        <td>{{ $ts->employee->english_name }}</td>
                                        <td><small class="text-muted">{{ $ts->employee->job }}</small></td>
                                        <td class="text-center"><strong>{{ $ts->value }}</strong></td>
                                        <td class="text-center">{{ $ts->over_time ?? 0 }}</td>
                                        <td>{{ $ts->employee->location->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No records found for the selected criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection