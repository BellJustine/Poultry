# TEST REPORT — Poultry Haven

**Test date:** 2026-10-20
**Application URL:** http://localhost/Poultry/
**Environment:** Apache (XAMPP) on port 80 · MariaDB 10.4.32 on port 3306 · PHP 8.2.12
**Method:** automated HTTP test harness (cURL) against the live site, plus a static PHP lint of every source file and a rendered-markup sweep of all 15 pages.

---

## 1. RESULT SUMMARY

| Metric | Value |
|---|---|
| Tests executed | **46** |
| PASS | **46** |
| FAIL | **0** |
| NOT TESTED | **0** |
| PHP files linted (`php -l`) | 24 / 24 clean |
| Pages with PHP warnings / notices / fatal errors | 0 / 15 |
| External image URLs on any page | 0 |

---

## 2. THE 20 REQUIRED TESTS

| # | Test | Result | Evidence |
|---|---|---|---|
| 1 | **Open the homepage** | **PASS** | HTTP 200. Verified in the response: navigation bar, "Healthy Chickens" hero with `chickens/hero-chickens.jpg`, "Explore Chickens by Age" (3 categories), "Our Featured Chickens" (3 featured records rendered), "About Our Farm", "Contact Us" panel and the footer. All 13 links on the page were fetched individually — none returned an error. |
| 2 | **Navigate to the chicken catalog** | **PASS** | `chickens.php` → HTTP 200. 7 product cards, each with image, name, breed, age, ₱ price, description excerpt, availability badge, stock count and a "View Details" button. 14 detail links (image + button per card). |
| 3 | **Select each age category** | **PASS** | `?category=1` → exactly the two 4-month birds (Rhode Island Red Pullet, Brown Farm Chicken); `?category=2` → exactly the two 5-month birds (Plymouth Rock Hen, Farm Raised Chicken); `?category=3` → exactly the three 6-month+ birds (Adult Brahma Hen, Mixed Breeds Group, Native Rooster). No cross-category leakage, and the correct `<option selected>` is rendered in the filter dropdown (the old int/string comparison bug). |
| 4 | **Open a chicken's product details** | **PASS** | `chicken-details.php?id=1` → HTTP 200 showing the picture, "Rhode Island Red Pullet", breed "Rhode Island Red", age "4 months", price "₱350.00", full description, "Stock on Hand 8 heads", gender "Female", colour "Reddish-brown", weight "1.60 kg", health / feeding / characteristics blocks and a "Related Chickens" section. Unknown id (`?id=99999`) returns a friendly "Chicken not found" page instead of a fatal error. |
| 5 | **Search for a chicken** | **PASS** | `?q=Plymouth` returns only "Plymouth Rock Hen"; `?q=Brahma` returns only "Adult Brahma Hen". Search covers name, breed, description, gender and colour. A no-match search shows "No chickens match your filters". |
| 6 | **Filter products** | **PASS** | Breed filter (`?breed=Brahma`) → only Adult Brahma Hen. Availability filter `?avail=out` → only Native Rooster (stock 0); `?avail=available` → the six in-stock birds only. Sort `?sort=price_asc` → first card is ₱300.00; `?sort=price_desc` → first card is ₱850.00; `?sort=age_asc` → first card is the 4-month bird; `?sort=age_desc` → first card is the 10-month Native Rooster. All filters run against live MySQL records. |
| 7 | **Log in as administrator** | **PASS** | Wrong password → "Invalid credentials. Please check your username and password." Correct credentials (`admin` / `admin123`) → HTTP 302 to `admin/dashboard.php` with a PHP session cookie set and the session id regenerated on login. |
| 8 | **Add a new chicken** | **PASS** | Posted name, breed, age, price, description, quantity, image file, gender, colour, weight, health notes, feeding information, special characteristics and the featured flag with a CSRF token. The row was written to MySQL (id 9) with `category_id = 2` — auto-derived from the age of 5 months — and `image_path = uploads/chickens/chicken_20261020_170256_4590ddcb.jpg`. The uploaded file is stored inside the project at `uploads/chickens/`. |
| 9 | **Verify the chicken appears in the public catalog** | **PASS** | Immediately after saving, `chickens.php` shows "ZZ TEST CHICKEN" with "₱999.00", and `chickens.php?category=2` lists it under 5-month-old chickens. The uploaded picture also resolves at HTTP 200 on the public page. |
| 10 | **Edit the chicken** | **PASS** | Edited name, breed, age (5 → 8 months), price (999.00 → 1234.50) and quantity (7 → 3) were written to MySQL. `category_id` was automatically re-derived to 3 (6-month category) from the new age. |
| 11 | **Verify changes appear publicly** | **PASS** | The public catalog shows "ZZ TEST CHICKEN EDITED" and the details page shows "₱1,234.50" and "Edited Test Breed" — reading straight from MySQL, no caching layer. |
| 12 | **Update inventory** | **PASS** | Posting a new quantity through `admin/inventory.php` set `quantity = 0` in the database (verified by direct SQL read). |
| 13 | **Verify availability updates correctly** | **PASS** | With quantity 0 the chicken disappears from `?avail=available`, appears under `?avail=out`, and the details page renders the "Out of Stock" badge. Availability is always derived from the stock column, never stored separately. |
| 14 | **Delete the test chicken** | **PASS** | The delete action (POST + CSRF token + `data-confirm` confirmation dialog) removed the row — verified by direct SQL read (0 rows). The uploaded image file was also removed from `uploads/chickens/`. |
| 15 | **Verify the chicken disappears from the catalog** | **PASS** | `chickens.php` no longer contains "ZZ TEST CHICKEN"; the database contains 0 rows for that id; the final catalog is back to the original 7 real records. |
| 16 | **Update business settings** | **PASS** | Saving Business Name, Contact Number, Email, Address, Business Hours and the tagline wrote all six values to the `settings` table in MySQL. |
| 17 | **Verify settings appear on the public website** | **PASS** | After saving "ZZ Test Poultry Farm" / "+63 900 111 2222" / "Test Hours 9:00 AM - 6:00 PM" / "123 Test Street, Test Town" / "test@poultry.local", the new values appeared in the site header, the footer, the homepage contact panel and `contact.php`. The original values were restored to the database afterwards. |
| 18 | **Log out** | **PASS** | `admin/logout.php` destroys the session, clears the session cookie and redirects (HTTP 302) to the login page with a "You have been logged out successfully" notice. |
| 19 | **Verify protected admin pages cannot be accessed without authentication** | **PASS** | With the session cleared, all 8 protected pages (`dashboard.php`, `chickens.php`, `add-chicken.php`, `edit-chicken.php`, `inventory.php`, `categories.php`, `settings.php`, `messages.php`) returned HTTP 302 → `login.php`. No page leaked its content. |
| 20 | **Check the website on mobile, tablet and desktop screen sizes** | **PASS** | Every public and admin page carries `<meta name="viewport" content="width=device-width, initial-scale=1.0">` plus `sm:` / `md:` / `lg:` responsive breakpoints (grids collapse to 1 column, the public nav becomes a hamburger drawer, the admin sidebar becomes a slide-in drawer with a backdrop). No horizontal overflow classes were introduced. See the manual checks in §5 — the final visual confirmation on real devices is left to you. |

---

## 3. ADDITIONAL VERIFICATION TESTS (26)

| Test | Result | Evidence |
|---|---|---|
| 1b — every homepage link resolves | PASS | 13 links fetched, all HTTP < 400 |
| 2b — one card per listing | PASS | 7 listings → 7 cards |
| 3b — public age-category page | PASS | `categories.php` lists all three categories with live counts |
| 4b — unknown product id | PASS | Friendly "Chicken not found" page, HTTP 200 |
| 5b — search by breed | PASS | Only the matching breed is returned |
| 6f — empty result set | PASS | "No chickens match your filters" + reset button |
| 6g — all product images load | PASS | 7 product images fetched, all HTTP 200 |
| 7a — invalid credentials rejected | PASS | Error message displayed |
| 7b — valid credentials + session | PASS | 302 → dashboard, session cookie issued |
| 7c — dashboard statistics | PASS | Total Listings, Available Chickens, In-Stock Listings, Out-of-Stock Listings, Total Categories, Total Breeds |
| 7d — dashboard number == database number | PASS | Both 7 |
| 8b — uploaded image path in admin | PASS | `../uploads/chickens/…` resolves to `/Poultry/uploads/chickens/…` HTTP 200 (this was the 404 bug) |
| 8c — uploaded image on public catalog | PASS | `uploads/chickens/…` HTTP 200 |
| 9b — new chicken in its age category | PASS | Listed under `?category=2` |
| 14b — uploaded file cleaned up on delete | PASS | File no longer on disk |
| 20b — admin mobile navigation | PASS | `adminMenuBtn` toggle + `adminSidebar` drawer + backdrop present |
| 20c — custom CSS/JS loaded | PASS | `assets/css/style.css` + `assets/js/main.js` referenced |
| 20d — asset files return HTTP 200 | PASS | js 200, css 200 |
| 20e — public contact form stores a message | PASS | Row written to `contact_messages` |
| 20f — administrator can read the message | PASS | Shown in `admin/messages.php` |
| 20g — no cart / checkout / payment | PASS | 15 pages scanned for "add to cart / shopping cart / checkout / buy now / payment / place order" → 0 hits |
| PHP lint (24 files) | PASS | `php -l` clean on every file |
| PHP warning sweep (15 pages) | PASS | 0 warnings, notices, deprecations or fatal errors |
| External image hosts | PASS | 0 — every `<img>` points at a local file |
| HTML structure (6 public pages) | PASS | DOCTYPE, `</html>`, `<nav>`, `<footer>` and viewport present on all |
| Brand palette in served HTML | PASS | `#234B36`, `#477A53`, `#F8F6EF`, `#D8A441`, `#252B26` all emitted |

---

## 4. TEST DATA HANDLING

No real business data was destroyed.

* The chicken created for tests 8–15 was named **`ZZ TEST CHICKEN (temporary)`** and then `ZZ TEST CHICKEN EDITED`; it was deleted in test 14, so the catalog is back to the original 7 real records.
* The business settings changed in tests 16–17 were written with clearly identifiable test values (`ZZ Test Poultry Farm`, `+63 900 111 2222`, `123 Test Street, Test Town`, `test@poultry.local`) and were **restored to the original values** immediately afterwards. The final `settings` table matches the pre-test state.
* The contact message used in tests 20e/20f used the subject `ZZ automated test message` and was deleted afterwards; the `contact_messages` table is empty.
* The uploaded test image was removed from disk by the delete action.
* The legacy database `poultryhub_db` was only ever **read** from (SELECT). Final state: 7 chickens, 1 admin — unchanged.
* Final database state: 7 chickens, 3 categories, 6 settings, 1 admin, 0 contact messages — identical to the real business data.

---

## 5. CHECKS YOU MUST STILL DO MANUALLY

Automated checks confirm behaviour and markup, but a human still needs to confirm the **visual** result on real devices:

1. Open `http://localhost/Poultry/` in Chrome, press **F12 → Toggle device toolbar (Ctrl+Shift+M)** and check the layouts at **375 px (iPhone SE)**, **768 px (iPad portrait)** and **1440 px (desktop)**.
2. On a phone width, tap the **☰** button in the top-right of the header — the menu must slide open, and every link must work.
3. On a phone width, log in to `http://localhost/Poultry/admin/login.php` and tap the **☰** button in the green admin bar — the sidebar must slide in over a dimmed backdrop and every admin link must work.
4. Confirm the five brand colours are visible: green `#234B36`, lighter green `#477A53`, cream `#F8F6EF`, gold `#D8A441`, charcoal `#252B26`.
5. Check the page weight — the hero photo is 5.6 MB and three product photos are over 3 MB, so the first load takes a few seconds. The images are used exactly as they were supplied and were **not** replaced, resized or renamed.

---

## 6. KNOWN LIMITATIONS / DELIBERATE CHOICES

* **No image optimisation.** The XAMPP PHP build has neither GD nor ImageMagick, so thumbnails could not be generated without rewriting the supplied images, which was not permitted. `loading="lazy"`, `decoding` hints and explicit dimensions were added instead.
* **Tailwind is loaded from the CDN** (`cdn.tailwindcss.com`), as in the original project. The site therefore needs internet access for the framework; the fonts also come from Google Fonts. The business content, images and JavaScript are fully local.
* **No pagination.** The catalog shows every match. With more than a few hundred listings, pagination should be added.
* **The default admin password is `admin123`.** Change it from *Admin → Settings → Change Password* before showing the project to anyone else.
* **`uploads/chickens/` must stay writable** by Apache. On Windows/XAMPP this is the default; on Linux use `chmod 755 uploads/chickens`.
