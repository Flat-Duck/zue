@extends('layouts.app', ['page' => 'administrations'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('administrations.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.administrations.show_title')</h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.administrations.inputs.name')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $administration->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('administrations.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >

            @can('create', App\Models\Administration::class)
            <a
                href="{{ route('administrations.create') }}"
                class="btn btn-primary"
            >
                @lang('crud.common.create')
            </a>
            @endcan
        </div>
    </div>
</div>

@endsection
