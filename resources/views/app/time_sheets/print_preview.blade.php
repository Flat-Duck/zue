@extends('layouts.app', ['page' => 'time-sheets'])
@section('content')
    <form action="{{ route('time-sheets.print') }}" method="post" class="card">
        @csrf
        <div class="card-header p-2">
            <div class="col-auto">
                {{-- @livewire('search-employee') --}}
            </div>
        </div>
        <div class="card-body">
            <div class="col-12">

                <div class="row">
                    <x-inputs.group class="col-sm-4">
                        <x-inputs.select name="selected_month" label="Select Month">
                            <option value="1 ">@lang('timesheets.january')</option>
                            <option value="2 ">@lang('timesheets.february')</option>
                            <option value="3 ">@lang('timesheets.march')</option>
                            <option value="4 ">@lang('timesheets.april')</option>
                            <option value="5 ">@lang('timesheets.may')</option>
                            <option value="6 ">@lang('timesheets.june')</option>
                            <option value="7 ">@lang('timesheets.july')</option>
                            <option value="8 ">@lang('timesheets.august')</option>
                            <option value="9 ">@lang('timesheets.september')</option>
                            <option value="10">@lang('timesheets.october')</option>
                            <option value="11">@lang('timesheets.november')</option>
                            <option value="12">@lang('timesheets.december')</option>
                        </x-inputs.select>
                    </x-inputs.group>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col">
                        <a href="{{ route('time-sheets.index') }}" class="btn btn-outline-secondary">@lang('crud.common.back')</a>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-printer"></i>
                    @lang('crud.common.print_preview')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endsection
