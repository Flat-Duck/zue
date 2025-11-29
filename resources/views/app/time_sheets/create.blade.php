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
                @livewire('time-table', ['employee' => $employee, 'revise' => false])
                {{-- @livewire ('t-s-table') --}}
            </div>
        </div>
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col">
                    <a href="{{ route('time-sheets.index') }}" class="btn btn-outline-secondary">@lang('crud.common.back')</a>
                </div>
                <div class="col-auto">
                    @livewire('time-sheet-approve', ['employee_id' => $employee->id])
                </div>
            </div>
        </div>
    </div>
@endsection
