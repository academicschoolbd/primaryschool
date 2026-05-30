<?php
$pageTitle  = 'ক্লাস রুটিন | Class Routine';
$activeMenu = 'academic';
require_once __DIR__ . '/includes/public_header.php';

$classes = $years = [];
if (db_ok()) {
    $classes = db()->query("
        SELECT c.id, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS label, c.year_id, ay.name AS year_name
        FROM classes c LEFT JOIN academic_years ay ON ay.id = c.year_id
        ORDER BY ay.name DESC, c.name, c.section
    ")->fetchAll();
    $years = all_years();
}
$curYear = current_year_id();
$classId = (int)($_GET['class_id'] ?? 0);
$yearId  = (int)($_GET['year_id']  ?? $curYear ?? 0);

$days = [
    0 => 'রবিবার', 1 => 'সোমবার', 2 => 'মঙ্গলবার',
    3 => 'বুধবার', 4 => 'বৃহস্পতিবার', 5 => 'শুক্রবার', 6 => 'শনিবার',
];

$cells = []; $classInfo = null;
if (db_ok() && $classId) {
    $stmt = db()->prepare("SELECT c.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS label, ay.name AS year_name
                           FROM classes c LEFT JOIN academic_years ay ON ay.id = c.year_id
                           WHERE c.id = ?");
    $stmt->execute([$classId]);
    $classInfo = $stmt->fetch();

    $stmt = db()->prepare("
        SELECT r.*, sb.name AS subject_name, t.name AS teacher_name
        FROM routine r
        LEFT JOIN subjects sb ON sb.id = r.subject_id
        LEFT JOIN teachers t  ON t.id = r.teacher_id
        WHERE r.class_id = ? " . ($yearId ? 'AND (r.year_id = ? OR r.year_id IS NULL)' : '') . "
        ORDER BY r.period
    ");
    $args = [$classId];
    if ($yearId) $args[] = $yearId;
    $stmt->execute($args);
    foreach ($stmt->fetchAll() as $r) {
        $cells[(int)$r['day_of_week']][(int)$r['period']] = $r;
    }
}
$periods = range(1, 8);
?>

<div class="t2-page-hero">
    <div class="container">
        <h1><i class="fa fa-calendar-week me-2" style="color:var(--accent);"></i>ক্লাস রুটিন</h1>
        <div class="t2-breadcrumb">
            <a href="<?= BASE_URL ?>/"><i class="fa fa-home"></i> হোম</a>
            <i class="fa fa-chevron-right"></i>
            <span>ক্লাস রুটিন</span>
        </div>
    </div>
</div>

<section style="padding:32px 0 50px;">
    <div class="container">

        <div class="t2-filter-card" data-aos="fade-up">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label>শ্রেণী &amp; শাখা</label>
                    <select name="class_id" required>
                        <option value="">— বেছে নিন —</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classId===(int)$c['id']?'selected':'' ?>>
                            <?= e($c['label']) ?> <?= $c['year_name'] ? '(' . e($c['year_name']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>শিক্ষাবর্ষ</label>
                    <select name="year_id">
                        <option value="0">সকল</option>
                        <?php foreach ($years as $y): ?>
                        <option value="<?= $y['id'] ?>" <?= $yearId===(int)$y['id']?'selected':'' ?>>
                            <?= e($y['name']) ?><?= $y['is_current'] ? ' (চলমান)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn-primary-custom w-100" type="submit"><i class="fa fa-search me-1"></i> রুটিন দেখুন</button>
                </div>
            </form>
        </div>

        <?php if ($classInfo): ?>
        <div class="t2-card mb-3">
            <div class="t2-card-header">
                <i class="fa fa-calendar-week"></i>
                <?= e($classInfo['label']) ?>
                <?php if ($classInfo['year_name']): ?>
                <span style="margin-left:auto;font-size:12px;background:rgba(255,255,255,.18);padding:3px 12px;border-radius:20px;">
                    শিক্ষাবর্ষ <?= e($classInfo['year_name']) ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="t2-card-body p-0">
                <div class="table-responsive">
                    <table class="t2-routine-table">
                        <thead>
                            <tr>
                                <th>সময় / Period</th>
                                <?php foreach ($days as $d): ?>
                                <th><?= e($d) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($periods as $p):
                                $hasAny = false;
                                foreach ($days as $dKey => $_) {
                                    if (!empty($cells[$dKey][$p])) { $hasAny = true; break; }
                                }
                                if (!$hasAny) continue;
                            ?>
                            <tr>
                                <td class="rt-period">
                                    <b>পিরিয়ড <?= bn_num($p) ?></b>
                                    <?php
                                    $sample = null;
                                    foreach ($days as $dKey => $_) if (!empty($cells[$dKey][$p])) { $sample = $cells[$dKey][$p]; break; }
                                    if ($sample && $sample['start_time'] && $sample['end_time']):
                                    ?>
                                    <div style="font-size:11px;color:var(--text-muted);"><?= substr($sample['start_time'],0,5) ?>–<?= substr($sample['end_time'],0,5) ?></div>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($days as $dKey => $dLbl):
                                    $cell = $cells[$dKey][$p] ?? null;
                                ?>
                                <td>
                                    <?php if ($cell): ?>
                                        <div class="rt-subject"><?= e($cell['subject_name'] ?: '—') ?></div>
                                        <?php if ($cell['teacher_name']): ?>
                                        <div class="rt-teacher"><i class="fa fa-user"></i> <?= e($cell['teacher_name']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--border);">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($cells)): ?>
                            <tr><td colspan="<?= count($days)+1 ?>" style="text-align:center;color:var(--text-muted);padding:40px;">
                                এই শ্রেণীর জন্য এখনো রুটিন তৈরি হয়নি।
                            </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php elseif ($classId): ?>
        <div class="t2-result-empty"><i class="fa fa-search"></i><p>শ্রেণী খুঁজে পাওয়া যায়নি।</p></div>
        <?php else: ?>
        <div class="t2-result-empty"><i class="fa fa-arrow-up"></i><p>উপরে শ্রেণী নির্বাচন করুন।</p></div>
        <?php endif; ?>
    </div>
</section>

<style>
.t2-routine-table { width:100%; border-collapse:collapse; font-size:13px; }
.t2-routine-table thead th {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff; padding: 11px 12px; text-align: center;
    font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;
    border-bottom: 3px solid var(--accent);
}
.t2-routine-table tbody tr { border-bottom: 1px solid var(--border); }
.t2-routine-table tbody tr:hover { background: var(--bg-section); }
.t2-routine-table td { padding: 10px 12px; vertical-align: middle; text-align: center; }
.t2-routine-table .rt-period { background: var(--bg-section); text-align: left; min-width: 110px; color: var(--primary); }
.rt-subject { font-weight: 700; color: var(--primary); font-size: 13px; }
.rt-teacher { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
.rt-teacher i { color: var(--accent); }
@media (max-width: 576px) {
    .t2-routine-table th, .t2-routine-table td { padding: 7px 6px; font-size: 11px; }
    .rt-subject { font-size: 11px; }
    .rt-teacher { font-size: 10px; }
}
</style>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
