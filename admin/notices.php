<?php
$pageTitle = 'Notices';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$record = ['id'=>'','title'=>'','body'=>'','pdf_url'=>'','is_floating'=>0,'is_published'=>1];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM notices WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $record = $row;
}

$notices = [];
if (db_ok() && $action === 'list') {
    $notices = db()->query('SELECT * FROM notices ORDER BY posted_at DESC')->fetchAll();
}

$floatingEnabled = get_setting('floating_notice_enabled', '1') === '1';
?>

<div class="page-head">
    <div>
        <h1>Notices</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Notices</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="notices.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Notice</a>
    <?php else: ?>
    <a href="notices.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:780px;">
    <div class="card-h">
        <h3><i class="bi bi-megaphone-fill"></i> <?= $action === 'edit' ? 'Edit Notice' : 'Add New Notice' ?></h3>
    </div>

    <form id="noticeForm" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">

        <div class="field">
            <label>Title <span style="color:#dc2626;">*</span></label>
            <input type="text" name="title" value="<?= e($record['title']) ?>" required>
        </div>
        <div class="field">
            <label>Body / Description</label>
            <textarea name="body" rows="4"><?= e($record['body']) ?></textarea>
        </div>

        <div class="field">
            <label>Attach PDF (optional, max 6 MB)</label>
            <?php if (!empty($record['pdf_url'])): ?>
                <div style="margin-bottom:8px;padding:8px 12px;background:rgba(var(--primary-rgb),.06);border-radius:8px;font-size:13px;">
                    <i class="bi bi-file-earmark-pdf-fill" style="color:#dc2626;"></i>
                    <a href="<?= e(media_url($record['pdf_url'])) ?>" target="_blank">Current attachment</a>
                    <span style="color:var(--muted);">(uploading a new file will replace it)</span>
                </div>
            <?php endif; ?>
            <input type="file" name="pdf" accept="application/pdf" style="padding:8px;background:#fff;">
            <div style="font-size:12px;color:var(--muted);margin-top:6px;">
                <i class="bi bi-info-circle"></i>
                For automatic compression install Ghostscript on the server (see README).
                Without it, the file is stored as-is.
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;background:var(--bg);padding:14px;border-radius:10px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_floating" value="1" <?= $record['is_floating']?'checked':'' ?> style="width:18px;height:18px;">
                <div>
                    <b style="font-size:13px;">Show as floating popup</b>
                    <div style="font-size:12px;color:var(--muted);">Auto-shows on every public page until dismissed.</div>
                </div>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_published" value="1" <?= $record['is_published']?'checked':'' ?> style="width:18px;height:18px;">
                <div>
                    <b style="font-size:13px;">Published</b>
                    <div style="font-size:12px;color:var(--muted);">Visible in marquee, notices page & API.</div>
                </div>
            </label>
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="notices.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('noticeForm').addEventListener('submit', async function(ev) {
    ev.preventDefault();
    const fd = new FormData(this);
    // Force checkbox values to 0 when unchecked
    if (!this.querySelector('[name=is_floating]').checked)  fd.set('is_floating',  '0');
    if (!this.querySelector('[name=is_published]').checked) fd.set('is_published', '0');
    const btn = this.querySelector('button[type=submit]');
    const old = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Saving…';
    try {
        const r = await fetch(window.APP.api + '/notices_save.php', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const j = await r.json();
        if (!j.ok) throw new Error(j.msg || 'Save failed');
        Toast.success(j.msg || 'Saved.');
        setTimeout(() => location.href = 'notices.php', 600);
    } catch (e) {
        Toast.error(e.message);
        btn.disabled = false; btn.innerHTML = old;
    }
});
</script>

<?php else: // ===== List view ===== ?>

<!-- Global floating toggle -->
<div class="card" style="margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
        <h3 style="margin:0;font-size:15px;color:var(--primary);">
            <i class="bi bi-window-stack"></i> Floating Popup (global on/off)
        </h3>
        <p style="margin:4px 0 0;font-size:13px;color:var(--muted);">
            Master switch for the auto-popup notice on the public site. When OFF,
            no notice will pop up even if a notice has "is floating" checked.
        </p>
    </div>
    <label class="switch" style="display:inline-flex;align-items:center;gap:10px;cursor:pointer;">
        <input id="floatToggle" type="checkbox" <?= $floatingEnabled ? 'checked' : '' ?>
            style="width:46px;height:26px;appearance:none;background:#cbd5e1;border-radius:99px;position:relative;outline:none;cursor:pointer;transition:.15s;">
        <span id="floatToggleLabel" style="font-weight:600;font-size:13px;color:<?= $floatingEnabled ? '#047857' : '#b91c1c' ?>;">
            <?= $floatingEnabled ? 'Enabled' : 'Disabled' ?>
        </span>
    </label>
</div>
<style>
#floatToggle:checked { background: var(--primary) !important; }
#floatToggle::after {
    content: ''; position: absolute; top: 2px; left: 2px;
    width: 22px; height: 22px; background: #fff; border-radius: 50%;
    transition: .15s; box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
#floatToggle:checked::after { left: 22px; }
</style>
<script>
document.getElementById('floatToggle').addEventListener('change', async function() {
    const on = this.checked;
    try {
        const r = await fetch(window.APP.api + '/floating_toggle.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'enabled=' + (on ? '1' : '0')
        });
        const j = await r.json();
        if (!j.ok) throw new Error(j.msg);
        Toast.success(j.msg);
        const lbl = document.getElementById('floatToggleLabel');
        lbl.textContent = on ? 'Enabled' : 'Disabled';
        lbl.style.color = on ? '#047857' : '#b91c1c';
    } catch (e) {
        this.checked = !on;
        Toast.error(e.message);
    }
});
</script>

<div class="card">
    <div class="toolbar">
        <span style="color:var(--muted);font-size:13px;">All notices · Pin one as floating</span>
        <span class="badge badge-info"><?= count($notices) ?> total</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>#</th><th>Title</th><th>PDF</th>
                <th>Floating</th><th>Published</th><th>Posted</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$notices): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">
                No notices yet. <a href="notices.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($notices as $n): ?>
            <tr data-id="<?= $n['id'] ?>">
                <td><?= $n['id'] ?></td>
                <td>
                    <b><?= e($n['title']) ?></b>
                    <?php if ($n['body']): ?>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;line-height:1.4;">
                            <?= e(mb_strimwidth($n['body'], 0, 80, '…')) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($n['pdf_url']): ?>
                        <a href="<?= e(media_url($n['pdf_url'])) ?>" target="_blank" class="badge badge-info">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                            <?= number_format(($n['pdf_size'] ?? 0)/1024, 0) ?> KB
                        </a>
                    <?php else: ?>
                        <span style="color:var(--muted);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($n['is_floating']): ?>
                        <span class="badge badge-warn"><i class="bi bi-pin-angle-fill"></i> Floating</span>
                    <?php else: ?>
                        <span class="badge badge-muted">No</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($n['is_published']): ?>
                        <span class="badge badge-success">Published</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Draft</span>
                    <?php endif; ?>
                </td>
                <td><?= date('M d, Y', strtotime($n['posted_at'])) ?></td>
                <td style="white-space:nowrap;">
                    <a class="icon-link" href="notices.php?action=edit&id=<?= $n['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="#"
                       data-ajax-delete="<?= BASE_URL ?>/api/notices_delete.php?id=<?= $n['id'] ?>"
                       data-confirm="Delete this notice?"
                       title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
