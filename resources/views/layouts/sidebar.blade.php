<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl">
                <ul class="navbar-nav">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}" >
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-home"></i>
                                </span>
                                <span class="nav-link-title">
                                    Dashboard
                                </span>
                            </a>
                        </li>
                            {{-- @can('view-any', App\Models\Administration::class)
                                <li class="nav-item {{ $page == 'administrations'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('administrations.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Administrations -->
                                            <!-- Administrations Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Administrations
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Center::class)
                                <li class="nav-item {{ $page == 'centers'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('centers.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Centers -->
                                            <!-- Centers Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Centers
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Department::class)
                                <li class="nav-item {{ $page == 'departments'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('departments.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Departments -->
                                            <!-- Departments Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Departments
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Employee::class)
                                <li class="nav-item {{ $page == 'employees'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('employees.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Employees -->
                                            <!-- Employees Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Employees
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Flight::class)
                                <li class="nav-item {{ $page == 'flights'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('flights.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Flights -->
                                            <!-- Flights Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Flights
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Location::class)
                                <li class="nav-item {{ $page == 'locations'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('locations.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Locations -->
                                            <!-- Locations Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Locations
                                        </span>
                                    </a>
                                </li>
                            @endcan --}}
                            @can('view-any', App\Models\Passenger::class)
                                <li class="nav-item {{ $page == 'passengers'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('passengers.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Passengers -->
                                            <!-- Passengers Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Passengers
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Residence::class)
                                <li class="nav-item {{ $page == 'residences'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('residences.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Residences -->
                                            <!-- Residences Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Residences
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Room::class)
                                <li class="nav-item {{ $page == 'rooms'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('rooms.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Rooms -->
                                            <!-- Rooms Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Rooms
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\Stock::class)
                                <li class="nav-item {{ $page == 'stocks'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('stocks.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Stocks -->
                                            <!-- Stocks Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Stocks
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\TimeSheet::class)
                                <li class="nav-item {{ $page == 'time-sheets'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('time-sheets.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Time Sheets -->
                                            <!-- Time Sheets Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Time Sheets
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-any', App\Models\User::class)
                                <li class="nav-item {{ $page == 'users'? 'active':''  }}">
                                    <a class="nav-link" href="{{ route('users.index') }}" >
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/Users -->
                                            <!-- Users Icon -->
                                        </span>
                                        <span class="nav-link-title">
                                            Users
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @if (Auth::user()->can('view-any', Spatie\Permission\Models\Role::class) || 
                            Auth::user()->can('view-any', Spatie\Permission\Models\Permission::class))
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#navbar-access" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <i class="ti ti-lock-access"></i>
                                        </span>
                                        <span class="nav-link-title">
                                            Access Management
                                        </span>
                                    </a>
                                    <div class="dropdown-menu">
                                        @can('view-any', Spatie\Permission\Models\Role::class)
                                            <a class="dropdown-item" href="{{ route('roles.index') }}" rel="noopener">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                    <i class="ti ti-user-check"></i>       
                                                </span>
                                                <span class="nav-link-title">
                                                    Roles
                                                </span>
                                            </a>
                                        @endcan
                                        @can('view-any', Spatie\Permission\Models\Permission::class)
                                            <a class="dropdown-item" href="{{ route('permissions.index') }}">
                                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                    <i class="ti ti-key"></i>
                                                </span>
                                                <span class="nav-link-title">
                                                    Permissions
                                                </span>
                                            </a>
                                        @endcan
                                    </div>
                                </li>
                            @endif
                    @endauth
                </ul>
            </div>
        </div>
    </div>
</header>