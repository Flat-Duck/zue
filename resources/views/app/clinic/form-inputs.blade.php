@php $editing = isset($clinical_exam) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="name" label="Name" :value="old('name', $editing ? $clinical_exam->name : '')" maxlength="255" placeholder="Name"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="company" label="Company" :value="old('company', $editing ? $clinical_exam->company : '')" maxlength="255"
            placeholder="Company"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="number" label="Number" :value="old('number', $editing ? $clinical_exam->number : '')" maxlength="255"
            placeholder="Number"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="nationality" label="Nationality" :value="old('nationality', $editing ? $clinical_exam->nationality : '')" maxlength="255"
            placeholder="Nationality"></x-inputs.text>
    </x-inputs.group>
    <div>
        <x-inputs.checkbox id="is_contractor" name="is_contractor" label="Contractor" value="1" :checked="isset($is_contractor) ? $clinical_exam->is_contractor : false"
            :add-hidden-value="false"></x-inputs.checkbox>
    </div>

</div>
