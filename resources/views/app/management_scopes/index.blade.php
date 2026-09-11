@extends('layouts.app', ['page' => 'management_scopes'])

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">@lang('scopes.title')</h2>
            <div class="text-secondary mt-1">@lang('scopes.subtitle')</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body border-bottom py-3">
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="{{ route('management-scopes.index') }}" class="flex-grow-1">
                <div class="row g-2">
                    <div class="col-sm">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input name="search" type="text" value="{{ request('search') }}"
                                class="form-control" placeholder="@lang('crud.common.search')"
                                aria-label="@lang('crud.common.search')" autocomplete="off">
                        </div>
                    </div>

                    <div class="col-sm-auto" style="min-width: 200px;">
                        <select name="context_id" class="form-select" onchange="this.form.submit()"
                            data-tomselect="select" aria-label="@lang('scopes.context')">
                            <option value="">@lang('scopes.all_contexts')</option>
                            @foreach ($contexts as $context)
                                <option value="{{ $context->id }}" @selected($contextId === $context->id)>
                                    {{ $context->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-auto" style="min-width: 240px;">
                        <select name="manager_id" class="form-select" onchange="this.form.submit()"
                            data-tomselect="select" aria-label="@lang('scopes.managers')">
                            <option value="">@lang('scopes.all_managers')</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" @selected($managerId === $manager->id)>
                                    {{ $manager->number }} - {{ $manager->english_name ?? '#'.$manager->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-auto">
                        <button class="btn btn-icon btn-primary" aria-label="@lang('crud.common.search')">
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </div>
            </form>

            <div class="ms-auto">
                <a class="btn btn-primary" href="{{ route('management-scopes.create', ['manager_id' => request('manager_id')]) }}">
                    <i class="ti ti-plus"></i>
                    @lang('scopes.create')
                </a>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>@lang('scopes.name')</th>
                    <th>@lang('scopes.context')</th>
                    <th>@lang('scopes.managers')</th>
                    <th>@lang('scopes.covers')</th>
                    <th class="text-center">@lang('crud.common.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($managementScopes as $scope)
                    <tr @class(['opacity-50' => ! $scope->is_active])>
                        <td>
                            {{ $scope->name ?: '#'.$scope->id }}
                            @unless ($scope->is_active)
                                <span class="badge bg-secondary-lt ms-1">@lang('scopes.is_active')</span>
                            @endunless
                        </td>
                        <td><span class="badge bg-blue-lt">{{ $scope->context?->label() }}</span></td>
                        <td>
                            @foreach ($scope->actors as $actor)
                                <span class="badge bg-secondary-lt mb-1">
                                    {{ $actor->actorEmployee?->english_name ?? '#'.$actor->actor_employee_id }}
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @include('app.management_scopes._coverage', ['scope' => $scope])
                        </td>
                        <td class="text-center table-actions">
                            <div role="group" aria-label="@lang('ui.row_actions')" class="btn-group">
                                <a href="{{ route('management-scopes.edit', $scope) }}"
                                    class="btn btn-icon btn-outline-warning ms-1"
                                    aria-label="@lang('crud.common.edit')">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('management-scopes.destroy', $scope) }}" method="POST"
                                    class="inline pointer ms-1"
                                    onsubmit="return confirm({{ Js::from(__('scopes.confirm_delete')) }})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-outline-danger"
                                        aria-label="@lang('crud.common.delete')">
                                        <i class="ti ti-trash-x"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-secondary">@lang('scopes.no_scopes')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-center">
        {!! $managementScopes->withQueryString()->render() !!}
    </div>
</div>
@endsection
