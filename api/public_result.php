<?php
// GET /api/public_result.php?class_id=5&roll_no=STU-1001&exam_term=final
//
// Public, no-auth endpoint that returns a student's marksheet as JSON.
// CORS-enabled so a mobile app can hit this same URL.
//
// Response shape:
// {
//   "ok": true,
//   "data": {
//     "student": { id, name, roll_no, gender, dob, parent_name, phone, photo_url },
//     "class":   { id, name, section, teacher_name },
//     "school":  { name_bn, name_en, logo, eiin, established },
//     "term":    { code, label_bn },
//     "subjects":[ { id, name, full_marks, pass_marks, marks_obtained, percent, grade, status } ],
//     "summary": { total_obtained, total_max, percent, grade, grade_color, status },
//     "issued_at": "2026-05-30T..."
//   }
// }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

public_api_headers();

if (!db_ok()) json_err('Database not connected', 503);

$classId  = (int)($_GET['class_id'] ?? 0);
$rollNo   = trim($_GET['roll_no'] ?? '');
$term     = $_GET['exam_term'] ?? 'final';

if (!$classId)  json_err('Class & section required.');
if ($rollNo === '') json_err('Roll no required.');
if (!in_array($term, ['first','mid','final'], true)) json_err('Invalid exam term.');

// Find student in that class+section by roll_no
$stmt = db()->prepare("
    SELECT s.id, s.name, s.roll_no, s.gender, s.dob, s.parent_name, s.phone,
           c.id AS cls_id, c.name AS cls_name, c.section AS cls_section, c.teacher_id,
           t.name AS teacher_name
    FROM students s
    LEFT JOIN classes c  ON c.id = s.class_id
    LEFT JOIN teachers t ON t.id = c.teacher_id
    WHERE s.class_id = ? AND s.roll_no = ?
    LIMIT 1
");
$stmt->execute([$classId, $rollNo]);
$student = $stmt->fetch();

if (!$student) {
    json_err('No student found with that class, section and roll number.', 404);
}

// Marks for every subject (left join so missing subjects show 0)
$stmt = db()->prepare("
    SELECT sb.id, sb.name, sb.full_marks, sb.pass_marks,
           COALESCE(r.marks_obtained, 0) AS marks_obtained,
           r.grade AS stored_grade
    FROM subjects sb
    LEFT JOIN results r ON r.subject_id = sb.id
                       AND r.student_id = ? AND r.exam_term = ?
    ORDER BY sb.id
");
$stmt->execute([(int)$student['id'], $term]);
$subjects = $stmt->fetchAll();

// Make sure at least one subject has a real result entry; otherwise tell the user
$haveAnyResult = false;
foreach ($subjects as $sb) {
    if ((int)$sb['marks_obtained'] > 0) { $haveAnyResult = true; break; }
}
if (!$haveAnyResult) {
    // Optional: still return zeros, but flag it. We return success with a note.
    // Front-end can decide how to display.
}

// Compute per-subject + totals
$totalObt = 0; $totalMax = 0; $failed = false;
$subjectsOut = [];
foreach ($subjects as $sb) {
    $full   = (int)$sb['full_marks'];
    $pass   = (int)$sb['pass_marks'];
    $marks  = (int)$sb['marks_obtained'];
    $pct    = $full > 0 ? ($marks / $full) * 100 : 0;
    [$g, $gColor] = calc_grade($pct);
    $isPass = $marks >= $pass;
    if (!$isPass) $failed = true;
    $totalObt += $marks;
    $totalMax += $full;
    $subjectsOut[] = [
        'id'             => (int)$sb['id'],
        'name'           => $sb['name'],
        'full_marks'     => $full,
        'pass_marks'     => $pass,
        'marks_obtained' => $marks,
        'percent'        => round($pct, 2),
        'grade'          => $g,
        'grade_color'    => $gColor,
        'status'         => $isPass ? 'pass' : 'fail',
    ];
}
$overallPct = $totalMax > 0 ? ($totalObt / $totalMax) * 100 : 0;
[$overallGrade, $overallColor] = calc_grade($overallPct);

$school = public_school();

json_ok([
    'student' => [
        'id'          => (int)$student['id'],
        'name'        => $student['name'],
        'roll_no'     => $student['roll_no'],
        'gender'      => $student['gender'],
        'dob'         => $student['dob'],
        'parent_name' => $student['parent_name'],
        'phone'       => $student['phone'],
    ],
    'class' => [
        'id'           => $student['cls_id'] ? (int)$student['cls_id'] : null,
        'name'         => $student['cls_name'],
        'section'      => $student['cls_section'],
        'teacher_name' => $student['teacher_name'],
    ],
    'school' => [
        'name_bn'     => $school['name_bn'],
        'name_en'     => $school['name_en'],
        'logo'        => $school['logo'],
        'eiin'        => $school['eiin'],
        'established' => $school['established'],
    ],
    'term' => [
        'code'      => $term,
        'label_bn'  => bn_term($term),
    ],
    'subjects' => $subjectsOut,
    'summary' => [
        'total_obtained' => $totalObt,
        'total_max'      => $totalMax,
        'percent'        => round($overallPct, 2),
        'grade'          => $overallGrade,
        'grade_color'    => $overallColor,
        'status'         => $failed ? 'fail' : 'pass',
        'has_results'    => $haveAnyResult,
    ],
    'issued_at' => date('c'),
]);
