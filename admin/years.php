<?php
$pageTitle = 'Academic Years';
require_once __DIR__ . '/../includes/header.php';

ensure_year_tables();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $name       = trim($_POST['name'] ?? '');
    $start      = $_POST['start_date'] ?? null;
    $end        = $_POST['end_date'] ?? null;
    $isCurrent  = !empty($_POST['is_current']) ? 1 : 0;

    if ($name === '') {
        flash_set('error', 'Year name is required.');
        redirect('years.php');
    }
    try {
        if ($isCurrent) {
            db()->exec("UPDATE academic_years SET is_current = 0");
        }
        if (!empty($_POST['id'])) {
            $before = db()->prepare('SELECT * FROM academic_years WHERE id=?');
            $before->execute([(int)$_POST['id']]);
            $bef = $before->fetch();

            db()->prepare('UPDATE academic_years SET name=?, start_date=?, end_date=?, is_current=? WHERE id=?')
                ->execute([$name, $start ?: null, $end ?: null, $isCurrent, (int)$_POST['id']]);
            $after = ['name'=>$name,'start_date'=>$start,'end_date'=>$end,'is_current'=>$isCurrent];
            audit_log('update', 'academic_year', (int)$_POST['id'], $name, $bef, $after);
            flash_set('success', "Year '$name' updated.");
        } else {
            db()->prepare('INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES (?,?,?,?)')
                ->execute([$name, $start ?: null, $end ?: null, $isCurrent]);
            $newId = (int)db()->lastInsertId();
            audit_log('create', 'academic_year', $newId, $name, null, ['name'=>$name,'is_current'=>$isCurrent]);
            flash_set('success', "Year '$name' created.");
        }
        if ($isCurrent) {
            // Mirror to settings for fast lookups
            $cy = (int)db()->query('SELECT id FROM academic_years WHERE is_current=1 LIMIT 1')->fetchColumn();
            if ($cy) set_setting('current_year_id', (string)$cy);
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('years.php');
}

if ($action === 'set-current' && $id && db_ok()) {
    db()->exec("UPDATE academic_years SET is_current = 0");
    db()->prepare('UPDATE academic_years SET is_current = 1 WHERE id = ?')->execute([$id]);
    set_setting('current_year_id', (string)$id);
    audit_log('update', 'academic_year', $id, 'set as current year');
    flash_set('success', 'Current academic year updated.');
    redirect('years.php');
}

if ($action === 'delete' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT name FROM academic_years WHERE id = ?');
    $stmt->execute([$id]);
    $name = $stmt->fetchColumn();
    db()->prepare('DELETE FROM academic_years WHERE id = ?')->execute([$id]);
    audit_log('delete', 'academic_year', $id, $name);
    flash_set('success', 'Year deleted (results referencing it kept).');
    redirect('years.php');
}

$record = ['id'=>'','name'=>'','start_date'=>'','end_date'=>'','is_current'=>0];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM academic_years WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) $record = $row;
}

$years = db_ok() ? db()->query('SELECT * FROM academic_years ORDER BY name DESC')->fetchAll() : [];
?>

<div class="page-head">
    <div>
        <h1>Academic Years</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Years</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="years.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Year</a>
    <?php else: ?>
    <a href="years.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:560px;">
    <div class="card-h"><h3><i class="bi bi-calendar3"></i> <?= $action === 'edit' ? 'Edit Year' : 'New Academic Year' ?></h3></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div class="field">
            <label>Year name <span style="color:#dc2626;">*</span></label>
            <input type="text" name="name" value="<?= e($record['name']) ?>" required placeholder="e.g. 2026 or 2025-26">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Start date</label>
                <input type="date" name="start_date" value="<?= e($record['start_date']) ?>">
            </div>
            <div class="field">
                <label>End date</label>
                <input type="date" name="end_date" value="<?= e($record['end_date']) ?>">
            </div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;padding:12px;background:rgba(var(--accent-rgb),.1);border:1px solid rgba(var(--accent-rgb),.3);border-radius:10px;cursor:pointer;">
            <input type="checkbox" name="is_current" value="1" <?= $record['is_current']?'checked':'' ?> style="width:18px;height:18px;">
            <div>
                <b style="font-size:13.5px;color:var(--accent-dark);">📅 Mark as current year</b>
                <div style="font-size:12px;color:var(--muted);">All result lookups + new mark entries default to this year.</div>
            </div>
        </label>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="years.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="card">
    <table class="tbl">
        <thead>
            <tr><th>#</th><th>Year</th><th>Period</th><th>Current?</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$years): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:30px;">
                No academic years yet. <a href="years.php?action=new">Create one</a>.
            </td></tr>
        <?php else: foreach ($years as $y): ?>
            <tr>
                <td><?= $y['id'] ?></td>
                <td><b><?= e($y['name']) ?></b></td>
                <td>
                    <?= $y['start_date'] ? date('M d, Y', strtotime($y['start_date'])) : '—' ?>
                    →
                    <?= $y['end_date'] ? date('M d, Y', strtotime($y['end_date'])) : '—' ?>
                </td>
                <td>
                    <?php if ($y['is_current']): ?>
                        <span class="badge badge-success"><i class="bi bi-check-circle-fill"></i> Current</span>
                    <?php else: ?>
                        <a href="years.php?action=set-current&id=<?= $y['id'] ?>" class="badge badge-muted" style="text-decoration:none;cursor:pointer;">Set current</a>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="icon-link" href="years.php?action=edit&id=<?= $y['id'] ?>"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="years.php?action=delete&id=<?= $y['id'] ?>" onclick="return confirm('Delete this year? Existing results referencing it will keep their year_id.');"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
