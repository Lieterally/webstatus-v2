<?php

namespace App\Services;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Authenticate user credentials.
     *
     * Verifies username/password using bcrypt (cost 12).
     * Returns the user if credentials are valid, null otherwise.
     */
    public function authenticate(string $username, string $password): ?User;

    /**
     * Check if account is locked due to too many failed attempts.
     *
     * Account is locked after 5 failed attempts within 15 minutes, for 15 minutes.
     */
    public function isAccountLocked(string $username): bool;

    /**
     * Get the remaining lockout time in minutes.
     */
    public function getLockoutRemainingMinutes(string $username): int;

    /**
     * Record a failed login attempt for the given username.
     */
    public function recordFailedAttempt(string $username): void;

    /**
     * Change user password.
     *
     * Verifies current password, enforces min 8 / max 128 chars,
     * rejects same-as-current password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool;

    /**
     * Invalidate all sessions for a user except the current one.
     */
    public function invalidateOtherSessions(User $user): void;
}
