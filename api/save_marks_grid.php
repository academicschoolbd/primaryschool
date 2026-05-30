<?php
// POST /api/save_marks_grid.php
// Body: year_id, class_id, subject_id, exam_term, marks[student_id] = value
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$yearId    = (int)($_POST['year_id'] ?? 0);
$subjectId = (int)($_POST['subject_id'] ?? 0);
$term      = $_POST['exam_term'] ?? 'final';
$marksMap  = $_POST['marks'] ?? [];

if (!$yearId)    json_err('Year required.');
if (!$subjectId) json_err('Subject required.');
if (!in_array($term, ['first','mid','final'], true)) json_err('Invalid term.');

$stmt = db()->prepare('SELECT * FROM subjects WHERE id = ?');
$stmt->execute([$subjectId]);
$subject = $stmt->fetch();
if (!$subject) json_err('Subject not found.', 404);
$full = (int)$subject['full_marks'];

$saved = 0; $skipped = 0; $changes = 0;

foreach ($marksMap as $studentId => $marksRaw) {
    $studentId = (int)$studentId;
    $marksRaw  = trim((string)$marksRaw);
    if ($marksRaw === '') { $skipped++; continue; }
    $marks = (int)$marksRaw;
    if ($marks < 0 || $marks > $full) { $skipped++; continue; }

    $percent = $full ? ($marks / $full) * 100 : 0;
    [$grade, ] = calc_grade($percent);

    $stmt = db()->prepare('SELECT id, marks_obtained, grade FROM results WHERE student_id=? AND subject_id=? AND exam_term=? AND year_id=?');
    $stmt->execute([$studentId, $subjectId, $term, $yearId]);
    $before = $stmt->fetch();

    $sql = "INSERT INTO results (student_id, subject_id, exam_term, year_id, marks_obtained, grade)
            VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), grade = VALUES(grade)";
    db()->prepare($sql)->execute([$studentId, $subjectId, $term, $yearId, $marks, $grade]);
    $saved++;

    $isChange = !$before || (int)$before['marks_obtained'] !== $marks;
    if ($isChange) {
        $changes++;
        $sn = db()->prepare('SELECT name, roll_no FROM students WHERE id = ?');
        $sn->execute([$studentId]);
        $stu = $sn->fetch();
        $label = ($stu['name'] ?? 'Student') . ' [' . ($stu['roll_no'] ?? $studentId) . '] · ' . $subject['name'] . ' · ' . ucfirst($term);
        audit_log(
            $before ? 'update' : 'create',
            'result', $studentId, $label,
            $before ? ['marks_obtained' => $before['marks_obtained'], 'grade' => $before['grade']] : null,
            ['marks_obtained' => $marks, 'grade' => $grade]
        );
    }
}

json_ok(['saved' => $saved, 'skipped' => $skipped, 'audit_changes' => $changes],
        "Saved $saved entries · $changes change(s) logged.");
