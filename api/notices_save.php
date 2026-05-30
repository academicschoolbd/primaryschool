<?php
// POST /api/notices_save.php  (multipart/form-data)
// Fields: id (optional), title, body, is_floating, is_published, pdf (file, optional)
require __DIR__ . '/_bootstrap.php';
if (!db_ok()) json_err('Database not connected', 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST required', 405);

$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') json_err('Forbidden', 403);

$id           = (int)($_POST['id'] ?? 0);
$title        = trim($_POST['title'] ?? '');
$body         = trim($_POST['body'] ?? '');
$isFloating   = !empty($_POST['is_floating']) ? 1 : 0;
$isPublished  = !empty($_POST['is_published']) ? 1 : 0;

if ($title === '') json_err('Title is required.');

// PDF upload (optional, max ~6MB)
$pdfRel = null; $pdfSize = null;
if (!empty($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['pdf'];
    if ($f['size'] > 6 * 1024 * 1024) json_err('PDF too large (max 6 MB).');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if ($mime !== 'application/pdf') json_err('Only PDF files allowed.');
    $dir = __DIR__ . '/../assets/uploads/notices';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = 'notice_' . bin2hex(random_bytes(6)) . '.pdf';
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) json_err('Could not save PDF.');
    $pdfRel  = 'assets/uploads/notices/' . $name;
    $pdfSize = filesize($dest);
}

try {
    db()->beginTransaction();

    // If marking this as the floating notice, unset any others first.
    if ($isFloating) {
        db()->exec('UPDATE notices SET is_floating = 0 WHERE is_floating = 1');
    }

    if ($id) {
        // Find existing PDF to optionally clean up
        $stmt = db()->prepare('SELECT pdf_url FROM notices WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetchColumn();

        if ($pdfRel) {
            // Replace existing PDF
            $sql = 'UPDATE notices SET title=?, body=?, pdf_url=?, pdf_size=?, is_floating=?, is_published=? WHERE id=?';
            db()->prepare($sql)->execute([$title, $body, $pdfRel, $pdfSize, $isFloating, $isPublished, $id]);
            if ($existing) delete_photo($existing);
        } else {
            $sql = 'UPDATE notices SET title=?, body=?, is_floating=?, is_published=? WHERE id=?';
            db()->prepare($sql)->execute([$title, $body, $isFloating, $isPublished, $id]);
        }
    } else {
        $sql = 'INSERT INTO notices (title, body, pdf_url, pdf_size, is_floating, is_published, posted_at)
                VALUES (?,?,?,?,?,?,NOW())';
        db()->prepare($sql)->execute([$title, $body, $pdfRel, $pdfSize, $isFloating, $isPublished]);
        $id = (int)db()->lastInsertId();
    }
    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    if ($pdfRel) delete_photo($pdfRel);
    json_err('Could not save: ' . $e->getMessage());
}

json_ok(['id' => $id], 'Notice saved.');
