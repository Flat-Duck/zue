@extends('layouts.app', ['page' => 'reports'])

@section('title', 'Run Report')

@section('styles')
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            .d-print-none {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .report-table th {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
        }

        .text-start {
            text-align: left !important;
        }
    </style>
@endsection

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Run Report</h2>
                </div>
                <div class="col-auto ms-auto">
                    <button onclick="window.print()" class="btn btn-primary">
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
                    <div class="row align-items-center mb-4">
                        <div class="col-3">
                            <img src="{{ asset('/img/zue-logo.png') }}" style="height: 60px;">
                        </div>
                        <div class="col-6 text-center">
                            <h2 class="mb-0">ZUEITINA OIL COMPANY</h2>
                            <h3 class="mb-0">General Balance Run Report</h3>
                            <p class="text-muted small">Generated on {{ now()->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ZOC Num</th>
                                <th class="text-start">Name</th>
                                <th>Start Date</th>
                                <th>Department</th>
                                <th>Location</th>
                                <th>Schedule</th>
                                <th>Last Date</th>
                                <th>Total Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $index => $employee)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $employee->number }}</td>
                                    <td class="text-start"><strong>{{ $employee->english_name }}</strong></td>
                                <td>{{ $employee->start_date ?? '-' }}</td>
                                    <td>{{ $employee->department->name ?? '-' }}</td>
                                    <td>{{ $employee->location->name ?? '-' }}</td>
                                    <td>{{ $employee->schedule }}</td>
                                <td>{{ $employee->last_date ?? '-' }}</td>
                                    <td class="{{ $employee->total_balance < 0 ? 'text-danger' : '' }}">
                                        <strong>{{ round($employee->total_balance, 2) }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
