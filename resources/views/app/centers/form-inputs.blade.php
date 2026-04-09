@php $editing = isset($center) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="name" label="Name" :value="old('name', ($editing ? $center->name : ''))" placeholder="Name"
            required></x-inputs.text>
    </x-inputs.group>
</div>