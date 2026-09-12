@foreach ($items as $item)
    @if ($item->isHeader())
        <div class="dropdown-header">{{ $item->title() }}</div>
    @elseif ($item->isDivider())
        <div class="dropdown-divider"></div>
    @elseif ($item->isLink())
        <a class="dropdown-item {{ $item->route_name && request()->routeIs($item->route_name) ? 'active' : '' }}" href="{{ route($item->route_name, $item->route_parameters ?? []) }}">
            @if ($item->icon)
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="{{ $item->icon }}"></i></span>
            @endif
            <span class="nav-link-title">{{ $item->title() }}</span>
        </a>
    @elseif ($item->isGroup())
        <div class="dropend">
            <a class="dropdown-item dropdown-toggle" href="#navbar-nav-{{ $item->id ?: Str::slug($item->title()) }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                @if ($item->icon)
                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="{{ $item->icon }}"></i></span>
                @endif
                <span class="nav-link-title">{{ $item->title() }}</span>
            </a>
            <div class="dropdown-menu">
                @include('layouts.navigation.dropdown-items', ['items' => $item->children])
            </div>
        </div>
    @endif
@endforeach
