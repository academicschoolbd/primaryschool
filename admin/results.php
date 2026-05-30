<?php
$pageTitle = 'Results';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$students = $subjects = $classes = [];
if (db_ok()) {
    $students = db()->query("SELECT id, roll_no, name FROM students ORDER BY name")->fetchAll();
    $subjects = db()->query("SELECT id, name, full_marks, pass_marks FROM subjects ORDER BY name")->fetchAll();
    $classes  = db()->query("SELECT id, CONCAT(name,' - ',COALESCE(section,'')) AS label FROM classes ORDER BY id")->fetchAll();
}

// === Save (single result) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $exam_term  = $_POST['exam_term'] ?? 'final';
    $marksMap   = $_POST['marks'] ?? []; // [subject_id => marks]
    $saved = 0;
    foreach ($marksMap as $sub_id => $marks) {
        $sub_id = (int)$sub_id;
        $marks  = trim($marks);
        if ($marks === '') continue;
        $marks  = (int)$marks;
        // Lookup full_marks for grade
        $full = 100;
        foreach ($subjects as $sb) if ((int)$sb['id'] === $sub_id) $full = (int)$sb['full_marks'];
        $percent = $full ? ($marks / $full) * 100 : 0;
        [$grade, ] = calc_grade($percent);

        try {
            $sql = "INSERT INTO results (student_id, subject_id, exam_term, marks_obtained, grade)
                    VALUES (?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), grade = VALUES(grade)";
            db()->prepare($sql)->execute([$student_id, $sub_id, $exam_term, $marks, $grade]);
            $saved++;
        } catch (PDOException $e) {
            flash_set('error', 'Could not save: ' . $e->getMessage());
            redirect('results.php');
        }
    }
    flash_set('success', "Saved $saved result entries.");
    redirect('results.php?action=new&student_id=' . $student_id . '&exam_term=' . urlencode($exam_term));
}

// === Delete ===
if ($action === 'delete' && $id && db_ok()) {
    db()->prepare('DELETE FROM results WHERE id = ?')->execute([$id]);
    flash_set('success', 'Result entry deleted.');
    redirect('results.php');
}

// === Listing ===
$results = [];
$filterStudent = (int)($_GET['student_id'] ?? 0);
$filterTerm    = $_GET['exam_term'] ?? '';
$filterClass   = (int)($_GET['class_id'] ?? 0);

if (db_ok() && $action === 'list') {
    $where = []; $args = [];
    if ($filterStudent) { $where[] = 'r.student_id = ?'; $args[] = $filterStudent; }
    if ($filterTerm)    { $where[] = 'r.exam_term = ?';  $args[] = $filterTerm; }
    if ($filterClass)   { $where[] = 's.class_id = ?';   $args[] = $filterClass; }
    $sql = "SELECT r.*, s.name AS student_name, s.roll_no, sb.name AS subject_name, sb.full_marks,
                   CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label
            FROM results r
            JOIN students s   ON s.id = r.student_id
            JOIN subjects sb  ON sb.id = r.subject_id
            LEFT JOIN classes c ON c.id = s.class_id"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY r.id DESC LIMIT 200';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $results = $stmt->fetchAll();
}

// === Pre-fill marks when entering for an existing student/term ===
$preStudent = (int)($_GET['student_id'] ?? 0);
$preTerm    = $_GET['exam_term'] ?? 'final';
$existing = []; // [subject_id => marks]
if ($action === 'new' && $preStudent && db_ok()) {
    $stmt = db()->prepare('SELECT subject_id, marks_obtained FROM results WHERE student_id = ? AND exam_term = ?');
    $stmt->execute([$preStudent, $preTerm]);
    foreach ($stmt->fetchAll() as $r) $existing[(int)$r['subject_id']] = $r['marks_obtained'];
}
?>

<div class="page-head">
    <div>
        <h1>Results</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Results</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="results.php?action=new" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Enter Results</a>
    <?php else: ?>
    <a href="results.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new'): ?>
<div class="card" style="max-width:780px;">
    <div class="card-h">
        <h3>Enter Result Marks</h3>
        <span class="badge badge-info">Existing entries are auto-loaded</span>
    </div>

    <form method="get" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        <input type="hidden" name="action" value="new">
        <select name="student_id" required style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;min-width:240px;">
            <option value="">— Select student —</option>
            <?php foreach ($students as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $preStudent===(int)$s['id']?'selected':'' ?>>
                <?= e($s['roll_no'] . ' · ' . $s['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="exam_term" style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
            <?php foreach (['first'=>'First Term','mid'=>'Mid Term','final'=>'Final Term'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $preTerm===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-light"><i class="bi bi-arrow-clockwise"></i> Load</button>
    </form>

    <?php if ($preStudent): ?>
    <form method="post">
        <input type="hidden" name="student_id" value="<?= $preStudent ?>">
        <input type="hidden" name="exam_term" value="<?= e($preTerm) ?>">

        <table class="tbl">
            <thead>
                <tr><th>Subject</th><th>Full Marks</th><th>Pass Marks</th><th style="width:160px;">Marks Obtained</th></tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $sb): ?>
                <tr>
                    <td><b><?= e($sb['name']) ?></b></td>
                    <td><?= $sb['full_marks'] ?></td>
                    <td><?= $sb['pass_marks'] ?></td>
                    <td>
                        <input type="number" name="marks[<?= $sb['id'] ?>]"
                            min="0" max="<?= $sb['full_marks'] ?>"
                            value="<?= e($existing[(int)$sb['id']] ?? '') ?>"
                            placeholder="0 - <?= $sb['full_marks'] ?>"
                            style="width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:8px;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:16px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save All</button>
            <a href="marksheet.php?student_id=<?= $preStudent ?>&exam_term=<?= e($preTerm) ?>" class="btn btn-light"><i class="bi bi-file-earmark-text"></i> View Marksheet</a>
        </div>
    </form>
    <?php else: ?>
    <div style="color:var(--muted);font-size:13px;padding:20px 0;text-align:center;">
        Select a student and term to begin entering marks.
    </div>
    <?php endif; ?>
</div>

<?php else: ?>

<div class="card">
    <div class="toolbar">
        <form class="left" method="get" style="display:flex;gap:10px;flex-wrap:wrap;">
            <select name="student_id" style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
                <option value="0">All students</option>
                <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filterStudent===(int)$s['id']?'selected':'' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="class_id" style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
                <option value="0">All classes</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterClass===(int)$c['id']?'selected':'' ?>><?= e($c['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="exam_term" style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
                <option value="">All terms</option>
                <?php foreach (['first'=>'First','mid'=>'Mid','final'=>'Final'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $filterTerm===$k?'selected':'' ?>><?= $v ?> Term</option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </form>
        <span class="badge badge-muted"><?= count($results) ?> entries</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>Roll</th><th>Student</th><th>Class</th><th>Subject</th>
                <th>Term</th><th>Marks</th><th>Grade</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$results): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">
                No result entries found. <a href="results.php?action=new">Add some</a>.
            </td></tr>
        <?php else: foreach ($results as $r):
            $percent = $r['full_marks'] ? ($r['marks_obtained'] / $r['full_marks']) * 100 : 0;
            [$g, $gColor] = calc_grade($percent);
        ?>
            <tr>
                <td><?= e($r['roll_no']) ?></td>
                <td><b><?= e($r['student_name']) ?></b></td>
                <td><?= e($r['class_label']) ?></td>
                <td><?= e($r['subject_name']) ?></td>
                <td><span class="badge badge-info"><?= ucfirst($r['exam_term']) ?></span></td>
                <td><?= $r['marks_obtained'] ?> / <?= $r['full_marks'] ?></td>
                <td><span class="badge" style="background:rgba(0,0,0,.04);color:<?= $gColor ?>;"><?= e($g) ?></span></td>
                <td>
                    <a class="icon-link" href="marksheet.php?student_id=<?= $r['student_id'] ?>&exam_term=<?= e($r['exam_term']) ?>" title="Marksheet"><i class="bi bi-file-earmark-text"></i></a>
                    <a class="icon-link" href="results.php?action=new&student_id=<?= $r['student_id'] ?>&exam_term=<?= e($r['exam_term']) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="results.php?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Delete this entry?');" title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
