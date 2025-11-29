@extends('layouts.app', ['page' => 'planes'])

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('planes.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.planes.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.planes.inputs.name')</h5>
                    <span>{{ $plane->name ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.planes.inputs.capacity')</h5>
                    <span>{{ $plane->capacity ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.planes.inputs.lines')</h5>
                    <span>{{ $plane->lines ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('planes.index') }}" class="btn btn-light">
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\Plane::class)
                <a href="{{ route('planes.create') }}" class="btn btn-light">
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
