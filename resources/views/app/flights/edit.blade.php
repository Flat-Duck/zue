@extends('layouts.app', ['page' => 'flights'])

@section('content')
<form
    method="POST"
    action="{{ route('flights.update', $flight) }}"
    class="card"
>
    @csrf @method('PUT')
    <div class="card-header">
        <a href="{{ route('flights.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.flights.edit_title')</h3>
    </div>
    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        @include('app.flights.form-inputs')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('flights.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >
            @can('create', App\Models\Flight::class)
            <a href="{{ route('flights.create') }}" class="btn btn-link">
                @lang('crud.common.create')
            </a>
            @endcan
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> @lang('crud.common.update')
            </button>
        </div>
    </div>
</form>

@can('view-any', App\Models\flight_passenger::class)
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Passengers</h3>
    </div>
    <div class="card-body">
        <livewire:flight-passengers-detail :flight="$flight" />
    </div>
</div>
@endcan @can('view-any', App\Models\employee_flight::class)
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Employees</h3>
    </div>
    <div class="card-body">
        <livewire:flight-employees-detail :flight="$flight" />
    </div>
</div>
@endcan @endsection
