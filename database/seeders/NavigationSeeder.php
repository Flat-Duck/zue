<?php

namespace Database\Seeders;

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
use App\Services\Navigation\NavigationDefaults;
use App\Services\Navigation\NavigationRouteRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NavigationSeeder extends Seeder
{
    public function __construct(private readonly NavigationRouteRegistry $routes) {}

    public function run(): void
    {
        DB::transaction(function (): void {
            NavigationItem::query()->delete();

            $defaultItems = NavigationDefaults::items();
            $seededRouteNames = collect($defaultItems)
                ->flatMap(fn (array $item): array => $this->routeNamesFrom($item))
                ->unique()
                ->values();

            foreach ($defaultItems as $item) {
                $this->createItem($item);
            }

            $currentRouteItems = $this->currentRouteItems($seededRouteNames);

            if ($currentRouteItems !== []) {
                $this->createItem([
                    'type' => NavigationItem::TYPE_GROUP,
                    'label_key' => 'nav.current_routes',
                    'icon' => 'ti ti-route',
                    'sort_order' => 10_000,
                    'is_active' => false,
                    'children' => $currentRouteItems,
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    private function routeNamesFrom(array $item): array
    {
        $routeNames = [];

        if (isset($item['route_name']) && is_string($item['route_name']) && $item['route_name'] !== '') {
            $routeNames[] = $item['route_name'];
        }

        foreach (($item['children'] ?? []) as $child) {
            if (is_array($child)) {
                array_push($routeNames, ...$this->routeNamesFrom($child));
            }
        }

        return $routeNames;
    }

    /**
     * @param  Collection<int, string>  $seededRouteNames
     * @return list<array<string, mixed>>
     */
    private function currentRouteItems(Collection $seededRouteNames): array
    {
        return collect($this->routes->namedGetRoutes())
            ->filter(fn (array $route): bool => $route['navigable'] === true)
            ->reject(fn (array $route): bool => $seededRouteNames->contains($route['name']))
            ->values()
            ->map(function (array $route, int $index): array {
                return [
                    'type' => NavigationItem::TYPE_LINK,
                    'label' => (string) $route['name'],
                    'icon' => 'ti ti-link',
                    'route_name' => (string) $route['name'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => false,
                    ...$this->authorizationForRoute((string) $route['name']),
                ];
            })
            ->all();
    }

    /**
     * @return array{authorization_type: string, permission_name?: string, gate?: string, policy_model?: class-string, policy_ability?: string}
     */
    private function authorizationForRoute(string $routeName): array
    {
        foreach ($this->gateRouteMap() as $prefix => $gateName) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return [
                    'authorization_type' => NavigationItem::AUTH_GATE,
                    'gate' => $gateName,
                ];
            }
        }

        foreach ($this->policyRouteMap() as $prefix => [$policyClass, $policyAbility]) {
            if (str_starts_with($routeName, $prefix)) {
                return [
                    'authorization_type' => NavigationItem::AUTH_POLICY,
                    'policy_model' => $policyClass,
                    'policy_ability' => $policyAbility,
                ];
            }
        }

        foreach ($this->permissionRouteMap() as $prefix => $permissionName) {
            if (str_starts_with($routeName, $prefix)) {
                return [
                    'authorization_type' => NavigationItem::AUTH_PERMISSION,
                    'permission_name' => $permissionName,
                ];
            }
        }

        return ['authorization_type' => NavigationItem::AUTH_NONE];
    }

    /**
     * @return array<string, string>
     */
    private function gateRouteMap(): array
    {
        return [
            'appraisals.' => 'manage-appraisals',
            'clinic.' => 'manage-clinic',
            'dashboard.performance' => 'view-dashboard-performance',
            'injury-reports.' => 'manage-clinic',
            'maintenance.' => 'maintenance',
            'navigation.builder' => 'manage-navigation',
            'operations.' => 'manage-operations',
        ];
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    private function policyRouteMap(): array
    {
        return [
            'administrations.' => [Administration::class, 'view-any'],
            'centers.' => [Center::class, 'view-any'],
            'departments.' => [Department::class, 'view-any'],
            'employees.' => [Employee::class, 'view-any'],
            'flight-routes.' => [FlightRoute::class, 'view-any'],
            'flight-stations.' => [FlightStation::class, 'view-any'],
            'flights.' => [Flight::class, 'view-any'],
            'locations.' => [Location::class, 'view-any'],
            'passengers.' => [Passenger::class, 'view-any'],
            'permissions.' => [Permission::class, 'list'],
            'planes.' => [Plane::class, 'view-any'],
            'roles.' => [Role::class, 'list'],
            'scope-contexts.' => [ScopeContext::class, 'viewAny'],
            'management-scopes.' => [ScopePolicy::class, 'viewAny'],
            'time-sheets.' => [TimeSheet::class, 'view-any'],
            'users.' => [User::class, 'view-any'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function permissionRouteMap(): array
    {
        return [
            'residences.' => 'list residences',
            'rooms.' => 'list rooms',
            'stocks.' => 'list stocks',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createItem(array $data, ?NavigationItem $parent = null): NavigationItem
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        if (($data['type'] ?? NavigationItem::TYPE_LINK) === NavigationItem::TYPE_LINK && isset($data['route_name'])) {
            if (! $this->routes->canRender((string) $data['route_name'], $data['route_parameters'] ?? null)) {
                $data['is_active'] = false;
            }
        }

        $item = NavigationItem::query()->create([
            ...$data,
            'parent_id' => $parent?->id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        foreach ($children as $child) {
            $this->createItem($child, $item);
        }

        return $item;
    }
}
