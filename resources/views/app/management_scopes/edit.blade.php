@extends('layouts.app', ['page' => 'management_scopes'])

@section('content')
<form
    method="POST"
    action="{{ route('management-scopes.update', $managementScope) }}"
    class="card"
>
    @csrf
    @method('PUT')

    <div class="card-header">
        <a href="{{ route('management-scopes.index') }}" class="mr-4">
            <i class="ti ti-arrow-back"></i>
        </a>
        <h3 class="card-title">
            @lang('crud.management_scopes.edit_title', [], 'en')
        </h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-6">
                <div class="row">
                    <div class="col-md-12 col-xl-12">
                        @include('app.management_scopes.form-inputs')
                    </div>
                </div>
            </div>
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
                @lang('crud.common.update')
            </button>
        </div>
    </div>
</form>
@endsection
