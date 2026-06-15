<?php

use App\Models\LoginAttempt;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->service = new AuthService();
});

describe('authenticate', function () {
    it('returns user with valid credentials', function () {
        $user = User::factory()->create([
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $result = $this->service->authenticate('admin', 'password123');

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($user->id);
    });

    it('returns null with invalid password', function () {
        User::factory()->create([
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $result = $this->service->authenticate('admin', 'wrongpassword');

        expect($result)->toBeNull();
    });

    it('returns null with non-existent username', function () {
        $result = $this->service->authenticate('nonexistent', 'password123');

        expect($result)->toBeNull();
    });

    it('records failed attempt on invalid credentials', function () {
        User::factory()->create([
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $this->service->authenticate('admin', 'wrongpassword');

        expect(LoginAttempt::where('username', 'admin')->where('successful', false)->count())
            ->toBe(1);
    });

    it('records successful attempt on valid credentials', function () {
        User::factory()->create([
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $this->service->authenticate('admin', 'password123');

        expect(LoginAttempt::where('username', 'admin')->where('successful', true)->count())
            ->toBe(1);
    });

    it('updates last_login_at on successful authentication', function () {
        $user = User::factory()->create([
            'username' => 'admin',
            'password' => 'password123',
            'last_login_at' => null,
        ]);

        $this->service->authenticate('admin', 'password123');

        $user->refresh();
        expect($user->last_login_at)->not->toBeNull();
    });

    it('returns null when account is locked', function () {
        User::factory()->create([
            'username' => 'admin',
            'password' => 'password123',
        ]);

        // Create 5 failed attempts within the time window
        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::create([
                'username' => 'admin',
                'ip_address' => '127.0.0.1',
                'attempted_at' => now()->subSeconds($i),
                'successful' => false,
            ]);
        }

        $result = $this->service->authenticate('admin', 'password123');

        expect($result)->toBeNull();
    });
});

describe('isAccountLocked', function () {
    it('returns false with no failed attempts', function () {
        expect($this->service->isAccountLocked('admin'))->toBeFalse();
    });

    it('returns false with fewer than 5 failed attempts', function () {
        for ($i = 0; $i < 4; $i++) {
            LoginAttempt::create([
                'username' => 'admin',
                'ip_address' => '127.0.0.1',
                'attempted_at' => now(),
                'successful' => false,
            ]);
        }

        expect($this->service->isAccountLocked('admin'))->toBeFalse();
    });

    it('returns true with 5 failed attempts within 15 minutes', function () {
        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::create([
                'username' => 'admin',
                'ip_address' => '127.0.0.1',
                'attempted_at' => now()->subMinutes($i),
                'successful' => false,
            ]);
        }

        expect($this->service->isAccountLocked('admin'))->toBeTrue();
    });

    it('returns false when failed attempts are older than 15 minutes', function () {
        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::create([
                'username' => 'admin',
                'ip_address' => '127.0.0.1',
                'attempted_at' => now()->subMinutes(20 + $i),
                'successful' => false,
            ]);
        }

        expect($this->service->isAccountLocked('admin'))->toBeFalse();
    });

    it('returns false after lockout period expires', function () {
        // Create 5 failed attempts exactly 16 minutes ago (lockout should have expired)
        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::create([
                'username' => 'admin',
                'ip_address' => '127.0.0.1',
                'attempted_at' => now()->subMinutes(16),
                'successful' => false,
            ]);
        }

        // The 5th attempt was 16 minutes ago, lockout is 15 min, so it should have expired
        // But the window itself is 15 minutes, so attempts 16 minutes old are outside the window
        expect($this->service->isAccountLocked('admin'))->toBeFalse();
    });

    it('does not count successful attempts', function () {
        // 4 failed + 1 successful should not trigger lockout
        for ($i = 0; $i < 4; $i++) {
            LoginAttempt::create([
                'username' => 'admin',
                'ip_address' => '127.0.0.1',
                'attempted_at' => now(),
                'successful' => false,
            ]);
        }

        LoginAttempt::create([
            'username' => 'admin',
            'ip_address' => '127.0.0.1',
            'attempted_at' => now(),
            'successful' => true,
        ]);

        expect($this->service->isAccountLocked('admin'))->toBeFalse();
    });
});

describe('recordFailedAttempt', function () {
    it('creates a login attempt record', function () {
        $this->service->recordFailedAttempt('admin');

        $attempt = LoginAttempt::where('username', 'admin')->first();
        expect($attempt)->not->toBeNull()
            ->and($attempt->successful)->toBeFalse()
            ->and($attempt->ip_address)->not->toBeNull();
    });
});

describe('changePassword', function () {
    it('changes password with valid current password', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $result = $this->service->changePassword($user, 'oldpassword', 'newpassword123');

        expect($result)->toBeTrue();
        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    it('rejects incorrect current password', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $result = $this->service->changePassword($user, 'wrongpassword', 'newpassword123');

        expect($result)->toBeFalse();
    });

    it('rejects new password shorter than 8 characters', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $result = $this->service->changePassword($user, 'oldpassword', 'short');

        expect($result)->toBeFalse();
    });

    it('rejects new password longer than 128 characters', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $longPassword = str_repeat('a', 129);
        $result = $this->service->changePassword($user, 'oldpassword', $longPassword);

        expect($result)->toBeFalse();
    });

    it('rejects new password same as current', function () {
        $user = User::factory()->create([
            'password' => 'samepassword',
        ]);

        $result = $this->service->changePassword($user, 'samepassword', 'samepassword');

        expect($result)->toBeFalse();
    });

    it('accepts new password at minimum length (8 chars)', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $result = $this->service->changePassword($user, 'oldpassword', '12345678');

        expect($result)->toBeTrue();
    });

    it('accepts new password at maximum length (128 chars)', function () {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $newPassword = str_repeat('a', 128);
        $result = $this->service->changePassword($user, 'oldpassword', $newPassword);

        expect($result)->toBeTrue();
    });
});

describe('invalidateOtherSessions', function () {
    it('removes all sessions except current', function () {
        $user = User::factory()->create();

        // Get the current session ID that Laravel assigned
        $currentSessionId = session()->getId();

        // Insert some fake sessions in the sessions table, including one matching current
        DB::table('sessions')->insert([
            ['id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'test', 'last_activity' => time()],
            ['id' => 'other_session1', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'test', 'last_activity' => time()],
            ['id' => 'other_session2', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'test', 'last_activity' => time()],
        ]);

        $this->service->invalidateOtherSessions($user);

        $remainingSessions = DB::table('sessions')->where('user_id', $user->id)->get();
        expect($remainingSessions)->toHaveCount(1)
            ->and($remainingSessions->first()->id)->toBe($currentSessionId);
    });

    it('does not remove sessions of other users', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $currentSessionId = session()->getId();

        DB::table('sessions')->insert([
            ['id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'test', 'last_activity' => time()],
            ['id' => 'user_other_session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'test', 'last_activity' => time()],
            ['id' => 'other_user_session', 'user_id' => $otherUser->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'test', 'last_activity' => time()],
        ]);

        $this->service->invalidateOtherSessions($user);

        $otherUserSessions = DB::table('sessions')->where('user_id', $otherUser->id)->count();
        expect($otherUserSessions)->toBe(1);
    });
});

describe('getSessionTimeout', function () {
    it('returns default 30 when no config exists', function () {
        expect($this->service->getSessionTimeout())->toBe(30);
    });

    it('returns configured value from system_configs', function () {
        SystemConfig::create(['key' => 'session_timeout_minutes', 'value' => '60']);

        expect($this->service->getSessionTimeout())->toBe(60);
    });

    it('clamps value to minimum 5 minutes', function () {
        SystemConfig::create(['key' => 'session_timeout_minutes', 'value' => '2']);

        expect($this->service->getSessionTimeout())->toBe(5);
    });

    it('clamps value to maximum 480 minutes', function () {
        SystemConfig::create(['key' => 'session_timeout_minutes', 'value' => '500']);

        expect($this->service->getSessionTimeout())->toBe(480);
    });
});
