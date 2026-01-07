@extends('layouts.app', ['page' => 'reports'])

@section('title', 'Official Monthly Time Sheet')

@section('styles')
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            .d-print-none {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .page-break {
                page-break-after: always;
            }
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            text-align: center;
            padding: 2px;
        }

        .header-box {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }

        .skyblue {
            background-color: #d1ecf1 !important;
        }

        .grassgreen {
            background-color: #d4edda !important;
        }

        .signature-box {
            border: 1px solid #000;
            height: 100px;
            position: relative;
            text-align: center;
            padding-top: 5px;
        }

        .signature-img {
            max-height: 60px;
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
        }
    </style>
@endsection

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Monthly Time Control Sheet</h2>
                </div>
                <div class="col-auto ms-auto">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="ti ti-printer me-2"></i> Print Official Sheet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @php $rt = 1; @endphp
            @foreach ($chunks as $chunk)
                <div class="card mb-4 page-break">
                    <div class="card-body">
                        <!-- Arabic Title Header -->
                        <div class="row mb-3 align-items-center">
                            <div class="col-3">
                                <img src="{{ asset('/img/zue-logo.png') }}" style="height: 70px;">
                            </div>
                            <div class="col-6 text-center">
                                <h2 class="mb-0">حقول الانتصار 103</h2>
                                <h3 class="mb-0">بطاقة ضبط الوقت</h3>
                                <div class="mt-2">
                                    <strong>Month:</strong> {{ $month_name }} | <strong>Year:</strong> {{ now()->year }}
                                    @if(isset($center_name)) | <strong>Center:</strong> {{ $center_name }} @endif
                                </div>
                            </div>
                            <div class="col-3 text-end">
                                <img src="{{ asset('/img/noc-logo.png') }}" style="height: 70px;">
                            </div>
                        </div>

                        <!-- Attendance Grid -->
                        <div class="table-responsive">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2">#</th>
                                        <th rowspan="2">Z-N</th>
                                        <th rowspan="2" style="width: 150px;">Employee Name</th>
                                        @for ($i = 1; $i <= $month_days; $i++)
                                            <th class="p-0">{{ $i }}</th>
                                        @endfor
                                        <th rowspan="2">OT</th>
                                    </tr>
                                    <tr>
                                        @for ($i = 1; $i <= $month_days; $i++)
                                            <th class="p-0"><small>OT</small></th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($chunk as $e_id => $month)
                                        @php $totalOT = 0; @endphp
                                        <tr>
                                            <td rowspan="2">{{ $rt++ }}</td>
                                            <td rowspan="2">{{ $e_id }}</td>
                                            <td rowspan="2" class="text-start ps-2">
                                                <strong>{{ $employees[$e_id] ?? 'N/A' }}</strong></td>
                                            @foreach ($month as $day)
                                                @php $totalOT += $day->ot_value; @endphp
                                                <td class="{{ $day->css_class }}"><strong>{{ $day->value ?? '?' }}</strong></td>
                                            @endforeach
                                            {{-- Pad remaining days if any --}}
                                            @for($i = count($month); $i < $month_days; $i++)
                                                <td>-</td>
                                            @endfor
                                            <td rowspan="2"><strong>{{ $totalOT }}</strong></td>
                                        </tr>
                                        <tr>
                                            @foreach ($month as $day)
                                                <td class="{{ $day->ot_value > 0 ? 'skyblue' : '' }}">
                                                    <small>{{ $day->ot_value > 0 ? $day->ot_value : '*' }}</small></td>
                                            @endforeach
                                            @for($i = count($month); $i < $month_days; $i++)
                                                <td>*</td>
                                            @endfor
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Approval Footer -->
                        <div class="row mt-4">
                            <div class="col-4">
                                <div class="signature-box">
                                    <strong>حافظ الوقت / Timekeeper</strong>
                                    <hr class="my-1">
                                    @isset($signatures['time_keeper']['sign'])
                                        <img src="{{ asset('storage/' . $signatures['time_keeper']['sign']) }}"
                                            class="signature-img">
                                        <div class="small mt-4">{{ $signatures['time_keeper']['name'] }}</div>
                                    @else
                                        <div class="mt-4 text-muted small">Pending Approval</div>
                                    @endisset
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="signature-box">
                                    <strong>مشرف القسم / Supervisor</strong>
                                    <hr class="my-1">
                                    @isset($signatures['super_visor']['sign'])
                                        <img src="{{ asset('storage/' . $signatures['super_visor']['sign']) }}"
                                            class="signature-img">
                                        <div class="small mt-4">{{ $signatures['super_visor']['name'] }}</div>
                                    @else
                                        <div class="mt-4 text-muted small">Pending Approval</div>
                                    @endisset
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="signature-box">
                                    <strong>منسق الحقل / Superintendent</strong>
                                    <hr class="my-1">
                                    @isset($signatures['super_intendent']['sign'])
                                        <img src="{{ asset('storage/' . $signatures['super_intendent']['sign']) }}"
                                            class="signature-img">
                                        <div class="small mt-4">{{ $signatures['super_intendent']['name'] }}</div>
                                    @else
                                        <div class="mt-4 text-muted small">Pending Approval</div>
                                    @endisset
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection