@php $editing = isset($administration) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="name" label="Name" :value="old('name', ($editing ? $administration->name : ''))"
            placeholder="@lang('ui.name')" required></x-inputs.text>
    </x-inputs.group>
</div>