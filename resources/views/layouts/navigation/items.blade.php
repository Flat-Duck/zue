@foreach ($items as $item)
    @if ($item->isGroup())
        @php
            $isActive = $item->children->contains(fn ($child) => $child->route_name && request()->routeIs($child->route_name));
        @endphp
        <li class="nav-item dropdown {{ $isActive ? 'active' : '' }}">
            <a class="nav-link dropdown-toggle" href="#navbar-nav-{{ $item->id ?: Str::slug($item->title()) }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                @if ($item->icon)
                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="{{ $item->icon }}"></i></span>
                @endif
                <span class="nav-link-title">{{ $item->title() }}</span>
            </a>
            <div class="dropdown-menu">
                @include('layouts.navigation.dropdown-items', ['items' => $item->children])
            </div>
        </li>
    @elseif ($item->isLink())
        <li class="nav-item {{ $item->route_name && request()->routeIs($item->route_name) ? 'active' : '' }}">
            <a class="nav-link" href="{{ route($item->route_name, $item->route_parameters ?? []) }}">
                @if ($item->icon)
                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="{{ $item->icon }}"></i></span>
                @endif
                <span class="nav-link-title">{{ $item->title() }}</span>
            </a>
        </li>
    @endif
@endforeach
