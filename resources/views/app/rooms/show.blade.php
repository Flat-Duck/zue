@extends('layouts.app', ['page' => 'rooms'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('rooms.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.rooms.show_title')</h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.rooms.inputs.number')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $room->number ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.rooms.inputs.beds')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $room->beds ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.rooms.inputs.residence_id')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($room->residence)->name ?? '-' }}"
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
                href="{{ route('rooms.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >

            @can('create', App\Models\Room::class)
            <a href="{{ route('rooms.create') }}" class="btn btn-primary">
                @lang('crud.common.create')
            </a>
            @endcan
        </div>
    </div>
</div>

@endsection
