# Implementation Plan: Webstatus-V2

## Overview

This plan implements the Webstatus-V2 website monitoring system for Institut Teknologi Kalimantan using Laravel 13, Blade + Livewire + Alpine.js, Tailwind CSS v4, MySQL 8.0, and Chart.js. Tasks are organized to build foundational components first (database, models, services), then layer UI and integrations on top, ensuring each step builds on the previous with no orphaned code.

## Tasks

- [x] 1. Project scaffolding and database foundation
  - [x] 1.1 Create Laravel 13 project structure with required packages
    - Initialize Laravel 13 project with Livewire, Alpine.js, and Tailwind CSS v4
    - Install Chart.js via npm
    - Configure MySQL 8.0 database connection in `.env`
    - Set up Pest PHP testing framework with property test helpers
    - Configure Tailwind CSS v4 with ITK brand color palette (Primary Blue #1565C0, Secondary Gold #F5A623, status colors)
    - Import Figtree font from Google Fonts
    - _Requirements: 1.1, 26.1_

  - [x] 1.2 Create all database migrations
    - Create migrations for: `users`, `login_attempts`, `categories`, `it_staffs`, `sites`, `pages`, `check_results`, `checking_cycles`, `telegram_targets`, `system_configs`, `notification_logs`
    - Define all columns, indexes, foreign keys, and enum types as specified in the data model
    - Create database seeder for `system_configs` with default values (cycle_interval_minutes=10, notification_cycle_threshold=6, false_positive_threshold=3, session_timeout_minutes=30)
    - Create seeder for initial Super_Admin user account
    - _Requirements: 1.1, 10.1, 28.3_

  - [x] 1.3 Create Eloquent models with relationships and casts
    - Implement `User`, `LoginAttempt`, `Category`, `ITStaff`, `Site`, `Page`, `CheckResult`, `CheckingCycle`, `TelegramTarget`, `SystemConfig`, `NotificationLog` models
    - Define all relationships (HasMany, BelongsTo) as per ERD
    - Create `SiteStatus` enum (up, partially_down, totally_down)
    - Create `ErrorType` enum (none, timeout, connection_failure, dns_failure)
    - Create `TriggerType` enum (automatic, manual_all, manual_site)
    - _Requirements: 3.1, 3.2, 3.3, 2.4_

- [x] 2. Core services - Status determination and health checking
  - [x] 2.1 Implement StatusDeterminationService
    - Create `App\Services\StatusDeterminationService` implementing `StatusDeterminationServiceInterface`
    - Implement `determineStatus()`: returns "up" when all pages 2xx/3xx, "partially_down" when some fail, "totally_down" when all fail, "up" when no pages defined
    - Implement `calculateAverageResponseTime()`: sum of reachable page response times / count of reachable pages, returns 0 if all unreachable
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 2.5, 2.6_

  - [x] 2.2 Write property test for status determination (Property 1)
    - **Property 1: Site status determination is correct for any combination of page results**
    - Generate random sets of page results (0 to N pages, each with random HTTP codes or unreachable status)
    - Assert correct status classification for all combinations
    - Minimum 100 iterations
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**

  - [x] 2.3 Write property test for average response time (Property 5)
    - **Property 5: Average response time calculation**
    - Generate random sets of page results with varying response times and reachability
    - Assert average equals sum of reachable response times / count of reachable, or 0 if all unreachable
    - Minimum 100 iterations
    - **Validates: Requirements 2.5, 2.6**

  - [x] 2.4 Implement HealthCheckService
    - Create `App\Services\HealthCheckService` implementing `HealthCheckServiceInterface`
    - Use Laravel HTTP Client pool for concurrent requests
    - Configure connection timeout (10s) and response timeout (15s)
    - Implement overall cycle timeout (10s) - terminate remaining checks and mark as unreachable
    - Return collection of page results with http_code, response_time_ms, error_type
    - Handle DNS failures, connection failures, and timeouts distinctly
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 1.5, 1.6, 1.8_

  - [x] 2.5 Write property test for fault isolation (Property 14)
    - **Property 14: Fault isolation during checking cycles**
    - Generate random sets of sites where some HTTP checks fail
    - Assert all sites are still processed and results recorded regardless of individual failures
    - Minimum 100 iterations
    - **Validates: Requirements 1.8**

- [x] 3. Core services - Notification logic
  - [x] 3.1 Implement NotificationService
    - Create `App\Services\NotificationService` implementing `NotificationServiceInterface`
    - Implement false positive threshold logic: only send notification when `consecutive_down_count` reaches exactly 3
    - Implement repeated notification logic: re-send every `notification_cycle_threshold` cycles counting from initial notification
    - Implement status change notification: send when status changes between partially_down and totally_down (above threshold)
    - Implement recovery notification: send only when transitioning to "up" AND `notification_sent` was true
    - Implement retry logic: 3 attempts with 5-second intervals on failure
    - Format notification messages with site name, status, down page URLs, and duration
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7, 14.1, 14.2, 14.3, 14.4, 14.5, 14.6_

  - [x] 3.2 Write property test for down notification threshold (Property 6)
    - **Property 6: Down notification is sent if and only if consecutive down count reaches threshold**
    - Generate random sequences of cycle results and consecutive_down_count values
    - Assert notification is sent exactly when count reaches 3 for the first time
    - Minimum 100 iterations
    - **Validates: Requirements 13.1, 13.2**

  - [x] 3.3 Write property test for repeated notification timing (Property 7)
    - **Property 7: Repeated down notifications occur at exact multiples of the configured threshold**
    - Generate random threshold values and cycle sequences where site remains down
    - Assert repeated notifications only at exact multiples of threshold from initial notification
    - Minimum 100 iterations
    - **Validates: Requirements 13.4, 13.5**

  - [x] 3.4 Write property test for status change notification (Property 8)
    - **Property 8: Status change between down states triggers updated notification**
    - Generate random sequences of status changes between partially_down and totally_down
    - Assert notification sent only on actual state change, not when status remains the same
    - Minimum 100 iterations
    - **Validates: Requirements 13.6**

  - [x] 3.5 Write property test for recovery notification (Property 9)
    - **Property 9: Recovery notification conditions**
    - Generate random outage/recovery sequences with varying down counts
    - Assert recovery notification only when transitioning to "up" with notification_sent=true
    - Assert no recovery when count was below threshold or when transitioning between down states
    - Minimum 100 iterations
    - **Validates: Requirements 14.1, 14.3, 14.5**

  - [x] 3.6 Write property test for notification cycle threshold validation (Property 4)
    - **Property 4: Notification cycle threshold validation accepts only whole numbers in [1, 100]**
    - Generate random integer and non-integer values across a wide range
    - Assert acceptance only for whole numbers in [1, 100], rejection for all others
    - Minimum 100 iterations
    - **Validates: Requirements 25.2, 25.3**

- [x] 4. Core services - Monitoring orchestration and Telegram bot
  - [x] 4.1 Implement MonitoringService
    - Create `App\Services\MonitoringService` implementing `MonitoringServiceInterface`
    - Implement `executeCycle()`: fetch all sites with pages, call HealthCheckService, determine statuses, persist results, update consecutive_down_count, evaluate notifications, update cycle timestamp
    - Implement `refreshSite()`: check single site, update status, record results without resetting global timer
    - Implement `getCycleState()`: return countdown, last check time, cycle in progress status
    - Implement cycle interval management with validation [5, 1440]
    - Implement consecutive_down_count increment (on down) and reset (on up)
    - Create `CheckingCycle` records for each cycle execution
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.7, 3.5, 3.6, 4.1, 4.3, 5.1, 5.2, 5.3, 5.5, 5.6, 5.7_

  - [x] 4.2 Write property test for consecutive down count (Property 2)
    - **Property 2: Consecutive down count follows increment/reset rules**
    - Generate random sequences of cycle results (up, partially_down, totally_down)
    - Assert count increments by exactly 1 on down, resets to 0 on up
    - Minimum 100 iterations
    - **Validates: Requirements 3.6**

  - [ ]* 4.3 Write property test for cycle interval validation (Property 3)
    - **Property 3: Cycle interval validation accepts only values in [5, 1440]**
    - Generate random integer values across a wide range (negative, zero, boundary, large)
    - Assert acceptance only for values in [5, 1440], rejection for all others
    - Minimum 100 iterations
    - **Validates: Requirements 1.4, 24.2**

  - [x] 4.4 Implement TelegramBotService
    - Create `App\Services\TelegramBotService` implementing `TelegramBotServiceInterface`
    - Implement `handleUpdate()`: parse incoming webhook, route to command handler
    - Implement all 8 commands: /start, /help, /chat_id, /recepient, /subscribe, /unsubscribe, /down, /refresh
    - Implement `sendMessage()`: send message to specific chat_id via Telegram Bot API
    - Implement `broadcastToActiveTargets()`: send to all is_active=1 targets
    - Handle unrecognized commands with help redirect message
    - Implement message splitting for responses exceeding 4096 characters
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 16.1, 16.2, 17.1, 17.2, 17.3, 17.4, 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 19.1, 19.2, 19.3, 20.1, 20.2, 20.3, 20.4, 20.5_

  - [x] 4.5 Write property test for subscribe/unsubscribe round trip (Property 15)
    - **Property 15: Subscribe/unsubscribe round trip**
    - Generate random sequences of subscribe/unsubscribe operations
    - Assert operations are inverses: subscribe→unsubscribe = inactive, unsubscribe→subscribe = active
    - Minimum 100 iterations
    - **Validates: Requirements 18.1, 18.4**

- [x] 5. Checkpoint - Core services complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Authentication and authorization
  - [x] 6.1 Implement AuthService and authentication flow
    - Create `App\Services\AuthService` implementing `AuthServiceInterface`
    - Implement `authenticate()`: verify username/password credentials with bcrypt (cost 12)
    - Implement login rate limiting: lock after 5 failed attempts in 15 minutes for 15 minutes
    - Implement `recordFailedAttempt()` and `isAccountLocked()` using `login_attempts` table
    - Implement session expiration (configurable, default 30 minutes, range 5-480)
    - Implement `changePassword()`: verify current password, enforce min 8/max 128, reject same-as-current
    - Implement `invalidateOtherSessions()`: remove all sessions except current on password change
    - Create login controller with generic error messages (no field-specific hints)
    - Create logout controller with session invalidation
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5, 21.6, 21.7, 21.8, 23.1, 23.2, 23.3, 23.4, 23.5, 23.6, 23.7, 27.2, 27.6_

  - [x] 6.2 Implement role-based access control middleware
    - Create `RoleMiddleware` to enforce Admin vs Super_Admin permissions
    - Admin access: Dashboard, Website_Manager, profile settings
    - Super_Admin access: all resources (Dashboard, Website_Manager, User_Manager, IT_Staff_Manager, Telegram_Manager, profile)
    - Redirect unauthorized access to Dashboard with error message
    - Handle invalid/expired sessions by redirecting to login
    - Enforce permissions on all protected routes
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.5, 22.6, 27.3, 27.4, 27.5_

  - [ ]* 6.3 Write property test for RBAC enforcement (Property 13)
    - **Property 13: Role-based access control enforcement**
    - Generate random combinations of user roles and resource paths
    - Assert access granted only for valid role-resource pairs in the permission matrix
    - Minimum 100 iterations
    - **Validates: Requirements 22.1, 22.2, 22.3**

- [x] 7. Website management CRUD
  - [x] 7.1 Implement Site CRUD controller and validation
    - Create `SiteController` with index, create, store, edit, update, destroy actions
    - Implement validation: name (required, max 100), category (required, exists), base_url (required, starts with http:// or https://, unique), description (optional, max 500), pages (required, at least 1, max 50, each starting with "/"), responsible_person (required, exists)
    - Implement create, update, delete operations with confirmation prompt for deletion
    - Display site list with name, category, base_url, page count, responsible person
    - Ensure new sites are included in next checking cycle, deleted sites excluded
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 9.8, 9.9, 9.10_

  - [ ]* 7.2 Write property test for URL format validation (Property 10)
    - **Property 10: URL format validation**
    - Generate random strings including valid URLs, invalid URLs, empty strings, and edge cases
    - Assert acceptance only for strings beginning with "http://" or "https://"
    - Minimum 100 iterations
    - **Validates: Requirements 9.7**

  - [ ]* 7.3 Write property test for page path validation (Property 11)
    - **Property 11: Page path validation**
    - Generate random strings including valid paths (starting with "/"), invalid paths, and edge cases
    - Assert acceptance only for strings starting with "/"
    - Minimum 100 iterations
    - **Validates: Requirements 9.8**

  - [x] 7.4 Implement Category CRUD
    - Create `CategoryController` with basic CRUD operations
    - Validate name (required, unique)
    - Display category list for site management dropdowns
    - _Requirements: 9.1_

- [x] 8. User, IT Staff, and Telegram target management
  - [x] 8.1 Implement User management (Super_Admin only)
    - Create `UserController` with index, create, store, edit, update, destroy actions
    - Validate: username (3-50 chars, unique), password (8-128 chars), role (admin|super_admin)
    - Prevent deletion of own account and last Super_Admin account
    - Prevent changing last Super_Admin's role to Admin
    - Apply Super_Admin role middleware
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8_

  - [x] 8.2 Implement IT Staff management (Super_Admin only)
    - Create `ITStaffController` with index, create, store, edit, update, destroy actions
    - Validate: name (1-100 chars, required), position (1-100 chars, required)
    - Prevent deletion of staff assigned to sites
    - Apply Super_Admin role middleware
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

  - [x] 8.3 Implement Telegram Target management (Super_Admin only)
    - Create `TelegramTargetController` with index, create, store, edit, update, destroy actions
    - Validate: chat_id (numeric string, max 32 chars, unique), is_active (0 or 1, default 1)
    - Confirmation prompt before deletion
    - Apply Super_Admin role middleware
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7_

- [ ] 9. Checkpoint - Backend logic and CRUD complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Dashboard UI - Layout and summary
  - [x] 10.1 Create application layout with navigation and sidebar
    - Build main Blade layout with solid blue (#1565C0) navigation bar, ITK logo, white text
    - Implement left sidebar navigation for admin sections (Website Manager, User Manager, IT Staff Manager, Telegram Manager)
    - Implement responsive breakpoints (mobile hamburger menu, collapsible tablet sidebar, full desktop sidebar)
    - Add dark blue footer with ITK logo centered
    - Apply Figtree font family throughout
    - _Requirements: 26.1_

  - [x] 10.2 Create login page
    - Build login form with username and password fields
    - Display generic error messages on failed login
    - Display lockout message with remaining time when account is locked
    - Display session expired message when redirected from expired session
    - Include ITK logo on login page
    - _Requirements: 21.1, 21.2, 21.3, 21.7, 21.8_

  - [x] 10.3 Implement Dashboard Livewire component with summary cards
    - Create `App\Livewire\Dashboard` component with 2-second polling
    - Display summary cards: total sites, sites down, sites up, last cycle datetime (YYYY-MM-DD HH:mm:ss)
    - Implement countdown timer with Alpine.js (updates every 1 second)
    - Display "No data yet" when no cycle has completed
    - Show paused timer state during manual refresh
    - Implement "Refresh All" button with loading indicator and disabled state during refresh
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 4.1, 4.2, 4.3_

- [x] 11. Dashboard UI - Website list and detail views
  - [x] 11.1 Implement website list with card and table views
    - Create card view showing site name, status badge (color-coded), average response time
    - Ensure 12 cards per row at 1920px viewport
    - Create table view showing site name, HTTP code, status, avg response time (24h), responsible person
    - Implement view toggle control (card/table), default to card view
    - Sort by status: totally_down first, then partially_down, then up, alphabetical within groups
    - Implement category filter dropdown
    - Display "no websites available" message when list is empty or filter yields no results
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

  - [x] 11.2 Write property test for dashboard sort order (Property 12)
    - **Property 12: Dashboard site list sort order**
    - Generate random lists of sites with varying statuses and names
    - Assert sorted order: totally_down first, partially_down second, up third, alphabetical within groups
    - Minimum 100 iterations
    - **Validates: Requirements 7.5**

  - [x] 11.3 Implement detailed site view with charts
    - Display all pages with individual HTTP codes and response times
    - Implement response time chart (Chart.js): Y-axis seconds, X-axis last 24 hours in 1-hour intervals
    - Implement downtime chart (Chart.js): Y-axis hours per day, X-axis last 30 days
    - Display first down datetime and duration ("Xd Xh Xm") for down sites
    - Include per-site "Refresh" button with loading indicator
    - Handle partial data (< 24h history) and no downtime history gracefully
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 5.1, 5.4_

- [x] 12. Admin management UI pages
  - [x] 12.1 Create Website Manager Blade views
    - Build site list page with table (name, category, base URL, page count, responsible person)
    - Build create/edit forms with dynamic page path inputs (add/remove)
    - Implement confirmation modal for site deletion
    - Display validation errors with preserved form values
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.9_

  - [x] 12.2 Create User Manager Blade views (Super_Admin only)
    - Build user list page with username and role columns
    - Build create/edit forms with role dropdown
    - Implement deletion protection (own account, last Super_Admin)
    - Display validation errors for duplicate username, invalid password length
    - _Requirements: 10.1, 10.2, 10.3, 10.7, 10.8_

  - [x] 12.3 Create IT Staff Manager Blade views (Super_Admin only)
    - Build staff list page with name and position columns
    - Build create/edit forms
    - Display error when attempting to delete staff assigned to sites
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.6, 11.7_

  - [x] 12.4 Create Telegram Target Manager Blade views (Super_Admin only)
    - Build target list page with chat_id and is_active status columns
    - Build create/edit forms with is_active toggle
    - Implement confirmation modal for deletion
    - Display error for duplicate chat_id
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

  - [x] 12.5 Create Profile/Password Change page
    - Build password change form with current password, new password, confirm password fields
    - Display specific validation errors (incorrect current, mismatch, same-as-current, length)
    - Show success confirmation message
    - _Requirements: 23.1, 23.2, 23.3, 23.4, 23.5, 23.6, 23.7_

  - [x] 12.6 Create System Configuration page (Super_Admin only)
    - Build settings form for cycle interval (5-1440 minutes) and notification cycle threshold (1-100)
    - Display current values and validation errors
    - Apply changes on save
    - _Requirements: 24.1, 24.2, 24.3, 24.4, 24.5, 25.1, 25.2, 25.3, 25.4, 25.5_

- [ ] 13. Checkpoint - UI complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Background jobs, scheduling, and Telegram webhook
  - [x] 14.1 Implement Laravel Scheduler for monitoring cycles
    - Register scheduled command to execute monitoring cycle at configured interval
    - Ensure scheduler triggers within 60 seconds of system startup (first cycle)
    - Implement cycle countdown timer state persistence to database
    - Implement system health alert: send Telegram notification after 3 consecutive cycle failures
    - Log failed cycles with timestamp and cycle identifier
    - _Requirements: 1.1, 1.2, 1.3, 1.7, 28.1, 28.2, 28.3, 28.4, 28.5_

  - [x] 14.2 Implement Telegram webhook controller and routing
    - Create `TelegramWebhookController` to receive and process incoming updates
    - Set up route for webhook endpoint (POST /telegram/webhook)
    - Validate incoming requests from Telegram
    - Route updates to `TelegramBotService::handleUpdate()`
    - Configure webhook URL via artisan command
    - _Requirements: 15.1, 15.2, 16.1, 17.1, 20.1_

  - [x] 14.3 Implement notification queue jobs
    - Create `SendTelegramNotificationJob` for async notification delivery
    - Implement retry logic (3 attempts, 5-second intervals) within the job
    - Handle message splitting for messages exceeding 4096 characters
    - Log notification delivery results to `notification_logs` table
    - Use database queue driver for reliability
    - _Requirements: 13.7, 14.6, 19.1, 28.3_

- [x] 15. Integration wiring and route definitions
  - [x] 15.1 Define all application routes with middleware
    - Define public routes: login, Telegram webhook
    - Define authenticated routes: dashboard, profile, password change
    - Define Admin routes: website manager, category manager
    - Define Super_Admin routes: user manager, IT staff manager, Telegram target manager, system config
    - Apply auth middleware, role middleware, and rate limiting middleware
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 27.3, 27.4, 27.5_

  - [x] 15.2 Wire all services together with dependency injection
    - Register all service interfaces and implementations in `AppServiceProvider`
    - Bind `MonitoringServiceInterface`, `HealthCheckServiceInterface`, `StatusDeterminationServiceInterface`, `NotificationServiceInterface`, `TelegramBotServiceInterface`, `AuthServiceInterface`
    - Ensure proper dependency injection in controllers and Livewire components
    - _Requirements: 1.1, 4.1, 5.1_

  - [ ]* 15.3 Write feature tests for authentication flow
    - Test login with valid credentials redirects to dashboard
    - Test login with invalid credentials shows generic error
    - Test account lockout after 5 failed attempts
    - Test session expiry redirects to login
    - Test logout invalidates session
    - _Requirements: 21.1, 21.2, 21.3, 21.5, 21.6, 21.7_

  - [ ]* 15.4 Write feature tests for Telegram webhook commands
    - Test all 8 commands with valid inputs
    - Test unrecognized commands return help message
    - Test /recepient with already-registered chat_id
    - Test /subscribe and /unsubscribe for registered and unregistered users
    - Test /refresh while cycle already in progress
    - Test /down with no down sites and with down sites
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 16.1, 17.1, 17.2, 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 19.1, 19.2, 20.1, 20.4_

  - [ ]* 15.5 Write feature tests for manual refresh
    - Test "Refresh All" triggers full cycle and resets timer
    - Test individual site refresh updates only that site and doesn't reset timer
    - Test duplicate refresh request is ignored
    - _Requirements: 4.1, 4.2, 4.3, 4.5, 5.1, 5.3, 5.5_

- [ ] 16. Final checkpoint - Full integration complete
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document (15 properties total)
- Unit tests validate specific examples and edge cases
- The system uses PHP with Laravel 13 — all code examples and implementations use PHP
- Pest PHP is used as the testing framework with a custom `forAll` helper for property-based testing
- Livewire polling (2s) is used for real-time dashboard updates instead of WebSockets

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2"] },
    { "id": 2, "tasks": ["1.3"] },
    { "id": 3, "tasks": ["2.1", "2.4"] },
    { "id": 4, "tasks": ["2.2", "2.3", "2.5", "3.1"] },
    { "id": 5, "tasks": ["3.2", "3.3", "3.4", "3.5", "3.6", "4.1"] },
    { "id": 6, "tasks": ["4.2", "4.3", "4.4"] },
    { "id": 7, "tasks": ["4.5", "6.1"] },
    { "id": 8, "tasks": ["6.2", "6.3"] },
    { "id": 9, "tasks": ["7.1", "7.4", "8.1", "8.2", "8.3"] },
    { "id": 10, "tasks": ["7.2", "7.3"] },
    { "id": 11, "tasks": ["10.1", "10.2"] },
    { "id": 12, "tasks": ["10.3"] },
    { "id": 13, "tasks": ["11.1", "11.3"] },
    { "id": 14, "tasks": ["11.2", "12.1", "12.2", "12.3", "12.4", "12.5", "12.6"] },
    { "id": 15, "tasks": ["14.1", "14.2", "14.3"] },
    { "id": 16, "tasks": ["15.1", "15.2"] },
    { "id": 17, "tasks": ["15.3", "15.4", "15.5"] }
  ]
}
```
