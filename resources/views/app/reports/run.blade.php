@extends('layouts.app', ['page' => 'reports'])

@section('title', 'Run Report')

@section('styles')
    @vite('resources/sass/print/report-run.scss')
@endsection

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('reports.run_report')</h2>
                </div>
                <div class="col-auto ms-auto">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="ti ti-printer me-2"></i> @lang('reports.print_report') </button>
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
                            <h2 class="mb-0">@lang('reports.zueitina_oil_company')</h2>
                            <h3 class="mb-0">@lang('reports.general_balance_run_report')</h3>
                            <p class="text-muted small">@lang('ui.generated_on', ['time' => now()->format('Y-m-d H:i')])</p>
                        </div>
                    </div>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('reports.zoc_num')</th>
                                <th class="text-start">@lang('reports.name')</th>
                                <th>@lang('reports.start_date')</th>
                                <th>@lang('reports.department')</th>
                                <th>@lang('reports.location')</th>
                                <th>@lang('reports.schedule')</th>
                                <th>@lang('reports.last_date')</th>
                                <th>@lang('reports.total_balance')</th>
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
