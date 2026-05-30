<?php
$pageTitle = 'Leadership Messages';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $name        = trim($_POST['name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $content     = trim($_POST['content'] ?? '');
    $sort        = (int)($_POST['sort_order'] ?? 0);

    $existing = null;
    if (!empty($_POST['id'])) {
        $stmt = db()->prepare('SELECT photo FROM school_messages WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $existing = $stmt->fetchColumn() ?: null;
    }
    $newPhoto = upload_photo('photo_file', 'messages', 'leader');
    $photoToSave = $newPhoto ?: (trim($_POST['photo_url'] ?? '') ?: $existing);
    if ($newPhoto && $existing) delete_photo($existing);

    if ($name === '') {
        flash_set('error', 'Name is required.');
        redirect('leadership.php');
    }
    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE school_messages SET name=?, designation=?, photo=?, content=?, sort_order=? WHERE id=?')
                ->execute([$name, $designation, $photoToSave, $content, $sort, (int)$_POST['id']]);
            flash_set('success', 'Message updated.');
        } else {
            db()->prepare('INSERT INTO school_messages (name, designation, photo, content, sort_order) VALUES (?,?,?,?,?)')
                ->execute([$name, $designation, $photoToSave, $content, $sort]);
            flash_set('success', 'Message added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('leadership.php');
}

if ($action === 'delete' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT photo FROM school_messages WHERE id = ?');
    $stmt->execute([$id]);
    $photo = $stmt->fetchColumn();
    db()->prepare('DELETE FROM school_messages WHERE id = ?')->execute([$id]);
    if ($photo) delete_photo($photo);
    flash_set('success', 'Deleted.');
    redirect('leadership.php');
}

$record = ['id'=>'','name'=>'','designation'=>'','photo'=>'','content'=>'','sort_order'=>0];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM school_messages WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) $record = $row;
}

$msgs = db_ok() ? db()->query('SELECT * FROM school_messages ORDER BY sort_order, id')->fetchAll() : [];
?>

<div class="page-head">
    <div>
        <h1>Leadership Messages</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Leadership</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="leadership.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Message</a>
    <?php else: ?>
    <a href="leadership.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:780px;">
    <div class="card-h"><h3><i class="bi bi-chat-quote-fill"></i> <?= $action === 'edit' ? 'Edit Message' : 'Add Message' ?></h3></div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">

        <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:18px;padding:16px;background:var(--bg);border-radius:12px;flex-wrap:wrap;">
            <div class="photo-preview" id="ldPreview" style="width:120px;height:140px;flex-shrink:0;">
                <?php if (!empty($record['photo'])): ?>
                    <img src="<?= e(media_url($record['photo'])) ?>" alt="">
                <?php else: ?>
                    <i class="bi bi-person-fill ph-empty"></i>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:240px;">
                <div class="field">
                    <label>Upload photo</label>
                    <input type="file" name="photo_file" accept="image/*" data-photo-preview="#ldPreview"
                        style="padding:8px;background:#fff;border:1px solid var(--line);border-radius:10px;width:100%;">
                </div>
                <div class="field" style="margin:0;">
                    <label>Or photo URL</label>
                    <input type="url" name="photo_url" value="<?= e(filter_var($record['photo'] ?? '', FILTER_VALIDATE_URL) ? $record['photo'] : '') ?>" placeholder="https://...">
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Name <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" value="<?= e($record['name']) ?>" required placeholder="e.g. জনাব মো. রফিকুল ইসলাম">
            </div>
            <div class="field">
                <label>Designation</label>
                <input type="text" name="designation" value="<?= e($record['designation']) ?>" placeholder="e.g. প্রধান শিক্ষক">
            </div>
        </div>
        <div class="field">
            <label>Message content</label>
            <textarea name="content" rows="8" placeholder="শুভেচ্ছা বাণী..."><?= e($record['content']) ?></textarea>
        </div>
        <div class="field">
            <label>Sort order</label>
            <input type="number" name="sort_order" value="<?= e($record['sort_order']) ?>">
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
        <a href="leadership.php" class="btn btn-light">Cancel</a>
    </form>
</div>

<?php else: ?>

<div class="card">
    <table class="tbl">
        <thead>
            <tr><th>#</th><th>Photo</th><th>Name</th><th>Designation</th><th>Sort</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$msgs): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">
                No leadership messages yet. <a href="leadership.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($msgs as $m): ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td>
                    <?php if ($m['photo']): ?>
                        <img src="<?= e(media_url($m['photo'])) ?>" style="width:50px;height:60px;object-fit:cover;border-radius:6px;border:1px solid var(--line);">
                    <?php else: ?>
                        <span class="avatar-sm" style="background:<?= avatar_color($m['name']) ?>"><?= strtoupper(substr($m['name'],0,1)) ?></span>
                    <?php endif; ?>
                </td>
                <td><b><?= e($m['name']) ?></b></td>
                <td><?= e($m['designation']) ?></td>
                <td><?= $m['sort_order'] ?></td>
                <td>
                    <a class="icon-link" href="leadership.php?action=edit&id=<?= $m['id'] ?>"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="leadership.php?action=delete&id=<?= $m['id'] ?>" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
