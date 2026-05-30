<?php
$pageTitle = 'Classes';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$teachers = [];
if (db_ok()) {
    $teachers = db()->query('SELECT id, name FROM teachers ORDER BY name')->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $data = [
        'name'       => trim($_POST['name'] ?? ''),
        'section'    => trim($_POST['section'] ?? '') ?: null,
        'teacher_id' => (int)($_POST['teacher_id'] ?? 0) ?: null,
        'capacity'   => (int)($_POST['capacity'] ?? 40),
    ];
    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE classes SET name=?,section=?,teacher_id=?,capacity=? WHERE id=?')
                ->execute([...array_values($data), (int)$_POST['id']]);
            flash_set('success', 'Class updated.');
        } else {
            db()->prepare('INSERT INTO classes (name,section,teacher_id,capacity) VALUES (?,?,?,?)')
                ->execute(array_values($data));
            flash_set('success', 'Class created.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('classes.php');
}

if ($action === 'delete' && $id && db_ok()) {
    db()->prepare('DELETE FROM classes WHERE id = ?')->execute([$id]);
    flash_set('success', 'Class deleted.');
    redirect('classes.php');
}

$record = ['id'=>'','name'=>'','section'=>'','teacher_id'=>'','capacity'=>40];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $record = $row;
}

$classes = [];
if (db_ok() && $action === 'list') {
    $classes = db()->query("
        SELECT c.*, t.name AS teacher_name,
               (SELECT COUNT(*) FROM students WHERE class_id = c.id) AS students_count
        FROM classes c LEFT JOIN teachers t ON t.id = c.teacher_id
        ORDER BY c.id DESC
    ")->fetchAll();
}
?>

<div class="page-head">
    <div>
        <h1>Classes</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Classes</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="classes.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Class</a>
    <?php else: ?>
    <a href="classes.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card" style="max-width:660px;">
    <div class="card-h"><h3><?= $action === 'edit' ? 'Edit Class' : 'Add New Class' ?></h3></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Class name</label>
                <input type="text" name="name" placeholder="e.g. Grade 1" value="<?= e($record['name']) ?>" required>
            </div>
            <div class="field">
                <label>Section</label>
                <input type="text" name="section" placeholder="e.g. A" value="<?= e($record['section']) ?>">
            </div>
            <div class="field">
                <label>Class teacher</label>
                <select name="teacher_id" style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;">
                    <option value="">— Select —</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (string)$record['teacher_id']===(string)$t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Capacity</label>
                <input type="number" name="capacity" min="1" value="<?= e($record['capacity']) ?>">
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="classes.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>

<div class="card">
    <div class="toolbar">
        <span style="color:var(--muted);font-size:13px;">All grades and sections</span>
        <span class="badge badge-muted"><?= count($classes) ?> total</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>#</th><th>Class</th><th>Section</th><th>Teacher</th>
                <th>Students</th><th>Capacity</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$classes): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">
                No classes yet. <a href="classes.php?action=new">Create one</a>.
            </td></tr>
        <?php else: foreach ($classes as $c):
            $occupancy = $c['capacity'] > 0 ? round(($c['students_count'] / $c['capacity']) * 100) : 0;
        ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><b><?= e($c['name']) ?></b></td>
                <td><?= e($c['section']) ?: '—' ?></td>
                <td><?= e($c['teacher_name']) ?: '<span style="color:var(--muted);">Unassigned</span>' ?></td>
                <td>
                    <?= $c['students_count'] ?> / <?= $c['capacity'] ?>
                    <div style="height:6px;background:#f1f5f9;border-radius:99px;margin-top:4px;width:120px;">
                        <div style="height:6px;width:<?= min(100,$occupancy) ?>%;background:<?= $occupancy>=90?'#ef4444':'#4f46e5' ?>;border-radius:99px;"></div>
                    </div>
                </td>
                <td><?= $occupancy ?>% full</td>
                <td>
                    <a class="icon-link" href="classes.php?action=edit&id=<?= $c['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="classes.php?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Delete this class?');" title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
