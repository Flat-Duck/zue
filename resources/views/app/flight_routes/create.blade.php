@extends('layouts.app', ['page' => 'flight-routes'])
@section('content')
<form method="POST" action="{{ route('flight-routes.store') }}" class="card">
    @csrf
    <div class="card-header">
        <a href="{{ route('flight-routes.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
        <h3 class="card-title">New route</h3>
    </div>
    <div class="card-body">
        @include('app.flight_routes.form-inputs')
    </div>
    <div class="card-footer text-end">
        <a href="{{ route('flight-routes.index') }}" class="btn btn-outline-secondary">Back</a>
        <button type="submit" class="btn btn-primary ms-2"><i class="ti ti-device-floppy"></i> Create</button>
    </div>
</form>
@endsection
