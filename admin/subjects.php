<?php
$pageTitle = 'Subjects';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $data = [
        'name'       => trim($_POST['name'] ?? ''),
        'code'       => trim($_POST['code'] ?? '') ?: null,
        'full_marks' => (int)($_POST['full_marks'] ?? 100),
        'pass_marks' => (int)($_POST['pass_marks'] ?? 33),
    ];
    if ($data['name'] === '') {
        flash_set('error', 'Subject name required.');
        redirect('subjects.php');
    }
    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE subjects SET name=?,code=?,full_marks=?,pass_marks=? WHERE id=?')
                ->execute([...array_values($data), (int)$_POST['id']]);
            flash_set('success', 'Subject updated.');
        } else {
            db()->prepare('INSERT INTO subjects (name,code,full_marks,pass_marks) VALUES (?,?,?,?)')
                ->execute(array_values($data));
            flash_set('success', 'Subject added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('subjects.php');
}

if ($action === 'delete' && $id && db_ok()) {
    db()->prepare('DELETE FROM subjects WHERE id = ?')->execute([$id]);
    flash_set('success', 'Subject deleted.');
    redirect('subjects.php');
}

$record = ['id'=>'','name'=>'','code'=>'','full_marks'=>100,'pass_marks'=>33];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM subjects WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) $record = $row;
}

$subjects = db_ok() ? db()->query('SELECT * FROM subjects ORDER BY name')->fetchAll() : [];
?>

<div class="page-head">
    <div>
        <h1>Subjects</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Subjects</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="subjects.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Subject</a>
    <?php else: ?>
    <a href="subjects.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:560px;">
    <div class="card-h"><h3><i class="bi bi-book-fill"></i> <?= $action === 'edit' ? 'Edit Subject' : 'Add Subject' ?></h3></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
            <div class="field">
                <label>Subject name <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" value="<?= e($record['name']) ?>" required>
            </div>
            <div class="field">
                <label>Code</label>
                <input type="text" name="code" value="<?= e($record['code']) ?>" placeholder="ENG">
            </div>
            <div class="field">
                <label>Full marks</label>
                <input type="number" name="full_marks" value="<?= e($record['full_marks']) ?>" min="1">
            </div>
            <div class="field">
                <label>Pass marks</label>
                <input type="number" name="pass_marks" value="<?= e($record['pass_marks']) ?>" min="1">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
        <a href="subjects.php" class="btn btn-light">Cancel</a>
    </form>
</div>

<?php else: ?>

<div class="card">
    <table class="tbl">
        <thead>
            <tr><th>#</th><th>Name</th><th>Code</th><th>Full</th><th>Pass</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$subjects): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">
                No subjects yet. <a href="subjects.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($subjects as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><b><?= e($s['name']) ?></b></td>
                <td><code><?= e($s['code']) ?></code></td>
                <td><?= $s['full_marks'] ?></td>
                <td><?= $s['pass_marks'] ?></td>
                <td>
                    <a class="icon-link" href="subjects.php?action=edit&id=<?= $s['id'] ?>"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="subjects.php?action=delete&id=<?= $s['id'] ?>" onclick="return confirm('Delete this subject? Existing results will keep referencing it.');"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
