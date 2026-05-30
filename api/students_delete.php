<?php
// POST /api/students_delete.php?id=1
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$id = (int)($_GET['id'] ?? 0);
if (!$id) json_err('Missing id');

db()->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
json_ok([], 'Student deleted.');
