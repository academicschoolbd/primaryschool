<?php
// Common helpers used across the admin panel.

function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function flash_set($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function render_flash() {
    $f = flash_get();
    if (!$f) return '';
    $colors = [
        'success' => ['bg' => '#ecfdf5', 'fg' => '#047857', 'icon' => 'bi-check-circle-fill'],
        'error'   => ['bg' => '#fef2f2', 'fg' => '#b91c1c', 'icon' => 'bi-exclamation-circle-fill'],
        'info'    => ['bg' => '#eff6ff', 'fg' => '#1d4ed8', 'icon' => 'bi-info-circle-fill'],
    ];
    $c = $colors[$f['type']] ?? $colors['info'];
    return '<div style="background:' . $c['bg'] . ';color:' . $c['fg']
         . ';padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;">'
         . '<i class="bi ' . $c['icon'] . '"></i> ' . e($f['msg']) . '</div>';
}

function db_ok() {
    return db() instanceof PDO;
}

function db_banner_if_offline() {
    if (db_ok()) return '';
    return '<div style="background:#fff7ed;color:#b45309;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;">'
        . '<i class="bi bi-database-exclamation"></i> '
        . 'Database not connected. Edit <code>config/database.php</code> and run <a href="' . BASE_URL . '/install.php"><b>install.php</b></a>.'
        . '</div>';
}

function calc_grade($percent) {
    if ($percent >= 90) return ['A+', '#047857'];
    if ($percent >= 80) return ['A',  '#10b981'];
    if ($percent >= 70) return ['B+', '#0ea5e9'];
    if ($percent >= 60) return ['B',  '#1d4ed8'];
    if ($percent >= 50) return ['C',  '#b45309'];
    if ($percent >= 33) return ['D',  '#f59e0b'];
    return ['F', '#b91c1c'];
}

function avatar_color($name) {
    $palette = ['#4f46e5','#8b5cf6','#10b981','#0ea5e9','#f59e0b','#ef4444','#ec4899'];
    return $palette[abs(crc32($name)) % count($palette)];
}
