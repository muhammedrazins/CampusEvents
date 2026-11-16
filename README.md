# CampusEvents: Student Event & Workshop Registration System

A full-stack, responsive web application built for managing university technical workshops, seminars, and student event registrations. Developed as an academic assignment for the **Internet Programming** course.

---

## 🚀 Key Features

* **Live Event Catalog (`index.php`):** Displays upcoming technical workshops with real-time seat tracking calculated dynamically via MySQL aggregation (`LEFT JOIN` & `COUNT()`). Automatically switches to a disabled "SOLD OUT" state when capacity is reached.
* **Server-Side PHP Validation (`register.php`):** Validates all form inputs securely on the server (regex checks for Name, Roll No, and Phone; email filter; department whitelist; global unique roll number enforcement; and live seat capacity verification).
* **Digital Entry Pass (`ticket.php`):** Generates a unique secure pass (`PASS-XXXXXX`) upon successful registration with built-in printing support (`window.print()` & print CSS).
* **Secure Admin Authentication (`login.php`):** Protects administrative routes using PHP sessions (`$_SESSION`) and password verification against salted Bcrypt hashes stored in MySQL.
* **Admin Dashboard & CRUD (`admin.php`):** A session-guarded management panel allowing administrators to add new workshops, delete existing events, filter attendee lists, and remove registrations to free up seats.

---

## 🛠️ Technology Stack

* **Frontend:** Semantic HTML5 (`register.html`, `about.html`), CSS3 (`style.css` with Flexbox, CSS Grid, status badges, and print media rules), Vanilla JavaScript (Fetch API for dropdown synchronization).
* **Backend:** PHP 8.x procedural and modular scripts, native session management (`$_SESSION`), `password_verify()` with Bcrypt.
* **Database:** MySQL / MariaDB via PHP `mysqli` extension with parameterized statements (`prepare()` / `bind_param()`) to prevent SQL Injection.
* **Local Environment:** XAMPP (Apache, PHP, MySQL, phpMyAdmin).

---

## 📂 Project Directory Structure

```text
event_portal/
│
├── database.sql        # MySQL database schema and sample data
├── db.php              # MySQL connection & auto-setup handler
├── style.css           # Unified responsive stylesheet
├── index.php           # Page 1: Live Event Catalog with seat counters
├── about.html          # Informational guidelines & rules page
├── register.html       # Standalone HTML registration form
├── register.php        # Server-side PHP validation & MySQL insertion
├── ticket.php          # Page 3: Digital entry pass & lookup terminal
├── login.php           # Page 4: Admin login authentication
├── admin.php           # Page 5: Protected Admin Dashboard (Add/Delete & CRUD)
└── events_json.php     # JSON endpoint for dynamic dropdown syncing
```

---

## ⚙️ Installation & Local Setup Guide

1. **Install XAMPP:** Download and install XAMPP with Apache and MySQL enabled.
2. **Clone / Place Files:** Move the `event_portal` folder into your local server root directory:
   * **Windows:** `C:\xampp\htdocs\event_portal\`
3. **Import Database:**
   * Open your browser and go to `http://localhost/phpmyadmin/`.
   * Create a database named `campus_events_db` (or import `database.sql` directly via the **Import** tab).
4. **Access the Application:**
   * Open your browser and navigate to: `http://localhost/event_portal/`
   * **Default Admin Credentials:** Username: `admin` | Password: `password`

---

## 🔒 Security Practices Implemented

* **Bcrypt Password Hashing:** Administrator passwords are stored securely using PHP's native `password_hash()` and verified via `password_verify()`.
* **Prepared Statements:** All database queries utilize parameterized prepared statements to neutralize SQL Injection vulnerabilities.
* **Input Sanitization:** User inputs are sanitized using `htmlspecialchars()`, `stripslashes()`, and `trim()` to prevent Cross-Site Scripting (XSS).
* **Strict Session Guard:** Unauthorized users attempting to access `admin.php` directly via URL are immediately intercepted and redirected to `login.php`.
