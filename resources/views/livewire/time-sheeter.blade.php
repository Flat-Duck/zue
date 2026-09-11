@props([
    /* See the note in time-table.blade.php: this range used to expire. */
    'options' => "{dateFormat:'Y-m-d', altFormat:'F j, Y', altInput:true, inline:true, mode:'range'}",
])
<div>
    <div class="row g-5">
        <div class="col-6 mb-3">
            <div class="row row-cards">
                <div class="col-2 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.zoc_no')</label>
                        <input value="{{ $employee->number}}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-6 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.full_name')</label>
                        <input value="{{ $employee->english_name}}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.start_date')</label>
                        <input value="{{ $employee->id_card_issue_date}}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.administration')</label>
                        <input value="{{ $employee->administration_name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.department')</label>
                        <input value="{{ $employee->department_name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.cost_center')</label>
                        <input value="{{ $employee->center_name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-3 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.location')</label>
                        <input value="{{ $employee->location_name }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.schedule')</label>
                        <input value="{{ $employee->schedule }}" type="text" class="form-control" disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.total_balance') </label>
                        <input value="{{ $employee->balance }}" type="text" class="form-control {{ ($employee->balance > 0)? 'bg-red-lt' : 'bg-green-lt' }} " disabled >
                    </div>
                </div>
                <div class="col-4 mt-3">
                    <div class="mb-3">
                        <label class="form-label">@lang('timesheets.to_date')</label>
                        <input value="{{ $employee->last_date }}" type="text" class="form-control" disabled >
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="row g-5">
                <div class="col-7">
                    <div class="flatpickr" wire:ignore>
                        <input id="time" wire:model.live="range" x-data x-init="flatpickr($refs.input, {{ $options }} );" x-ref="input" type="hidden" data-input class="d-none" />
                    </div>
                </div>
                <div class="col-5">
                    <label class="form-label">Selectgroup with icons and text {{$val}}</label>
                    <div class="form-selectgroup">
                        @foreach(range('A','Z') as $V) 
                            <label class="form-selectgroup-item">
                                <input type="radio" wire:confirm="ok?" wire:click="save" wire:model.live="val" value="{{$V}}" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">{{$V}}</span>
                            </label>
                        @endforeach
                        <label class="form-selectgroup-item">
                            <input type="radio" wire:confirm="ok?" wire:click="destroy" class="form-selectgroup-input">
                            <span class="form-selectgroup-label">@lang('timesheets.delete')</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

