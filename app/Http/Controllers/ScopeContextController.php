<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScopeContextRequest;
use App\Models\ScopeContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The contexts a management scope can exist for.
 *
 * A context is why somebody needs to see a set of people — time sheets,
 * dispatch, leave — and the list is expected to grow, so it is kept here rather
 * than in code. Three of them are asked for by name in the code itself and are
 * protected accordingly.
 */
class ScopeContextController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ScopeContext::class);

        return view('app.scope_contexts.index', [
            'contexts' => ScopeContext::query()
                ->withCount('policies')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ScopeContext::class);

        return view('app.scope_contexts.create');
    }

    public function store(ScopeContextRequest $request): RedirectResponse
    {
        ScopeContext::query()->create($this->attributes($request->validated()));

        return redirect()->route('scope-contexts.index')->with('success', __('crud.common.created'));
    }

    public function edit(ScopeContext $scopeContext): View
    {
        $this->authorize('update', $scopeContext);

        return view('app.scope_contexts.edit', ['context' => $scopeContext]);
    }

    public function update(ScopeContextRequest $request, ScopeContext $scopeContext): RedirectResponse
    {
        $scopeContext->update($this->attributes($request->validated()));

        return redirect()->route('scope-contexts.index')->with('success', __('crud.common.saved'));
    }

    public function destroy(ScopeContext $scopeContext): RedirectResponse
    {
        $this->authorize('delete', $scopeContext);

        // A super admin passes every policy, so this is checked here as well:
        // the code asks for these three by name and would be left asking.
        if ($scopeContext->isBuiltIn()) {
            return back()->with('error', __('scopes.contexts_built_in_hint'));
        }

        // The foreign key refuses to orphan a scope. Say so, rather than 500.
        if ($scopeContext->policies()->exists()) {
            return back()->with('error', __('scopes.contexts_in_use', ['count' => $scopeContext->policies()->count()]));
        }

        $scopeContext->delete();

        return redirect()->route('scope-contexts.index')->with('success', __('crud.common.deleted'));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return [
            ...array_intersect_key($validated, array_flip(['key', 'name', 'name_ar'])),
            'carves_out_managers' => (bool) ($validated['carves_out_managers'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}
