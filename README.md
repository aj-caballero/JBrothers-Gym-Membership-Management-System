# JBrothers Gym Membership Management System

A robust, full-stack gym administration portal built with pure PHP, MySQL, and vanilla web technologies. Designed for gym owners, staff, and members to seamlessly manage daily operations, track payments, generate revenue reports, and manage memberships in real-time — wrapped in a modern, professional SaaS-grade interface.

---

## 🚀 Key Features

### 👑 Admin & Staff Operations
- **Role-Based Access Control (RBAC):** Secure multi-tier permissions. Admins can restrict specific module visibility (e.g., hiding financial reports) per staff account via granular permission checklists.
- **Membership Plan Management:** Create and price custom membership packages with duration and auto-calculated expiry dates.
- **Member Directory:** Track all members with searchable, filterable tables — view profile, status, contact info, and history in one click.
- **Real-Time Attendance:** Smart toggle check-in/check-out — the system auto-detects whether to log time-in or time-out based on today's records.
- **Automated Expiry Checks:** On every page load, memberships and member statuses are swept and updated automatically — no cron job required.
- **Centralized Dashboard:** At-a-glance KPI cards: Total Members, Active Members, Total Revenue, Expiring Soon, and Today's Check-ins.
- **Financial Analytics:** Dynamic Chart.js line graph tracking the last 6 months of revenue, plus a full payment log with method breakdown (Cash, GCash, Card).
- **Transactional Payment Flow:** Payments, membership creation, and member activation all execute inside a single PDO database transaction — atomically.

### 👤 Dedicated Member Panel
- **Isolated User Portal:** A safely containerized portal accessible exclusively by standard gym members, completely separate from staff screens.
- **Profile Self-Service:** Members can update demographics and change their password securely.
- **Membership Status Overview:** Live badge showing active plan, expiry date, and plan description.
- **Attendance History Viewer:** Members can view their own time-in/time-out history.

---

## 🎨 UI Design System (v2.0)

The interface was fully redesigned to match real-world SaaS fitness dashboards.

- **Theme:** Dark professional — deep slate backgrounds (`#0a0a0f`, `#111118`, `#16161f`) with a fitness-green accent (`#22c55e`)
- **Typography:** Inter (800/700/600/500/400) with a strict 5-level hierarchy
- **Login Page:** Split-panel layout — form left, feature highlights right
- **Sidebar:** Fixed 256px sidebar with section labels, icon-prefixed nav items, active state highlighting, and mobile collapse
- **Topbar:** Sticky 60px header with search bar, notification bell, user avatar chip (generated initials), and sign-out button
- **Stat Cards:** 5-card KPI row with colored icon blocks, hover glow effect, and gradient overlay
- **Tables:** Uppercase column headers, dual-line name+email cells, colored status badges, and row hover
- **Badges:** Pill-shaped with dot indicator — green (Active), red (Expired), amber (Suspended), muted (Inactive)
- **Buttons:** Primary (green), Secondary (ghost), Danger; all with hover lift and active press states
- **Modals:** Backdrop blur overlay, slide-in animation, structured header/body/footer layout
- **Empty States:** Illustrated fallbacks for every empty table — icon + heading + CTA
- **Alerts/Flash Messages:** Color-coded inline alerts (success/error) that auto-dismiss after 5 seconds
- **Responsive:** Sidebar collapses to slide-in drawer below 1024px; stat grid stacks on mobile

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | Native PHP 8.x |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML5, Vanilla CSS3 (custom design system v2) |
| **Icons** | FontAwesome 6.5 |
| **Charts** | Chart.js (CDN) |
| **Fonts** | Inter — Google Fonts |

---

## 📁 Project Structure

```
GYM MEMBERSHIP/
├── config/          — App constants, session, database connection (PDO)
├── includes/        — Shared header, footer, sidebar, helper functions
├── auth/            — Login, logout, staff registration
├── admin/           — User accounts & gym settings (admin only)
├── members/         — Full member CRUD + search/filter/pagination
├── plans/           — Membership plan builder
├── payments/        — Payment recording + receipt printer
├── attendance/      — Smart check-in/check-out + full history
├── reports/         — Revenue chart + member stat analytics
├── notifications/   — System alert center
├── member_panel/    — Isolated customer self-service portal
├── assets/css/      — Global design system stylesheet (style.css)
└── database/        — gym_db.sql schema + seed data
```

---

## ⚙️ Installation & Usage

1. Import `./database/gym_db.sql` into MySQL via phpMyAdmin.
2. Configure credentials in `./config/database.php`.
3. Serve via XAMPP, WAMP, or PHP built-in server.
4. Navigate to `http://localhost/GYM MEMBERSHIP/` and sign in.

**Default admin credentials:**
- Email: `admin@gym.com`
- Password: `password123`

**Default member password** (set at registration): `password`

---

## 🔮 Future Roadmap

- [ ] **QR Code / RFID Check-Ins** — Automate attendance via scanner APIs
- [ ] **Automated SMS/Email Reminders** — Ping members 3 days before expiration
- [ ] **PDF/CSV Export Hub** — Snapshot analytics directly into spreadsheets
- [ ] **Class Scheduling Module** — Members reserve trainer sessions from their panel
- [ ] **Integrated Payment Gateway** — Stripe / PayPal webhook callbacks
- [ ] **CSRF Token Protection** — Secure all state-mutating forms
- [ ] **Dedicated Cron Expiry Job** — Replace page-load sweep with a scheduled background task
