<?php

namespace App\Services\Navigation;

use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Throwable;

class NavigationMenuBuilder
{
    public function __construct(private readonly NavigationRouteRegistry $routes) {}

    /**
     * @return Collection<int, NavigationItem>
     */
    public function forUser(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        $items = $this->databaseItems();

        if ($items->isEmpty()) {
            $items = $this->fallbackItems();
        }

        return $this->visibleItems($items, $user);
    }

    /**
     * @return Collection<int, NavigationItem>
     */
    private function databaseItems(): Collection
    {
        try {
            return NavigationItem::query()
                ->active()
                ->with(['children' => fn ($query) => $query->active()->ordered()])
                ->whereNull('parent_id')
                ->ordered()
                ->get();
        } catch (QueryException) {
            return collect();
        }
    }

    /**
     * @return Collection<int, NavigationItem>
     */
    private function fallbackItems(): Collection
    {
        return Collection::make(NavigationDefaults::items())
            ->map(fn (array $item): NavigationItem => $this->hydrateDefaultItem($item));
    }

    /**
     * @param  Collection<int, NavigationItem>  $items
     * @return Collection<int, NavigationItem>
     */
    private function visibleItems(Collection $items, User $user): Collection
    {
        return $items
            ->map(function (NavigationItem $item) use ($user): ?NavigationItem {
                $children = $item->relationLoaded('children') ? $item->children : collect();
                $visibleChildren = $this->visibleItems($children, $user);
                $item->setRelation('children', $visibleChildren);

                if ($item->isGroup()) {
                    return $visibleChildren->isNotEmpty() && $this->authorized($item, $user) ? $item : null;
                }

                if ($item->isHeader() || $item->isDivider()) {
                    return $item;
                }

                return $this->isRenderableLink($item) && $this->authorized($item, $user) ? $item : null;
            })
            ->filter()
            ->values()
            ->pipe(fn (Collection $filtered): Collection => $this->removeDanglingDecorators($filtered));
    }

    private function isRenderableLink(NavigationItem $item): bool
    {
        if (! $item->route_name) {
            return false;
        }

        return $this->routes->canRender($item->route_name, $item->route_parameters);
    }

    private function authorized(NavigationItem $item, User $user): bool
    {
        try {
            return match ($item->authorization_type) {
                NavigationItem::AUTH_PERMISSION => $item->permission_name ? $user->checkPermissionTo($item->permission_name) : false,
                NavigationItem::AUTH_GATE => $item->gate ? Gate::forUser($user)->allows($item->gate) : false,
                NavigationItem::AUTH_POLICY => $item->policy_ability && $item->policy_model ? Gate::forUser($user)->allows($item->policy_ability, $item->policy_model) : false,
                default => true,
            };
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  Collection<int, NavigationItem>  $items
     * @return Collection<int, NavigationItem>
     */
    private function removeDanglingDecorators(Collection $items): Collection
    {
        $result = collect();
        $pendingDecorators = collect();

        foreach ($items as $item) {
            if ($item->isHeader() || $item->isDivider()) {
                $pendingDecorators->push($item);

                continue;
            }

            foreach ($pendingDecorators as $pendingDecorator) {
                $result->push($pendingDecorator);
            }

            $pendingDecorators = collect();
            $result->push($item);
        }

        while ($result->isNotEmpty() && ($result->last()->isHeader() || $result->last()->isDivider())) {
            $result->pop();
        }

        return $result->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hydrateDefaultItem(array $data): NavigationItem
    {
        $children = new Collection;

        foreach (($data['children'] ?? []) as $child) {
            $children->push($this->hydrateDefaultItem($child));
        }

        unset($data['children']);

        $item = new NavigationItem($data);
        $item->setRelation('children', $children);

        return $item;
    }
}
