<?php
require_once __DIR__ . '/auth.php';
$current = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'Dashboard';
$user = current_user();
$initials = strtoupper(substr($user['name'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> · <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo">E</div>
            <div>
                <div><?= APP_NAME ?></div>
                <div style="font-size:11px;color:#64748b;font-weight:400;">Admin Panel</div>
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

        <div class="foot">
            v1.0 · &copy; <?= date('Y') ?> <?= APP_NAME ?>
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
                    <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="meta">
                        <b><?= htmlspecialchars($user['name']) ?></b>
                        <span><?= htmlspecialchars(ucfirst($user['role'])) ?></span>
                    </div>
                    <a href="logout.php" class="icon-btn" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </header>
        <main class="content">
