<?php
$pageTitle = 'Photo Gallery';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $caption = trim($_POST['caption'] ?? '');
    $sort    = (int)($_POST['sort_order'] ?? 0);
    $imgUrl  = trim($_POST['image_url'] ?? '');

    $existing = null;
    if (!empty($_POST['id'])) {
        $stmt = db()->prepare('SELECT image FROM gallery WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $existing = $stmt->fetchColumn() ?: null;
    }
    $newImg = upload_photo('image_file', 'gallery', 'photo');
    $imageToSave = $newImg ?: ($imgUrl ?: $existing);
    if ($newImg && $existing) delete_photo($existing);

    if (!$imageToSave) {
        flash_set('error', 'Please upload an image or provide an image URL.');
        redirect('gallery.php');
    }
    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE gallery SET image=?, caption=?, sort_order=? WHERE id=?')
                ->execute([$imageToSave, $caption, $sort, (int)$_POST['id']]);
            flash_set('success', 'Photo updated.');
        } else {
            db()->prepare('INSERT INTO gallery (image, caption, sort_order) VALUES (?,?,?)')
                ->execute([$imageToSave, $caption, $sort]);
            flash_set('success', 'Photo added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('gallery.php');
}

if ($action === 'delete' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT image FROM gallery WHERE id = ?');
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    db()->prepare('DELETE FROM gallery WHERE id = ?')->execute([$id]);
    if ($img) delete_photo($img);
    flash_set('success', 'Photo deleted.');
    redirect('gallery.php');
}

$record = ['id'=>'','image'=>'','caption'=>'','sort_order'=>0];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM gallery WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) $record = $row;
}

$photos = db_ok() ? db()->query('SELECT * FROM gallery ORDER BY sort_order, id')->fetchAll() : [];
?>

<div class="page-head">
    <div>
        <h1>Photo Gallery</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Gallery</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="gallery.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Photo</a>
    <?php else: ?>
    <a href="gallery.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:680px;">
    <div class="card-h"><h3><i class="bi bi-camera-fill"></i> <?= $action === 'edit' ? 'Edit Photo' : 'Add Photo' ?></h3></div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:18px;flex-wrap:wrap;">
            <div class="photo-preview" id="galPreview" style="width:160px;height:120px;flex-shrink:0;">
                <?php if (!empty($record['image'])): ?>
                    <img src="<?= e(media_url($record['image'])) ?>" alt="">
                <?php else: ?>
                    <i class="bi bi-image-fill ph-empty"></i>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:240px;">
                <div class="field">
                    <label>Upload image</label>
                    <input type="file" name="image_file" accept="image/*" data-photo-preview="#galPreview"
                        style="padding:8px;background:#fff;border:1px solid var(--line);border-radius:10px;width:100%;">
                </div>
                <div class="field" style="margin:0;">
                    <label>Or image URL</label>
                    <input type="url" name="image_url" value="<?= e(filter_var($record['image'] ?? '', FILTER_VALIDATE_URL) ? $record['image'] : '') ?>" placeholder="https://...">
                </div>
            </div>
        </div>

        <div class="field">
            <label>Caption</label>
            <input type="text" name="caption" value="<?= e($record['caption']) ?>">
        </div>
        <div class="field">
            <label>Sort order</label>
            <input type="number" name="sort_order" value="<?= e($record['sort_order']) ?>">
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
        <a href="gallery.php" class="btn btn-light">Cancel</a>
    </form>
</div>

<?php else: ?>

<div class="card">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:16px;">
        <?php if (!$photos): ?>
            <div style="grid-column:1/-1;text-align:center;color:var(--muted);padding:40px;">
                No photos yet. <a href="gallery.php?action=new">Add one</a>.
            </div>
        <?php else: foreach ($photos as $p): ?>
        <div style="background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;">
            <img src="<?= e(media_url($p['image'])) ?>" style="width:100%;height:140px;object-fit:cover;display:block;">
            <div style="padding:10px 12px;">
                <div style="font-size:13px;font-weight:600;color:var(--ink);"><?= e($p['caption']) ?: '<span style="color:var(--muted);">No caption</span>' ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px;">sort: <?= $p['sort_order'] ?></div>
                <div style="margin-top:8px;display:flex;gap:6px;">
                    <a class="icon-link" href="gallery.php?action=edit&id=<?= $p['id'] ?>"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="gallery.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
