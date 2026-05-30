<?php
// GET /api/floating_notice.php
// Returns the currently flagged floating notice (or null if disabled / none).
// Public, CORS-enabled.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

if (!db_ok()) json_ok(null);

$enabled = get_setting('floating_notice_enabled', '1');
if ($enabled !== '1') {
    json_ok(null);
}

$stmt = db()->query("
    SELECT id, title, body, pdf_url, posted_at
    FROM notices
    WHERE is_floating = 1 AND is_published = 1
    ORDER BY posted_at DESC LIMIT 1
");
$row = $stmt->fetch();
if (!$row) json_ok(null);

if ($row['pdf_url']) $row['pdf_url'] = media_url($row['pdf_url']);
$row['posted_at_label'] = date('M d, Y', strtotime($row['posted_at']));

json_ok($row);
