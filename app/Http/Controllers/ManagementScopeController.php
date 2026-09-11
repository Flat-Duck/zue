<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManagementScopeStoreRequest;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\ScopeContext;
use App\Models\ScopePolicy;
use App\Services\ManagementScopes\ScopeWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagementScopeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ScopePolicy::class);

        $managerId = $request->integer('manager_id') ?: null;
        $contextId = $request->integer('context_id') ?: null;
        $search = trim((string) $request->get('search'));

        $scopes = ScopePolicy::query()
            ->with(['context', 'criteria', 'actors.actorEmployee:id,number,english_name'])
            ->when($managerId, fn ($query) => $query->whereHas(
                'actors',
                fn ($actors) => $actors->where('actor_employee_id', $managerId)
            ))
            ->when($contextId, fn ($query) => $query->where('context_id', $contextId))
            ->when($search !== '', fn ($query) => $query->where(function ($where) use ($search): void {
                $where->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas(
                        'actors.actorEmployee',
                        fn ($employees) => $employees->where('english_name', 'like', '%'.$search.'%')
                    );
            }))
            ->orderByDesc('is_active')
            ->orderBy('context_id')
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('app.management_scopes.index', [
            'managementScopes' => $scopes,
            'managers' => $this->managers(),
            'contexts' => ScopeContext::query()->orderBy('sort_order')->get(),
            'managerId' => $managerId,
            'contextId' => $contextId,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ScopePolicy::class);

        return view('app.management_scopes.create', $this->formData() + [
            'managerId' => $request->integer('manager_id') ?: null,
        ]);
    }

    public function store(ManagementScopeStoreRequest $request, ScopeWriter $writer): RedirectResponse
    {
        $writer->create($request->validated());

        return redirect()
            ->route('management-scopes.index')
            ->with('success', __('crud.common.created'));
    }

    public function edit(ScopePolicy $managementScope): View
    {
        $this->authorize('update', $managementScope);

        return view('app.management_scopes.edit', $this->formData() + [
            'managementScope' => $managementScope->load(['criteria', 'actors']),
        ]);
    }

    public function update(
        ManagementScopeStoreRequest $request,
        ScopePolicy $managementScope,
        ScopeWriter $writer
    ): RedirectResponse {
        $writer->update($managementScope, $request->validated());

        return redirect()
            ->route('management-scopes.index')
            ->with('success', __('crud.common.saved'));
    }

    public function destroy(ScopePolicy $managementScope, ScopeWriter $writer): RedirectResponse
    {
        $this->authorize('delete', $managementScope);

        $writer->delete($managementScope);

        return redirect()
            ->route('management-scopes.index')
            ->with('success', __('crud.common.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'contexts' => ScopeContext::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'managers' => $this->managers(),
            'employees' => $this->managers(),
            'fields' => Location::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'centers' => Center::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    private function managers()
    {
        return Employee::query()
            ->whereNull('archived_at')
            ->orderBy('english_name')
            ->get(['id', 'number', 'english_name']);
    }
}
