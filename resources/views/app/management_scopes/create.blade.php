@extends('layouts.app', ['page' => 'management_scopes'])

@section('content')
<form
    method="POST"
    action="{{ route('management-scopes.store') }}"
    class="card"
>
    @csrf
    <div class="card-header">
        <a href="{{ route('management-scopes.index') }}" class="mr-4">
            <i class="ti ti-arrow-back"></i>
        </a>
        <h3 class="card-title">
            @lang('crud.management_scopes.create_title', [], 'en')
        </h3>
    </div>

    <div class="card-body">
        <div class="col-12">
            @include('app.management_scopes.form-inputs', [
                'managementScope' => null,
            ])
        </div>
    </div>

    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('management-scopes.index') }}"
                class="btn btn-outline-secondary"
            >
                @lang('crud.common.back')
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i>
                @lang('crud.common.create')
            </button>
        </div>
    </div>
</form>
@endsection
