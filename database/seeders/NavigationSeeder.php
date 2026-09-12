<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use App\Services\Navigation\NavigationDefaults;
use App\Services\Navigation\NavigationRouteRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationSeeder extends Seeder
{
    public function __construct(private readonly NavigationRouteRegistry $routes) {}

    public function run(): void
    {
        DB::transaction(function (): void {
            NavigationItem::query()->delete();

            foreach (NavigationDefaults::items() as $item) {
                $this->createItem($item);
            }
        });
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
