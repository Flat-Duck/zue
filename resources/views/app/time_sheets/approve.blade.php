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
    @media print 
    {
        html, body{height:100%;width:100%;margin:0;padding:0;}
        
        img {
            /* width:100%; */
            height:100%;
            display:block;
        }

        
        @page 
        {
            size: A4 landscape;
            max-height:100%;
            max-width:100%;
            margin-bottom: 0px;
            margin-top: 0px;
            margin-left: 10px;
            margin-right: 10px;
        }
        .pagebreak { page-break-after: always; }
    }
    .header
    {
        max-height: 120px;
        margin: 0px;
        margin-top: 10px;
    }
    .box
    {
        border: 1px solid black;
        margin: 0px;
        margin-left: 2px;        
    }
    
    .divider{margin: 0px;}
    h6{margin: 0;}
    table { page-break-inside:auto; }
    thead { display:table-header-group; }
    tfoot { display:table-footer-group; }
    tr { page-break-inside:avoid; page-break-after:auto; }
    table tr:last-child { border-bottom: 1px solid black; }
    td, th { 
        border: 1px solid black;
        border-bottom: 0px;
    }
</style> 
@endsection
<div class="card">
    <div class="card-body">
        @php
        $rt = 1;
        @endphp
        @foreach ($chunks as  $k=> $days )
            <div class="header">
                <div class="row mt-2">
                    <div class="col-3">
                        <img src="./img/zue-logo.png" style="height: 100px" class="mx-auto d-block">
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
                        <img src="./img/noc-logo.png" style="height: 100px" class="mx-auto d-block">
                    </div>
                </div>
                <div class="row mt-2" >
                    <div class="col-1"></div>
                    <div class="col-2 box">
                        <h6 class="text-center">
                            السنة
                        </h6>
                        <hr class="divider">
                        <h6 class="text-center">
                            2023
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
                            5M49
                        </h6>
                    </div>
                    <div class="col-2 box">
                        <h6 class="text-center">
                            القسم
                        </h6>
                        <hr class="divider">
                        <h6 class="text-center">
                            معمل الغاز
                        </h6>
                    </div>
                    <div class="col-2 box">
                        <h6 class="text-center">
                            الادارة
                        </h6>
                        <hr class="divider">
                        <h6 class="text-center">
                            العمليات
                        </h6>
                    </div>            
                    <div class="col-1"></div>
                </div>
            </div>
        <div  class="table-responsive p-0 my-5" >
            <table  class="table table-vcenter text-center">
                <thead>
                    <tr>
                        <th class="p-1">#</th>
                        <th class="p-0 m-0">ZOC Number</th>
                        <th class="p-0" >Employee Name</th>
                        @for ($i = 1; $i < $month_days; $i++)
                            <th rowspan="2" class="p-1">
                                <h5 class="p-0 m-0"> {{$x = $i >= 10? $i : '0'.$i  }}</h5>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>                    
                    @foreach ($days as $e_id => $month )
                    <tr>
                        <th rowspan="2" class="p-0">{{$rt++}}</th>
                        <th rowspan="2" class="p-0 m-0">{{$e_id}}</th>
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
                    @endforeach
                    
                </tbody>
            </table>
        </div>
        <footer>
          <div class="row">

              <div class="col-1"></div>
              <div class="col-3 box text-center "><h6>
                  منسق الحقل
                </h6><hr class="divider">
                <h6 class="text-center">
                    Salah Ahjel
                </h6></div>
                <div class="col-4 box text-center"><h6 >
                    مشرف القسم	
                </h6><hr class="divider">
                <h6 class="container">
                    <img src="./img/sig.png" style="height: 70px" class="mx-auto d-block centered">                    
                </h6>
                Omar Aggar
            </div>
                <div class="col-3 box text-center"><h6 >
                    حافظ الوقت	
                </h6><hr class="divider">
                <h6 >
                    Omar Aggar
                </h6></div>
                <div class="col-1"></div>
            </div>
            
        </footer> 
        <div class="pagebreak"></div>
        @endforeach
    </div>
</div>

@endsection