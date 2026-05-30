<?php
$pageTitle = 'Daily Attendance';
require_once __DIR__ . '/../includes/header.php';

$years   = all_years();
$classes = [];
if (db_ok()) {
    $classes = db()->query("
        SELECT id, CONCAT(name,' - ',COALESCE(section,'')) AS label
        FROM classes ORDER BY name, section
    ")->fetchAll();
}

$yearId  = (int)($_GET['year_id']  ?? current_year_id() ?? 0);
$classId = (int)($_GET['class_id'] ?? 0);
$date    = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$students = $existing = [];
if (db_ok() && $classId) {
    $stmt = db()->prepare("
        SELECT id, roll_no, name, gender, photo
        FROM students
        WHERE class_id = ? AND status = 'active'
        ORDER BY roll_no
    ");
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT student_id, status, note FROM attendance WHERE class_id = ? AND date = ?');
    $stmt->execute([$classId, $date]);
    foreach ($stmt->fetchAll() as $r) {
        $existing[(int)$r['student_id']] = ['status' => $r['status'], 'note' => $r['note']];
    }
}
?>

<div class="page-head">
    <div>
        <h1>Daily Attendance</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Attendance</div>
    </div>
    <span class="badge badge-info"><i class="bi bi-calendar"></i> <?= e(date('M d, Y', strtotime($date))) ?></span>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="card" style="margin-bottom:18px;">
    <div class="card-h"><h3><i class="bi bi-funnel-fill"></i> Pick a class &amp; date</h3>
        <span class="badge badge-info">Year: <b><?= e(current_year_name()) ?></b></span>
    </div>
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:12px;align-items:end;">
        <div class="field" style="margin:0;">
            <label>Year</label>
            <select name="year_id">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $yearId===(int)$y['id']?'selected':'' ?>>
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
            <label>Date</label>
            <input type="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>">
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-clockwise"></i> Load</button>
    </form>
</div>

<?php if ($classId && $students): ?>
<div class="card">
    <div class="card-h">
        <h3><i class="bi bi-clipboard-check-fill"></i> Mark attendance for <?= count($students) ?> students</h3>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button type="button" class="btn btn-soft btn-sm" onclick="bulkSet('present')"><i class="bi bi-check-circle"></i> All Present</button>
            <button type="button" class="btn btn-soft btn-sm" onclick="bulkSet('absent')"><i class="bi bi-x-circle"></i> All Absent</button>
        </div>
    </div>

    <form id="attendanceForm">
        <input type="hidden" name="class_id" value="<?= $classId ?>">
        <input type="hidden" name="year_id"  value="<?= $yearId ?>">
        <input type="hidden" name="date"     value="<?= e($date) ?>">

        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="width:50px;">Photo</th>
                    <th style="width:80px;">Roll</th>
                    <th>Name</th>
                    <th style="width:330px;">Status</th>
                    <th style="width:160px;">Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $i => $s):
                    $cur = $existing[(int)$s['id']]['status'] ?? 'present';
                    $note = $existing[(int)$s['id']]['note'] ?? '';
                ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td>
                        <?php if (!empty($s['photo'])): ?>
                            <img src="<?= e(media_url($s['photo'])) ?>" style="width:36px;height:42px;object-fit:cover;border-radius:5px;border:1px solid var(--line);">
                        <?php else: ?>
                            <span class="avatar-sm" style="background:<?= avatar_color($s['name']) ?>;width:36px;height:36px;"><?= strtoupper(substr($s['name'],0,1)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><b><?= e($s['roll_no']) ?></b></td>
                    <td><?= e($s['name']) ?></td>
                    <td>
                        <div class="att-radio-group">
                            <?php foreach ([
                                'present' => ['Present', '#10b981'],
                                'absent'  => ['Absent',  '#ef4444'],
                                'late'    => ['Late',    '#f59e0b'],
                                'leave'   => ['Leave',   '#6b7280'],
                            ] as $key => [$lbl, $color]): ?>
                            <label class="att-pill <?= $cur===$key?'active':'' ?>" style="--c:<?= $color ?>;">
                                <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $key ?>" <?= $cur===$key?'checked':'' ?>>
                                <span><?= $lbl ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="note[<?= $s['id'] ?>]" value="<?= e($note) ?>" placeholder="optional"
                               style="width:100%;padding:6px 8px;border:1px solid var(--line);border-radius:6px;font-size:12px;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:18px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Attendance</button>
            <span style="color:var(--muted);font-size:12px;">
                <i class="bi bi-shield-check"></i>
                Every attendance change is recorded in the audit log.
            </span>
        </div>
    </form>
</div>

<style>
.att-radio-group { display:flex; gap:6px; flex-wrap:wrap; }
.att-pill {
    display:inline-flex; align-items:center; gap:4px;
    padding: 5px 12px; border-radius:20px; cursor:pointer;
    font-size:12px; font-weight:600; border:1.5px solid var(--line);
    background:#fff; color:var(--muted); transition:.15s;
}
.att-pill input { display:none; }
.att-pill.active { background:var(--c); color:#fff; border-color:var(--c); }
.att-pill:hover:not(.active) { border-color:var(--c); color:var(--c); }
</style>

<script>
document.querySelectorAll('.att-pill input').forEach(input => {
    input.addEventListener('change', function() {
        // mark sibling pills inactive, this active
        const group = this.closest('.att-radio-group');
        group.querySelectorAll('.att-pill').forEach(p => p.classList.remove('active'));
        this.closest('.att-pill').classList.add('active');
    });
});

function bulkSet(status) {
    document.querySelectorAll('.att-radio-group').forEach(g => {
        const target = g.querySelector('input[value="' + status + '"]');
        if (target) { target.checked = true; target.dispatchEvent(new Event('change')); }
    });
}

document.getElementById('attendanceForm').addEventListener('submit', async function(ev) {
    ev.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type=submit]');
    const old = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Saving…';
    try {
        const r = await fetch(window.APP.api + '/save_attendance.php', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
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

<?php elseif ($classId): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--muted);">
    <i class="bi bi-people" style="font-size:32px;"></i>
    <p style="margin-top:12px;">No active students in this class+section.</p>
</div>
<?php else: ?>
<div class="card" style="text-align:center;padding:40px;color:var(--muted);">
    <i class="bi bi-arrow-up" style="font-size:32px;"></i>
    <p style="margin-top:12px;">Select Class+Section + Date to load the attendance grid.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
