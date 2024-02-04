@extends('layouts.app', ['page' => 'time-sheets'])
@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection
@section('content')
<div class="card">
    <div class="card-header p-2">        
        <div class="col-auto">
            @livewire('search-employee')
        </div>
    </div>
    <div class="card-body">
        <div class="col-12">
            {{-- @livewire('time-sheeter') --}}
            @livewire('time-table',['employee' => $employee, 'revise' => false])
            {{-- @livewire ('t-s-table') --}}
        </div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a href="{{ route('time-sheets.index') }}" class="btn btn-outline-secondary">@lang('crud.common.back')</a>            
         
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-check"></i> @lang('crud.time_sheets.time_keeper_approve')
            </button>
        </div>
    </div>
</div>
@endsection