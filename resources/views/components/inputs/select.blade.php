@props([
    'name',
    'label',
    'type' => 'text',
])

@if($label ?? null)
    @include('components.inputs.partials.label')
@endif

<select
    id="{{ $name }}"
    name="{{ $name }}"
    {{ ($required ?? false) ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'form-control']) }}
    autocomplete="off"
    @error($name)
    {{ $attributes->merge(['classs' => 'is-invalid']) }}
    {{-- {{dd($attributes->get('class'))}} --}}
    @enderror
>
    {{ $slot }}
</select>

@error($name)
    @include('components.inputs.partials.error')
@enderror