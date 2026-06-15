<?php

namespace App\Http\Controllers;

use App\Services\AuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $authService
    ) {}

    /**
     * Show the password change form.
     */
    public function showPasswordForm(): View
    {
        return view('profile.password');
    }

    /**
     * Handle the password change request.
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'max:128'],
            'new_password_confirmation' => ['required', 'string'],
        ]);

        $user = Auth::user();

        // Check if new password confirmation matches
        if ($request->new_password !== $request->new_password_confirmation) {
            return back()->withErrors([
                'new_password_confirmation' => 'The new password confirmation does not match.',
            ])->withInput();
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ])->withInput();
        }

        // Check if new password is the same as current
        if (Hash::check($request->new_password, $user->password)) {
            return back()->withErrors([
                'new_password' => 'The new password must be different from your current password.',
            ])->withInput();
        }

        // Attempt to change the password via AuthService
        $changed = $this->authService->changePassword($user, $request->current_password, $request->new_password);

        if (!$changed) {
            return back()->withErrors([
                'current_password' => 'Unable to change password. Please try again.',
            ])->withInput();
        }

        // Invalidate other sessions
        $this->authService->invalidateOtherSessions($user);

        return redirect()->route('profile.password')->with('success', 'Your password has been changed successfully.');
    }
}
