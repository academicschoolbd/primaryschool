<?php
$pageTitle = 'Classes';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$teachers = $existingClassNames = [];
if (db_ok()) {
    $teachers = db()->query('SELECT id, name FROM teachers WHERE status="active" ORDER BY name')->fetchAll();
    $existingClassNames = db()->query('SELECT DISTINCT name FROM classes ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $form = $_POST['form'] ?? '';

    if ($form === 'add-class' || $form === 'edit') {
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'section'    => trim($_POST['section'] ?? '') ?: null,
            'teacher_id' => (int)($_POST['teacher_id'] ?? 0) ?: null,
            'capacity'   => (int)($_POST['capacity'] ?? 40),
        ];
        if ($data['name'] === '') {
            flash_set('error', 'Class name is required.');
            redirect('classes.php');
        }
        try {
            if (!empty($_POST['id'])) {
                db()->prepare('UPDATE classes SET name=?,section=?,teacher_id=?,capacity=? WHERE id=?')
                    ->execute([...array_values($data), (int)$_POST['id']]);
                flash_set('success', 'Class updated.');
            } else {
                db()->prepare('INSERT INTO classes (name,section,teacher_id,capacity) VALUES (?,?,?,?)')
                    ->execute(array_values($data));
                flash_set('success', 'Class created.');
            }
        } catch (PDOException $e) {
            flash_set('error', 'Could not save: ' . $e->getMessage());
        }
        redirect('classes.php');
    }

    if ($form === 'add-section') {
        $name     = trim($_POST['parent_name'] ?? '');
        $section  = trim($_POST['section'] ?? '');
        $teacher  = (int)($_POST['teacher_id'] ?? 0) ?: null;
        $capacity = (int)($_POST['capacity'] ?? 40);
        if ($name === '' || $section === '') {
            flash_set('error', 'Class and section are required.');
            redirect('classes.php');
        }
        try {
            db()->prepare('INSERT INTO classes (name,section,teacher_id,capacity) VALUES (?,?,?,?)')
                ->execute([$name, $section, $teacher, $capacity]);
            flash_set('success', "Section $section added under $name.");
        } catch (PDOException $e) {
            flash_set('error', 'Could not save: ' . $e->getMessage());
        }
        redirect('classes.php');
    }
}

if ($action === 'delete' && $id && db_ok()) {
    db()->prepare('DELETE FROM classes WHERE id = ?')->execute([$id]);
    flash_set('success', 'Class deleted.');
    redirect('classes.php');
}

$record = ['id'=>'','name'=>'','section'=>'','teacher_id'=>'','capacity'=>40];
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $record = $row;
}

$classes = [];
if (db_ok() && $action === 'list') {
    $classes = db()->query("
        SELECT c.*, t.name AS teacher_name,
               (SELECT COUNT(*) FROM students WHERE class_id = c.id) AS students_count
        FROM classes c LEFT JOIN teachers t ON t.id = c.teacher_id
        ORDER BY c.name, c.section
    ")->fetchAll();
}
?>

<div class="page-head">
    <div>
        <h1>Classes</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Classes</div>
    </div>
    <?php if ($action === 'list'): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="classes.php?action=add-class" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Class</a>
        <a href="classes.php?action=add-section" class="btn btn-accent"><i class="bi bi-plus-square"></i> Add Section</a>
    </div>
    <?php else: ?>
    <a href="classes.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'add-class' || $action === 'edit'): ?>

<div class="card" style="max-width:660px;">
    <div class="card-h">
        <h3><i class="bi bi-bookmark-plus-fill"></i> <?= $action === 'edit' ? 'Edit Class' : 'Add New Class' ?></h3>
    </div>
    <p style="color:var(--muted);font-size:13px;margin:-8px 0 16px;">
        <i class="bi bi-info-circle"></i>
        A class can stand alone (e.g. "Grade 1") or have a section letter (e.g. "Grade 1 - A").
        Use <b>Add Section</b> to add another section under an existing class.
    </p>
    <form method="post">
        <input type="hidden" name="form" value="<?= $action === 'edit' ? 'edit' : 'add-class' ?>">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Class name <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" placeholder="e.g. Grade 1" value="<?= e($record['name']) ?>" required>
            </div>
            <div class="field">
                <label>Section (optional)</label>
                <input type="text" name="section" placeholder="e.g. A" value="<?= e($record['section']) ?>">
            </div>
            <div class="field">
                <label>Class teacher</label>
                <select name="teacher_id">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (string)$record['teacher_id']===(string)$t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Capacity</label>
                <input type="number" name="capacity" min="1" value="<?= e($record['capacity']) ?>">
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="classes.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php elseif ($action === 'add-section'): ?>

<div class="card" style="max-width:660px;">
    <div class="card-h">
        <h3><i class="bi bi-plus-square-fill"></i> Add Section to Existing Class</h3>
    </div>
    <p style="color:var(--muted);font-size:13px;margin:-8px 0 16px;">
        <i class="bi bi-info-circle"></i>
        Pick an existing class and add a new section under it. Each section becomes its own row,
        so it can have a different class teacher and capacity.
    </p>
    <form method="post">
        <input type="hidden" name="form" value="add-section">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Existing class <span style="color:#dc2626;">*</span></label>
                <select name="parent_name" required>
                    <option value="">— Select class —</option>
                    <?php foreach ($existingClassNames as $cn): ?>
                    <option value="<?= e($cn) ?>"><?= e($cn) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>New section letter <span style="color:#dc2626;">*</span></label>
                <input type="text" name="section" placeholder="e.g. B" required>
            </div>
            <div class="field">
                <label>Section teacher</label>
                <select name="teacher_id">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Capacity</label>
                <input type="number" name="capacity" min="1" value="40">
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Add Section</button>
            <a href="classes.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php else: // ===== List view ===== ?>

<div class="card">
    <div class="toolbar">
        <span style="color:var(--muted);font-size:13px;">
            <i class="bi bi-info-circle"></i>
            Click a teacher cell to <b>reassign</b> instantly (AJAX, no save needed).
        </span>
        <span class="badge badge-info"><?= count($classes) ?> classes</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>#</th><th>Class</th><th>Section</th>
                <th style="width:200px;">Class Teacher</th>
                <th>Students</th><th>Occupancy</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$classes): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">
                No classes yet. <a href="classes.php?action=add-class">Create one</a>.
            </td></tr>
        <?php else: foreach ($classes as $c):
            $occupancy = $c['capacity'] > 0 ? round(($c['students_count'] / $c['capacity']) * 100) : 0;
        ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><b><?= e($c['name']) ?></b></td>
                <td><?= e($c['section']) ?: '—' ?></td>
                <td>
                    <select class="inline-teacher" data-class-id="<?= $c['id'] ?>"
                        style="width:100%;padding:6px 8px;border:1px solid var(--line);border-radius:6px;font-size:12px;">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (string)$c['teacher_id']===(string)$t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><?= $c['students_count'] ?> / <?= $c['capacity'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="height:6px;background:#f1f5f9;border-radius:99px;width:80px;flex-shrink:0;">
                            <div style="height:6px;width:<?= min(100,$occupancy) ?>%;background:<?= $occupancy>=90?'#ef4444':'var(--primary)' ?>;border-radius:99px;"></div>
                        </div>
                        <span style="font-size:12px;"><?= $occupancy ?>%</span>
                    </div>
                </td>
                <td>
                    <a class="icon-link" href="classes.php?action=edit&id=<?= $c['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="classes.php?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Delete this class? Students in it will become unassigned.');" title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
// Inline AJAX teacher assignment
document.querySelectorAll('.inline-teacher').forEach(sel => {
    sel.addEventListener('change', async function() {
        const id = this.dataset.classId;
        this.disabled = true;
        try {
            const r = await fetch(window.APP.api + '/class_assign_teacher.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id) + '&teacher_id=' + encodeURIComponent(this.value)
            });
            const j = await r.json();
            if (!j.ok) throw new Error(j.msg);
            Toast.success(j.msg);
        } catch (e) {
            Toast.error(e.message);
        } finally {
            this.disabled = false;
        }
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
