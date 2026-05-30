<?php
$pageTitle = 'Marksheet';
require_once __DIR__ . '/../includes/header.php';

$studentId = (int)($_GET['student_id'] ?? 0);
$term      = $_GET['exam_term'] ?? 'final';

$students = $student = $rows = [];
$totalObt = 0; $totalMax = 0;

if (db_ok()) {
    $students = db()->query("SELECT id, roll_no, name FROM students ORDER BY name")->fetchAll();

    if ($studentId) {
        $stmt = db()->prepare("
            SELECT s.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label, t.name AS teacher_name
            FROM students s
            LEFT JOIN classes c ON c.id = s.class_id
            LEFT JOIN teachers t ON t.id = c.teacher_id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();

        if ($student) {
            $stmt = db()->prepare("
                SELECT sb.id, sb.name, sb.full_marks, sb.pass_marks,
                       COALESCE(r.marks_obtained, 0) AS marks_obtained,
                       r.grade
                FROM subjects sb
                LEFT JOIN results r ON r.subject_id = sb.id AND r.student_id = ? AND r.exam_term = ?
                ORDER BY sb.id
            ");
            $stmt->execute([$studentId, $term]);
            $rows = $stmt->fetchAll();

            foreach ($rows as $r) {
                $totalObt += (int)$r['marks_obtained'];
                $totalMax += (int)$r['full_marks'];
            }
        }
    }
}

$overallPercent = $totalMax ? ($totalObt / $totalMax) * 100 : 0;
[$overallGrade, $overallColor] = calc_grade($overallPercent);
$termNames = ['first' => 'First Term', 'mid' => 'Mid Term', 'final' => 'Final Term'];
$failed = false;
foreach ($rows as $r) if ($r['marks_obtained'] < $r['pass_marks']) { $failed = true; break; }
?>

<div class="page-head no-print">
    <div>
        <h1>Marksheet</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Marksheet</div>
    </div>
    <?php if ($student): ?>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-light" onclick="window.print();"><i class="bi bi-printer"></i> Print</button>
        <a href="results.php?action=new&student_id=<?= $studentId ?>&exam_term=<?= e($term) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit Marks</a>
    </div>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>

<!-- Selector -->
<div class="card no-print" style="margin-bottom:20px;">
    <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:240px;">
            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Student</label>
            <select name="student_id" required style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;">
                <option value="">— Select —</option>
                <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $studentId===(int)$s['id']?'selected':'' ?>>
                    <?= e($s['roll_no'] . ' · ' . $s['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Term</label>
            <select name="exam_term" style="padding:11px 14px;border:1px solid var(--line);border-radius:10px;">
                <?php foreach ($termNames as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $term===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Generate</button>
    </form>
</div>

<?php if ($student): ?>
<!-- Printable marksheet -->
<div class="card" id="marksheet" style="padding:0;overflow:hidden;">
    <!-- Header band -->
    <div style="background:linear-gradient(135deg,#4f46e5,#8b5cf6);color:#fff;padding:28px 32px;display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,.18);border-radius:14px;display:grid;place-items:center;font-size:26px;font-weight:800;">E</div>
            <div>
                <div style="font-size:22px;font-weight:700;"><?= APP_NAME ?></div>
                <div style="opacity:.85;font-size:13px;"><?= APP_TAGLINE ?></div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:12px;opacity:.85;text-transform:uppercase;letter-spacing:.08em;">Marksheet</div>
            <div style="font-size:18px;font-weight:600;"><?= e($termNames[$term] ?? $term) ?></div>
            <div style="font-size:12px;opacity:.85;"><?= date('F Y') ?></div>
        </div>
    </div>

    <!-- Student info -->
    <div style="padding:24px 32px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Student</div>
            <div style="font-weight:700;font-size:15px;"><?= e($student['name']) ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Roll No</div>
            <div style="font-weight:700;font-size:15px;"><?= e($student['roll_no']) ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Class</div>
            <div style="font-weight:700;font-size:15px;"><?= e($student['class_label'] ?: '—') ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Class Teacher</div>
            <div style="font-weight:700;font-size:15px;"><?= e($student['teacher_name'] ?: '—') ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Parent / Guardian</div>
            <div style="font-weight:600;font-size:14px;"><?= e($student['parent_name'] ?: '—') ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Date of Birth</div>
            <div style="font-weight:600;font-size:14px;"><?= $student['dob'] ? date('M d, Y', strtotime($student['dob'])) : '—' ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Phone</div>
            <div style="font-weight:600;font-size:14px;"><?= e($student['phone'] ?: '—') ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Issued On</div>
            <div style="font-weight:600;font-size:14px;"><?= date('M d, Y') ?></div>
        </div>
    </div>

    <!-- Marks table -->
    <div style="padding:24px 32px;">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Subject</th>
                    <th style="text-align:center;">Full Marks</th>
                    <th style="text-align:center;">Pass Marks</th>
                    <th style="text-align:center;">Obtained</th>
                    <th style="text-align:center;">%</th>
                    <th style="text-align:center;">Grade</th>
                    <th style="text-align:center;">Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r):
                    $pct = $r['full_marks'] ? ($r['marks_obtained']/$r['full_marks'])*100 : 0;
                    [$g, $gColor] = calc_grade($pct);
                    $isPass = $r['marks_obtained'] >= $r['pass_marks'];
                ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><b><?= e($r['name']) ?></b></td>
                    <td style="text-align:center;"><?= $r['full_marks'] ?></td>
                    <td style="text-align:center;"><?= $r['pass_marks'] ?></td>
                    <td style="text-align:center;font-weight:700;"><?= $r['marks_obtained'] ?></td>
                    <td style="text-align:center;"><?= number_format($pct, 1) ?>%</td>
                    <td style="text-align:center;">
                        <span style="display:inline-block;padding:3px 10px;border-radius:99px;background:rgba(0,0,0,.04);color:<?= $gColor ?>;font-weight:700;font-size:12px;"><?= e($g) ?></span>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($isPass): ?>
                            <span class="badge badge-success">Pass</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Fail</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;font-weight:700;">
                    <td colspan="2" style="padding:14px;">Total</td>
                    <td style="text-align:center;"><?= $totalMax ?></td>
                    <td></td>
                    <td style="text-align:center;"><?= $totalObt ?></td>
                    <td style="text-align:center;"><?= number_format($overallPercent, 1) ?>%</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary band -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-top:1px solid var(--line);">
        <div style="padding:18px 24px;border-right:1px solid var(--line);">
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Total Marks</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;"><?= $totalObt ?> / <?= $totalMax ?></div>
        </div>
        <div style="padding:18px 24px;border-right:1px solid var(--line);">
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Percentage</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;"><?= number_format($overallPercent, 2) ?>%</div>
        </div>
        <div style="padding:18px 24px;border-right:1px solid var(--line);">
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Overall Grade</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;color:<?= $overallColor ?>;"><?= e($overallGrade) ?></div>
        </div>
        <div style="padding:18px 24px;">
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Result</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;color:<?= $failed ? '#b91c1c' : '#047857' ?>;">
                <?= $failed ? 'Fail' : 'Pass' ?>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div style="padding:36px 32px 28px;display:grid;grid-template-columns:repeat(3,1fr);gap:24px;border-top:1px solid var(--line);">
        <div style="text-align:center;">
            <div style="border-top:1px dashed #94a3b8;padding-top:8px;font-size:12px;color:var(--muted);">Class Teacher</div>
        </div>
        <div style="text-align:center;">
            <div style="border-top:1px dashed #94a3b8;padding-top:8px;font-size:12px;color:var(--muted);">Examination Officer</div>
        </div>
        <div style="text-align:center;">
            <div style="border-top:1px dashed #94a3b8;padding-top:8px;font-size:12px;color:var(--muted);">Principal</div>
        </div>
    </div>
</div>

<?php elseif ($studentId): ?>
<div class="card" style="text-align:center;padding:50px;">
    <i class="bi bi-search" style="font-size:36px;color:var(--muted);"></i>
    <p style="margin-top:12px;color:var(--muted);">Student not found.</p>
</div>
<?php else: ?>
<div class="card" style="text-align:center;padding:50px;">
    <i class="bi bi-file-earmark-text" style="font-size:36px;color:var(--muted);"></i>
    <p style="margin-top:12px;color:var(--muted);">Select a student and term to generate the marksheet.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
