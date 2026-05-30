<?php
require_once __DIR__ . '/auth.php';
$current = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'Dashboard';
$user = current_user();
$initials = strtoupper(substr($user['name'] ?? 'A', 0, 1));
$isAdmin = ($user['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<?php theme_styles_inline(); ?>
<script>
window.APP = {
    base: <?= json_encode(BASE_URL) ?>,
    admin: <?= json_encode(ADMIN_URL) ?>,
    api:   <?= json_encode(BASE_URL . '/api') ?>
};
</script>
</head>
<body>
<div class="toast-host" id="toastHost"></div>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo">E</div>
            <div>
                <div><?= APP_NAME ?></div>
                <div style="font-size:11px;opacity:.6;font-weight:400;">Admin Panel</div>
            </div>
        </div>

        <div class="menu-label">Main</div>
        <nav>
            <a href="index.php" class="<?= $current === 'index' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        </nav>

        <div class="menu-label">Manage</div>
        <nav>
            <a href="teachers.php" class="<?= $current === 'teachers' ? 'active' : '' ?>">
                <i class="bi bi-person-workspace"></i> Teachers
            </a>
            <a href="students.php" class="<?= $current === 'students' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i> Students
            </a>
            <a href="classes.php" class="<?= $current === 'classes' ? 'active' : '' ?>">
                <i class="bi bi-bookmark-star-fill"></i> Classes
            </a>
        </nav>

        <div class="menu-label">Academic</div>
        <nav>
            <a href="results.php" class="<?= $current === 'results' ? 'active' : '' ?>">
                <i class="bi bi-clipboard-data-fill"></i> Results
            </a>
            <a href="marksheet.php" class="<?= $current === 'marksheet' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text-fill"></i> Marksheet
            </a>
        </nav>

        <?php if ($isAdmin): ?>
        <div class="menu-label">System</div>
        <nav>
            <a href="settings.php" class="<?= $current === 'settings' ? 'active' : '' ?>">
                <i class="bi bi-palette-fill"></i> Theme &amp; Settings
            </a>
            <a href="<?= BASE_URL ?>/" target="_blank">
                <i class="bi bi-globe2"></i> View Public Site
            </a>
        </nav>
        <?php endif; ?>

        <div class="foot">
            v1.1 · &copy; <?= date('Y') ?> <?= APP_NAME ?>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search students, teachers, classes...">
            </div>
            <div class="actions">
                <a href="#" class="icon-btn" title="Notifications">
                    <i class="bi bi-bell"></i><span class="dot"></span>
                </a>
                <a href="#" class="icon-btn" title="Messages">
                    <i class="bi bi-chat-left-dots"></i>
                </a>
                <div class="user">
                    <div class="avatar"><?= e($initials) ?></div>
                    <div class="meta">
                        <b><?= e($user['name']) ?></b>
                        <span><?= e(ucfirst($user['role'])) ?></span>
                    </div>
                    <a href="logout.php" class="icon-btn" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </header>
        <main class="content">
