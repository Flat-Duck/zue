@php
    /** @var \App\Models\ScopeContext|null $context */
    $context = $context ?? null;
    $editing = $context !== null;
@endphp

<div class="row">
    <x-inputs.group class="col-sm-6">
        <label for="key" class="form-label required">@lang('scopes.contexts_key')</label>
        <input type="text" name="key" id="key" class="form-control @error('key') is-invalid @enderror"
            value="{{ old('key', $editing ? $context->key : '') }}"
            placeholder="@lang('scopes.contexts_key_example')" pattern="[a-z][a-z0-9_]*"
            @disabled($editing) @required(! $editing)>
        <small class="form-hint">
            @lang($editing && $context->isBuiltIn() ? 'scopes.contexts_built_in_hint' : 'scopes.contexts_key_hint')
        </small>
        @error('key') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
    </x-inputs.group>

    <x-inputs.group class="col-sm-3">
        <label for="sort_order" class="form-label">@lang('scopes.contexts_sort_order')</label>
        <input type="number" name="sort_order" id="sort_order" class="form-control" min="0" max="1000"
            value="{{ old('sort_order', $editing ? $context->sort_order : 0) }}">
    </x-inputs.group>

    <x-inputs.group class="col-sm-6">
        <label for="name" class="form-label required">@lang('scopes.contexts_name')</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $editing ? $context->name : '') }}" required>
        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </x-inputs.group>

    <x-inputs.group class="col-sm-6">
        <label for="name_ar" class="form-label">@lang('scopes.contexts_name_ar')</label>
        <input type="text" name="name_ar" id="name_ar" class="form-control" dir="rtl" lang="ar"
            value="{{ old('name_ar', $editing ? $context->name_ar : '') }}">
    </x-inputs.group>

    <div class="col-12">
        <label class="form-check form-switch">
            <input type="hidden" name="carves_out_managers" value="0">
            <input class="form-check-input" type="checkbox" name="carves_out_managers" value="1"
                @checked(old('carves_out_managers', $editing ? $context->carves_out_managers : false))>
            <span class="form-check-label">@lang('scopes.contexts_carves_out')</span>
        </label>
        <small class="form-hint d-block mb-3">@lang('scopes.contexts_carves_out_hint')</small>

        <label class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                @checked(old('is_active', $editing ? $context->is_active : true))>
            <span class="form-check-label">@lang('scopes.is_active')</span>
        </label>
    </div>
</div>
