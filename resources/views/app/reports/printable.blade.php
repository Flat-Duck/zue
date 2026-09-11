@extends('layouts.app', ['page' => 'reports'])
@section('title', 'Balance Report')
@section('content')
@section('styles')
    @vite('resources/sass/print/report-printable.scss')
@endsection
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                @php
                $rt = 1;
                @endphp
                @foreach ($departments as $k => $dep)
                <div class="header">
                    <div class="row mt-2">
                        <div class="col-3">
                            <img src="/img/zue-logo.png" class="print-logo" class="mx-auto d-block">
                        </div>
                        <div class="col-6">
                            <h2 class="h2 text-center"> @lang('reports.zueitina_oil_company') </h2>
                            <h3 class="h3 text-center"> @lang('reports.accounting_department_103') </h3>
                            <h4 class="h4 text-center"> @lang('reports.detailed_field_break_balance') </h4>
                        </div>
                        <div class="col-3 d-flex align-items-end ">
                            {{-- <img src="/img/noc-logo.png" class="print-logo" class="mx-auto d-block"> --}}
                            <h4 class="h4 text-right d-flex align-items-end">
                                <span class="align-text-bottom">{{ now()->format('d/M/Y') }}</span>
                            </h4>
                        </div>
                    </div>
                    <div class="row mt-2">

                    </div>
                </div>
                <div class="table-responsive p-0 my-5">
                    <table class="table table-vcenter text-center">
                        <thead>
                            <tr>
                                <th class="p-1">#</th>
                                <th class="p-0 m-0">@lang('reports.number')</th>
                                <th class="p-0 m-0">@lang('reports.name')</th>
                                <th class="p-0 m-0">@lang('reports.start_date')</th>
                                <th class="p-0 m-0">@lang('reports.department')</th>
                                <th class="p-0 m-0">@lang('reports.cost_center')</th>
                                <th class="p-0 m-0">@lang('reports.location')</th>
                                <th class="p-0 m-0">@lang('reports.last_date')</th>
                                <th class="p-0 m-0">@lang('reports.balance')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dep as $k=> $employee)
                                <tr>
                                    <td class="p-0 m-0">{{ $k+1 }}</td>
                                    <td class="p-0 m-0">{{ $employee->number ?? '-' }}</td>
                                    <td class="p-0 m-0">{{ $employee->english_name ?? '-' }}</td>
                                    <td class="p-0 m-0">{{ $employee->start_date ?? '-' }}</td>
                                    <td class="p-0 m-0">{{ $employee->department->name ?? '-' }}</td>
                                    <td class="p-0 m-0">{{ $employee->center->name ?? '-' }}</td>
                                    <td class="p-0 m-0">{{ $employee->location->name ?? '-' }}</td>
                                    <td class="p-0 m-0">{{ $employee->last_date ?? '-' }}</td>
                                    <td class="p-0 m-0">{{ round($employee->total_balance) ?? '-' }}</td>
                                   
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2">@lang('crud.common.no_items_found')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="pagebreak"></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection