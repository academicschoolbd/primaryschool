<?php
$pageTitle = 'Student Promotion';
require_once __DIR__ . '/../includes/header.php';

$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    flash_set('error', 'Only admins can promote students.');
    redirect('index.php');
}

$years   = all_years();
$current = current_year_id();
$fromYear = (int)($_GET['from_year'] ?? $current);
$toYear   = (int)($_GET['to_year']   ?? 0);

// Eligible "to_year" candidates: any year != fromYear
$toYearOptions = array_filter($years, fn($y) => (int)$y['id'] !== $fromYear);

// === Process promotion ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $toY     = (int)($_POST['to_year'] ?? 0);
    $promote = $_POST['promote'] ?? [];        // [student_id => new_class_id]
    if (!$toY) {
        flash_set('error', 'Pick a target year.');
        redirect('promote.php');
    }
    $promoted = 0;
    foreach ($promote as $sid => $newClassId) {
        $sid = (int)$sid; $newClassId = (int)$newClassId;
        if (!$sid || !$newClassId) continue;
        // Snapshot before for audit
        $bs = db()->prepare('SELECT id, name, roll_no, class_id, year_id FROM students WHERE id = ?');
        $bs->execute([$sid]);
        $before = $bs->fetch();
        if (!$before) continue;

        db()->prepare('UPDATE students SET class_id = ?, year_id = ? WHERE id = ?')
            ->execute([$newClassId, $toY, $sid]);

        audit_log('promote', 'student', $sid,
            ($before['name'] ?? '#'.$sid) . ' (roll ' . ($before['roll_no'] ?? '?') . ')',
            ['class_id' => $before['class_id'], 'year_id' => $before['year_id']],
            ['class_id' => $newClassId,         'year_id' => $toY]);
        $promoted++;
    }
    flash_set('success', "Promoted $promoted student(s) into year " . (string)(db()->query('SELECT name FROM academic_years WHERE id=' . $toY)->fetchColumn()));
    redirect('promote.php?from_year=' . $fromYear . '&to_year=' . $toY);
}

// Load students of $fromYear with their current class
$students = $targetClasses = [];
if (db_ok()) {
    $stmt = db()->prepare("
        SELECT s.id, s.name, s.roll_no, s.gender, s.photo, s.status,
               c.id AS class_id, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label
        FROM students s LEFT JOIN classes c ON c.id = s.class_id
        WHERE s.year_id = ? AND s.status = 'active'
        ORDER BY c.name, c.section, s.roll_no
    ");
    $stmt->execute([$fromYear]);
    $students = $stmt->fetchAll();

    if ($toYear) {
        $stmt = db()->prepare("SELECT id, CONCAT(name,' - ',COALESCE(section,'')) AS label FROM classes WHERE year_id = ? ORDER BY name, section");
        $stmt->execute([$toYear]);
        $targetClasses = $stmt->fetchAll();
    }
}
?>

<div class="page-head">
    <div>
        <h1>Student Promotion <span style="font-weight:400;color:var(--muted);font-size:14px;">· bulk-move students to next year</span></h1>
        <div class="crumbs"><a href="index.php">Home</a> / Promote</div>
    </div>
    <a href="years.php" class="btn btn-light"><i class="bi bi-calendar3"></i> Manage Years</a>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="card" style="margin-bottom:18px;">
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;align-items:end;">
        <div class="field" style="margin:0;">
            <label>From year (current students)</label>
            <select name="from_year" required>
                <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $fromYear===(int)$y['id']?'selected':'' ?>>
                    <?= e($y['name']) ?><?= $y['is_current'] ? ' (current)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin:0;">
            <label>To year (target)</label>
            <select name="to_year" required>
                <option value="">— Select target —</option>
                <?php foreach ($toYearOptions as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $toYear===(int)$y['id']?'selected':'' ?>>
                    <?= e($y['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-right-circle"></i> Load Students</button>
    </form>
</div>

<?php if ($toYear && $targetClasses): ?>
<form method="post">
    <input type="hidden" name="from_year" value="<?= $fromYear ?>">
    <input type="hidden" name="to_year"   value="<?= $toYear ?>">

    <div class="card">
        <div class="card-h">
            <h3><i class="bi bi-arrow-up-right-circle-fill"></i> Promote students</h3>
            <span class="badge badge-info"><?= count($students) ?> active students</span>
        </div>

        <?php if (!$students): ?>
        <div style="text-align:center;color:var(--muted);padding:30px;">
            No active students in this source year.
        </div>
        <?php else: ?>
        <div style="background:rgba(var(--accent-rgb),.08);border:1px solid rgba(var(--accent-rgb),.3);padding:12px 16px;border-radius:10px;margin-bottom:14px;font-size:13px;color:var(--accent-dark);">
            <i class="bi bi-info-circle"></i>
            For each student, pick the new class+section in the target year. Leave a row blank to skip it.
            Each promotion is recorded in the <a href="audit.php" style="color:var(--accent-dark);text-decoration:underline;">audit log</a> with before/after.
        </div>

        <table class="tbl">
            <thead>
                <tr>
                    <th>Roll</th><th>Name</th><th>Current Class</th>
                    <th style="width:240px;">Promote to (target year)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><b><?= e($s['roll_no']) ?></b></td>
                    <td>
                        <?php if (!empty($s['photo'])): ?>
                            <img src="<?= e(media_url($s['photo'])) ?>" style="width:30px;height:36px;object-fit:cover;border-radius:4px;margin-right:8px;vertical-align:middle;">
                        <?php endif; ?>
                        <?= e($s['name']) ?>
                    </td>
                    <td><?= e($s['class_label']) ?: '—' ?></td>
                    <td>
                        <select name="promote[<?= $s['id'] ?>]" style="width:100%;padding:7px 10px;border:1px solid var(--line);border-radius:6px;font-size:12px;">
                            <option value="">— Skip —</option>
                            <?php foreach ($targetClasses as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Promote selected students? This will move them to the target year and class. Each change is audit-logged.');">
                <i class="bi bi-check-lg"></i> Promote Selected
            </button>
            <span style="color:var(--muted);font-size:12px;">
                <i class="bi bi-shield-check"></i>
                Every promotion is recorded with old + new class_id and year_id.
            </span>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php elseif ($toYear): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--muted);">
    <i class="bi bi-exclamation-circle" style="font-size:32px;color:var(--accent);"></i>
    <p style="margin-top:12px;">The target year has <b>no classes yet</b>. Create classes for the new year first under <a href="classes.php?action=add-class">Classes → Add Class</a>.</p>
</div>
<?php else: ?>
<div class="card" style="text-align:center;padding:40px;color:var(--muted);">
    <i class="bi bi-arrow-up" style="font-size:32px;"></i>
    <p style="margin-top:12px;">Pick a source year + target year to begin promotion.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
