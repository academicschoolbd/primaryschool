<?php
$pageTitle  = 'নোটিশ বোর্ড | Notice Board';
$activeMenu = 'notices';
require_once __DIR__ . '/includes/public_header.php';

$notices = [];
if (db_ok()) {
    $notices = db()->query("SELECT * FROM notices WHERE is_published=1 ORDER BY posted_at DESC")->fetchAll();
}
$bnMonths = bn_months_arr();
?>

<section class="result-hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-icon"><i class="fa fa-clipboard-list"></i></div>
            <h1 class="hero-title">নোটিশ বোর্ড</h1>
            <div class="hero-sub">Notice Board · সর্বশেষ ঘোষণা ও বিজ্ঞপ্তি</div>
            <div class="crumbs">
                <a href="<?= BASE_URL ?>/">হোম</a>
                <i class="fa fa-chevron-right"></i>
                <span>নোটিশ</span>
            </div>
        </div>
    </div>
</section>

<section class="result-search-section">
    <div class="container">
        <div class="t2-card" data-aos="fade-up">
            <div class="t2-card-header">
                <i class="fa fa-bullhorn"></i>
                সকল নোটিশ
                <span class="ms-auto" style="font-size:12px;font-weight:500;background:rgba(255,255,255,.18);padding:2px 10px;border-radius:20px;"><?= bn_num(count($notices)) ?> টি</span>
            </div>
            <div class="t2-card-body">
                <?php if (!$notices): ?>
                    <div class="rs-alert rs-warn">
                        <i class="fa fa-info-circle"></i>
                        <span>এখনো কোনো নোটিশ প্রকাশিত হয়নি।</span>
                    </div>
                <?php else: ?>
                    <ul class="t2-notice-list notice-list-full">
                        <?php foreach ($notices as $n):
                            $ts  = strtotime($n['posted_at']);
                            $day = bn_num(date('d', $ts));
                            $mon = $bnMonths[(int)date('n', $ts) - 1];
                            $yr  = bn_num(date('Y', $ts));
                        ?>
                        <li class="t2-notice-item" id="n<?= (int)$n['id'] ?>">
                            <div class="t2-notice-date">
                                <div class="day"><?= $day ?></div>
                                <div><?= mb_substr($mon, 0, 3) ?></div>
                                <div style="font-size:10px;"><?= $yr ?></div>
                            </div>
                            <div class="t2-notice-info">
                                <a href="#n<?= (int)$n['id'] ?>"><?= e($n['title']) ?></a>
                                <?php if (!empty($n['body'])): ?>
                                <p style="font-size:13px;color:var(--text-muted);margin:6px 0 0;line-height:1.6;">
                                    <?= e($n['body']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
