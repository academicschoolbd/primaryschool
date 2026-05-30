<?php $school = public_school(); ?>
</main>

<footer class="t2-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="t2-footer-brand">
                    <img src="<?= e($school['logo']) ?>" alt="<?= e($school['name_en']) ?>">
                    <div class="brand-name">
                        <?= e($school['name_bn']) ?>
                        <small><?= e($school['name_en']) ?></small>
                    </div>
                </div>
                <div class="t2-footer-info">
                    <?php if ($school['address']): ?>
                    <p><i class="fa fa-map-marker-alt"></i><?= e($school['address']) ?></p>
                    <?php endif; ?>
                    <?php if ($school['phone']): ?>
                    <p><i class="fa fa-phone"></i><a href="tel:<?= e($school['phone']) ?>"><?= e($school['phone']) ?></a></p>
                    <?php endif; ?>
                    <?php if ($school['email']): ?>
                    <p><i class="fa fa-envelope"></i><a href="mailto:<?= e($school['email']) ?>"><?= e($school['email']) ?></a></p>
                    <?php endif; ?>
                    <?php if ($school['eiin']): ?>
                    <p><i class="fa fa-id-card"></i>EIIN: <?= e($school['eiin']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-sm-6">
                <h5><i class="fa fa-link"></i>দ্রুত লিঙ্ক</h5>
                <ul>
                    <li><a href="<?= BASE_URL ?>/">হোম</a></li>
                    <li><a href="<?= BASE_URL ?>/notices.php">নোটিশ বোর্ড</a></li>
                    <li><a href="<?= BASE_URL ?>/result.php">ফলাফল</a></li>
                    <li><a href="<?= ADMIN_URL ?>/teachers.php">শিক্ষক</a></li>
                    <li><a href="<?= ADMIN_URL ?>/students.php">শিক্ষার্থী</a></li>
                    <li><a href="<?= BASE_URL ?>/#contact">যোগাযোগ</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <h5><i class="fa fa-star"></i>গুরুত্বপূর্ণ</h5>
                <ul>
                    <li><a href="<?= BASE_URL ?>/#about">প্রতিষ্ঠান সম্পর্কিত</a></li>
                    <li><a href="#">প্রশাসনিক তথ্য</a></li>
                    <li><a href="#">একাডেমিক তথ্য</a></li>
                    <li><a href="#">তথ্যাবলী</a></li>
                    <li><a href="#">ডাউনলোড</a></li>
                    <li><a href="<?= BASE_URL ?>/#gallery">ফটো গ্যালারী</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <?php if ($school['established']): ?>
                <div style="background:rgba(var(--accent-rgb),0.1);border:1px solid rgba(var(--accent-rgb),0.3);border-radius:var(--radius-sm);padding:14px 16px;text-align:center;">
                    <div style="font-size:11px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px;">প্রতিষ্ঠাকাল</div>
                    <div style="font-size:1.6rem;font-weight:800;color:var(--accent);line-height:1.2;"><?= e($school['established']) ?></div>
                </div>
                <?php endif; ?>
                <div style="margin-top:14px;background:rgba(255,255,255,0.06);border-radius:var(--radius-sm);padding:14px 16px;text-align:center;">
                    <div style="font-size:11px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px;">এডমিন প্যানেল</div>
                    <a href="<?= ADMIN_URL ?>/login.php" style="color:var(--accent);font-weight:700;font-size:14px;">
                        <i class="fa fa-sign-in-alt me-1"></i>লগইন
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="t2-footer-bottom">
        <div class="container">
            <p>
                © <?= date('Y') ?> <?= e($school['name_en']) ?>. সর্বস্বত্ব সংরক্ষিত।
                &nbsp;|&nbsp; Powered by <a href="<?= ADMIN_URL ?>/login.php"><?= APP_NAME ?></a>
            </p>
        </div>
    </div>
</footer>

<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="উপরে যান">
    <i class="fa fa-chevron-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 700, once: true, offset: 80 });

document.getElementById('navToggle')?.addEventListener('click', () => {
    document.getElementById('mainNav').classList.toggle('active');
});
document.querySelectorAll('.t2-nav-list .has-drop').forEach(link => {
    link.addEventListener('click', e => {
        if (window.innerWidth <= 991) {
            e.preventDefault();
            link.nextElementSibling?.classList.toggle('show');
        }
    });
});

window.addEventListener('scroll', () => {
    document.getElementById('backToTop')?.classList.toggle('show', window.scrollY > 300);
});

// ============ Site-wide "Coming soon" toast for placeholder links ============
// Catches any <a href="#"> on any public page (homepage, students.php, teachers.php,
// result.php, notices.php) so visitors never see a broken-looking nav item.
function publicToast(msg, type = 'info') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(20px);
        background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;
        padding:12px 22px;border-radius:99px;box-shadow:0 8px 24px rgba(var(--primary-rgb),.35);
        z-index:10000;font-size:14px;font-weight:600;opacity:0;transition:.25s;
        display:flex;align-items:center;gap:8px;max-width:90vw;`;
    t.innerHTML = '<i class="fa fa-info-circle" style="color:var(--accent);"></i><span>' + msg + '</span>';
    document.body.appendChild(t);
    requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateX(-50%) translateY(0)'; });
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}
document.addEventListener('click', function(e) {
    const a = e.target.closest('a[href="#"]');
    if (!a) return;
    if (a.classList.contains('has-drop')) return;          // nav dropdown toggles
    if (a.closest('.t2-marquee-content')) return;           // marquee already-real notice links
    if (a.closest('.t2-dropdown li')) {
        // Dropdown placeholder items inside the public nav — show toast
        e.preventDefault();
        publicToast('শীঘ্রই আসছে — এই ফিচারটি এখনো প্রস্তুত হয়নি।', 'info');
        return;
    }
    e.preventDefault();
    publicToast('শীঘ্রই আসছে — এই ফিচারটি এখনো প্রস্তুত হয়নি।', 'info');
});

// ============ Animated counter on stats ============
function animateCounter(el, target) {
    const bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    const isBn = /[০-৯]/.test(el.textContent);
    const dur = 1200;
    const start = performance.now();
    function tick(now) {
        const t = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - t, 3);
        const v = Math.round(target * eased);
        el.textContent = isBn ? String(v).split('').map(c => /\d/.test(c) ? bnDigits[+c] : c).join('') : v;
        if (t < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}
const statObs = new IntersectionObserver(entries => {
    entries.forEach(en => {
        if (en.isIntersecting) {
            const el = en.target;
            const original = el.textContent.trim();
            const num = Number(original.replace(/[^0-9]/g, '')) || 0;
            if (num > 0) animateCounter(el, num);
            statObs.unobserve(el);
        }
    });
}, { threshold: 0.4 });
document.querySelectorAll('.t2-stat-item .stat-num').forEach(el => statObs.observe(el));

// ============ Scroll reveal staggered ============
const revealObs = new IntersectionObserver(entries => {
    entries.forEach(en => {
        if (en.isIntersecting) {
            en.target.classList.add('in-view');
            revealObs.unobserve(en.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.t2-quick-item, .t2-extra-card, .t2-teacher-card').forEach(el => {
    el.classList.add('scroll-reveal');
    revealObs.observe(el);
});

// ============ Floating notice popup ============
(async function() {
    try {
        const r = await fetch(window.APP.api + '/floating_notice.php');
        const j = await r.json();
        if (!j.ok || !j.data) return;
        const n = j.data;
        const dismissedKey = 'fnDismissed_' + n.id;
        if (localStorage.getItem(dismissedKey) === '1') return;

        const overlay = document.createElement('div');
        overlay.className = 'fn-overlay';
        const safe = (s) => String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        overlay.innerHTML = `
            <div class="fn-modal" role="dialog" aria-modal="true">
                <div class="fn-modal-head">
                    <div class="icon"><i class="fa fa-bell"></i></div>
                    <div>
                        <div class="label">গুরুত্বপূর্ণ নোটিশ</div>
                        <div class="title-bn">${safe(n.title)}</div>
                    </div>
                    <button type="button" class="fn-modal-close" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="fn-modal-body">
                    <div class="meta"><i class="fa fa-calendar-alt"></i> ${safe(n.posted_at_label)}</div>
                    ${n.body ? '<div>' + safe(n.body).replace(/\n/g, '<br>') + '</div>' : ''}
                </div>
                <div class="fn-modal-actions">
                    ${n.pdf_url ? `<a href="${safe(n.pdf_url)}" target="_blank" class="fn-btn fn-btn-primary"><i class="fa fa-file-pdf"></i> PDF ডাউনলোড</a>` : ''}
                    <a href="${window.APP.base}/notices.php#n${n.id}" class="fn-btn fn-btn-primary"><i class="fa fa-arrow-right"></i> সকল নোটিশ</a>
                    <button type="button" class="fn-btn fn-btn-light" data-action="close">পরে দেখব</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        setTimeout(() => overlay.classList.add('show'), 800);

        function close() {
            overlay.classList.remove('show');
            localStorage.setItem(dismissedKey, '1');
            setTimeout(() => overlay.remove(), 300);
        }
        overlay.addEventListener('click', e => {
            if (e.target === overlay) close();
            if (e.target.closest('[data-action=close]')) close();
            if (e.target.closest('.fn-modal-close')) close();
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); }, { once: true });
    } catch (e) { /* silently fail */ }
})();
</script>
</body>
</html>
