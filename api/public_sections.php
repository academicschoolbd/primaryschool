<?php
// GET /api/public_sections.php?class=Grade%201
// Public sections lookup (CORS-enabled) for the result-page form & mobile app.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

$class = trim($_GET['class'] ?? '');
$yearId = (int)($_GET['year_id'] ?? 0);
if ($class === '') json_ok([]);
if (!db_ok()) json_err('Database not connected', 503);

$sql = "SELECT id, name, section
        FROM classes
        WHERE name = ?"
     . ($yearId ? ' AND year_id = ?' : '')
     . " ORDER BY section IS NULL, section";
$args = [$class];
if ($yearId) $args[] = $yearId;
$stmt = db()->prepare($sql);
$stmt->execute($args);
json_ok($stmt->fetchAll());
