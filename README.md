# JBrothers Gym Membership Management System

A complete multi-role gym operations platform built with native PHP, MySQL/MariaDB, and vanilla frontend technologies. It supports admin/staff operations, a dedicated member portal, payments, memberships, attendance, reports, and QR-based check-ins.

## Overview

This project contains three application surfaces:

1. Admin Portal: full access to users, settings, all modules.
2. Staff Portal: module access controlled by permission list.
3. Member Portal: isolated self-service dashboard and profile area.

Main goals of the system:

1. Manage members, plans, and active memberships.
2. Record payments and keep financial logs consistent.
3. Track attendance with manual and QR-based flow.
4. Provide reports and daily activity visibility.

## Core Capabilities

### Access and Security

1. Session-based authentication for users and members.
2. Role-based separation (`admin`, `staff`, `member`).
3. Per-module permission enforcement for staff.
4. Global login guard and unauthorized redirect handling.

### Member and Plan Management

1. Member CRUD with profile details, status, and photo.
2. Auto-generated membership IDs in format `GYM-YYYY-XXXXX`.
3. Membership plans with duration and pricing.
4. Active/expired status handling for plans and members.
5. Soft-delete archive support for members and plans.

### Payments and Membership Activation

1. Payment recording with method and status.
2. Membership creation and activation tied to payment flow.
3. Transaction-style consistency (payment + membership updates).
4. Printable receipts.

### Attendance and QR System

1. Manual attendance check-in/check-out.
2. QR camera scanning and image-file decoding.
3. Automatic toggle between check-in and check-out for the same day.
4. Live “today logs” refresh in attendance screen.

### Member Portal

1. Member dashboard with own membership status and recent attendance.
2. Profile update support (phone/address/demographics).
3. Password change with current password verification.
4. Member card page with printable/downloadable QR ID card.

### Dashboard and Reporting

1. KPI cards for members/revenue/check-ins/expiry.
2. Revenue chart visualization.
3. Payment history and operational summaries.
4. Notifications view for system/member-related events.

## QR Implementation (Current)

The project now uses:

1. Server-side QR generation: `endroid/qr-code` via Composer.
2. Browser-side QR decoding: `html5-qrcode` JavaScript library.

### Generation Flow

1. QR images are served by [qrcode.php](qrcode.php).
2. Endpoint is authenticated (`require_login()`), and returns PNG.
3. Card/profile pages render QR as standard image source:
	1. [members/id_card.php](members/id_card.php)
	2. [member_panel/my_card.php](member_panel/my_card.php)
	3. [members/view.php](members/view.php)
	4. [member_panel/index.php](member_panel/index.php)

### Scan Flow

1. Frontend scanner in [attendance/index.php](attendance/index.php) decodes payload.
2. Scanner posts both fields to backend:
	1. `membership_id` (normalized value)
	2. `qr_data` (raw scanned payload)
3. Backend endpoint [attendance/scan.php](attendance/scan.php) parses multiple payload formats:
	1. Plain membership ID
	2. URL query payloads (`membership_id`, `member_id`, etc.)
	3. JSON payload
	4. Prefixed text (`member:...`, `id:...`)
	5. Embedded `GYM-YYYY-XXXXX` pattern

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Native PHP 8.x |
| Database | MySQL / MariaDB |
| DB Access | PDO |
| Frontend | HTML5, Vanilla CSS3, Vanilla JS |
| Charts | Chart.js |
| Icons | FontAwesome 6.5 |
| Searchable Select | Tom Select |
| QR Generation | endroid/qr-code (Composer) |
| QR Decoding | html5-qrcode (local vendor JS) |

## Project Structure

```text
GYM MEMBERSHIP/
├── index.php
├── dashboard.php
├── qrcode.php
├── composer.json
├── composer.lock
├── config/
│   ├── config.php
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── functions.php
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── admin/
│   ├── users.php
│   ├── edit_user.php
│   └── settings.php
├── members/
├── plans/
├── payments/
├── attendance/
├── reports/
├── notifications/
├── member_panel/
├── assets/
│   ├── css/
│   ├── js/
│   │   └── vendor/
│   │       └── html5-qrcode.min.js
│   └── uploads/
└── database/
	 ├── gym_db.sql
	 ├── soft_delete_migration.sql
	 └── membership_qr_migration.sql
```

## Database Model

Base schema ([database/gym_db.sql](database/gym_db.sql)) includes:

1. `gym_settings`
2. `users`
3. `members`
4. `membership_plans`
5. `memberships`
6. `payments`
7. `attendance_logs`
8. `notifications`

Additional migrations:

1. [database/soft_delete_migration.sql](database/soft_delete_migration.sql)
	1. Adds `deleted_at` to `members` and `membership_plans`.
2. [database/membership_qr_migration.sql](database/membership_qr_migration.sql)
	1. Adds `membership_id` and `photo_path` to `members`.
	2. Backfills IDs for existing members.

## Installation and Setup

### Requirements

1. PHP 8.1+ (8.2 recommended).
2. MySQL or MariaDB.
3. Composer.
4. PHP extensions:
	1. `pdo_mysql`
	2. `gd` (required for `endroid/qr-code` image generation)

### Clone and Install

```bash
git clone <your-repo-url>
cd "GYM MEMBERSHIP"
composer install
```

Important:

1. `vendor/` is ignored by Git.
2. Everyone who clones must run `composer install`.
3. `composer require endroid/qr-code` is only needed when adding/changing dependencies.

### Database Setup

1. Create/import base schema using [database/gym_db.sql](database/gym_db.sql).
2. Run migration SQL files in this order:
	1. [database/soft_delete_migration.sql](database/soft_delete_migration.sql)
	2. [database/membership_qr_migration.sql](database/membership_qr_migration.sql)

### App Configuration

1. Edit DB credentials in [config/database.php](config/database.php).
2. Ensure `APP_URL` in [config/config.php](config/config.php) matches your local URL.

### Run the App

1. Serve through XAMPP/WAMP/Laragon or PHP server.
2. Open: `http://localhost/GYM%20MEMBERSHIP/`

## Default Credentials

1. Admin account:
	1. Email: `admin@gym.com`
	2. Password: `password123`
2. Newly created members default password: `password`

## Role and Permission Flow

1. Admin users have full module access.
2. Staff users are limited to modules listed in `users.permissions`.
3. Members are redirected to member portal and blocked from admin/staff modules.
4. Protected modules are enforced in [includes/header.php](includes/header.php):
	1. `members`
	2. `plans`
	3. `payments`
	4. `attendance`
	5. `reports`

## Operational Notes

1. Expiry sweep runs globally during page load in [includes/header.php](includes/header.php):
	1. Memberships with past `end_date` become `Expired`.
	2. Members without active memberships become `Expired`.
2. Attendance check-in/check-out is day-based and auto-toggles open session.
3. Archived (`deleted_at` not null) members/plans are excluded in active lists.

## Troubleshooting

### QR generator dependency missing

Symptom:

1. `QR generator dependency is missing. Run composer install.`

Fix:

1. Run `composer install` in project root.

### QR scanner library failed to load

Fix:

1. Ensure local file exists: [assets/js/vendor/html5-qrcode.min.js](assets/js/vendor/html5-qrcode.min.js)
2. Hard-refresh browser (`Ctrl+F5`).

### Camera access errors

Fix:

1. Allow camera permission in browser.
2. Use HTTPS or localhost.
3. Try file upload mode as fallback.

### Database connection error

Fix:

1. Verify credentials in [config/database.php](config/database.php).
2. Confirm MySQL service is running.
3. Confirm `gym_db` and tables are imported.

## Development Notes

1. Keep [composer.json](composer.json) and [composer.lock](composer.lock) committed.
2. Keep `vendor/` untracked (already in [.gitignore](.gitignore)).
3. If dependency list changes:
	1. Run `composer require <package>`
	2. Commit updated lock file.

## Known Gaps / Future Improvements

1. Add CSRF protection to all state-changing forms.
2. Move expiry sweep to cron job for scale.
3. Add export endpoints (CSV/PDF).
4. Add automated reminders (SMS/email).
5. Add test coverage for critical flows.
