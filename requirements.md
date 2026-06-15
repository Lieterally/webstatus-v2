# Product Requirements Document (PRD): Webstatus-V2

## 1. Document Control

| Field   | Value                     |
| ------- | ------------------------- |
| Version | 1.0.0                     |
| Author  | UPA TIK                   |
| Date    | 04 Juni 2026              |
| Status  | Draft                     |

---

# 2. Executive Summary

## Project Overview

Webstatus is a monitoring tool for website status. This tool is mainly used for website status checking (up, partiallu down, or totally down) that works by the system cycles in defined period of time (can manually be defined, minimum 5 minutes) to check each site and that site pages HTTP response code and from that code, decide the availability status of that website. After checking, the countdown timer resets again for the next cycle. this cycle runs in background (even when no user is logged in) so the system always display the concurrent refresh cycle. 

There are 4 main pages, which are dashboard, websites, user, IT staffs, and telegram targets.

The landing page after login is the dashboard, the first section displays cards for total sites, total sites that are down, total sites that are up, and any information that are important. the second section displays list of defined website urls on the landing page, with detail such as the name of the site, HTTP response code, status, average response time, and the responsible person in charge. this section consists of two view, table view or card view for each web (simple card considering there are 50 sites, and these card view can be filtered to see by the categories, the default is showing all but sort the down web first). the third section, is a detailed view for each sites that shows defined url that the system checks, HTTP code for each page, and the response time, graph that shows response time with Y axis as seconds and the X axis as hour, downtime graph with Y axis as hour and X axis as date. there is also information for the datetime of down status and how long the site has been down. note that a site is considered down if every pages (defined page such as "/", "/profile") is all down.
In this page, there are information about the refreshing cycle such as the countdown timer and datetime of latest cycle. User are able to bypass the checking cycle by pressing provided "Refresh" button for all sites and for each site. when this manual refresh is pressed, the current countdown is paused and reset after the manual refresh.

The websites page, is just a crud management for the monitored sites. which shows the list of the site, that has the name, category, url, description, pages, the responsible person in charge. 

The user and it staff page is only accessible by superadmin.  manage user containing username, password, role. manage it staff containing name and position

The telegram target page is only accessible by superadmin, which is to manage the telegram target that the system sends notification to when a website is down or up. this works by entering the chat_id of the user on the telegram webstatus bot.

A detailed information of how the notification system works:
when the checking cycle detects a down site, the system send notification to defined telegram target containing list of the website with the url that are down. and so is when that down website is up. this message keeps being sent to receiver for the defined period of checking cycle, for example each 6 cycle, send the down notif again. To prevent false positives on sending the notif, the notif is send only if the web is down or partially down after 3 cycles

in this bot, there are several commands such as /start, /help (shows available commands), /chat_id (get chat_id), /recepient (register as recepient), subscribe (activate notifcation receive, works by changing is_active to 1), /unsubscribe (deactivate notification receive, works by changing is_active to 0), /down (get the list of sites that are down), /refresh (to trigger manual refresh)

each command should have validation for every condition, for example if user send /recepient but that user is already registered, then the system send message "You are already registered" and so on


### Core Problem

I work in higher education institution. everyday, there are a lot of digital service in form of website that is being used by lecturer, academics, students, and staff. currently, the procedure of handling a down website is mostly reactively by user report, so not proactively. 

### Proposed Solution

there has to be a system that can monitor all the defined website 24/7, and send notif everytime a site is down to IT staff.

### Target Audience

* Primary Users: IT staff
* Secondary Users: IT staff
* Stakeholders: lecturer, units, staffs, students

---

# 3. Goals & Success Metrics

## Business Goals

* Implementing web monitoring system in Kalimantan Institute of Technology
* Improving the digital service through keeping the availability of websites

## Success Metrics

| Metric        | Target |
| ------------- | ------ |
| Checking cycle | < 10s   |
| Error Rate (False Alarm)   | < 1%   |

---

# 4. Scope

## In Scope

* Dashboard monitoring website
* Manual cycle refresh for all and specific site
* Cycle countdown timer customization
* Notification cycle count customization
* User management
* IT staff management
* websites and page for each sites management

## Out of Scope

* Feature X
* Feature Y
* Feature Z

---

# 5. Assumptions & Dependencies

## Assumptions

* Users have internet access.
* Modern browsers are supported.

## Dependencies

* Telegram bot token
---

# 6. User Roles & Permissions

## Roles

| Role        | Description                 |
| ----------- | --------------------------- |
| Admin       | able to access dashboard, websites, profile (to change password)              |
| Super Admin | able to access everything     |

### Permission Matrix

| Action            | Admin| Superadmin |
| ----------------- | ----- | ---- | 
| View Dashboard | ✅     | ✅    | 
| Manage user   | ❌     | ✅    | 
| Manage websites      | ✅     | ✅    |
| Manage it staff      | ❌     | ✅    |
| Manage telegram target      | ❌     | ✅    |

---

# 7. User Stories

## Epic: User Management

### US-001

**As a** visitor

**I want to** register an account

**So that** I can access the platform.

### Acceptance Criteria

* [ ] Email required
* [ ] Email must be unique
* [ ] Password minimum 8 characters
* [ ] Verification email sent

---

### US-002

**As a** user

**I want to** update my profile

**So that** my information remains accurate.

### Acceptance Criteria

* [ ] Profile form available
* [ ] Validation applied
* [ ] Changes saved successfully

---

# 8. Functional Requirements

## Module: Authentication

### REQ-001 User Registration

#### Description

Allow users to register using email and password.

#### Inputs

| Field    | Type   | Required |
| -------- | ------ | -------- |
| email    | string | Yes      |
| password | string | Yes      |

#### Validation Rules

* Email format required
* Unique email
* Password minimum 8 characters

#### Expected Behavior

* Create account
* Send verification email
* Return success response

---

### REQ-002 User Login

#### Description

Allow users to authenticate.

#### Expected Behavior

* Validate credentials
* Generate session/token
* Redirect to dashboard

---

## Module: Reporting

### REQ-003 Export Reports

#### Description

Admins can export reports to CSV.

#### Acceptance Criteria

* [ ] CSV generated successfully
* [ ] Correct headers included
* [ ] Export respects filters

---

# 9. Non-Functional Requirements

## Performance

### NFR-001

Page load time < 2 seconds.

### NFR-002

Support 10,000 concurrent users.

---

## Security

### NFR-003

Passwords must be hashed.

### NFR-004

HTTPS required.

### NFR-005

Role-based authorization enforced.

---

## Reliability

### NFR-006

99.9% uptime target.

---

# 10. Data Model Requirements

## Entity: User

| Field      | Type      | Required |
| ---------- | --------- | -------- |
| id         | UUID      | Yes      |
| email      | String    | Yes      |
| password   | String    | Yes      |
| avatar_url | String    | No       |
| created_at | Timestamp | Yes      |

---

## Relationships

* User HAS MANY Reports
* Report BELONGS TO User

---

# 11. API Requirements

## POST /api/auth/register

### Request

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

### Response

```json
{
  "id": "uuid",
  "email": "user@example.com"
}
```

---

## POST /api/auth/login

### Request

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

### Response

```json
{
  "token": "jwt-token"
}
```

---

# 12. UI / UX Requirements

## Registration Page

### Components

* Email Input
* Password Input
* Submit Button

### States

* Loading
* Success
* Error
* Validation Error

### Responsive

* Desktop
* Tablet
* Mobile

---

# 13. Edge Cases & Error Handling

## Duplicate Email

### Expected Behavior

* Reject request
* Return validation error

---

## Invalid Credentials

### Expected Behavior

* Show error message
* Do not reveal whether email exists

---

## Service Unavailable

### Expected Behavior

* Graceful error page
* Retry mechanism where applicable

---

# 14. Analytics & Monitoring

## Analytics Events

| Event           | Trigger                 |
| --------------- | ----------------------- |
| user_registered | Registration successful |
| user_logged_in  | Login successful        |
| report_exported | Export completed        |

---

## Monitoring

* Application Logs
* Error Tracking
* Performance Monitoring

---

# 15. Testing Requirements

## Unit Tests

* Registration validation
* Password hashing
* Permission checks

---

## Integration Tests

* Registration flow
* Login flow
* Report export flow

---

## Coverage

Minimum 80% coverage.

---

# 16. Technical Stack & Constraints

## Frontend

* React
* Next.js
* Tailwind CSS

## Backend

* Laravel / Node.js / Express

## Database

* PostgreSQL / MySQL

## Deployment

* AWS
* Docker

---

# 17. Environment Requirements

## Environments

* Development
* Staging
* Production

---

## Environment Variables

```env
APP_ENV=
DATABASE_URL=
JWT_SECRET=
SMTP_HOST=
SMTP_PORT=
SMTP_USER=
SMTP_PASSWORD=
```

---

# 18. Implementation Constraints (AI Coding Section)

## Must Use

* Dependency Injection
* Service Layer
* Repository Pattern
* Form Validation

---

## Must Not Use

* Hardcoded Credentials
* Inline Business Logic in Controllers
* Deprecated Libraries

---

## Coding Standards

* Typed Properties
* Strict Typing
* Meaningful Naming
* Linting Enabled

---

## Existing References

Follow implementation patterns from:

* [Path A]
* [Path B]
* [Path C]

---

# 19. Risks & Mitigation

| Risk         | Impact   | Mitigation    |
| ------------ | -------- | ------------- |
| API Downtime | High     | Retry Logic   |
| High Traffic | High     | Auto Scaling  |
| Data Loss    | Critical | Daily Backups |

---

# 20. Release Criteria

Before release:

* [ ] Functional requirements complete
* [ ] Acceptance criteria passed
* [ ] Tests passing
* [ ] Security review completed
* [ ] Documentation updated
* [ ] Staging approved

---

# 21. Definition of Done (DoD)

A feature is considered complete when:

* [ ] Code implemented
* [ ] Acceptance criteria satisfied
* [ ] Unit tests written
* [ ] Integration tests written
* [ ] Code reviewed
* [ ] Documentation updated
* [ ] QA approved
* [ ] Deployed to staging
* [ ] Product owner approved
* [ ] Ready for production

```
```
