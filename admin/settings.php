<?php
$pageTitle = 'Theme & Settings';
require_once __DIR__ . '/../includes/header.php';

// Super-admin guard
$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    flash_set('error', 'Only administrators can change settings.');
    redirect('index.php');
}

// === Handle school-info save (non-AJAX, submits to same page) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'school_info' && db_ok()) {
    $fields = ['name_bn','name_en','tagline','address','phone','email','website','eiin','established','logo','about_bn','map_embed'];
    $vals = [];
    foreach ($fields as $f) $vals[$f] = trim($_POST[$f] ?? '');

    // Upsert single row
    $exists = db()->query('SELECT id FROM school_info LIMIT 1')->fetchColumn();
    try {
        if ($exists) {
            $set = implode(',', array_map(fn($f) => "`$f`=?", $fields));
            $sql = "UPDATE school_info SET $set WHERE id = ?";
            db()->prepare($sql)->execute([...array_values($vals), (int)$exists]);
        } else {
            $cols = '`' . implode('`,`', $fields) . '`';
            $ph   = implode(',', array_fill(0, count($fields), '?'));
            db()->prepare("INSERT INTO school_info ($cols) VALUES ($ph)")->execute(array_values($vals));
        }
        flash_set('success', 'School info updated.');
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('settings.php');
}

// === Load current values ===
$theme_primary = get_setting('theme_primary', '#1a237e');
$theme_accent  = get_setting('theme_accent',  '#f9a825');

$school = [
    'name_bn'=>'','name_en'=>'','tagline'=>'','address'=>'','phone'=>'','email'=>'',
    'website'=>'','eiin'=>'','established'=>'','logo'=>'','about_bn'=>'','map_embed'=>'',
];
if (db_ok()) {
    if ($row = db()->query('SELECT * FROM school_info ORDER BY id LIMIT 1')->fetch()) {
        $school = array_merge($school, $row);
    }
}

// Predefined theme presets
$presets = [
    ['name' => 'Default Blue & Gold', 'primary' => '#1a237e', 'accent' => '#f9a825'],
    ['name' => 'Royal Purple',         'primary' => '#4a148c', 'accent' => '#ffa726'],
    ['name' => 'Forest Green',         'primary' => '#1b5e20', 'accent' => '#fdd835'],
    ['name' => 'Maroon & Cream',       'primary' => '#880e4f', 'accent' => '#ffeb3b'],
    ['name' => 'Ocean Teal',           'primary' => '#00695c', 'accent' => '#ffb300'],
    ['name' => 'Crimson',              'primary' => '#b71c1c', 'accent' => '#ffd54f'],
    ['name' => 'Slate Indigo',         'primary' => '#283593', 'accent' => '#26c6da'],
    ['name' => 'Pink Coral',           'primary' => '#ad1457', 'accent' => '#ff7043'],
];
?>

<div class="page-head">
    <div>
        <h1>Theme &amp; Settings</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Settings</div>
    </div>
    <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-light"><i class="bi bi-eye"></i> Preview Public Site</a>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="row-2">

    <!-- ============ Theme Customiser (AJAX) ============ -->
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-h">
                <h3><i class="bi bi-palette-fill"></i> Theme Colors</h3>
                <span class="badge badge-info"><i class="bi bi-lightning-fill"></i> Live preview</span>
            </div>
            <p style="color:var(--muted);font-size:13px;margin-bottom:18px;">
                Pick two colors. Light/dark variants are generated automatically. Changes preview instantly across
                the entire admin and public site, then save via AJAX (no page reload).
            </p>

            <form id="themeForm">
                <div class="swatch-row">
                    <div id="chipPrimary" class="swatch-chip" style="background:<?= e($theme_primary) ?>;"></div>
                    <div class="meta" style="flex:1;">
                        <b>Primary color</b>
                        <div>Sidebar, headings, primary buttons, links</div>
                    </div>
                    <div class="field" style="margin:0;">
                        <input type="color" name="theme_primary" value="<?= e($theme_primary) ?>">
                    </div>
                </div>

                <div class="swatch-row">
                    <div id="chipAccent" class="swatch-chip" style="background:<?= e($theme_accent) ?>;"></div>
                    <div class="meta" style="flex:1;">
                        <b>Accent color</b>
                        <div>Highlights, gold logo, active nav border, hover states</div>
                    </div>
                    <div class="field" style="margin:0;">
                        <input type="color" name="theme_accent" value="<?= e($theme_accent) ?>">
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <h4 style="font-size:14px;color:var(--primary);margin-bottom:12px;">
                        <i class="bi bi-magic"></i> Quick presets
                    </h4>
                    <div class="preset-grid">
                        <?php foreach ($presets as $p):
                            $isActive = (strtolower($p['primary']) === strtolower($theme_primary)
                                      && strtolower($p['accent'])  === strtolower($theme_accent));
                        ?>
                        <div class="preset <?= $isActive ? 'active' : '' ?>"
                             data-primary="<?= e($p['primary']) ?>"
                             data-accent="<?= e($p['accent']) ?>">
                            <div class="swatches">
                                <span style="background:<?= e($p['primary']) ?>;"></span>
                                <span style="background:<?= e($p['accent']) ?>;"></span>
                            </div>
                            <div class="nm"><?= e($p['name']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="margin-top:24px;display:flex;gap:10px;align-items:center;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Theme
                    </button>
                    <button type="button" class="btn btn-light" onclick="
                        document.querySelector('[name=theme_primary]').value='#1a237e';
                        document.querySelector('[name=theme_accent]').value='#f9a825';
                        applyTheme('#1a237e','#f9a825');
                    ">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset to default
                    </button>
                    <span style="color:var(--muted);font-size:12px;margin-left:auto;">
                        <i class="bi bi-info-circle"></i> Saved settings apply to every visitor.
                    </span>
                </div>
            </form>
        </div>

        <!-- ============ School Info ============ -->
        <div class="card">
            <div class="card-h"><h3><i class="bi bi-building"></i> School Information</h3></div>
            <p style="color:var(--muted);font-size:13px;margin-bottom:18px;">
                These details appear in the public homepage header, footer, and metadata.
            </p>
            <form method="post">
                <input type="hidden" name="form" value="school_info">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="field">
                        <label>School name (Bangla)</label>
                        <input type="text" name="name_bn" value="<?= e($school['name_bn']) ?>">
                    </div>
                    <div class="field">
                        <label>School name (English)</label>
                        <input type="text" name="name_en" value="<?= e($school['name_en']) ?>">
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label>Tagline</label>
                        <input type="text" name="tagline" value="<?= e($school['tagline']) ?>">
                    </div>
                    <div class="field">
                        <label>EIIN</label>
                        <input type="text" name="eiin" value="<?= e($school['eiin']) ?>">
                    </div>
                    <div class="field">
                        <label>Established (year)</label>
                        <input type="number" name="established" min="1800" max="<?= date('Y') ?>" value="<?= e($school['established']) ?>">
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= e($school['phone']) ?>">
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= e($school['email']) ?>">
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label>Address</label>
                        <input type="text" name="address" value="<?= e($school['address']) ?>">
                    </div>
                    <div class="field">
                        <label>Website</label>
                        <input type="text" name="website" value="<?= e($school['website']) ?>">
                    </div>
                    <div class="field">
                        <label>Logo URL</label>
                        <input type="text" name="logo" placeholder="https://… or /assets/uploads/…" value="<?= e($school['logo']) ?>">
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label>About (Bangla)</label>
                        <textarea name="about_bn" rows="3"><?= e($school['about_bn']) ?></textarea>
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label>Map embed URL</label>
                        <input type="text" name="map_embed" placeholder="https://www.google.com/maps/embed?…" value="<?= e($school['map_embed']) ?>">
                    </div>
                </div>
                <div style="margin-top:14px;display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save School Info</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ Live Preview ============ -->
    <div>
        <div class="card" style="position:sticky;top:80px;">
            <div class="card-h"><h3><i class="bi bi-eye"></i> Live Preview</h3></div>

            <!-- Mini sidebar mock -->
            <div style="border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-bottom:14px;">
                <div style="background:linear-gradient(180deg, var(--primary-dark), var(--primary) 70%);color:#fff;padding:12px 14px;display:flex;align-items:center;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg, var(--accent), var(--accent-light));color:var(--primary-dark);font-weight:800;display:grid;place-items:center;">E</div>
                    <div style="font-weight:700;font-size:14px;">EduSaaS</div>
                </div>
                <div style="background:#fff;padding:14px;">
                    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Sample button</div>
                    <button type="button" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Student</button>
                    <button type="button" class="btn btn-accent" style="margin-left:6px;"><i class="bi bi-star-fill"></i> Featured</button>
                </div>
                <div style="padding:14px;border-top:1px solid var(--line);">
                    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Badges</div>
                    <span class="badge badge-success">Active</span>
                    <span class="badge badge-info">Info</span>
                    <span class="badge badge-warn">Warning</span>
                    <span class="badge badge-muted">Inactive</span>
                </div>
            </div>

            <div style="font-size:12px;color:var(--muted);text-align:center;padding:8px;">
                Colors update across the entire site as you change them.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
