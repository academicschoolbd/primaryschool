<?php
/**
 * migrate.php — Non-destructive database upgrade.
 *
 * Adds any tables / columns / settings rows that newer code depends on,
 * without touching existing data. Safe to re-run.
 *
 * Usage:  http://localhost/primaryschool/migrate.php
 *         (Delete the file after a clean run.)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$results = [];
$err     = null;

function tableExists($name) {
    $stmt = db()->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$name]);
    return (bool) $stmt->fetchColumn();
}
function columnExists($table, $col) {
    $stmt = db()->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$col]);
    return (bool) $stmt->fetchColumn();
}

if (!db_ok()) {
    $err = 'Could not connect to MySQL — check config/database.php';
} else {
    try {
        // ── settings table (the one that crashed for the user)
        if (!tableExists('settings')) {
            db()->exec("CREATE TABLE settings (
                `key`   VARCHAR(64) PRIMARY KEY,
                `value` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>settings</code> table';
        } else {
            $results[] = '• <code>settings</code> table already exists';
        }

        // ── school_info
        if (!tableExists('school_info')) {
            db()->exec("CREATE TABLE school_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name_bn VARCHAR(255), name_en VARCHAR(255), tagline VARCHAR(255),
                address VARCHAR(255), phone VARCHAR(50), email VARCHAR(120),
                website VARCHAR(120), eiin VARCHAR(20), established YEAR,
                logo VARCHAR(255), about_bn TEXT, map_embed TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>school_info</code> table';
        }

        // ── notices: create or add missing cols
        if (!tableExists('notices')) {
            db()->exec("CREATE TABLE notices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL, body TEXT,
                pdf_url VARCHAR(255) DEFAULT NULL,
                pdf_size INT DEFAULT NULL,
                is_floating TINYINT(1) DEFAULT 0,
                is_published TINYINT(1) DEFAULT 1,
                posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>notices</code> table';
        } else {
            foreach ([
                'pdf_url'     => 'VARCHAR(255) DEFAULT NULL',
                'pdf_size'    => 'INT DEFAULT NULL',
                'is_floating' => 'TINYINT(1) DEFAULT 0',
            ] as $col => $def) {
                if (!columnExists('notices', $col)) {
                    db()->exec("ALTER TABLE notices ADD COLUMN $col $def");
                    $results[] = "✓ Added <code>notices.$col</code> column";
                }
            }
        }

        // ── school_messages, gallery, sliders
        if (!tableExists('school_messages')) {
            db()->exec("CREATE TABLE school_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120), designation VARCHAR(120),
                photo VARCHAR(255), content TEXT, sort_order INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>school_messages</code> table';
        }
        if (!tableExists('gallery')) {
            db()->exec("CREATE TABLE gallery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image VARCHAR(255), caption VARCHAR(255), sort_order INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>gallery</code> table';
        }
        if (!tableExists('sliders')) {
            db()->exec("CREATE TABLE sliders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image VARCHAR(255), caption VARCHAR(255),
                is_active TINYINT(1) DEFAULT 1, sort_order INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>sliders</code> table';
        }

        // ── teachers: designation + photo
        if (tableExists('teachers')) {
            if (!columnExists('teachers', 'designation')) {
                db()->exec("ALTER TABLE teachers ADD COLUMN designation VARCHAR(120) DEFAULT NULL AFTER subject");
                $results[] = '✓ Added <code>teachers.designation</code> column';
            }
            if (!columnExists('teachers', 'photo')) {
                db()->exec("ALTER TABLE teachers ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER designation");
                $results[] = '✓ Added <code>teachers.photo</code> column';
            }
        }

        // ── students: photo
        if (tableExists('students') && !columnExists('students', 'photo')) {
            db()->exec("ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER address");
            $results[] = '✓ Added <code>students.photo</code> column';
        }

        // ── results: UNIQUE index for upserts
        if (tableExists('results')) {
            $stmt = db()->query("SHOW INDEX FROM results WHERE Key_name = 'uniq_result'");
            if (!$stmt->fetch()) {
                try {
                    db()->exec("ALTER TABLE results ADD UNIQUE KEY uniq_result (student_id, subject_id, exam_term)");
                    $results[] = '✓ Added <code>results.uniq_result</code> unique key';
                } catch (Throwable $e) {
                    $results[] = '⚠ Skipped <code>results.uniq_result</code> — duplicate rows in table prevent the index. Clean them up first.';
                }
            }
        }

        // ── Seed default settings rows (only inserts what's missing)
        $defaults = [
            'theme_primary'             => '#1a237e',
            'theme_accent'              => '#f9a825',
            'floating_notice_enabled'   => '1',
            'home_show_hero'            => '1',
            'home_show_stats'           => '1',
            'home_show_quick_menu'      => '1',
            'home_show_notices'         => '1',
            'home_show_messages'        => '1',
            'home_show_services'        => '1',
            'home_show_gallery'         => '1',
            'home_show_extras'          => '1',
            'home_show_map'             => '1',
            'home_show_about_widget'    => '1',
            'home_show_calendar'        => '1',
            'home_show_anthem'          => '1',
            'home_show_links'           => '1',
        ];
        $ins = db()->prepare('INSERT IGNORE INTO settings (`key`,`value`) VALUES (?,?)');
        $added = 0;
        foreach ($defaults as $k => $v) {
            $ins->execute([$k, $v]);
            if ($ins->rowCount() > 0) $added++;
        }
        if ($added > 0) {
            $results[] = "✓ Inserted $added default setting row(s)";
        } else {
            $results[] = '• All default settings already present';
        }

        // ── upload directories
        foreach (['assets/uploads/teachers', 'assets/uploads/notices'] as $dir) {
            $abs = __DIR__ . '/' . $dir;
            if (!is_dir($abs)) {
                if (@mkdir($abs, 0775, true)) {
                    $results[] = "✓ Created upload directory <code>$dir/</code>";
                } else {
                    $results[] = "⚠ Could not create <code>$dir/</code> — check write permissions";
                }
            }
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migrate · <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<?php theme_styles_inline(); ?>
</head>
<body style="background:#f4f6fb;">
<div style="max-width:680px;margin:60px auto;background:#fff;padding:36px;border-radius:14px;border:1px solid var(--line);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
        <div style="width:44px;height:44px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:var(--accent);display:grid;place-items:center;font-size:22px;">
            <i class="bi bi-arrow-up-circle-fill"></i>
        </div>
        <div>
            <h1 style="margin:0;font-size:22px;color:var(--primary);">Database Migration</h1>
            <div style="color:var(--muted);font-size:13px;">Non-destructive — adds missing tables/columns/settings only.</div>
        </div>
    </div>

    <?php if ($err): ?>
        <div style="background:#fef2f2;color:#b91c1c;padding:14px;border-radius:10px;font-size:13px;margin-bottom:16px;">
            <b><i class="bi bi-exclamation-triangle-fill"></i> Migration aborted:</b><br>
            <?= htmlspecialchars($err) ?>
        </div>
    <?php else: ?>
        <?php foreach ($results as $r):
            $isOk   = strpos($r, '✓') === 0;
            $isWarn = strpos($r, '⚠') === 0;
            $bg = $isOk ? '#ecfdf5' : ($isWarn ? '#fff7ed' : '#f1f5f9');
            $fg = $isOk ? '#047857' : ($isWarn ? '#b45309' : '#475569');
        ?>
            <div style="background:<?= $bg ?>;color:<?= $fg ?>;padding:9px 14px;border-radius:8px;font-size:13px;margin-bottom:6px;"><?= $r ?></div>
        <?php endforeach; ?>

        <div style="background:rgba(var(--accent-rgb),.12);color:var(--accent-dark);padding:12px 14px;border-radius:10px;font-size:13px;margin-top:16px;border:1px solid rgba(var(--accent-rgb),.3);">
            <b><i class="bi bi-shield-check"></i> Done.</b>
            Your existing teacher/student/result data was <b>not</b> touched. For security, <b>delete <code>migrate.php</code></b> from your project root after a successful run.
        </div>

        <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="<?= ADMIN_URL ?>/settings.php" class="btn btn-primary"><i class="bi bi-palette-fill"></i> Try Theme Save</a>
            <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-light"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
            <a href="<?= BASE_URL ?>/" class="btn btn-light"><i class="bi bi-globe2"></i> Public Site</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
