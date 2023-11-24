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
{{-- <div 
    x-data="{
        show: @entangle($attributes->wire('model')).defer,
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
    }" --}}
    {{-- wire:ignore.self 
    class="modal fade" 
    tabindex="-1" 
    id="{{ $id }}" 
    aria-labelledby="{{ $id }}" 
    aria-hidden="true"
    x-ref="{{ $id }}"
> --}}
    {{-- <div class="modal-dialog{{ $maxWidth }}">
        {{ $slot }}
    </div> --}}
{{-- </div> --}}

<div x-data="{ show: true }"
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
wire:ignore.self class="modal modal-blur fade" x-ref="{{ $id }}" aria-labelledby="{{ $id }}" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal" role="document">
        {{ $slot }}
        {{-- <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">

                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div> --}}
    </div>
</div>
