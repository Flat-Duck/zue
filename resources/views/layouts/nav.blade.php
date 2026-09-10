<header class="navbar navbar-expand-md d-print-none" >
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="{{ url('/') }}">
                <img src="{{ asset('img/logo.svg') }}" width="110" height="32" alt="zue" class="navbar-brand-image">
            </a>
        </h1>
        <div class="navbar-nav flex-row order-md-last">
            @if(auth()->check() && session()->has('impersonator_id'))
                <div class="nav-item d-none d-md-flex me-3 align-items-center">
                    <span class="badge bg-orange-lt text-orange me-2">
                        Signed in as {{ auth()->user()->name }}
                    </span>
                    <form action="{{ route('users.impersonate.stop') }}" method="POST" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            Return to {{ session('impersonator_name', 'Super Admin') }}
                        </button>
                    </form>
                </div>
            @endif
            <div class="nav-item dropdown d-none d-md-flex me-3">
                <a href="#" class="nav-link px-2" data-bs-toggle="dropdown" aria-label="@lang('crud.common.language')" title="@lang('crud.common.language')">
                    <i class="ti ti-language"></i>
                    <span class="ms-1">{{ config('locales.supported.'.app()->getLocale().'.native') }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    @foreach (config('locales.supported') as $code => $language)
                        <a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}"
                           href="{{ route('locale.switch', $code) }}"
                           lang="{{ $code }}">
                            {{ $language['native'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        <div class="d-none d-md-flex">
            {{-- `fullUrlWithQuery` keeps whatever the page was already showing: switching
                 theme from a filtered list should not throw the filter away. --}}
            <a href="{{ request()->fullUrlWithQuery(['theme' => 'dark']) }}"
               class="nav-link px-0 hide-theme-dark"
               title="@lang('crud.common.dark_mode')"
               aria-label="@lang('crud.common.dark_mode')"
               data-bs-toggle="tooltip" data-bs-placement="bottom">
                <i class="ti ti-moon"></i>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['theme' => 'light']) }}"
               class="nav-link px-0 hide-theme-light"
               title="@lang('crud.common.light_mode')"
               aria-label="@lang('crud.common.light_mode')"
               data-bs-toggle="tooltip" data-bs-placement="bottom">
                <i class="ti ti-sun"></i>
            </a>
            <livewire:notification-bell />
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                    {{-- <span class="avatar avatar-sm" style="background-image: url(./static/avatars/000m.jpg)"></span> --}}
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ auth()->user()->name ?? null }}</div>
                        <div class="mt-1 small text-secondary">{{ auth()->user()->email ?? null }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    @guest
                        <a href="{{ route('login') }}" class="dropdown-item">Login</a>
                    @endguest
                    @auth
                        @if(session()->has('impersonator_id'))
                            <form action="{{ route('users.impersonate.stop') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-arrow-back-up"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        Return to {{ session('impersonator_name', 'Super Admin') }}
                                    </span>
                                </button>
                            </form>
                        @endif
                        <a class="dropdown-item" href="{{ route('profile.show') }}" rel="noopener">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-user-circle"></i>
                            </span>
                            <span class="nav-link-title">
                                {{ __('Profile') }}
                            </span>
                        </a>
                        <a class="dropdown-item" href="{{ route('signature.show') }}" rel="noopener">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-signature"></i>
                            </span>
                            <span class="nav-link-title">
                                {{ __('My Signature') }}
                            </span>
                        </a>
                        <a class="dropdown-item" href="{{ route('logout') }}" rel="noopener" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-logout-2"></i>
                            </span>
                            <span class="nav-link-title">
                                {{ __('Logout') }}
                            </span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</header>
