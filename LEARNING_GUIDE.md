# JBrothers Gym System — Deep Learning Guide
### Written for the Developer Who Built It but Wants to Truly Understand It

---

> **How to use this guide:** Read it top to bottom once. Then go back through each section and open the matching file in your editor side-by-side. Don't just read — trace the code with your eyes as you read.

---

## PART 1 — HIGH-LEVEL OVERVIEW: What Is This System?

This is a **multi-role web application** that manages gym memberships. Think of it like three mini-apps living inside one project:

| Portal | Who Uses It | Entry Point |
|---|---|---|
| Admin Panel | Owner / Admin | `/dashboard.php` |
| Staff Panel | Receptionist / Trainer | `/dashboard.php` (limited view) |
| Member Portal | Gym Customers | `/member_panel/index.php` |

The system's job is to answer these questions at any given moment:
- Who are our members and are they still active?
- What plans do we offer and what did members pay for?
- Who came to the gym today?
- How much money have we made this month?

---

## PART 2 — ARCHITECTURE BREAKDOWN: The Building Blocks

Think of the project like a house. Every folder has a specific job.

```
GYM MEMBERSHIP/
│
├── config/              ← THE FOUNDATION. Load this first, always.
│   ├── config.php       ← App settings, session start, helper functions
│   └── database.php     ← Opens the door to the database (PDO connection)
│
├── includes/            ← SHARED PIECES used on every page
│   ├── header.php       ← HTML <head>, sidebar, topbar. Also runs security + expiry.
│   ├── sidebar.php      ← The left navigation menu, reused everywhere
│   ├── footer.php       ← Closes HTML tags + sidebar toggle + alert dismiss JS
│   └── functions.php    ← Reusable PHP helper functions (formatDate, getTotalRevenue etc.)
│
├── auth/                ← IDENTITY & ACCESS. Who are you?
│   ├── login.php        ← Checks your email+password, starts your session
│   ├── logout.php       ← Destroys your session, sends you back to login
│   └── register.php     ← Creates a new Staff account (Admin-only action)
│
├── admin/               ← OWNER-ONLY ZONE
│   ├── users.php        ← View + add staff accounts
│   ├── edit_user.php    ← Edit staff permissions
│   └── settings.php     ← Gym name, contact info, currency
│
├── members/             ← MEMBER MANAGEMENT (Staff module)
│   ├── index.php        ← Member list with search/filter/pagination
│   ├── add.php          ← Register a new member
│   ├── view.php         ← View member's full profile
│   ├── edit.php         ← Edit member's info
│   └── delete.php       ← Remove a member
│
├── plans/               ← PLAN BUILDER (Staff module)
│   └── index.php        ← List, add, edit, delete membership plans
│
├── payments/            ← PAYMENT LOGGING (Staff module)
│   ├── index.php        ← Payment history table
│   ├── add.php          ← Record a new payment + auto-create membership (PDO transaction)
│   └── receipt.php      ← Printable receipt after a transaction
│
├── attendance/          ← CHECK-IN / CHECK-OUT (Staff module)
│   ├── index.php        ← Smart toggle check-in form + today's activity
│   └── history.php      ← Browse all historical attendance logs
│
├── reports/             ← ANALYTICS (Staff module, often admin-only)
│   └── index.php        ← Revenue chart + member stats
│
├── notifications/       ← NOTIFICATION CENTER
│   └── index.php        ← System alerts (expiry warnings, etc.)
│
├── member_panel/        ← CUSTOMER PORTAL (isolated from admin)
│   ├── index.php        ← Customer dashboard: my plan + recent attendance
│   └── profile.php      ← Edit profile, change password
│
├── database/
│   └── gym_db.sql       ← The database blueprint (all tables + default data)
│
└── assets/
    └── css/style.css    ← Global design system v2 (fully redesigned)
```

---

## PART 3 — THE DESIGN SYSTEM (v2.0): What Changed and Why

In version 2 of the UI, the entire `assets/css/style.css` was rewritten from scratch and key template files were updated. Understanding the design system will help you maintain and extend the UI confidently.

### Design Tokens (CSS Variables)

Every color, shadow, spacing value, and radius is stored as a CSS custom property at the top of `style.css` inside `:root {}`. This is a design token system — the single source of truth for every visual decision.

```css
:root {
    --bg-base:    #0a0a0f;   /* Deepest background — page base */
    --bg-surface: #111118;   /* Sidebar, topbar */
    --bg-card:    #16161f;   /* Cards, tables */
    --bg-elevated:#1c1c27;   /* Inputs, hover states, modals */

    --accent:        #22c55e; /* Primary green — buttons, active states */
    --accent-hover:  #16a34a;
    --accent-soft:   rgba(34,197,94,0.12); /* Icon backgrounds, nav active */
    --accent-ring:   rgba(34,197,94,0.25); /* Focus box-shadow rings */

    --text-primary:  #f1f1f3;
    --text-secondary:#9292a4;
    --text-muted:    #55556a;
}
```

**Why this matters:** If your professor or client asks you to change the accent color from green to blue, you change ONE variable (`--accent`) and the entire system updates everywhere instantly. This is professional-grade CSS architecture.

### The 5-Level Background Stack

The UI uses layering to create visual depth without clutter:
1. `--bg-base` (darkest) → page background
2. `--bg-surface` → sidebar and topbar
3. `--bg-card` → content cards
4. `--bg-elevated` → inputs, modals
5. `--bg-hover` → hover states on rows/items

Each layer is only slightly lighter than the one below it, creating a subtle but readable hierarchy.

### Components Updated in v2

| Component | What Changed |
|---|---|
| `index.php` (Login) | Split-panel layout. Left: form. Right: feature highlights. Completely custom CSS. |
| `includes/sidebar.php` | Brand icon block, section labels (`MAIN`, `ADMIN`), icon prefix on every nav item, sign-out in footer. |
| `includes/header.php` | Topbar now has: search bar, notification bell with dot, user avatar chip (auto-generated initials from name), refined sign-out button. Also uses prepared statement for expiry query. Flash message auto-renderer from `?success=` / `?error=` GET params. |
| `includes/footer.php` | Now includes JS: sidebar toggle for mobile, 5-second auto-dismiss on alerts, modal overlay click-to-close. |
| `dashboard.php` | 5 stat cards (was 3). Added "Expiring Soon" and "Check-ins Today" cards. New empty states in recent tables. |
| `members/index.php` | Status filter dropdown, dual-line name+email cell, payment shortcut button per row, proper prev/next pagination. |
| `admin/users.php` | Modal rebuilt with new `.modal-overlay` / `.modal` CSS classes. Backdrop blur. Slide-in animation. |

---

## PART 4 — THE DATABASE: Your Data's Home

The database is the single source of truth. Everything the PHP pages display comes from here. Open `database/gym_db.sql` and follow along.

### The 7 Tables and How They Relate

```
users          ← Staff and Admin accounts
members        ← Gym customers
membership_plans ← Plan templates (Monthly, Annual, etc.)
memberships    ← Which member bought which plan on which dates
payments       ← Money received, links to member + membership
attendance_logs ← Time-in/time-out records per member per day
notifications  ← System messages and alerts
```

### The Relationships (Foreign Keys)

A **foreign key** is a field in one table that points to the `id` of another table. This creates a link.

```
members [id: 5, name: "Juan"]
          ↑
          └── memberships [member_id: 5, plan_id: 2, end_date: "2025-05-01"]
                            ↑
                            └── payments [member_id: 5, membership_id: 8, amount: 1000]
                                          ↑
                                          └── attendance_logs [member_id: 5, time_in: "2025-04-13 09:00"]
```

The cascading rule `ON DELETE CASCADE` means: **if you delete a member, all their records are automatically deleted too.** But payments use `ON DELETE SET NULL` — meaning if a membership is deleted, the payment record stays but its `membership_id` field is set to NULL. This preserves your revenue figures.

---

## PART 5 — THE BOOT SEQUENCE: What Happens On Every Page Load

This is the most important concept to understand. Every single page in the admin panel starts by including `includes/header.php`. This one file does A LOT of work.

### Step-by-step, when you open any page (e.g., `members/index.php`):

```
Step 1: PHP reads members/index.php
        ↓
Step 2: It hits → require_once '../includes/header.php'
        ↓
Step 3: header.php itself loads:
        → config/config.php   (APP_URL, session_start(), helper functions)
        → config/database.php (creates $pdo — the database connection)
        → includes/functions.php (formatDate, getTotalRevenue, etc.)
        ↓
Step 4: require_login() — no session? → redirect to login
        ↓
Step 5: Role isolation — member? → redirect to /member_panel/index.php
        ↓
Step 6: Module permission check — no permission? → redirect to dashboard
        ↓
Step 7: AUTOMATED EXPIRY SWEEP (runs on every page load):
        UPDATE memberships SET status='Expired' WHERE end_date < TODAY  (prepared!)
        UPDATE members SET status='Expired' WHERE they have no active memberships
        ↓
Step 8: Build user initials for avatar chip (e.g. "Juan Dela Cruz" → "JD")
        ↓
Step 9: Output HTML head + sidebar + sticky topbar with search/bell/avatar
        ↓
Step 10: members/index.php resumes — page-specific queries and output
         ↓
Step 11: includes/footer.php → closes HTML + injects sidebar toggle JS
```

**Key insight:** Steps 4-7 are your security guards. They run before anything is shown on screen.

---

## PART 6 — CODE EXPLANATIONS: Every Important Piece

### 6.1 — PDO Database Connection (`config/database.php`)

```php
$pdo = new PDO("mysql:host=localhost;dbname=gym_db;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
```

- `new PDO(...)` — Opens a connection to MySQL. PDO ("PHP Data Objects") is the safe, modern way.
- `ERRMODE_EXCEPTION` — Failed queries throw a catchable error instead of silently failing.
- `FETCH_OBJ` — Database rows come back as objects (`$user->email`) not arrays (`$user['email']`).

---

### 6.2 — Sessions: The Memory Between Pages

HTTP is "stateless" — every page request forgets the previous one. **Sessions** fix this.

```php
// On login (auth/login.php):
$_SESSION['user_id']          = $user->id;
$_SESSION['user_name']        = $user->full_name;
$_SESSION['user_role']        = $user->role;        // 'admin', 'staff', or 'member'
$_SESSION['user_permissions'] = ['members', 'payments', 'attendance'];
```

Now every page can read `$_SESSION['user_role']` to know who you are — without hitting the database again.

---

### 6.3 — The `has_permission()` Function

```php
function has_permission($module) {
    if (!isset($_SESSION['user_role'])) return false;
    if ($_SESSION['user_role'] === 'admin')  return true;   // always passes
    if ($_SESSION['user_role'] === 'member') return false;  // never passes

    $perms = $_SESSION['user_permissions'] ?? [];
    return in_array($module, $perms);  // staff: check their assigned list
}
```

The `??` is the null coalescing operator: "use `$_SESSION['user_permissions']` if it exists, otherwise use `[]`."

---

### 6.4 — The Dual-Login System (`auth/login.php`)

The login flow checks **two tables**, in order:

```php
// Step 1: Check the 'users' table (staff and admins)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user->password)) {
    // ✅ Staff/Admin login
    redirect('/dashboard.php');
} else {
    // Step 2: Check the 'members' table (customers)
    if ($member && password_verify($password, $member->password)) {
        // ✅ Member login
        $_SESSION['user_role'] = 'member';
        redirect('/member_panel/index.php');
    }
}
```

Passwords are never stored plain — only BCrypt hashes. `password_verify()` re-hashes the input and compares.

---

### 6.5 — The Payment Transaction (`payments/add.php`)

This is the most complex logic in the system. Four things must succeed **together**:

```php
$pdo->beginTransaction();
try {
    // 1. Get plan duration → calculate end_date
    $end_date = date('Y-m-d', strtotime("+{$plan->duration_days} days"));

    // 2. Create membership record
    INSERT INTO memberships (member_id, plan_id, start_date, end_date, 'Active')
    $membership_id = $pdo->lastInsertId();

    // 3. Create payment record linked to that membership
    INSERT INTO payments (member_id, $membership_id, amount, method, NOW(), 'Paid')

    // 4. Flip member status to Active
    UPDATE members SET status = 'Active' WHERE id = ?

    $pdo->commit();   // ✅ All 4 steps succeeded — save permanently
} catch (Exception $e) {
    $pdo->rollBack(); // ❌ Anything failed — undo ALL 4 steps
}
```

Without a transaction, a partial failure would corrupt your data (e.g., membership created but payment missing).

---

### 6.6 — Smart Attendance Toggle (`attendance/index.php`)

Pressing "Process Attendance" once checks you IN, pressing it again checks you OUT:

```php
// Does this member have an open log today (time_out IS NULL)?
$openLog = $pdo->prepare(
    "SELECT id FROM attendance_logs WHERE member_id = ? AND DATE(time_in) = ? AND time_out IS NULL"
)->fetch();

if ($openLog) {
    UPDATE attendance_logs SET time_out = NOW() WHERE id = $openLog->id  // Check OUT
} else {
    INSERT INTO attendance_logs (member_id, time_in) VALUES (?, NOW())    // Check IN
}
```

`time_out IS NULL` means the member is still inside the gym (no value yet written for time-out).

---

### 6.7 — The Revenue Chart (`reports/index.php`)

PHP calculates 6 months of data server-side, then injects it directly into JavaScript:

```php
// PHP builds arrays on the server
for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $months[]   = date('M Y', strtotime("-$i months")); // e.g. "Apr 2025"
    $revenues[] = $result->total ?? 0;
}
$monthsJson   = json_encode($months);   // JSON array of strings
$revenuesJson = json_encode($revenues); // JSON array of numbers
```

```javascript
// JavaScript reads the PHP-injected JSON
labels: <?= $monthsJson ?>,   // PHP writes the array literal directly
data:   <?= $revenuesJson ?>
```

This PHP-to-JS data bridge is a standard pattern in server-rendered web apps.

---

### 6.8 — User Avatar Initials (New in v2 — `includes/header.php`)

```php
$nameParts = explode(' ', $_SESSION['user_name'] ?? 'U');
$initials  = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
```

`explode(' ', "Juan Dela Cruz")` → `["Juan", "Dela", "Cruz"]`
First letter of index 0 + first letter of index 1 → `"JD"`

This is displayed in the topbar avatar chip as the user's profile indicator without needing profile photos.

---

## PART 7 — KEY CONCEPTS TABLE

| Concept | Where In Your System | Why It Matters |
|---|---|---|
| **Sessions** | Login, `$_SESSION` | Remembers who is logged in between pages |
| **Prepared Statements** | Every `$pdo->prepare()->execute()` | Prevents SQL injection |
| **Database Transactions** | `payments/add.php` | All-or-nothing writes — prevents corrupt data |
| **Foreign Keys & Cascades** | `gym_db.sql` | Maintains data integrity across related tables |
| **BCrypt Password Hashing** | Login, register, member edit | Raw passwords are never stored |
| **RBAC** | `has_permission()`, `require_admin()` | Different users see different data |
| **CSS Design Tokens** | `assets/css/style.css` `:root {}` | One-variable global theming |
| **PHP-to-JS Data Bridge** | `json_encode()` → Chart.js | Passes server data to browser scripts |
| **NULL vs. empty** | `time_out IS NULL` | NULL = no value; empty string = has value but blank |
| **Aggregate SQL** | `SUM(amount)`, `COUNT(*)` | Math across many rows in one query |

---

## PART 8 — WEAK POINTS & IMPROVEMENTS

### ⚠️ 1. Expiry Runs on Every Page Load
The automated expiry sweep runs on every user page load. For large data sets this adds query overhead. **Better:** a scheduled cron job running once per day.

### ⚠️ 2. Permissions Stored as a CSV String
`"members,payments,attendance"` stored as a raw string is fragile. A **pivot table** (`user_permissions` with `user_id` + `module_name`) is the correct relational approach.

### ⚠️ 3. No CSRF Protection on Forms
Any form that modifies data should embed a **CSRF token** — a secret random value that proves the form was submitted from your site, not a malicious third-party page.

### ⚠️ 4. No Input Length Validation
While email is validated with `filter_var()`, text fields like `full_name` and `phone` have no max-length or format enforcement at the PHP layer (only HTML `maxlength`).

### ⚠️ 5. Global SQL in Dashboard for Today's Count
`$pdo->query("... WHERE DATE(time_in) = '$todayDate'")` — while `$todayDate` is PHP-generated, the pattern of string interpolation into queries is a bad habit. All queries should use prepared statements.

---

## PART 9 — EXERCISES TO REINFORCE YOUR UNDERSTANDING

### 🟢 Beginner

**Exercise 1 — Trace the Design Token:**
In `style.css`, find `--accent`. How many places in the CSS file use `var(--accent)`? Now change `--accent` to `#3b82f6` (blue). Open the system in a browser. What changed?

**Exercise 2 — Read One Stat Card:**
Open `dashboard.php`. Find the "Active Members" stat card. Trace where `$activeMembers` comes from all the way back to the SQL query. Write a comment in the file above the query explaining it in your own words.

**Exercise 3 — Break the Login Intentionally:**
Comment out the `password_verify()` check in `auth/login.php` and return `true`. Log in with a wrong password. Restore the code. What does this prove about what `password_verify()` is guarding?

---

### 🟡 Intermediate

**Exercise 4 — Add a New Stat Card:**
Add a 6th stat card to the dashboard: "Expired Members" (count of members where `status = 'Expired'`). Use `stat-icon-red` for the icon class. This exercises reading/writing PHP, SQL, and the CSS design system together.

**Exercise 5 — Add a Status Filter to Payments:**
Look at how `members/index.php` added a status filter dropdown. Replicate that pattern in `payments/index.php` to filter by `payment_method` (Cash, GCash, Card). This teaches you how to reuse established patterns.

**Exercise 6 — Prove the Transaction Works:**
In `payments/add.php`, temporarily add `throw new Exception("test")` between step 2 (membership insert) and step 3 (payment insert). Submit a payment and check phpMyAdmin — did the membership get created? It should not have. Then remove the test exception. This proves the `rollBack()` is working.

---

### 🔴 Advanced

**Exercise 7 — Fix the Permissions Architecture:**
Design a new `user_permissions` table with columns `user_id` (FK) and `module` (VARCHAR). Write the SQL to create it. Rewrite `auth/login.php` to populate `$_SESSION['user_permissions']` by querying this table instead of exploding a string. Update `auth/register.php` to INSERT rows instead of joining a string.

**Exercise 8 — Add CSRF Protection:**
In `config/config.php`, add a function that generates and stores a CSRF token in `$_SESSION`. Add the hidden token field to `members/add.php`. Verify the token on POST before processing the form. This is one of the most important real-world security skills.

**Exercise 9 — Build the Notifications Module:**
`/notifications/` exists but may be incomplete. Build `notifications/index.php` that:
1. Displays all rows from the `notifications` table
2. Marks all as read when the page is opened (`UPDATE notifications SET is_read = 1`)
3. Shows an unread count badge on the topbar bell icon
This touches four areas of the system simultaneously: database, PHP, HTML, and the shared includes.

---

## SUMMARY: Your Mental Model

```
Browser Request
      ↓
  Any PHP page
      ↓
  includes/header.php  ← Security gate (login check → role check → expiry sweep → render topbar)
      ↓
  Page-specific PHP    ← Queries the database, builds variables
      ↓
  HTML + PHP Output    ← Displays data using design system classes; may feed Chart.js
      ↓
  includes/footer.php  ← Closes HTML + runs sidebar toggle JS + alert dismiss
      ↓
Browser renders HTML
```

Once you can trace any page through that loop and explain what every line does — you understand this system deeply.
