<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     */
    public function index(): View
    {
        $users = User::orderBy('username')->get();
        $superAdminCount = User::where('role', 'super_admin')->count();

        return view('users.index', compact('users', 'superAdminCount'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $isLastSuperAdmin = $user->role === 'super_admin'
            && User::where('role', 'super_admin')->count() <= 1;

        return view('users.edit', compact('user', 'isLastSuperAdmin'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        // Prevent changing the last Super_Admin's role to Admin
        if ($user->role === 'super_admin' && $validated['role'] === 'admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Cannot change the role of the last Super Admin. At least one Super Admin must exist.');
            }
        }

        $data = [
            'username' => $validated['username'],
            'role' => $validated['role'],
        ];

        // Only update password if provided
        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deletion of own account
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deletion of the last Super_Admin
        if ($user->role === 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()->route('users.index')
                    ->with('error', 'Cannot delete the last Super Admin account. At least one Super Admin must exist.');
            }
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
