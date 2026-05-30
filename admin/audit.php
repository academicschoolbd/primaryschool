<?php
$pageTitle = 'Audit Log';
require_once __DIR__ . '/../includes/header.php';

ensure_audit_table();

$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    flash_set('error', 'Only administrators can view the audit log.');
    redirect('index.php');
}

$entity_type = $_GET['entity_type'] ?? '';
$action      = $_GET['action_f']  ?? '';
$user_id_f   = (int)($_GET['user_id'] ?? 0);
$days        = (int)($_GET['days'] ?? 30);
$days        = max(1, min(365, $days));

$where = []; $args = [];
$where[] = 'created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'; $args[] = $days;
if ($entity_type) { $where[] = 'entity_type = ?'; $args[] = $entity_type; }
if ($action)      { $where[] = 'action = ?';      $args[] = $action; }
if ($user_id_f)   { $where[] = 'user_id = ?';     $args[] = $user_id_f; }

$rows = [];
$totalCount = 0;
if (db_ok()) {
    $sql = 'SELECT * FROM audit_log WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 300';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $rows = $stmt->fetchAll();
    $totalCount = (int)db()->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
}

$entityTypes = db_ok() ? db()->query('SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type')->fetchAll(PDO::FETCH_COLUMN) : [];
$actions     = db_ok() ? db()->query('SELECT DISTINCT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN) : [];
?>

<div class="page-head">
    <div>
        <h1>Audit Log <span style="font-weight:400;color:var(--muted);font-size:14px;"> · history of all admin actions</span></h1>
        <div class="crumbs"><a href="index.php">Home</a> / Audit</div>
    </div>
    <span class="badge badge-info"><?= number_format($totalCount) ?> total entries</span>
</div>

<?= db_banner_if_offline() ?>
<?= render_flash() ?>

<div class="card" style="margin-bottom:18px;">
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:12px;align-items:end;">
        <div class="field" style="margin:0;">
            <label>Last (days)</label>
            <select name="days">
                <?php foreach ([1,7,30,90,365] as $d): ?>
                <option value="<?= $d ?>" <?= $days===$d?'selected':'' ?>><?= $d ?> day<?= $d!==1?'s':'' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin:0;">
            <label>Entity</label>
            <select name="entity_type">
                <option value="">All</option>
                <?php foreach ($entityTypes as $et): ?>
                <option value="<?= e($et) ?>" <?= $entity_type===$et?'selected':'' ?>><?= e($et) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin:0;">
            <label>Action</label>
            <select name="action_f">
                <option value="">All</option>
                <?php foreach ($actions as $a): ?>
                <option value="<?= e($a) ?>" <?= $action===$a?'selected':'' ?>><?= e($a) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
    </form>
</div>

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>When</th><th>Who</th><th>Action</th><th>Entity</th><th>Target</th><th>Diff</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">
                No audit entries match these filters.
            </td></tr>
        <?php else: foreach ($rows as $r):
            $changes = $r['changes_json'] ? json_decode($r['changes_json'], true) : null;
            $hasChanges = $changes && (!empty($changes['before']) || !empty($changes['after']));
            $actionColors = [
                'create' => '#047857', 'update' => '#1d4ed8',
                'delete' => '#b91c1c', 'login'  => '#7c3aed',
            ];
            $aColor = $actionColors[$r['action']] ?? '#475569';
        ?>
            <tr>
                <td style="white-space:nowrap;font-size:12px;color:var(--muted);">
                    <?= date('M d, H:i', strtotime($r['created_at'])) ?>
                </td>
                <td>
                    <b><?= e($r['user_name'] ?: '—') ?></b>
                    <?php if ($r['user_role']): ?>
                        <div style="font-size:11px;color:var(--muted);"><?= e(ucfirst($r['user_role'])) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="display:inline-block;padding:3px 10px;border-radius:99px;background:<?= $aColor ?>15;color:<?= $aColor ?>;font-weight:700;font-size:11px;text-transform:uppercase;">
                        <?= e($r['action']) ?>
                    </span>
                </td>
                <td><span class="badge badge-muted"><?= e($r['entity_type']) ?></span><?= $r['entity_id'] ? ' #' . (int)$r['entity_id'] : '' ?></td>
                <td><?= e($r['entity_label']) ?></td>
                <td>
                    <?php if ($hasChanges): ?>
                    <details>
                        <summary style="cursor:pointer;color:var(--primary);font-weight:600;font-size:12px;">View diff</summary>
                        <pre style="background:var(--bg);padding:10px;border-radius:8px;font-size:11px;line-height:1.5;margin-top:8px;overflow-x:auto;max-width:400px;"><?= e(json_encode($changes, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
                    </details>
                    <?php else: ?>
                        <span style="color:var(--muted);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
