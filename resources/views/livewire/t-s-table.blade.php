<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs nav-fill"  role="tablist">
            <li class="nav-item" >
                <button type="button" class="nav-link" wire:click="updateyear(-1)">{{$years[0]}}</button>
            </li>
            <li class="nav-item" >
                <button type="button" class="nav-link active" wire:click="updateyear(0)">{{$years[1]}}</button>
            </li>
            <li class="nav-item" >
                <button type="button" class="nav-link" wire:click="updateyear(1)">{{$years[2]}}</button>
            </li>
        </ul>
    </div>
    <div >
        <div class="table-responsive p-0">
            <table class="table table-vcenter text-center">
                <thead>
                    <tr class="bg-dark text-white">
                        <td class="p-1">Month</td>
                        @for ($i = 1; $i<32; $i++)
                            <td class="p-1">{{$i}}</td>
                        @endfor
                        {{-- <td class="p-1">W</td>
                        <td class="p-1">F</td> --}}
                        {{-- <td class="p-1">Balance</td> --}}
                    </tr>
                </thead>
                <tbody>
                    {{-- @php $tf=0;$tw=0; @endphp --}}
                    @foreach ($times as $mont => $days )
                    <tr>
                        <th class="p-1 bg-cyan text-cyan-fg">{{$mont}}</th>
                        @foreach ($days as $day)
                            @php $value = $day['value']; @endphp
                            @if ($value)
                                @if ($value == 'F' || $value == 'X')
                                    <td class="p-1 bg-red">{{ $value }}</td>
                                @elseif ($value == 'A' || $value == 'Y'|| $value == 'B')
                                    <td class="p-1 bg-azure">{{ $value }}</td>
                                @else
                                    <td class="p-1" >{{ $value }}</td>
                                @endif
                            @else
                                <td class="p-1 bg-muted" >?</td>
                            @endif
                        @endforeach
                        @for ($i = count($days); $i < 31; $i++)
                            <td class="p-1 bg-dark" ></td>
                        @endfor                        
                        {{-- <td class="p-1">@php $w = $days->where('value', 'A')->count(); $tw +=$w @endphp {{$w}}</td>
                        <td class="p-1">@php $f = $days->where('value', 'F')->count(); $tf +=$f @endphp {{$f}}</td> --}}
                        {{-- <td class="p-1">{{ $w*2 - count($days) }}</td>                         --}}
                    </tr>
                    @endforeach                        
                    {{-- <tr class="bg-muted text-white">
                        <td class="p-1" colspan="32"></td>
                        <td class="p-1"> {{$tw}} </td>
                        <td class="p-1"> {{$tf}} </td>
                        <td class="p-1">-10</td>
                    </tr> --}}
                </tbody>
            </table>
        </div>
    </div>
</div>

