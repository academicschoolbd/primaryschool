<?php
// GET /api/sections.php?class=Grade%201
// Returns list of section rows under the given class name.
require __DIR__ . '/_bootstrap.php';

$class = trim($_GET['class'] ?? '');
if ($class === '') json_ok([]);
if (!db_ok()) json_err('Database not connected', 503);

$stmt = db()->prepare("
    SELECT id, name, section, capacity,
           (SELECT COUNT(*) FROM students WHERE class_id = c.id) AS students_count
    FROM classes c
    WHERE name = ?
    ORDER BY section IS NULL, section
");
$stmt->execute([$class]);
json_ok($stmt->fetchAll());
