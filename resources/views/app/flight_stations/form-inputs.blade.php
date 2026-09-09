@php $editing = isset($station) @endphp

<div class="row">
    <x-inputs.group class="col-sm-6">
        <x-inputs.text name="name" label="Name" required
            :value="old('name', $editing ? $station->name : '')"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-6">
        <x-inputs.text name="name_ar" label="Arabic name (printed on manifests)"
            :value="old('name_ar', $editing ? $station->name_ar : '')"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-6">
        <x-inputs.text name="code" label="Code" required
            :value="old('code', $editing ? $station->code : '')"></x-inputs.text>
    </x-inputs.group>

    <div class="col-sm-6">
        <div class="mb-3">
            <label class="form-check form-switch">
                <input type="hidden" name="is_field" value="0">
                <input class="form-check-input" type="checkbox" name="is_field" value="1"
                    {{ old('is_field', $editing ? $station->is_field : false) ? 'checked' : '' }}>
                <span class="form-check-label">This is a field site</span>
            </label>

            <label class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $editing ? $station->is_active : true) ? 'checked' : '' }}>
                <span class="form-check-label">Active</span>
            </label>
        </div>
    </div>
</div>
