<div class="list-group-item" wire:key="navigation-item-{{ $item->id }}" draggable="true" x-on:dragstart="dragged = {{ $item->id }}" x-on:dragover.prevent x-on:drop.prevent="if (dragged) { $wire.moveBefore(dragged, {{ $item->id }}); dragged = null }">
    <div class="d-flex align-items-center gap-2" style="padding-left: {{ $level * 1.5 }}rem">
        <span class="text-muted cursor-move"><i class="ti ti-grip-vertical"></i></span>
        @if ($item->icon)
            <i class="{{ $item->icon }} text-muted"></i>
        @endif
        <div class="flex-fill">
            <div class="d-flex align-items-center gap-2">
                <strong>{{ $item->title() }}</strong>
                <span class="badge bg-secondary-lt">{{ $item->type }}</span>
                @if (! $item->is_active)
                    <span class="badge bg-red-lt">{{ __('navigation_builder.disabled') }}</span>
                @endif
            </div>
            <div class="text-muted small">
                @if ($item->route_name)
                    {{ $item->route_name }}
                @elseif ($item->isGroup())
                    {{ __('navigation_builder.empty_link_group') }}
                @elseif ($item->isHeader())
                    {{ __('navigation_builder.dropdown_header') }}
                @else
                    {{ __('navigation_builder.divider') }}
                @endif
            </div>
        </div>
        <div class="btn-list flex-nowrap">
            @if ($item->parent_id)
                <button type="button" class="btn btn-sm" wire:click="moveToRoot({{ $item->id }})">{{ __('navigation_builder.root') }}</button>
            @endif
            @foreach ($parentOptions as $parentOption)
                @if ($parentOption->id !== $item->id && $item->parent_id !== $parentOption->id)
                    <button type="button" class="btn btn-sm" wire:click="moveInto({{ $item->id }}, {{ $parentOption->id }})">{{ __('navigation_builder.move_into', ['name' => $parentOption->title()]) }}</button>
                    @break
                @endif
            @endforeach
            <button type="button" class="btn btn-sm" wire:click="toggle({{ $item->id }})">{{ $item->is_active ? __('navigation_builder.disable') : __('navigation_builder.enable') }}</button>
            <button type="button" class="btn btn-sm btn-primary" wire:click="edit({{ $item->id }})">{{ __('navigation_builder.edit') }}</button>
            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('navigation_builder.delete_confirm') }}">{{ __('navigation_builder.delete') }}</button>
        </div>
    </div>
</div>
@if ($item->relationLoaded('children'))
    @foreach ($item->children as $child)
        @include('livewire.partials.navigation-builder-item', ['item' => $child, 'level' => $level + 1])
    @endforeach
@endif
