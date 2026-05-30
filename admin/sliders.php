<?php
$pageTitle = 'Hero Sliders';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $caption  = trim($_POST['caption'] ?? '');
    $isActive = !empty($_POST['is_active']) ? 1 : 0;
    $sort     = (int)($_POST['sort_order'] ?? 0);
    $imgUrl   = trim($_POST['image_url'] ?? '');

    // Optional file upload (overrides image_url)
    $existingImg = null;
    if (!empty($_POST['id'])) {
        $stmt = db()->prepare('SELECT image FROM sliders WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $existingImg = $stmt->fetchColumn() ?: null;
    }
    $newImg = upload_photo('image_file', 'sliders', 'slide');
    $imageToSave = $newImg ?: ($imgUrl ?: $existingImg);
    if ($newImg && $existingImg) delete_photo($existingImg);

    if (!$imageToSave) {
        flash_set('error', 'Please upload an image or provide an image URL.');
        redirect('sliders.php');
    }

    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE sliders SET image=?, caption=?, is_active=?, sort_order=? WHERE id=?')
                ->execute([$imageToSave, $caption, $isActive, $sort, (int)$_POST['id']]);
            flash_set('success', 'Slider updated.');
        } else {
            db()->prepare('INSERT INTO sliders (image, caption, is_active, sort_order) VALUES (?,?,?,?)')
                ->execute([$imageToSave, $caption, $isActive, $sort]);
            flash_set('success', 'Slider added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('sliders.php');
}

if ($action === 'delete' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT image FROM sliders WHERE id = ?');
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    db()->prepare('DELETE FROM sliders WHERE id = ?')->execute([$id]);
    if ($img) delete_photo($img);
    flash_set('success', 'Slider deleted.');
    redirect('sliders.php');
}

$record = ['id'=>'','image'=>'','caption'=>'','is_active'=>1,'sort_order'=>0];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM sliders WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) $record = $row;
}

$sliders = db_ok() ? db()->query('SELECT * FROM sliders ORDER BY sort_order, id')->fetchAll() : [];
?>

<div class="page-head">
    <div>
        <h1>Hero Sliders</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Sliders</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="sliders.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Slider</a>
    <?php else: ?>
    <a href="sliders.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:760px;">
    <div class="card-h"><h3><i class="bi bi-images"></i> <?= $action === 'edit' ? 'Edit Slider' : 'Add Slider' ?></h3></div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">

        <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:18px;padding:16px;background:var(--bg);border-radius:12px;flex-wrap:wrap;">
            <div class="photo-preview" id="slidePreview" style="width:200px;height:120px;flex-shrink:0;">
                <?php if (!empty($record['image'])): ?>
                    <img src="<?= e(media_url($record['image'])) ?>" alt="">
                <?php else: ?>
                    <i class="bi bi-image-fill ph-empty"></i>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:240px;">
                <div class="field">
                    <label>Upload image (recommended 1400×500)</label>
                    <input type="file" name="image_file" accept="image/*" data-photo-preview="#slidePreview"
                        style="padding:8px;background:#fff;border:1px solid var(--line);border-radius:10px;width:100%;">
                </div>
                <div class="field" style="margin:0;">
                    <label>Or paste an image URL</label>
                    <input type="url" name="image_url" value="<?= e(filter_var($record['image'] ?? '', FILTER_VALIDATE_URL) ? $record['image'] : '') ?>" placeholder="https://...">
                </div>
            </div>
        </div>

        <div class="field">
            <label>Caption</label>
            <input type="text" name="caption" value="<?= e($record['caption']) ?>" placeholder="e.g. বার্ষিক ক্রীড়া প্রতিযোগিতা ২০২৬">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;background:var(--bg);padding:14px;border-radius:10px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" <?= $record['is_active']?'checked':'' ?> style="width:18px;height:18px;">
                <div>
                    <b style="font-size:13px;">Active</b>
                    <div style="font-size:12px;color:var(--muted);">Show in the homepage hero swiper</div>
                </div>
            </label>
            <div class="field" style="margin:0;">
                <label>Sort order</label>
                <input type="number" name="sort_order" value="<?= e($record['sort_order']) ?>" placeholder="0">
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="sliders.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>#</th><th>Preview</th><th>Caption</th><th>Active</th><th>Sort</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$sliders): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">
                No sliders yet. <a href="sliders.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($sliders as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><img src="<?= e(media_url($s['image'])) ?>" alt="" style="width:140px;height:60px;object-fit:cover;border-radius:6px;"></td>
                <td><?= e($s['caption']) ?: '<span style="color:var(--muted);">—</span>' ?></td>
                <td><?php if ($s['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                <td><?= $s['sort_order'] ?></td>
                <td>
                    <a class="icon-link" href="sliders.php?action=edit&id=<?= $s['id'] ?>"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="sliders.php?action=delete&id=<?= $s['id'] ?>" onclick="return confirm('Delete this slide?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
