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
