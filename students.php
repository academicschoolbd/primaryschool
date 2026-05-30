<?php
$pageTitle  = 'শিক্ষার্থী তালিকা | Student List';
$activeMenu = 'admin';
require_once __DIR__ . '/includes/public_header.php';

$classNames = [];
if (db_ok()) {
    $classNames = db()->query("SELECT DISTINCT name FROM classes ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="t2-page-hero">
    <div class="container">
        <h1><i class="fa fa-user-graduate me-2" style="color:var(--accent);"></i>শিক্ষার্থী তালিকা</h1>
        <div class="t2-breadcrumb">
            <a href="<?= BASE_URL ?>/"><i class="fa fa-home"></i> হোম</a>
            <i class="fa fa-chevron-right"></i>
            <span>শিক্ষার্থী</span>
        </div>
    </div>
</div>

<section style="padding:32px 0 50px;">
    <div class="container">

        <div class="t2-filter-card" data-aos="fade-up">
            <h5 class="section-title mb-4">শিক্ষার্থী অনুসন্ধান</h5>
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label>শিক্ষাবর্ষ</label>
                    <select id="stYear">
                        <?php foreach ($years as $y): ?>
                        <option value="<?= $y['id'] ?>" <?= $y['id']==$curYear?'selected':'' ?>>
                            <?= e($y['name']) ?><?= $y['is_current'] ? ' (চলমান)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if (!$years): ?><option value=""><?= e(date('Y')) ?></option><?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label>শ্রেণী</label>
                    <select id="stClass">
                        <option value="">— শ্রেণী বেছে নিন —</option>
                        <?php foreach ($classNames as $cn): ?>
                        <option value="<?= e($cn) ?>"><?= e($cn) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label>শাখা</label>
                    <select id="stSection" disabled>
                        <option value="">— শ্রেণী আগে বেছে নিন —</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label>লিঙ্গ</label>
                    <select id="stGender">
                        <option value="">— সকল —</option>
                        <option value="male">ছেলে</option>
                        <option value="female">মেয়ে</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label>স্ট্যাটাস</label>
                    <select id="stStatus">
                        <option value="active" selected>চলমান</option>
                        <option value="inactive">নিষ্ক্রিয়</option>
                        <option value="">সকল</option>
                    </select>
                </div>
                <div class="col-12 mt-2">
                    <button id="stSearch" class="btn-primary-custom" type="button">
                        <i class="fa fa-search me-1"></i> শিক্ষার্থী খুঁজুন
                    </button>
                </div>
            </div>
        </div>

        <div id="stListContainer">
            <div class="t2-result-empty">
                <i class="fa fa-users"></i>
                <p>শ্রেণী ও শাখা নির্বাচন করে অনুসন্ধান করুন।</p>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const cls    = document.getElementById('stClass');
    const sec    = document.getElementById('stSection');
    const gender = document.getElementById('stGender');
    const status = document.getElementById('stStatus');
    const year   = document.getElementById('stYear');
    const btn    = document.getElementById('stSearch');
    const target = document.getElementById('stListContainer');

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    const bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    function bnNum(n){ return String(n).split('').map(c=>/\d/.test(c)?bnDigits[+c]:c).join(''); }

    cls.addEventListener('change', async function() {
        const v = cls.value;
        if (!v) { sec.innerHTML='<option value="">— শ্রেণী আগে বেছে নিন —</option>'; sec.disabled=true; return; }
        sec.disabled = true;
        sec.innerHTML = '<option value="">লোড হচ্ছে...</option>';
        try {
            const yp = year && year.value ? '&year_id=' + encodeURIComponent(year.value) : '';
            const r = await fetch(window.APP.api + '/public_sections.php?class=' + encodeURIComponent(v) + yp);
            const j = await r.json();
            const opts = ['<option value="">সকল শাখা</option>'];
            (j.data || []).forEach(s => {
                opts.push(`<option value="${s.id}">${escapeHtml(s.section || '(কোনো শাখা নয়)')}</option>`);
            });
            sec.innerHTML = opts.join('');
            sec.disabled = false;
        } catch(e) {
            sec.innerHTML = '<option value="">— লোড ব্যর্থ —</option>';
        }
    });

    async function search() {
        if (!cls.value) {
            target.innerHTML = '<div class="t2-result-empty"><i class="fa fa-info-circle"></i><p>প্রথমে একটি শ্রেণী নির্বাচন করুন।</p></div>';
            return;
        }
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> লোড হচ্ছে...';
        btn.disabled = true;
        target.innerHTML = '<div class="t2-result-empty"><i class="fa fa-spinner fa-spin"></i><p>শিক্ষার্থী খোঁজা হচ্ছে...</p></div>';

        const params = new URLSearchParams();
        if (sec.value) params.set('class_id', sec.value);
        else params.set('class', cls.value);
        if (gender.value) params.set('gender', gender.value);
        if (status.value) params.set('status', status.value);
        if (year && year.value) params.set('year_id', year.value);

        try {
            const r = await fetch(window.APP.api + '/public_students.php?' + params.toString());
            const j = await r.json();
            btn.innerHTML = oldHtml; btn.disabled = false;
            if (!j.ok || !(j.data || []).length) {
                target.innerHTML = '<div class="t2-result-empty"><i class="fa fa-search"></i><p>কোনো শিক্ষার্থী পাওয়া যায়নি।</p></div>';
                return;
            }
            const rows = j.data;
            let html = `
                <div class="t2-card">
                    <div class="t2-card-header">
                        <i class="fa fa-users"></i> শিক্ষার্থী তালিকা
                        <span style="margin-left:auto;font-size:12px;background:rgba(255,255,255,.2);padding:2px 12px;border-radius:20px;">${bnNum(rows.length)} জন</span>
                    </div>
                    <div class="t2-card-body p-0">
                        <div class="table-responsive">
                            <table class="t2-list-table">
                                <thead>
                                    <tr>
                                        <th>ছবি</th>
                                        <th>রোল</th>
                                        <th>নাম</th>
                                        <th>অভিভাবক</th>
                                        <th>লিঙ্গ</th>
                                        <th>শ্রেণী</th>
                                    </tr>
                                </thead>
                                <tbody>`;
            rows.forEach(s => {
                const photo = s.photo_url
                    ? `<img class="t2-list-photo" src="${escapeHtml(s.photo_url)}" alt="">`
                    : `<div class="t2-list-photo-placeholder"><i class="fa fa-user"></i></div>`;
                const genderBn = s.gender === 'male' ? 'ছেলে' : s.gender === 'female' ? 'মেয়ে' : '—';
                html += `
                    <tr>
                        <td>${photo}</td>
                        <td>${escapeHtml(s.roll_no || '')}</td>
                        <td style="font-weight:600;color:var(--primary);">${escapeHtml(s.name)}</td>
                        <td>${escapeHtml(s.parent_name || '—')}</td>
                        <td>${genderBn}</td>
                        <td>${escapeHtml(s.class_label || '—')}</td>
                    </tr>`;
            });
            html += `</tbody></table></div></div></div>`;
            target.innerHTML = html;
        } catch(e) {
            btn.innerHTML = oldHtml; btn.disabled = false;
            target.innerHTML = '<div class="t2-result-empty"><i class="fa fa-times-circle"></i><p>শিক্ষার্থী লোড করতে সমস্যা হয়েছে।</p></div>';
        }
    }
    btn.addEventListener('click', search);
})();
</script>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
