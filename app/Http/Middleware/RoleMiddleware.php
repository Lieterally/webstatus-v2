<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Verifies the authenticated user has the required role.
     * Accepts a role parameter specifying the minimum required role.
     *
     * Usage in routes:
     *   ->middleware('role:super_admin') — Only super_admin users
     *   ->middleware('role:admin')       — Both admin and super_admin users
     *
     * @param string $role The minimum required role ('admin' or 'super_admin')
     */
    public function handle(Request $request, Closure $next, string $role = 'admin'): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect('/login?expired=1');
        }

        $user = Auth::user();

        // Verify the user has a valid role
        if (!$user || !in_array($user->role, ['admin', 'super_admin'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login?expired=1');
        }

        // Super_Admin has access to everything
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Admin role: check if the required role is 'super_admin'
        // If super_admin is required but user is only admin, deny access
        if ($role === 'super_admin') {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this resource.');
        }

        // Admin accessing admin-level resources — allowed
        return $next($request);
    }
}
