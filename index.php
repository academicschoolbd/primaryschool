<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// === Fetch homepage data from MySQL ===
$school = [
    'name_bn'     => 'আদর্শ সরকারি উচ্চ বিদ্যালয়',
    'name_en'     => 'Adarsha Government High School',
    'tagline'     => 'শিক্ষা, সংস্কৃতি ও মূল্যবোধের আলোয় আলোকিত আগামী',
    'address'     => 'School Road, Dhaka, Bangladesh',
    'phone'       => '+880-2-1234567',
    'email'       => 'info@adarshaschool.edu.bd',
    'website'     => '#',
    'eiin'        => '115349',
    'established' => 1955,
    'logo'        => 'https://placehold.co/200x200/1a237e/f9a825?text=School&font=roboto',
    'about_bn'    => 'আমাদের শিক্ষা প্রতিষ্ঠানে আপনাকে স্বাগতম।',
    'map_embed'   => '',
];

$slides = $notices = $messages = $gallery = [];
$stats = [
    'students' => 0, 'teachers' => 0, 'classes' => 0, 'years' => 0,
];

if (db_ok()) {
    if ($row = db()->query('SELECT * FROM school_info ORDER BY id LIMIT 1')->fetch()) {
        $school = array_merge($school, $row);
    }
    $slides   = db()->query("SELECT * FROM sliders WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
    $notices  = db()->query("SELECT * FROM notices WHERE is_published=1 ORDER BY posted_at DESC LIMIT 6")->fetchAll();
    $messages = db()->query("SELECT * FROM school_messages ORDER BY sort_order, id LIMIT 4")->fetchAll();
    $gallery  = db()->query("SELECT * FROM gallery ORDER BY sort_order, id LIMIT 6")->fetchAll();

    $stats['students'] = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $stats['teachers'] = (int) db()->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
    $stats['classes']  = (int) db()->query('SELECT COUNT(*) FROM classes')->fetchColumn();
}
if (!empty($school['established'])) {
    $stats['years'] = (int) date('Y') - (int) $school['established'];
}

// Bengali numerals + month names are defined in includes/functions.php
$bnMonths = bn_months_arr();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($school['name_en']) ?> | <?= e($school['name_bn']) ?></title>
<meta name="description" content="<?= e($school['name_en']) ?> - <?= e($school['address']) ?>">

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
</head>
<body class="public" data-aos-easing="ease" data-aos-duration="700">

<!-- ===== Top Bar ===== -->
<div class="t2-topbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <?php if ($school['eiin']): ?>
                <span class="eiin-badge"><i class="fa fa-id-badge me-1"></i>EIIN: <?= e($school['eiin']) ?></span>
                <?php endif; ?>
                <?php if ($school['established']): ?>
                <span><i class="fa fa-calendar-alt me-1 text-warning"></i>প্রতিষ্ঠাকাল: <?= e($school['established']) ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if ($school['phone']): ?>
                <a href="tel:<?= e($school['phone']) ?>"><i class="fa fa-phone me-1 text-warning"></i><?= e($school['phone']) ?></a>
                <?php endif; ?>
                <a href="<?= ADMIN_URL ?>/login.php"><i class="fa fa-sign-in-alt me-1 text-warning"></i>লগইন</a>
            </div>
        </div>
    </div>
</div>

<!-- ===== Header / Brand ===== -->
<header class="t2-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
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
    </div>
</header>

<!-- ===== Navigation ===== -->
<nav class="t2-navbar">
    <div class="container">
        <div class="t2-nav-inner">
            <a href="<?= BASE_URL ?>/" class="t2-nav-home"><i class="fa fa-home"></i></a>
            <button class="t2-nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <i class="fa fa-bars"></i>
            </button>
            <ul class="t2-nav-list" id="mainNav">
                <li>
                    <a href="javascript:void(0);" class="has-drop">প্রতিষ্ঠান সম্পর্কিত <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="#about">প্রতিষ্ঠান সম্পর্কে</a></li>
                        <li><a href="#">প্রতিষ্ঠাতা ও দানকারী</a></li>
                        <li><a href="#">অনার বোর্ড</a></li>
                        <li><a href="#">এনুয়াল রিপোর্ট</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0);" class="has-drop">প্রশাসনিক তথ্য <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="#">প্রধান শিক্ষকের তালিকা</a></li>
                        <li><a href="#">শিক্ষক-শিক্ষিকার তালিকা</a></li>
                        <li><a href="#">কর্মচারী তালিকা</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0);" class="has-drop">একাডেমিক <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="#">ছাত্র-ছাত্রীদের তালিকা</a></li>
                        <li><a href="#">ক্লাস রুটিন</a></li>
                        <li><a href="#">পরীক্ষার রুটিন</a></li>
                        <li><a href="#">পরীক্ষার ফলাফল</a></li>
                        <li><a href="#">সিলেবাস</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0);" class="has-drop">তথ্যাবলী <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="#">ক্রীড়া ও সাংস্কৃতিক অনুষ্ঠান</a></li>
                        <li><a href="#">উপবৃত্তি</a></li>
                        <li><a href="#">আয়-ব্যয়ের হিসাব</a></li>
                        <li><a href="#">হোস্টেলের তথ্য</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0);" class="has-drop">ডাউনলোড <i class="fa fa-chevron-down"></i></a>
                    <ul class="t2-dropdown">
                        <li><a href="#">ম্যানুয়েল</a></li>
                        <li><a href="#">ই-বুক</a></li>
                        <li><a href="#">লেকচার শীট</a></li>
                        <li><a href="#">ফর্ম</a></li>
                    </ul>
                </li>
                <li><a href="#notices">নোটিশ</a></li>
                <li><a href="#gallery">ফটো গ্যালারী</a></li>
                <li><a href="#contact">যোগাযোগ</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== Marquee Notice Bar ===== -->
<?php if ($notices): ?>
<div class="t2-marquee-bar">
    <div class="container">
        <div class="t2-marquee-wrap">
            <span class="t2-marquee-label"><i class="fa fa-bullhorn me-1"></i>নোটিশ</span>
            <div style="overflow:hidden;flex:1;">
                <div class="t2-marquee-content">
                    <?php for ($i = 0; $i < 2; $i++): // duplicate so loop scrolls smoothly ?>
                        <?php foreach ($notices as $n): ?>
                            <a href="#notices">
                                <i class="fa fa-chevron-right me-1" style="font-size:10px;opacity:.6;"></i>
                                <?= e($n['title']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<main id="main-content">

    <!-- ===== Hero Slider ===== -->
    <section class="t2-hero">
        <div class="swiper t2-hero-swiper">
            <div class="swiper-wrapper">
                <?php if ($slides): foreach ($slides as $s): ?>
                <div class="swiper-slide">
                    <img src="<?= e($s['image']) ?>" alt="<?= e($s['caption']) ?>">
                    <?php if ($s['caption']): ?>
                    <div class="t2-hero-caption"><h2><?= e($s['caption']) ?></h2></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; else: ?>
                <div class="swiper-slide">
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;flex-direction:column;gap:14px;">
                        <i class="fa fa-school" style="font-size:48px;color:var(--accent);"></i>
                        <h2 style="margin:0;"><?= e($school['name_bn']) ?></h2>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </section>

    <!-- ===== Stats Strip ===== -->
    <section class="t2-stats">
        <div class="container">
            <div class="row g-0 text-center">
                <div class="col-6 col-md-3 t2-stat-item">
                    <div class="stat-num"><?= bn_num($stats['students']) ?></div>
                    <div class="stat-label"><i class="fa fa-user-graduate me-1"></i>শিক্ষার্থী</div>
                </div>
                <div class="col-6 col-md-3 t2-stat-item t2-stat-divider">
                    <div class="stat-num"><?= bn_num($stats['teachers']) ?></div>
                    <div class="stat-label"><i class="fa fa-chalkboard-teacher me-1"></i>শিক্ষক</div>
                </div>
                <div class="col-6 col-md-3 t2-stat-item t2-stat-divider">
                    <div class="stat-num"><?= bn_num($stats['classes']) ?></div>
                    <div class="stat-label"><i class="fa fa-school me-1"></i>শ্রেণী</div>
                </div>
                <div class="col-6 col-md-3 t2-stat-item t2-stat-divider">
                    <div class="stat-num"><?= bn_num($stats['years']) ?></div>
                    <div class="stat-label"><i class="fa fa-calendar me-1"></i>বছরের অভিজ্ঞতা</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Main Layout ===== -->
    <section class="t2-main-wrap">
        <div class="container">
            <div class="row g-4">

                <!-- ============ LEFT COLUMN ============ -->
                <div class="col-lg-8">

                    <!-- Quick menu -->
                    <div class="t2-card mb-4" data-aos="fade-up">
                        <div class="t2-card-header"><i class="fa fa-th"></i>দ্রুত মেনু</div>
                        <div class="t2-card-body">
                            <div class="t2-quick-grid">
                                <?php
                                $quick = [
                                    ['url' => '#',                              'icon' => 'fa-credit-card',       'color' => '#2e7d32', 'bg' => 'linear-gradient(135deg,#e8f5e9,#c8e6c9)', 'label' => 'বেতন পরিশোধ'],
                                    ['url' => '#',                              'icon' => 'fa-id-card',           'color' => '#283593', 'bg' => 'linear-gradient(135deg,#e8eaf6,#c5cae9)', 'label' => 'এডমিট কার্ড'],
                                    ['url' => '#',                              'icon' => 'fa-user-plus',         'color' => '#1565c0', 'bg' => 'linear-gradient(135deg,#e3f2fd,#bbdefb)', 'label' => 'অনলাইন ভর্তি'],
                                    ['url' => ADMIN_URL.'/teachers.php',        'icon' => 'fa-chalkboard-teacher','color' => '#e65100', 'bg' => 'linear-gradient(135deg,#fff3e0,#ffe0b2)', 'label' => 'শিক্ষক তালিকা'],
                                    ['url' => ADMIN_URL.'/students.php',        'icon' => 'fa-users',             'color' => '#c62828', 'bg' => 'linear-gradient(135deg,#fce4ec,#f8bbd0)', 'label' => 'শিক্ষার্থী তালিকা'],
                                    ['url' => ADMIN_URL.'/results.php',         'icon' => 'fa-certificate',       'color' => '#00695c', 'bg' => 'linear-gradient(135deg,#e0f7fa,#b2ebf2)', 'label' => 'ফলাফল'],
                                    ['url' => '#',                              'icon' => 'fa-calendar-week',     'color' => '#6a1b9a', 'bg' => 'linear-gradient(135deg,#f3e5f5,#e1bee7)', 'label' => 'ক্লাস রুটিন'],
                                    ['url' => '#',                              'icon' => 'fa-file-alt',          'color' => '#f57f17', 'bg' => 'linear-gradient(135deg,#fff8e1,#ffecb3)', 'label' => 'পরীক্ষার রুটিন'],
                                ];
                                foreach ($quick as $q): ?>
                                <a href="<?= e($q['url']) ?>" class="t2-quick-item">
                                    <div class="icon" style="background: <?= $q['bg'] ?>;">
                                        <i class="fa <?= $q['icon'] ?>" style="color: <?= $q['color'] ?>;"></i>
                                    </div>
                                    <span><?= e($q['label']) ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Notice Board -->
                    <div class="t2-card mb-4" id="notices" data-aos="fade-up" data-aos-delay="100">
                        <div class="t2-card-header" style="justify-content:space-between;">
                            <span><i class="fa fa-clipboard-list"></i>নোটিশ বোর্ড</span>
                            <a href="#" style="font-size:12px;color:var(--accent);font-weight:600;">
                                সকল দেখুন <i class="fa fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="t2-card-body">
                            <?php if ($notices): ?>
                            <ul class="t2-notice-list">
                                <?php foreach ($notices as $n):
                                    $ts = strtotime($n['posted_at']);
                                    $day = bn_num(date('d', $ts));
                                    $mon = $bnMonths[(int)date('n', $ts) - 1];
                                    $yr  = bn_num(date('Y', $ts));
                                ?>
                                <li class="t2-notice-item">
                                    <div class="t2-notice-date">
                                        <div class="day"><?= $day ?></div>
                                        <div><?= mb_substr($mon, 0, 3) ?></div>
                                        <div style="font-size:10px;"><?= $yr ?></div>
                                    </div>
                                    <div class="t2-notice-info">
                                        <a href="#"><?= e($n['title']) ?></a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <div style="text-align:center;color:var(--text-muted);padding:30px;">
                                <i class="fa fa-inbox" style="font-size:32px;"></i>
                                <p style="margin-top:10px;">কোনো নোটিশ নেই।</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Leadership Messages -->
                    <?php if ($messages): ?>
                    <div class="t2-card mb-4" data-aos="fade-up" data-aos-delay="150">
                        <div class="t2-card-header"><i class="fa fa-comment-dots"></i>বাণী ও শুভেচ্ছা</div>
                        <div class="t2-card-body">
                            <div class="row g-3 justify-content-center">
                                <?php foreach ($messages as $m): ?>
                                <div class="col-md-6">
                                    <div class="t2-msg-grid-item">
                                        <?php if ($m['photo']): ?>
                                        <img class="t2-msg-grid-photo" src="<?= e($m['photo']) ?>" alt="<?= e($m['name']) ?>">
                                        <?php endif; ?>
                                        <div class="t2-msg-grid-name"><?= e($m['name']) ?></div>
                                        <div class="t2-msg-grid-desig"><?= e($m['designation']) ?></div>
                                        <div class="t2-msg-grid-text"><?= e($m['content']) ?></div>
                                        <a href="#" class="t2-msg-grid-more">বিস্তারিত পড়ুন</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Important Links / Services -->
                    <div class="t2-card mb-4" data-aos="fade-up" data-aos-delay="150">
                        <div class="t2-card-header"><i class="fa fa-th-list"></i>গুরুত্বপূর্ণ লিঙ্ক ও সেবা</div>
                        <div class="t2-card-body">
                            <div class="row g-3">
                                <?php
                                $services = [
                                    ['head' => 'প্রতিষ্ঠান সম্পর্কিত', 'icon' => 'fa-folder', 'links' => [
                                        ['#', 'প্রতিষ্ঠান সম্পর্কে'],
                                        ['#', 'অনার বোর্ড'],
                                        ['#', 'প্রতিষ্ঠাতা ও দানকারী'],
                                    ]],
                                    ['head' => 'প্রশাসনিক তথ্য', 'icon' => 'fa-folder', 'links' => [
                                        ['#', 'প্রধান শিক্ষকের তালিকা'],
                                        [ADMIN_URL.'/teachers.php', 'শিক্ষক-শিক্ষিকার তালিকা'],
                                        ['#', 'কর্মচারী তালিকা'],
                                    ]],
                                    ['head' => 'একাডেমিক', 'icon' => 'fa-folder', 'links' => [
                                        [ADMIN_URL.'/students.php', 'ছাত্র-ছাত্রীদের তালিকা'],
                                        [ADMIN_URL.'/results.php',  'পরীক্ষার ফলাফল'],
                                        [ADMIN_URL.'/marksheet.php','মার্কশীট'],
                                        ['#', 'ক্লাস রুটিন'],
                                    ]],
                                    ['head' => 'ডাউনলোড', 'icon' => 'fa-folder', 'links' => [
                                        ['#', 'ম্যানুয়েল'],
                                        ['#', 'ই-বুক'],
                                        ['#', 'লেকচার শীট'],
                                        ['#', 'বুক লিস্ট'],
                                    ]],
                                ];
                                foreach ($services as $sv): ?>
                                <div class="col-md-6">
                                    <div class="t2-service-box">
                                        <div class="t2-service-box-head">
                                            <div style="width:36px;height:36px;background:var(--bg-section);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                                <i class="fa <?= $sv['icon'] ?>" style="color:var(--primary);"></i>
                                            </div>
                                            <h6><?= e($sv['head']) ?></h6>
                                        </div>
                                        <div class="t2-service-box-links">
                                            <?php foreach ($sv['links'] as [$url, $label]): ?>
                                            <a href="<?= e($url) ?>"><?= e($label) ?></a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Gallery -->
                    <?php if ($gallery): ?>
                    <div class="t2-card mb-4" id="gallery" data-aos="fade-up" data-aos-delay="200">
                        <div class="t2-card-header"><i class="fa fa-camera-retro"></i>ছবি গ্যালারী</div>
                        <div class="t2-card-body">
                            <div class="row g-4">
                                <?php foreach ($gallery as $g): ?>
                                <div class="col-md-4 col-6">
                                    <a href="<?= e($g['image']) ?>" class="t2-gallery-box" target="_blank">
                                        <img src="<?= e($g['image']) ?>" alt="<?= e($g['caption']) ?>">
                                        <?php if ($g['caption']): ?>
                                        <div class="t2-gallery-caption"><?= e($g['caption']) ?></div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Extracurricular -->
                    <div class="t2-card mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="t2-card-header"><i class="fa fa-star"></i>সহ-পাঠক্রমিক কার্যক্রম</div>
                        <div class="t2-card-body">
                            <div class="t2-extra-grid">
                                <?php
                                $extras = [
                                    ['Nature Club',  'https://picsum.photos/seed/nature/400/200'],
                                    ['Rover Scout',  'https://picsum.photos/seed/rover/400/200'],
                                    ['BNCC',         'https://picsum.photos/seed/bncc/400/200'],
                                    ['Red Crescent', 'https://picsum.photos/seed/bdrcs/400/200'],
                                    ['Debate Club',  'https://picsum.photos/seed/debate/400/200'],
                                    ['Music Club',   'https://picsum.photos/seed/music/400/200'],
                                ];
                                foreach ($extras as [$lbl, $img]): ?>
                                <div class="t2-extra-card">
                                    <img src="<?= e($img) ?>" alt="<?= e($lbl) ?>">
                                    <p><?= e($lbl) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="t2-card mb-4" id="contact" data-aos="fade-up" data-aos-delay="250">
                        <div class="t2-card-header"><i class="fa fa-map-marker-alt"></i>প্রতিষ্ঠানের অবস্থান</div>
                        <div class="t2-card-body p-0" style="overflow:hidden;border-radius:0 0 var(--radius-md) var(--radius-md);">
                            <?php if (!empty($school['map_embed'])): ?>
                            <iframe src="<?= e($school['map_embed']) ?>" width="100%" height="380" style="border:0;" allowfullscreen loading="lazy"></iframe>
                            <?php else: ?>
                            <div style="padding:40px;text-align:center;color:var(--text-muted);">
                                <i class="fa fa-map" style="font-size:36px;"></i>
                                <p style="margin-top:10px;"><?= e($school['address']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- ============ RIGHT SIDEBAR ============ -->
                <div class="col-lg-4">

                    <div class="t2-sidebar-widget" id="about" data-aos="fade-left">
                        <div class="t2-widget-head"><i class="fa fa-university"></i>প্রতিষ্ঠান সম্পর্কে</div>
                        <div class="t2-widget-body text-center">
                            <img src="<?= e($school['logo']) ?>" alt="Logo" style="width:80px;margin-bottom:12px;">
                            <h6 style="color:var(--primary);font-weight:700;margin-bottom:5px;"><?= e($school['name_bn']) ?></h6>
                            <p style="font-size:12px;color:var(--text-muted);line-height:1.5;"><?= e($school['address']) ?></p>
                            <div style="text-align:justify;font-size:13px;color:var(--text-dark);margin-top:10px;line-height:1.6;">
                                <?= e($school['about_bn']) ?>
                            </div>
                        </div>
                    </div>

                    <div class="t2-sidebar-widget" data-aos="fade-left" data-aos-delay="100">
                        <div class="t2-widget-head"><i class="fa fa-calendar-alt"></i>ক্যালেন্ডার</div>
                        <div class="t2-widget-body">
                            <div class="t2-calendar">
                                <div class="t2-cal-header">
                                    <button type="button" onclick="t2CalPrev()"><i class="fa fa-chevron-left"></i></button>
                                    <h6 id="t2-cal-title"></h6>
                                    <button type="button" onclick="t2CalNext()"><i class="fa fa-chevron-right"></i></button>
                                </div>
                                <table>
                                    <thead>
                                        <tr><th>রবি</th><th>সোম</th><th>মঙ্গল</th><th>বুধ</th><th>বৃহঃ</th><th>শুক্র</th><th>শনি</th></tr>
                                    </thead>
                                    <tbody id="t2-cal-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="t2-sidebar-widget" data-aos="fade-left" data-aos-delay="200">
                        <div class="t2-widget-head"><i class="fa fa-link"></i>গুরুত্বপূর্ণ লিঙ্ক</div>
                        <div class="t2-widget-body">
                            <div class="t2-hotline-items">
                                <a href="<?= ADMIN_URL ?>/login.php" class="t2-hotline-item" style="background:#eef0fb;border-left-color:#3949ab;">
                                    <i class="fa fa-id-card" style="color:#3949ab;"></i>
                                    <span style="color:#1a237e;font-weight:700;">এডমিন প্যানেল লগইন</span>
                                    <i class="fa fa-external-link-alt ms-auto" style="font-size:12px;color:var(--text-muted);"></i>
                                </a>
                                <?php
                                $hotlines = [
                                    ['http://www.moedu.gov.bd/',     'শিক্ষা মন্ত্রণালয়'],
                                    ['http://www.shed.gov.bd/',      'মাধ্যমিক ও উচ্চ শিক্ষা বিভাগ'],
                                    ['http://www.dshe.gov.bd/',      'মাধ্যমিক ও উচ্চশিক্ষা অধিদপ্তর'],
                                    ['http://emis.gov.bd/emis',      'ইএমআইএস'],
                                    ['http://www.banbeis.gov.bd/',   'ব্যানবেইস'],
                                    ['https://www.teachers.gov.bd/', 'শিক্ষক বাতায়ন'],
                                    ['https://muktopaath.gov.bd/',   'মুক্তপাঠ'],
                                ];
                                foreach ($hotlines as [$href, $lbl]): ?>
                                <a href="<?= e($href) ?>" target="_blank" class="t2-hotline-item">
                                    <i class="fa fa-chevron-right" style="font-size:10px;"></i>
                                    <span><?= e($lbl) ?></span>
                                    <i class="fa fa-external-link-alt ms-auto" style="font-size:12px;color:var(--text-muted);"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="t2-sidebar-widget" data-aos="fade-left" data-aos-delay="250">
                        <div class="t2-widget-head"><i class="fa fa-music"></i>জাতীয় সংগীত</div>
                        <div class="t2-widget-body">
                            <audio controls style="width:100%;border-radius:var(--radius-sm);">
                                <source src="https://upload.wikimedia.org/wikipedia/commons/2/2b/Amar_Sonar_Bangla_instrumental_by_US_Navy_Band.oga" type="audio/ogg">
                                Your browser does not support audio.
                            </audio>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

</main>

<!-- ===== Footer ===== -->
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
                    <li><a href="#notices">নোটিশ বোর্ড</a></li>
                    <li><a href="<?= ADMIN_URL ?>/teachers.php">শিক্ষক তালিকা</a></li>
                    <li><a href="<?= ADMIN_URL ?>/students.php">শিক্ষার্থী তালিকা</a></li>
                    <li><a href="<?= ADMIN_URL ?>/results.php">পরীক্ষার ফলাফল</a></li>
                    <li><a href="#contact">যোগাযোগ</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <h5><i class="fa fa-star"></i>গুরুত্বপূর্ণ</h5>
                <ul>
                    <li><a href="#about">প্রতিষ্ঠান সম্পর্কিত</a></li>
                    <li><a href="#">প্রশাসনিক তথ্য</a></li>
                    <li><a href="#">একাডেমিক তথ্য</a></li>
                    <li><a href="#">তথ্যাবলী</a></li>
                    <li><a href="#">ডাউনলোড</a></li>
                    <li><a href="#gallery">ফটো গ্যালারী</a></li>
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
                    <a href="<?= ADMIN_URL ?>/login.php" style="color:var(--accent);font-weight:700;font-size:14px;text-decoration:none;">
                        <i class="fa fa-sign-in-alt me-1"></i>লগইন করুন
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
// AOS animations
AOS.init({ duration: 700, once: true, offset: 80 });

// Hero swiper
new Swiper('.t2-hero-swiper', {
    loop: true,
    autoplay: { delay: 4500, disableOnInteraction: false },
    pagination: { el: '.swiper-pagination', clickable: true },
    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    effect: 'fade',
    fadeEffect: { crossFade: true }
});

// Mobile nav
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

// Back to top
window.addEventListener('scroll', () => {
    document.getElementById('backToTop')?.classList.toggle('show', window.scrollY > 300);
});

// Bengali calendar
const t2BnMonths  = <?= json_encode($bnMonths) ?>;
const t2BnDigits  = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
const bnDigit = n => String(n).split('').map(d => /\d/.test(d) ? t2BnDigits[+d] : d).join('');
let t2Year  = new Date().getFullYear();
let t2Month = new Date().getMonth();
function t2RenderCal() {
    const today = new Date();
    const firstDay    = new Date(t2Year, t2Month, 1).getDay();
    const daysInMonth = new Date(t2Year, t2Month + 1, 0).getDate();
    document.getElementById('t2-cal-title').textContent = t2BnMonths[t2Month] + ' ' + bnDigit(t2Year);
    let html = '', day = 1;
    for (let i = 0; i < 6; i++) {
        html += '<tr>';
        for (let j = 0; j < 7; j++) {
            if ((i === 0 && j < firstDay) || day > daysInMonth) {
                html += '<td class="empty"></td>';
            } else {
                let cls = '';
                if (j === 5) cls += ' friday';
                if (day === today.getDate() && t2Month === today.getMonth() && t2Year === today.getFullYear()) cls += ' today';
                html += `<td class="${cls}">${bnDigit(day)}</td>`;
                day++;
            }
        }
        html += '</tr>';
        if (day > daysInMonth) break;
    }
    document.getElementById('t2-cal-body').innerHTML = html;
}
function t2CalPrev(){ t2Month--; if(t2Month<0){t2Month=11;t2Year--;} t2RenderCal(); }
function t2CalNext(){ t2Month++; if(t2Month>11){t2Month=0;t2Year++;} t2RenderCal(); }
t2RenderCal();
</script>
</body>
</html>
