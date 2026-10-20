# Poultry Haven

A poultry product catalog and inventory management website.
**No shopping cart, no checkout, no payment processing** — this project lists chickens and manages stock.

---

## Technologies

* HTML5, Tailwind CSS (CDN), Vanilla JavaScript
* PHP 8+, MySQL / MariaDB (PDO with prepared statements)
* XAMPP (Apache + MySQL)

## Brand palette

| Colour | Hex |
|---|---|
| Primary Green | `#234B36` |
| Secondary Green | `#477A53` |
| Warm Cream | `#F8F6EF` |
| Golden Yellow | `#D8A441` |
| Dark Charcoal | `#252B26` |

---

## Setup on XAMPP

1. Copy the **`Poultry`** folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, create/select the database `poultry_db`
   (or just import `database.sql`, which creates it for you).
4. Import the schema:
   * `phpMyAdmin` → tab **Import** → choose `database.sql` → **Go**.
   * The script is **idempotent**: running it again will not delete or duplicate anything.
5. Open the site:
   * Public site → <http://localhost/Poultry/>
   * Admin login → <http://localhost/Poultry/admin/login.php>

### Default administrator

| Username | Password |
|---|---|
| `admin` | `admin123` |

Change it after logging in: **Admin → Settings → Change Password**.
If the `admins` table is empty, create the first account at
<http://localhost/Poultry/admin/create-admin.php>.

---

## Migrations (for an existing installation)

If you already have data, apply the files in `migrations/` in order through
**phpMyAdmin → select `poultry_db` → Import**:

| File | Purpose | Safety |
|---|---|---|
| `migrations/001_import_legacy_data.sql` | Copies the 7 chickens, the admin account and the business settings out of the older `poultryhub_db` database into `poultry_db`, mapping the old columns (`image`, no `category_id`) onto the current schema | Read-only on `poultryhub_db`; pure INSERT on `poultry_db`; no DROP / TRUNCATE / DELETE; skips rows that already exist |
| `migrations/002_seed_catalog.sql` | Inserts the 7 demo chickens and the 3 age categories if they are missing | Pure INSERT, skips existing rows |
| `migrations/003_add_contact_messages.sql` | Creates the `contact_messages` table used by the Contact Us form | Creates one new table only |

None of them delete existing records, drop tables or reset the database.

---

## Project structure

```
Poultry/
├── index.php              Homepage (hero, age categories, featured chickens, about, contact)
├── chickens.php           Catalog with search, filters and sorting
├── chicken-details.php    Product details page
├── categories.php         Public age-category overview
├── about.php              About the farm
├── contact.php            Contact details + working message form
├── database.sql           Full schema + starter data (re-runnable)
├── FEATURE_AUDIT.md       Audit of the original project
├── TEST_REPORT.md         Test results
├── includes/
│   ├── config.php         Database and application constants
│   ├── database.php       PDO connection factory
│   ├── auth.php           Sessions, login, CSRF tokens
│   ├── functions.php      Helpers (settings, images, uploads, flash messages)
│   ├── header.php         Public navigation
│   ├── footer.php         Public footer
│   └── admin-layout.php   Shared admin shell (sidebar + mobile drawer)
├── admin/
│   ├── login.php          Administrator login
│   ├── logout.php         End session
│   ├── dashboard.php      Statistics overview
│   ├── chickens.php       Manage listings (view / edit / delete / feature)
│   ├── add-chicken.php    Create a chicken
│   ├── edit-chicken.php   Update a chicken
│   ├── inventory.php      Bulk stock control
│   ├── categories.php     Age categories + re-categorise by age
│   ├── messages.php       Contact form messages
│   ├── settings.php       Business information + change password
│   └── create-admin.php   Create the first / an extra administrator
├── assets/
│   ├── css/style.css      Custom styles
│   └── js/main.js         Confirm dialogs, image preview, mobile nav
├── chickens/              Product photographs (8 local JPG files)
├── migrations/            Safe, re-runnable SQL migrations
└── uploads/chickens/      Pictures uploaded through the admin panel
```

---

## Features

### Public website
* Sticky navigation with a working mobile menu, and a link into the admin area
* Hero section using a local photograph, business introduction
* The three required age categories with live listing counts
* Featured chickens section
* Catalog with **search**, **filter by age category / breed / availability** and
  **sort by name, price, age or newest**
* Product details with image, name, breed, age, price, description, stock,
  gender, colour, weight, health notes, feeding information and special
  characteristics, plus related chickens
* Age categories are derived from the real `age_months` column in MySQL
* Contact page with a working message form
* About page with live business information
* Footer driven by the business settings in the database

### Admin dashboard
* Session-based login with bcrypt password hashing, CSRF protection on every
  POST and logout that fully destroys the session
* Dashboard statistics read live from MySQL: total listings, available
  chickens, in-stock listings, out-of-stock listings, categories and breeds
* Add / edit / delete chickens — new records appear in the public catalog
  immediately
* Inventory management with bulk quantity updates, an out-of-stock filter and
  automatic availability status
* Age-category management with a "re-categorise all by age" repair action
* Business settings (name, contact number, email, address, hours, tagline)
  that are reflected across the public website
* Change-password screen and the ability to add extra administrator accounts
* Inbox for contact form messages

---

## Notes

* All images are local files inside `chickens/`; there are no external image URLs.
* Every database query uses PDO prepared statements.
* The catalog is a **catalog only** — there is deliberately no cart, checkout,
  buy-now button, payment method or order processing anywhere in the codebase.
* The default admin password is `admin123`; change it before demonstrating.
