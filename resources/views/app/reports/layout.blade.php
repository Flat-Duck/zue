@extends('layouts.app', ['page' => 'reports'])
@section('title', 'Balance Report')
@section('content')
@section('styles')
    <style>
        .centered {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Container holding the image and the text */
        .container {
            position: relative;
            text-align: center;
            color: white;
            min-height: 60px;
        }

        @media print {

            html,
            body {
                height: 100%;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            img {
                /* width:100%; */
                height: 100%;
                display: block;
            }


            @page {
                size: A4 portrait;
                max-height: 100%;
                max-width: 100%;
                margin-bottom: 0px;
                margin-top: 0px;
                margin-left: 10px;
                margin-right: 10px;
            }

            .pagebreak {
                page-break-after: always;
            }
        }

        .header {
            max-height: 120px;
            margin: 0px;
            margin-top: 10px;
        }

        .box {
            border: 1px solid black;
            margin: 0px;
            margin-left: 2px;
        }

        .divider {
            margin: 0px;
        }

        h6 {
            margin: 0;
        }

        table {
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table tr:last-child {
            border-bottom: 1px solid black;
        }

        td,
        th {
            border: 1px solid black;
            border-bottom: 0px;
        }
    </style>
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
                            <img src="/img/zue-logo.png" style="height: 100px" class="mx-auto d-block">
                        </div>
                        <div class="col-6">
                            <h2 class="h2 text-center">
                                ZUEITINA OIL COMPANY
                            </h2>
                            <h3 class="h3 text-center">
                                ACCOUNTING DEPARTMENT 103
                            </h3>
                            <h4 class="h4 text-center">
                                DETAILED FIELD-BREAK BALANCE
                            </h4>
                        </div>
                        <div class="col-3 d-flex align-items-end ">
                            {{-- <img src="/img/noc-logo.png" style="height: 100px" class="mx-auto d-block"> --}}
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
                                <th class="p-0 m-0">Number</th>
                                <th class="p-0 m-0">Name</th>
                                <th class="p-0 m-0">Start Date</th>
                                <th class="p-0 m-0">Department</th>
                                <th class="p-0 m-0">Cost Center</th>
                                <th class="p-0 m-0">Location</th>
                                <th class="p-0 m-0">Last Date</th>
                                <th class="p-0 m-0">Balance</th>
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