@extends('layouts.app', ['page' => 'users'])
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('users.index') }}" class="mr-4"
            ><i class="ti ti-arrow-back"></i
        ></a>
        <h3 class="card-title">@lang('crud.users.show_title')</h3>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-xl-4">
                <div class="row">
                    <div class="col-md-6 col-xl-12">
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.users.inputs.name')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $user->name ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.users.inputs.email')</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $user->email ?? '-' }}"
                                disabled=""
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                >@lang('crud.roles.name')</label
                            >
                            @forelse ($user->roles as $role)
                            <h2>
                                <span class="badge bg-azure-lt"
                                    >{{ $role->name }}</span
                                >
                            </h2>
                            @empty - @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <div class="d-flex">
            <a
                href="{{ route('users.index') }}"
                class="btn btn-outline-secondary"
                >@lang('crud.common.back')</a
            >

            @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                @lang('crud.common.create')
            </a>
            @endcan
        </div>
    </div>
</div>

@endsection
