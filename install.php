<?php
// One-time installer: creates DB, runs schema, creates default admin user.
// Run by visiting: http://localhost/school-saas/install.php
// Delete this file after installation.

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$messages = [];
$err = null;

try {
    // 1. Connect without DB to create it
    $root = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $messages[] = 'Connected to MySQL server.';

    // 2. Run schema.sql
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    $root->exec($sql);
    $messages[] = 'Schema executed and sample data seeded.';

    // 3. Reconnect to the new database
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 4. Create default admin if not exists
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute(['admin@school.test']);
    if (!$check->fetch()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        $ins->execute(['Super Admin', 'admin@school.test', $hash, 'admin']);
        $messages[] = 'Default admin created: admin@school.test / admin123';
    } else {
        $messages[] = 'Admin user already exists.';
    }
} catch (PDOException $e) {
    $err = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Installer · <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body style="background:#f4f6fb;">
<div style="max-width:560px;margin:80px auto;background:#fff;padding:36px;border-radius:14px;border:1px solid var(--line);">
    <h1 style="margin:0 0 6px;font-size:22px;"><?= APP_NAME ?> Installer</h1>
    <p style="color:var(--muted);margin:0 0 20px;">Set up the MySQL database for your school admin panel.</p>

    <?php if ($err): ?>
        <div style="background:#fef2f2;color:#b91c1c;padding:14px;border-radius:10px;font-size:13px;margin-bottom:16px;">
            <b>Error:</b> <?= htmlspecialchars($err) ?>
            <div style="margin-top:8px;">Check your DB credentials in <code>config/database.php</code>.</div>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <div style="background:#ecfdf5;color:#047857;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:8px;">
                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($m) ?>
            </div>
        <?php endforeach; ?>
        <div style="background:#fff7ed;color:#b45309;padding:12px 14px;border-radius:10px;font-size:13px;margin-top:14px;">
            <b>Important:</b> delete <code>install.php</code> from the project root after installation.
        </div>
        <a href="<?= ADMIN_URL ?>/login.php" class="btn btn-primary" style="margin-top:18px;display:inline-flex;">
            Go to login &rarr;
        </a>
    <?php endif; ?>
</div>
</body>
</html>
