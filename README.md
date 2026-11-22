# ClubQuest — College Club Finder

A full-stack **Core PHP 8 + MySQL** web app that helps students discover college clubs by interest, skill, department and career goal, request membership, and register for events. Includes admin and club-coordinator dashboards.

No frameworks. PDO prepared statements, CSRF protection, role-based access control, dark/light mode, responsive UI.

## Run with XAMPP

1. Install XAMPP (PHP 8.0+) and start **Apache** and **MySQL** from the control panel.
2. Copy this folder to `C:\xampp\htdocs\college-club-finder` (macOS/Linux: `/opt/lampp/htdocs/`).
3. Open **http://localhost/phpmyadmin** → *Import* → choose `database.sql` → *Go*.
   (or run `mysql -u root < database.sql`). This creates the `college_club_finder` database with demo data.
4. Check `config/database.php` — defaults match XAMPP (`root`, empty password). Change if yours differ.
5. Visit **http://localhost/college-club-finder/**.

The base URL is detected automatically. If the folder name differs or you use a virtual host and links break, set `BASE_URL` manually at the top of `config/database.php`.

Make sure `uploads/` is writable (logo uploads). On Windows this works by default.

## Demo accounts

| Role | Login page | Email | Password |
|---|---|---|---|
| Admin | `/auth/admin-login.php` | admin@college.edu | Admin@123 |
| Coordinator (CodeCraft) | `/auth/admin-login.php` | coord1@college.edu | Coord@123 |
| Coordinator (NeuralNest / RoboForge / Shutterbugs) | `/auth/admin-login.php` | coord2 / coord3 / coord4 @college.edu | Coord@123 |
| Student | `/auth/login.php` | student1@college.edu … student6@college.edu | Student@123 |

## Features

**Students** — register / login / logout (hashed passwords), profile with skills, career goal and interest selection, club discovery with cards, filter sidebar (category, department, skill, sort), search with live suggestions, *popular* and *recently added* clubs, personalised **"For you"** suggestions (SQL fetches clubs + interests, PHP scores category/skill/department matches), club details page, join request with Pending / Approved / Rejected status, cancel / leave / re-apply, **My Clubs** dashboard, upcoming events with one-tap AJAX registration (capacity-aware), notifications.

**Admin** — statistics + charts, add/edit/delete clubs (logo upload), manage categories, manage students (block/unblock/delete) and create coordinator accounts, approve/reject requests, view/remove members, create/edit/delete events and see registrants.

**Coordinator** — overview, members, applications (approve/reject only if the admin enabled *"Coordinator may approve"* for the club), create/edit events, update club information. Coordinators can only touch their own club.

## Project structure

```
config/database.php      PDO connection + settings
includes/                auth (sessions, RBAC), functions (CSRF, helpers, uploads), queries, components, layouts
includes/dash/           shared admin/coordinator views (requests, members, events, forms)
auth/                    login, register, admin-login, logout
student/  admin/  coordinator/   role-protected pages
api/                     JSON endpoints (live search, event registration)
assets/css, assets/js    design system + front-end behaviour (no build step)
uploads/                 club logos (PHP execution disabled via .htaccess)
index.php clubs.php club-details.php events.php database.sql
```

## Security notes

PDO prepared statements everywhere · `password_hash` / `password_verify` · session cookie `HttpOnly` + `SameSite=Lax`, session ID regenerated on login · CSRF token on every POST (and `X-CSRF-Token` for fetch) · server-side validation, output escaped with `htmlspecialchars` · role checks on every protected page, plus ownership checks for coordinators · login throttling (5 failures → 5 min) · uploads validated by real MIME type, random filenames, size limit · foreign keys with cascade rules.

## Notes

Icons (Font Awesome) and fonts (Google Fonts) load from CDNs, so an internet connection is needed for them. Charts are dependency-free SVG.
Before deploying for real: change all demo passwords, set a DB password, and serve over HTTPS.
