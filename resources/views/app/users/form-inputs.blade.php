@php $editing = isset($user) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="number" label="ZOC No" :value="old('number', ($editing ? $user->id : ''))" maxlength="255"
            placeholder="ZOC No" required></x-inputs.text>
    </x-inputs.group>
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="name" label="Name" :value="old('name', ($editing ? $user->name : ''))" maxlength="255"
            placeholder="Name" required></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.email name="email" label="Email" :value="old('email', ($editing ? $user->email : ''))" maxlength="255"
            placeholder="Email" required></x-inputs.email>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.password name="password" label="Password" maxlength="255" placeholder="Password"
            :required="!$editing"></x-inputs.password>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <label class="form-label">@lang('Signature')</label>
        <input type="file" name="signature_file" class="form-control" accept="image/png, image/jpeg, image/webp" />
        @if($editing && $user->signature)
            <div class="mt-2">
                <img src="{{ asset('storage/' . $user->signature->image_path) }}" alt="Signature"
                    style="max-height: 50px; border: 1px solid #ccc;">
            </div>
        @endif
        @error('signature_file') @include('components.inputs.partials.error') @enderror
    </x-inputs.group>

    <div class="form-group col-sm-12 mt-4">
        <h4>Assign @lang('crud.roles.name')</h4>

        @foreach ($roles as $role)
            <div>
                <x-inputs.checkbox id="role{{ $role->id }}" name="roles[]" label="{{ ucfirst($role->name) }}"
                    value="{{ $role->id }}" :checked="isset($user) ? $user->hasRole($role) : false"
                    :add-hidden-value="false"></x-inputs.checkbox>
            </div>
        @endforeach
    </div>
</div>