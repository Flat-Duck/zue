<?php

namespace App\Http\Controllers;

use App\Contracts\AuditLoggerContract;
use App\Http\Requests\ArchivedEmployeeImportRequest;
use App\Http\Requests\EmployeeProfileImportRequest;
use App\Http\Requests\EmployeeQuickStoreRequest;
use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;
use App\Imports\ArchivedEmployeesImport;
use App\Imports\EmployeeProfilesImport;
use App\Models\Administration;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function __construct(private readonly AuditLoggerContract $auditLogger) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Employee::class);

        $search = $request->get('search', '');

        $employees = Employee::search($search)
            ->with(['user', 'location', 'department', 'center'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('app.employees.index', compact('employees', 'search'));
    }

    /**
     * Display a listing of the resource.
     */
    public function dir(Request $request): View
    {
        $this->authorize('view-any', Employee::class);

        $employees = Employee::latest()
            ->with(['user', 'location', 'department', 'center', 'details'])
            ->paginate(30)
            ->withQueryString();

        return view('app.employees.directory', compact('employees'));
    }

    /**
     * Display a listing of the resource.
     */
    public function imports(Request $request): View
    {
        $this->authorize('create', Employee::class);
        $this->authorize('update', new Employee);

        // The page exists to run an import, which creates and updates records,
        // so it is gated the same way the import itself is.
        return view('app.employees.imports');
    }

    /**
     * Display a listing of the resource.
     */
    public function importArchivedEmployees(ArchivedEmployeeImportRequest $request): RedirectResponse
    {
        Excel::import(new ArchivedEmployeesImport, $request->file('file'));

        return back()->with('success', 'Employees archived successfully.');
    }

    /**
     * Import the personnel export, keyed on employee number.
     *
     * Existing employees are updated and new ones created, so the same export
     * can be re-imported whenever HR refresh it.
     */
    public function importProfiles(EmployeeProfileImportRequest $request): RedirectResponse
    {
        // Creating and updating personnel records, so both are required.
        $import = new EmployeeProfilesImport;

        Excel::import($import, $request->file('file'));

        $this->auditLogger->record('employees.profiles_imported', [
            'created' => $import->created,
            'updated' => $import->updated,
            'skipped' => $import->skipped,
            'problem_count' => count($import->errors),
        ]);

        $summary = "Import finished: {$import->created} created, {$import->updated} updated";

        if ($import->skipped > 0) {
            $summary .= ", {$import->skipped} skipped";
        }

        $response = back()->with('success', $summary.'.');

        // Surface the values that could not be read rather than failing quietly.
        if ($import->errors !== []) {
            $response->with('import_problems', array_slice($import->errors, 0, 50));
        }

        return $response;
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Options shared by the create and edit forms.
     *
     * The department-to-administration map lets the form narrow the department
     * list without a round trip, since an employee's administration follows its
     * department rather than being stored on the employee.
     *
     * @return array{0: Collection<int, string>, 1: Collection<int, string>, 2: Collection<int, string>, 3: Collection<int, string>, 4: Collection<int, string>, 5: array<int, int|string>}
     */
    private function formOptions(): array
    {
        $departmentRecords = Department::query()->orderBy('name')->get();

        return [
            User::orderBy('name')->pluck('name', 'id'),
            Location::orderBy('name')->pluck('name', 'id'),
            $departmentRecords->pluck('name', 'id'),
            Center::orderBy('name')->pluck('name', 'id'),
            Administration::query()->orderBy('name')->pluck('name', 'id'),
            $departmentRecords->pluck('administration_id', 'id')->map(fn ($id) => (string) $id)->all(),
        ];
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Employee::class);

        [$users, $locations, $departments, $centers, $administrations, $departmentAdministrations] = $this->formOptions();

        return view(
            'app.employees.create',
            compact('users', 'locations', 'departments', 'centers', 'administrations', 'departmentAdministrations')
        );
    }

    /**
     * Show the quick create form for employees.
     */
    public function quickCreate(Request $request): View
    {
        $this->authorize('create', Employee::class);

        $locations = Location::pluck('name', 'id');
        $centers = Center::pluck('name', 'id');

        return view('app.employees.quick-create', compact('locations', 'centers'));
    }

    /**
     * Store a newly created employee from the quick create form.
     */
    public function quickStore(EmployeeQuickStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $validated = $request->validated();

        $departmentId =
            optional(auth()->user()?->employee)->department_id
            ?? Department::query()->value('id');

        if (! $departmentId) {
            return back()
                ->withErrors([
                    'department' => 'No department found. Please create a department first.',
                ])
                ->withInput();
        }

        $employee = Employee::create([
            'english_name' => $validated['english_name'],
            'number' => (int) $validated['number'],
            'start_date' => $validated['employment_date'],
            'location_id' => $validated['location_id'],
            'center_id' => $validated['center_id'],
            'department_id' => $departmentId,
            'transfered_balance' => 0,
        ]);

        return redirect()
            ->route('employees.edit', $employee)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $employee = (new Employee)->saveProfile($request->validated());

        return redirect()
            ->route('employees.edit', $employee)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Employee $employee): View
    {
        $this->authorize('view', $employee);

        return view('app.employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Employee $employee): View
    {
        $this->authorize('update', $employee);

        [$users, $locations, $departments, $centers, $administrations, $departmentAdministrations] = $this->formOptions();

        return view(
            'app.employees.edit',
            compact('employee', 'users', 'locations', 'departments', 'centers', 'administrations', 'departmentAdministrations')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        EmployeeUpdateRequest $request,
        Employee $employee
    ): RedirectResponse {
        $this->authorize('update', $employee);

        $employee->saveProfile($request->validated());

        return redirect()
            ->route('employees.edit', $employee)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        Employee $employee
    ): RedirectResponse {
        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
