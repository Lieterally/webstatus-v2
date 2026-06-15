<?php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthServiceInterface
{
    /**
     * Maximum failed login attempts before account lockout.
     */
    protected const MAX_ATTEMPTS = 5;

    /**
     * Time window (in minutes) within which failed attempts are counted.
     */
    protected const ATTEMPT_WINDOW_MINUTES = 15;

    /**
     * Duration (in minutes) an account is locked after exceeding max attempts.
     */
    protected const LOCKOUT_DURATION_MINUTES = 15;

    /**
     * Authenticate user credentials.
     */
    public function authenticate(string $username, string $password): ?User
    {
        if ($this->isAccountLocked($username)) {
            return null;
        }

        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->recordFailedAttempt($username);
            return null;
        }

        // Clear failed attempts on successful login
        $this->recordSuccessfulAttempt($username);

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        return $user;
    }

    /**
     * Check if account is locked due to too many failed attempts.
     */
    public function isAccountLocked(string $username): bool
    {
        $windowStart = Carbon::now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES);

        $failedAttempts = LoginAttempt::where('username', $username)
            ->where('successful', false)
            ->where('attempted_at', '>=', $windowStart)
            ->count();

        if ($failedAttempts < self::MAX_ATTEMPTS) {
            return false;
        }

        // Check if the lockout period has passed since the last failed attempt
        $lastFailedAttempt = LoginAttempt::where('username', $username)
            ->where('successful', false)
            ->where('attempted_at', '>=', $windowStart)
            ->orderBy('attempted_at', 'desc')
            ->first();

        if (!$lastFailedAttempt) {
            return false;
        }

        // Account is locked if the 5th failed attempt was within the lockout duration
        $fifthAttempt = LoginAttempt::where('username', $username)
            ->where('successful', false)
            ->where('attempted_at', '>=', $windowStart)
            ->orderBy('attempted_at', 'asc')
            ->skip(self::MAX_ATTEMPTS - 1)
            ->first();

        if (!$fifthAttempt) {
            return false;
        }

        $lockoutExpiry = $fifthAttempt->attempted_at->addMinutes(self::LOCKOUT_DURATION_MINUTES);

        return Carbon::now()->lt($lockoutExpiry);
    }

    /**
     * Get the remaining lockout time in minutes.
     */
    public function getLockoutRemainingMinutes(string $username): int
    {
        $windowStart = Carbon::now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES);

        $fifthAttempt = LoginAttempt::where('username', $username)
            ->where('successful', false)
            ->where('attempted_at', '>=', $windowStart)
            ->orderBy('attempted_at', 'asc')
            ->skip(self::MAX_ATTEMPTS - 1)
            ->first();

        if (!$fifthAttempt) {
            return 0;
        }

        $lockoutExpiry = $fifthAttempt->attempted_at->addMinutes(self::LOCKOUT_DURATION_MINUTES);
        $remaining = Carbon::now()->diffInMinutes($lockoutExpiry, false);

        return max(0, (int) ceil($remaining));
    }

    /**
     * Record a failed login attempt.
     */
    public function recordFailedAttempt(string $username): void
    {
        LoginAttempt::create([
            'username' => $username,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'attempted_at' => now(),
            'successful' => false,
        ]);
    }

    /**
     * Record a successful login attempt.
     */
    protected function recordSuccessfulAttempt(string $username): void
    {
        LoginAttempt::create([
            'username' => $username,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'attempted_at' => now(),
            'successful' => true,
        ]);
    }

    /**
     * Change user password.
     *
     * Returns false if:
     * - Current password is incorrect
     * - New password is the same as current password
     * - New password length is outside 8-128 range
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        // Validate new password length
        if (strlen($newPassword) < 8 || strlen($newPassword) > 128) {
            return false;
        }

        // Reject if new password is same as current
        if (Hash::check($newPassword, $user->password)) {
            return false;
        }

        // Hash with bcrypt (uses configured cost factor) and update
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }

    /**
     * Invalidate all sessions for a user except the current one.
     */
    public function invalidateOtherSessions(User $user): void
    {
        $currentSessionId = session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Get the configured session timeout in minutes.
     * Default: 30 minutes, range: 5-480 minutes.
     */
    public function getSessionTimeout(): int
    {
        $timeout = (int) SystemConfig::getValue('session_timeout_minutes', '30');

        // Clamp to valid range
        return max(5, min(480, $timeout));
    }
}
