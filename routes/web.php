<?php

use App\Http\Controllers\DowntimeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SystemConfigController;
use App\Http\Controllers\TelegramTargetController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\ITStaffController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Routes accessible without authentication.
*/

// Redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Telegram webhook endpoint (public, no auth, CSRF exempted in bootstrap/app.php)
Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Guest Only)
|--------------------------------------------------------------------------
| Login routes with rate limiting to prevent brute force attacks.
| Rate limit: 5 attempts per minute per IP address.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Admin + Super_Admin)
|--------------------------------------------------------------------------
| Routes accessible by any authenticated user with at least Admin role.
| The 'role:admin' middleware grants access to both Admin and Super_Admin.
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Logout
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Downtime Details
    Route::get('/downtime', [DowntimeController::class, 'index'])->name('downtime.index');

    // Website Manager - accessible by both Admin and Super_Admin
    Route::resource('sites', SiteController::class)->except(['show']);

    // Category Manager - accessible by both Admin and Super_Admin
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::get('/categories-list', [CategoryController::class, 'list'])->name('categories.list');

    // Profile & Password Change - accessible by both Admin and Super_Admin
    Route::get('/profile', [ProfileController::class, 'showPasswordForm'])->name('profile.index');
    Route::get('/profile/password', [ProfileController::class, 'showPasswordForm'])->name('profile.password');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.update');
});

/*
|--------------------------------------------------------------------------
| Super_Admin Only Routes
|--------------------------------------------------------------------------
| Routes accessible only by users with the Super_Admin role.
| The 'role:super_admin' middleware denies access to Admin users.
*/

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    // User Manager
    Route::resource('users', UserController::class)->except(['show']);

    // IT Staff Manager
    Route::resource('it-staff', ITStaffController::class)
        ->except(['show'])
        ->parameters(['it-staff' => 'itStaff']);

    // Telegram Target Manager
    Route::resource('telegram-targets', TelegramTargetController::class)->except(['show']);

    // System Configuration
    Route::get('/system-config', [SystemConfigController::class, 'index'])->name('system-config.index');
    Route::put('/system-config', [SystemConfigController::class, 'update'])->name('system-config.update');
});
