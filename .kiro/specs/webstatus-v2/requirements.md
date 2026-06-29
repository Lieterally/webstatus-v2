# Requirements Document

## Introduction

Webstatus-V2 is a website monitoring system for Kalimantan Institute of Technology (Institut Teknologi Kalimantan). The system continuously monitors the availability of institutional websites by performing HTTP health checks on defined pages at configurable intervals. It determines site availability status (up, partially down, or totally down), displays real-time monitoring data on a dashboard, and sends Telegram notifications to IT staff when outages are detected. The system operates 24/7 in the background, enabling proactive incident response rather than relying on reactive user reports.

## Glossary

- **Monitoring_System**: The background service that performs periodic HTTP health checks on defined website pages and determines availability status
- **Dashboard**: The main web interface displaying real-time monitoring data including site statuses, response times, and historical graphs
- **Website_Manager**: The CRUD interface for managing monitored sites, their pages, categories, and responsible persons
- **Category_Manager**: The CRUD interface for managing site categories, accessible by both Admin and Super_Admin roles
- **User_Manager**: The administrative interface for managing user accounts and roles
- **IT_Staff_Manager**: The administrative interface for managing IT staff records
- **Telegram_Manager**: The administrative interface for managing Telegram notification targets
- **Notification_Service**: The service responsible for sending Telegram notifications when site status changes are detected
- **Telegram_Bot**: The Telegram bot interface that responds to user commands and delivers notifications
- **Auth_System**: The authentication and authorization system managing user sessions and role-based access
- **Checking_Cycle**: A single complete execution of HTTP health checks across all monitored sites
- **Cycle_Interval**: The configurable time period between checking cycles (minimum 5 minutes)
- **Consecutive_Down_Count**: The number of consecutive checking cycles a site has been detected as down or partially down
- **Site**: A monitored website consisting of a base URL and one or more defined pages
- **Page**: A specific URL path within a site that is checked during each cycle (e.g., "/", "/profile")
- **Notification_Cycle_Threshold**: The configurable number of cycles between repeated down notifications (e.g., every 6 cycles)
- **False_Positive_Threshold**: The number of consecutive down detections required before sending a notification (fixed at 3 cycles)
- **Admin**: A user role with access to dashboard, website management, and profile settings
- **Super_Admin**: A user role with full system access including user, IT staff, and Telegram target management
- **Telegram_Target**: A registered Telegram chat_id that receives system notifications

## Requirements

### Requirement 1: Background Monitoring Cycle Execution

**User Story:** As an IT staff member, I want the system to automatically check all websites on a recurring cycle, so that outages are detected without manual intervention.

#### Acceptance Criteria

1. THE Monitoring_System SHALL execute a checking cycle at the configured cycle interval continuously in the background, beginning the first cycle within 60 seconds of system startup
2. THE Monitoring_System SHALL continue executing checking cycles regardless of whether any user is logged into the web interface
3. WHEN a checking cycle completes, THE Monitoring_System SHALL reset the countdown timer to the configured cycle interval
4. IF a user attempts to configure a cycle interval of less than 5 minutes or greater than 1440 minutes, THEN THE Monitoring_System SHALL reject the value and retain the previously configured interval
5. THE Monitoring_System SHALL use a cache-based lock with a 5-minute TTL to prevent concurrent cycle execution
6. IF an individual HTTP check fails due to network error or timeout, THEN THE Monitoring_System SHALL continue processing the remaining sites in that cycle without interruption
7. THE Monitoring_System SHALL record the datetime of each completed checking cycle in the `last_cycle_completed_at` and `last_cycle_run_at` system configuration keys
8. THE Monitoring_System SHALL support two execution modes: scheduled (via Laravel scheduler/cron) and auto-triggered (via Livewire polling when countdown expires, spawned as a non-blocking background process)

### Requirement 2: HTTP Health Check Execution

**User Story:** As an IT staff member, I want the system to check each defined page of every monitored site, so that I know the exact status of each URL.

#### Acceptance Criteria

1. WHEN a checking cycle executes, THE Monitoring_System SHALL send an HTTP GET request to each defined page of every monitored site with a configurable connection timeout (default 10 seconds, range 1–60) and a configurable response timeout (default 25 seconds, range 5–120)
2. WHEN an HTTP response is received, THE Monitoring_System SHALL record the HTTP response code for that page
3. WHEN an HTTP response is received, THE Monitoring_System SHALL record the response time in milliseconds for that page, measured from the moment the request is sent to the moment the full response headers are received
4. IF a page request exceeds the response timeout or fails to establish a connection within the connection timeout, THEN THE Monitoring_System SHALL record the page as unreachable with a status code of 0 and an error indication distinguishing between timeout, connection failure, and DNS failure
5. WHEN all page requests for a site have completed within a checking cycle, THE Monitoring_System SHALL calculate and store the average response time in milliseconds across all reachable pages for that site
6. IF all pages of a site are unreachable within a checking cycle, THEN THE Monitoring_System SHALL record the site average response time as 0 milliseconds
7. THE Monitoring_System SHALL process HTTP requests in batches limited by a configurable concurrency limit (default 30, range 5–100) to avoid overwhelming OS connections and DNS resolution
8. THE Monitoring_System SHALL disable SSL/TLS certificate verification for all HTTP checks to allow monitoring sites with invalid or self-signed certificates

### Requirement 3: Site Availability Status Determination

**User Story:** As an IT staff member, I want the system to determine whether a site is up, partially down, or totally down, so that I can prioritize incident response.

#### Acceptance Criteria

1. WHEN all defined pages of a site return a successful HTTP response code (2xx or 3xx) within 10 seconds, THE Monitoring_System SHALL set the site status to "up"
2. WHEN some but not all defined pages of a site return a non-successful HTTP response code or are unreachable (connection timeout after 10 seconds, DNS resolution failure, or connection refused), THE Monitoring_System SHALL set the site status to "partially_down"
3. WHEN all defined pages of a site return a non-successful HTTP response code or are unreachable (connection timeout after 10 seconds, DNS resolution failure, or connection refused), THE Monitoring_System SHALL set the site status to "totally_down"
4. IF a site has no defined pages, THEN THE Monitoring_System SHALL set the site status to "up" and skip HTTP checks for that site
5. THE Monitoring_System SHALL update the site status after each checking cycle completes for all pages of that site
6. THE Monitoring_System SHALL maintain a consecutive down count for each site that increments by 1 when status is "partially_down" or "totally_down" and resets to 0 when status is "up"

### Requirement 4: Manual Refresh for All Sites

**User Story:** As an IT staff member, I want to manually trigger a refresh of all sites, so that I can get immediate status updates without waiting for the next cycle.

#### Acceptance Criteria

1. WHEN a user presses the "Refresh All" button, THE Monitoring_System SHALL pause the current countdown timer and initiate a checking cycle for all monitored sites within 1 second of the button press
2. WHILE a manual refresh is in progress, THE Dashboard SHALL display a loading indicator and disable the "Refresh All" button to prevent duplicate requests
3. WHEN the manual refresh cycle completes successfully, THE Monitoring_System SHALL reset the countdown timer to the full configured cycle interval
4. IF the manual refresh cycle fails for one or more sites due to network error or timeout, THEN THE Monitoring_System SHALL display the last known status for the failed sites, indicate which sites failed to refresh, and still reset the countdown timer to the configured cycle interval
5. THE Monitoring_System SHALL process manual refresh results identically to automatic cycle results for status determination and notification logic, including the 3-cycle confirmation rule for down notifications

### Requirement 5: Manual Refresh for Individual Site

**User Story:** As an IT staff member, I want to manually refresh a specific site, so that I can verify a fix without refreshing all sites.

#### Acceptance Criteria

1. WHEN a user presses the "Refresh" button for a specific site, THE Monitoring_System SHALL initiate HTTP checks for all defined pages of that site only within 1 second of the button press
2. WHEN an individual site refresh completes, THE Monitoring_System SHALL update that site's status, response codes, and response times using the same status determination rules as automatic checking cycles
3. WHEN an individual site refresh is triggered, THE Monitoring_System SHALL NOT pause or reset the global countdown timer
4. WHILE an individual site refresh is in progress, THE Dashboard SHALL display a loading indicator on that site's entry
5. IF an individual site refresh is triggered while a refresh for the same site is already in progress, THEN THE Monitoring_System SHALL ignore the duplicate request
6. IF a page request fails during an individual site refresh due to timeout or connection error, THEN THE Monitoring_System SHALL record that page as unreachable and continue checking the remaining pages of that site
7. THE Monitoring_System SHALL include individual site refresh results in that site's consecutive down count calculation identically to automatic cycle results

### Requirement 6: Dashboard Summary Cards

**User Story:** As an IT staff member, I want to see a high-level summary of all monitored sites, so that I can quickly assess overall system health.

#### Acceptance Criteria

1. THE Dashboard SHALL display the total number of monitored sites
2. THE Dashboard SHALL display the total number of sites with status "totally_down" as the "down" count
3. THE Dashboard SHALL display the total number of sites with status "up" or "partially_down" as the "up" count
4. THE Dashboard SHALL display the datetime of the most recent completed checking cycle in "YYYY-MM-DD HH:mm:ss" format using the server's local timezone
5. THE Dashboard SHALL display a countdown timer showing time remaining until the next checking cycle, updating the displayed value every 1 second via Alpine.js client-side countdown
6. WHEN a checking cycle completes, THE Dashboard SHALL update all summary card values within the next 2-second polling interval
7. IF no checking cycle has been completed since system startup, THEN THE Dashboard SHALL display a "No data yet" indicator in place of the last cycle datetime and show counts as zero
8. WHILE a manual refresh is in progress, THE Dashboard SHALL display a loading indicator on the refresh button and prevent duplicate requests

### Requirement 7: Dashboard Website List View

**User Story:** As an IT staff member, I want to see a list of all monitored websites with key metrics, so that I can identify which sites need attention.

#### Acceptance Criteria

1. THE Dashboard SHALL display a list of all monitored websites showing: site name, HTTP response code, availability status (one of "totally_down", "partially_down", or "up"), average response time in milliseconds over the last 24 hours, and responsible person
2. THE Dashboard SHALL provide a table view option and a card view option for the website list, with a toggle control allowing the user to switch between them
3. WHEN the card view is active, THE Dashboard SHALL display a summary card for each site showing site name, availability status, and average response time, sized so that at least 12 cards are visible per row on a 1920px-wide viewport without horizontal scrolling
4. THE Dashboard SHALL default to card view with no category filter applied
5. THE Dashboard SHALL sort the website list by availability status in the following order: "totally_down" first, then "partially_down", then "up", with alphabetical sorting by site name as the secondary sort within each status group
6. WHEN a category filter is applied, THE Dashboard SHALL display only sites belonging to the selected category, maintaining the same status-based sort order defined in criterion 5
7. IF no monitored websites exist or the filtered result is empty, THEN THE Dashboard SHALL display a message indicating that no websites are available for the current view

### Requirement 8: Dashboard Detailed Site View

**User Story:** As an IT staff member, I want to see detailed monitoring data for a specific site, so that I can diagnose issues and track historical performance.

#### Acceptance Criteria

1. WHEN a user selects a specific site, THE Dashboard SHALL display all defined pages for that site with individual HTTP response codes, response times in milliseconds, and error type
2. WHEN a user selects a specific site, THE Dashboard SHALL display a response time graph with Y-axis representing seconds and X-axis showing time intervals based on the selected time filter (default: 1D with hourly intervals)
3. WHEN a user selects a specific site, THE Dashboard SHALL display a downtime graph with Y-axis representing hours of downtime and X-axis showing time intervals based on the selected time filter (default: 7D with daily intervals)
4. WHEN a site has status "totally_down" or "partially_down", THE Dashboard SHALL display the datetime when the down status was first detected
5. WHEN a site has status "totally_down" or "partially_down", THE Dashboard SHALL display the duration the site has been down in the format "Xd Xh Xm" (days, hours, minutes)
6. THE Dashboard SHALL provide a per-site "Refresh" button in the detailed view
7. IF a site has no monitoring data for the selected time range, THEN THE Dashboard SHALL display the chart with null values for missing data points
8. THE Dashboard SHALL display a downtime history log showing outage windows from the last 30 days with start time, end time, affected pages, and duration

### Requirement 9: Website CRUD Management

**User Story:** As an IT staff member, I want to manage the list of monitored websites, so that I can add new sites and update existing configurations.

#### Acceptance Criteria

1. THE Website_Manager SHALL allow creating a new site with the following fields: name (required, max 100 characters), category (required), base URL (required), description (optional, max 500 characters), list of pages (required, at least 1, max 50 pages), and responsible person (required)
2. THE Website_Manager SHALL allow updating any field of an existing monitored site, subject to the same validation rules as creation
3. WHEN a user requests to delete a monitored site, THE Website_Manager SHALL prompt for confirmation before performing the deletion
4. THE Website_Manager SHALL display a list of all monitored sites showing: name, category, base URL, number of defined pages, and responsible person
5. WHEN a new site is created, THE Monitoring_System SHALL include that site in the next checking cycle
6. WHEN a site is deleted, THE Monitoring_System SHALL exclude that site from subsequent checking cycles
7. THE Website_Manager SHALL validate that the base URL is a valid URL format (beginning with http:// or https://) before saving
8. THE Website_Manager SHALL require at least one page to be defined for each site, where each page is a valid relative path starting with "/"
9. IF any required field is empty or any field fails validation, THEN THE Website_Manager SHALL display an error message indicating which field failed validation and SHALL NOT save the record
10. IF a user attempts to create a site with a base URL that already exists in the system, THEN THE Website_Manager SHALL reject the creation and display an error message indicating duplicate URL

### Requirement 10: User Account Management

**User Story:** As a super admin, I want to manage user accounts, so that I can control who has access to the monitoring system.

#### Acceptance Criteria

1. WHILE a user has the Super_Admin role, THE User_Manager SHALL allow creating new user accounts with: username (between 3 and 50 characters), password (between 8 and 128 characters), and role (Admin or Super_Admin)
2. WHILE a user has the Super_Admin role, THE User_Manager SHALL allow updating existing user accounts' username, password, and role
3. WHILE a user has the Super_Admin role, THE User_Manager SHALL allow deleting user accounts except for the currently logged-in user's own account and the last remaining Super_Admin account
4. THE User_Manager SHALL enforce a minimum password length of 8 characters and a maximum password length of 128 characters
5. THE Auth_System SHALL store passwords using a secure one-way hash algorithm
6. THE User_Manager SHALL allow assigning either the Admin or Super_Admin role to each user
7. IF a Super_Admin attempts to create or update a user account with a username that already exists, THEN THE User_Manager SHALL reject the operation and display an error message indicating the username is already taken
8. IF a Super_Admin attempts to change the role of the last remaining Super_Admin account to Admin, THEN THE User_Manager SHALL reject the operation and display an error message indicating at least one Super_Admin must exist

### Requirement 11: IT Staff Management

**User Story:** As a super admin, I want to manage IT staff records, so that I can assign responsible persons to monitored sites.

#### Acceptance Criteria

1. WHILE a user has the Super_Admin role, THE IT_Staff_Manager SHALL allow creating IT staff records with: name (1 to 100 characters, required) and position (1 to 100 characters, required)
2. WHILE a user has the Super_Admin role, THE IT_Staff_Manager SHALL allow updating the name and position of existing IT staff records
3. WHILE a user has the Super_Admin role, THE IT_Staff_Manager SHALL allow deleting IT staff records that are not currently assigned to any monitored site
4. THE IT_Staff_Manager SHALL display a list of all IT staff with their name and position
5. IF a user without the Super_Admin role attempts to access the IT_Staff_Manager, THEN THE System SHALL deny access and display an error message indicating insufficient permissions
6. IF a Super_Admin attempts to delete an IT staff record that is currently assigned to one or more monitored sites, THEN THE IT_Staff_Manager SHALL prevent the deletion and display an error message indicating the staff member is still assigned to a site
7. IF a Super_Admin submits an IT staff record with an empty name or empty position, THEN THE IT_Staff_Manager SHALL reject the submission and display a validation error message indicating the missing field

### Requirement 12: Telegram Target Management

**User Story:** As a super admin, I want to manage Telegram notification recipients, so that the right people receive outage alerts.

#### Acceptance Criteria

1. WHILE a user has the Super_Admin role, THE Telegram_Manager SHALL allow adding a Telegram target by providing a chat_id (numeric string, maximum 32 characters) and an is_active flag (0 or 1, default: 1)
2. IF a Super_Admin attempts to add a chat_id that already exists in the system, THEN THE Telegram_Manager SHALL reject the addition and display an error message indicating the chat_id is already registered
3. WHILE a user has the Super_Admin role, THE Telegram_Manager SHALL allow updating the chat_id and is_active flag of an existing Telegram target
4. WHILE a user has the Super_Admin role, THE Telegram_Manager SHALL allow deleting a Telegram target after displaying a confirmation prompt
5. WHILE a user has the Super_Admin role, THE Telegram_Manager SHALL display a list of all Telegram targets showing their chat_id and is_active status
6. IF a user without the Super_Admin role attempts to access the Telegram_Manager, THEN THE System SHALL deny access and redirect the user to the dashboard
7. THE Notification_Service SHALL only send notifications to Telegram targets where is_active equals 1

### Requirement 13: Down Notification with False Positive Prevention

**User Story:** As an IT staff member, I want to receive Telegram notifications only after a site has been consistently down, so that I am not alerted by transient network issues.

#### Acceptance Criteria

1. WHEN a site's consecutive down count reaches the False_Positive_Threshold (3 cycles) for the first time during an outage, THE Notification_Service SHALL send a down notification to all active Telegram targets
2. IF the consecutive down count for a site is less than the False_Positive_Threshold, THEN THE Notification_Service SHALL NOT send a down notification for that site
3. THE Notification_Service SHALL include in the down notification: the site name, the current availability status (partially_down or totally_down), and the list of specific page URLs that are down
4. WHILE a site remains in "partially_down" or "totally_down" status after the initial notification has been sent, THE Notification_Service SHALL repeat the down notification every Notification_Cycle_Threshold cycles (default: 6 cycles), counting from the cycle when the initial notification was sent
5. THE Notification_Service SHALL count repeated notification intervals starting from the cycle when the first notification was sent
6. IF a site's status changes between "partially_down" and "totally_down" while the consecutive down count is at or above the False_Positive_Threshold, THEN THE Notification_Service SHALL send an updated notification reflecting the new status to all active Telegram targets
7. IF the Notification_Service fails to deliver a notification to a Telegram target, THEN THE Notification_Service SHALL retry delivery up to 3 attempts with a 5-second interval between attempts before skipping that target for the current notification cycle

### Requirement 14: Recovery Notification

**User Story:** As an IT staff member, I want to be notified when a previously down site recovers, so that I know the issue has been resolved.

#### Acceptance Criteria

1. WHEN a site transitions from "totally_down" or "partially_down" to "up" AND the site had previously triggered a down notification, THE Notification_Service SHALL send a recovery notification to all active Telegram targets
2. THE Notification_Service SHALL include in the recovery notification: the site name and the total duration the site was down, calculated from the datetime of the first cycle where the site was detected as down to the datetime of the cycle where status returned to "up"
3. THE Notification_Service SHALL NOT send a recovery notification for sites that recovered before reaching the False_Positive_Threshold
4. WHEN a recovery notification is sent, THE Notification_Service SHALL reset the consecutive down count and notification cycle counter for that site
5. WHEN a site transitions from "totally_down" to "partially_down", THE Notification_Service SHALL NOT send a recovery notification
6. IF the Notification_Service fails to deliver a recovery notification to a Telegram target, THEN THE Notification_Service SHALL retry delivery on the next checking cycle

### Requirement 15: Telegram Bot /start and /help Commands

**User Story:** As a Telegram user, I want to see available bot commands, so that I know how to interact with the monitoring system.

#### Acceptance Criteria

1. WHEN a user sends /start to the Telegram_Bot, THE Telegram_Bot SHALL respond within 3 seconds with a single message containing the bot name, a brief description of the monitoring system purpose, and a list of all available commands
2. WHEN a user sends /help to the Telegram_Bot, THE Telegram_Bot SHALL respond within 3 seconds with a single message containing each available command followed by a one-line description of that command's function
3. THE Telegram_Bot SHALL include the following 8 commands in both /start and /help responses: /start, /help, /chat_id, /recepient, /subscribe, /unsubscribe, /down, /refresh
4. IF a user sends an unrecognized command to the Telegram_Bot, THEN THE Telegram_Bot SHALL respond with a message indicating the command is not recognized and directing the user to send /help to see available commands

### Requirement 16: Telegram Bot /chat_id Command

**User Story:** As a Telegram user, I want to retrieve my chat_id, so that I can provide it to an admin for registration.

#### Acceptance Criteria

1. WHEN a user sends /chat_id to the Telegram_Bot, THE Telegram_Bot SHALL respond within 3 seconds with the user's numeric Telegram chat_id
2. THE Telegram_Bot SHALL process the /chat_id command regardless of whether the user is registered as a Telegram target

### Requirement 17: Telegram Bot /recepient Command

**User Story:** As a Telegram user, I want to register myself as a notification recipient, so that I can receive outage alerts.

#### Acceptance Criteria

1. WHEN a user sends /recepient to the Telegram_Bot AND the user's chat_id is not registered, THE Telegram_Bot SHALL register the chat_id as a new Telegram target with is_active set to 1 and respond within 5 seconds
2. IF a user sends /recepient to the Telegram_Bot AND the user's chat_id is already registered, THEN THE Telegram_Bot SHALL respond with a message indicating the user is already registered
3. WHEN registration is successful, THE Telegram_Bot SHALL respond with a message indicating the user has been successfully registered as a notification recipient
4. IF a user sends /recepient to the Telegram_Bot AND the registration fails due to a system error, THEN THE Telegram_Bot SHALL respond with a message indicating registration could not be completed and SHALL NOT store any partial registration data

### Requirement 18: Telegram Bot /subscribe and /unsubscribe Commands

**User Story:** As a registered Telegram recipient, I want to activate or deactivate notifications, so that I can control when I receive alerts.

#### Acceptance Criteria

1. WHEN a registered user sends /subscribe to the Telegram_Bot, THE Telegram_Bot SHALL set that user's is_active flag to 1 and respond with a confirmation message
2. IF an unregistered user (chat_id not found in telegram targets) sends /subscribe to the Telegram_Bot, THEN THE Telegram_Bot SHALL respond with a message indicating the user must register first using /recepient
3. IF a user sends /subscribe AND is already subscribed (is_active equals 1), THEN THE Telegram_Bot SHALL respond with "You are already subscribed"
4. WHEN a registered user sends /unsubscribe to the Telegram_Bot, THE Telegram_Bot SHALL set that user's is_active flag to 0 and respond with a confirmation message
5. IF an unregistered user (chat_id not found in telegram targets) sends /unsubscribe to the Telegram_Bot, THEN THE Telegram_Bot SHALL respond with a message indicating the user must register first using /recepient
6. IF a user sends /unsubscribe AND is already unsubscribed (is_active equals 0), THEN THE Telegram_Bot SHALL respond with "You are already unsubscribed"

### Requirement 19: Telegram Bot /down Command

**User Story:** As a Telegram user, I want to query the current list of down sites, so that I can check status without accessing the web dashboard.

#### Acceptance Criteria

1. WHEN a user sends /down to the Telegram_Bot AND there are sites with status "totally_down" or "partially_down", THE Telegram_Bot SHALL respond within 10 seconds with a list of down sites, where each entry includes the site name, the status ("totally_down" or "partially_down"), and the URLs of the down pages. IF the response exceeds 4096 characters, THEN THE Telegram_Bot SHALL split the response into multiple messages
2. WHEN a user sends /down to the Telegram_Bot AND all sites have status "up", THE Telegram_Bot SHALL respond with a message indicating all sites are operational
3. IF a user sends /down to the Telegram_Bot AND the system is unable to retrieve site status data, THEN THE Telegram_Bot SHALL respond with a message indicating that status information is temporarily unavailable

### Requirement 20: Telegram Bot /refresh Command

**User Story:** As a Telegram user, I want to trigger a manual refresh from Telegram, so that I can force a status update remotely.

#### Acceptance Criteria

1. WHEN a user sends /refresh to the Telegram_Bot, THE Monitoring_System SHALL execute an immediate checking cycle for all monitored sites
2. WHEN the triggered refresh completes, THE Telegram_Bot SHALL respond with a message containing the total number of monitored sites, the number of sites with status "up", the number of sites with status "partially_down" or "totally_down", and the names of any down sites
3. WHEN a /refresh is triggered, THE Monitoring_System SHALL pause the current countdown timer and reset it to the configured cycle interval after the refresh completes
4. IF a user sends /refresh while a refresh (manual or automatic) is already in progress, THEN THE Telegram_Bot SHALL respond with a message indicating a refresh is already in progress and not initiate a duplicate cycle
5. IF the triggered refresh fails to complete due to an internal error, THEN THE Telegram_Bot SHALL respond with a message indicating the refresh could not be completed

### Requirement 21: Authentication and Session Management

**User Story:** As a user, I want to log in securely, so that I can access the monitoring dashboard.

#### Acceptance Criteria

1. THE Auth_System SHALL authenticate users using username and password credentials, where username is between 3 and 64 characters and password is between 8 and 128 characters
2. WHEN credentials are valid, THE Auth_System SHALL create an authenticated session and redirect the user to the Dashboard
3. IF credentials are invalid, THEN THE Auth_System SHALL display a generic error message indicating that the login failed without revealing which specific field (username or password) is incorrect
4. THE Auth_System SHALL enforce session expiration after a configurable inactivity period with a default of 30 minutes and a configurable range of 5 to 480 minutes
5. WHEN a user logs out, THE Auth_System SHALL invalidate the session immediately and redirect the user to the login page
6. IF a session expires due to inactivity, THEN THE Auth_System SHALL invalidate the session and redirect the user to the login page with a message indicating the session has expired
7. IF a user submits 5 consecutive failed login attempts within a 15-minute window, THEN THE Auth_System SHALL lock the account for 15 minutes and display a message indicating the account is temporarily locked
8. IF username or password fields are submitted empty or exceed maximum length, THEN THE Auth_System SHALL reject the submission and display a validation error indicating which fields are invalid

### Requirement 22: Role-Based Access Control

**User Story:** As a system administrator, I want role-based access enforcement, so that users can only access features appropriate to their role.

#### Acceptance Criteria

1. WHILE a user has the Admin role, THE Auth_System SHALL grant access to: Dashboard, Website_Manager, Category_Manager, and profile settings, and SHALL deny access to: User_Manager, IT_Staff_Manager, Telegram_Manager, and System Configuration
2. WHILE a user has the Super_Admin role, THE Auth_System SHALL grant access to: Dashboard, Website_Manager, Category_Manager, User_Manager, IT_Staff_Manager, Telegram_Manager, System Configuration, and profile settings
3. IF a user attempts to access a resource outside their permitted role, THEN THE Auth_System SHALL deny the request and redirect the user to the Dashboard with a message indicating unauthorized access
4. WHEN a request is made to a protected resource, THE Auth_System SHALL verify the user's role permissions before granting access to that resource
5. IF a user's role is changed by a Super_Admin, THEN THE Auth_System SHALL enforce the updated permissions no later than the user's next request
6. IF the Auth_System cannot determine the user's role during a permission check due to an invalid or expired session, THEN THE Auth_System SHALL deny access and redirect the user to the login page

### Requirement 23: Profile Password Change

**User Story:** As a user, I want to change my password, so that I can maintain account security.

#### Acceptance Criteria

1. THE Auth_System SHALL allow any authenticated user to change their own password
2. WHEN changing password, THE Auth_System SHALL require the current password and a new password with confirmation field
3. IF the current password verification fails, THEN THE Auth_System SHALL reject the password change and display an error message indicating the current password is incorrect
4. IF the new password confirmation does not match the new password, THEN THE Auth_System SHALL reject the password change and display an error message indicating the passwords do not match
5. THE Auth_System SHALL enforce a minimum password length of 8 characters and a maximum length of 128 characters for new passwords
6. IF the new password is identical to the current password, THEN THE Auth_System SHALL reject the change and display an error message indicating the new password must differ from the current password
7. WHEN a password is changed successfully, THE Auth_System SHALL invalidate all other active sessions for that user and display a success confirmation message

### Requirement 24: Cycle Interval Configuration

**User Story:** As a super admin, I want to configure the monitoring cycle interval, so that I can balance between detection speed and system load.

#### Acceptance Criteria

1. WHILE a user has the Super_Admin role, THE Monitoring_System SHALL allow configuring the cycle interval value as a whole number in minutes
2. IF the submitted cycle interval value is less than 5 minutes or greater than 1440 minutes, THEN THE Monitoring_System SHALL reject the value and display an error message indicating the acceptable range
3. WHEN the cycle interval is updated, THE Monitoring_System SHALL apply the new interval starting from the next cycle
4. THE Dashboard SHALL display the currently configured cycle interval in minutes
5. IF a user without the Super_Admin role attempts to access the cycle interval configuration, THEN THE Monitoring_System SHALL deny access and not display the configuration control

### Requirement 25: Notification Repeat Cycle Configuration

**User Story:** As a super admin, I want to configure how often repeated down notifications are sent, so that IT staff are reminded without being overwhelmed.

#### Acceptance Criteria

1. WHILE a user has the Super_Admin role, THE Notification_Service SHALL allow configuring the Notification_Cycle_Threshold value as a whole number representing the number of cycles between repeated down notifications
2. THE Notification_Service SHALL enforce a minimum Notification_Cycle_Threshold of 1 cycle and a maximum of 100 cycles
3. IF a user submits a Notification_Cycle_Threshold value that is less than 1, greater than 100, or not a whole number, THEN THE Notification_Service SHALL reject the value and display an error message indicating the valid range
4. WHEN the threshold is updated, THE Notification_Service SHALL apply the new value to all subsequent notification cycle evaluations, including sites currently in a down state
5. THE Dashboard SHALL display the currently configured Notification_Cycle_Threshold value

### Requirement 26: Page Load Performance

**User Story:** As a user, I want the application to load quickly, so that I can efficiently monitor sites.

#### Acceptance Criteria

1. THE Dashboard SHALL render the page layout and summary cards (with placeholder or cached values) within 2 seconds of navigation, measured as First Contentful Paint under standard network conditions with up to 50 monitored sites
2. THE Dashboard SHALL load and display all site status data within 4 seconds of navigation (including initial render time), measured as the time until all site statuses, response codes, and responsible person fields are visible, with up to 50 monitored sites
3. THE Website_Manager SHALL render the complete site list within 2 seconds of navigation, measured as First Contentful Paint with up to 50 monitored sites
4. IF the Dashboard or Website_Manager fails to load data within the specified time limits, THEN THE system SHALL display the page layout with a visible loading indicator until data retrieval completes or a 10-second timeout is reached

### Requirement 27: Security Requirements

**User Story:** As a system administrator, I want the system to follow security best practices, so that monitoring data and credentials are protected.

#### Acceptance Criteria

1. THE Auth_System SHALL serve all pages and API endpoints over HTTPS only
2. THE Auth_System SHALL store all passwords using bcrypt with a minimum cost factor of 12
3. THE Auth_System SHALL enforce role-based authorization on all API endpoints according to the defined permission matrix
4. IF an unauthenticated request is made to a protected API endpoint, THEN THE Auth_System SHALL return a 401 response without revealing whether the requested resource exists
5. IF an authenticated user requests a resource their role does not permit, THEN THE Auth_System SHALL return a 403 response without revealing the existence or content of the resource
6. THE Auth_System SHALL enforce rate limiting on the login endpoint, allowing a maximum of 5 failed attempts per account within a 15-minute window, after which the account is locked for 15 minutes
7. THE Auth_System SHALL expire user sessions after a configurable period of inactivity (default 30 minutes, range 5–480 minutes via `session_timeout_minutes` system config), requiring re-authentication to continue

### Requirement 28: System Reliability

**User Story:** As an IT staff member, I want the monitoring system to be highly available, so that outages are never missed.

#### Acceptance Criteria

1. THE Monitoring_System SHALL maintain 99.9% uptime availability measured on a rolling 30-day window, where downtime is defined as the system failing to complete 2 or more consecutive checking cycles
2. IF the Monitoring_System fails to complete a checking cycle due to an internal error, THEN THE Monitoring_System SHALL log the error with a timestamp and the affected cycle identifier, and retry on the next scheduled cycle
3. THE Monitoring_System SHALL persist all monitoring state to the database after each checking cycle completes, including the last check timestamp, countdown timer position, per-site check results, and the notification cycle counter, so that state is preserved across system restarts
4. WHEN the Monitoring_System restarts after a failure, THE Monitoring_System SHALL resume the checking cycle from the last persisted state within one cycle interval (minimum 5 minutes) without re-sending notifications that were already sent in a prior cycle
5. IF the Monitoring_System fails to complete 3 consecutive checking cycles, THEN THE Monitoring_System SHALL send a system health alert to all active Telegram notification targets indicating the monitoring system requires attention

### Requirement 29: Category Management

**User Story:** As an IT staff member, I want to manage categories for organizing monitored sites, so that sites can be grouped logically and filtered on the dashboard.

#### Acceptance Criteria

1. WHILE a user has the Admin or Super_Admin role, THE Website_Manager SHALL allow creating a new category with a name field (required, max 255 characters, unique across all categories)
2. WHILE a user has the Admin or Super_Admin role, THE Website_Manager SHALL allow updating the name of an existing category, subject to the same uniqueness constraint
3. WHILE a user has the Admin or Super_Admin role, THE Website_Manager SHALL allow deleting a category only if no monitored sites are currently assigned to that category
4. IF a user attempts to delete a category that has associated sites, THEN THE Website_Manager SHALL reject the deletion and display an error message indicating the category has associated sites
5. THE Website_Manager SHALL display a list of all categories showing their name and the number of sites in each category
6. IF a user attempts to create a category with a name that already exists, THEN THE Website_Manager SHALL reject the creation and display a duplicate name error
7. THE Website_Manager SHALL provide a JSON endpoint listing all categories for use in AJAX dropdowns

### Requirement 30: Automatic Retry of Down Sites

**User Story:** As an IT staff member, I want the system to automatically retry sites that appear down during a cycle, so that transient network glitches do not produce false status readings.

#### Acceptance Criteria

1. WHEN the initial HTTP checks complete and one or more sites have at least one failing page, THE Monitoring_System SHALL immediately re-check all failing sites once before finalizing results
2. THE Monitoring_System SHALL replace the original check results with the retry results for each retried site
3. THE Monitoring_System SHALL append a "--- RETRY CYCLE ---" separator entry to the live log before retry checks begin
4. THE Monitoring_System SHALL NOT retry sites where all pages were successful in the initial check
5. IF the retry check also fails for a site, THE Monitoring_System SHALL use the retry results (not the original) for status determination

### Requirement 31: Dashboard Chart Time Filters

**User Story:** As an IT staff member, I want to view response time and downtime charts with different time ranges, so that I can analyze both short-term and long-term performance trends.

#### Acceptance Criteria

1. THE Dashboard SHALL provide the following time filter options for both overview and per-site charts: 1 Day (1D), 3 Days (3D), 7 Days (7D), 1 Month (1M), 3 Months (3M), 6 Months (6M), and 1 Year (1Y)
2. WHEN the 1D filter is selected, THE Dashboard SHALL display hourly data points (24 data points total)
3. WHEN the 3D filter is selected, THE Dashboard SHALL display 6-hour interval data points (12 data points total)
4. WHEN the 7D filter is selected, THE Dashboard SHALL display daily data points (7 data points total)
5. WHEN the 1M filter is selected, THE Dashboard SHALL display daily data points (30 data points total)
6. WHEN the 3M filter is selected, THE Dashboard SHALL display weekly data points (13 data points total)
7. WHEN the 6M filter is selected, THE Dashboard SHALL display weekly data points (26 data points total)
8. WHEN the 1Y filter is selected, THE Dashboard SHALL display monthly data points (12 data points total)
9. THE Dashboard overview charts SHALL support per-site filtering via a searchable dropdown, where selecting a site shows only that site's data
10. THE Dashboard SHALL cache overview chart data for 60 seconds to reduce query load during 2-second polling

### Requirement 32: Dashboard Site Search and Status Filter

**User Story:** As an IT staff member, I want to search and filter the site list by name and status, so that I can quickly find specific sites in a large monitored set.

#### Acceptance Criteria

1. THE Dashboard SHALL provide a text search field that filters the site list by site name (case-insensitive partial match)
2. THE Dashboard SHALL provide a status filter dropdown with options: All, Up, Partially Down, Totally Down
3. WHEN a search or filter is applied, THE Dashboard SHALL reset pagination to the first page
4. THE Dashboard SHALL paginate the site list with 24 sites per page
5. THE Dashboard SHALL display pagination controls when the total number of filtered sites exceeds 24

### Requirement 33: Downtime History Log

**User Story:** As an IT staff member, I want to see a historical log of outage events for a specific site, so that I can identify recurring issues and their duration.

#### Acceptance Criteria

1. WHEN viewing a site's detail view, THE Dashboard SHALL display a downtime history showing all outage events within the last 30 days
2. THE Dashboard SHALL group consecutive down check results into outage windows showing: start time, end time (or "Ongoing"), affected pages, and total duration
3. IF a site has no outage events in the last 30 days, THEN THE Dashboard SHALL display the downtime history section as empty
4. THE Dashboard SHALL display outage events in reverse chronological order (most recent first)
5. IF an outage is still ongoing at the time of display, THE Dashboard SHALL indicate "Ongoing" in the end time and append "(ongoing)" to the duration
