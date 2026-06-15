<?php

namespace App\Http\Middleware;

use App\Models\SystemConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * Checks if the session has exceeded the configurable inactivity timeout.
     * Default: 30 minutes, range: 5-480 minutes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timeout = $this->getSessionTimeout();
            $lastActivity = $request->session()->get('last_activity');

            if ($lastActivity && (time() - $lastActivity) > ($timeout * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login?expired=1');
            }

            $request->session()->put('last_activity', time());
        }

        return $next($request);
    }

    /**
     * Get session timeout in minutes from system config.
     */
    protected function getSessionTimeout(): int
    {
        $timeout = (int) SystemConfig::getValue('session_timeout_minutes', '30');

        // Clamp to valid range: 5-480 minutes
        return max(5, min(480, $timeout));
    }
}
