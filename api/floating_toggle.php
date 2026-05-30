<?php
// POST /api/floating_toggle.php  body: enabled=0|1
// Admin-only: globally turn the floating notice popup on or off.
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') json_err('Forbidden', 403);

$enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 0;
$enabled = $enabled ? '1' : '0';
set_setting('floating_notice_enabled', $enabled);
json_ok(['enabled' => $enabled], $enabled === '1' ? 'Floating popup enabled.' : 'Floating popup disabled.');
