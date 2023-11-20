@extends('layouts.app', ['page' => 'time-sheets'])
@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('time-sheets.index') }}" class="mr-4">
            <i class="ti ti-arrow-back"></i>
        </a>
        <h3 class="card-title">@lang('crud.time_sheets.create_title')</h3>
        @livewire('search-employee')
    </div>
    <div class="card-body">
        <div class="col-12">
            @livewire('time-sheeter')
            <br>
            @livewire ('t-s-table', ['number' => '1'])
        </div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a href="{{ route('time-sheets.index') }}" class="btn btn-outline-secondary">@lang('crud.common.back')</a>
            {{-- <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> @lang('crud.common.create')
            </button> --}}
        </div>
    </div>
</div>
@endsection
