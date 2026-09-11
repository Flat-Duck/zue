<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl">
                <ul class="navbar-nav">
                    @auth
                        @can('view-any', App\Models\Employee::class)
                            <li class="nav-item {{ $page == 'dashboard' ? 'active' : ''  }}">
                                <a class="nav-link" href="{{ route('home') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.dashboard')
                                    </span>
                                </a>
                            </li>
                        @endcan

                        @if (
                                Auth::user()->can('view-any', App\Models\Flight::class) ||
                                Auth::user()->can('view-any', App\Models\Passenger::class) ||
                                Auth::user()->can('view-any', App\Models\Plane::class) ||
                                Auth::user()->can('view-any', App\Models\FlightRoute::class) ||
                                Auth::user()->can('view-any', App\Models\FlightStation::class)
                            )
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#navbar-access" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-building-airport">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M3.59 7h8.82a1 1 0 0 1 .902 1.433l-1.44 3a1 1 0 0 1 -.901 .567h-5.942a1 1 0 0 1 -.901 -.567l-1.44 -3a1 1 0 0 1 .901 -1.433" />
                                            <path
                                                d="M6 7l-.78 -2.342a.5 .5 0 0 1 .473 -.658h4.612a.5 .5 0 0 1 .475 .658l-.78 2.342" />
                                            <path d="M8 2v2" />
                                            <path d="M6 12v9h4v-9" />
                                            <path d="M3 21h18" />
                                            <path d="M22 5h-6l-1 -1" />
                                            <path d="M18 3l2 2l-2 2" />
                                            <path d="M10 17h7a2 2 0 0 1 2 2v2" />
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.dispatcher_management')
                                    </span>
                                </a>
                                <div class="dropdown-menu">
                                    @can('view-any', App\Models\Flight::class)
                                        <a class="dropdown-item" href="{{ route('flights.index') }}" rel="noopener">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-plane-departure"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.flights')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', App\Models\Passenger::class)
                                        <a class="dropdown-item" href="{{ route('passengers.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-friends"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.passengers')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', App\Models\Plane::class)
                                        <a class="dropdown-item" href="{{ route('planes.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-plane"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.planes')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', App\Models\FlightRoute::class)
                                        <a class="dropdown-item" href="{{ route('flight-routes.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-route"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.routes')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', App\Models\FlightStation::class)
                                        <a class="dropdown-item" href="{{ route('flight-stations.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-map-pin"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.stations')
                                            </span>
                                        </a>
                                    @endcan
                                </div>
                            </li>
                        @endif

                        @if (
                                Auth::user()->can('view-any', App\Models\Administration::class) ||
                                Auth::user()->can('view-any', App\Models\Center::class) ||
                                Auth::user()->can('view-any', App\Models\Department::class) ||
                                Auth::user()->can('view-any', App\Models\Location::class)
                            )
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#navbar-access" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-lock-access"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.management')
                                    </span>
                                </a>
                                <div class="dropdown-menu">
                                    @can('view-any', App\Models\Administration::class)
                                        <a class="dropdown-item" href="{{ route('administrations.index') }}" rel="noopener">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-user-check"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.administrations')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', App\Models\Center::class)
                                        <a class="dropdown-item" href="{{ route('centers.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-key"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.centers')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', App\Models\Department::class)
                                        <a class="dropdown-item" href="{{ route('departments.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-key"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.departments')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', App\Models\Location::class)
                                        <a class="dropdown-item" href="{{ route('locations.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-key"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.locations')
                                            </span>
                                        </a>
                                    @endcan
                                </div>
                            </li>
                        @endif

                        {{-- @if (
                        Auth::user()->can('view-any', App\Models\Residence::class) ||
                        Auth::user()->can('view-any', App\Models\Room::class)
                        )
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-access" data-bs-toggle="dropdown"
                                data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-lock-access"></i>
                                </span>
                                <span class="nav-link-title">
                                    @lang('nav.camp_boss')
                                </span>
                            </a>
                            <div class="dropdown-menu">
                                @can('view-any', App\Models\Residence::class)
                                <a class="dropdown-item" href="{{ route('residences.index') }}" rel="noopener">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-user-check"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.residences')
                                    </span>
                                </a>
                                @endcan
                                @can('view-any', App\Models\Room::class)
                                <a class="dropdown-item" href="{{ route('rooms.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-key"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.rooms')
                                    </span>
                                </a>
                                @endcan
                            </div>
                        </li>
                        @endif --}}

                        {{-- @can('view-any', App\Models\Stock::class)
                        <li class="nav-item {{ $page == 'stocks' ? 'active' : ''  }}">
                            <a class="nav-link" href="{{ route('stocks.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/Stocks -->
                                    <!-- Stocks Icon -->
                                </span>
                                <span class="nav-link-title">
                                    @lang('nav.stocks')
                                </span>
                            </a>
                        </li>
                        @endcan --}}
                        @can('view-any', App\Models\TimeSheet::class)
                            <li class="nav-item {{ $page == 'time-sheets' ? 'active' : ''  }}">
                                <a class="nav-link" href="{{ route('time-sheets.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-calendar-time"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.time_sheets')
                                    </span>
                                </a>
                            </li>
                        @endcan
                        @can('viewAny', App\Models\ScopePolicy::class)
                            <li class="nav-item {{ $page == 'management_scopes' ? 'active' : ''  }}">
                                <a class="nav-link" href="{{ route('management-scopes.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-adjustments-horizontal"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.management_scopes')
                                    </span>
                                </a>
                            </li>
                        @endcan
                        @can('view-any', App\Models\Employee::class)
                            <li class="nav-item {{ $page == 'operations' ? 'active' : ''  }}">
                                <a class="nav-link" href="{{ route('operations.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-settings-automation"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.operations')
                                    </span>
                                </a>
                            </li>
                        @endcan
                        @can('view-any', App\Models\User::class)
                            <li class="nav-item {{ $page == 'maintenance' ? 'active' : ''  }}">
                                <a class="nav-link" href="{{ route('maintenance.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-database"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.maintenance')
                                    </span>
                                </a>
                            </li>
                        @endcan
                        @can('view-any', App\Models\TimeSheet::class)
                            <li class="nav-item {{ $page == 'reports' ? 'active' : ''  }}">
                                <a class="nav-link" href="{{ route('reports.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-report-analytics"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.reports')
                                    </span>
                                </a>
                            </li>
                        @endcan

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-appraisals" data-bs-toggle="dropdown"
                                data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-clipboard-check"></i>
                                </span>
                                <span class="nav-link-title">
                                    @lang('nav.appraisals')
                                </span>
                            </a>

                            <div class="dropdown-menu">

                                {{-- ========= Builder: Items / Forms / Versions ========= --}}
                                <div class="dropdown-header">@lang('nav.builder')</div>

                                <a class="dropdown-item" href="{{ route('appraisals.items.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-list-details"></i></span>
                                    <span class="nav-link-title">@lang('nav.items')</span>
                                </a>

                                <a class="dropdown-item" href="{{ route('appraisals.items.create') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-square-plus"></i></span>
                                    <span class="nav-link-title">@lang('nav.create_item')</span>
                                </a>

                                <a class="dropdown-item" href="{{ route('appraisals.forms.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-layout"></i></span>
                                    <span class="nav-link-title">@lang('nav.forms')</span>
                                </a>

                                <a class="dropdown-item" href="{{ route('appraisals.forms.create') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-square-plus"></i></span>
                                    <span class="nav-link-title">@lang('nav.create_form')</span>
                                </a>

                                {{-- NOTE: versions/form edit تحتاج ID، هذي روابط “مثال” حطها في صفحات الforms نفسها --}}
                                {{-- route('appraisals.versions.index', $form->id) --}}
                                {{-- route('appraisals.forms.edit', $form->id) --}}
                                {{-- route('appraisals.version-items.edit', $version->id) --}}

                                <div class="dropdown-divider"></div>

                                {{-- ========= Appraisal Process ========= --}}
                                <div class="dropdown-header">@lang('nav.process')</div>

                                {{-- ⚠️ حسب web.php اللي عندك ممكن الاسم يكون مكرر appraisals.appraisals.* --}}
                                {{-- إذا شغّال عندك الاسم هذا استخدمه بدل اللي تحت --}}
                                {{-- href="{{ route('appraisals.appraisals.periods.index') }}" --}}
                                <a class="dropdown-item" href="{{ route('appraisals.periods.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-calendar"></i></span>
                                    <span class="nav-link-title">@lang('nav.periods')</span>
                                </a>

                                {{-- href="{{ route('appraisals.appraisals.reviews.index') }}" --}}
                                <a class="dropdown-item" href="{{ route('appraisals.reviews.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-file-text"></i></span>
                                    <span class="nav-link-title">@lang('nav.reviews')</span>
                                </a>

                                {{-- href="{{ route('appraisals.appraisals.reviews.create') }}" --}}
                                <a class="dropdown-item" href="{{ route('appraisals.reviews.create') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-square-plus"></i></span>
                                    <span class="nav-link-title">@lang('nav.create_review')</span>
                                </a>

                                <div class="dropdown-divider"></div>

                                {{-- ========= Employee form assignment ========= --}}
                                <div class="dropdown-header">@lang('nav.employee')</div>

                                {{-- هذا يحتاج employee id: حطه عادة في صفحة الموظف --}}
                                {{-- href="{{ route('appraisals.employees.appraisal-form.edit', $employee->id) }}" --}}
                                <span class="dropdown-item text-muted">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-user"></i></span>
                                    <span class="nav-link-title">@lang('nav.assign_employee_form')</span>
                                </span>

                                {{-- Official show/finalize يحتاج period + employee: حطه في صفحة الموظف/التقارير --}}
                                <span class="dropdown-item text-muted">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i
                                            class="ti ti-chart-bar"></i></span>
                                    <span class="nav-link-title">@lang('nav.official_result')</span>
                                </span>

                            </div>
                        </li>

                        @if (
                                Auth::user()->can('view-any', Spatie\Permission\Models\Role::class) ||
                                Auth::user()->can('view-any', Spatie\Permission\Models\Permission::class)
                            )
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#navbar-access" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-lock-access"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        @lang('nav.access_management')
                                    </span>
                                </a>

                                <div class="dropdown-menu">
                                    @can('view-any', App\Models\User::class)
                                        <a class="dropdown-item" href="{{ route('users.index') }}" rel="noopener">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-user-check"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.users')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', Spatie\Permission\Models\Role::class)
                                        <a class="dropdown-item" href="{{ route('roles.index') }}" rel="noopener">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-user-check"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.roles')
                                            </span>
                                        </a>
                                    @endcan
                                    @can('view-any', Spatie\Permission\Models\Permission::class)
                                        <a class="dropdown-item" href="{{ route('permissions.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="ti ti-key"></i>
                                            </span>
                                            <span class="nav-link-title">
                                                @lang('nav.permissions')
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