<?php
$pageTitle = 'Fee Payments';
require_once __DIR__ . '/../includes/header.php';

$years   = all_years();
$curYear = current_year_id();
$yearId  = (int)($_GET['year_id'] ?? $curYear ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);

$student = $structures = $payments = [];
$totalDue = 0; $totalPaid = 0;

if (db_ok() && $studentId) {
    $stmt = db()->prepare("
        SELECT s.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label, c.id AS class_id
        FROM students s LEFT JOIN classes c ON c.id = s.class_id
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if ($student) {
        // Fee structures applicable to this student's class+year
        $stmt = db()->prepare('SELECT * FROM fee_structures WHERE class_id = ? AND (year_id = ? OR year_id IS NULL) ORDER BY sort_order');
        $stmt->execute([$student['class_id'], $yearId]);
        $structures = $stmt->fetchAll();

        // Payments for this student in target year
        $stmt = db()->prepare('SELECT * FROM fee_payments WHERE student_id = ? AND year_id = ? ORDER BY paid_on DESC, id DESC');
        $stmt->execute([$studentId, $yearId]);
        $payments = $stmt->fetchAll();

        foreach ($structures as $s) {
            $multiplier = $s['is_recurring'] ? 12 : 1;
            $totalDue += (float)$s['amount'] * $multiplier;
        }
        foreach ($payments as $p) $totalPaid += (float)$p['amount'];
    }
}

// Save payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok() && $studentId && $student) {
    $sid = (int)$_POST['fee_structure_id'] ?: null;
    $name = trim($_POST['fee_name'] ?? '');
    if (!$name && $sid) {
        $stmt = db()->prepare('SELECT fee_name FROM fee_structures WHERE id=?');
        $stmt->execute([$sid]);
        $name = $stmt->fetchColumn() ?: 'Payment';
    }
    $amount = (float)($_POST['amount'] ?? 0);
    $paidOn = $_POST['paid_on'] ?? date('Y-m-d');
    $method = trim($_POST['method'] ?? 'cash');
    $note   = trim($_POST['note'] ?? '');

    if ($amount <= 0) {
        flash_set('error', 'Amount must be greater than zero.');
    } else {
        $user = current_user();
        $receiptNo = 'RCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        try {
            db()->prepare('INSERT INTO fee_payments (student_id,year_id,fee_structure_id,fee_name,amount,paid_on,method,note,received_by,receipt_no) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$studentId, $yearId, $sid, $name, $amount, $paidOn, $method, $note ?: null, $user['id'] ?? null, $receiptNo]);
            $payId = (int)db()->lastInsertId();
            audit_log('create', 'fee_payment', $payId,
                ($student['name'] ?? '#'.$studentId) . ' · ' . $name . ' · ৳' . $amount,
                null, ['amount'=>$amount,'fee_name'=>$name,'method'=>$method,'paid_on'=>$paidOn,'receipt_no'=>$receiptNo]);
            flash_set('success', "Payment recorded. Receipt: $receiptNo");
        } catch (PDOException $e) {
            flash_set('error', 'Could not save: ' . $e->getMessage());
        }
    }
    redirect('payments.php?student_id=' . $studentId . '&year_id=' . $yearId);
}

// Student lookup form data
$students = [];
if (db_ok()) {
    $stmt = db()->prepare("
        SELECT s.id, s.roll_no, s.name, c.name AS class_name, c.section
        FROM students s LEFT JOIN classes c ON c.id = s.class_id
        WHERE s.year_id = ? AND s.status='active'
        ORDER BY c.name, c.section, s.roll_no
    ");
    $stmt->execute([$yearId]);
    $students = $stmt->fetchAll();
}
$totalDueForUnpaid = max(0, $totalDue - $totalPaid);
?>

<div class="page-head">
    <div>
        <h1>Fee Payments</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Payments</div>
    </div>
    <a href="fees.php" class="btn btn-light"><i class="bi bi-cash-stack"></i> Manage Fee Structure</a>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="card" style="margin-bottom:18px;">
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:12px;align-items:end;">
        <div class="field" style="margin:0;">
            <label>Year</label>
            <select name="year_id">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $yearId===(int)$y['id']?'selected':'' ?>>
                    <?= e($y['name']) ?><?= $y['is_current']?' (current)':'' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin:0;">
            <label>Student <span style="color:#dc2626;">*</span></label>
            <select name="student_id" required>
                <option value="">— Select student —</option>
                <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $studentId===(int)$s['id']?'selected':'' ?>>
                    <?= e($s['roll_no'] . ' · ' . $s['name'] . ' · ' . $s['class_name'] . ($s['section'] ? ' - ' . $s['section'] : '')) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-clockwise"></i> Load</button>
    </form>
</div>

<?php if ($student): ?>
<div class="row-2" style="margin-bottom:18px;">
    <div class="card">
        <div class="card-h"><h3><i class="bi bi-person-fill"></i> <?= e($student['name']) ?></h3></div>
        <div style="display:grid;grid-template-columns:auto 1fr;gap:8px 14px;font-size:13px;">
            <span style="color:var(--muted);">Roll:</span><b><?= e($student['roll_no']) ?></b>
            <span style="color:var(--muted);">Class:</span><b><?= e($student['class_label']) ?></b>
            <span style="color:var(--muted);">Parent:</span><span><?= e($student['parent_name']) ?: '—' ?></span>
            <span style="color:var(--muted);">Phone:</span><span><?= e($student['phone']) ?: '—' ?></span>
        </div>
    </div>
    <div class="card">
        <div class="card-h"><h3><i class="bi bi-cash-coin"></i> Summary</h3></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;">
            <div><div style="font-size:11px;color:var(--muted);">Annual Due</div><div style="font-size:20px;font-weight:700;color:var(--primary);">৳<?= number_format($totalDue, 0) ?></div></div>
            <div><div style="font-size:11px;color:var(--muted);">Paid</div><div style="font-size:20px;font-weight:700;color:#047857;">৳<?= number_format($totalPaid, 0) ?></div></div>
            <div><div style="font-size:11px;color:var(--muted);">Pending</div><div style="font-size:20px;font-weight:700;color:<?= $totalDueForUnpaid>0?'#dc2626':'#047857' ?>;">৳<?= number_format($totalDueForUnpaid, 0) ?></div></div>
        </div>
    </div>
</div>

<div class="row-2">
    <!-- Record payment form -->
    <div class="card">
        <div class="card-h"><h3><i class="bi bi-plus-circle-fill"></i> Record Payment</h3></div>
        <form method="post">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="field">
                    <label>Fee item</label>
                    <select name="fee_structure_id" id="feeStructure">
                        <option value="">— Custom (no structure) —</option>
                        <?php foreach ($structures as $s): ?>
                        <option value="<?= $s['id'] ?>" data-name="<?= e($s['fee_name']) ?>" data-amount="<?= e($s['amount']) ?>">
                            <?= e($s['fee_name']) ?> · ৳<?= number_format((float)$s['amount'], 0) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Fee name</label>
                    <input type="text" name="fee_name" id="feeName" placeholder="e.g. May Tuition">
                </div>
                <div class="field">
                    <label>Amount (BDT) <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="amount" id="feeAmount" step="0.01" min="0.01" required>
                </div>
                <div class="field">
                    <label>Date</label>
                    <input type="date" name="paid_on" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="field">
                    <label>Method</label>
                    <select name="method">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="bkash">bKash / Mobile Banking</option>
                        <option value="cheque">Cheque</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <div class="field">
                    <label>Note</label>
                    <input type="text" name="note" placeholder="optional">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-cash-stack"></i> Save Payment</button>
        </form>
        <script>
        document.getElementById('feeStructure').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.name) {
                document.getElementById('feeName').value = opt.dataset.name;
                document.getElementById('feeAmount').value = opt.dataset.amount;
            }
        });
        </script>
    </div>

    <!-- Payment history -->
    <div class="card">
        <div class="card-h">
            <h3><i class="bi bi-clock-history"></i> Payment History</h3>
            <span class="badge badge-info"><?= count($payments) ?></span>
        </div>
        <?php if (!$payments): ?>
        <div style="text-align:center;color:var(--muted);padding:20px;">No payments recorded yet.</div>
        <?php else: ?>
        <div style="max-height:480px;overflow-y:auto;">
            <table class="tbl" style="font-size:12px;">
                <thead>
                    <tr><th>Date</th><th>Fee</th><th>Amount</th><th>Receipt</th></tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= date('M d', strtotime($p['paid_on'])) ?></td>
                    <td><?= e($p['fee_name']) ?: '—' ?>
                        <?php if ($p['method']): ?>
                            <div style="font-size:10px;color:var(--muted);"><?= e($p['method']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><b>৳<?= number_format((float)$p['amount'], 0) ?></b></td>
                    <td>
                        <a href="receipt.php?id=<?= $p['id'] ?>" target="_blank" class="icon-link" title="Print Receipt"><i class="bi bi-printer"></i></a>
                        <span style="font-size:10px;color:var(--muted);"><?= e($p['receipt_no']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card" style="text-align:center;padding:40px;color:var(--muted);">
    <i class="bi bi-cash-stack" style="font-size:32px;"></i>
    <p style="margin-top:12px;">Pick a student to view fees and record payments.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
