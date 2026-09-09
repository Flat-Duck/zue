@extends('layouts.app', ['page' => 'employees'])

@section('content')
<form method="POST" action="{{ route('employees.store') }}">
    @csrf

    <div class="card">
        <div class="card-header">
            <a href="{{ route('employees.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
            <h3 class="card-title">@lang('crud.employees.create_title')</h3>
        </div>
    </div>

    @include('app.employees.form-inputs')

    <div class="card mt-3">
        <div class="card-footer text-end">
            <div class="d-flex">
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                    @lang('crud.common.back')
                </a>

                <button type="submit" class="btn btn-primary ms-auto">
                    <i class="ti ti-device-floppy"></i> @lang('crud.common.create')
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
