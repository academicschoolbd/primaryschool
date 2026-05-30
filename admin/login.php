<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['user'])) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!db_ok()) {
        $error = 'Database not connected. Run install.php first.';
    } else {
        $stmt = db()->prepare('SELECT id,name,email,password,role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            unset($u['password']);
            $_SESSION['user'] = $u;
            redirect('index.php');
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in · <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<?php theme_styles_inline(); ?>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-side">
        <div>
            <div style="display:flex;align-items:center;gap:10px;font-weight:700;font-size:18px;">
                <div style="width:36px;height:36px;background:rgba(255,255,255,.18);border-radius:9px;display:grid;place-items:center;">E</div>
                <?= APP_NAME ?>
            </div>
        </div>
        <div style="position:relative;z-index:1;">
            <h2>Welcome back to<br><?= APP_NAME ?></h2>
            <p><?= APP_TAGLINE ?>. Manage students, teachers, classes and results from one beautiful dashboard.</p>
            <div class="feat">
                <div class="f"><i class="bi bi-people-fill"></i> Student & teacher records</div>
                <div class="f"><i class="bi bi-clipboard-data-fill"></i> Exam results & grades</div>
                <div class="f"><i class="bi bi-file-earmark-text-fill"></i> Printable marksheets</div>
            </div>
        </div>
        <div style="font-size:12px;opacity:.8;position:relative;z-index:1;">&copy; <?= date('Y') ?> <?= APP_NAME ?></div>
    </div>

    <div class="auth-form">
        <div class="box">
            <h1>Sign in</h1>
            <p class="sub">Enter your credentials to access the admin panel.</p>

            <?php if ($error): ?>
                <div style="background:#fef2f2;color:#b91c1c;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;">
                    <i class="bi bi-exclamation-circle"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="field">
                    <label>Email address</label>
                    <input type="email" name="email" placeholder="admin@school.test" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="row">
                    <label><input type="checkbox"> Remember me</label>
                    <a href="#">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right"></i> Sign in
                </button>
            </form>

            <div class="auth-hint">
                <b>Demo credentials:</b> admin@school.test / admin123
                <br>(Run <a href="<?= BASE_URL ?>/install.php"><b>install.php</b></a> first if you haven't.)
            </div>
        </div>
    </div>
</div>
</body>
</html>
