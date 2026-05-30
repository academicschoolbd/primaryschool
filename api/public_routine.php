<?php
// GET /api/public_routine.php?class_id=X&year_id=Y
// Public, CORS-enabled. For the future mobile app to show class routines.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

if (!db_ok()) json_err('Database not connected', 503);

$classId = (int)($_GET['class_id'] ?? 0);
$yearId  = (int)($_GET['year_id']  ?? 0) ?: (int)current_year_id();
if (!$classId) json_err('class_id required.');

$stmt = db()->prepare("
    SELECT c.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS label, ay.name AS year_name
    FROM classes c LEFT JOIN academic_years ay ON ay.id = c.year_id
    WHERE c.id = ?
");
$stmt->execute([$classId]);
$cls = $stmt->fetch();
if (!$cls) json_err('Class not found.', 404);

$stmt = db()->prepare("
    SELECT r.day_of_week, r.period, r.start_time, r.end_time,
           r.subject_id, sb.name AS subject_name,
           r.teacher_id, t.name  AS teacher_name,
           r.room
    FROM routine r
    LEFT JOIN subjects sb ON sb.id = r.subject_id
    LEFT JOIN teachers t  ON t.id  = r.teacher_id
    WHERE r.class_id = ? " . ($yearId ? 'AND (r.year_id = ? OR r.year_id IS NULL)' : '') . "
    ORDER BY r.day_of_week, r.period
");
$args = [$classId];
if ($yearId) $args[] = $yearId;
$stmt->execute($args);
$rows = $stmt->fetchAll();

json_ok([
    'class' => [
        'id'         => (int)$cls['id'],
        'name'       => $cls['name'],
        'section'    => $cls['section'],
        'label'      => $cls['label'],
        'year_id'    => $cls['year_id'] ? (int)$cls['year_id'] : null,
        'year_name'  => $cls['year_name'],
    ],
    'days'    => ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
    'days_bn' => ['রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার','শনিবার'],
    'periods' => $rows,
]);
