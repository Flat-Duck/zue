@extends('layouts.app', ['page' => 'employees'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('employees.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
        <h3 class="card-title">@lang('crud.employees.show_title')</h3>

        <div class="col-auto ms-auto d-print-none">
            @can('update', $employee)
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                    <i class="ti ti-edit"></i> @lang('crud.common.edit')
                </a>
            @endcan
        </div>
    </div>
</div>

{{-- Every field, read only, from the same definition the form uses. --}}
@include('app.employees._profile-fields', ['employee' => $employee, 'readonly' => true])

<div class="card mt-3">
    <div class="card-footer text-end">
        <div class="d-flex">
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                @lang('crud.common.back')
            </a>

            @can('create', App\Models\Employee::class)
                <a href="{{ route('employees.create') }}" class="btn btn-primary ms-2">
                    @lang('crud.common.create')
                </a>
            @endcan
        </div>
    </div>
</div>

@endsection
