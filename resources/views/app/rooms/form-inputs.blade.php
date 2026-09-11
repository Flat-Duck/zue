@php $editing = isset($room) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="number" label="Number" :value="old('number', ($editing ? $room->number : ''))"
            placeholder="@lang('ui.number')" required></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.number name="beds" label="Beds" :value="old('beds', ($editing ? $room->beds : ''))" max="255"
            placeholder="@lang('ui.beds')" required></x-inputs.number>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="residence_id" label="Residence" required>
            @php $selected = old('residence_id', ($editing ? $room->residence_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>@lang('ui.please_select_the_residence')</option>
            @foreach($residences as $k => $residence)
                <option value="{{ $residence->id }}" {{ $selected == $residence->id ? 'selected' : '' }}>
                    {{ $residence->type . ' - ' . $residence->name }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="employee_id[]" label="Employee" multiple required>
            @foreach($employees as $value => $label)
                @php
                    $selected = old('employee_id', ($editing && in_array($label, $residents) ? $label : ''));
                @endphp
                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>



</div>
@push('scripts')
    <script @cspNonce>
        document.addEventListener("DOMContentLoaded", function () {
            const selectElement = document.querySelector('#employee_id');
            let ts = new TomSelect(selectElement, {
                create: false,
                copyClassesToDropdown: false,
                dropdownParent: 'body',
                maxItems: {{ $editing ? $room->beds : 2 }},
                controlInput: '<input>',
                render: {
                    item: function (data, escape) {
                        if (data.customProperties) {
                            return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                        }
                        return '<div>' + escape(data.text) + '</div>';
                    },
                    option: function (data, escape) {
                        if (data.customProperties) {
                            return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                        }
                        return '<div>' + escape(data.text) + '</div>';
                    },
                },
            });
        });
    </script>
@endpush