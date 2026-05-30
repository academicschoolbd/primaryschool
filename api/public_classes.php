<?php
// GET /api/public_classes.php
// Returns the list of distinct class names for the public result form
// (no auth required, CORS-enabled — usable by mobile app).
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

if (!db_ok()) json_err('Database not connected', 503);

$rows = db()->query("SELECT DISTINCT name FROM classes ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
json_ok($rows);
