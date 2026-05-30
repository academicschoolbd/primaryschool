<?php
$pageTitle = 'Bulk Import Students';
require_once __DIR__ . '/../includes/header.php';

$years = all_years();
$curYear = current_year_id();
?>

<div class="page-head">
    <div>
        <h1>Bulk Import Students</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Import</div>
    </div>
    <a href="<?= ASSETS_URL ?>/students_template.csv" download class="btn btn-light"><i class="bi bi-download"></i> Download Sample CSV</a>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="card" style="max-width:920px;">
    <div class="card-h">
        <h3><i class="bi bi-file-earmark-spreadsheet-fill"></i> Step 1 — Upload CSV</h3>
        <span class="badge badge-info">Year: <b><?= e(current_year_name()) ?></b></span>
    </div>

    <div style="background:rgba(var(--accent-rgb),.08);border:1px solid rgba(var(--accent-rgb),.3);padding:14px 18px;border-radius:10px;margin-bottom:18px;font-size:13px;color:var(--accent-dark);">
        <i class="bi bi-info-circle-fill"></i>
        <b>CSV columns (first row = header):</b>
        <code style="background:#fff;padding:1px 6px;border-radius:4px;color:var(--ink);font-size:12px;">roll_no, name, class_name, section, gender, dob, parent_name, phone, address, status</code>
        <ul style="margin:8px 0 0;padding-left:22px;font-size:12.5px;line-height:1.7;">
            <li><b>class_name + section</b> must match an existing class for the chosen year (e.g. "Grade 1" + "A")</li>
            <li><b>gender</b> = male / female / other · <b>status</b> = active / inactive</li>
            <li><b>dob</b> in YYYY-MM-DD format (or leave blank)</li>
            <li>Use the <b>Download Sample CSV</b> button above to get a template</li>
        </ul>
    </div>

    <form id="csvForm" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 200px;gap:14px;align-items:end;">
            <div class="field" style="margin:0;">
                <label>CSV file</label>
                <input type="file" name="csv" accept=".csv,text/csv" required
                       style="padding:10px;background:#fff;border:1px solid var(--line);border-radius:10px;width:100%;">
            </div>
            <div class="field" style="margin:0;">
                <label>Target year</label>
                <select name="year_id">
                    <?php foreach ($years as $y): ?>
                    <option value="<?= $y['id'] ?>" <?= (int)$curYear===(int)$y['id']?'selected':'' ?>>
                        <?= e($y['name']) ?><?= $y['is_current']?' (current)':'' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="margin-top:14px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-eye"></i> Preview Rows</button>
        </div>
    </form>
</div>

<div id="previewBox" style="margin-top:20px;"></div>

<script>
let lastUploadedFile = null;
let lastYearId = null;

document.getElementById('csvForm').addEventListener('submit', async function(ev) {
    ev.preventDefault();
    const fd = new FormData(this);
    fd.set('mode', 'preview');
    lastUploadedFile = fd.get('csv');
    lastYearId = fd.get('year_id');
    const btn = this.querySelector('button[type=submit]');
    const old = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Parsing…';
    try {
        const r = await fetch(window.APP.api + '/students_import.php', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        const j = await r.json();
        if (!j.ok) throw new Error(j.msg || 'Parse failed');
        renderPreview(j.data);
    } catch(e) {
        Toast.error(e.message);
    } finally {
        btn.disabled = false; btn.innerHTML = old;
    }
});

function renderPreview(data) {
    const rows = data.preview || [];
    const valid = data.valid_count || 0;
    const errs  = data.errors || [];
    const total = data.total_rows || 0;

    let html = `
        <div class="card">
            <div class="card-h">
                <h3><i class="bi bi-list-check"></i> Step 2 — Review &amp; import</h3>
                <div style="display:flex;gap:6px;">
                    <span class="badge badge-success">${valid} valid</span>
                    ${errs.length ? '<span class="badge badge-danger">' + errs.length + ' errors</span>' : ''}
                    <span class="badge badge-info">${total} total rows</span>
                </div>
            </div>
            ${errs.length ? `
                <details style="margin-bottom:14px;">
                    <summary style="cursor:pointer;color:var(--primary);font-weight:600;">⚠ ${errs.length} error(s) — click to view</summary>
                    <ul style="background:#fef2f2;border-radius:8px;padding:10px 22px;margin-top:8px;font-size:12px;color:#b91c1c;line-height:1.7;">
                        ${errs.map(er => '<li>Row ' + er.row + ': ' + escapeHtml(er.msg) + '</li>').join('')}
                    </ul>
                </details>
            ` : ''}
            <div class="table-responsive">
                <table class="tbl">
                    <thead>
                        <tr><th>#</th><th>Roll</th><th>Name</th><th>Class</th><th>Gender</th><th>Parent</th><th>Status</th></tr>
                    </thead>
                    <tbody>
    `;
    rows.forEach((r, i) => {
        html += `<tr>
            <td>${i+1}</td>
            <td><b>${escapeHtml(r.roll_no || '')}</b></td>
            <td>${escapeHtml(r.name || '')}</td>
            <td>${escapeHtml((r.class_name || '') + (r.section ? ' - ' + r.section : ''))}</td>
            <td>${escapeHtml(r.gender || '')}</td>
            <td>${escapeHtml(r.parent_name || '')}</td>
            <td>${r.class_id ? '<span class="badge badge-success">OK</span>' : '<span class="badge badge-danger">No class match</span>'}</td>
        </tr>`;
    });
    html += `
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <button id="btnDoImport" class="btn btn-primary" ${valid === 0 ? 'disabled' : ''}>
                    <i class="bi bi-cloud-arrow-up-fill"></i> Import ${valid} valid student(s)
                </button>
                <span style="color:var(--muted);font-size:12px;">
                    <i class="bi bi-shield-check"></i>
                    Each insert is audit-logged. Errors are skipped.
                </span>
            </div>
        </div>
    `;
    document.getElementById('previewBox').innerHTML = html;

    document.getElementById('btnDoImport')?.addEventListener('click', doImport);
}

async function doImport() {
    if (!lastUploadedFile) { Toast.error('Re-upload the CSV.'); return; }
    const btn = document.getElementById('btnDoImport');
    const old = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Importing…';
    const fd = new FormData();
    fd.set('csv', lastUploadedFile);
    fd.set('year_id', lastYearId);
    fd.set('mode', 'import');
    try {
        const r = await fetch(window.APP.api + '/students_import.php', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        const j = await r.json();
        if (!j.ok) throw new Error(j.msg);
        Toast.success(j.msg);
        document.getElementById('previewBox').innerHTML =
            '<div class="card" style="text-align:center;padding:40px;color:var(--muted);">' +
            '<i class="bi bi-check-circle-fill" style="font-size:32px;color:#10b981;"></i>' +
            '<h3 style="margin:14px 0 6px;color:var(--primary);">Import complete</h3>' +
            '<p>' + escapeHtml(j.msg) + '</p>' +
            '<a href="students.php" class="btn btn-primary" style="margin-top:14px;"><i class="bi bi-arrow-right"></i> View students</a>' +
            '</div>';
    } catch(e) {
        Toast.error(e.message);
        btn.disabled = false; btn.innerHTML = old;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
