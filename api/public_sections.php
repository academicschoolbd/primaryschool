<?php
// GET /api/public_sections.php?class=Grade%201
// Public sections lookup (CORS-enabled) for the result-page form & mobile app.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

$class = trim($_GET['class'] ?? '');
if ($class === '') json_ok([]);
if (!db_ok()) json_err('Database not connected', 503);

$stmt = db()->prepare("
    SELECT id, name, section
    FROM classes
    WHERE name = ?
    ORDER BY section IS NULL, section
");
$stmt->execute([$class]);
json_ok($stmt->fetchAll());
