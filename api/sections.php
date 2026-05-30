<?php
// GET /api/sections.php?class=Grade%201
// Returns list of section rows under the given class name.
require __DIR__ . '/_bootstrap.php';

$class = trim($_GET['class'] ?? '');
$yearId = (int)($_GET['year_id'] ?? 0);
if ($class === '') json_ok([]);
if (!db_ok()) json_err('Database not connected', 503);

$sql = "SELECT id, name, section, capacity,
               (SELECT COUNT(*) FROM students WHERE class_id = c.id" . ($yearId ? ' AND year_id = ?' : '') . ") AS students_count
        FROM classes c
        WHERE name = ?"
     . ($yearId ? ' AND year_id = ?' : '')
     . " ORDER BY section IS NULL, section";
$args = [];
if ($yearId) $args[] = $yearId;
$args[] = $class;
if ($yearId) $args[] = $yearId;
$stmt = db()->prepare($sql);
$stmt->execute($args);
json_ok($stmt->fetchAll());
