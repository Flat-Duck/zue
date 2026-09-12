@php
    $navigationItems = app(App\Services\Navigation\NavigationMenuBuilder::class)->forUser(auth()->user());
@endphp

<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl">
                <ul class="navbar-nav">
                    @auth
                        @include('layouts.navigation.items', ['items' => $navigationItems])
                    @endauth
                </ul>
            </div>
        </div>
    </div>
</header>
