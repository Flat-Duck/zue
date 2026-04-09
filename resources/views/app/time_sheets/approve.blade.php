@extends('layouts.app', ['page' => 'employees'])
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
                    size: A4 landscape;
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

            .name {
                font-size: small;
                padding: 0px;
                margin: 0px;
            }

            .skyblue {
                background-color: #87ceeb !important;
            }

            .grassgreen {
                background-color: #7cb378 !important;
            }

            .expnded {
                /* font-size: x-large !important; */
                font-size: larger !important;
                color: black !important;


            }

            /* table {
                                                                                border-collapse: collapse;
                                                                                width: 100%;
                                                                            } */

            tbody tr:nth-child(even) {
                border-bottom: 4px double #000 !important;
                border-left: 4px double #000 !important;
                border-right: 4px double #000 !important;

            }

            tbody tr:nth-child(odd) {
                border-top: 4px double #000 !important;
                border-left: 4px double #000 !important;
                border-right: 4px double #000 !important;
            }

            tbody tr th:nth-child(1, 2, 3) {
                border-top: 4px double #000 !important;
                border-left: 4px double #000 !important;
                border-right: 4px double #000 !important;
            }

            tbody th:nth-child(-n+3) {
                border-top: 4px double #000 !important;
                border-left: 4px double #000 !important;
                border-right: 4px double #000 !important;
            }

            tbody td:last-child() {
                border-top: 4px double #000 !important;
                border-left: 4px double #000 !important;
                border-right: 4px double #000 !important;
            }

            /* tbody th, tbody td {
                                                                                border: 1px solid #ccc;
                                                                                padding: 4px;
                                                                            } */
        </style>
    @endsection
    <div class="card">
        <div class="card-body">
            @php
                $rt = 1;
            @endphp
            @foreach ($chunks as $k => $days)
                <div class="header">
                    <div class="row mt-2">
                        <div class="col-3">
                            <img src="{{ asset('/img/zue-logo.png') }}" style="height: 100px" class="mx-auto d-block">
                        </div>
                        <div class="col-6">
                            <h2 class="h2 text-center">
                                حقول الانتصار 103
                            </h2>
                            <h3 class="h3 text-center">
                                بطاقة ضبط الوقت
                            </h3>
                        </div>
                        <div class="col-3">
                            <img src="{{ asset('/img/noc-logo.png') }}" style="height: 100px" class="mx-auto d-block">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-1"></div>
                        <div class="col-2 box">
                            <h6 class="text-center">
                                السنة
                            </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $selected_year }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center">
                                الشهر
                            </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $month_name }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center">
                                مركز التكلفة
                            </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $center ?? '' }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center">
                                القسم
                            </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $department ?? '' }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center">
                                الادارة
                            </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $administration ?? '' }}
                            </h6>
                        </div>
                        <div class="col-1"></div>
                    </div>
                </div>
                <div class="table-responsive p-0 mt-4 mb-1" style="margin-top: 50px !important;">
                    <table class="table table-vcenter text-center">
                        <thead>
                            <tr>
                                <th class="p-1">#</th>
                                <th class="p-0 m-0">Z-N</th>
                                <th class="p-0 ">Employee Name</th>
                                @for ($i = 1; $i < $month_days; $i++)
                                    <th rowspan="2" class="p-1">
                                        <h5 class="p-0 m-0"> {{ $x = $i >= 10 ? $i : '0' . $i }}</h5>
                                    </th>
                                @endfor
                                <th class="p-0">OT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($days as $e_id => $month)
                                <div>
                                    <tr>
                                        <th rowspan="2" class="p-0">{{ $rt++ }}</th>
                                        <th rowspan="2" class="p-0 m-0">{{ $e_id }}</th>
                                        <th rowspan="2" class="p-0 name m-0">{{ $employees[$e_id] }}</th>
                                        @php
                                            $totalOT = 0;
                                            $daysCount = count($month);
                                            $columnsCount = $month_days - 1;
                                        @endphp
                                        @foreach ($month as $day)
                                            @php
                                                $totalOT += $day->ot_value;
                                            @endphp
                                            @if ($day->value)
                                                <th class="p-0 {{ $day->css_class }} expnded">{{ $day->value }}</th>
                                            @else
                                                <th class="p-0 bg-muted expnded">?</th>
                                            @endif
                                        @endforeach
                                        @for ($i = $daysCount; $i < $columnsCount; $i++)
                                            <th class="p-0 bg-muted expnded">?</th>
                                        @endfor
                                        <th rowspan="2" class="p-1 expnded">{{ $totalOT }}</th>
                                    </tr>
                                    <tr>
                                        @foreach ($month as $day)
                                            @php
                                                $bg = '';
                                                if ($day->ot_value > 0) {
                                                    $bg = 'skyblue';
                                                }
                                            @endphp
                                            <td class="p-0 {{ $bg }} expnded">{{ $day->ot_value == 0 ? '*' : $day->ot_value }}</td>
                                        @endforeach

                                        {{-- Fill remaining columns --}}
                                        @for ($i = $daysCount; $i < $columnsCount; $i++)
                                            <td class="p-0 bg-muted expnded">*</td>
                                        @endfor
                                    </tr>

                                </div>
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <footer>
                    @php
                        $timekeeperSigned = !empty($signatures['time_keeper']['sign'] ?? null);
                        $supervisorSigned = !empty($signatures['super_visor']['sign'] ?? null);
                        $fieldCoordinatorSigned = !empty($signatures['field_coordinator']['sign'] ?? null);
                        $superintendentSigned = !empty($signatures['super_intendent']['sign'] ?? null);

                        $showSupervisorStage = $requiresSupervisorStage && ($timekeeperSigned || $supervisorSigned || $canSupervisorApprove);
                        $showFieldCoordinatorStage = $requiresFieldCoordinatorStage && ($timekeeperSigned || $supervisorSigned || $fieldCoordinatorSigned || $canFieldCoordinatorApprove);
                        $showSuperintendentStage = $requiresSuperintendentStage && ($timekeeperSigned || $superintendentSigned || $canSuperintendentApprove);
                    @endphp

                    <div class="row gx-2">
                        <div class="col box text-center">
                            <h6>حافظ الوقت</h6>
                            <hr class="divider">
                            @if($timekeeperSigned)
                                <div>
                                    <h6 class="container">
                                        <img src="{{ asset('storage/' . $signatures['time_keeper']['sign']) }}"
                                            style="height: 70px; margin-top: 12px;" class="mx-auto d-block centered">
                                    </h6>
                                    {{ $signatures['time_keeper']['name'] }}
                                </div>
                            @elseif(auth()->user()->hasRole('timekeeper') && $canTimekeeperApprove)
                                <a data-bs-original-title="إعتماد" data-bs-placement="top" data-bs-toggle="tooltip"
                                    class="pull-right btn btn-yellow"
                                    href="{{ route('time-sheets.approves', ['level' => 'timekeeper', 'month' => $selected_month, 'year' => $selected_year]) }}">
                                    <i class="ti ti-check"></i>
                                    @lang('crud.common.time_sheet_approve')
                                </a>
                            @endif
                        </div>

                        @if($showSupervisorStage)
                            <div class="col box text-center">
                                <h6>مشرف القسم</h6>
                                <hr class="divider">
                                @if($supervisorSigned)
                                    <div>
                                        <h6 class="container">
                                            <img src="{{ asset('storage/' . $signatures['super_visor']['sign']) }}"
                                                style="height: 70px; margin-top: 12px;" class="mx-auto d-block centered">
                                        </h6>
                                        {{ $signatures['super_visor']['name'] }}
                                    </div>
                                @elseif(auth()->user()->hasRole('supervisor') && $canSupervisorApprove)
                                    <a data-bs-original-title="إعتماد" data-bs-placement="top" data-bs-toggle="tooltip"
                                        class="pull-right btn btn-yellow"
                                        href="{{ route('time-sheets.approves', ['level' => 'supervisor', 'month' => $selected_month, 'year' => $selected_year]) }}">
                                        <i class="ti ti-check"></i>
                                        @lang('crud.common.time_sheet_approve')
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if($showFieldCoordinatorStage)
                            <div class="col box text-center">
                                <h6>منسق الحقول</h6>
                                <hr class="divider">
                                @if($fieldCoordinatorSigned)
                                    <div>
                                        <h6 class="container">
                                            <img src="{{ asset('storage/' . $signatures['field_coordinator']['sign']) }}"
                                                style="height: 70px; margin-top: 12px;" class="mx-auto d-block centered">
                                        </h6>
                                        {{ $signatures['field_coordinator']['name'] }}
                                    </div>
                                @elseif(auth()->user()->hasRole('fieldcoordinator') && $canFieldCoordinatorApprove)
                                    <a data-bs-original-title="إعتماد" data-bs-placement="top" data-bs-toggle="tooltip"
                                        class="pull-right btn btn-yellow"
                                        href="{{ route('time-sheets.approves', ['level' => 'fieldcoordinator', 'month' => $selected_month, 'year' => $selected_year]) }}">
                                        <i class="ti ti-check"></i>
                                        @lang('crud.common.time_sheet_approve')
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if($showSuperintendentStage)
                            <div class="col box text-center">
                                <h6>مراقب الحقول</h6>
                                <hr class="divider">
                                @if($superintendentSigned)
                                    <div>
                                        <h6 class="container">
                                            <img src="{{ asset('storage/' . $signatures['super_intendent']['sign']) }}"
                                                style="height: 70px; margin-top: 12px;" class="mx-auto d-block centered">
                                        </h6>
                                        {{ $signatures['super_intendent']['name'] }}
                                    </div>
                                @elseif(auth()->user()->hasRole('superintendent') && $canSuperintendentApprove)
                                    <a data-bs-original-title="إعتماد" data-bs-placement="top" data-bs-toggle="tooltip"
                                        class="pull-right btn btn-yellow"
                                        href="{{ route('time-sheets.approves', ['level' => 'superintendent', 'month' => $selected_month, 'year' => $selected_year]) }}">
                                        <i class="ti ti-check"></i>
                                        @lang('crud.common.time_sheet_approve')
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </footer>
                <div class="pagebreak"></div>
            @endforeach
        </div>
    </div>

@endsection
