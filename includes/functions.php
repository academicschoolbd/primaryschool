<?php
// Common helpers used across the admin panel.

function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function flash_set($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function render_flash() {
    $f = flash_get();
    if (!$f) return '';
    $colors = [
        'success' => ['bg' => '#ecfdf5', 'fg' => '#047857', 'icon' => 'bi-check-circle-fill'],
        'error'   => ['bg' => '#fef2f2', 'fg' => '#b91c1c', 'icon' => 'bi-exclamation-circle-fill'],
        'info'    => ['bg' => '#eff6ff', 'fg' => '#1d4ed8', 'icon' => 'bi-info-circle-fill'],
    ];
    $c = $colors[$f['type']] ?? $colors['info'];
    return '<div style="background:' . $c['bg'] . ';color:' . $c['fg']
         . ';padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;">'
         . '<i class="bi ' . $c['icon'] . '"></i> ' . e($f['msg']) . '</div>';
}

function db_ok() {
    return db() instanceof PDO;
}

function db_banner_if_offline() {
    if (db_ok()) return '';
    return '<div style="background:#fff7ed;color:#b45309;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;">'
        . '<i class="bi bi-database-exclamation"></i> '
        . 'Database not connected. Edit <code>config/database.php</code> and run <a href="' . BASE_URL . '/install.php"><b>install.php</b></a>.'
        . '</div>';
}

function calc_grade($percent) {
    if ($percent >= 90) return ['A+', '#047857'];
    if ($percent >= 80) return ['A',  '#10b981'];
    if ($percent >= 70) return ['B+', '#0ea5e9'];
    if ($percent >= 60) return ['B',  '#1d4ed8'];
    if ($percent >= 50) return ['C',  '#b45309'];
    if ($percent >= 33) return ['D',  '#f59e0b'];
    return ['F', '#b91c1c'];
}

function avatar_color($name) {
    $palette = ['#4f46e5','#8b5cf6','#10b981','#0ea5e9','#f59e0b','#ef4444','#ec4899'];
    return $palette[abs(crc32($name)) % count($palette)];
}


// ====================================================================
// Settings helpers (key/value store, used for theme + global preferences)
// ====================================================================

function get_setting($key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        if (db_ok()) {
            try {
                $rows = db()->query('SELECT `key`,`value` FROM settings')->fetchAll();
                foreach ($rows as $r) $cache[$r['key']] = $r['value'];
            } catch (Throwable $e) { /* table may not exist yet */ }
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function set_setting($key, $value) {
    if (!db_ok()) return false;
    $sql = 'INSERT INTO settings (`key`,`value`) VALUES (?,?) '
         . 'ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)';
    return db()->prepare($sql)->execute([$key, (string)$value]);
}

// ====================================================================
// Color helpers
// ====================================================================

/**
 * Adjust a hex color's lightness. $amount in -100..+100.
 * Negative = darker, positive = lighter.
 */
function adjust_color($hex, $amount) {
    $hex = ltrim((string)$hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '#' . $hex;
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $delta = (int) round(255 * $amount / 100);
    $r = max(0, min(255, $r + $delta));
    $g = max(0, min(255, $g + $delta));
    $b = max(0, min(255, $b + $delta));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/** Convert hex to "r,g,b" string for use in rgba() */
function hex_to_rgb_str($hex) {
    $hex = ltrim((string)$hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '79,70,229';
    return hexdec(substr($hex, 0, 2)) . ',' . hexdec(substr($hex, 2, 2)) . ',' . hexdec(substr($hex, 4, 2));
}

/**
 * Build a {--primary, --primary-light, ...} dictionary derived from the two
 * configurable colors, plus accent. Used to inject the theme on every page.
 */
function theme_palette() {
    $primary = get_setting('theme_primary', '#1a237e');
    $accent  = get_setting('theme_accent',  '#f9a825');
    return [
        '--primary'        => $primary,
        '--primary-light'  => adjust_color($primary, +12),
        '--primary-dark'   => adjust_color($primary, -10),
        '--primary-rgb'    => hex_to_rgb_str($primary),
        '--accent'         => $accent,
        '--accent-light'   => adjust_color($accent, +10),
        '--accent-dark'    => adjust_color($accent, -15),
        '--accent-rgb'     => hex_to_rgb_str($accent),
    ];
}

/**
 * Output a <style>:root{...}</style> block so saved theme colors override
 * the defaults in style.css / public.css. Call inside <head>.
 */
function theme_styles_inline() {
    $vars = theme_palette();
    $out = ':root{';
    foreach ($vars as $k => $v) $out .= $k . ':' . $v . ';';
    $out .= '}';
    echo '<style id="theme-vars">' . $out . '</style>';
}

// ====================================================================
// File upload helper
// ====================================================================

/**
 * Handle a single file upload. Returns the stored relative path
 * (e.g. 'assets/uploads/teachers/abc123.jpg') or null on no upload / error.
 *
 * @param string $field  $_FILES key
 * @param string $subdir relative subdir under assets/uploads/
 * @param string $prefix filename prefix
 */
function upload_photo($field, $subdir = 'misc', $prefix = 'img') {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'Upload failed (code ' . $f['error'] . ').');
        return null;
    }
    if ($f['size'] > 4 * 1024 * 1024) {
        flash_set('error', 'Photo too large (max 4 MB).');
        return null;
    }
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        flash_set('error', 'Only JPG, PNG, GIF, or WEBP images allowed.');
        return null;
    }
    $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $ext = $extMap[$mime];
    $dir = __DIR__ . '/../assets/uploads/' . trim($subdir, '/');
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        flash_set('error', 'Could not save uploaded file.');
        return null;
    }
    return 'assets/uploads/' . trim($subdir, '/') . '/' . $name;
}

/** Resolve a stored upload path (may be a full URL or relative) to a usable URL. */
function media_url($path) {
    if (!$path) return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Delete a previously uploaded local file (no-op for remote URLs). */
function delete_photo($path) {
    if (!$path || preg_match('/^https?:\/\//i', $path)) return;
    $abs = __DIR__ . '/../' . ltrim($path, '/');
    if (is_file($abs)) @unlink($abs);
}

// ====================================================================
// AJAX / JSON helpers
// ====================================================================

function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_ok($data = [], $msg = null) {
    json_response(['ok' => true] + ($msg ? ['msg' => $msg] : []) + ($data ? ['data' => $data] : []));
}

function json_err($msg, $status = 400) {
    json_response(['ok' => false, 'msg' => $msg], $status);
}

/** For AJAX endpoints: ensure the user is logged in, else return JSON 401. */
function require_ajax_login() {
    if (empty($_SESSION['user'])) json_err('Unauthorized', 401);
}


// ====================================================================
// Public-site helpers
// ====================================================================

/** Convert ASCII digits to Bengali numerals. */
function bn_num($num) {
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($en, $bn, (string)$num);
}

/** Bengali month names. */
function bn_months_arr() {
    return ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
}

/** Bengali term names. */
function bn_term($term) {
    return [
        'first' => 'প্রথম সাময়িক',
        'mid'   => 'দ্বিতীয় সাময়িক',
        'final' => 'বার্ষিক পরীক্ষা',
    ][$term] ?? $term;
}

/** Cached fetcher for the single-row school_info record (with safe defaults). */
function public_school() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [
        'name_bn'     => 'আদর্শ সরকারি উচ্চ বিদ্যালয়',
        'name_en'     => 'Adarsha Government High School',
        'tagline'     => 'শিক্ষা, সংস্কৃতি ও মূল্যবোধের আলোয় আলোকিত আগামী',
        'address'     => 'School Road, Dhaka, Bangladesh',
        'phone'       => '',
        'email'       => '',
        'website'     => '#',
        'eiin'        => '',
        'established' => '',
        'logo'        => 'https://placehold.co/200x200/1a237e/f9a825?text=School&font=roboto',
        'about_bn'    => 'আমাদের শিক্ষা প্রতিষ্ঠানে আপনাকে স্বাগতম।',
        'map_embed'   => '',
    ];
    if (db_ok()) {
        try {
            if ($row = db()->query('SELECT * FROM school_info ORDER BY id LIMIT 1')->fetch()) {
                $cache = array_merge($cache, $row);
            }
        } catch (Throwable $e) { /* table may not exist yet */ }
    }
    return $cache;
}

// ====================================================================
// Mobile-app friendly: enable CORS for public read-only endpoints
// ====================================================================
function public_api_headers() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Cache-Control: no-store');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
