@extends('layouts.app', ['page' => 'flights'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('flights.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.flights.show_title')</h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.flights.inputs.type')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $flight->type ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.flights.inputs.date')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $flight->date ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.flights.inputs.time')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $flight->time ?? '-' }}"
                                disabled=""
                            />
                        </div>
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
            <a href="{{ route('flights.create') }}" class="btn btn-primary">
                @lang('crud.common.create')
            </a>
            @endcan
        </div>
    </div>
</div>

@can('view-any', App\Models\flight_passenger::class)
<div class="card mt-4">
    <div class="card-body">
        <h4 class="card-title w-100 mb-2">Passengers</h4>

        <livewire:flight-passengers-detail :flight="$flight" />
    </div>
</div>
@endcan @can('view-any', App\Models\employee_flight::class)
<div class="card mt-4">
    <div class="card-body">
        <h4 class="card-title w-100 mb-2">Employees</h4>

        <livewire:flight-employees-detail :flight="$flight" />
    </div>
</div>
@endcan @endsection
