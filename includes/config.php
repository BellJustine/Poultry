<?php
// includes/config.php
// Central configuration for the Poultry Haven application.

/* ---------- Database ---------- */
define('DB_HOST', 'localhost');
define('DB_NAME', 'poultry_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/* ---------- Application ---------- */
define('SITE_NAME', 'Poultry Haven');                                   // fallback
define('SITE_TAGLINE', 'Farm-Fresh Quality, Raised with Care.');       // fallback
define('APP_ROOT', dirname(__DIR__));                                  // physical project root
define('MIN_AGE_MONTHS', 4);                                          // youngest sellable category
define('DEFAULT_IMAGE', 'chickens/4month-brown-chicken.jpg');          // local fallback picture

/* ---------- Uploads ---------- */
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'chickens' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', 'uploads/chickens/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
