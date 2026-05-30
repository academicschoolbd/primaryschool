<?php
// POST /api/save_routine.php
// Body: class_id, year_id, day_of_week, period, subject_id, teacher_id, start_time, end_time
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$classId   = (int)($_POST['class_id'] ?? 0);
$yearId    = (int)($_POST['year_id']  ?? 0) ?: (int)current_year_id();
$dow       = (int)($_POST['day_of_week'] ?? -1);
$period    = (int)($_POST['period'] ?? 0);
$subjectId = (int)($_POST['subject_id'] ?? 0) ?: null;
$teacherId = (int)($_POST['teacher_id'] ?? 0) ?: null;
$start     = trim($_POST['start_time'] ?? '') ?: null;
$end       = trim($_POST['end_time']   ?? '') ?: null;

if (!$classId)                  json_err('Class required.');
if ($dow < 0 || $dow > 6)       json_err('Invalid day.');
if ($period < 1 || $period > 12) json_err('Invalid period.');

// Auto-heal table
try { db()->query('SELECT 1 FROM routine LIMIT 1'); }
catch (Throwable $e) {
    db()->exec("CREATE TABLE IF NOT EXISTS routine (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL, year_id INT,
        day_of_week TINYINT NOT NULL, period INT NOT NULL,
        start_time TIME, end_time TIME,
        subject_id INT, teacher_id INT, room VARCHAR(40),
        UNIQUE KEY uniq_period (class_id, year_id, day_of_week, period),
        INDEX idx_routine (class_id, day_of_week)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Snapshot before
$bs = db()->prepare('SELECT * FROM routine WHERE class_id=? AND year_id=? AND day_of_week=? AND period=?');
$bs->execute([$classId, $yearId, $dow, $period]);
$before = $bs->fetch();

// If both subject + teacher cleared, delete the row
if (!$subjectId && !$teacherId) {
    if ($before) {
        db()->prepare('DELETE FROM routine WHERE id=?')->execute([$before['id']]);
        audit_log('delete', 'routine', (int)$before['id'],
            'Class ' . $classId . ' · Day ' . $dow . ' · Period ' . $period,
            $before, null);
    }
    json_ok([], 'Cleared.');
}

$sql = "INSERT INTO routine (class_id, year_id, day_of_week, period, start_time, end_time, subject_id, teacher_id)
        VALUES (?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE start_time=VALUES(start_time), end_time=VALUES(end_time),
                                subject_id=VALUES(subject_id), teacher_id=VALUES(teacher_id)";
db()->prepare($sql)->execute([$classId, $yearId, $dow, $period, $start, $end, $subjectId, $teacherId]);

$after = ['day' => $dow, 'period' => $period, 'subject_id' => $subjectId, 'teacher_id' => $teacherId];
audit_log(
    $before ? 'update' : 'create',
    'routine', null,
    'Class ' . $classId . ' · Day ' . $dow . ' · Period ' . $period,
    $before ?: null, $after
);

json_ok([], 'Saved.');
