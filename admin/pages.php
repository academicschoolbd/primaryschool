<?php
$pageTitle = 'CMS Pages';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// === Save (full page submit; non-AJAX so HTML body posts cleanly) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $title = trim($_POST['title'] ?? '');
    $slug  = slugify($_POST['slug'] ?? $title);
    $body  = sanitize_html($_POST['body'] ?? '');
    $pub   = !empty($_POST['is_published']) ? 1 : 0;
    $sort  = (int)($_POST['sort_order'] ?? 0);

    if ($title === '') {
        flash_set('error', 'Title is required.');
        redirect('pages.php');
    }
    try {
        if (!empty($_POST['id'])) {
            db()->prepare('UPDATE pages SET slug=?, title=?, body=?, is_published=?, sort_order=? WHERE id=?')
                ->execute([$slug, $title, $body, $pub, $sort, (int)$_POST['id']]);
            flash_set('success', 'Page updated.');
        } else {
            db()->prepare('INSERT INTO pages (slug,title,body,is_published,sort_order) VALUES (?,?,?,?,?)')
                ->execute([$slug, $title, $body, $pub, $sort]);
            flash_set('success', 'Page created.');
        }
    } catch (PDOException $e) {
        if ((int)$e->errorInfo[1] === 1062) {
            flash_set('error', 'Slug "' . e($slug) . '" already exists. Pick a different one.');
        } else {
            flash_set('error', 'Could not save: ' . $e->getMessage());
        }
    }
    redirect('pages.php');
}

if ($action === 'delete' && $id && db_ok()) {
    db()->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
    flash_set('success', 'Page deleted.');
    redirect('pages.php');
}

$record = ['id'=>'','slug'=>'','title'=>'','body'=>'','is_published'=>1,'sort_order'=>0];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $record = $row;
}

$pages = [];
if (db_ok() && $action === 'list') {
    $pages = db()->query('SELECT * FROM pages ORDER BY sort_order, title')->fetchAll();
}
?>

<div class="page-head">
    <div>
        <h1>CMS Pages</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Pages</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="pages.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Page</a>
    <?php else: ?>
    <a href="pages.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:920px;">
    <div class="card-h">
        <h3><i class="bi bi-file-text-fill"></i> <?= $action === 'edit' ? 'Edit Page' : 'New Page' ?></h3>
        <?php if ($action === 'edit' && !empty($record['slug'])): ?>
        <a href="<?= BASE_URL ?>/page.php?slug=<?= e($record['slug']) ?>" target="_blank" class="btn btn-light btn-sm">
            <i class="bi bi-eye"></i> Preview
        </a>
        <?php endif; ?>
    </div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
            <div class="field">
                <label>Title <span style="color:#dc2626;">*</span></label>
                <input type="text" name="title" value="<?= e($record['title']) ?>" required placeholder="e.g. প্রতিষ্ঠান সম্পর্কে">
            </div>
            <div class="field">
                <label>Slug (URL)</label>
                <input type="text" name="slug" value="<?= e($record['slug']) ?>" placeholder="auto from title">
                <div style="font-size:11px;color:var(--muted);margin-top:4px;">
                    URL: <code><?= BASE_URL ?>/page.php?slug=<?= e($record['slug'] ?: 'auto') ?></code>
                </div>
            </div>
        </div>

        <div class="field">
            <label>Body (HTML allowed) <span style="color:#dc2626;">*</span></label>
            <div style="background:rgba(var(--accent-rgb),.08);border:1px solid rgba(var(--accent-rgb),.3);border-radius:8px;padding:8px 12px;font-size:12px;color:var(--accent-dark);margin-bottom:8px;">
                <i class="bi bi-info-circle"></i>
                Allowed tags: <code>p, br, b, i, u, h2-h6, ul, ol, li, a, img, table, blockquote, pre, code, hr, div, span</code>.
                Event handlers and javascript: URLs are auto-stripped.
            </div>
            <textarea name="body" rows="14" style="font-family:monospace;font-size:13px;line-height:1.6;"><?= e($record['body']) ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;background:var(--bg);padding:14px;border-radius:10px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_published" value="1" <?= $record['is_published']?'checked':'' ?> style="width:18px;height:18px;">
                <div>
                    <b style="font-size:13px;">Published</b>
                    <div style="font-size:12px;color:var(--muted);">Visible to public visitors via URL</div>
                </div>
            </label>
            <div class="field" style="margin:0;">
                <label>Sort order</label>
                <input type="number" name="sort_order" value="<?= e($record['sort_order']) ?>" placeholder="0">
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Page</button>
            <a href="pages.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="card" style="margin-bottom:18px;background:linear-gradient(135deg, rgba(var(--primary-rgb),.06), rgba(var(--accent-rgb),.06));border:1px solid rgba(var(--primary-rgb),.2);">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <div style="width:46px;height:46px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:var(--accent);display:grid;place-items:center;font-size:22px;flex-shrink:0;">
            <i class="bi bi-magic"></i>
        </div>
        <div style="flex:1;min-width:200px;">
            <h3 style="margin:0;font-size:15px;color:var(--primary);">CMS Pages — fill any "missing" page from here</h3>
            <p style="margin:4px 0 0;font-size:13px;color:var(--muted);">
                Every nav-dropdown link points to <code>page.php?slug=...</code>. Edit the body of each page below
                and visitors will see the new content instantly. To add a brand-new menu item later, just create a
                page here and link to it.
            </p>
        </div>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <span style="color:var(--muted);font-size:13px;">All CMS pages</span>
        <span class="badge badge-info"><?= count($pages) ?> total</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>#</th><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$pages): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">
                No pages yet. <a href="pages.php?action=new">Create one</a>.
            </td></tr>
        <?php else: foreach ($pages as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td>
                    <b><?= e($p['title']) ?></b>
                    <?php if (!empty($p['body'])): ?>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;line-height:1.4;">
                            <?= e(mb_strimwidth(strip_tags($p['body']), 0, 80, '…')) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td><code style="font-size:12px;background:var(--bg);padding:2px 6px;border-radius:4px;"><?= e($p['slug']) ?></code></td>
                <td>
                    <?php if ($p['is_published']): ?>
                        <span class="badge badge-success">Published</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Draft</span>
                    <?php endif; ?>
                </td>
                <td><?= date('M d, Y', strtotime($p['updated_at'])) ?></td>
                <td style="white-space:nowrap;">
                    <a class="icon-link" href="<?= BASE_URL ?>/page.php?slug=<?= e($p['slug']) ?>" target="_blank" title="View"><i class="bi bi-eye"></i></a>
                    <a class="icon-link" href="pages.php?action=edit&id=<?= $p['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="pages.php?action=delete&id=<?= $p['id'] ?>"
                       onclick="return confirm('Delete page \'<?= e(addslashes($p['title'])) ?>\'?');"
                       title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
