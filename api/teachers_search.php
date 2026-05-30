<?php
// GET /api/teachers_search.php?q=foo&status=active
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);

$q      = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = []; $args = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR designation LIKE ?)';
    array_push($args, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($status === 'active' || $status === 'inactive') {
    $where[] = 'status = ?';
    $args[] = $status;
}
$sql = 'SELECT * FROM teachers' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
    $r['photo_url']        = $r['photo'] ? media_url($r['photo']) : null;
    $r['joined_on_label']  = $r['joined_on'] ? date('M d, Y', strtotime($r['joined_on'])) : null;
}
json_ok($rows);
