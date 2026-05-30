<?php
// GET /api/public_page.php?slug=foo
// Public CMS page fetch (CORS-enabled — for the future mobile app).
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

if (!db_ok()) json_err('Database not connected', 503);

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') json_err('Slug required.', 400);

$stmt = db()->prepare('SELECT id, slug, title, body, updated_at FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1');
$stmt->execute([$slug]);
$page = $stmt->fetch();
if (!$page) json_err('Page not found.', 404);

$page['body'] = sanitize_html($page['body']);
json_ok($page);
