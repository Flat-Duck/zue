@extends('layouts.app', ['page' => 'flight-stations'])
@section('content')
<form method="POST" action="{{ route('flight-stations.store') }}" class="card">
    @csrf
    <div class="card-header">
        <a href="{{ route('flight-stations.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
        <h3 class="card-title">@lang('flights.new_station')</h3>
    </div>
    <div class="card-body">
        @include('app.flight_stations.form-inputs')
    </div>
    <div class="card-footer text-end">
        <a href="{{ route('flight-stations.index') }}" class="btn btn-outline-secondary">@lang('flights.back')</a>
        <button type="submit" class="btn btn-primary ms-2"><i class="ti ti-device-floppy"></i> @lang('flights.create')</button>
    </div>
</form>
@endsection
