<?php

namespace App\Services\Navigation;

use App\Models\Administration;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightRoute;
use App\Models\FlightStation;
use App\Models\Location;
use App\Models\NavigationItem;
use App\Models\Passenger;
use App\Models\Plane;
use App\Models\ScopeContext;
use App\Models\ScopePolicy;
use App\Models\TimeSheet;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NavigationDefaults
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function items(): array
    {
        return [
            self::link('nav.dashboard', 'ti ti-home', 'home', 10, policyModel: Employee::class),
            self::group('nav.dispatcher_management', 'ti ti-building-airport', 20, [
                self::link('nav.flights', 'ti ti-plane-departure', 'flights.index', 10, policyModel: Flight::class),
                self::link('nav.passengers', 'ti ti-friends', 'passengers.index', 20, policyModel: Passenger::class),
                self::link('nav.planes', 'ti ti-plane', 'planes.index', 30, policyModel: Plane::class),
                self::link('nav.routes', 'ti ti-route', 'flight-routes.index', 40, policyModel: FlightRoute::class),
                self::link('nav.stations', 'ti ti-map-pin', 'flight-stations.index', 50, policyModel: FlightStation::class),
            ]),
            self::group('nav.management', 'ti ti-lock-access', 30, [
                self::link('nav.administrations', 'ti ti-user-check', 'administrations.index', 10, policyModel: Administration::class),
                self::link('nav.centers', 'ti ti-key', 'centers.index', 20, policyModel: Center::class),
                self::link('nav.departments', 'ti ti-key', 'departments.index', 30, policyModel: Department::class),
                self::link('nav.locations', 'ti ti-key', 'locations.index', 40, policyModel: Location::class),
            ]),
            self::link('nav.time_sheets', 'ti ti-calendar-time', 'time-sheets.index', 40, policyModel: TimeSheet::class),
            self::link('nav.management_scopes', 'ti ti-adjustments-horizontal', 'management-scopes.index', 50, policyAbility: 'viewAny', policyModel: ScopePolicy::class),
            self::link('nav.scope_contexts', 'ti ti-category', 'scope-contexts.index', 60, policyAbility: 'viewAny', policyModel: ScopeContext::class),
            self::link('nav.operations', 'ti ti-settings-automation', 'operations.index', 70, policyModel: Employee::class),
            self::link('nav.maintenance', 'ti ti-database', 'maintenance.index', 80, policyModel: User::class),
            self::link('nav.system_status', 'ti ti-heartbeat', 'maintenance.status', 90, gate: 'maintenance'),
            self::link('nav.reports', 'ti ti-report-analytics', 'reports.index', 100, policyModel: TimeSheet::class),
            self::group('nav.appraisals', 'ti ti-clipboard-check', 110, [
                self::header('nav.builder', 10),
                self::link('nav.items', 'ti ti-list-details', 'appraisals.items.index', 20, gate: 'manage-appraisals'),
                self::link('nav.create_item', 'ti ti-square-plus', 'appraisals.items.create', 30, gate: 'manage-appraisals'),
                self::link('nav.forms', 'ti ti-layout', 'appraisals.forms.index', 40, gate: 'manage-appraisals'),
                self::link('nav.create_form', 'ti ti-square-plus', 'appraisals.forms.create', 50, gate: 'manage-appraisals'),
                self::divider(60),
                self::header('nav.process', 70),
                self::link('nav.periods', 'ti ti-calendar', 'appraisals.periods.index', 80, gate: 'manage-appraisals'),
                self::link('nav.reviews', 'ti ti-file-text', 'appraisals.reviews.index', 90),
                self::link('nav.create_review', 'ti ti-square-plus', 'appraisals.reviews.create', 100),
            ]),
            self::accessManagementGroup(120),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fallbackItems(): array
    {
        return [self::accessManagementGroup(10)];
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private static function group(string $labelKey, string $icon, int $sortOrder, array $children): array
    {
        return [
            'type' => NavigationItem::TYPE_GROUP,
            'label_key' => $labelKey,
            'icon' => $icon,
            'sort_order' => $sortOrder,
            'children' => $children,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function link(
        string $labelKey,
        string $icon,
        string $routeName,
        int $sortOrder,
        ?string $policyModel = null,
        string $policyAbility = 'view-any',
        ?string $permission = null,
        ?string $gate = null,
    ): array {
        $authorizationType = NavigationItem::AUTH_NONE;

        if ($gate) {
            $authorizationType = NavigationItem::AUTH_GATE;
        } elseif ($permission) {
            $authorizationType = NavigationItem::AUTH_PERMISSION;
        } elseif ($policyModel) {
            $authorizationType = NavigationItem::AUTH_POLICY;
        }

        return [
            'type' => NavigationItem::TYPE_LINK,
            'label_key' => $labelKey,
            'icon' => $icon,
            'route_name' => $routeName,
            'authorization_type' => $authorizationType,
            'permission_name' => $permission,
            'gate' => $gate,
            'policy_ability' => $policyModel ? $policyAbility : null,
            'policy_model' => $policyModel,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function header(string $labelKey, int $sortOrder): array
    {
        return [
            'type' => NavigationItem::TYPE_HEADER,
            'label_key' => $labelKey,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function divider(int $sortOrder): array
    {
        return [
            'type' => NavigationItem::TYPE_DIVIDER,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function accessManagementGroup(int $sortOrder): array
    {
        return self::group('nav.access_management', 'ti ti-lock-access', $sortOrder, [
            self::link('nav.users', 'ti ti-user-check', 'users.index', 10, policyModel: User::class),
            self::link('nav.roles', 'ti ti-user-check', 'roles.index', 20, policyModel: Role::class, policyAbility: 'list'),
            self::link('nav.permissions', 'ti ti-key', 'permissions.index', 30, policyModel: Permission::class, policyAbility: 'list'),
            self::link('nav.navigation_builder', 'ti ti-menu-2', 'navigation.builder', 40, gate: 'manage-navigation'),
        ]);
    }
}
