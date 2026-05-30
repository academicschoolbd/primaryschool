<?php
// GET /api/classes_search.php?q=foo
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);

$q = trim($_GET['q'] ?? '');
$yearId = (int)($_GET['year_id'] ?? 0);
$where = []; $args = [];
if ($q !== '') {
    $where[] = '(c.name LIKE ? OR c.section LIKE ? OR t.name LIKE ?)';
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($yearId) { $where[] = 'c.year_id = ?'; $args[] = $yearId; }
$wherePart = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
$sql = "SELECT c.*, t.name AS teacher_name,
               (SELECT COUNT(*) FROM students WHERE class_id = c.id) AS students_count
        FROM classes c LEFT JOIN teachers t ON t.id = c.teacher_id
        $wherePart
        ORDER BY c.id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($args);
json_ok($stmt->fetchAll());
