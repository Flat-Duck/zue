@extends('layouts.app', ['page' => 'flight-stations'])
@section('content')
<form method="POST" action="{{ route('flight-stations.update', $station) }}" class="card">
    @csrf @method('PUT')
    <div class="card-header">
        <a href="{{ route('flight-stations.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
        <h3 class="card-title">@lang('flights.edit_station')</h3>
    </div>
    <div class="card-body">
        @include('app.flight_stations.form-inputs')
    </div>
    <div class="card-footer text-end">
        <a href="{{ route('flight-stations.index') }}" class="btn btn-outline-secondary">@lang('flights.back')</a>
        <button type="submit" class="btn btn-primary ms-2"><i class="ti ti-device-floppy"></i> @lang('flights.save')</button>
    </div>
</form>
@endsection
