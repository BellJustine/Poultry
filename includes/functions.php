<?php
// includes/functions.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/* =====================================================================
 * Output helpers
 * ===================================================================== */

function esc($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function format_price($n): string {
    return '₱' . number_format((float)$n, 2);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function excerpt(?string $text, int $limit = 110): string {
    $text = trim(preg_replace('/\s+/', ' ', (string)$text));
    if ($text === '') return '';
    if (mb_strlen($text) <= $limit) return $text;
    return rtrim(mb_substr($text, 0, $limit), " ,.;:-") . '…';
}

/* =====================================================================
 * Age categories
 * ===================================================================== */

function category_for_age(int $age): int {
    if ($age <= 4) return 1;
    if ($age === 5) return 2;
    return 3;
}

/** Inclusive [min, max] month range covered by a category id. */
function category_age_range(int $categoryId): array {
    switch ($categoryId) {
        case 1:  return [4, 4];
        case 2:  return [5, 5];
        case 3:  return [6, 999];
        default: return [MIN_AGE_MONTHS, 999];
    }
}

function category_name(int $categoryId): string {
    $names = [
        1 => '4-Month-Old Chickens',
        2 => '5-Month-Old Chickens',
        3 => '6-Month-Old Chickens and Older',
    ];
    return $names[$categoryId] ?? 'Other Chickens';
}

/** Picture used for a category card on the public pages (local files only). */
function category_image(int $categoryId): string {
    $images = [
        1 => 'chickens/4month-brown-chicken.jpg',
        2 => 'chickens/5month-farm-chicken.jpg',
        3 => 'chickens/6month-adult-chicken.jpg',
    ];
    return $images[$categoryId] ?? DEFAULT_IMAGE;
}

/* =====================================================================
 * Availability
 * ===================================================================== */

function availability_label(int $qty): string {
    return $qty > 0 ? 'Available' : 'Out of Stock';
}

function availability_class(int $qty): string {
    return $qty > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
}

function availability_dot(int $qty): string {
    return $qty > 0 ? 'bg-green-500' : 'bg-red-500';
}

/* =====================================================================
 * Settings (cached, with safe defaults)
 * ===================================================================== */

function &settings_cache(): array {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['setting_key']] = (string)$row['setting_value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache;
}

function setting_defaults(): array {
    return [
        'business_name'        => SITE_NAME,
        'business_description' => SITE_TAGLINE,
        'contact_number'       => '+63 900 000 0000',
        'email'                => 'info@poultryhaven.local',
        'address'              => 'Local Farm, Philippines',
        'business_hours'       => 'Mon-Sat 8:00 AM - 5:00 PM',
    ];
}

function setting(string $key, $default = null): string {
    $cache = &settings_cache();
    if (isset($cache[$key]) && trim($cache[$key]) !== '') {
        return $cache[$key];
    }
    if ($default !== null) {
        return (string)$default;
    }
    $defaults = setting_defaults();
    return $defaults[$key] ?? '';
}

function set_setting(string $key, $value): void {
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, (string)$value]);
    $cache = &settings_cache();
    $cache[$key] = (string)$value;
}

function site_name(): string {
    return setting('business_name', SITE_NAME);
}

function site_tagline(): string {
    return setting('business_description', SITE_TAGLINE);
}

/* =====================================================================
 * Paths & images
 * ===================================================================== */

/**
 * Relative prefix that leads from the current page back to the project
 * root ('' in the public pages, '../' inside /admin).
 */
function app_relative_base(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $rootDir = basename(dirname(__DIR__));
    $needle  = '/' . $rootDir . '/';
    $pos     = strpos($script, $needle);
    if ($pos === false) {
        $base = '';
    } else {
        $rest = substr($script, $pos + strlen($needle));
        $base = str_repeat('../', substr_count($rest, '/'));
    }
    return $base;
}

/** URL of the project root relative to the current page. */
function app_base(): string {
    return app_relative_base();
}

/**
 * Resolves a stored image path to a working, document-relative URL.
 * Falls back to a local default picture when the file is missing.
 */
function image_url(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') {
        return app_relative_base() . DEFAULT_IMAGE;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;                       // external URL (kept only for safety)
    }
    if (str_starts_with($path, '/')) {
        return $path;                       // already root relative
    }
    $relative = ltrim($path, '/');
    if (!is_file(APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
        return app_relative_base() . DEFAULT_IMAGE;
    }
    return app_relative_base() . $relative;
}

/** True when the stored image file really exists on disk. */
function image_exists(?string $path): bool {
    $path = trim((string)$path);
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return $path !== '';
    }
    return is_file(APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/')));
}

/* =====================================================================
 * Flash messages
 * ===================================================================== */

function flash(string $type, string $message): void {
    start_session();
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array {
    start_session();
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

/* =====================================================================
 * Uploads
 * ===================================================================== */

function upload_image(array $file, ?string &$error = null): ?string {
    $error = null;
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        $error = 'The image is larger than 5 MB. Please choose a smaller file.';
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }
    if (!is_dir(UPLOAD_DIR) || !is_writable(UPLOAD_DIR)) {
        $error = 'The uploads/chickens folder is not writable. Check its permissions in XAMPP.';
        return null;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        $error = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        return null;
    }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext  = $extensions[$mime] ?? 'jpg';
    $name = 'chicken_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $error = 'The image could not be saved on the server.';
        return null;
    }
    return UPLOAD_URL . $name;
}

/** Removes a previously uploaded picture (only files inside uploads/). */
function delete_uploaded_image(?string $path): void {
    $path = trim((string)$path);
    if ($path === '' || !str_starts_with($path, UPLOAD_URL)) {
        return;
    }
    $file = UPLOAD_DIR . basename($path);
    if (is_file($file)) {
        @unlink($file);
    }
}
