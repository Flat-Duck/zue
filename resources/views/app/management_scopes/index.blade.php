@extends('layouts.app', ['page' => 'management_scopes'])

@section('content')
<div class="card">
    <div class="card-body border-bottom py-3">
        <div class="d-flex">
            <form method="GET" action="{{ route('management-scopes.index') }}">
                <div class="row g-2">
                    <div class="input-icon col">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input
                            id="indexSearch"
                            name="search"
                            type="text"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="@lang('crud.common.search')"
                            aria-label="@lang('ui.search_2')"
                            autocomplete="off"
                        />
                    </div>

                    <div class="col-auto" style="min-width: 250px;">
                        <select
                            name="manager_id"
                            class="form-control"
                            onchange="this.form.submit()"
                            data-tomselect="select"
                        >
                            <option value="">
                                @lang('crud.management_scopes.filters.all_managers', [], 'en')
                            </option>
                            @foreach($managers as $manager)
                                <option
                                    value="{{ $manager->id }}"
                                    @selected(request('manager_id') == $manager->id)
                                >
                                    {{ $manager->number }} - {{ $manager->english_name ?? ('#'.$manager->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-auto">
                        <button
                            class="btn btn-icon btn-primary"
                            aria-label="@lang('ui.button')"
                        >
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </div>
            </form>

            <div class="col-auto ms-auto d-print-none">
                <a
                    data-bs-original-title="@lang('crud.common.create')"
                    data-bs-placement="top"
                    data-bs-toggle="tooltip"
                    class="pull-right btn btn-primary"
                    href="{{ route('management-scopes.create', ['manager_id' => request('manager_id')]) }}"
                >
                    <i class="ti ti-plus"></i>
                    @lang('crud.common.create')
                </a>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap datatable">
            <thead>
                <tr>
                    <th class="text-left">@lang('ui.scope_name')</th>
                    <th class="text-left">@lang('ui.template')</th>
                    <th class="text-left">@lang('ui.context')</th>
                    <th class="text-left">@lang('ui.managers')</th>
                    <th class="text-left">
                        @lang('crud.management_scopes.columns.scope_type', [], 'en')
                    </th>
                    <th class="text-left">
                        @lang('crud.management_scopes.columns.scope_detail', [], 'en')
                    </th>
                    <th class="text-center">
                        @lang('crud.common.actions')
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($managementScopes as $scope)
                    <tr>
                        <td>{{ $scope->name ?? '-' }}</td>
                        <td><span class="badge badge-info">{{ ucfirst($scope->template) }}</span></td>
                        <td><span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $scope->context)) }}</span></td>
                        <td>
                            @foreach($scope->managers as $m)
                                <span class="badge badge-outline-secondary mb-1">
                                    {{ $m->english_name }}
                                </span>
                                @if(!$loop->last) <br> @endif
                            @endforeach
                            @if($scope->managers->isEmpty() && $scope->manager_id)
                                <span class="badge badge-outline-secondary">
                                    @lang('ui.legacy_manager', ['name' => $scope->manager->english_name ?? '#'.$scope->manager_id])
                                </span>
                            @endif
                        </td>
                        <td>
                            {{ ucfirst($scope->scope_type) }}
                        </td>
                        <td>
                            @switch($scope->scope_type)
                                @case(\App\Models\ManagementScope::TYPE_GLOBAL)
                                    @lang('crud.management_scopes.scope_labels.global', [], 'en')
                                    @break

                                @case(\App\Models\ManagementScope::TYPE_LOCATION)
                                    {{ $scope->location->name ?? '-' }}
                                    @break

                                @case(\App\Models\ManagementScope::TYPE_DEPARTMENT)
                                    {{ $scope->location->name ?? '-' }} /
                                    {{ $scope->department->name ?? '-' }}
                                    @break

                                @case(\App\Models\ManagementScope::TYPE_CENTER)
                                    {{ $scope->center->name ?? '-' }}
                                    @break

                                @case(\App\Models\ManagementScope::TYPE_EMPLOYEE)
                                    {{ $scope->subordinate?->english_name ?? ('#'.$scope->subordinate_employee_id) }}
                                    @if(!empty($scope->settings['target_employee_ids']))
                                        <br><small>@lang('ui.plus_grouped', ['count' => count($scope->settings['target_employee_ids'])])</small>
                                    @endif
                                    @break

                                @default
                                    -
                            @endswitch
                        </td>
                        <td class="text-center table-actions">
                            <div
                                role="group"
                                aria-label="@lang('ui.row_actions')"
                                class="btn-group"
                            >
                                <a
                                    href="{{ route('management-scopes.edit', $scope) }}"
                                    class="btn btn-icon btn-outline-warinig ms-1"
                                >
                                    <i class="ti ti-edit"></i>
                                </a>

                                <form
                                    action="{{ route('management-scopes.destroy', $scope) }}"
                                    method="POST"
                                    class="inline pointer ms-1"
                                    onsubmit="return confirm('{{ __('crud.common.are_you_sure') }}')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="btn btn-icon btn-outline-danger"
                                    >
                                        <i class="ti ti-trash-x"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @lang('crud.common.no_items_found')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-left">
        {!! $managementScopes->withQueryString()->render() !!}
    </div>
</div>
@endsection
