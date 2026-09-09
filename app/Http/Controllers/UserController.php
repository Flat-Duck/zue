<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Imports\UsersImport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const IMPERSONATOR_ID_SESSION_KEY = 'impersonator_id';

    private const IMPERSONATOR_NAME_SESSION_KEY = 'impersonator_name';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', User::class);

        $search = $request->get('search', '');

        $users = User::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.users.index', compact('users', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $roles = Role::get();

        return view('app.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();
        $validated['number'] = (int) $validated['number'];

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if ($request->hasFile('signature_file')) {
            app(SignatureService::class)->saveSignature($user, $request->file('signature_file'));
        }

        $user->syncRoles($request->roles);

        app(AuditLogger::class)->record('user.created', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames()->all(),
        ]);

        return redirect()
            ->route('users.edit', $user)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user): View
    {
        $this->authorize('view', $user);

        return view('app.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        $roles = Role::get();

        return view('app.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UserUpdateRequest $request,
        User $user
    ): RedirectResponse {
        $this->authorize('update', $user);

        $oldUserId = (int) $user->id;
        $validated = $request->validated();

        if (array_key_exists('number', $validated)) {
            $validated['number'] = (int) $validated['number'];
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if (auth()->check() && (int) auth()->id() === $oldUserId && (int) $user->id !== $oldUserId) {
            Auth::login($user);
            $request->session()->migrate(true);
        }

        if ($request->hasFile('signature_file')) {
            app(SignatureService::class)->saveSignature($user, $request->file('signature_file'));
        }

        $rolesBefore = $user->getRoleNames()->all();

        $user->syncRoles($request->roles);

        $rolesAfter = $user->fresh()->getRoleNames()->all();

        if ($rolesBefore !== $rolesAfter) {
            app(AuditLogger::class)->record('user.roles_changed', [
                'user_id' => $user->id,
                'from' => $rolesBefore,
                'to' => $rolesAfter,
            ]);
        }

        return redirect()
            ->route('users.edit', $user)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->withSuccess(__('crud.common.removed'));
    }

    public function uploadSignature(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $request->validate([
            'signature_file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        app(SignatureService::class)->saveSignature($user, $request->file('signature_file'));

        return redirect()
            ->back()
            ->withSuccess('Signature uploaded successfully');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:51200'],
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file'));
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $message = 'Import failed. Row '.$failures[0]->row().': '.$failures[0]->errors()[0];

            return redirect()->back()->withErrors(['file' => $message]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->withErrors(['file' => 'Error importing file. Check the application logs for details.']);
        }

        return redirect()
            ->route('users.index')
            ->withSuccess('Users imported successfully');
    }

    public function downloadTemplate()
    {
        $this->authorize('create', User::class);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_import_template.csv"',
        ];

        $columns = ['name', 'email', 'number', 'phone'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, ['John Doe', 'john@example.com', '10001', '0912345678']); // Example row
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $actor = auth()->user();

        if (! $actor || ! $actor->hasRole('super-admin')) {
            abort(403);
        }

        if ($actor->id === $user->id) {
            return back()->withErrors(['impersonation' => 'You are already signed in as this user.']);
        }

        $request->session()->put(self::IMPERSONATOR_ID_SESSION_KEY, $actor->id);
        $request->session()->put(self::IMPERSONATOR_NAME_SESSION_KEY, $actor->name);

        Auth::login($user);
        $request->session()->migrate(true);

        return redirect()
            ->route('home')
            ->withSuccess("Signed in as {$user->name}.");
    }

    public function stopImpersonation(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull(self::IMPERSONATOR_ID_SESSION_KEY);
        $request->session()->forget(self::IMPERSONATOR_NAME_SESSION_KEY);

        if (! $impersonatorId) {
            return back()->withErrors(['impersonation' => 'No active impersonation session found.']);
        }

        $impersonator = User::find($impersonatorId);

        if (! $impersonator || ! $impersonator->hasRole('super-admin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['impersonation' => 'Unable to restore the super-admin session.']);
        }

        Auth::login($impersonator);
        $request->session()->migrate(true);

        return redirect()
            ->route('users.index')
            ->withSuccess("Returned to super-admin ({$impersonator->name}).");
    }
}
