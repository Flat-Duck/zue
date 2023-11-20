<header class="navbar navbar-expand-md d-print-none" >
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="{{ url('/') }}">
                <img src="../img/logo.svg" width="110" height="32" alt="zue" class="navbar-brand-image">
            </a>
        </h1>
        <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item d-none d-md-flex me-3">
                <div class="btn-list">
                <a href="https://github.com/sponsors/codecalm" class="btn" target="_blank" rel="noreferrer">
                    <i class="ti ti-heart text-pink"></i>
                    Sponsor
                </a>
            </div>
        </div>
        <div class="d-none d-md-flex">
            <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                <i class="ti ti-moon"></i>
            </a>
            <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                <i class="ti ti-sun"></i>
            </a>
            <div class="nav-item dropdown d-none d-md-flex me-3">
                <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                    <i class="ti ti-bell"></i>
                    <span class="badge bg-red"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Last updates</h3>
                        </div>
                        <div class="list-group list-group-flush list-group-hoverable">
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto"><span class="status-dot status-dot-animated bg-red d-block"></span></div>
                                        <div class="col text-truncate">
                                            <a href="#" class="text-body d-block">Example 1</a>
                                            <div class="d-block text-secondary text-truncate mt-n1">
                                                Change deprecated html tags to text decoration classes (#29604)
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="list-group-item-actions">
                                                <i class="ti ti-star"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="dropdown-item">Register</a>
                        @endif
                    @endguest
                    @auth
                        <a class="dropdown-item" href="{{ route('profile.show') }}" rel="noopener">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-user-circle"></i>
                            </span>
                            <span class="nav-link-title">
                                {{ __('Profile') }}
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
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</header>