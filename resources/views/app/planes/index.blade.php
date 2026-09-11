@extends('layouts.app', ['page' => 'planes'])
@section('content')
<div class="card">
    <div class="card-body border-bottom py-3">
        <div class="d-flex">
            <form>
                <div class="row g-2">
                    <div class="input-icon col">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input
                            id="indexSearch"
                            name="search"
                            type="text"
                            value=""
                            class="form-control"
                            placeholder="@lang('flights.search_2')"
                            aria-label="@lang('flights.search')"
                            spellcheck="false"
                            data-ms-editor="true"
                            autocomplete="off"
                        />
                    </div>
                    <div class="col-auto">
                        <button
                            class="btn btn-icon btn-primary"
                            aria-label="@lang('flights.button')"
                        >
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </div>
            </form>
            <div class="col-auto ms-auto d-print-none">
                @can('create', App\Models\Plane::class)
                <a
                    data-bs-original-title="@lang('flights.create')"
                    data-bs-placement="top"
                    data-bs-toggle="tooltip"
                    class="pull-right btn btn-primary"
                    href="{{ route('planes.create') }}"
                >
                    <i class="ti ti-plus"></i>
                    @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>

            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead>
                        <tr>
                            <th class="text-left">
                                @lang('crud.planes.inputs.name')
                            </th>
                            <th class="text-right">
                                @lang('crud.planes.inputs.capacity')
                            </th>
                            <th class="text-left">
                                @lang('crud.planes.inputs.lines')
                            </th>
                            <th class="text-center">
                                @lang('crud.common.actions')
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($planes as $plane)
                        <tr>
                            <td>{{ $plane->name ?? '-' }}</td>
                            <td>{{ $plane->capacity ?? '-' }}</td>
                            <td>{{ $plane->lines ?? '-' }}</td>
                            <td class="text-center table-actions">
                                <div
                                    role="group"
                                    aria-label="@lang('flights.row_actions')"
                                    class="btn-group"
                                >
                                    @can('update', $plane)
                                    <a
                                        href="{{ route('planes.edit', $plane) }}"
                                        class="btn btn-icon btn-outline-warinig ms-1"
                                    >
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    @endcan @can('view', $plane)
                                    <a
                                        href="{{ route('planes.show', $plane) }}"
                                        class="btn btn-icon btn-outline-info ms-1"
                                    >
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @endcan @can('delete', $plane)
                                    <form
                                        action="{{ route('planes.destroy', $plane) }}"
                                        method="POST"
                                        class="inline pointer ms-1"
                                        data-confirm="{{ __('crud.common.are_you_sure') }}"
                                    >
                                        @csrf @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-icon btn-outline-danger"
                                        >
                                            <i class="ti ti-trash-x"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4">@lang('crud.common.no_items_found')</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-left">
                {!! $planes->render() !!}
            </div>
        </div>
        @endsection
        