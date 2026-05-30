<?php
// GET /api/public_teachers.php?status=active&q=foo
// Public teacher list for the front "শিক্ষক তালিকা" page.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

if (!db_ok()) json_err('Database not connected', 503);

$q       = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? 'active';
$subject = trim($_GET['subject'] ?? '');

$where = []; $args = [];
if ($status === 'active' || $status === 'inactive') {
    $where[] = 'status = ?'; $args[] = $status;
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR designation LIKE ? OR subject LIKE ?)';
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($subject !== '') {
    $where[] = 'subject = ?'; $args[] = $subject;
}

$sql = 'SELECT id, name, email, phone, subject, designation, photo, gender, joined_on
        FROM teachers'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY designation IS NULL, designation, name';
$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
    $r['photo_url']       = $r['photo'] ? media_url($r['photo']) : null;
    $r['joined_on_label'] = $r['joined_on'] ? date('M Y', strtotime($r['joined_on'])) : null;
}

json_ok($rows);
