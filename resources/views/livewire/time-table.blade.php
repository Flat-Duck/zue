@props([
    /*
     * Flatpickr's options, rendered into the Alpine `x-init` below.
     *
     * The upper bound used to be the literal `2026-10-10`, written into an inline
     * script that re-initialised this same input after Alpine had already set it up.
     * A hard-coded ceiling silently stops the picker accepting any date once it
     * passes, so the bound is worked out from today instead.
     */
    'options' => "{dateFormat:'Y-m-d', altFormat:'F j, Y', altInput:true, inline:true, mode:'range', enable:[{from:'2024-01-01', to:'"
        .now()->addMonth()->toDateString()."'}]}",
])
<div>
    <div class="row g-3">
        <div class="col-1 p-0 overflow-auto" style="max-height: 20rem">
            @foreach ($dep_employees as $id => $number)
                {{-- <div class="list-group list-group-flush list-group-hoverable"> --}}
                    {{-- <div class="list-group-item m-0 ps-1 p-0"> --}}
                        <div class="row align-items-center m-1">
                            <div class="col-auto m-0 p-0">
                                <span class="avatar avatar-xs rounded me-2">JL</span>
                            </div>
                            <div class="col-auto text-truncate ps-0">
                                <a href="{{ route('time-sheets.fill', $id) }}" class="text-reset d-block">{{$number}}</a>
                            </div>
                        </div>
                        {{--
                    </div> --}}
                    {{-- </div> --}}


            @endforeach
        </div>

        <div class="col-5 mb-3">
            <div class="row row-cards">
                <div class="col-2 mt-3">
                    <div class="mb-3">
                        <label class="form-label">ZOC No :</label>
                        <input value="{{ $employee->number}}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-6 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Full Name :</label>
                        <input value="{{ $employee->english_name}}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Start Date :</label>
                        <input value="{{ $employee->start_date}}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Administration :</label>
                        <input value="{{ $employee->administration_name }}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Department :</label>
                        <input value="{{ $employee->department_name }}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Cost Center :</label>
                        <input value="{{ $employee->center_name }}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Location :</label>
                        <input value="{{ $employee->location_name }}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Schedule :</label>
                        <input value="{{ $employee->schedule }}" type="text" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">Total Balance : </label>
                        <input value="{{ $employee->balance }}" type="text"
                            class="form-control {{ ($employee->balance < 0) ? 'bg-red-lt' : 'bg-green-lt' }} " disabled>
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">To Date :</label>
                        <input value="{{ $employee->last_date }}" type="text" class="form-control" disabled>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-5 mb-3">
            <div class="row g-5">
                <div class="col-7">
                    <div class="flatpickr" wire:ignore>
                        <input id="time" wire:model.live="range" x-data
                            x-init="flatpickr($refs.input, {{ $options }} );" x-ref="input" type="hidden" data-input
                            style="display: none;" />
                    </div>
                </div>
                <div class="col-5">
                    <div class="form-selectgroup">
                        @foreach(App\Helpers\Rules::getLetters(auth()->user()) as $V)
                            <label class="form-selectgroup-item" style="max-height: 40px; max-width: 40px;">
                                <input type="radio" wire:confirm="ok?" wire:click="save" wire:model.live="val"
                                    value="{{$V}}" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">{{$V}}</span>
                            </label>
                        @endforeach
                        @if($revise)
                            <div class="col-6 col-sm-4 col-md-2 col-xl-auto">
                                <a class="btn btn-pinterest w-100" wire:confirm="ok?" wire:click="destroy">
                                    Delete <i class="ti ti-trash-x"> </i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-1">
            <label for="ov">Over time</label>
            <input type="text" value="2" wire:model="ov" class="form-control">
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs nav-fill" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link" wire:click="updateyear(-1)">{{$years[0]}}</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link active" wire:click="updateyear(0)">{{$years[1]}}</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" wire:click="updateyear(1)">{{$years[2]}}</button>
                </li>
            </ul>
        </div>
        <div>
            <div class="table-responsive p-0">
                <table class="table table-vcenter text-center">
                    <thead>
                        <tr class="bg-dark text-white">
                            <td class="p-1">Month</td>
                            @for ($i = 1; $i < 32; $i++)
                                <td class="p-1">{{$i}}</td>
                            @endfor
                            {{-- <td class="p-1">W</td>
                            <td class="p-1">F</td> --}}
                            {{-- <td class="p-1">Balance</td> --}}
                        </tr>
                    </thead>
                    <tbody>
                        {{-- @php $tf=0;$tw=0; @endphp --}}
                        @php $months = \App\Helpers\MomentsJs::getMonthsInYear()->toArray(); @endphp
                        @foreach ($times as $mont => $days)
                            <tr>
                                <th class="p-1 bg-cyan text-cyan-fg">{{$months[$mont]}}</th>
                                @foreach ($days as $day)
                                    @php $value = $day['value']; @endphp
                                    @if ($value)
                                        @if ($value == 'F' || $value == 'X')
                                            <td class="p-1 bg-red">{{ $value }}</td>
                                        @elseif ($value == 'A' || $value == 'Y' || $value == 'B')
                                            <td class="p-1 bg-azure">{{ $value }}</td>
                                        @else
                                            <td class="p-1">{{ $value }}</td>
                                        @endif
                                    @else
                                        <td class="p-1 bg-muted">?</td>
                                    @endif
                                @endforeach
                                @for ($i = count($days); $i < 31; $i++)
                                    <td class="p-1 bg-dark"></td>
                                @endfor
                                {{-- <td class="p-1">{{ $w*2 - count($days) }}</td> --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
