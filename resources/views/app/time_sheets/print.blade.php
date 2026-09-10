@extends('layouts.app', ['page' => 'employees'])
@section('content')
@section('styles')
    @vite('resources/sass/print/timesheet-print.scss')
@endsection
<div class="card">
    <div class="card-body border-bottom" id="section-to-print">
        <div id="pageHead"  style="position: fixed;">
            <table border="1" class="table table-vcenter text-center" >
                <tbody>
                    <tr style="height: 55px;">
                        <td style="width: 20%; height: 43px;"><img alt="" /></td>
                        <td style="width: 20%; height: 43px;" colspan="3">
                            <p style="text-align: center;">حقول الانتصار 103&nbsp;</p>
                            <p style="text-align: center;">بطاقة ضبط اوقت</p>
                        </td>
                        <td style="width: 20%; height: 43px; text-align: center;"><img alt="" /></td>
                    </tr>
                    <tr style="height: 30px; text-align: center;">
                        <td class="signature-cell">السنة</td>
                        <td class="signature-cell">الشهر</td>
                        <td class="signature-cell">مركز التكلفة</td>
                        <td class="signature-cell">القسم</td>
                        <td class="signature-cell">الادارة&nbsp;</td>
                    </tr>
                    <tr style="height: 30px; text-align: center;">
                        <td class="signature-cell"></td>
                        <td class="signature-cell"></td>
                        <td class="signature-cell"></td>
                        <td class="signature-cell"></td>
                        <td class="signature-cell"></td>
                    </tr>
                </tbody>
            </table>
            </div>
            <div  class="table-responsive p-0" >
            <table  class="table table-vcenter text-center " border="1">
                <thead>
                    <tr id="mainTable">
                        <th class="p-1">#</th>
                        <th class="p-1">ZOC Number</th>
                        <th class="p-0" >Employee Name</th>                    
                        @for ($i = 1; $i<29; $i++)
                            <th rowspan="2"  class="p-1">
                                {{$x = $i >= 10? $i : '0'.$i  }}</th>
                        @endfor                    
                    </tr>
                </thead>
                <tbody>
                    @php $days = \App\Models\TimeSheet::whereMonth('day',2)->whereYear('day','2023')->limit(2800)->get() @endphp
                    @php $days = $days->groupBy('employee_id') ;
                    $x = 1;
                    @endphp
                    @foreach ($days as $e_id => $month )
                        <tr>
                            <th rowspan="2" class="p-0">{{$x++}}</th>
                            <th rowspan="2" class="p-0">{{$e_id}}</th>
                            <th rowspan="2" class="p-0">abdulrahaman ali mahidwei</th>
                            @foreach ($month as $day)
                                @php $value = $day->value; @endphp
                                @if ($value)
                                    @if ($value == 'F' || $value == 'X')
                                        <td class="p-0">{{ $value }}</td>
                                    @elseif ($value == 'A' || $value == 'Y'|| $value == 'B')
                                        <td class="p-0">{{ $value }}</td>
                                    @else
                                        <td class="p-0" >{{ $value }}</td>
                                    @endif
                                @else
                                    <td class="p-0 bg-muted" >?</td>
                                @endif
                            @endforeach
                            
                        </tr>
                        <tr>
                            @foreach ($month as $day)
                                <td class="p-0 bg-muted" >{{$day->over_time}}</td>
                            @endforeach
                        </tr>
                        @if (($x-1) % 10 == 0) 
                            <tr class="pagebreak"></tr>
                        @endif
                    @endforeach

                    
                </tbody>
            </table>
        </div>
        <div class="print-corner-note">THIS IS MY FOOTER</div>

    </div>
</div>
@endsection
    