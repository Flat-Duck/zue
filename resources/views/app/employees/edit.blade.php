@extends('layouts.app', ['page' => 'employees'])

@section('content')
{{-- The form is not itself a card: the field sections render their own, the
     same way the show page does. --}}
<form method="POST" action="{{ route('employees.update', $employee) }}">
    @csrf @method('PUT')

    <div class="card">
        <div class="card-header">
            <a href="{{ route('employees.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
            <h3 class="card-title">@lang('crud.employees.edit_title')</h3>

            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-eye"></i> @lang('ui.view') </a>
            </div>
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
                    <i class="ti ti-device-floppy"></i> @lang('crud.common.update')
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
