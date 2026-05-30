<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id || !db_ok()) { http_response_code(404); exit('Not found'); }

$stmt = db()->prepare("
    SELECT p.*, s.name AS student_name, s.roll_no, s.parent_name, s.phone,
           CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label,
           ay.name AS year_name,
           u.name AS received_by_name
    FROM fee_payments p
    LEFT JOIN students s        ON s.id = p.student_id
    LEFT JOIN classes c         ON c.id = s.class_id
    LEFT JOIN academic_years ay ON ay.id = p.year_id
    LEFT JOIN users u           ON u.id = p.received_by
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { http_response_code(404); exit('Receipt not found'); }
$school = public_school();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= e($p['receipt_no']) ?> · <?= e($school['name_en']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php theme_styles_inline(); ?>
<style>
    body { font-family: 'Inter', sans-serif; background: #eef1f8; padding: 30px 12px; margin: 0; }
    .receipt {
        max-width: 540px; margin: 0 auto; background: #fff;
        border-radius: 14px; box-shadow: 0 8px 28px rgba(26,35,126,0.15);
        overflow: hidden;
    }
    .receipt-band {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary), var(--primary-light));
        color: #fff; padding: 22px 26px;
        display: flex; align-items: center; gap: 14px;
        border-bottom: 4px solid var(--accent);
    }
    .receipt-band img { width: 56px; height: 56px; border-radius: 50%; background: #fff; padding: 4px; border: 2px solid var(--accent); flex-shrink: 0; }
    .receipt-band .school-bn { font-size: 1.05rem; font-weight: 700; line-height: 1.3; }
    .receipt-band .school-en { font-size: 0.85rem; opacity: 0.85; line-height: 1.3; }
    .receipt-band .tag {
        margin-left: auto; text-align: right;
    }
    .receipt-band .tag .lbl { font-size: 10px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }
    .receipt-band .tag .num { font-size: 0.95rem; font-weight: 800; color: var(--accent); }
    .receipt-body { padding: 24px 26px; font-size: 14px; line-height: 1.7; }
    .row { display: grid; grid-template-columns: auto 1fr; gap: 6px 18px; margin-bottom: 18px; }
    .row .k { color: #5a6270; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding-top: 1px; }
    .row .v { font-weight: 600; color: #1a1a2e; }
    .amount-box {
        background: rgba(var(--accent-rgb), 0.10);
        border: 1px dashed rgba(var(--accent-rgb), 0.5);
        border-radius: 10px; padding: 16px 18px; text-align: center;
        margin: 18px 0;
    }
    .amount-box .lbl { font-size: 11px; color: var(--accent-dark); text-transform: uppercase; letter-spacing: 1px; }
    .amount-box .val { font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 4px; }
    .signs { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 36px; padding-top: 16px; }
    .signs .s { text-align: center; }
    .signs .line { border-top: 1px dashed #94a3b8; margin-bottom: 8px; height: 30px; }
    .signs .lbl { font-size: 11px; color: #5a6270; font-weight: 600; }
    .actions {
        padding: 14px 26px; background: #fafbff; border-top: 1px solid #dde3f0;
        display: flex; gap: 8px; justify-content: flex-end;
    }
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; }
    .btn-p { background: var(--primary); color: #fff; }
    .btn-l { background: #fff; border: 1px solid #dde3f0; color: #1a1a2e; }
    @media print {
        body { background: #fff; padding: 0; }
        .receipt { box-shadow: none; border-radius: 0; max-width: 100%; }
        .actions { display: none; }
    }
</style>
</head>
<body>
<div class="receipt">
    <div class="receipt-band">
        <img src="<?= e($school['logo']) ?>" alt="">
        <div>
            <div class="school-bn"><?= e($school['name_bn']) ?></div>
            <div class="school-en"><?= e($school['name_en']) ?></div>
            <?php if ($school['address']): ?>
            <div style="font-size:11px;opacity:0.7;margin-top:2px;"><?= e($school['address']) ?></div>
            <?php endif; ?>
        </div>
        <div class="tag">
            <div class="lbl">Receipt</div>
            <div class="num"><?= e($p['receipt_no']) ?></div>
        </div>
    </div>

    <div class="receipt-body">
        <div class="row">
            <div class="k">Student</div><div class="v"><?= e($p['student_name']) ?></div>
            <div class="k">Roll No</div><div class="v"><?= e($p['roll_no']) ?></div>
            <div class="k">Class</div><div class="v"><?= e($p['class_label']) ?: '—' ?></div>
            <?php if ($p['parent_name']): ?>
            <div class="k">Parent</div><div class="v"><?= e($p['parent_name']) ?></div>
            <?php endif; ?>
            <div class="k">Year</div><div class="v"><?= e($p['year_name']) ?: '—' ?></div>
            <div class="k">Date</div><div class="v"><?= e(date('M d, Y', strtotime($p['paid_on']))) ?></div>
            <div class="k">Method</div><div class="v"><?= e(ucfirst($p['method'])) ?></div>
            <div class="k">For</div><div class="v"><?= e($p['fee_name'] ?: 'Payment') ?></div>
            <?php if ($p['note']): ?>
            <div class="k">Note</div><div class="v"><?= e($p['note']) ?></div>
            <?php endif; ?>
        </div>

        <div class="amount-box">
            <div class="lbl">Amount Received</div>
            <div class="val">৳ <?= number_format((float)$p['amount'], 2) ?></div>
        </div>

        <div class="signs">
            <div class="s"><div class="line"></div><div class="lbl">Received by<br><?= e($p['received_by_name'] ?: '') ?></div></div>
            <div class="s"><div class="line"></div><div class="lbl">Authorized signature</div></div>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-l" onclick="window.close()">Close</button>
        <button class="btn btn-p" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>
</body>
</html>
