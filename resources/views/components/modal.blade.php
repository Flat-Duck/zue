{{--
    This modal was copied from Jetstrap components,
    an awesome package for adapting Jetstrem for Bootstrap:

    https://github.com/nascent-africa/jetstrap
--}}

@props(['id', 'maxWidth', 'modal' => false])

@php
$id = $id ?? md5($attributes->wire('model'));
switch ($maxWidth ?? '') {
    case 'sm':
        $maxWidth = ' modal-sm';
        break;
    case 'md':
        $maxWidth = '';
        break;
    case 'lg':
        $maxWidth = ' modal-lg';
        break;
    case 'xl':
        $maxWidth = ' modal-xl';
        break;
    case '2xl':
    default:
        $maxWidth = '';
        break;
}
@endphp

<!-- Modal -->
<div
    x-data="{
        show: @entangle($attributes->wire('model')),
    }"
    x-init="() => {
        let modal = $('#{{ $id }}');
        $watch('show', value => {
            if (value) {
                modal.modal('show')
            } else {
                modal.modal('hide')
            }
        });
        modal.on('hide.bs.modal', function () {
            show = false
        })
    }"
    wire:ignore.self
    class="modal modal-blur fade"
    tabindex="-1"
    id="{{ $id }}"
    aria-labelledby="{{ $id }}"
    aria-hidden="true"
    role="dialog"
    x-ref="{{ $id }}"
>
    <div class="modal-dialog {{ $maxWidth }} modal-dialog-centered" role="document">
        {{ $slot }}
    </div>
</div>
