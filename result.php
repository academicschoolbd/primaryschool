<?php
$pageTitle  = 'পরীক্ষার ফলাফল | Result';
$activeMenu = 'academic';
require_once __DIR__ . '/includes/public_header.php';

$classNames = [];
if (db_ok()) {
    $classNames = db()->query("SELECT DISTINCT name FROM classes ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!-- ===== Page Hero ===== -->
<section class="result-hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-icon">
                <i class="fa fa-graduation-cap"></i>
            </div>
            <h1 class="hero-title">পরীক্ষার ফলাফল</h1>
            <div class="hero-sub">Examination Result · Look up your child's marksheet online</div>
            <div class="crumbs">
                <a href="<?= BASE_URL ?>/">হোম</a>
                <i class="fa fa-chevron-right"></i>
                <span>ফলাফল</span>
            </div>
        </div>
    </div>
</section>

<!-- ===== Search Card ===== -->
<section class="result-search-section">
    <div class="container">
        <div class="t2-card result-search-card" data-aos="fade-up">
            <div class="t2-card-header">
                <i class="fa fa-search"></i>
                ফলাফল অনুসন্ধান
            </div>
            <div class="t2-card-body">
                <form id="resultForm" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="rs-label">
                                <i class="fa fa-school"></i> শ্রেণী <span class="req">*</span>
                            </label>
                            <select class="rs-select" id="resClass" required>
                                <option value="">— শ্রেণী নির্বাচন করুন —</option>
                                <?php foreach ($classNames as $cn): ?>
                                <option value="<?= e($cn) ?>"><?= e($cn) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="rs-label">
                                <i class="fa fa-layer-group"></i> শাখা <span class="req">*</span>
                            </label>
                            <select class="rs-select" id="resSection" name="class_id" required disabled>
                                <option value="">— শ্রেণী আগে নির্বাচন করুন —</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="rs-label">
                                <i class="fa fa-id-badge"></i> রোল নম্বর <span class="req">*</span>
                            </label>
                            <input class="rs-input" type="text" id="resRoll" name="roll_no"
                                   placeholder="যেমন: STU-1001" autocomplete="off" required>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="rs-label">
                                <i class="fa fa-clipboard-check"></i> পরীক্ষা <span class="req">*</span>
                            </label>
                            <select class="rs-select" id="resTerm" name="exam_term" required>
                                <option value="first">প্রথম সাময়িক</option>
                                <option value="mid">দ্বিতীয় সাময়িক</option>
                                <option value="final" selected>বার্ষিক পরীক্ষা</option>
                            </select>
                        </div>
                    </div>
                    <div class="rs-actions">
                        <button type="submit" class="btn-accent-custom rs-submit">
                            <i class="fa fa-search me-1"></i> ফলাফল দেখুন
                        </button>
                        <button type="reset" class="rs-reset">
                            <i class="fa fa-eraser me-1"></i> পুনরায় শুরু
                        </button>
                        <span class="rs-hint">
                            <i class="fa fa-info-circle"></i>
                            শ্রেণী, শাখা ও রোল নম্বর সঠিকভাবে দিন।
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== Result Display ===== -->
        <div id="resultArea" class="result-display"></div>
    </div>
</section>

<!-- Hidden marksheet template (cloned by JS) -->
<template id="resultTpl">
    <div class="t2-card marksheet-card" id="marksheetPrintable">
        <!-- Header band -->
        <div class="ms-band">
            <div class="ms-band-inner">
                <img class="ms-logo" src="" alt="Logo" data-bind="school.logo">
                <div class="ms-school">
                    <div class="ms-school-bn" data-bind="school.name_bn"></div>
                    <div class="ms-school-en" data-bind="school.name_en"></div>
                </div>
                <div class="ms-band-meta">
                    <div class="ms-band-tag">MARKSHEET</div>
                    <div class="ms-band-term" data-bind="term.label_bn"></div>
                    <div class="ms-band-year" data-bind="issued_year"></div>
                </div>
            </div>
        </div>

        <!-- Student info -->
        <div class="ms-info">
            <div class="ms-info-grid">
                <div><span class="ms-k">শিক্ষার্থী</span><span class="ms-v" data-bind="student.name"></span></div>
                <div><span class="ms-k">রোল নম্বর</span><span class="ms-v" data-bind="student.roll_no"></span></div>
                <div><span class="ms-k">শ্রেণী</span><span class="ms-v" data-bind="class.label"></span></div>
                <div><span class="ms-k">শ্রেণী শিক্ষক</span><span class="ms-v" data-bind="class.teacher_name"></span></div>
                <div><span class="ms-k">অভিভাবক</span><span class="ms-v" data-bind="student.parent_name"></span></div>
                <div><span class="ms-k">জন্ম তারিখ</span><span class="ms-v" data-bind="student.dob_label"></span></div>
                <div><span class="ms-k">EIIN</span><span class="ms-v" data-bind="school.eiin"></span></div>
                <div><span class="ms-k">প্রকাশের তারিখ</span><span class="ms-v" data-bind="issued_label"></span></div>
            </div>
        </div>

        <!-- Marks table (responsive) -->
        <div class="ms-table-wrap">
            <table class="ms-table">
                <thead>
                    <tr>
                        <th class="num">#</th>
                        <th>বিষয়</th>
                        <th class="num">পূর্ণমান</th>
                        <th class="num">পাশ মার্ক</th>
                        <th class="num">প্রাপ্ত</th>
                        <th class="num">শতাংশ</th>
                        <th class="num">গ্রেড</th>
                        <th class="num">ফলাফল</th>
                    </tr>
                </thead>
                <tbody data-bind="subjects-rows"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><b>মোট</b></td>
                        <td class="num" data-bind="summary.total_max"></td>
                        <td></td>
                        <td class="num"><b data-bind="summary.total_obtained"></b></td>
                        <td class="num" data-bind="summary.percent_label"></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Summary band -->
        <div class="ms-summary">
            <div class="ms-sum-cell">
                <div class="ms-sum-k">মোট নম্বর</div>
                <div class="ms-sum-v" data-bind="summary.total_label"></div>
            </div>
            <div class="ms-sum-cell">
                <div class="ms-sum-k">শতাংশ</div>
                <div class="ms-sum-v" data-bind="summary.percent_label"></div>
            </div>
            <div class="ms-sum-cell">
                <div class="ms-sum-k">গ্রেড</div>
                <div class="ms-sum-v" data-bind="summary.grade" data-bind-color="summary.grade_color"></div>
            </div>
            <div class="ms-sum-cell">
                <div class="ms-sum-k">ফলাফল</div>
                <div class="ms-sum-v" data-bind="summary.status_label" data-bind-color="summary.status_color"></div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="ms-signs">
            <div><div class="line"></div>শ্রেণী শিক্ষক</div>
            <div><div class="line"></div>পরীক্ষা নিয়ন্ত্রক</div>
            <div><div class="line"></div>প্রধান শিক্ষক</div>
        </div>

        <!-- Actions -->
        <div class="ms-actions no-print">
            <button class="btn-primary-custom" type="button" id="btnPrint">
                <i class="fa fa-print me-1"></i> প্রিন্ট করুন
            </button>
            <button class="btn-light-custom" type="button" id="btnNew">
                <i class="fa fa-arrow-left me-1"></i> অন্য ফলাফল
            </button>
            <button class="btn-light-custom d-none" type="button" id="btnShare">
                <i class="fa fa-share-alt me-1"></i> শেয়ার
            </button>
        </div>
    </div>
</template>

<script>
(function() {
    const form     = document.getElementById('resultForm');
    const cls      = document.getElementById('resClass');
    const sec      = document.getElementById('resSection');
    const roll     = document.getElementById('resRoll');
    const term     = document.getElementById('resTerm');
    const submit   = form.querySelector('button[type=submit]');
    const area     = document.getElementById('resultArea');
    const tpl      = document.getElementById('resultTpl');

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    const bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    function bnNum(n) {
        return String(n == null ? '' : n).split('').map(c => /\d/.test(c) ? bnDigits[+c] : c).join('');
    }
    const bnMonths = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
    function bnDate(iso) {
        if (!iso) return '—';
        const d = new Date(iso);
        if (isNaN(d)) return '—';
        return bnNum(d.getDate()) + ' ' + bnMonths[d.getMonth()] + ' ' + bnNum(d.getFullYear());
    }

    function flash(type, msg) {
        area.innerHTML = `<div class="rs-alert rs-${type}">
            <i class="fa ${type==='error'?'fa-exclamation-circle':type==='warn'?'fa-exclamation-triangle':'fa-info-circle'}"></i>
            <span>${escapeHtml(msg)}</span>
        </div>`;
    }

    // ---------- Cascading Class -> Section ----------
    async function loadSections(name) {
        sec.disabled = true;
        sec.innerHTML = '<option value="">লোড হচ্ছে...</option>';
        if (!name) {
            sec.innerHTML = '<option value="">— শ্রেণী আগে নির্বাচন করুন —</option>';
            sec.disabled = true;
            return;
        }
        try {
            const r = await fetch(window.APP.api + '/public_sections.php?class=' + encodeURIComponent(name));
            const j = await r.json();
            if (!j.ok) throw new Error(j.msg || 'লোড ব্যর্থ');
            const opts = ['<option value="">— শাখা নির্বাচন করুন —</option>'];
            (j.data || []).forEach(s => {
                const lbl = s.section || '(কোনো শাখা নয়)';
                opts.push(`<option value="${s.id}">${escapeHtml(lbl)}</option>`);
            });
            sec.innerHTML = opts.join('');
            sec.disabled = false;
        } catch (e) {
            sec.innerHTML = '<option value="">— লোড ব্যর্থ —</option>';
        }
    }
    cls.addEventListener('change', () => loadSections(cls.value));
    form.addEventListener('reset', () => {
        setTimeout(() => {
            sec.innerHTML = '<option value="">— শ্রেণী আগে নির্বাচন করুন —</option>';
            sec.disabled = true;
            area.innerHTML = '';
        }, 10);
    });

    // ---------- Submit -> AJAX ----------
    form.addEventListener('submit', async ev => {
        ev.preventDefault();
        if (!cls.value) { cls.focus(); flash('warn','শ্রেণী নির্বাচন করুন।'); return; }
        if (!sec.value) { sec.focus(); flash('warn','শাখা নির্বাচন করুন।'); return; }
        if (!roll.value.trim()) { roll.focus(); flash('warn','রোল নম্বর দিন।'); return; }

        submit.disabled = true;
        const oldHtml = submit.innerHTML;
        submit.innerHTML = '<span class="rs-spinner"></span> অনুসন্ধান করা হচ্ছে...';
        area.innerHTML = '<div class="rs-loading"><span class="rs-spinner big"></span><div>ফলাফল লোড হচ্ছে...</div></div>';

        const params = new URLSearchParams({
            class_id:  sec.value,
            roll_no:   roll.value.trim(),
            exam_term: term.value
        });

        try {
            const r = await fetch(window.APP.api + '/public_result.php?' + params.toString());
            const j = await r.json();
            if (!j.ok) {
                flash('error', j.msg || 'ফলাফল পাওয়া যায়নি।');
                return;
            }
            renderResult(j.data);
        } catch (e) {
            flash('error', 'নেটওয়ার্ক ত্রুটি — পুনরায় চেষ্টা করুন।');
        } finally {
            submit.disabled = false;
            submit.innerHTML = oldHtml;
        }
    });

    // ---------- Render marksheet from JSON ----------
    function renderResult(data) {
        if (!data) { flash('error','কোনো ডেটা পাওয়া যায়নি।'); return; }

        // Pre-format derived fields
        data.issued_label  = bnDate(data.issued_at);
        data.issued_year   = data.issued_at ? bnNum(new Date(data.issued_at).getFullYear()) : '';
        data.class.label   = (data.class.name || '') + (data.class.section ? ' - ' + data.class.section : '');
        data.student.dob_label = data.student.dob ? bnDate(data.student.dob) : '—';
        data.summary.total_label   = bnNum(data.summary.total_obtained) + ' / ' + bnNum(data.summary.total_max);
        data.summary.percent_label = bnNum(data.summary.percent.toFixed(2)) + '%';
        data.summary.status_label  = data.summary.status === 'pass' ? 'উত্তীর্ণ' : 'অনুত্তীর্ণ';
        data.summary.status_color  = data.summary.status === 'pass' ? '#047857' : '#b91c1c';

        // Clone template
        const node = tpl.content.cloneNode(true);

        // Generic data-bind filling
        node.querySelectorAll('[data-bind]').forEach(el => {
            const path = el.getAttribute('data-bind');
            if (path === 'subjects-rows') return; // handled below
            const v = path.split('.').reduce((o,k) => (o == null ? o : o[k]), data);
            if (el.tagName === 'IMG') {
                el.src = v || '';
            } else {
                el.textContent = (v == null || v === '') ? '—' : v;
            }
            // optional color binding
            const colorPath = el.getAttribute('data-bind-color');
            if (colorPath) {
                const cv = colorPath.split('.').reduce((o,k) => (o == null ? o : o[k]), data);
                if (cv) el.style.color = cv;
            }
        });

        // Subject rows
        const tbody = node.querySelector('[data-bind="subjects-rows"]');
        const rows = (data.subjects || []).map((s, i) => `
            <tr>
                <td class="num">${bnNum(i + 1)}</td>
                <td><b>${escapeHtml(s.name)}</b></td>
                <td class="num">${bnNum(s.full_marks)}</td>
                <td class="num">${bnNum(s.pass_marks)}</td>
                <td class="num"><b>${bnNum(s.marks_obtained)}</b></td>
                <td class="num">${bnNum(Number(s.percent).toFixed(1))}%</td>
                <td class="num"><span class="ms-grade-pill" style="color:${s.grade_color};">${escapeHtml(s.grade)}</span></td>
                <td class="num">
                    <span class="ms-status ${s.status === 'pass' ? 'pass' : 'fail'}">
                        ${s.status === 'pass' ? 'উত্তীর্ণ' : 'অনুত্তীর্ণ'}
                    </span>
                </td>
            </tr>
        `);
        tbody.innerHTML = rows.join('') || '<tr><td colspan="8" class="text-center" style="padding:30px;color:#5a6270;">কোনো ফলাফল নেই।</td></tr>';

        // No-results soft warning
        if (data.summary && data.summary.has_results === false) {
            area.innerHTML = '';
            area.appendChild(node);
            const card = area.querySelector('.marksheet-card');
            const warn = document.createElement('div');
            warn.className = 'rs-alert rs-warn';
            warn.innerHTML = '<i class="fa fa-exclamation-triangle"></i><span>এই শিক্ষার্থীর জন্য এখনো নম্বর প্রকাশিত হয়নি। প্রকাশিত হলে এখানে দেখা যাবে।</span>';
            card.parentNode.insertBefore(warn, card);
        } else {
            area.innerHTML = '';
            area.appendChild(node);
        }

        // Wire up actions
        document.getElementById('btnPrint')?.addEventListener('click', () => window.print());
        document.getElementById('btnNew')?.addEventListener('click', () => {
            area.innerHTML = '';
            form.scrollIntoView({behavior:'smooth', block:'start'});
            roll.focus();
        });
        const shareBtn = document.getElementById('btnShare');
        if (shareBtn && navigator.share) {
            shareBtn.classList.remove('d-none');
            shareBtn.addEventListener('click', () => {
                navigator.share({
                    title: data.school.name_en + ' - Marksheet',
                    text: data.student.name + ' - ' + data.term.label_bn + ' - Grade ' + data.summary.grade,
                    url: location.href
                }).catch(()=>{});
            });
        }

        // Smooth scroll to result
        document.getElementById('marksheetPrintable')?.scrollIntoView({behavior:'smooth', block:'start'});
    }
})();
</script>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
