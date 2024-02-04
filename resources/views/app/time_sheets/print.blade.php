

@media print {
    .pagebreak { page-break-before: always; } page-break-after works, as well
    /* #headTable {margin-bottom:200px } */
    #pageHead {margin-bottom:200px;
        position: fixed;
        top:0;
        left: 0;
        A_CSS_ATTRIBUTE:all;
        width: 100%;
     }
    table {page-break-inside: auto}
    tr {page-break-inside:avoid; page-break-after: auto;}
    thead {display: table-header-group}
    tfoot {display: table-footer-group}
    
}

/* -------------------- */
td,th { 
  border: 1px solid black;
  border-bottom: 0px;
}
table tr:last-child {
  border-bottom: 1px solid black;
} 
/* -------------------- */
</style>
@endsection
<div class="card">
    <div class="card-body border-bottom" id="section-to-print">
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
        <div style="A_CSS_ATTRIBUTE:all;position: fixed;bottom: 10px; left: 10px; ">THIS IS MY FOOTER</div>

    </div>
</div>
@endsection
    