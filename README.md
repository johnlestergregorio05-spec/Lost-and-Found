# Web-Based Campus Lost and Found Asset Tracking System

Vanilla PHP + MySQL + HTML/CSS/JavaScript (Bootstrap 5 and AJAX) project for IT21WEBPROG / CC21DBSYS.

## Setup (XAMPP / Laragon)

1. Copy the `lostfound` folder into `htdocs` (XAMPP) or `www` (Laragon).
2. Start **Apache** and **MySQL**.
3. Open phpMyAdmin, go to **Import**, choose `database.sql`, and run it. This creates the `campus_lostfound` database with sample data.
4. If your MySQL has a password, edit `includes/config.php` (`DB_USER`, `DB_PASS`).
5. Open `http://localhost/lostfound/`.

The `uploads/` folder must be writable by the web server.

## Demo accounts (password for all: `Password123`)

| Role  | Email              |
|-------|--------------------|
| Admin | admin@campus.edu   |
| Staff | staff@campus.edu   |
| User  | juan@campus.edu    |
| User  | ana@campus.edu     |

## User roles and permissions

| Feature                                   | User | Staff | Admin |
|-------------------------------------------|:----:|:-----:|:-----:|
| Browse / search / filter approved items   | yes  | yes   | yes   |
| Post, edit, delete own reports + photo    | yes  | yes   | yes   |
| Submit a claim with proof                 | yes  | yes   | yes   |
| Approve / reject reports                  |      | yes   | yes   |
| Approve / reject claims, set Claimed status |    | yes   | yes   |
| Edit or delete any report                 |      | yes   | yes   |
| User management (add, role, deactivate, delete) | |     | yes   |
| View audit logs                           |      |       | yes   |

## Requirement checklist

- Landing page, navigation, responsive layout: `index.php`, `includes/header.php`, Bootstrap grid
- Form validation: HTML5 + JS (`assets/js/app.js`) and server-side checks in every form
- Search and filtering: `browse.php`, `ajax_search.php`, `assets/js/browse.js` (AJAX)
- CRUD: items (`item_form.php`, `my_items.php`, `admin/items.php`), claims, users
- Authentication and sessions: `login.php`, `logout.php`, `register.php` (password_hash, session_regenerate_id, CSRF tokens)
- Access restriction: `require_login()` / `require_role()` in `includes/functions.php`
- 3 roles, user management, audit logs: `admin/users.php`, `admin/audit.php`, `audit()` helper
- Photo upload: MIME check, 2 MB limit, random filename, script execution blocked in `uploads/`
- Status update (Claimed / Unclaimed): `admin/items.php` and automatic when a claim is approved (`admin/claims.php`, uses a transaction)

## Database (3NF)

Tables: `roles`, `users`, `categories`, `locations`, `items`, `claims`, `audit_logs`.

Relationships:
- roles 1 : N users
- users 1 : N items (reporter)
- categories 1 : N items
- locations 1 : N items
- items 1 : N claims
- users 1 : N claims (claimant), users 1 : N claims (reviewer)
- users 1 : N audit_logs

## Diagram cheat sheet (draw these in draw.io / Lucidchart)

**DFD Level 0 (context):** one process "Campus Lost and Found System".
External entities: User, Staff, Administrator.
- User to system: registration/login data, lost or found report + photo, search filters, claim request. System to User: item listings, claim status.
- Staff to system: approval decisions, claim decisions, status updates. System to Staff: pending reports, pending claims.
- Administrator to system: user account data, role changes. System to Administrator: user list, audit log reports.

**DFD Level 2 (suggested process to break down: 3.0 Claim Management):**
3.1 Submit claim, 3.2 Review proof, 3.3 Approve/reject claim, 3.4 Update item status, 3.5 Write audit log.
Data stores: D1 Users, D2 Items, D3 Claims, D4 Audit Logs. External entities: User (claimant), Staff.

**VTOC:**
- Public: Home, Browse Items (search/filter), Item Details, Login, Register
- User: Dashboard, Report Item, My Reports (edit/delete), My Claims, Profile
- Staff: all User modules + All Item Reports (approve/reject, status), Claim Requests
- Admin: all Staff modules + User Management, Audit Logs

**ERD:** use the table list above; PKs are the `*_id` columns, FKs are in `database.sql`.
(Tip: in phpMyAdmin, Database > Designer shows the ERD generated from the real tables.)

## GitHub

```
git init
git add .
git commit -m "Initial commit: Campus Lost and Found system"
git branch -M main
git remote add origin <your-repo-url>
git push -u origin main
```

Each member should commit their own parts regularly so the history shows everyone's contribution.
