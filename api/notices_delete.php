<?php
// POST /api/notices_delete.php?id=1
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') json_err('Forbidden', 403);

$id = (int)($_GET['id'] ?? 0);
if (!$id) json_err('Missing id');

$stmt = db()->prepare('SELECT pdf_url FROM notices WHERE id = ?');
$stmt->execute([$id]);
$pdf = $stmt->fetchColumn();

db()->prepare('DELETE FROM notices WHERE id = ?')->execute([$id]);
if ($pdf) delete_photo($pdf);

json_ok([], 'Notice deleted.');
