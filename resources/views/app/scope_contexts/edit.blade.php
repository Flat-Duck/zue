@extends('layouts.app', ['page' => 'scope_contexts'])

@section('content')
<form method="POST" action="{{ route('scope-contexts.update', $context) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-header">
        <a href="{{ route('scope-contexts.index') }}" class="mr-4"><i class="ti ti-arrow-back"></i></a>
        <h3 class="card-title">@lang('scopes.contexts_edit')</h3>
    </div>
    <div class="card-body">
        @include('app.scope_contexts.form-inputs')
    </div>
    <div class="card-footer text-end">
        <div class="d-flex justify-content-between">
            <a href="{{ route('scope-contexts.index') }}" class="btn btn-outline-secondary">@lang('crud.common.back')</a>
            <button type="submit" class="btn btn-primary" dusk="save">
                <i class="ti ti-device-floppy"></i>
                @lang('crud.common.update')
            </button>
        </div>
    </div>
</form>
@endsection
