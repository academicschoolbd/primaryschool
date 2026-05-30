<?php
$pageTitle = 'Students';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// Available classes for select dropdown
$classes = [];
if (db_ok()) {
    $classes = db()->query("SELECT id, CONCAT(name,' - ',COALESCE(section,'')) AS label FROM classes ORDER BY id")->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $data = [
        'roll_no'     => trim($_POST['roll_no'] ?? ''),
        'name'        => trim($_POST['name'] ?? ''),
        'class_id'    => (int)($_POST['class_id'] ?? 0) ?: null,
        'gender'      => $_POST['gender'] ?? 'male',
        'dob'         => $_POST['dob'] ?? null,
        'parent_name' => trim($_POST['parent_name'] ?? '') ?: null,
        'phone'       => trim($_POST['phone'] ?? '') ?: null,
        'address'     => trim($_POST['address'] ?? '') ?: null,
        'status'      => $_POST['status'] ?? 'active',
    ];
    try {
        if (!empty($_POST['id'])) {
            $sql = 'UPDATE students SET roll_no=?,name=?,class_id=?,gender=?,dob=?,parent_name=?,phone=?,address=?,status=? WHERE id=?';
            db()->prepare($sql)->execute([...array_values($data), (int)$_POST['id']]);
            flash_set('success', 'Student updated.');
        } else {
            $sql = 'INSERT INTO students (roll_no,name,class_id,gender,dob,parent_name,phone,address,status) VALUES (?,?,?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute(array_values($data));
            flash_set('success', 'Student added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('students.php');
}

if ($action === 'delete' && $id && db_ok()) {
    db()->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
    flash_set('success', 'Student deleted.');
    redirect('students.php');
}

$record = ['id'=>'','roll_no'=>'','name'=>'','class_id'=>'','gender'=>'male','dob'=>'','parent_name'=>'','phone'=>'','address'=>'','status'=>'active'];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $record = $row;
}

$students = [];
$q = trim($_GET['q'] ?? '');
$filterClass = (int)($_GET['class_id'] ?? 0);
if (db_ok() && $action === 'list') {
    $where = []; $args = [];
    if ($q !== '')        { $where[] = '(s.name LIKE ? OR s.roll_no LIKE ? OR s.parent_name LIKE ?)'; $args = array_merge($args, ["%$q%","%$q%","%$q%"]); }
    if ($filterClass)     { $where[] = 's.class_id = ?'; $args[] = $filterClass; }
    $sql = "SELECT s.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label
            FROM students s LEFT JOIN classes c ON c.id = s.class_id"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY s.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $students = $stmt->fetchAll();
}
?>

<div class="page-head">
    <div>
        <h1>Students</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Students</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="students.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Student</a>
    <?php else: ?>
    <a href="students.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card" style="max-width:860px;">
    <div class="card-h"><h3><?= $action === 'edit' ? 'Edit Student' : 'Add New Student' ?></h3></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Roll No</label>
                <input type="text" name="roll_no" value="<?= e($record['roll_no']) ?>" required>
            </div>
            <div class="field">
                <label>Full name</label>
                <input type="text" name="name" value="<?= e($record['name']) ?>" required>
            </div>
            <div class="field">
                <label>Class</label>
                <select name="class_id" style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;">
                    <option value="">— Select —</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (string)$record['class_id']===(string)$c['id']?'selected':'' ?>><?= e($c['label']) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label>Date of birth</label>
                <input type="date" name="dob" value="<?= e($record['dob']) ?>">
            </div>
            <div class="field">
                <label>Parent name</label>
                <input type="text" name="parent_name" value="<?= e($record['parent_name']) ?>">
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= e($record['phone']) ?>">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status" style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;">
                    <option value="active"   <?= $record['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $record['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="field" style="grid-column:1/-1;">
                <label>Address</label>
                <input type="text" name="address" value="<?= e($record['address']) ?>">
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="students.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>

<div class="card">
    <div class="toolbar">
        <form class="left" method="get" style="display:flex;gap:10px;">
            <input class="search-i" type="text" name="q" value="<?= e($q) ?>" placeholder="Search by name, roll no, parent...">
            <select name="class_id" style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
                <option value="0">All classes</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterClass===(int)$c['id']?'selected':'' ?>><?= e($c['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </form>
        <span class="badge badge-muted"><?= count($students) ?> total</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>Roll</th><th>Name</th><th>Class</th><th>Gender</th>
                <th>Parent</th><th>Phone</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$students): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">
                No students found. <a href="students.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($students as $s): ?>
            <tr>
                <td><?= e($s['roll_no']) ?></td>
                <td>
                    <span class="avatar-sm" style="background:<?= avatar_color($s['name']) ?>">
                        <?= strtoupper(substr($s['name'],0,1)) ?>
                    </span>
                    <?= e($s['name']) ?>
                </td>
                <td><?= e($s['class_label']) ?></td>
                <td><?= ucfirst($s['gender']) ?></td>
                <td><?= e($s['parent_name']) ?></td>
                <td><?= e($s['phone']) ?></td>
                <td>
                    <?php if ($s['status']==='active'): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                    <a class="icon-link" href="marksheet.php?student_id=<?= $s['id'] ?>" title="Marksheet"><i class="bi bi-file-earmark-text"></i></a>
                    <a class="icon-link" href="students.php?action=edit&id=<?= $s['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="students.php?action=delete&id=<?= $s['id'] ?>" onclick="return confirm('Delete this student?');" title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
