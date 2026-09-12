<?php

namespace App\Livewire;

use App\Models\NavigationItem;
use App\Services\Navigation\NavigationRouteRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class NavigationBuilder extends Component
{
    public ?int $editingId = null;

    public ?int $parentId = null;

    public string $type = NavigationItem::TYPE_LINK;

    public ?string $label = null;

    public ?string $labelKey = null;

    public ?string $icon = null;

    public ?string $routeName = null;

    public string $authorizationType = NavigationItem::AUTH_NONE;

    public ?string $permissionName = null;

    public ?string $gate = null;

    public ?string $policyAbility = null;

    public ?string $policyModel = null;

    public bool $isActive = true;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('manage-navigation');
        $this->resetForm();
    }

    public function render(NavigationRouteRegistry $routes): View
    {
        return view('livewire.navigation-builder', [
            'items' => $this->items(),
            'parentOptions' => $this->parentOptions(),
            'availableRoutes' => $this->availableRoutes($routes),
            'authorizationTypes' => [
                NavigationItem::AUTH_NONE => 'Always visible',
                NavigationItem::AUTH_PERMISSION => 'Permission name',
                NavigationItem::AUTH_GATE => 'Gate',
                NavigationItem::AUTH_POLICY => 'Policy ability',
            ],
            'types' => [
                NavigationItem::TYPE_LINK => 'Link',
                NavigationItem::TYPE_GROUP => 'Empty link / group',
                NavigationItem::TYPE_HEADER => 'Dropdown header',
                NavigationItem::TYPE_DIVIDER => 'Dropdown divider',
            ],
        ]);
    }

    public function create(): void
    {
        Gate::authorize('manage-navigation');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        Gate::authorize('manage-navigation');

        $item = NavigationItem::query()->findOrFail($id);

        $this->editingId = $item->id;
        $this->parentId = $item->parent_id;
        $this->type = $item->type;
        $this->label = $item->label;
        $this->labelKey = $item->label_key;
        $this->icon = $item->icon;
        $this->routeName = $item->route_name;
        $this->authorizationType = $item->authorization_type;
        $this->permissionName = $item->permission_name;
        $this->gate = $item->gate;
        $this->policyAbility = $item->policy_ability;
        $this->policyModel = $item->policy_model;
        $this->isActive = $item->is_active;
    }

    public function save(NavigationRouteRegistry $routes): void
    {
        Gate::authorize('manage-navigation');

        $data = $this->validate($this->rules());

        if ($data['type'] === NavigationItem::TYPE_LINK && ! $routes->canRender((string) $data['routeName'])) {
            $this->addError('routeName', 'Choose a named GET route that does not require URL parameters.');

            return;
        }

        $item = $this->editingId ? NavigationItem::query()->findOrFail($this->editingId) : new NavigationItem;
        $item->fill([
            'parent_id' => $data['parentId'],
            'type' => $data['type'],
            'label' => $data['label'],
            'label_key' => $data['labelKey'],
            'icon' => $data['icon'],
            'route_name' => $data['type'] === NavigationItem::TYPE_LINK ? $data['routeName'] : null,
            'route_parameters' => null,
            'authorization_type' => $data['authorizationType'],
            'permission_name' => $data['authorizationType'] === NavigationItem::AUTH_PERMISSION ? $data['permissionName'] : null,
            'gate' => $data['authorizationType'] === NavigationItem::AUTH_GATE ? $data['gate'] : null,
            'policy_ability' => $data['authorizationType'] === NavigationItem::AUTH_POLICY ? $data['policyAbility'] : null,
            'policy_model' => $data['authorizationType'] === NavigationItem::AUTH_POLICY ? $data['policyModel'] : null,
            'is_active' => $data['isActive'],
        ]);

        if (! $item->exists) {
            $item->sort_order = $this->nextSortOrder($data['parentId']);
        }

        $item->save();
        $this->resetForm();
        session()->flash('success', 'Navigation item saved.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-navigation');

        NavigationItem::query()->findOrFail($id)->delete();
        $this->resetForm();
    }

    public function toggle(int $id): void
    {
        Gate::authorize('manage-navigation');

        $item = NavigationItem::query()->findOrFail($id);
        $item->forceFill(['is_active' => ! $item->is_active])->save();
    }

    public function moveBefore(int $draggedId, int $targetId): void
    {
        Gate::authorize('manage-navigation');

        if ($draggedId === $targetId) {
            return;
        }

        $dragged = NavigationItem::query()->findOrFail($draggedId);
        $target = NavigationItem::query()->findOrFail($targetId);

        $dragged->forceFill(['parent_id' => $target->parent_id])->save();

        $siblings = NavigationItem::query()
            ->where('parent_id', $target->parent_id)
            ->ordered()
            ->get()
            ->reject(fn (NavigationItem $item): bool => $item->id === $dragged->id)
            ->values();

        $ordered = collect();
        foreach ($siblings as $sibling) {
            if ($sibling->id === $target->id) {
                $ordered->push($dragged);
            }
            $ordered->push($sibling);
        }

        $this->persistOrder($ordered);
    }

    public function moveInto(int $id, int $parentId): void
    {
        Gate::authorize('manage-navigation');

        if ($id === $parentId) {
            return;
        }

        $item = NavigationItem::query()->findOrFail($id);
        $parent = NavigationItem::query()->findOrFail($parentId);

        if (! $parent->isGroup()) {
            return;
        }

        $item->forceFill([
            'parent_id' => $parent->id,
            'sort_order' => $this->nextSortOrder($parent->id),
        ])->save();
    }

    public function moveToRoot(int $id): void
    {
        Gate::authorize('manage-navigation');

        $item = NavigationItem::query()->findOrFail($id);
        $item->forceFill([
            'parent_id' => null,
            'sort_order' => $this->nextSortOrder(null),
        ])->save();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->parentId = null;
        $this->type = NavigationItem::TYPE_LINK;
        $this->label = null;
        $this->labelKey = null;
        $this->icon = 'ti ti-circle';
        $this->routeName = null;
        $this->authorizationType = NavigationItem::AUTH_NONE;
        $this->permissionName = null;
        $this->gate = null;
        $this->policyAbility = 'view-any';
        $this->policyModel = null;
        $this->isActive = true;
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'parentId' => ['nullable', 'integer', 'exists:navigation_items,id'],
            'type' => ['required', Rule::in([NavigationItem::TYPE_LINK, NavigationItem::TYPE_GROUP, NavigationItem::TYPE_HEADER, NavigationItem::TYPE_DIVIDER])],
            'label' => ['nullable', 'string', 'max:80', 'required_without:labelKey'],
            'labelKey' => ['nullable', 'string', 'max:120', 'required_without:label'],
            'icon' => ['nullable', 'string', 'max:80', 'regex:/^ti ti-[a-z0-9-]+$/'],
            'routeName' => ['nullable', 'required_if:type,'.NavigationItem::TYPE_LINK, 'string', 'max:120'],
            'authorizationType' => ['required', Rule::in([NavigationItem::AUTH_NONE, NavigationItem::AUTH_PERMISSION, NavigationItem::AUTH_GATE, NavigationItem::AUTH_POLICY])],
            'permissionName' => ['nullable', 'required_if:authorizationType,'.NavigationItem::AUTH_PERMISSION, 'string', 'max:120'],
            'gate' => ['nullable', 'required_if:authorizationType,'.NavigationItem::AUTH_GATE, 'string', 'max:80'],
            'policyAbility' => ['nullable', 'required_if:authorizationType,'.NavigationItem::AUTH_POLICY, 'string', 'max:80'],
            'policyModel' => ['nullable', 'required_if:authorizationType,'.NavigationItem::AUTH_POLICY, 'string', 'max:160'],
            'isActive' => ['boolean'],
        ];
    }

    /**
     * @return Collection<int, NavigationItem>
     */
    private function items(): Collection
    {
        return NavigationItem::query()
            ->with(['children' => fn ($query) => $query->ordered()])
            ->whereNull('parent_id')
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, NavigationItem>
     */
    private function parentOptions(): Collection
    {
        return NavigationItem::query()
            ->where('type', NavigationItem::TYPE_GROUP)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->ordered()
            ->get();
    }

    /**
     * @return list<array{name: string, uri: string, action: string, middleware: list<string>, parameters: list<string>, navigable: bool, reason: string|null}>
     */
    private function availableRoutes(NavigationRouteRegistry $routes): array
    {
        return collect($routes->namedGetRoutes())
            ->filter(fn (array $route): bool => $route['navigable'])
            ->filter(function (array $route): bool {
                if ($this->search === '') {
                    return true;
                }

                $needle = mb_strtolower($this->search);

                return str_contains(mb_strtolower($route['name']), $needle)
                    || str_contains(mb_strtolower($route['uri']), $needle);
            })
            ->values()
            ->all();
    }

    private function nextSortOrder(?int $parentId): int
    {
        return ((int) NavigationItem::query()->where('parent_id', $parentId)->max('sort_order')) + 10;
    }

    /**
     * @param  Collection<int, NavigationItem>  $ordered
     */
    private function persistOrder(Collection $ordered): void
    {
        $order = 10;

        foreach ($ordered as $item) {
            $item->forceFill(['sort_order' => $order])->save();
            $order += 10;
        }
    }
}
