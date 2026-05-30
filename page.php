<?php
/**
 * Public CMS page renderer.
 * Usage:  /page.php?slug=about-institution
 *
 * Looks up the slug in the `pages` table; renders title + sanitized body.
 * Any nav-dropdown link points here, so the super admin can fill in any
 * "missing" page from the admin panel without writing PHP.
 */
require_once __DIR__ . '/includes/public_header.php';

$slug = trim($_GET['slug'] ?? '');
$page = null;

if ($slug !== '' && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
}

$pageTitle = $page ? ($page['title']) : 'Page Not Found';

if (!$page) {
    http_response_code(404);
}
?>

<div class="t2-page-hero">
    <div class="container">
        <h1>
            <i class="fa <?= $page ? 'fa-file-lines' : 'fa-circle-exclamation' ?> me-2" style="color:var(--accent);"></i>
            <?= e($page ? $page['title'] : 'পৃষ্ঠা পাওয়া যায়নি') ?>
        </h1>
        <div class="t2-breadcrumb">
            <a href="<?= BASE_URL ?>/"><i class="fa fa-home"></i> হোম</a>
            <i class="fa fa-chevron-right"></i>
            <span><?= e($page ? $page['title'] : '404') ?></span>
        </div>
    </div>
</div>

<section class="cms-page-section">
    <div class="container">
        <?php if ($page): ?>
        <article class="cms-page-card" data-aos="fade-up">
            <div class="cms-page-meta">
                <i class="fa fa-calendar-alt"></i>
                <span>সর্বশেষ আপডেট: <?= e(date('M d, Y', strtotime($page['updated_at']))) ?></span>
                <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                <a href="<?= ADMIN_URL ?>/pages.php?action=edit&id=<?= (int)$page['id'] ?>" class="cms-edit-link" title="Edit in admin panel">
                    <i class="fa fa-pen"></i> এডিট করুন
                </a>
                <?php endif; ?>
            </div>
            <div class="cms-page-body">
                <?= sanitize_html($page['body']) ?>
            </div>
        </article>
        <?php else: ?>
        <div class="cms-404">
            <div class="icon"><i class="fa fa-magnifying-glass"></i></div>
            <h3>পৃষ্ঠাটি খুঁজে পাওয়া যায়নি</h3>
            <p>আপনি যে লিঙ্কে ক্লিক করেছেন সেটি বাতিল হয়ে গেছে অথবা পৃষ্ঠাটি প্রকাশিত নয়।</p>
            <p style="font-size:13px;color:var(--text-muted);">খুঁজছিলেন: <code><?= e($slug ?: '(empty)') ?></code></p>
            <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
                <a href="<?= BASE_URL ?>/" class="btn-primary-custom"><i class="fa fa-home me-1"></i> হোমে ফিরুন</a>
                <a href="<?= BASE_URL ?>/notices.php" class="btn-accent-custom"><i class="fa fa-clipboard-list me-1"></i> নোটিশ বোর্ড</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
