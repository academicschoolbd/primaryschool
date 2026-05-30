<?php
$pageTitle = 'Class Routine';
require_once __DIR__ . '/../includes/header.php';

$years    = all_years();
$classes  = $subjects = $teachers = [];
if (db_ok()) {
    $classes  = db()->query("SELECT id, CONCAT(name,' - ',COALESCE(section,'')) AS label FROM classes ORDER BY name, section")->fetchAll();
    $subjects = db()->query("SELECT id, name FROM subjects ORDER BY name")->fetchAll();
    $teachers = db()->query("SELECT id, name FROM teachers WHERE status='active' ORDER BY name")->fetchAll();
}

$yearId  = (int)($_GET['year_id']  ?? current_year_id() ?? 0);
$classId = (int)($_GET['class_id'] ?? 0);

$days = [
    0 => 'রবি',
    1 => 'সোম',
    2 => 'মঙ্গল',
    3 => 'বুধ',
    4 => 'বৃহঃ',
    5 => 'শুক্র',
    6 => 'শনি',
];
$periods = range(1, 8);

// Default time slots for periods 1-8
$periodTimes = [
    1 => ['08:00', '08:45'],
    2 => ['08:45', '09:30'],
    3 => ['09:30', '10:15'],
    4 => ['10:30', '11:15'],
    5 => ['11:15', '12:00'],
    6 => ['12:00', '12:45'],
    7 => ['13:30', '14:15'],
    8 => ['14:15', '15:00'],
];

$cells = []; // [day][period] => row
if (db_ok() && $classId && $yearId) {
    $stmt = db()->prepare('SELECT * FROM routine WHERE class_id=? AND year_id=?');
    $stmt->execute([$classId, $yearId]);
    foreach ($stmt->fetchAll() as $r) {
        $cells[(int)$r['day_of_week']][(int)$r['period']] = $r;
    }
}
?>

<div class="page-head">
    <div>
        <h1>Class Routine</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Routine</div>
    </div>
    <span class="badge badge-info">Year: <b><?= e(current_year_name()) ?></b></span>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="card" style="margin-bottom:18px;">
    <div class="card-h"><h3><i class="bi bi-calendar-week-fill"></i> Pick a class</h3></div>
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;align-items:end;">
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
        <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-clockwise"></i> Load Grid</button>
    </form>
</div>

<?php if ($classId && $yearId): ?>
<div class="card">
    <div class="card-h">
        <h3><i class="bi bi-grid-3x3-gap-fill"></i> 7-day × 8-period grid</h3>
        <span style="color:var(--muted);font-size:12px;">
            <i class="bi bi-info-circle"></i> Pick a subject + teacher per cell. Saves automatically per cell on change.
        </span>
    </div>

    <div class="table-responsive">
        <table class="tbl routine-grid">
            <thead>
                <tr>
                    <th style="width:90px;">Period</th>
                    <?php foreach ($days as $d): ?>
                    <th style="text-align:center;"><?= e($d) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periods as $p):
                    [$start, $end] = $periodTimes[$p] ?? ['', ''];
                ?>
                <tr>
                    <td>
                        <b style="color:var(--primary);">P<?= $p ?></b>
                        <div style="font-size:10px;color:var(--muted);"><?= $start ?>–<?= $end ?></div>
                    </td>
                    <?php foreach ($days as $dKey => $dLbl):
                        $cur = $cells[$dKey][$p] ?? null;
                        $sid = $cur['subject_id'] ?? '';
                        $tid = $cur['teacher_id'] ?? '';
                    ?>
                    <td style="vertical-align:top;padding:6px;">
                        <select class="rt-cell rt-subject"
                                data-day="<?= $dKey ?>" data-period="<?= $p ?>"
                                data-start="<?= $start ?>" data-end="<?= $end ?>"
                                style="width:100%;padding:5px 6px;border:1px solid var(--line);border-radius:5px;font-size:11px;margin-bottom:3px;">
                            <option value="">— Subject —</option>
                            <?php foreach ($subjects as $sb): ?>
                            <option value="<?= $sb['id'] ?>" <?= (string)$sid===(string)$sb['id']?'selected':'' ?>><?= e($sb['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="rt-cell rt-teacher"
                                data-day="<?= $dKey ?>" data-period="<?= $p ?>"
                                style="width:100%;padding:5px 6px;border:1px solid var(--line);border-radius:5px;font-size:11px;color:var(--muted);">
                            <option value="">— Teacher —</option>
                            <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (string)$tid===(string)$t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/routine.php?class_id=<?= $classId ?>&year_id=<?= $yearId ?>" target="_blank" class="btn btn-light"><i class="bi bi-eye"></i> Public View</a>
        <span style="color:var(--muted);font-size:12px;">
            <i class="bi bi-shield-check"></i>
            Each cell saves via AJAX with audit log.
        </span>
    </div>
</div>

<style>
.routine-grid td { padding: 6px; vertical-align: top; min-width: 130px; }
.routine-grid .rt-cell:focus { outline: none; border-color: var(--primary); }
.routine-grid .rt-cell.is-saving { opacity: .5; }
</style>

<script>
(function() {
    const classId = <?= (int)$classId ?>;
    const yearId  = <?= (int)$yearId ?>;
    document.querySelectorAll('.rt-cell').forEach(sel => {
        sel.addEventListener('change', async function() {
            const day    = this.dataset.day, period = this.dataset.period;
            const row    = this.closest('td');
            const sub    = row.querySelector('.rt-subject');
            const teach  = row.querySelector('.rt-teacher');
            const start  = sub.dataset.start || '';
            const end    = sub.dataset.end || '';
            row.querySelectorAll('.rt-cell').forEach(c => c.classList.add('is-saving'));
            try {
                const fd = new FormData();
                fd.set('class_id', classId);
                fd.set('year_id',  yearId);
                fd.set('day_of_week', day);
                fd.set('period', period);
                fd.set('subject_id', sub.value);
                fd.set('teacher_id', teach.value);
                fd.set('start_time', start);
                fd.set('end_time',   end);
                const r = await fetch(window.APP.api + '/save_routine.php', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const j = await r.json();
                if (!j.ok) throw new Error(j.msg);
                Toast.success(j.msg, 1500);
            } catch(e) {
                Toast.error(e.message);
            } finally {
                row.querySelectorAll('.rt-cell').forEach(c => c.classList.remove('is-saving'));
            }
        });
    });
})();
</script>

<?php else: ?>
<div class="card" style="text-align:center;padding:40px;color:var(--muted);">
    <i class="bi bi-arrow-up" style="font-size:32px;"></i>
    <p style="margin-top:12px;">Select Year + Class+Section to load the routine grid.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
