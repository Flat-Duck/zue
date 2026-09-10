<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(): View
    {
        // $this->authorize('view', Permission::class);
        $user = auth()->user();

        return view('auth.profile')->with('user', $user);
    }

    /**
     * Display the specified resource.
     */
    public function signature(): View
    {
        // $this->authorize('view', Permission::class);
        $user = auth()->user();

        return view('auth.signature')->with('user', $user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request): RedirectResponse
    {
        //  $this->authorize('update', $permission);

        $data = $this->validate($request, [
            'name' => 'required|max:40',
            'roles' => 'array',
        ]);

        if ($request->password) {
            auth()->user()->update(['password' => Hash::make($request->password)]);
        }

        $data = $this->validate($request, [
            'name' => 'max:250',
            'email' => 'unique:users,email,'.auth()->id(),
        ]);

        auth()->user()->update($data);

        return redirect()
            ->route('profile.show')
            ->withSuccess(__('crud.common.saved'));
    }
}
