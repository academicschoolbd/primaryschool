<?php
$pageTitle = 'Extracurricular (সহ-পাঠক্রমিক)';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort        = (int)($_POST['sort_order'] ?? 0);
    $isActive    = !empty($_POST['is_active']) ? 1 : 0;
    $imgUrl      = trim($_POST['image_url'] ?? '');

    $existing = null;
    if (!empty($_POST['id'])) {
        $stmt = db()->prepare('SELECT image FROM extracurricular WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $existing = $stmt->fetchColumn() ?: null;
    }
    $newImg = upload_photo('image_file', 'extracurricular', 'extra');
    $imageToSave = $newImg ?: ($imgUrl ?: $existing);
    if ($newImg && $existing) delete_photo($existing);

    if ($name === '') {
        flash_set('error', 'Name is required.');
        redirect('extracurricular.php');
    }
    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE extracurricular SET name=?, image=?, description=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$name, $imageToSave, $description, $sort, $isActive, (int)$_POST['id']]);
            flash_set('success', 'Activity updated.');
        } else {
            db()->prepare('INSERT INTO extracurricular (name, image, description, sort_order, is_active) VALUES (?,?,?,?,?)')
                ->execute([$name, $imageToSave, $description, $sort, $isActive]);
            flash_set('success', 'Activity added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('extracurricular.php');
}

if ($action === 'delete' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT image FROM extracurricular WHERE id = ?');
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    db()->prepare('DELETE FROM extracurricular WHERE id = ?')->execute([$id]);
    if ($img) delete_photo($img);
    flash_set('success', 'Deleted.');
    redirect('extracurricular.php');
}

$record = ['id'=>'','name'=>'','image'=>'','description'=>'','sort_order'=>0,'is_active'=>1];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM extracurricular WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) $record = $row;
}

$items = db_ok() ? db()->query('SELECT * FROM extracurricular ORDER BY sort_order, id')->fetchAll() : [];
?>

<div class="page-head">
    <div>
        <h1>Extracurricular Activities</h1>
        <div class="crumbs"><a href="index.php">Home</a> / সহ-পাঠক্রমিক কার্যক্রম</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="extracurricular.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Activity</a>
    <?php else: ?>
    <a href="extracurricular.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:680px;">
    <div class="card-h"><h3><i class="bi bi-star-fill"></i> <?= $action === 'edit' ? 'Edit Activity' : 'Add Activity' ?></h3></div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">

        <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:18px;flex-wrap:wrap;">
            <div class="photo-preview" id="exPreview" style="width:160px;height:100px;flex-shrink:0;">
                <?php if (!empty($record['image'])): ?>
                    <img src="<?= e(media_url($record['image'])) ?>" alt="">
                <?php else: ?>
                    <i class="bi bi-image-fill ph-empty"></i>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:220px;">
                <div class="field">
                    <label>Upload image</label>
                    <input type="file" name="image_file" accept="image/*" data-photo-preview="#exPreview"
                        style="padding:8px;background:#fff;border:1px solid var(--line);border-radius:10px;width:100%;">
                </div>
                <div class="field" style="margin:0;">
                    <label>Or image URL</label>
                    <input type="url" name="image_url" value="<?= e(filter_var($record['image'] ?? '', FILTER_VALIDATE_URL) ? $record['image'] : '') ?>" placeholder="https://...">
                </div>
            </div>
        </div>

        <div class="field">
            <label>Name <span style="color:#dc2626;">*</span></label>
            <input type="text" name="name" value="<?= e($record['name']) ?>" required placeholder="e.g. BNCC, Debate Club">
        </div>
        <div class="field">
            <label>Description (optional)</label>
            <textarea name="description" rows="3" placeholder="Short description..."><?= e($record['description']) ?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;background:var(--bg);padding:14px;border-radius:10px;">
            <div class="field" style="margin:0;">
                <label>Sort order</label>
                <input type="number" name="sort_order" value="<?= e($record['sort_order']) ?>">
            </div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" <?= $record['is_active']?'checked':'' ?> style="width:18px;height:18px;">
                <div>
                    <b style="font-size:13px;">Active</b>
                    <div style="font-size:12px;color:var(--muted);">Show on homepage</div>
                </div>
            </label>
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="extracurricular.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="card">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:16px;">
        <?php if (!$items): ?>
            <div style="grid-column:1/-1;text-align:center;color:var(--muted);padding:40px;">
                No activities yet. <a href="extracurricular.php?action=new">Add the first one</a>.
            </div>
        <?php else: foreach ($items as $it): ?>
        <div style="background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;<?= $it['is_active']?'':'opacity:.55;' ?>">
            <?php if ($it['image']): ?>
                <img src="<?= e(media_url($it['image'])) ?>" style="width:100%;height:120px;object-fit:cover;display:block;">
            <?php else: ?>
                <div style="width:100%;height:120px;background:var(--bg);display:grid;place-items:center;color:var(--muted);">
                    <i class="bi bi-star" style="font-size:28px;"></i>
                </div>
            <?php endif; ?>
            <div style="padding:10px 12px;">
                <div style="font-size:13px;font-weight:700;color:var(--primary);"><?= e($it['name']) ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px;">
                    <?php if ($it['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Hidden</span>
                    <?php endif; ?>
                    sort: <?= $it['sort_order'] ?>
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;">
                    <a class="icon-link" href="extracurricular.php?action=edit&id=<?= $it['id'] ?>"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="extracurricular.php?action=delete&id=<?= $it['id'] ?>" onclick="return confirm('Delete \'<?= e(addslashes($it['name'])) ?>\'?')"><i class="bi bi-trash"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
