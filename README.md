# Blood Donation Management System (BDMS)

A simple student-project web app built with **HTML, CSS, JavaScript, PHP, and MySQL**, based on the system's use case, class, EER, activity, and sequence diagrams.

## How it maps to the design

- **Donor** registers, then records a donation → updates local inventory (IA) and central inventory (BDMS), and generates a certificate.
- **Receiver** requests blood at an Intermediate Agency (IA). If stock is available locally, it's supplied immediately; otherwise the request is forwarded to BDMS.
- **BDMS Admin** monitors central inventory, searches other IAs for shortages, and assigns a Transportation Agency to move blood.
- **Transportation Agency** steps a task through Assigned → Collected → In Transit → Delivered → Confirmed, which updates inventory and notifies BDMS.

## ⚠️ Important — this is a PHP app, it will NOT work by double-clicking files

Opening `index.php` or any `.php` file directly from your file system (a `file://...` URL)
will **not** work: PHP code needs to be executed by a PHP server, and the CSS/pages use
relative links that assume they're being served, not opened as local files. You must run this
through a real (or local) web server. Two easy ways to do that:

### Option A — XAMPP / WAMP / MAMP (recommended for beginners)

1. Install a local PHP + MySQL stack (e.g. [XAMPP](https://www.apachefriends.org/)).
2. Copy the entire `bdms/` folder into your server's web root:
   - XAMPP: `C:\xampp\htdocs\bdms` (Windows) or `/Applications/XAMPP/htdocs/bdms` (Mac)
3. Start **Apache** and **MySQL** from the XAMPP control panel (both must show "Running").
4. Open **phpMyAdmin** (`http://localhost/phpmyadmin`):
   - Go to the **Import** tab, choose `database/schema.sql`, and run it. It creates the `bdms` database, all tables, and demo seed data (donors, agencies, staff logins).
5. Open `includes/db_connect.php` and adjust `$DB_USER` / `$DB_PASS` if your MySQL credentials differ from the XAMPP defaults (`root` / empty password).
6. Visit **http://localhost/bdms/index.php** in your browser.

### Option B — PHP's built-in server (fastest if you already have PHP + MySQL installed)

```bash
cd bdms
php -S localhost:8000
```
Then import `database/schema.sql` into your MySQL server (via `mysql -u root -p < database/schema.sql`
or phpMyAdmin), and visit **http://localhost:8000/index.php**.

## Demo login credentials

| Role | URL | Username | Password |
|---|---|---|---|
| BDMS Admin | `admin/login.php` | `admin` | `admin123` |
| IA Staff (City Central) | `ia/login.php` | `ia1` | `ia123` |
| IA Staff (Lalitpur) | `ia/login.php` | `ia2` | `ia123` |
| Transport Staff | `transport/login.php` | `ta1` | `ta123` |

Donors and receivers don't log in — they're identified by the Donor ID / Receiver ID
generated at registration (e.g. `DNR001`, `RCV001`), which they use again on
"My Donation History" / "My Request History".

## Folder structure

```
bdms/
├── index.php                  Landing page
├── donor/
│   ├── register.php            Donor registration
│   ├── donate.php              Record a donation (updates inventory + certificate)
│   └── my_donations.php        Donor's own donation history, by Donor ID
├── receiver/
│   ├── request.php             Blood request (checks local stock, forwards if needed)
│   └── my_requests.php         Receiver's own request history, by Receiver ID
├── ia/
│   ├── login.php / logout.php  IA staff session login
│   └── dashboard.php           IA staff view: local inventory, requests, donors (own agency only)
├── admin/
│   ├── login.php / logout.php  Admin session login
│   ├── dashboard.php           BDMS: central inventory + forwarded requests
│   ├── handle_request.php      Search other IAs, assign transport, or launch a donation campaign
│   ├── campaigns.php           View / close active donation campaigns
│   ├── rebalance.php           Identify excess vs. low-stock agencies, arrange a proactive transfer
│   ├── reports.php             System-wide statistics
│   └── certificate.php         Certificate lookup by Donor ID (printable)
├── transport/
│   ├── login.php / logout.php  Transport staff session login
│   ├── dashboard.php           TA staff: view assigned tasks (own agency only)
│   └── confirm.php             Advance a task through its delivery stages
├── includes/
│   ├── db_connect.php          MySQL connection, ID generator, and login-guard helpers
│   └── header.php / footer.php Shared layout (relative paths, login-aware nav)
├── assets/
│   ├── style.css
│   └── script.js                Client-side form validation
└── database/
    └── schema.sql               Full MySQL schema + seed data (agencies, staff logins, demo inventory)
```

## Notes

- **Passwords are stored in plaintext** in `bdms_admin`, `ia_staff`, and `ta_staff` — deliberately simple for a coursework build. Before using this anywhere real, hash passwords with `password_hash()` / `password_verify()`.
- IDs are generated sequentially (e.g. `DNR001`, `RCV001`) via a helper in `includes/db_connect.php`.
- All blood-type/inventory logic uses SQL transactions so partial updates roll back on error.
- The donation campaign flow doesn't send real notifications (no email/SMS) — it lists the matching donors on-screen, which you can extend with a mail library if needed.
