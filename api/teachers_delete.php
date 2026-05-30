<?php
// POST /api/teachers_delete.php?id=1
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$id = (int)($_GET['id'] ?? 0);
if (!$id) json_err('Missing id');

$stmt = db()->prepare('SELECT photo, name FROM teachers WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) json_err('Teacher not found', 404);

db()->prepare('DELETE FROM teachers WHERE id = ?')->execute([$id]);
delete_photo($row['photo']);
audit_log('delete', 'teacher', $id, $row['name'] ?? '#'.$id);

json_ok([], 'Teacher deleted.');
