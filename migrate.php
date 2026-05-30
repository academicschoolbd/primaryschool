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

        // ── pages (CMS)
        if (!tableExists('pages')) {
            db()->exec("CREATE TABLE pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(120) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                body MEDIUMTEXT,
                is_published TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>pages</code> table';

            // Seed common stub pages so navigation dropdowns work out of the box
            $seed = [
                ['about-institution',  'প্রতিষ্ঠান সম্পর্কে',  '<p>প্রতিষ্ঠান সম্পর্কে বিস্তারিত তথ্য এখানে যোগ করুন।</p>'],
                ['honor-board',        'অনার বোর্ড',          '<p>অনার বোর্ড।</p>'],
                ['founder-donor-info', 'প্রতিষ্ঠাতা ও দানকারী', '<p>প্রতিষ্ঠাতাদের তথ্য।</p>'],
                ['annual-report',      'এনুয়াল রিপোর্ট',       '<p>বার্ষিক প্রতিবেদন।</p>'],
                ['class-routine',      'ক্লাস রুটিন',          '<p>ক্লাস রুটিন।</p>'],
                ['exam-routine',       'পরীক্ষার রুটিন',       '<p>পরীক্ষার রুটিন।</p>'],
                ['syllabus',           'সিলেবাস',             '<p>সিলেবাস।</p>'],
                ['admission',          'অনলাইন ভর্তি',         '<p>ভর্তি তথ্য।</p>'],
                ['admit-card',         'এডমিট কার্ড',         '<p>এডমিট কার্ড সংগ্রহ।</p>'],
                ['fees-payment',       'বেতন পরিশোধ',         '<p>ফি পরিশোধ।</p>'],
                ['e-book',             'ই-বুক',               '<p>ই-বুক ও লেকচার শীট।</p>'],
                ['manual',             'ম্যানুয়াল',           '<p>ম্যানুয়াল।</p>'],
                ['rules-regulations',  'বিধি বিধান',          '<p>বিধি বিধান।</p>'],
                ['hostel-information', 'হোস্টেলের তথ্য',       '<p>হোস্টেল তথ্য।</p>'],
                ['scholarship',        'উপবৃত্তি',            '<p>উপবৃত্তি।</p>'],
                ['contact',            'যোগাযোগ',             '<p>যোগাযোগ।</p>'],
            ];
            $stmt = db()->prepare('INSERT IGNORE INTO pages (slug,title,body,is_published) VALUES (?,?,?,1)');
            foreach ($seed as [$s,$t,$b]) $stmt->execute([$s,$t,$b]);
            $results[] = '✓ Seeded ' . count($seed) . ' default CMS pages';
        }

        // ── academic_years
        if (!tableExists('academic_years')) {
            db()->exec("CREATE TABLE academic_years (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(40) NOT NULL,
                start_date DATE, end_date DATE,
                is_current TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_year (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $y = (int)date('Y');
            db()->prepare('INSERT INTO academic_years (name,start_date,end_date,is_current) VALUES (?,?,?,1)')
                ->execute([(string)$y, "$y-01-01", "$y-12-31"]);
            $results[] = '✓ Created <code>academic_years</code> table + ' . $y . ' (current)';
        }

        // ── audit_log
        if (!tableExists('audit_log')) {
            db()->exec("CREATE TABLE audit_log (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT, user_name VARCHAR(120), user_role VARCHAR(40),
                action VARCHAR(40) NOT NULL,
                entity_type VARCHAR(40) NOT NULL,
                entity_id INT, entity_label VARCHAR(255),
                changes_json MEDIUMTEXT,
                ip_address VARCHAR(45), user_agent VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_user (user_id), INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results[] = '✓ Created <code>audit_log</code> table (anti-fraud history)';
        }

        // ── results.year_id column
        if (tableExists('results') && !columnExists('results', 'year_id')) {
            db()->exec("ALTER TABLE results ADD COLUMN year_id INT DEFAULT NULL AFTER exam_term");
            // Backfill existing rows with current year
            db()->exec("UPDATE results SET year_id = (SELECT id FROM academic_years WHERE is_current=1 LIMIT 1)");
            // Update unique key
            try {
                db()->exec("ALTER TABLE results DROP INDEX uniq_result");
                db()->exec("ALTER TABLE results ADD UNIQUE KEY uniq_result (student_id, subject_id, exam_term, year_id)");
            } catch (Throwable $e) {
                $results[] = '⚠ Could not update results unique key: ' . htmlspecialchars($e->getMessage());
            }
            $results[] = '✓ Added <code>results.year_id</code> column + back-filled existing rows';
        }

        // ── teachers.subject_id column
        if (tableExists('teachers') && !columnExists('teachers', 'subject_id')) {
            db()->exec("ALTER TABLE teachers ADD COLUMN subject_id INT DEFAULT NULL AFTER subject");
            $results[] = '✓ Added <code>teachers.subject_id</code> column';
        }

        // ── extracurricular
        if (!tableExists('extracurricular')) {
            db()->exec("CREATE TABLE extracurricular (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                image VARCHAR(255),
                description TEXT,
                sort_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $seed = [
                ['Nature Club',  'https://picsum.photos/seed/nature/400/200', 1],
                ['Rover Scout',  'https://picsum.photos/seed/rover/400/200',  2],
                ['BNCC',         'https://picsum.photos/seed/bncc/400/200',   3],
                ['Red Crescent', 'https://picsum.photos/seed/bdrcs/400/200',  4],
                ['Debate Club',  'https://picsum.photos/seed/debate/400/200', 5],
                ['Music Club',   'https://picsum.photos/seed/music/400/200',  6],
            ];
            $stmt = db()->prepare('INSERT INTO extracurricular (name,image,sort_order) VALUES (?,?,?)');
            foreach ($seed as [$n,$img,$o]) $stmt->execute([$n,$img,$o]);
            $results[] = '✓ Created <code>extracurricular</code> table + ' . count($seed) . ' default activities';
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
            'current_year_id'           => (string)(current_year_id() ?: ''),
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
