<?php
$pageTitle = 'Subject-wise Mark Entry';
require_once __DIR__ . '/../includes/header.php';

$years    = all_years();
$subjects = $classes = [];
if (db_ok()) {
    $subjects = db()->query("SELECT id, name, full_marks, pass_marks FROM subjects ORDER BY name")->fetchAll();
    $classes  = db()->query("SELECT id, CONCAT(name,' - ',COALESCE(section,'')) AS label, name AS class_name, section FROM classes ORDER BY name, section")->fetchAll();
}

$yearId    = (int)($_GET['year_id']    ?? current_year_id() ?? 0);
$classId   = (int)($_GET['class_id']   ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? 0);
$term      = $_GET['exam_term'] ?? 'final';

// Load students + existing marks for this filter combination
$students = []; $existing = []; $subjectInfo = null;
if (db_ok() && $classId && $subjectId) {
    $stmt = db()->prepare("SELECT s.id, s.roll_no, s.name, s.photo, s.gender FROM students s WHERE s.class_id = ? AND s.status='active' ORDER BY s.roll_no");
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT * FROM subjects WHERE id = ?');
    $stmt->execute([$subjectId]);
    $subjectInfo = $stmt->fetch();

    $stmt = db()->prepare('SELECT student_id, marks_obtained FROM results WHERE subject_id=? AND exam_term=? AND year_id=?');
    $stmt->execute([$subjectId, $term, $yearId]);
    foreach ($stmt->fetchAll() as $r) $existing[(int)$r['student_id']] = $r['marks_obtained'];
}
?>

<div class="page-head">
    <div>
        <h1>Subject-wise Mark Entry</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Mark Entry</div>
    </div>
    <a href="results.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Per-Student Mode</a>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<!-- Filter card -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-h"><h3><i class="bi bi-funnel-fill"></i> Filter</h3>
        <span class="badge badge-info">Year auto-set to <b><?= e(current_year_name()) ?></b></span>
    </div>
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:12px;align-items:end;">
        <div class="field" style="margin:0;">
            <label>Academic Year</label>
            <select name="year_id">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $yearId === (int)$y['id'] ? 'selected' : '' ?>>
                    <?= e($y['name']) ?><?= $y['is_current'] ? ' (current)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin:0;">
            <label>Class &amp; Section <span style="color:#dc2626;">*</span></label>
            <select name="class_id" required>
                <option value="">— Select —</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $classId===(int)$c['id']?'selected':'' ?>><?= e($c['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin:0;">
            <label>Subject <span style="color:#dc2626;">*</span></label>
            <select name="subject_id" required>
                <option value="">— Select —</option>
                <?php foreach ($subjects as $sb): ?>
                <option value="<?= $sb['id'] ?>" <?= $subjectId===(int)$sb['id']?'selected':'' ?>><?= e($sb['name']) ?> (<?= $sb['full_marks'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin:0;">
            <label>Exam Term</label>
            <select name="exam_term">
                <?php foreach (['first'=>'First Term','mid'=>'Mid Term','final'=>'Final Term'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $term===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-clockwise"></i> Load Students</button>
    </form>
</div>

<?php if ($classId && $subjectId && $subjectInfo): ?>
<div class="card">
    <div class="card-h">
        <h3>
            <i class="bi bi-pencil-square"></i>
            <?= e($subjectInfo['name']) ?>
            <span style="font-weight:400;color:var(--muted);font-size:13px;">·
                Full <?= $subjectInfo['full_marks'] ?> · Pass <?= $subjectInfo['pass_marks'] ?>
            </span>
        </h3>
        <span class="badge badge-info"><?= count($students) ?> students</span>
    </div>

    <?php if (!$students): ?>
    <div style="text-align:center;color:var(--muted);padding:30px;">
        No active students in this class+section.
    </div>
    <?php else: ?>
    <form id="markGridForm">
        <input type="hidden" name="year_id"    value="<?= $yearId ?>">
        <input type="hidden" name="class_id"   value="<?= $classId ?>">
        <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
        <input type="hidden" name="exam_term"  value="<?= e($term) ?>">

        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:50px;">Photo</th>
                    <th style="width:80px;">Roll</th>
                    <th>Name</th>
                    <th style="width:70px;">Gender</th>
                    <th style="width:140px;">Marks (0-<?= $subjectInfo['full_marks'] ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td>
                        <?php if (!empty($s['photo'])): ?>
                            <img src="<?= e(media_url($s['photo'])) ?>" style="width:36px;height:42px;object-fit:cover;border-radius:5px;border:1px solid var(--line);">
                        <?php else: ?>
                            <span class="avatar-sm" style="background:<?= avatar_color($s['name']) ?>;width:36px;height:36px;"><?= strtoupper(substr($s['name'],0,1)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><b><?= e($s['roll_no']) ?></b></td>
                    <td><?= e($s['name']) ?></td>
                    <td><?= ucfirst($s['gender']) ?></td>
                    <td>
                        <input type="number" name="marks[<?= $s['id'] ?>]"
                               min="0" max="<?= $subjectInfo['full_marks'] ?>"
                               value="<?= e($existing[(int)$s['id']] ?? '') ?>"
                               placeholder="—"
                               style="width:100%;padding:8px 10px;border:1.5px solid var(--line);border-radius:8px;font-weight:600;text-align:center;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:18px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save All Marks</button>
            <span style="color:var(--muted);font-size:12px;">
                <i class="bi bi-shield-check"></i>
                Every change is recorded in the audit log with your name &amp; timestamp.
            </span>
        </div>
    </form>

    <script>
    document.getElementById('markGridForm').addEventListener('submit', async function(ev) {
        ev.preventDefault();
        const fd = new FormData(this);
        const btn = this.querySelector('button[type=submit]');
        const old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving…';
        try {
            const r = await fetch(window.APP.api + '/save_marks_grid.php', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const j = await r.json();
            if (!j.ok) throw new Error(j.msg || 'Save failed');
            Toast.success(j.msg);
        } catch(e) {
            Toast.error(e.message);
        } finally {
            btn.disabled = false; btn.innerHTML = old;
        }
    });
    </script>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card" style="text-align:center;padding:50px;color:var(--muted);">
    <i class="bi bi-arrow-up" style="font-size:32px;"></i>
    <p style="margin-top:12px;">Select Class+Section and Subject to load the mark-entry grid.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
