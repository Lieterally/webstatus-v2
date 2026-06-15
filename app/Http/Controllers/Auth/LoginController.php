<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $authService
    ) {}

    /**
     * Show the login form.
     */
    public function showLoginForm(Request $request): View
    {
        return view('auth.login', [
            'sessionExpired' => $request->query('expired') === '1',
        ]);
    }

    /**
     * Handle a login attempt.
     *
     * Uses generic error messages - no field-specific hints revealed.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|min:3|max:64',
            'password' => 'required|string|min:8|max:128',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        // Check if account is locked
        if ($this->authService->isAccountLocked($username)) {
            $remaining = $this->authService->getLockoutRemainingMinutes($username);

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => "Your account is temporarily locked. Please try again in {$remaining} minute(s).",
                ]);
        }

        // Attempt authentication
        $user = $this->authService->authenticate($username, $password);

        if (!$user) {
            // Check if account just became locked
            if ($this->authService->isAccountLocked($username)) {
                $remaining = $this->authService->getLockoutRemainingMinutes($username);

                return back()
                    ->withInput($request->only('username'))
                    ->withErrors([
                        'login' => "Your account is temporarily locked. Please try again in {$remaining} minute(s).",
                    ]);
            }

            // Generic error message - don't reveal which field is incorrect
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => 'The provided credentials are incorrect.',
                ]);
        }

        // Log the user in via Laravel's Auth
        Auth::login($user);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}
