@extends('layouts.app', ['page' => 'scope_contexts'])

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">@lang('scopes.contexts_title')</h2>
            <div class="text-secondary mt-1">@lang('scopes.contexts_subtitle')</div>
        </div>
        <div class="col-auto ms-auto">
            @can('create', App\Models\ScopeContext::class)
                <a class="btn btn-primary" href="{{ route('scope-contexts.create') }}">
                    <i class="ti ti-plus"></i>
                    @lang('scopes.contexts_create')
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>@lang('scopes.contexts_key')</th>
                    <th>@lang('scopes.contexts_name')</th>
                    <th>@lang('scopes.contexts_carves_out')</th>
                    <th>@lang('scopes.is_active')</th>
                    <th>@lang('scopes.title')</th>
                    <th class="text-center">@lang('crud.common.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contexts as $context)
                    <tr @class(['opacity-50' => ! $context->is_active])>
                        <td>
                            <code>{{ $context->key }}</code>
                            @if ($context->isBuiltIn())
                                <span class="badge bg-blue-lt ms-1" title="@lang('scopes.contexts_built_in_hint')">
                                    @lang('scopes.contexts_built_in')
                                </span>
                            @endif
                        </td>
                        <td>
                            {{ $context->name }}
                            @if ($context->name_ar)
                                <div class="text-secondary small" lang="ar" dir="rtl">{{ $context->name_ar }}</div>
                            @endif
                        </td>
                        <td>{{ $context->carves_out_managers ? __('appraisals.yes') : __('appraisals.no') }}</td>
                        <td>{{ $context->is_active ? __('appraisals.yes') : __('appraisals.no') }}</td>
                        <td>
                            <a href="{{ route('management-scopes.index', ['context_id' => $context->id]) }}">
                                @lang('scopes.contexts_scope_count', ['count' => $context->policies_count])
                            </a>
                        </td>
                        <td class="text-center table-actions">
                            <div role="group" aria-label="@lang('ui.row_actions')" class="btn-group">
                                @can('update', $context)
                                    <a href="{{ route('scope-contexts.edit', $context) }}"
                                        class="btn btn-icon btn-outline-warning ms-1"
                                        aria-label="@lang('crud.common.edit')">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                @endcan
                                @can('delete', $context)
                                    <form action="{{ route('scope-contexts.destroy', $context) }}" method="POST"
                                        class="inline pointer ms-1"
                                        data-confirm="{{ __('scopes.contexts_confirm_delete') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-outline-danger"
                                            aria-label="@lang('crud.common.delete')">
                                            <i class="ti ti-trash-x"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary">@lang('scopes.contexts_none')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
