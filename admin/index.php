<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Live counts from MySQL (with safe fallback if DB offline)
$counts = ['students' => 0, 'teachers' => 0, 'classes' => 0, 'results' => 0];
$recentStudents = [];
$gradeDist = [];
$enrollTrend = ['labels' => [], 'students' => []];

if (db_ok()) {
    $counts['students'] = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $counts['teachers'] = (int) db()->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
    $counts['classes']  = (int) db()->query('SELECT COUNT(*) FROM classes')->fetchColumn();
    $counts['results']  = (int) db()->query('SELECT COUNT(*) FROM results')->fetchColumn();

    $recentStudents = db()->query("
        SELECT s.id, s.name, s.roll_no, s.status, s.gender,
               CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label
        FROM students s LEFT JOIN classes c ON c.id = s.class_id
        ORDER BY s.id DESC LIMIT 5
    ")->fetchAll();

    $gradeDist = db()->query("
        SELECT CONCAT(c.name,' ',COALESCE(c.section,'')) AS lbl, COUNT(s.id) AS cnt
        FROM classes c LEFT JOIN students s ON s.class_id = c.id
        GROUP BY c.id ORDER BY c.id
    ")->fetchAll();

    // Enrollment trend - last 6 months by created_at
    $rows = db()->query("
        SELECT DATE_FORMAT(created_at,'%b') AS m,
               DATE_FORMAT(created_at,'%Y-%m') AS ym,
               COUNT(*) AS cnt
        FROM students
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY ym ORDER BY ym
    ")->fetchAll();
    foreach ($rows as $r) {
        $enrollTrend['labels'][]   = $r['m'];
        $enrollTrend['students'][] = (int) $r['cnt'];
    }
}

// If trend is empty, fill placeholder so chart still renders
if (!$enrollTrend['labels']) {
    $enrollTrend['labels']   = ['Jan','Feb','Mar','Apr','May','Jun'];
    $enrollTrend['students'] = [0,0,0,0,0,$counts['students']];
}

$stats = [
    ['label' => 'Total Students', 'value' => number_format($counts['students']), 'icon' => 'bi-people-fill',         'class' => 'b1'],
    ['label' => 'Total Teachers', 'value' => number_format($counts['teachers']), 'icon' => 'bi-person-workspace',    'class' => 'b2'],
    ['label' => 'Active Classes', 'value' => number_format($counts['classes']),  'icon' => 'bi-bookmark-star-fill',  'class' => 'b3'],
    ['label' => 'Result Entries', 'value' => number_format($counts['results']),  'icon' => 'bi-clipboard-data-fill', 'class' => 'b4'],
];
?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Dashboard</div>
    </div>
    <div>
        <a href="students.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Student</a>
    </div>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="stat-grid">
    <?php foreach ($stats as $s): ?>
    <div class="stat">
        <div class="ico <?= $s['class'] ?>"><i class="bi <?= $s['icon'] ?>"></i></div>
        <div>
            <div class="v"><?= $s['value'] ?></div>
            <div class="l"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row-2" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-h">
            <h3>Enrollment Trend</h3>
            <span class="badge badge-info">Last 6 months</span>
        </div>
        <canvas id="enrollChart" height="110"></canvas>
    </div>

    <div class="card">
        <div class="card-h">
            <h3>Students by Class</h3>
        </div>
        <?php if ($gradeDist): ?>
            <canvas id="gradeChart" height="200"></canvas>
        <?php else: ?>
            <div style="text-align:center;color:var(--muted);padding:40px 0;">
                <i class="bi bi-pie-chart" style="font-size:32px;"></i>
                <div style="margin-top:8px;">No class data yet.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row-2">
    <div class="card">
        <div class="card-h">
            <h3>Recent Students</h3>
            <a href="students.php" class="btn btn-soft btn-sm">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <?php if ($recentStudents): ?>
        <table class="tbl">
            <thead>
                <tr><th>Name</th><th>Roll No</th><th>Class</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentStudents as $s): ?>
                <tr>
                    <td>
                        <span class="avatar-sm" style="background:<?= avatar_color($s['name']) ?>">
                            <?= strtoupper(substr($s['name'],0,1)) ?>
                        </span>
                        <?= e($s['name']) ?>
                    </td>
                    <td><?= e($s['roll_no']) ?></td>
                    <td><?= e($s['class_label']) ?></td>
                    <td>
                        <?php if ($s['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="text-align:center;color:var(--muted);padding:30px 0;">
                <i class="bi bi-inbox" style="font-size:32px;"></i>
                <div style="margin-top:8px;">No students yet. <a href="students.php?action=new">Add the first one</a>.</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-h"><h3>Quick Links</h3></div>
        <div style="display:grid;gap:10px;">
            <a href="teachers.php?action=new" class="btn btn-light" style="justify-content:flex-start;"><i class="bi bi-person-plus"></i> Add new teacher</a>
            <a href="students.php?action=new" class="btn btn-light" style="justify-content:flex-start;"><i class="bi bi-person-plus-fill"></i> Add new student</a>
            <a href="classes.php?action=new" class="btn btn-light" style="justify-content:flex-start;"><i class="bi bi-plus-square"></i> Create class</a>
            <a href="results.php?action=new" class="btn btn-light" style="justify-content:flex-start;"><i class="bi bi-pencil-square"></i> Enter results</a>
            <a href="marksheet.php" class="btn btn-light" style="justify-content:flex-start;"><i class="bi bi-printer"></i> Print marksheet</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Read theme colors from CSS variables so the chart matches the saved theme.
const cs = getComputedStyle(document.documentElement);
const brand    = cs.getPropertyValue('--primary').trim() || '#1a237e';
const brandRgb = cs.getPropertyValue('--primary-rgb').trim() || '26,35,126';
const accent   = cs.getPropertyValue('--accent').trim() || '#f9a825';
new Chart(document.getElementById('enrollChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($enrollTrend['labels']) ?>,
        datasets: [{
            label: 'New students',
            data: <?= json_encode($enrollTrend['students']) ?>,
            borderColor: brand, backgroundColor: 'rgba(' + brandRgb + ',.12)',
            fill: true, tension: .35, borderWidth: 2,
            pointBackgroundColor: brand, pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } }
    }
});

<?php if ($gradeDist): ?>
new Chart(document.getElementById('gradeChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($gradeDist, 'lbl')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($gradeDist, 'cnt'))) ?>,
            backgroundColor: [brand, accent, '#10b981', '#0ea5e9', '#8b5cf6', '#ec4899', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: { cutout: '65%', plugins: { legend: { position: 'bottom' } } }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
