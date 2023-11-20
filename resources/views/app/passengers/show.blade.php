@extends('layouts.app', ['page' => 'passengers'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('passengers.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.passengers.show_title')</h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.passengers.inputs.name')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $passenger->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.passengers.inputs.company')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $passenger->company ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.passengers.inputs.number')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $passenger->number ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.passengers.inputs.nationality')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $passenger->nationality ?? '-' }}"
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
                href="{{ route('passengers.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >

            @can('create', App\Models\Passenger::class)
            <a href="{{ route('passengers.create') }}" class="btn btn-primary">
                @lang('crud.common.create')
            </a>
            @endcan
        </div>
    </div>
</div>

@endsection
