<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$school = public_school();
$pageTitle  = $pageTitle  ?? $school['name_en'];
$activeMenu = $activeMenu ?? '';

$marqueeNotices = [];
if (db_ok()) {
    try {
        $marqueeNotices = db()->query("SELECT id, title FROM notices WHERE is_published=1 ORDER BY posted_at DESC LIMIT 8")->fetchAll();
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="<?= e(get_setting('theme_primary', '#1a237e')) ?>">
<title><?= e($pageTitle) ?> | <?= e($school['name_bn']) ?></title>
<meta name="description" content="<?= e($school['name_en']) ?> – <?= e($school['address']) ?>">
<link rel="icon" type="image/png" href="<?= e($school['logo']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/public.css">
<?php theme_styles_inline(); ?>
<script>
window.APP = {
    base: <?= json_encode(BASE_URL) ?>,
    api:  <?= json_encode(BASE_URL . '/api') ?>
};
</script>
</head>
<body class="public" data-aos-easing="ease" data-aos-duration="700">

<div class="t2-topbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <?php if ($school['eiin']): ?>
                <span class="eiin-badge"><i class="fa fa-id-badge me-1"></i>EIIN: <?= e($school['eiin']) ?></span>
                <?php endif; ?>
                <?php if ($school['established']): ?>
                <span class="d-none d-sm-inline"><i class="fa fa-calendar-alt me-1 text-warning"></i>প্রতিষ্ঠাকাল: <?= e($school['established']) ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <?php if ($school['phone']): ?>
                <a href="tel:<?= e($school['phone']) ?>" class="d-none d-sm-inline-flex"><i class="fa fa-phone me-1 text-warning"></i><?= e($school['phone']) ?></a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/result.php"><i class="fa fa-certificate me-1 text-warning"></i>ফলাফল</a>
                <a href="<?= ADMIN_URL ?>/login.php"><i class="fa fa-sign-in-alt me-1 text-warning"></i>লগইন</a>
            </div>
        </div>
    </div>
</div>

<header class="t2-header">
    <div class="container">
        <a href="<?= BASE_URL ?>/" class="t2-logo-wrap">
            <img class="t2-logo-img" src="<?= e($school['logo']) ?>" alt="<?= e($school['name_en']) ?>">
            <div class="t2-school-name">
                <div class="name-bn"><?= e($school['name_bn']) ?></div>
                <div class="name-en"><?= e($school['name_en']) ?></div>
                <div class="info-row">
                    <?php if ($school['address']): ?>
                    <span><i class="fa fa-map-marker-alt"></i><?= e($school['address']) ?></span>
                    <?php endif; ?>
                    <?php if ($school['email']): ?>
                    <span><i class="fa fa-envelope"></i><?= e($school['email']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>
</header>

<nav class="t2-navbar">
    <div class="container">
        <div class="t2-nav-inner">
            <a href="<?= BASE_URL ?>/" class="t2-nav-home" title="হোম"><i class="fa fa-home"></i></a>
            <button class="t2-nav-toggle" id="navToggle" aria-label="Toggle navigation"><i class="fa fa-bars"></i></button>
            <ul class="t2-nav-list" id="mainNav">
                <li class="<?= $activeMenu === 'home' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/">হোম</a></li>
                <li>
                    <a href="javascript:void(0);" class="has-drop">প্রতিষ্ঠান <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="<?= BASE_URL ?>/page.php?slug=about-institution">প্রতিষ্ঠান সম্পর্কে</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=honor-board">অনার বোর্ড</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=founder-donor-info">প্রতিষ্ঠাতা ও দানকারী</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=annual-report">এনুয়াল রিপোর্ট</a></li>
                    </ul>
                </li>
                <li class="<?= $activeMenu === 'admin' ? 'active' : '' ?>">
                    <a href="javascript:void(0);" class="has-drop">প্রশাসনিক <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="<?= BASE_URL ?>/teachers.php">শিক্ষক তালিকা</a></li>
                        <li><a href="<?= BASE_URL ?>/students.php">শিক্ষার্থী তালিকা</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=rules-regulations">বিধি বিধান</a></li>
                    </ul>
                </li>
                <li class="<?= $activeMenu === 'academic' ? 'active' : '' ?>">
                    <a href="javascript:void(0);" class="has-drop">একাডেমিক <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="<?= BASE_URL ?>/result.php">পরীক্ষার ফলাফল</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=class-routine">ক্লাস রুটিন</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=exam-routine">পরীক্ষার রুটিন</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=syllabus">সিলেবাস</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=admission">অনলাইন ভর্তি</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=admit-card">এডমিট কার্ড</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0);" class="has-drop">তথ্যাবলী <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="<?= BASE_URL ?>/page.php?slug=fees-payment">বেতন পরিশোধ</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=scholarship">উপবৃত্তি</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=hostel-information">হোস্টেল তথ্য</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0);" class="has-drop">ডাউনলোড <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="<?= BASE_URL ?>/page.php?slug=manual">ম্যানুয়াল</a></li>
                        <li><a href="<?= BASE_URL ?>/page.php?slug=e-book">ই-বুক</a></li>
                    </ul>
                </li>
                <li class="<?= $activeMenu === 'notices' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/notices.php">নোটিশ</a></li>
                <li class="<?= $activeMenu === 'gallery' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/#gallery">ফটো গ্যালারী</a></li>
                <li class="<?= $activeMenu === 'contact' ? 'active' : '' ?>"><a href="<?= BASE_URL ?>/page.php?slug=contact">যোগাযোগ</a></li>
            </ul>
        </div>
    </div>
</nav>

<?php if ($marqueeNotices): ?>
<div class="t2-marquee-bar">
    <div class="container">
        <div class="t2-marquee-wrap">
            <span class="t2-marquee-label"><i class="fa fa-bullhorn me-1"></i>নোটিশ</span>
            <div style="overflow:hidden;flex:1;">
                <div class="t2-marquee-content">
                    <?php for ($i = 0; $i < 2; $i++): foreach ($marqueeNotices as $n): ?>
                        <a href="<?= BASE_URL ?>/notices.php#n<?= (int)$n['id'] ?>">
                            <i class="fa fa-chevron-right me-1" style="font-size:10px;opacity:.6;"></i>
                            <?= e($n['title']) ?>
                        </a>
                    <?php endforeach; endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<main id="main-content">
