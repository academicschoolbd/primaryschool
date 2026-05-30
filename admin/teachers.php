<?php
$pageTitle = 'Teachers';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// === Handle POST (create / update) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $data = [
        'name'      => trim($_POST['name'] ?? ''),
        'email'     => trim($_POST['email'] ?? '') ?: null,
        'phone'     => trim($_POST['phone'] ?? '') ?: null,
        'subject'   => trim($_POST['subject'] ?? '') ?: null,
        'gender'    => $_POST['gender'] ?? 'male',
        'joined_on' => $_POST['joined_on'] ?? null,
        'status'    => $_POST['status'] ?? 'active',
    ];
    try {
        if (!empty($_POST['id'])) {
            $sql = 'UPDATE teachers SET name=?,email=?,phone=?,subject=?,gender=?,joined_on=?,status=? WHERE id=?';
            db()->prepare($sql)->execute([...array_values($data), (int)$_POST['id']]);
            flash_set('success', 'Teacher updated.');
        } else {
            $sql = 'INSERT INTO teachers (name,email,phone,subject,gender,joined_on,status) VALUES (?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute(array_values($data));
            flash_set('success', 'Teacher added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('teachers.php');
}

// === Handle delete ===
if ($action === 'delete' && $id && db_ok()) {
    db()->prepare('DELETE FROM teachers WHERE id = ?')->execute([$id]);
    flash_set('success', 'Teacher deleted.');
    redirect('teachers.php');
}

// === Load record for edit ===
$record = ['id'=>'','name'=>'','email'=>'','phone'=>'','subject'=>'','gender'=>'male','joined_on'=>'','status'=>'active'];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $record = $row;
}

// === Listing ===
$teachers = [];
$q = trim($_GET['q'] ?? '');
if (db_ok() && $action === 'list') {
    if ($q !== '') {
        $stmt = db()->prepare('SELECT * FROM teachers WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? ORDER BY id DESC');
        $stmt->execute(["%$q%","%$q%","%$q%"]);
    } else {
        $stmt = db()->query('SELECT * FROM teachers ORDER BY id DESC');
    }
    $teachers = $stmt->fetchAll();
}
?>

<div class="page-head">
    <div>
        <h1>Teachers</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Teachers</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="teachers.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Teacher</a>
    <?php else: ?>
    <a href="teachers.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card" style="max-width:760px;">
    <div class="card-h"><h3><?= $action === 'edit' ? 'Edit Teacher' : 'Add New Teacher' ?></h3></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Full name</label>
                <input type="text" name="name" value="<?= e($record['name']) ?>" required>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($record['email']) ?>">
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= e($record['phone']) ?>">
            </div>
            <div class="field">
                <label>Subject</label>
                <input type="text" name="subject" value="<?= e($record['subject']) ?>">
            </div>
            <div class="field">
                <label>Gender</label>
                <select name="gender" style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;">
                    <?php foreach (['male','female','other'] as $g): ?>
                    <option value="<?= $g ?>" <?= $record['gender']===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Joined on</label>
                <input type="date" name="joined_on" value="<?= e($record['joined_on']) ?>">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status" style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;">
                    <option value="active"   <?= $record['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $record['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="teachers.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>

<div class="card">
    <div class="toolbar">
        <form class="left" method="get">
            <input class="search-i" type="text" name="q" value="<?= e($q) ?>" placeholder="Search by name, email, subject...">
            <button class="btn btn-light" type="submit"><i class="bi bi-search"></i></button>
        </form>
        <span class="badge badge-muted"><?= count($teachers) ?> total</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>#</th><th>Name</th><th>Subject</th><th>Email</th><th>Phone</th>
                <th>Joined</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$teachers): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">
                No teachers found. <a href="teachers.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($teachers as $t): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td>
                    <span class="avatar-sm" style="background:<?= avatar_color($t['name']) ?>">
                        <?= strtoupper(substr($t['name'],0,1)) ?>
                    </span>
                    <?= e($t['name']) ?>
                </td>
                <td><?= e($t['subject']) ?></td>
                <td><?= e($t['email']) ?></td>
                <td><?= e($t['phone']) ?></td>
                <td><?= $t['joined_on'] ? date('M d, Y', strtotime($t['joined_on'])) : '—' ?></td>
                <td>
                    <?php if ($t['status']==='active'): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="icon-link" href="teachers.php?action=edit&id=<?= $t['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="teachers.php?action=delete&id=<?= $t['id'] ?>" onclick="return confirm('Delete this teacher?');" title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
