@extends('layouts.app', ['page' => 'roles'])

@section('content')
<form method="POST" action="{{ route('roles.store') }}" class="card">
    @csrf
    <div class="card-header">
        <a href="{{ route('roles.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.roles.create_title')</h3>
    </div>
    <div class="card-body">
        <div class="col-6">@include('app.roles.form-inputs')</div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('roles.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> @lang('crud.common.create')
            </button>
        </div>
    </div>
</form>
@endsection
