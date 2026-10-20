# FEATURE AUDIT — Poultry Haven (Poultry E-Commerce Website)

**Audit date:** 2026-10-20
**Scope:** `C:\xampp\htdocs\Poultry` only
**Environment verified live:** Apache on `http://localhost/Poultry/` (HTTP 200), MariaDB 10.4.32 on port 3306, PHP 8.2.12 (pdo_mysql, mysqli, fileinfo, mbstring, session)

---

## 1. WHAT THE PROJECT CONTAINS (verified file listing)

| Path | Role | State found |
|---|---|---|
| `index.php` | Homepage | Present, renders, but no products shown (DB empty) |
| `chickens.php` | Catalog + filters | Present, **2 real bugs** |
| `chicken-details.php` | Product details | Present, works |
| `about.php` | About | Present, 8 lines, very thin |
| `contact.php` | Contact | Present, info only, no form |
| `categories.php` | Public age-category page | **MISSING** (nav "Categories" jumps to `?category=1`) |
| `includes/header.php` | Public nav | Present, missing Admin link + `main.js` |
| `includes/footer.php` | Footer | Present, settings-driven |
| `includes/functions.php` | Helpers | Present, **broken upload URL from admin pages** |
| `includes/database.php` | PDO factory | Correct, connects |
| `includes/config.php` | DB config | Correct (`poultry_db` / `root` / no password) |
| `includes/auth.php` | Session auth + CSRF | Correct |
| `admin/login.php` | Admin login | Works, but **theme colors missing** |
| `admin/dashboard.php` | Stats | Works, ambiguous stat + **no mobile nav** |
| `admin/chickens.php` | Manage list | **Broken "View" link (404)** |
| `admin/add-chicken.php` | Create | Works, no PRG |
| `admin/edit-chicken.php` | Update | Works, no PRG |
| `admin/inventory.php` | Stock | Works, no feedback |
| `admin/categories.php` | Categories | Read-only |
| `admin/settings.php` | Business settings | Works, no password change |
| `admin/create-admin.php` | First-admin bootstrap | Works |
| `admin/logout.php` | Logout | Works |
| `admin/messages.php` | Contact messages | **MISSING** |
| `assets/css/style.css` | Custom CSS | Present, **never linked on admin pages** |
| `assets/js/main.js` | Custom JS | Present, **never loaded by any page** |
| `chickens/*.jpg` | 7 product images + hero | Present, all referenced correctly |
| `uploads/chickens/` | Admin upload target | **MISSING** (auto-created, but never present) |
| `database.sql` | Schema installer | **Not idempotent**, no seed products |

---

## 2. FEATURE CHECKLIST

Status legend: **OK** = verified working · **BUG** = broken/needs repair · **MISSING** = not implemented · **PARTIAL** = works but incomplete

### 2.1 Public website

| # | Feature | Status | Problem identified | Required fix | Test status |
|---|---|---|---|---|---|
| P1 | Professional navigation bar | PARTIAL | No "Admin" link → admin area unreachable from public site; "Categories" links straight to one filter instead of a category page; `main.js` never loaded | Add Admin link, real Categories page, load `main.js`, keep mobile drawer | Pending |
| P2 | Poultry business introduction | OK | — | — | Pending |
| P3 | Hero section with local image | OK | `chickens/hero-chickens.jpg` resolves correctly | — | Pending |
| P4 | Featured chickens | BUG | Section is empty — no product rows exist in `poultry_db.chickens` | Migrate/seed product rows, mark 3 as featured | Pending |
| P5 | Chicken age categories (3) | OK | Rows exist in `categories`, but card images chosen by `strpos()` on the category name (fragile) | Map images by category id | Pending |
| P6 | About section | OK | — | — | Pending |
| P7 | Contact information | PARTIAL | `setting('contact_number')` has no default → renders blank if a setting row is missing | Add default map in `setting()` | Pending |
| P8 | Footer | OK | — | — | Pending |
| P9 | Every nav link/button works | BUG | Admin area not linked from public nav; `admin/chickens.php` "View" link points to a non-existent `admin/chicken-details.php` → 404 | Add Admin nav link; fix View link to `../chicken-details.php` | Pending |
| P10 | Category 1 → 4-month chickens | BUG | Filter selected-state never applies (`$cat === $c['id']` compares int to string) | Cast to int; filter on real `age_months` | Pending |
| P11 | Category 2 → 5-month chickens | BUG | Same selected-state bug; filtering used `category_id` only | Same fix | Pending |
| P12 | Category 3 → 6-month+ chickens | BUG | Same selected-state bug | Same fix | Pending |
| P13 | Age filtering uses DB records | PARTIAL | Filter relied on denormalised `category_id` only | Filter by `age_months` range derived from the category | Pending |
| P14 | Product listing: image | OK | `chickens/*.jpg` all resolve (200) | — | Pending |
| P15 | Product listing: name / breed / age | OK | — | — | Pending |
| P16 | Product listing: price in ₱ | OK | `format_price()` emits `₱` | — | Pending |
| P17 | Product listing: description | MISSING | Card shows no description | Add excerpt on card | Pending |
| P18 | Product listing: availability | OK | Derived from `quantity` | — | Pending |
| P19 | Product listing: View Details button | OK | — | — | Pending |
| P20 | Product data from MySQL | BUG | Query correct, but the table is empty | Seed data | Pending |
| P21 | Product details page | OK | Loads by id, renders all fields | — | Pending |
| P22 | Details: stock + gender/color/weight/characteristics | OK | Conditional rendering works | — | Pending |
| P23 | Search | OK | `LIKE` on name + breed | — | Pending |
| P24 | Filter by age category | BUG | Selected-state bug | Fix | Pending |
| P25 | Filter by breed | OK | `DISTINCT breed` list + exact match | — | Pending |
| P26 | Filter by availability | OK | `quantity > 0` / `= 0` | — | Pending |
| P27 | Sort by price | OK | ASC/DESC present | Add price-low, price-high and newest options | Pending |
| P28 | No cart / checkout / payment | OK | Grep confirms zero cart, checkout, payment or order code | Keep it that way | Pending |

### 2.2 Admin dashboard

| # | Feature | Status | Problem identified | Required fix | Test status |
|---|---|---|---|---|---|
| A1 | Admin login | OK | `password_verify` + session | — | Pending |
| A2 | Secure password verification | OK | bcrypt hash via `password_hash` | — | Pending |
| A3 | Session authentication | OK | `session_regenerate_id` on login | — | Pending |
| A4 | Protected admin pages | OK | `require_login()` on every admin page | — | Pending |
| A5 | Logout | OK | Session destroyed + cookie cleared | — | Pending |
| A6 | Invalid-credential error | OK | Red error box shown | — | Pending |
| A7 | **Admin account exists** | BUG | `poultry_db.admins` has **0 rows** → nobody can log in. The real account sits in `poultryhub_db.admins` | Import the admin row (hash preserved) | Pending |
| A8 | Stat: total listings | OK | `COUNT(*)` | — | Pending |
| A9 | Stat: total available chickens | PARTIAL | Shows `SUM(quantity)` (units) but is labelled like a listing count; ambiguous vs "out-of-stock listings" | Show both "Units in Stock" and "In-Stock Listings" | Pending |
| A10 | Stat: out-of-stock listings | OK | `COUNT(quantity=0)` | — | Pending |
| A11 | Stat: total categories | OK | `COUNT(*)` | — | Pending |
| A12 | Add chicken — all 13 fields | OK | Form covers name, breed, age, price, description, quantity, image, gender, color, weight, health, feeding, special | — | Pending |
| A13 | Add chicken → appears in public catalog | BUG | Insert works, but catalog is empty so it is invisible; also no post-save redirect (refresh re-submits) | Seed data + POST/redirect/GET | Pending |
| A14 | Edit chicken | OK | Update works and re-reads the row | Add POST/redirect/GET + image preview | Pending |
| A15 | Edit → saved to MySQL & public | OK | Single UPDATE + public reads the same table | — | Pending |
| A16 | Delete chicken | OK | `DELETE` + CSRF + `confirm()` | Keep; use shared `data-confirm` handler | Pending |
| A17 | Delete confirmation | OK | Inline `onsubmit` | Move to `main.js` | Pending |
| A18 | Inventory: view | OK | Table lists all chickens | — | Pending |
| A19 | Inventory: update quantities | OK | Bulk UPDATE | Add success/error feedback | Pending |
| A20 | Inventory: identify out-of-stock | PARTIAL | Status badge shown, but no way to filter to out-of-stock only | Add filter + highlight | Pending |
| A21 | Availability auto-updates from stock | OK | Derived from `quantity` everywhere | — | Pending |
| A22 | Categories displayed (3) | OK | All 3 listed with counts | — | Pending |
| A23 | Chickens auto-categorised by age | PARTIAL | `category_for_age()` runs on add/edit, but there is no way to re-categorise existing rows | Add "Re-categorise by age" action | Pending |
| A24 | Settings: business name / phone / email / address / hours | OK | Saved via upsert | Add validation + CSRF error handling | Pending |
| A25 | Settings reflected on public site | PARTIAL | `site_name` constant is hardcoded in `header.php`, so renaming the business does **not** change the header/logo | Read business name from settings | Pending |
| A26 | Admin theme colours | BUG | Admin pages load Tailwind CDN but define **no `tailwind.config`**, so `bg-primary`, `text-primary`, `bg-secondary`, `bg-cream`, `bg-charcoal` do not exist → the admin UI renders unthemed (no green sidebar) | Shared admin layout with the brand palette | Pending |
| A27 | Admin responsive navigation | BUG | Sidebar is `hidden md:block` with **no mobile menu at all** → on a phone the admin dashboard has no navigation whatsoever | Add a mobile drawer + top bar | Pending |
| A28 | Admin code duplication | PARTIAL | Sidebar duplicated across 6 files | Extract `includes/admin-layout.php` | Pending |
| A29 | Change admin password | MISSING | No way to change the password | Add to Settings | Pending |
| A30 | Admin can recover access | PARTIAL | `create-admin.php` only works while the `admins` table is empty; after import it is locked | Allow a logged-in admin to add another admin | Pending |

### 2.3 Database

| # | Check | Status | Problem identified | Required fix | Test status |
|---|---|---|---|---|---|
| D1 | DB configuration correct | OK | `poultry_db` / `root` / empty password — connects successfully | — | Verified |
| D2 | Required tables defined | OK | `admins`, `categories`, `chickens`, `settings` all exist with correct columns | Add `contact_messages` | Verified |
| D3 | Queries match schema | OK | All queries reference real columns | — | Verified |
| D4 | Product records retrieved | BUG | `chickens` table has **0 rows** | Migration `001` (import) + `002` (seed) | Verified |
| D5 | Admin changes persist | PARTIAL | Writes work, but there was no admin to write as | Import admin row | Verified |
| D6 | Category relationships work | OK | FK + `JOIN categories` both valid | — | Verified |
| D7 | Inventory quantities update | OK | Bulk UPDATE works | — | Verified |
| D8 | Featured chickens load | BUG | No rows → empty section | Migration | Verified |
| D9 | `database.sql` re-runnable | BUG | Plain `INSERT` for categories/settings → duplicate-key error on a second import | Use `INSERT … ON DUPLICATE KEY UPDATE` / `INSERT IGNORE` | Pending |
| D10 | Legacy data stranded | BUG | `poultryhub_db` holds 7 chickens + 1 admin with an **incompatible schema** (`image` not `image_path`, no `category_id`, no `categories` table, admin has no `email`) | Safe cross-database migration | Verified |

### 2.4 Images & assets

| # | Check | Status | Problem identified | Required fix | Test status |
|---|---|---|---|---|---|
| I1 | All 7 product images + hero exist | OK | 8 JPGs present in `chickens/` | — | Verified |
| I2 | Filenames preserved | OK | Untouched | — | Verified |
| I3 | Product image paths | BUG | `UPLOAD_URL = 'uploads/chickens/'` is resolved relative to the **admin** folder on admin pages → uploaded images 404 in the admin table | Root-relative resolver `image_url()` | Pending |
| I4 | Legacy image values | BUG | Stored as bare filenames (`4month-rhode-island-red.jpg`) while the files live in `chickens/` → 404 if imported as-is | Migration prefixes `chickens/` | Verified |
| I5 | `uploads/chickens/` target dir | MISSING | Never created (only on first upload) | Create it | Pending |
| I6 | `assets/js/main.js` reachable | BUG | Never referenced by any page → dead file | Load it with a relative path | Pending |
| I7 | `assets/css/style.css` reachable | PARTIAL | Linked only from the public header; admin pages ignore it | Wire into the admin layout | Pending |
| I8 | Page weight | PARTIAL | Hero is 5.6 MB, three product images > 3 MB → slow first paint on localhost | `loading="lazy"`, `decoding="async"`, fixed dimensions | Pending |
| I9 | No external image URLs | OK | All images local | Keep local | Verified |

### 2.5 localhost / XAMPP

| # | Check | Status | Problem identified | Required fix | Test status |
|---|---|---|---|---|---|
| L1 | Apache serves the project | OK | `http://localhost/Poultry/index.php` → HTTP 200 | — | Verified |
| L2 | PHP include paths | OK | `includes/…` / `../includes/…` resolve correctly | — | Verified |
| L3 | DB connection | OK | PDO connects to `poultry_db` | — | Verified |
| L4 | Form actions | OK | All point at the right script | — | Verified |
| L5 | Redirects | OK | `login.php` → `dashboard.php`, `logout.php` → `login.php` | — | Verified |
| L6 | Session handling | OK | No "headers already sent" warnings observed | — | Verified |
| L7 | SQL errors | OK | No SQL errors on any page | — | Verified |
| L8 | Image loading | PARTIAL | See I3/I4 | Fix paths | Pending |
| L9 | PHP error log clean | OK | No notice/warning output on any page | — | Verified |
| L10 | `.htaccess` not required | OK | Works without rewrites | — | Verified |

---

## 3. ROOT-CAUSE SUMMARY

1. **Empty target database (blocker).** `database.sql` created the schema but never inserted product rows, and the previous session's real data was written to a differently-named database (`poultryhub_db`) with an older schema. The configured `poultry_db` therefore has 3 categories, 6 settings, **0 chickens and 0 admins** → the public catalog is empty and nobody can log in.
2. **Broken admin link.** `admin/chickens.php` links to `admin/chicken-details.php`, which was never created → 404.
3. **Broken image resolution for uploads.** Uploaded images are stored as `uploads/chickens/x.jpg` and rendered with that literal string; from `/Poultry/admin/` the browser requests `/Poultry/admin/uploads/chickens/x.jpg` → 404.
4. **Filter selected-state bug.** `if ($cat === $c['id'])` compares `int` with the PDO `string`, so the age-category dropdown never shows the active category.
5. **Admin pages lost the brand theme.** The Tailwind CDN is loaded on admin pages without a `tailwind.config`, so every `bg-primary` / `text-primary` / `bg-cream` class is undefined.
6. **Admin pages are unusable on mobile.** The sidebar is `hidden md:block` and no mobile menu exists.
7. **Settings were not wired to the brand name.** `SITE_NAME` was a hardcoded constant in the header, so an admin renaming the business had no effect on the public header.
8. **Dead assets.** `assets/js/main.js` and `assets/css/style.css` were never loaded by the admin area, and `main.js` was not loaded anywhere.
9. **`database.sql` was not idempotent** → re-importing through phpMyAdmin raised duplicate-key errors.
10. **Missing pages.** No public age-category page and no admin view for contact messages.

---

## 4. FIX PLAN (what will be changed)

| Layer | Action |
|---|---|
| SQL | `migrations/001_import_legacy_data.sql` (cross-DB import, no deletes), `migrations/002_seed_catalog.sql` (idempotent seed), `migrations/003_add_contact_messages.sql` (additive table). `database.sql` rewritten to be re-runnable and to seed the demo catalog. |
| Core | `includes/config.php` (paths/constants), `includes/functions.php` (root-relative `image_url()`, settings cache + defaults, flash messages, upload hardening, age-range helper), `includes/header.php` (Admin link, Categories page, `main.js`), `includes/footer.php` (settings-driven footer). |
| Public | `index.php`, `chickens.php`, `chicken-details.php`, `about.php`, `contact.php` rewritten/extended; new `categories.php`. |
| Admin | New `includes/admin-layout.php` (brand Tailwind config + desktop sidebar + mobile drawer + shared flash); `login.php`, `dashboard.php`, `chickens.php`, `add-chicken.php`, `edit-chicken.php`, `inventory.php`, `categories.php`, `settings.php`, `create-admin.php`, `messages.php` all rebuilt on the shared layout. |
| Assets | `assets/js/main.js` (confirm dialogs, image preview, mobile nav, filter auto-submit, broken-image fallback), `assets/css/style.css` (extended, same palette). |
| Docs | `README.md` updated with the exact XAMPP steps, `FEATURE_AUDIT.md`, `TEST_REPORT.md`. |

---

## 5. FINAL VERIFICATION (after the fixes)

Every audit item above was re-checked against the running site on
`http://localhost/Poultry/` with an automated HTTP test suite (46 checks,
**46 PASS / 0 FAIL**). Full evidence is in `TEST_REPORT.md`.

| Audit IDs | Final state | Verified by |
|---|---|---|
| P1, P9 | Navigation bar complete with a working mobile drawer; Admin link added to header and footer; every homepage link returns HTTP < 400 | TEST 1, 1b, 20, 20b |
| P2, P3, P6, P7, P8 | Homepage renders hero image, business intro, categories, featured birds, about and contact; footer reads live settings | TEST 1 |
| P4, P20, P14 | 7 chickens now present in `poultry_db.chickens` (imported from the legacy DB and/or seeded); the catalog shows them | TEST 2, 2b |
| P5, P13 | Category cards show live counts and map to the correct local image; filtering runs on the real `age_months` column | TEST 3, 3b |
| P10–P12 | All three age categories return exactly the right chickens with no leakage, and the filter dropdown shows the active category | TEST 3 |
| P15–P19 | Cards show image, name, breed, age, ₱ price, description, availability and a View Details button | TEST 2 |
| P21, P22 | Details page shows image, name, breed, age, price, description, stock, gender, colour, weight and care characteristics; unknown ids give a friendly page | TEST 4, 4b |
| P23–P27 | Search, breed filter, availability filter, price sort and age sort all verified against live rows | TEST 5, 5b, 6a–6f |
| P28 | No cart / checkout / payment code anywhere (15 pages scanned, 0 hits) | TEST 20g |
| A1–A6 | Login, bcrypt verification, session auth, protected pages, logout and the invalid-credential error all work | TEST 7a–7d, 18, 19 |
| A7 | The administrator row was imported from `poultryhub_db` with its original bcrypt hash, so the existing password still works | TEST 7b |
| A8–A11 | Dashboard shows total listings, available chickens, in-stock listings, out-of-stock listings, categories and breeds — the total matches the database | TEST 7c, 7d |
| A12–A15 | Add and edit save every field, auto-categorise by age and appear instantly on the public site | TEST 8–8c, 10, 11 |
| A16, A17 | Delete works with a confirmation dialog, removes the row and cleans up the uploaded file | TEST 14, 14b, 15 |
| A18–A21 | Inventory view, bulk quantity updates, out-of-stock filtering and automatic availability all verified | TEST 12, 13 |
| A22, A23 | The three categories are displayed and a "re-categorise all by age" repair action was added | TEST 7c, admin/categories.php |
| A24, A25 | Settings save to MySQL and the business name now drives the header, footer, homepage and contact page | TEST 16, 17 |
| A26 | The admin area now uses the shared layout with the full brand palette | sanity sweep — palette present in served HTML |
| A27 | The admin sidebar becomes a slide-in drawer with a backdrop on phones | TEST 20b |
| A28 | The duplicated sidebar markup was replaced by `includes/admin-layout.php` | source review |
| A29 | Change-password screen added under Admin → Settings | source review |
| A30 | `create-admin.php` now also lets a signed-in admin add further accounts | source review |
| D1–D8 | Config, tables, queries, retrieval, persistence, relationships, inventory updates and featured loading all verified against live MySQL | TEST 7d, 8, 10, 12 |
| D9 | `database.sql` re-runnable (upserts + anti-join seeding) | source review + migration re-run |
| D10 | Legacy data imported without deleting anything | TEST 8–15; `poultryhub_db` still holds its original 7 chickens and 1 admin |
| I1, I2 | All 8 original images intact, original filenames and file sizes | filesystem listing |
| I3, I4 | Image paths resolved through `image_url()` (relative to the page) — admin uploads return HTTP 200 from both the admin and public pages | TEST 8b, 8c, 6g |
| I5 | `uploads/chickens/` created and hardened with `uploads/.htaccess` | TEST 8b (upload still serves) |
| I6, I7 | `assets/js/main.js` and `assets/css/style.css` now load on both the public and admin pages | TEST 20c, 20d |
| I8 | `loading="lazy"`, `decoding` hints and explicit width/height added to product images | source review |
| I9 | Zero external image URLs | sanity sweep |
| L1–L10 | Site serves over Apache; includes, DB connection, form actions, redirects, sessions, SQL and images all clean; 0 PHP warnings across 15 pages | full sweep |
