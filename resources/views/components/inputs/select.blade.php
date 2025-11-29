@props([
    'name',
    'label',
    'type' => 'text',
    'class' => 'form-select',
])

@if($label ?? null)
    @include('components.inputs.partials.label')
@endif

<select
    id="{{ str_replace('[]', '', $name) }}"
    name="{{ $name }}"
    {{ ($required ?? false) ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'form-control '.$class.'']) }}
    autocomplete="off"
    @error($name)
    {{ $attributes->merge(['class' => 'is-invalid']) }}
    {{-- {{dd($attributes->get('class'))}} --}}
    @enderror
>
    {{ $slot }}
</select>

@error($name)
    @include('components.inputs.partials.error')
@enderror