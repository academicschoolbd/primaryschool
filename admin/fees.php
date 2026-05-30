<?php
$pageTitle = 'Fee Structures';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$years   = all_years();
$classes = $allClasses = [];
if (db_ok()) {
    $classes = db()->query("
        SELECT c.id, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS label, c.year_id, ay.name AS year_name
        FROM classes c LEFT JOIN academic_years ay ON ay.id = c.year_id
        ORDER BY ay.name DESC, c.name, c.section
    ")->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $data = [
        'class_id'     => (int)($_POST['class_id'] ?? 0),
        'year_id'      => (int)($_POST['year_id']  ?? 0) ?: current_year_id(),
        'fee_name'     => trim($_POST['fee_name'] ?? ''),
        'amount'       => (float)($_POST['amount'] ?? 0),
        'due_month'    => $_POST['due_month'] !== '' ? (int)$_POST['due_month'] : null,
        'is_recurring' => !empty($_POST['is_recurring']) ? 1 : 0,
        'sort_order'   => (int)($_POST['sort_order'] ?? 0),
    ];
    if (!$data['class_id'] || $data['fee_name'] === '' || $data['amount'] <= 0) {
        flash_set('error', 'Class, fee name and amount are required.');
        redirect('fees.php');
    }
    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE fee_structures SET class_id=?,year_id=?,fee_name=?,amount=?,due_month=?,is_recurring=?,sort_order=? WHERE id=?')
                ->execute([...array_values($data), (int)$_POST['id']]);
            audit_log('update', 'fee_structure', (int)$_POST['id'], $data['fee_name']);
            flash_set('success', 'Fee structure updated.');
        } else {
            db()->prepare('INSERT INTO fee_structures (class_id,year_id,fee_name,amount,due_month,is_recurring,sort_order) VALUES (?,?,?,?,?,?,?)')
                ->execute(array_values($data));
            $newId = (int)db()->lastInsertId();
            audit_log('create', 'fee_structure', $newId, $data['fee_name'], null, $data);
            flash_set('success', 'Fee structure added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('fees.php');
}

if ($action === 'delete' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT fee_name FROM fee_structures WHERE id=?');
    $stmt->execute([$id]);
    $name = $stmt->fetchColumn();
    db()->prepare('DELETE FROM fee_structures WHERE id=?')->execute([$id]);
    audit_log('delete', 'fee_structure', $id, $name);
    flash_set('success', 'Deleted.');
    redirect('fees.php');
}

$record = ['id'=>'','class_id'=>'','year_id'=>current_year_id(),'fee_name'=>'','amount'=>'','due_month'=>'','is_recurring'=>0,'sort_order'=>0];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM fee_structures WHERE id=?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) $record = $row;
}

$filterYear = (int)($_GET['filter_year'] ?? current_year_id() ?? 0);
$structures = [];
if (db_ok() && $action === 'list') {
    $sql = "SELECT fs.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label, ay.name AS year_name
            FROM fee_structures fs
            LEFT JOIN classes c ON c.id = fs.class_id
            LEFT JOIN academic_years ay ON ay.id = fs.year_id"
        . ($filterYear ? ' WHERE fs.year_id = ?' : '')
        . ' ORDER BY ay.name DESC, c.name, c.section, fs.sort_order';
    $stmt = db()->prepare($sql);
    $stmt->execute($filterYear ? [$filterYear] : []);
    $structures = $stmt->fetchAll();
}

$months = [
    1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun',
    7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec',
];
?>

<div class="page-head">
    <div>
        <h1>Fee Structures</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Fees</div>
    </div>
    <?php if ($action === 'list'): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="payments.php" class="btn btn-accent"><i class="bi bi-cash-coin"></i> Record Payment</a>
        <a href="fees.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Fee</a>
    </div>
    <?php else: ?>
    <a href="fees.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:680px;">
    <div class="card-h"><h3><i class="bi bi-cash-stack"></i> <?= $action === 'edit' ? 'Edit Fee' : 'Add Fee' ?></h3></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Class &amp; Section <span style="color:#dc2626;">*</span></label>
                <select name="class_id" required>
                    <option value="">— Select —</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)$record['class_id']===(int)$c['id']?'selected':'' ?>>
                        <?= e($c['label']) ?> <?= $c['year_name'] ? '(' . e($c['year_name']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Year <span style="color:#dc2626;">*</span></label>
                <select name="year_id" required>
                    <?php foreach ($years as $y): ?>
                    <option value="<?= $y['id'] ?>" <?= (int)$record['year_id']===(int)$y['id']?'selected':'' ?>>
                        <?= e($y['name']) ?><?= $y['is_current']?' (current)':'' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Fee name <span style="color:#dc2626;">*</span></label>
                <input type="text" name="fee_name" value="<?= e($record['fee_name']) ?>" required placeholder="e.g. Monthly Tuition">
            </div>
            <div class="field">
                <label>Amount (BDT) <span style="color:#dc2626;">*</span></label>
                <input type="number" name="amount" step="0.01" min="0" value="<?= e($record['amount']) ?>" required>
            </div>
            <div class="field">
                <label>Due month (optional)</label>
                <select name="due_month">
                    <option value="">— Any / one-time —</option>
                    <?php foreach ($months as $mn => $ml): ?>
                    <option value="<?= $mn ?>" <?= (int)$record['due_month']===$mn?'selected':'' ?>><?= $ml ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Sort order</label>
                <input type="number" name="sort_order" value="<?= e($record['sort_order']) ?>">
            </div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;grid-column:1/-1;background:rgba(var(--accent-rgb),.08);padding:12px;border-radius:10px;">
                <input type="checkbox" name="is_recurring" value="1" <?= $record['is_recurring']?'checked':'' ?> style="width:18px;height:18px;">
                <div>
                    <b style="font-size:13px;">Recurring</b>
                    <div style="font-size:12px;color:var(--muted);">e.g. monthly tuition fee — student owes this every month until the year ends.</div>
                </div>
            </label>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="fees.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="card">
    <div class="toolbar">
        <form method="get" style="display:flex;align-items:center;gap:8px;">
            <span style="color:var(--muted);font-size:13px;">Filter year:</span>
            <select name="filter_year" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid var(--line);border-radius:8px;">
                <option value="0">All years</option>
                <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $filterYear===(int)$y['id']?'selected':'' ?>>
                    <?= e($y['name']) ?><?= $y['is_current']?' (current)':'' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <span class="badge badge-info"><?= count($structures) ?> fee items</span>
    </div>

    <table class="tbl">
        <thead>
            <tr><th>#</th><th>Class</th><th>Year</th><th>Fee Name</th><th>Amount</th><th>Type</th><th>Due</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$structures): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">
                No fees defined. <a href="fees.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($structures as $f): ?>
            <tr>
                <td><?= $f['id'] ?></td>
                <td><b><?= e($f['class_label']) ?></b></td>
                <td><span class="badge badge-info"><?= e($f['year_name']) ?: '—' ?></span></td>
                <td><b><?= e($f['fee_name']) ?></b></td>
                <td>৳ <?= number_format((float)$f['amount'], 2) ?></td>
                <td>
                    <?php if ($f['is_recurring']): ?>
                        <span class="badge badge-success">Recurring</span>
                    <?php else: ?>
                        <span class="badge badge-muted">One-time</span>
                    <?php endif; ?>
                </td>
                <td><?= $f['due_month'] ? e($months[(int)$f['due_month']] ?? '—') : '—' ?></td>
                <td>
                    <a class="icon-link" href="fees.php?action=edit&id=<?= $f['id'] ?>"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="fees.php?action=delete&id=<?= $f['id'] ?>" onclick="return confirm('Delete this fee?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
