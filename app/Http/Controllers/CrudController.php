<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The behaviour every scaffolded resource screen shares.
 *
 * Nine controllers held the same hundred lines: authorize, search, paginate, render,
 * flash, redirect. They are the same because the screens are the same, so the rules
 * live here once — change how a resource is paginated or where a save redirects to,
 * and every resource follows.
 *
 * Subclasses still declare the four route-bound actions themselves. Implicit route
 * model binding and form request validation both work off the concrete type hints in
 * the signature, and both are worth more than the five lines they cost.
 *
 * @template TModel of Model
 */
abstract class CrudController extends Controller
{
    /**
     * @var class-string<TModel>
     */
    protected string $model;

    protected int $perPage = 5;

    public function index(Request $request): View
    {
        $this->authorize('view-any', $this->model);

        $search = (string) $request->get('search', '');

        $records = $this->indexQuery($this->newQuery()->search($search))
            ->paginate($this->perPage)
            ->withQueryString();

        return view($this->view('index'), [
            $this->plural() => $records,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', $this->model);

        return view($this->view('create'), $this->formOptions());
    }

    protected function storeModel(FormRequest $request): RedirectResponse
    {
        $this->authorize('create', $this->model);

        $record = $this->model::create($request->validated());
        $this->afterSave($record, $request);

        return $this->redirectToEdit($record, __('crud.common.created'));
    }

    /**
     * @param  TModel  $record
     */
    protected function showModel(Model $record): View
    {
        $this->authorize('view', $record);

        return view($this->view('show'), [$this->singular() => $record]);
    }

    /**
     * @param  TModel  $record
     */
    protected function editModel(Model $record): View
    {
        $this->authorize('update', $record);

        return view($this->view('edit'), [$this->singular() => $record] + $this->formOptions($record));
    }

    /**
     * @param  TModel  $record
     */
    protected function updateModel(FormRequest $request, Model $record): RedirectResponse
    {
        $this->authorize('update', $record);

        $record->update($request->validated());
        $this->afterSave($record, $request);

        return $this->redirectToEdit($record, __('crud.common.saved'));
    }

    /**
     * @param  TModel  $record
     */
    protected function destroyModel(Model $record): RedirectResponse
    {
        $this->authorize('delete', $record);

        $record->delete();

        return redirect()
            ->route($this->routeName().'.index')
            ->withSuccess(__('crud.common.removed'));
    }

    /**
     * Narrow or eager-load the listing. The search filter is already applied.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function indexQuery(Builder $query): Builder
    {
        return $query->latest();
    }

    /**
     * Reference data the create and edit forms need — the options in their selects.
     * `$record` is present only when editing.
     *
     * @param  TModel|null  $record
     * @return array<string, mixed>
     */
    protected function formOptions(?Model $record = null): array
    {
        return [];
    }

    /**
     * Anything that has to happen alongside the record itself, such as syncing a
     * pivot table. Runs after both create and update.
     *
     * @param  TModel  $record
     */
    protected function afterSave(Model $record, FormRequest $request): void {}

    /**
     * @param  TModel  $record
     */
    protected function redirectToEdit(Model $record, string $message): RedirectResponse
    {
        return redirect()
            ->route($this->routeName().'.edit', $record)
            ->withSuccess($message);
    }

    /**
     * @return Builder<TModel>
     */
    protected function newQuery(): Builder
    {
        return $this->model::query();
    }

    /**
     * Names are derived from the model, which is what the scaffolding did by hand:
     * `Center` gives the `center` and `centers` view variables, the `app.centers`
     * views, and the `centers.*` routes. A resource that breaks the pattern overrides
     * the one method it breaks.
     */
    protected function singular(): string
    {
        return Str::snake(class_basename($this->model));
    }

    protected function plural(): string
    {
        return Str::plural($this->singular());
    }

    protected function routeName(): string
    {
        return $this->plural();
    }

    protected function view(string $action): string
    {
        return 'app.'.$this->plural().'.'.$action;
    }
}
