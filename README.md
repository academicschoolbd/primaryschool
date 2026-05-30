# EduSaaS — School Management (PHP + MySQL)

A complete, modern PHP school management script with both a **public Bangla/English homepage** and a clean **admin panel**. Manage teachers, students, classes, exam results, marksheets, notices, gallery, and leadership messages — all wired to MySQL.

---

## Features

### Public Homepage (`/index.php`)
- Bangla + English bilingual layout, deep blue + gold theme
- Top bar with EIIN badge, established year, phone, login link
- Sticky navigation with hover dropdowns + mobile hamburger
- Scrolling marquee for active notices
- Hero slider (Swiper) with caption overlay
- Live stats strip (students/teachers/classes/years from DB)
- 8-tile quick-menu grid
- Notice board with date stickers (Bangla numerals)
- Leadership message cards (principal, vice principal, etc.)
- Important links / services blocks
- Photo gallery with hover effect
- Extracurricular activity cards
- Map embed
- Sidebar: about, dynamic Bengali calendar, important links, national anthem audio
- Animated footer with school info, quick links, established-year card

### Admin Panel (`/admin/`)
- **Login** with hashed passwords (bcrypt via `password_hash`)
- **Dashboard** — live counts, enrollment trend chart, students-by-class doughnut
- **Teachers** — full CRUD, search by name/email/subject
- **Students** — full CRUD, filter by class, search by name/roll/parent
- **Classes** — full CRUD, occupancy bar, class-teacher assignment
- **Results** — single-form grid entry of all subject marks per student per term, auto-graded
- **Marksheet** — printable report card with totals, percentage, grade, pass/fail
- **Print-ready** styles for marksheet (`@media print` hides the chrome)

---

## Tech stack

| Layer       | Choice                                          |
|-------------|-------------------------------------------------|
| Server      | PHP 7.4+ (vanilla, no framework)                |
| Database    | MySQL 5.7+ / MariaDB                            |
| DB layer    | PDO (prepared statements)                       |
| Admin UI    | Custom CSS (Inter font, indigo theme)           |
| Public UI   | Bootstrap 5 + custom CSS (Hind Siliguri + Inter)|
| Icons       | Font Awesome 6 + Bootstrap Icons                |
| Charts      | Chart.js 4 (CDN)                                |
| Slider      | Swiper 10 (CDN)                                 |
| Animations  | AOS 2.3 (CDN)                                   |

---

## Project structure

```
school-saas/
├── index.php                 → public Bangla/English homepage
├── install.php               → one-time DB installer (delete after setup)
├── config/
│   ├── config.php            → app constants, session
│   └── database.php          → PDO connection (edit credentials here)
├── database/
│   └── schema.sql            → tables + sample seed data
├── includes/
│   ├── auth.php              → require_login() guard
│   ├── functions.php         → helpers (e, redirect, flash, calc_grade, …)
│   ├── header.php            → admin sidebar + topbar layout
│   └── footer.php
├── assets/
│   └── css/
│       ├── style.css         → admin theme (indigo)
│       └── public.css        → public theme (deep blue + gold)
└── admin/
    ├── login.php             → bcrypt-verified login
    ├── logout.php
    ├── index.php             → dashboard
    ├── teachers.php          → list + add/edit/delete
    ├── students.php          → list + add/edit/delete + filters
    ├── classes.php           → list + add/edit/delete
    ├── results.php           → enter / edit marks per student+term
    └── marksheet.php         → printable report card
```

---

## Setup (5 minutes)

### 1. Requirements
- PHP **7.4+** with `pdo_mysql` extension
- MySQL **5.7+** (or MariaDB 10.3+)
- A web server: Apache, Nginx, or just `php -S` for dev

### 2. Drop the project in your web root
For XAMPP / WAMP / Laragon:
```
htdocs/school-saas/        ← project here
```
For nginx/apache, point the docroot at `school-saas/`.

### 3. Configure database credentials
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_saas');
define('DB_USER', 'root');
define('DB_PASS', '');         // your MySQL password
```

### 4. Run the installer
Open in your browser:
```
http://localhost/school-saas/install.php
```
The installer will:
1. Create the `school_saas` database
2. Build all tables
3. Insert sample teachers, classes, students, subjects, results
4. Create the default admin user with a properly hashed password

> **Important:** Delete `install.php` after a successful run.

### 5. Open the site
- Public homepage: `http://localhost/school-saas/`
- Admin login:    `http://localhost/school-saas/admin/login.php`

### Admin credentials
```
| Field    | Value                  |
|----------|------------------------|
| Email    | `admin@school.test`    |
| Password | `admin123`             |

You can also bootstrap manually with `mysql < database/schema.sql`, but you'll need to insert an admin user with a `password_hash()` value yourself.

---

## Quick run with PHP's built-in server (no Apache needed)

```bash
cd school-saas
php -S localhost:8000
```
Then visit `http://localhost:8000/install.php`, then `/admin/login.php`.

---

## Day-to-day usage

| Task                       | Where to go                                  |
|----------------------------|----------------------------------------------|
| Add a new student          | Students → **Add Student**                   |
| Assign class teacher       | Classes → edit row                           |
| Enter exam marks           | Results → **Enter Results** → pick student   |
| View / print report card   | Marksheet → pick student + term → **Print**  |
| Search teachers            | Teachers → search box (name/email/subject)   |
| Filter students by class   | Students → class dropdown                    |

The `results` table has a `UNIQUE (student_id, subject_id, exam_term)` constraint — re-saving marks for the same combination is an upsert, so editing is safe.

---

## Schema overview

```
-- Admin / academic
users           (id, name, email, password, role)
classes         (id, name, section, teacher_id, capacity)
teachers        (id, name, email, phone, subject, gender, joined_on, status)
students        (id, roll_no, name, class_id, gender, dob, parent_name, phone, address, status)
subjects        (id, name, code, full_marks, pass_marks)
results         (id, student_id, subject_id, exam_term, marks_obtained, grade)

-- Public site content
school_info     (id, name_bn, name_en, address, phone, email, eiin, established, logo, about_bn, map_embed, …)
sliders         (id, image, caption, is_active, sort_order)
notices         (id, title, body, is_published, posted_at)
school_messages (id, name, designation, photo, content, sort_order)
gallery         (id, image, caption, sort_order)
```

To customise the public homepage, edit the row in `school_info` (school name, address, EIIN, established year, logo, etc.) and add/remove rows in `sliders`, `notices`, `school_messages`, and `gallery` directly via your favourite SQL tool. A simple admin UI for these is on the roadmap.

---

## Grading scale

Configured in `includes/functions.php → calc_grade()`:

| Percentage | Grade |
|-----------:|:-----:|
| ≥ 90       | A+    |
| ≥ 80       | A     |
| ≥ 70       | B+    |
| ≥ 60       | B     |
| ≥ 50       | C     |
| ≥ 33       | D     |
| < 33       | F     |

Adjust the thresholds in one place to retheme the gradebook.

---

## Roadmap (next logical steps)

- Attendance tracking
- Fee management & receipts
- Parent / student portals (multi-role auth)
- Notices & messaging
- Multi-tenant: each school its own subdomain & isolated data
- CSV import/export for students and results

---

## License

MIT — use freely for your school or as a starting point for a commercial product.



---

## Public Result Page (`/result.php`)

A fully responsive Bangla/English result-lookup page for parents and students.

**Flow:**
1. Pick **শ্রেণী** (Class) → sections auto-load via AJAX
2. Pick **শাখা** (Section)
3. Enter **রোল নম্বর** (Roll No)
4. Choose exam term (প্রথম সাময়িক / দ্বিতীয় সাময়িক / বার্ষিক)
5. Click "ফলাফল দেখুন" → marksheet renders inline (no page reload), printable, sharable on mobile (`navigator.share`)

The marksheet UI auto-collapses to mobile (single-column info, 2-column summary, horizontally-scrollable marks table on small screens).

## Other public pages
- `/notices.php` — full notice board
- `/result.php` — result lookup
- `/` — homepage

All public pages share the same `includes/public_header.php` and `includes/public_footer.php` for consistency.

---

## Public JSON API (CORS-enabled — ready for the mobile app)

These endpoints are designed so a future React Native / Flutter / native mobile app can call them directly. They return `application/json`, set permissive CORS headers, and require **no authentication** (they only expose data the public should see).

| Endpoint | Method | Params | Returns |
|---|---|---|---|
| `/api/public_classes.php` | GET | — | List of distinct class names |
| `/api/public_sections.php` | GET | `class` | Sections under that class with their `class_id` |
| `/api/public_result.php` | GET | `class_id`, `roll_no`, `exam_term` (`first`\|`mid`\|`final`) | Full marksheet JSON: student, class, school, term, subjects[], summary |

**Response shape (success):**
```json
{ "ok": true, "data": { ... } }
```
**Response shape (error):**
```json
{ "ok": false, "msg": "Human-readable error" }
```
HTTP codes: `200` ok · `400` bad input · `404` not found · `503` DB down.

### Example mobile call
```js
const r = await fetch(
  'https://yourdomain/primaryschool/api/public_result.php?class_id=5&roll_no=STU-1001&exam_term=final'
);
const j = await r.json();
if (j.ok) showMarksheet(j.data);
```

### Admin (auth-required) endpoints
These need a logged-in admin session (cookie-based today; token-based recommended for mobile in v2):
`teachers_search`, `teachers_delete`, `students_search`, `students_delete`, `classes_search`, `sections`, `settings_save`.

---

## Database structure for the mobile app

The current schema is intentionally normalised so a mobile app can map cleanly:

| Concept | Table | Notes |
|---|---|---|
| Schools | `school_info` | Single tenant today; for multi-tenant add `tenant_id` to every other table |
| Auth users | `users` | Web admin/teacher accounts (bcrypt). For mobile, add a `tokens` table or reuse `users` with API tokens |
| Classes | `classes` | `(name, section)` pair = one row, used both for filtering and as the FK target for students |
| Teachers | `teachers` | Has `photo`, `designation` for app display |
| Students | `students` | `roll_no` is the public lookup key; pair `(class_id, roll_no)` is effectively unique per school |
| Subjects | `subjects` | Per-school subjects with full/pass marks |
| Results | `results` | `UNIQUE(student_id, subject_id, exam_term)` — safe to re-sync from the app |
| Notices | `notices` | `is_published` controls visibility on public/app |
| Sliders | `sliders` | `is_active` + `sort_order` for hero on web/app |
| Gallery | `gallery` | `sort_order` for app's photo grid |
| Messages | `school_messages` | Principal/VP cards |
| Settings | `settings` | Key/value (theme colors, etc.) — easy for the app to fetch as JSON |

**Roadmap additions for the mobile app (next iteration):**
- `device_tokens (id, user_id, token, platform, last_seen)` — for FCM/APNs push
- `student_users (student_id, password_hash, last_login)` — per-student PIN login
- `auth_tokens (user_id, token, expires_at)` — issue from `/api/auth/login.php` instead of session cookies, so the app can authenticate via `Authorization: Bearer …`
- All future write APIs should accept JSON body and return JSON — already the convention here.



---

## Public list pages (mobile-friendly, AJAX)

| URL | What it does |
|---|---|
| `/students.php` | শিক্ষার্থী তালিকা — pick class → section → gender → status, AJAX-loaded table with photo |
| `/teachers.php` | শিক্ষক তালিকা — searchable card grid with photo, designation, subject, contact |
| `/result.php`   | পরীক্ষার ফলাফল — class+section+roll lookup, prints a marksheet |
| `/notices.php`  | নোটিশ বোর্ড — full notice board |

All four pages use the same `includes/public_header.php` + `includes/public_footer.php`, and call public CORS-enabled JSON endpoints under `/api/`.

## Class management (admin)

The classes admin page now has three separate flows so each action is a single, focused step:

| Button | What it does |
|---|---|
| **+ Add Class** | Creates a brand-new class (e.g. "Grade 1") with optional first section |
| **+ Add Section** | Pick an existing class → add another section letter under it |
| **(inline)** | Each row in the listing has an inline teacher dropdown — change it and it AJAX-saves instantly with a toast confirmation |

## Notices admin (`/admin/notices.php`)

Full CRUD with:
- **PDF upload** (max 6 MB, MIME-validated). Stored under `assets/uploads/notices/`. Replacing an attachment auto-deletes the old file. To enable true compression, install Ghostscript on the server and call `gs -sDEVICE=pdfwrite -dPDFSETTINGS=/ebook ...` in `api/notices_save.php`.
- **"Show as floating popup"** — at most one notice can be the floating one. Setting it auto-clears the flag on others.
- **"Published"** flag — controls whether the notice appears in the marquee, notices page, and floating popup.

A **global on/off toggle** at the top of the page (and in **Settings → Homepage Sections & Floating Popup**) lets the super admin disable the floating popup site-wide without touching individual notices.

## Floating notice popup (public site)

Auto-pops on every public page when:
1. `floating_notice_enabled = '1'` in settings, **AND**
2. There is a notice with `is_floating = 1 AND is_published = 1`

The popup:
- Animates in 800ms after page load
- Has Title (Bangla) + body + posted date + optional PDF download button + "সকল নোটিশ" link
- Can be dismissed by clicking the × button, the overlay, the "পরে দেখব" button, or pressing Esc
- Stores `fnDismissed_<id>` in `localStorage` so the user doesn't see the same notice twice
- Is fully responsive (full-width on phone, action buttons stack)

## Super-admin "Site Manager" controls

In **/admin/settings.php** an admin can:

1. **Theme colors** — primary + accent with live preview + 8 quick presets (existing)
2. **School info** — name, address, phone, EIIN, established, logo, map embed (existing)
3. **Homepage sections & floating popup** (NEW): one checkbox per section. Hide the hero, stats, quick menu, notices, messages, services, gallery, extracurricular, map, sidebar widgets (about/calendar/anthem/links), or the floating popup itself — saved straight to the `settings` table and applied on every visitor's next page load.

## Modern home animations

- **Animated counters** on the stats strip (intersection observer, 1.2s ease-out cubic, supports Bengali numerals)
- **Scroll reveal stagger** on quick-menu icons, extracurricular cards and teacher cards (fade + slide-up via IntersectionObserver)
- AOS still used for cards/widgets

## Schema additions

```sql
ALTER TABLE students  ADD photo VARCHAR(255) NULL;
ALTER TABLE notices   ADD pdf_url VARCHAR(255) NULL,
                      ADD pdf_size INT NULL,
                      ADD is_floating TINYINT(1) DEFAULT 0;

INSERT INTO settings (`key`,`value`) VALUES
  ('floating_notice_enabled', '1'),
  ('home_show_hero', '1'),       ('home_show_stats', '1'),
  ('home_show_quick_menu', '1'), ('home_show_notices', '1'),
  ('home_show_messages', '1'),   ('home_show_services', '1'),
  ('home_show_gallery', '1'),    ('home_show_extras', '1'),
  ('home_show_map', '1'),        ('home_show_about_widget', '1'),
  ('home_show_calendar', '1'),   ('home_show_anthem', '1'),
  ('home_show_links', '1');
```

(Re-run `install.php` to apply — installer DROPs and re-creates tables, so existing data will be lost.)

## Roadmap (next iteration)

- **Student CSV bulk import** — pick a file, preview the first 10 rows, AJAX import in batches with per-row error reporting
- **Subject-wise / Teacher-wise mark entry** — pick a subject + class + term → grid of all students with one save button
- **Custom exam creator** — replace the `first/mid/final` enum with a flexible `exams` table (id, name, year, start/end dates), update `results` to FK to `exams`
- **True PDF compression** — call Ghostscript (`gs -sDEVICE=pdfwrite -dPDFSETTINGS=/ebook`) in `api/notices_save.php` if the binary is available; falls back to as-is upload otherwise
- **Slider / Message / Gallery CRUD** — admin pages for the homepage's hero swiper, leadership messages, and photo gallery (currently editable via SQL)
- **Mobile app auth** — `auth_tokens` + `student_users` (PIN login) + `device_tokens` (push) tables



---

## CMS Pages — every dropdown link is now editable

The admin panel now ships with a full **CMS Pages** section (`/admin/pages.php`). Every navigation dropdown item points at `page.php?slug=<name>`, so the super-admin can fill in any page from one place — no PHP edits needed.

| URL pattern | Renders |
|---|---|
| `/page.php?slug=about-institution` | The `about-institution` row from the `pages` table |
| `/page.php?slug=class-routine` | Class routine page |
| `/page.php?slug=syllabus` | Syllabus page |
| `/page.php?slug=contact` | Contact page |
| `/page.php?slug=anything-you-want` | Whatever the admin types into the CMS |

Pages support sanitized HTML (allowlist of common tags, all `on*` event handlers and `javascript:` URLs are stripped). 404 page when slug is missing or unpublished. Admins viewing a page see an "এডিট করুন" link to jump straight to the editor.

**Public mobile-app endpoint:** `GET /api/public_page.php?slug=foo` returns the same content as JSON with CORS headers.

## Site content controllable from the admin panel

Every visual block on the homepage and every public page is now editable end-to-end through the admin panel:

| Admin URL | Manages |
|---|---|
| `/admin/pages.php`      | CMS pages (any nav-dropdown destination) |
| `/admin/sliders.php`    | Hero swiper slides — image upload + caption + active toggle + sort order |
| `/admin/leadership.php` | Principal/VP/Chairman cards — photo upload + name + designation + body |
| `/admin/gallery.php`    | Homepage photo gallery — image upload + caption + sort |
| `/admin/notices.php`    | Notice board (existing) — PDF upload + floating popup |
| `/admin/subjects.php`   | Exam subjects — name, code, full marks, pass marks |
| `/admin/teachers.php`   | Teacher records (existing) — photo, designation |
| `/admin/students.php`   | Student records (existing) — class+section cascading |
| `/admin/classes.php`    | Classes (existing) — separate add-class / add-section / inline teacher AJAX |
| `/admin/settings.php`   | Theme colors + school info + 13 homepage section toggles + floating popup global switch |

All image uploads live under `assets/uploads/{teachers,sliders,messages,gallery,notices}/` with MIME validation and old-file cleanup on replace/delete.

## Scroll color fix

Two CSS additions stop the iOS rubber-band overscroll showing the wrong (white) color:

```css
html { background-color: var(--bg-light); overscroll-behavior-y: contain; -webkit-overflow-scrolling: touch; }
body.public { background: var(--bg-light); min-height: 100vh; }
```

Plus `scroll-behavior: smooth` for in-page anchor links (with a `prefers-reduced-motion` opt-out for accessibility), and the same fix in the admin theme so the deep-blue sidebar gradient extends past any overscroll bounce.

## Schema additions

Run **`migrate.php`** once after pulling — it's safe and non-destructive. It will add what's missing:

- `pages` table (CMS pages) + 16 stub rows for common menu items
- All previously-added columns (settings, students.photo, notices.pdf_url, etc.)
- Upload directories (`assets/uploads/messages/`, `sliders/`, `gallery/`)

## Roadmap (still pending)

- Student CSV bulk import (preview + AJAX import in batches)
- Subject-wise / Teacher-wise mark entry grid
- Custom exam creator (replace `first/mid/final` enum with an `exams` table)
- Real PDF compression via Ghostscript in `notices_save.php`
- Replace plain-text-with-HTML CMS editor with Quill / TipTap rich text



---

## Year support across the app

Every relevant table now carries a `year_id` so historical data is preserved as years roll over:

| Table | Year column | Purpose |
|---|---|---|
| `academic_years` | (primary key) | Source of truth — one row per year, one flagged `is_current` |
| `classes`        | `year_id` | A "Grade 5 - A" in 2026 is a different row from "Grade 5 - A" in 2025 |
| `students`       | `year_id` | Enrollment year for that student record |
| `results`        | `year_id` | Same student can have results in multiple years |
| `settings`       | `current_year_id` | Mirror of the flagged row for fast reads |

### Year-aware UX

- **`/admin/years.php`** — CRUD for years; "Set as current" toggle auto-clears the flag on others
- **`/admin/classes.php`** — year filter at top, year selector required when adding/editing
- **`/admin/students.php`** — year filter, year selector in form, year column in list
- **`/admin/mark_entry.php`** — year selector defaults to current; results saved with year_id
- **`/admin/promote.php`** — bulk-promote active students from one year to another, each promotion audit-logged with before/after class+year
- **`/result.php`** — public year selector defaults to current; mobile-app API accepts `year_id`
- **`/students.php`** (public) — year selector defaults to current; cascading sections respect the chosen year
- **All AJAX endpoints** (`students_search`, `sections`, `classes_search`, `public_students`, `public_sections`, `public_classes`, `public_result`) accept optional `year_id`

### Workflow at year-end
1. Admin → Academic Years → "Add Year" → e.g. `2027`
2. Click "Set current" so all dropdowns + new entries default to 2027
3. Admin → Classes → create classes for 2027 (Grade 1, Grade 2, … with their sections)
4. Admin → Promote Students → from 2026, to 2027 → assign new class for each student → Save
5. Each promotion appears in the Audit Log with before/after class_id + year_id

---

## What's still missing (roadmap)

The following are NOT yet implemented; they're the most common asks for a school SaaS that we'll prioritize next:

| Feature | Notes |
|---|---|
| **Attendance** | Daily attendance per student per class, AJAX grid like mark entry, auto-defaults to today + current year |
| **Fees / Payments** | Fee structure per class+year, payment receipts, pending dues, online payment gateway hooks |
| **Routine / Timetable** | Class routine with day+period grid, exam routine with rooms |
| **ID cards / Admit cards** | Print-ready cards with photo, barcode/QR, school logo |
| **Certificates** | Transfer certificate, character certificate, marksheet PDF generator |
| **Parent / Student / Teacher login** | Separate login flows + dashboards (currently everyone uses admin login) |
| **SMS notifications** | Bangladeshi SMS gateway integration (BulkSMSBD, SSL Wireless, etc.) |
| **Library** | Books, issue/return, fines |
| **Hostel** | Rooms, beds, fees |
| **Transport** | Routes, vehicles, students per route |
| **Subject ↔ Class mapping** | `class_subjects` table linking subjects taught in each class+year |
| **Teacher ↔ Class permissions** | Restrict subject teachers to their assigned subjects only |
| **Reports / Analytics** | Class pass-fail %, attendance %, fee collection, top students |
| **Backup / Export** | Database backup .sql download, CSV export per table |
| **Student bulk CSV import** | Preview + AJAX import in batches with per-row error reporting |
| **PDF compression** | Auto-compress notice PDFs via Ghostscript |
| **Rich-text CMS editor** | Replace plain HTML textarea with Quill/TipTap |



---

## Attendance, Routine, Bulk import (latest additions)

### 📋 Daily Attendance (`/admin/attendance.php`)
Pick **Year + Class+Section + Date** → grid loads every active student in that section. Each row has 4 status pills (Present / Absent / Late / Leave) with color-coded toggles + an optional note field. **Bulk-actions**: "All Present" / "All Absent" buttons set every row in one click. **AJAX batch save** via `/api/save_attendance.php` — every individual status change is recorded in the audit log with before/after diff.

Schema: `attendance (student_id, class_id, year_id, date, status, note, marked_by, …)` with `UNIQUE (student_id, date)` so re-saving the same day is an upsert.

### 🗓 Class Routine (`/admin/routine.php` + public `/routine.php`)
Admin: pick **Year + Class+Section** → 7-day × 8-period grid with per-cell **subject + teacher** dropdowns. **Each cell saves instantly** via AJAX on change — no master Save button needed. Backend auto-deletes the cell row when both dropdowns are cleared. Default time slots (08:00–15:00) are pre-filled.

Public `/routine.php?class_id=…&year_id=…` shows a clean read-only routine table with the school's theme — accessible from the homepage's একাডেমিক → ক্লাস রুটিন nav. CORS-enabled API at `/api/public_routine.php` for the future mobile app.

### 📥 Bulk Import Students (`/admin/import_students.php`)
Two-step CSV upload UX:
1. **Step 1 — Upload + Preview:** Pick CSV (max 4 MB) and target year, click "Preview Rows". Server parses, validates each row (roll_no/name/class_name required, gender = male/female/other, status = active/inactive, class_name+section must match an existing class in the target year), and returns the first 10 rows + total + a list of any errors with row numbers.
2. **Step 2 — Confirm Import:** Review the preview + error list (with row numbers), click "Import N valid student(s)". Backend inserts in bulk via prepared statements, **every row gets its own audit log entry** with full data captured.

Sample template at `/assets/students_template.csv` (downloadable from the admin page header). Required columns: `roll_no, name, class_name, section, gender, dob, parent_name, phone, address, status`.

### 🔗 Subject ↔ Class mapping (`class_subjects` table)
Schema is in place (no admin UI yet — added to roadmap): `(class_id, subject_id, teacher_id, year_id)` with unique key, ready for the next iteration's per-teacher subject permission layer.

---

## Updated roadmap (still missing)

- **Subject ↔ Class assignment UI** (table exists, admin page pending)
- **Per-teacher subject permission layer** so subject teachers only see their own subjects in mark entry
- **Fees / Payments** module
- **Attendance reports** (monthly summary per student / class %)
- **ID cards / Admit cards** with QR codes
- **Certificates** generator (transfer, character, marksheet PDF)
- **Parent / Student / Teacher login flows**
- **SMS notifications** (Bangladeshi gateway integration)
- **Library / Hostel / Transport** modules
- **Backup/Export** (DB dump button, CSV export per table)
- **PDF compression** via Ghostscript for notice attachments
- **Rich-text CMS editor** (Quill/TipTap) replacing the HTML textarea
