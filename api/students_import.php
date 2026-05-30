<?php
// POST /api/students_import.php
// multipart: csv (file), year_id, mode=preview|import
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$user = current_user();
if (!$user || !in_array($user['role'] ?? '', ['admin'], true)) json_err('Forbidden', 403);

$mode    = $_POST['mode'] ?? 'preview';
$yearId  = (int)($_POST['year_id'] ?? 0) ?: (int)current_year_id();

if (empty($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) json_err('Please upload a CSV file.');
$f = $_FILES['csv'];
if ($f['size'] > 4 * 1024 * 1024) json_err('CSV too large (max 4 MB).');

// Parse CSV
$fh = @fopen($f['tmp_name'], 'r');
if (!$fh) json_err('Could not open file.');
$header = fgetcsv($fh);
if (!$header) { fclose($fh); json_err('Empty CSV.'); }
// Normalize header keys
$header = array_map(fn($h) => strtolower(trim((string)$h)), $header);

$expected = ['roll_no','name','class_name','section','gender','dob','parent_name','phone','address','status'];
$missing  = array_diff(['roll_no','name','class_name'], $header);
if ($missing) {
    fclose($fh);
    json_err('Missing required columns: ' . implode(', ', $missing));
}

$rows = []; $errors = []; $rowNum = 1;
while (($row = fgetcsv($fh)) !== false) {
    $rowNum++;
    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue; // skip empty
    $r = [];
    foreach ($header as $i => $h) $r[$h] = trim((string)($row[$i] ?? ''));
    $r['_row_num'] = $rowNum;
    $rows[] = $r;
}
fclose($fh);

// Pre-load classes for current year for class_id resolution
$stmt = db()->prepare('SELECT id, name, COALESCE(section,\'\') AS section FROM classes WHERE year_id = ?');
$stmt->execute([$yearId]);
$classMap = [];
foreach ($stmt->fetchAll() as $c) {
    $key = strtolower($c['name'] . '|' . $c['section']);
    $classMap[$key] = (int)$c['id'];
}

// Validate each row + resolve class_id
$valid = 0;
foreach ($rows as &$r) {
    if (empty($r['roll_no']) || empty($r['name']) || empty($r['class_name'])) {
        $errors[] = ['row' => $r['_row_num'], 'msg' => 'roll_no, name, and class_name are required'];
        $r['class_id'] = null;
        continue;
    }
    $key = strtolower($r['class_name'] . '|' . ($r['section'] ?? ''));
    if (!isset($classMap[$key])) {
        $errors[] = ['row' => $r['_row_num'], 'msg' => "Class '" . $r['class_name'] . "' (section: " . ($r['section'] ?: '—') . ") not found in target year"];
        $r['class_id'] = null;
        continue;
    }
    $r['class_id'] = $classMap[$key];
    if (!empty($r['gender']) && !in_array($r['gender'], ['male','female','other'], true)) {
        $errors[] = ['row' => $r['_row_num'], 'msg' => 'gender must be male/female/other (got "' . $r['gender'] . '")'];
        $r['class_id'] = null;
        continue;
    }
    if (!empty($r['status']) && !in_array($r['status'], ['active','inactive'], true)) {
        $errors[] = ['row' => $r['_row_num'], 'msg' => 'status must be active/inactive (got "' . $r['status'] . '")'];
        $r['class_id'] = null;
        continue;
    }
    $valid++;
}
unset($r);

if ($mode === 'preview') {
    // Return first 10 rows
    json_ok([
        'preview'     => array_slice($rows, 0, 10),
        'total_rows'  => count($rows),
        'valid_count' => $valid,
        'errors'      => $errors,
    ]);
}

// === Actual import ===
$inserted = 0; $updated = 0; $skipped = 0;
$ins = db()->prepare("INSERT INTO students (roll_no,name,class_id,gender,dob,parent_name,phone,address,status,year_id)
                      VALUES (?,?,?,?,?,?,?,?,?,?)");

foreach ($rows as $r) {
    if (!$r['class_id']) { $skipped++; continue; }
    try {
        $args = [
            $r['roll_no'],
            $r['name'],
            $r['class_id'],
            $r['gender'] ?: 'male',
            $r['dob'] ?: null,
            $r['parent_name'] ?: null,
            $r['phone'] ?: null,
            $r['address'] ?: null,
            $r['status'] ?: 'active',
            $yearId,
        ];
        $ins->execute($args);
        $newId = (int)db()->lastInsertId();
        $inserted++;
        audit_log('create', 'student', $newId,
            $r['name'] . ' (roll ' . $r['roll_no'] . ') · CSV import',
            null, ['roll_no'=>$r['roll_no'],'name'=>$r['name'],'class_id'=>$r['class_id'],'year_id'=>$yearId]);
    } catch (PDOException $e) {
        $skipped++;
    }
}

json_ok(
    ['inserted' => $inserted, 'skipped' => $skipped],
    "Imported $inserted student(s) · $skipped skipped"
);
