<?php
// GET /api/public_students.php?class=Grade%201&class_id=5&status=active
// Public listing of students for the front "শিক্ষার্থী তালিকা" page.
// CORS-enabled, no auth, only safe fields.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

if (!db_ok()) json_err('Database not connected', 503);

$cls     = trim($_GET['class'] ?? '');
$classId = (int)($_GET['class_id'] ?? 0);
$gender  = $_GET['gender'] ?? '';
$status  = $_GET['status'] ?? 'active';

$where = []; $args = [];
if ($status === 'active' || $status === 'inactive') {
    $where[] = 's.status = ?'; $args[] = $status;
}
if ($classId) {
    $where[] = 's.class_id = ?'; $args[] = $classId;
} elseif ($cls !== '') {
    $where[] = 'c.name = ?'; $args[] = $cls;
}
if (in_array($gender, ['male','female','other'], true)) {
    $where[] = 's.gender = ?'; $args[] = $gender;
}

$sql = "SELECT s.id, s.roll_no, s.name, s.gender, s.parent_name, s.photo,
               c.id AS class_id, c.name AS class_name, c.section
        FROM students s
        LEFT JOIN classes c ON c.id = s.class_id"
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY c.name, c.section, s.roll_no LIMIT 1000';

$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
    $r['photo_url']   = $r['photo'] ? media_url($r['photo']) : null;
    $r['class_label'] = $r['class_name']
        ? ($r['class_name'] . ($r['section'] ? ' - ' . $r['section'] : ''))
        : null;
}

json_ok($rows);
