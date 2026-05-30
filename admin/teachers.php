<?php
$pageTitle = 'Teachers';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// === Handle POST (create / update with photo upload) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $existingPhoto = null;
    if (!empty($_POST['id'])) {
        $stmt = db()->prepare('SELECT photo FROM teachers WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $existingPhoto = $stmt->fetchColumn() ?: null;
    }
    $newPhoto = upload_photo('photo', 'teachers', 'teacher');
    $photoToSave = $newPhoto ?: $existingPhoto;
    if ($newPhoto && $existingPhoto) delete_photo($existingPhoto);

    $data = [
        'name'        => trim($_POST['name'] ?? ''),
        'email'       => trim($_POST['email'] ?? '') ?: null,
        'phone'       => trim($_POST['phone'] ?? '') ?: null,
        'subject'     => trim($_POST['subject'] ?? '') ?: null,
        'designation' => trim($_POST['designation'] ?? '') ?: null,
        'photo'       => $photoToSave,
        'gender'      => $_POST['gender'] ?? 'male',
        'joined_on'   => $_POST['joined_on'] ?? null,
        'status'      => $_POST['status'] ?? 'active',
    ];
    try {
        if (!empty($_POST['id'])) {
            $sql = 'UPDATE teachers SET name=?,email=?,phone=?,subject=?,designation=?,photo=?,gender=?,joined_on=?,status=? WHERE id=?';
            db()->prepare($sql)->execute([...array_values($data), (int)$_POST['id']]);
            flash_set('success', 'Teacher updated.');
        } else {
            $sql = 'INSERT INTO teachers (name,email,phone,subject,designation,photo,gender,joined_on,status) VALUES (?,?,?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute(array_values($data));
            flash_set('success', 'Teacher added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('teachers.php');
}

// === Load record for edit ===
$record = ['id'=>'','name'=>'','email'=>'','phone'=>'','subject'=>'','designation'=>'','photo'=>'','gender'=>'male','joined_on'=>'','status'=>'active'];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $record = $row;
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

<div class="card" style="max-width:860px;">
    <div class="card-h"><h3><?= $action === 'edit' ? 'Edit Teacher' : 'Add New Teacher' ?></h3></div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">

        <!-- Photo block -->
        <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:22px;padding:16px;background:var(--bg);border-radius:12px;">
            <div class="photo-preview" id="photoPreview" style="width:110px;height:130px;flex-shrink:0;">
                <?php if (!empty($record['photo'])): ?>
                    <img src="<?= e(media_url($record['photo'])) ?>" alt="">
                <?php else: ?>
                    <i class="bi bi-person-fill ph-empty"></i>
                <?php endif; ?>
            </div>
            <div style="flex:1;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Profile photo</label>
                <input type="file" name="photo" accept="image/*"
                       data-photo-preview="#photoPreview"
                       style="padding:8px;background:#fff;border:1px solid var(--line);border-radius:10px;width:100%;">
                <div style="font-size:12px;color:var(--muted);margin-top:6px;">
                    <i class="bi bi-info-circle"></i>
                    JPG/PNG/WEBP, max 4 MB. Live preview appears on the left when you pick a file.
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Full name <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" value="<?= e($record['name']) ?>" required>
            </div>
            <div class="field">
                <label>Designation</label>
                <input type="text" name="designation" placeholder="e.g. Senior Teacher" value="<?= e($record['designation']) ?>">
            </div>
            <div class="field">
                <label>Subject</label>
                <input type="text" name="subject" placeholder="e.g. Mathematics" value="<?= e($record['subject']) ?>">
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
                <label>Gender</label>
                <select name="gender">
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
                <select name="status">
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

<?php else: // ===== List view ===== ?>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <input class="search-i" type="text" id="teacherSearch"
                   data-live-search="<?= BASE_URL ?>/api/teachers_search.php"
                   data-target="#teachersTbody"
                   data-extra-fields=".filter-field"
                   data-renderer="renderTeachersRows"
                   placeholder="Search by name, designation, email, subject…">
            <select class="filter-field" name="status">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <span class="badge badge-info"><i class="bi bi-lightning-fill"></i> Live AJAX search</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>#</th><th>Name</th><th>Designation</th><th>Subject</th>
                <th>Email</th><th>Phone</th><th>Joined</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody id="teachersTbody">
        <?php
        // Server-side initial render so the page works without JS.
        $teachers = db_ok()
            ? db()->query('SELECT * FROM teachers ORDER BY id DESC')->fetchAll()
            : [];
        if (!$teachers): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px;">
                No teachers yet. <a href="teachers.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($teachers as $t): ?>
            <tr data-id="<?= $t['id'] ?>">
                <td><?= $t['id'] ?></td>
                <td>
                    <span class="avatar-sm" style="background:<?= avatar_color($t['name']) ?>">
                        <?php if (!empty($t['photo'])): ?>
                            <img src="<?= e(media_url($t['photo'])) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($t['name'],0,1)) ?>
                        <?php endif; ?>
                    </span>
                    <?= e($t['name']) ?>
                </td>
                <td><?= e($t['designation'] ?? '—') ?: '—' ?></td>
                <td><?= e($t['subject'] ?? '—') ?: '—' ?></td>
                <td><?= e($t['email']) ?: '—' ?></td>
                <td><?= e($t['phone']) ?: '—' ?></td>
                <td><?= $t['joined_on'] ? date('M d, Y', strtotime($t['joined_on'])) : '—' ?></td>
                <td>
                    <?php if ($t['status']==='active'): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                    <a class="icon-link" href="teachers.php?action=edit&id=<?= $t['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="#"
                       data-ajax-delete="<?= BASE_URL ?>/api/teachers_delete.php?id=<?= $t['id'] ?>"
                       data-confirm="Delete teacher '<?= e(addslashes($t['name'])) ?>'?"
                       title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
