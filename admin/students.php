<?php
$pageTitle = 'Students';
require_once __DIR__ . '/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// Distinct class names (parent of cascade)
$classNames = [];
$yearOptions = all_years();
$filterYear = (int)($_GET['filter_year'] ?? current_year_id() ?? 0);
if (db_ok()) {
    if ($filterYear) {
        $stmt = db()->prepare("SELECT DISTINCT name FROM classes WHERE year_id = ? ORDER BY name");
        $stmt->execute([$filterYear]);
        $classNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $classNames = db()->query("SELECT DISTINCT name FROM classes ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    }
}

// === Handle POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_ok()) {
    $data = [
        'roll_no'     => trim($_POST['roll_no'] ?? ''),
        'name'        => trim($_POST['name'] ?? ''),
        'class_id'    => (int)($_POST['class_id'] ?? 0) ?: null,
        'gender'      => $_POST['gender'] ?? 'male',
        'dob'         => $_POST['dob'] ?? null,
        'parent_name' => trim($_POST['parent_name'] ?? '') ?: null,
        'phone'       => trim($_POST['phone'] ?? '') ?: null,
        'address'     => trim($_POST['address'] ?? '') ?: null,
        'status'      => $_POST['status'] ?? 'active',
        'year_id'     => (int)($_POST['year_id'] ?? 0) ?: current_year_id(),
    ];
    try {
        if (!empty($_POST['id'])) {
            $sql = 'UPDATE students SET roll_no=?,name=?,class_id=?,gender=?,dob=?,parent_name=?,phone=?,address=?,status=?,year_id=? WHERE id=?';
            $sid = (int)$_POST['id'];
            // Audit: snapshot before
            $bs = db()->prepare('SELECT * FROM students WHERE id=?'); $bs->execute([$sid]); $before = $bs->fetch();
            db()->prepare($sql)->execute([...array_values($data), $sid]);
            $diff = diff_changed($before ?: [], $data);
            audit_log('update', 'student', $sid, $data['name'] . ' (roll ' . $data['roll_no'] . ')', $diff['before'], $diff['after']);
            flash_set('success', 'Student updated.');
        } else {
            $sql = 'INSERT INTO students (roll_no,name,class_id,gender,dob,parent_name,phone,address,status,year_id) VALUES (?,?,?,?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute(array_values($data));
            $newId = (int)db()->lastInsertId();
            audit_log('create', 'student', $newId, $data['name'] . ' (roll ' . $data['roll_no'] . ')', null, $data);
            flash_set('success', 'Student added.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Could not save: ' . $e->getMessage());
    }
    redirect('students.php');
}

// === Load record for edit ===
$record = ['id'=>'','roll_no'=>'','name'=>'','class_id'=>'','gender'=>'male','dob'=>'','parent_name'=>'','phone'=>'','address'=>'','status'=>'active','year_id'=>current_year_id()];
$recordClassName = '';  // for the cascade preselect
if ($action === 'edit' && $id && db_ok()) {
    $stmt = db()->prepare('SELECT s.*, c.name AS cls_name FROM students s LEFT JOIN classes c ON c.id = s.class_id WHERE s.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $record = $row;
        $recordClassName = $row['cls_name'] ?? '';
    }
}
?>

<div class="page-head">
    <div>
        <h1>Students</h1>
        <div class="crumbs"><a href="index.php">Home</a> / Students</div>
    </div>
    <?php if ($action === 'list'): ?>
    <a href="students.php?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Student</a>
    <?php else: ?>
    <a href="students.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to list</a>
    <?php endif; ?>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

<div class="card" style="max-width:880px;">
    <div class="card-h"><h3><?= $action === 'edit' ? 'Edit Student' : 'Add New Student' ?></h3></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($record['id']) ?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field">
                <label>Roll No <span style="color:#dc2626;">*</span></label>
                <input type="text" name="roll_no" value="<?= e($record['roll_no']) ?>" required>
            </div>
            <div class="field">
                <label>Full name <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" value="<?= e($record['name']) ?>" required>
            </div>

            <!-- Cascading: Class → Section -->
            <div class="field">
                <label>Class <span style="color:#dc2626;">*</span></label>
                <select id="classSelect"
                        data-cascade-source
                        data-target="#sectionSelect"
                        required>
                    <option value="">— Select class —</option>
                    <?php foreach ($classNames as $cn): ?>
                    <option value="<?= e($cn) ?>" <?= $recordClassName === $cn ? 'selected' : '' ?>><?= e($cn) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Section <span style="color:#dc2626;">*</span></label>
                <select id="sectionSelect" name="class_id"
                        data-preselect="<?= e($record['class_id']) ?>" required>
                    <option value="">— Select class first —</option>
                </select>
            </div>

            <div class="field">
                <label>Gender</label>
                <select name="gender">
                    <?php foreach (['male','female','other'] as $g): ?>
                    <option value="<?= $g ?>" <?= $record['gender']===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Date of birth</label>
                <input type="date" name="dob" value="<?= e($record['dob']) ?>">
            </div>
            <div class="field">
                <label>Parent name</label>
                <input type="text" name="parent_name" value="<?= e($record['parent_name']) ?>">
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= e($record['phone']) ?>">
            </div>
            <div class="field" style="grid-column:1/-1;">
                <label>Address</label>
                <input type="text" name="address" value="<?= e($record['address']) ?>">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="active"   <?= $record['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $record['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="field">
                <label>Academic Year <span style="color:#dc2626;">*</span></label>
                <select name="year_id" required>
                    <?php foreach ($yearOptions as $y): ?>
                    <option value="<?= $y['id'] ?>" <?= (int)$record['year_id']===(int)$y['id']?'selected':'' ?>>
                        <?= e($y['name']) ?><?= $y['is_current'] ? ' (current)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="students.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <div style="margin-top:14px;font-size:12px;color:var(--muted);">
        <i class="bi bi-info-circle"></i>
        Section list is loaded dynamically (AJAX) when you choose a class.
    </div>
</div>

<?php else: // ===== List view ===== ?>

<div class="card">
    <div class="toolbar">
        <div class="left" style="flex-wrap:wrap;">
            <input class="search-i" type="text" id="studentSearch"
                   data-live-search="<?= BASE_URL ?>/api/students_search.php"
                   data-target="#studentsTbody"
                   data-extra-fields=".filter-field"
                   data-renderer="renderStudentsRows"
                   placeholder="Search by name, roll no, parent…">

            <select class="filter-field" name="year_id"
                    style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
                <option value="">All years</option>
                <?php foreach ($yearOptions as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $filterYear===(int)$y['id']?'selected':'' ?>>
                    <?= e($y['name']) ?><?= $y['is_current'] ? ' (current)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Cascading filter: Class name → Section -->
            <select class="filter-field" id="filterClass"
                    name="class"
                    data-cascade-source data-target="#filterSection"
                    style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
                <option value="">All classes</option>
                <?php foreach ($classNames as $cn): ?>
                <option value="<?= e($cn) ?>"><?= e($cn) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="filter-field" id="filterSection"
                    name="class_id"
                    style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;">
                <option value="">All sections</option>
            </select>
        </div>
        <span class="badge badge-info"><i class="bi bi-lightning-fill"></i> Live AJAX</span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th>Roll</th><th>Name</th><th>Class</th><th>Gender</th>
                <th>Parent</th><th>Phone</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody id="studentsTbody">
        <?php
        $students = db_ok()
            ? db()->query("
                SELECT s.*, CONCAT(c.name,' - ',COALESCE(c.section,'')) AS class_label, ay.name AS year_name
                FROM students s
                LEFT JOIN classes c          ON c.id = s.class_id
                LEFT JOIN academic_years ay  ON ay.id = s.year_id
                " . ($filterYear ? 'WHERE s.year_id = ' . (int)$filterYear : '') . "
                ORDER BY s.id DESC
            ")->fetchAll()
            : [];
        if (!$students): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">
                No students yet. <a href="students.php?action=new">Add one</a>.
            </td></tr>
        <?php else: foreach ($students as $s): ?>
            <tr data-id="<?= $s['id'] ?>">
                <td><?= e($s['roll_no']) ?></td>
                <td>
                    <span class="avatar-sm" style="background:<?= avatar_color($s['name']) ?>"><?= strtoupper(substr($s['name'],0,1)) ?></span>
                    <?= e($s['name']) ?>
                </td>
                <td><?= e($s['class_label']) ?></td>
                <td><?= ucfirst($s['gender']) ?></td>
                <td><?= e($s['parent_name']) ?: '—' ?></td>
                <td><?= e($s['phone']) ?: '—' ?></td>
                <td>
                    <?php if ($s['status']==='active'): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                    <a class="icon-link" href="marksheet.php?student_id=<?= $s['id'] ?>" title="Marksheet"><i class="bi bi-file-earmark-text"></i></a>
                    <a class="icon-link" href="students.php?action=edit&id=<?= $s['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a class="icon-link danger" href="#"
                       data-ajax-delete="<?= BASE_URL ?>/api/students_delete.php?id=<?= $s['id'] ?>"
                       data-confirm="Delete student '<?= e(addslashes($s['name'])) ?>'?"
                       title="Delete"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
// Customise the section dropdown for the *filter* (it should keep an "All sections" option).
document.addEventListener('DOMContentLoaded', function() {
    const fc = document.getElementById('filterClass');
    const fs = document.getElementById('filterSection');
    if (!fc || !fs) return;
    // Replace the cascade behaviour for the filter to add "All sections" instead of empty
    fc.addEventListener('change', async function() {
        if (!fc.value) { fs.innerHTML = '<option value="">All sections</option>'; fs.dispatchEvent(new Event('change')); return; }
        fs.innerHTML = '<option value="">Loading…</option>';
        try {
            const r = await api(window.APP.api + '/sections.php?class=' + encodeURIComponent(fc.value));
            const opts = ['<option value="">All sections</option>'];
            (r.data || []).forEach(s => {
                opts.push('<option value="' + s.id + '">' + escapeHtml(s.section || '(no section)') + '</option>');
            });
            fs.innerHTML = opts.join('');
            fs.dispatchEvent(new Event('change'));
        } catch (e) { Toast.error(e.message); }
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
