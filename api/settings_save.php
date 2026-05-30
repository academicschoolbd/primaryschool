<?php
// POST /api/settings_save.php
// Body: theme_primary=#xxxxxx&theme_accent=#xxxxxx
// Only admins may change theme settings.
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') json_err('Forbidden', 403);

$primary = trim($_POST['theme_primary'] ?? '');
$accent  = trim($_POST['theme_accent']  ?? '');

$hex = '/^#[0-9a-fA-F]{6}$/';
if (!preg_match($hex, $primary)) json_err('Invalid primary color (use #rrggbb)');
if (!preg_match($hex, $accent))  json_err('Invalid accent color (use #rrggbb)');

set_setting('theme_primary', $primary);
set_setting('theme_accent',  $accent);

json_ok(['theme_primary' => $primary, 'theme_accent' => $accent], 'Theme saved.');
