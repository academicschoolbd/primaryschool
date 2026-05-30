<?php
// GET /api/students_search.php?q=foo&class=Grade+1&section=A&class_id=5
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);

$q        = trim($_GET['q'] ?? '');
$cls      = trim($_GET['class'] ?? '');
$section  = trim($_GET['section'] ?? '');
$classId  = (int)($_GET['class_id'] ?? 0);

$where = []; $args = [];
if ($q !== '') {
    $where[] = '(s.name LIKE ? OR s.roll_no LIKE ? OR s.parent_name LIKE ?)';
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($classId) {
    $where[] = 's.class_id = ?'; $args[] = $classId;
} else {
    if ($cls !== '')     { $where[] = 'c.name = ?';    $args[] = $cls; }
    if ($section !== '') { $where[] = 'c.section = ?'; $args[] = $section; }
}

$sql = "SELECT s.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label
        FROM students s LEFT JOIN classes c ON c.id = s.class_id"
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY s.id DESC LIMIT 500';

$stmt = db()->prepare($sql);
$stmt->execute($args);
json_ok($stmt->fetchAll());
