<?php
// POST /api/save_attendance.php
// Body: class_id, year_id, date, status[student_id]=present|absent|late|leave, note[student_id]=...
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$classId  = (int)($_POST['class_id'] ?? 0);
$yearId   = (int)($_POST['year_id']  ?? 0) ?: (int)current_year_id();
$date     = $_POST['date'] ?? date('Y-m-d');
$statuses = $_POST['status'] ?? [];
$notes    = $_POST['note']   ?? [];

if (!$classId) json_err('Class required.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_err('Invalid date.');

$user = current_user();
$markedBy = $user['id'] ?? null;

$saved = 0; $changes = 0;

// Make sure attendance table exists (auto-heal for fresh upgrades)
try { db()->query('SELECT 1 FROM attendance LIMIT 1'); }
catch (Throwable $e) {
    db()->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL, class_id INT, year_id INT,
        date DATE NOT NULL,
        status ENUM('present','absent','late','leave') DEFAULT 'present',
        note VARCHAR(255), marked_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_attendance (student_id, date),
        INDEX idx_class_date (class_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

foreach ($statuses as $studentId => $status) {
    $studentId = (int)$studentId;
    if (!$studentId) continue;
    if (!in_array($status, ['present','absent','late','leave'], true)) continue;
    $note = trim((string)($notes[$studentId] ?? '')) ?: null;

    // Snapshot before for audit
    $bs = db()->prepare('SELECT status, note FROM attendance WHERE student_id=? AND date=?');
    $bs->execute([$studentId, $date]);
    $before = $bs->fetch();

    $sql = "INSERT INTO attendance (student_id, class_id, year_id, date, status, note, marked_by)
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE status=VALUES(status), note=VALUES(note), marked_by=VALUES(marked_by)";
    db()->prepare($sql)->execute([$studentId, $classId, $yearId, $date, $status, $note, $markedBy]);
    $saved++;

    $changedStatus = !$before || $before['status'] !== $status;
    if ($changedStatus) {
        $changes++;
        $sn = db()->prepare('SELECT name, roll_no FROM students WHERE id=?');
        $sn->execute([$studentId]);
        $stu = $sn->fetch();
        $label = ($stu['name'] ?? 'Student') . ' [' . ($stu['roll_no'] ?? $studentId) . '] · ' . $date;
        audit_log(
            $before ? 'update' : 'create',
            'attendance', $studentId, $label,
            $before ? ['status' => $before['status'], 'note' => $before['note']] : null,
            ['status' => $status, 'note' => $note, 'date' => $date]
        );
    }
}

json_ok(['saved' => $saved, 'changes' => $changes], "Saved $saved · $changes change(s) logged.");
