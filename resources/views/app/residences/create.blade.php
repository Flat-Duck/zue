@extends('layouts.app', ['page' => 'residences'])

@section('content')
<form method="POST" action="{{ route('residences.store') }}" class="card">
    @csrf
    <div class="card-header">
        <a href="{{ route('residences.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.residences.create_title')</h3>
    </div>
    <div class="card-body">
        <div class="col-6">@include('app.residences.form-inputs')</div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('residences.index') }}"
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
