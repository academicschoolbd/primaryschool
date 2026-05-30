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
                <div style="background:rgba(249,168,37,0.1);border:1px solid rgba(249,168,37,0.3);border-radius:var(--radius-sm);padding:14px 16px;text-align:center;">
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
</script>
</body>
</html>
