@extends('layouts.app', ['page' => 'flight-routes'])
@section('content')
<form method="POST" action="{{ route('flight-routes.update', $route) }}" class="card">
    @csrf @method('PUT')
    <div class="card-header">
        <a href="{{ route('flight-routes.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
        <h3 class="card-title">Edit route</h3>
    </div>

    <div class="card-body">
        <div class="alert alert-info">
            Changing this route only affects flights created from now on. Flights that already
            used it keep the legs they were built with, so past manifests are never rewritten.
        </div>

        @include('app.flight_routes.form-inputs')
    </div>

    <div class="card-footer text-end">
        <a href="{{ route('flight-routes.index') }}" class="btn btn-outline-secondary">Back</a>
        <button type="submit" class="btn btn-primary ms-2"><i class="ti ti-device-floppy"></i> Save</button>
    </div>
</form>
@endsection
