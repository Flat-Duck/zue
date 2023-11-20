@props([
    'value'
])
@if ($value)
    @if ($value == 'F' || $value == 'X')
        <td class="p-0 bg-red">{{$value}}</td>
    @elseif ($value == 'A' || $value == 'Y'|| $value == 'B')
        <td class="p-0 bg-azure">{{$value}}</td>
    @elseif ($value == '9')
        <td class="p-0 bg-dark" ></td>
    @else
        <td class="p-0" >{{$value}}</td>
    @endif
@else
    <td class="p-0 bg-muted" >?</td>
@endif