@php $editing = isset($residence) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="name"
            label="Name"
            :value="old('name', ($editing ? $residence->name : ''))"
            maxlength="255"
            placeholder="Name"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="type" label="Type">
            @php $selected = old('type', ($editing ? $residence->type : 'Villa')) @endphp
            <option value="Villa" {{ $selected == 'Villa' ? 'selected' : '' }} >Villa</option>
            <option value="Trailer" {{ $selected == 'Trailer' ? 'selected' : '' }} >Trailer</option>
        </x-inputs.select>
    </x-inputs.group>
</div>
