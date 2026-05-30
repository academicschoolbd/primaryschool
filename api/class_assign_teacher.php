<?php
// POST /api/class_assign_teacher.php
// Body: id=1&teacher_id=3   (teacher_id="" to unassign)
// Inline AJAX teacher assignment from the class list.
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$id        = (int)($_POST['id'] ?? 0);
$teacherId = trim($_POST['teacher_id'] ?? '');
$teacherId = $teacherId === '' ? null : (int)$teacherId;

if (!$id) json_err('Missing class id');

if ($teacherId !== null) {
    $stmt = db()->prepare('SELECT id, name FROM teachers WHERE id = ?');
    $stmt->execute([$teacherId]);
    $tch = $stmt->fetch();
    if (!$tch) json_err('Teacher not found', 404);
}

db()->prepare('UPDATE classes SET teacher_id = ? WHERE id = ?')->execute([$teacherId, $id]);

json_ok(
    ['teacher_id' => $teacherId, 'teacher_name' => $tch['name'] ?? null],
    $teacherId ? 'Teacher assigned.' : 'Teacher unassigned.'
);
