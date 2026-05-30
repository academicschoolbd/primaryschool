<?php
$pageTitle  = 'শিক্ষক তালিকা | Teacher List';
$activeMenu = 'admin';
require_once __DIR__ . '/includes/public_header.php';
?>

<div class="t2-page-hero">
    <div class="container">
        <h1><i class="fa fa-chalkboard-teacher me-2" style="color:var(--accent);"></i>শিক্ষক-শিক্ষিকা তালিকা</h1>
        <div class="t2-breadcrumb">
            <a href="<?= BASE_URL ?>/"><i class="fa fa-home"></i> হোম</a>
            <i class="fa fa-chevron-right"></i>
            <span>শিক্ষক</span>
        </div>
    </div>
</div>

<section style="padding:32px 0 50px;">
    <div class="container">

        <div class="t2-filter-card" data-aos="fade-up">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label>অনুসন্ধান</label>
                    <input id="tQ" type="text" placeholder="নাম, পদবী বা বিষয় দিয়ে খুঁজুন...">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label>স্ট্যাটাস</label>
                    <select id="tStatus">
                        <option value="active" selected>সক্রিয়</option>
                        <option value="inactive">নিষ্ক্রিয়</option>
                        <option value="">সকল</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <button id="tSearch" class="btn-primary-custom w-100" type="button">
                        <i class="fa fa-search me-1"></i> খুঁজুন
                    </button>
                </div>
            </div>
        </div>

        <div id="tListContainer">
            <div class="t2-result-empty">
                <i class="fa fa-spinner fa-spin"></i>
                <p>শিক্ষকদের তালিকা লোড হচ্ছে...</p>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const q     = document.getElementById('tQ');
    const stat  = document.getElementById('tStatus');
    const btn   = document.getElementById('tSearch');
    const target= document.getElementById('tListContainer');

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    const bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    function bnNum(n){ return String(n).split('').map(c=>/\d/.test(c)?bnDigits[+c]:c).join(''); }

    let timer = null;
    function debounced(fn, d=250) { return function(){ clearTimeout(timer); timer = setTimeout(fn, d); }; }

    async function load() {
        const params = new URLSearchParams();
        if (q.value.trim()) params.set('q', q.value.trim());
        if (stat.value)     params.set('status', stat.value);
        target.innerHTML = '<div class="t2-result-empty"><i class="fa fa-spinner fa-spin"></i><p>লোড হচ্ছে...</p></div>';
        try {
            const r = await fetch(window.APP.api + '/public_teachers.php?' + params.toString());
            const j = await r.json();
            if (!j.ok || !(j.data || []).length) {
                target.innerHTML = '<div class="t2-result-empty"><i class="fa fa-user-slash"></i><p>কোনো শিক্ষক পাওয়া যায়নি।</p></div>';
                return;
            }
            const rows = j.data;
            let html = `
                <div class="t2-card mb-4">
                    <div class="t2-card-header">
                        <i class="fa fa-chalkboard-teacher"></i> শিক্ষক-শিক্ষিকা
                        <span style="margin-left:auto;font-size:12px;background:rgba(255,255,255,.2);padding:2px 12px;border-radius:20px;">${bnNum(rows.length)} জন</span>
                    </div>
                    <div class="t2-card-body">
                        <div class="t2-teacher-grid">`;
            rows.forEach(t => {
                const photo = t.photo_url
                    ? `<img src="${escapeHtml(t.photo_url)}" alt="">`
                    : `<i class="fa fa-user"></i>`;
                html += `
                    <div class="t2-teacher-card">
                        <div class="photo-wrap">${photo}</div>
                        <div class="name">${escapeHtml(t.name)}</div>
                        ${t.designation ? `<div class="desig">${escapeHtml(t.designation)}</div>` : ''}
                        ${t.subject ? `<div class="subj"><i class="fa fa-book"></i> ${escapeHtml(t.subject)}</div>` : ''}
                        <div class="contact">
                            ${t.email ? `<a href="mailto:${escapeHtml(t.email)}"><i class="fa fa-envelope"></i>${escapeHtml(t.email)}</a>` : ''}
                            ${t.phone ? `<a href="tel:${escapeHtml(t.phone)}"><i class="fa fa-phone"></i>${escapeHtml(t.phone)}</a>` : ''}
                            ${t.joined_on_label ? `<span><i class="fa fa-calendar-alt"></i>যোগদান: ${escapeHtml(t.joined_on_label)}</span>` : ''}
                        </div>
                    </div>`;
            });
            html += `</div></div></div>`;
            target.innerHTML = html;
        } catch(e) {
            target.innerHTML = '<div class="t2-result-empty"><i class="fa fa-times-circle"></i><p>লোড ব্যর্থ।</p></div>';
        }
    }
    btn.addEventListener('click', load);
    q.addEventListener('input', debounced(load));
    stat.addEventListener('change', load);
    document.addEventListener('DOMContentLoaded', load);
    load();
})();
</script>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
