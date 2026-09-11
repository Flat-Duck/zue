@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('appraisals.version_items')</h2>
                    <div class="text-muted">
                        {{ $version->form->name_ar }} ({{ $version->form->code }}) — v{{ $version->version }}
                        @if($version->is_active) <span class="badge bg-green ms-2">@lang('appraisals.active')</span> @endif
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a class="btn btn-outline-secondary"
                        href="{{ route('appraisals.versions.index', $version->form->id) }}">@lang('appraisals.back_to_versions')</a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">@lang('appraisals.add_item_to_this_version')</h3>
            </div>
            <div class="card-body">
                <form class="row g-3" method="POST"
                    action="{{ route('appraisals.version-items.add', $version->id) }}">
                    @csrf

                    <div class="col-md-5">
                        <label class="form-label">@lang('appraisals.item')</label>
                        <select name="item_id" class="form-select" required>
                            <option value="">@lang('appraisals.choose_placeholder')</option>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->default_section }} | {{ $i->key }} | {{ $i->default_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">@lang('appraisals.max_score')</label>
                        <input type="number" name="max_score_override" class="form-control"
                            value="{{ old('max_score_override', 0) }}" min="0" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">@lang('appraisals.sort')</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 10) }}"
                            min="1" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">@lang('appraisals.override_section_optional')</label>
                        <select name="section_override" class="form-select">
                            <option value="">@lang('appraisals.default')</option>
                            @foreach(['job_performance', 'personal_traits', 'initiative'] as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">@lang('appraisals.override_label_optional')</label>
                        <input name="label_override" class="form-control" value="{{ old('label_override') }}">
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_required" value="1" checked>
                            <span class="form-check-label">@lang('appraisals.required')</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <span class="form-check-label">@lang('appraisals.active_2')</span>
                        </label>
                        <button class="btn btn-primary ms-auto">@lang('appraisals.add')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">@lang('appraisals.items_in_this_version')</h3>
            </div>

            <form method="POST" action="{{ route('appraisals.version-items.update', $version->id) }}">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>@lang('appraisals.item')</th>
                                <th style="width:140px">@lang('appraisals.section')</th>
                                <th style="width:220px">@lang('appraisals.label_override')</th>
                                <th class="text-center" style="width:120px">@lang('appraisals.max')</th>
                                <th class="text-center" style="width:90px">@lang('appraisals.sort')</th>
                                <th class="text-center" style="width:90px">@lang('appraisals.req')</th>
                                <th class="text-center" style="width:90px">@lang('appraisals.active_2')</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versionItems as $vi)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $vi->item->key }}</div>
                                        <div class="text-muted">{{ $vi->item->default_label }}</div>
                                    </td>

                                    <td>
                                        <input type="hidden" name="rows[{{ $loop->index }}][id]" value="{{ $vi->id }}">
                                        <select class="form-select" name="rows[{{ $loop->index }}][section_override]">
                                            <option value="">@lang('appraisals.default_2')</option>
                                            @foreach(['job_performance', 'personal_traits', 'initiative'] as $s)
                                                <option value="{{ $s }}" {{ $vi->section_override === $s ? 'selected' : '' }}>{{ $s }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <input class="form-control" name="rows[{{ $loop->index }}][label_override]"
                                            value="{{ $vi->label_override }}">
                                    </td>

                                    <td class="text-center">
                                        <input type="number" class="form-control text-center"
                                            name="rows[{{ $loop->index }}][max_score_override]"
                                            value="{{ $vi->max_score_override ?? 0 }}" min="0" required>
                                    </td>

                                    <td class="text-center">
                                        <input type="number" class="form-control text-center"
                                            name="rows[{{ $loop->index }}][sort_order]" value="{{ $vi->sort_order }}" min="1"
                                            required>
                                    </td>

                                    <td class="text-center">
                                        <input type="hidden" name="rows[{{ $loop->index }}][is_required]" value="0">
                                        <input type="checkbox" class="form-check-input"
                                            name="rows[{{ $loop->index }}][is_required]" value="1" {{ $vi->is_required ? 'checked' : '' }}>
                                    </td>

                                    <td class="text-center">
                                        <input type="hidden" name="rows[{{ $loop->index }}][is_active]" value="0">
                                        <input type="checkbox" class="form-check-input"
                                            name="rows[{{ $loop->index }}][is_active]" value="1" {{ $vi->is_active ? 'checked' : '' }}>
                                    </td>

                                    <td>
                                        <form method="POST"
                                            action="{{ route('appraisals.version-items.destroy', [$version->id, $vi->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm({{ Js::from(__('appraisals.confirm_delete_version_item')) }})">@lang('appraisals.delete')</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                            @if($versionItems->count() === 0)
                                <tr>
                                    <td colspan="8" class="text-center text-muted">@lang('appraisals.no_items_linked')</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">@lang('appraisals.save_all_changes')</button>
                    <a class="btn btn-outline-secondary" href="{{ route('appraisals.items.index') }}">@lang('appraisals.items_library')</a>
                </div>
            </form>
        </div>
    </div>
@endsection