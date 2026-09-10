<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomStoreRequest;
use App\Http\Requests\RoomUpdateRequest;
use App\Models\Employee;
use App\Models\Residence;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Room>
 */
class RoomController extends CrudController
{
    protected string $model = Room::class;

    protected int $perPage = 50;

    public function store(RoomStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Room $room): View
    {
        return $this->showModel($room);
    }

    public function edit(Request $request, Room $room): View
    {
        return $this->editModel($room);
    }

    public function update(RoomUpdateRequest $request, Room $room): RedirectResponse
    {
        return $this->updateModel($request, $room);
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        return $this->destroyModel($room);
    }

    /**
     * The listing shows who is in each room, and how full it is.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function indexQuery(Builder $query): Builder
    {
        return $query->with(['residence', 'employees'])->withCount('employees');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(?Model $record = null): array
    {
        $options = [
            'residences' => Residence::query()->select(['id', 'type', 'name'])->orderBy('name')->get(),
            'employees' => Employee::pluck('number', 'id'),
        ];

        if ($record !== null) {
            $options['residents'] = $record->employees()->pluck('number', 'id')->toArray();
        }

        return $options;
    }

    /**
     * Occupancy lives on the pivot table, so it is saved alongside the room itself.
     */
    protected function afterSave(Model $record, FormRequest $request): void
    {
        $record->employees()->sync($request->input('employee_id') ?? []);
    }
}
