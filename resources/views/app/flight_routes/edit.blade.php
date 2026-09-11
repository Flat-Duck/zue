@extends('layouts.app', ['page' => 'flight-routes'])
@section('content')
<form method="POST" action="{{ route('flight-routes.update', $route) }}" class="card">
    @csrf @method('PUT')
    <div class="card-header">
        <a href="{{ route('flight-routes.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
        <h3 class="card-title">@lang('flights.edit_route')</h3>
    </div>

    <div class="card-body">
        <div class="alert alert-info"> @lang('flights.changing_this_route_only_affects_flights') </div>

        @include('app.flight_routes.form-inputs')
    </div>

    <div class="card-footer text-end">
        <a href="{{ route('flight-routes.index') }}" class="btn btn-outline-secondary">@lang('flights.back')</a>
        <button type="submit" class="btn btn-primary ms-2"><i class="ti ti-device-floppy"></i> @lang('flights.save')</button>
    </div>
</form>
@endsection
