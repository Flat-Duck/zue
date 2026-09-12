<div>
    <div class="row g-2 align-items-center mb-3">
        <div class="col">
            <div class="page-pretitle">{{ __('navigation_builder.section') }}</div>
            <h2 class="page-title">{{ __('navigation_builder.title') }}</h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <button type="button" class="btn btn-primary" wire:click="create">
                <i class="ti ti-plus me-1"></i>
                {{ __('navigation_builder.new_item') }}
            </button>
        </div>
    </div>
            @if (session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif

            <div class="row row-cards">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('navigation_builder.get_routes') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="navigation-route-search">{{ __('navigation_builder.search_routes') }}</label>
                                <input id="navigation-route-search" type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="{{ __('navigation_builder.route_search_placeholder') }}">
                            </div>
                            <div class="list-group list-group-flush overflow-auto" style="max-height: 36rem">
                                @forelse ($availableRoutes as $route)
                                    <button type="button" class="list-group-item list-group-item-action" wire:click="$set('routeName', '{{ $route['name'] }}')">
                                        <div class="d-flex justify-content-between gap-3">
                                            <span class="fw-semibold">{{ $route['name'] }}</span>
                                            <span class="badge bg-green-lt">{{ __('navigation_builder.get') }}</span>
                                        </div>
                                        <div class="text-muted small text-truncate">/{{ $route['uri'] }}</div>
                                    </button>
                                @empty
                                    <div class="text-muted small">{{ __('navigation_builder.no_routes') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">{{ $editingId ? __('navigation_builder.edit_item') : __('navigation_builder.create_item') }}</h3>
                        </div>
                        <form wire:submit="save">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="navigation-type">{{ __('navigation_builder.type') }}</label>
                                        <select id="navigation-type" class="form-select" wire:model.live="type">
                                            @foreach ($types as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" for="navigation-parent">{{ __('navigation_builder.parent') }}</label>
                                        <select id="navigation-parent" class="form-select" wire:model="parentId">
                                            <option value="">{{ __('navigation_builder.top_level') }}</option>
                                            @foreach ($parentOptions as $parent)
                                                <option value="{{ $parent->id }}">{{ $parent->title() }}</option>
                                            @endforeach
                                        </select>
                                        @error('parentId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" for="navigation-active">{{ __('navigation_builder.status') }}</label>
                                        <select id="navigation-active" class="form-select" wire:model="isActive">
                                            <option value="1">{{ __('navigation_builder.active') }}</option>
                                            <option value="0">{{ __('navigation_builder.disabled') }}</option>
                                        </select>
                                    </div>

                                    @if ($type !== App\Models\NavigationItem::TYPE_DIVIDER)
                                        <div class="col-md-6">
                                            <label class="form-label" for="navigation-label-key">{{ __('navigation_builder.translation_key') }}</label>
                                            <input id="navigation-label-key" type="text" class="form-control" wire:model="labelKey" placeholder="{{ __('navigation_builder.translation_key_placeholder') }}">
                                            @error('labelKey') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="navigation-label">{{ __('navigation_builder.fallback_label') }}</label>
                                            <input id="navigation-label" type="text" class="form-control" wire:model="label" placeholder="{{ __('navigation_builder.fallback_label_placeholder') }}">
                                            @error('label') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @endif

                                    @if (in_array($type, [App\Models\NavigationItem::TYPE_LINK, App\Models\NavigationItem::TYPE_GROUP], true))
                                        <div class="col-md-6">
                                            <label class="form-label" for="navigation-icon">{{ __('navigation_builder.icon_class') }}</label>
                                            <input id="navigation-icon" type="text" class="form-control" wire:model="icon" placeholder="{{ __('navigation_builder.icon_placeholder') }}">
                                            @error('icon') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @endif

                                    @if ($type === App\Models\NavigationItem::TYPE_LINK)
                                        <div class="col-md-6">
                                            <label class="form-label" for="navigation-route">{{ __('navigation_builder.route') }}</label>
                                            <select id="navigation-route" class="form-select" wire:model="routeName">
                                                <option value="">{{ __('navigation_builder.choose_route') }}</option>
                                                @foreach ($availableRoutes as $route)
                                                    <option value="{{ $route['name'] }}">{{ $route['name'] }} — /{{ $route['uri'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('routeName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @endif

                                    <div class="col-md-4">
                                        <label class="form-label" for="navigation-auth-type">{{ __('navigation_builder.authorization') }}</label>
                                        <select id="navigation-auth-type" class="form-select" wire:model.live="authorizationType">
                                            @foreach ($authorizationTypes as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @if ($authorizationType === App\Models\NavigationItem::AUTH_PERMISSION)
                                        <div class="col-md-8">
                                            <label class="form-label" for="navigation-permission">{{ __('navigation_builder.permission_name') }}</label>
                                            <input id="navigation-permission" type="text" class="form-control" wire:model="permissionName" placeholder="{{ __('navigation_builder.permission_placeholder') }}">
                                            @error('permissionName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @elseif ($authorizationType === App\Models\NavigationItem::AUTH_GATE)
                                        <div class="col-md-8">
                                            <label class="form-label" for="navigation-gate">{{ __('navigation_builder.gate') }}</label>
                                            <input id="navigation-gate" type="text" class="form-control" wire:model="gate" placeholder="{{ __('navigation_builder.gate_placeholder') }}">
                                            @error('gate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @elseif ($authorizationType === App\Models\NavigationItem::AUTH_POLICY)
                                        <div class="col-md-4">
                                            <label class="form-label" for="navigation-policy-ability">{{ __('navigation_builder.policy_ability') }}</label>
                                            <input id="navigation-policy-ability" type="text" class="form-control" wire:model="policyAbility" placeholder="{{ __('navigation_builder.policy_ability_placeholder') }}">
                                            @error('policyAbility') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="navigation-policy-model">{{ __('navigation_builder.policy_model') }}</label>
                                            <input id="navigation-policy-model" type="text" class="form-control" wire:model="policyModel" placeholder="{{ __('navigation_builder.policy_model_placeholder') }}">
                                            @error('policyModel') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="card-footer d-flex gap-2 justify-content-end">
                                <button type="button" class="btn" wire:click="resetForm">{{ __('navigation_builder.cancel') }}</button>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                    <span wire:loading.remove wire:target="save">{{ __('navigation_builder.save') }}</span>
                                    <span wire:loading wire:target="save">{{ __('navigation_builder.saving') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="card" x-data="{ dragged: null }">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('navigation_builder.menu_structure') }}</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">{{ __('navigation_builder.drag_help') }}</p>
                            <div class="list-group">
                                @forelse ($items as $item)
                                    @include('livewire.partials.navigation-builder-item', ['item' => $item, 'level' => 0])
                                @empty
                                    <div class="empty">
                                        <div class="empty-icon"><i class="ti ti-menu-2"></i></div>
                                        <p class="empty-title">{{ __('navigation_builder.empty_title') }}</p>
                                        <p class="empty-subtitle text-muted">{{ __('navigation_builder.empty_subtitle') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>
