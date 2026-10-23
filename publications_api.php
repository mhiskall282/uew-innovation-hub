 <?php
header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

$conn = new mysqli("localhost", "root", "mysql", "project_mysql");
if ($conn->connect_error) {
    respond(false, 'Database connection failed.');
}
$conn->set_charset('utf8mb4');

$uploadDir = __DIR__ . '/publications/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/* Block direct web access to the PDF folder (files are served through file.php) */
if (!file_exists($uploadDir . '.htaccess')) {
    file_put_contents(
        $uploadDir . '.htaccess',
        "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
    );
}

$action = $_REQUEST['action'] ?? '';

if (in_array($action, ['add', 'edit', 'delete'], true)) {
    require_admin(true);
}

/* ---------- LIST ---------- */
if ($action === 'list') {
    $rows = [];
    $res = $conn->query(
        "SELECT id, title, type, description, publication_date, file_name
         FROM publications
         ORDER BY publication_date DESC, id DESC"
    );
    if (!$res) {
        respond(false, 'Could not load publications. Have you run publications.sql?');
    }
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    respond(true, 'OK', ['publications' => $rows]);
}

/* ---------- ADD ---------- */
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $type  = $_POST['type'] ?? 'Publication';
    $desc  = trim($_POST['description'] ?? '');
    $date  = trim($_POST['publication_date'] ?? '');

    if ($title === '') {
        respond(false, 'Title is required.');
    }
    if (!in_array($type, ['Publication', 'Newsletter'], true)) {
        respond(false, 'Invalid type.');
    }
    if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        respond(false, 'Invalid date.');
    }
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'Please choose a PDF file to upload (check the PHP upload size limit if it fails).');
    }

    $file = $_FILES['file'];

    if ($file['size'] > 100 * 1024 * 1024) {
        respond(false, 'File is too large (100 MB max).');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = function_exists('finfo_open')
        ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])
        : 'application/pdf';

    if ($ext !== 'pdf' || $mime !== 'application/pdf') {
        respond(false, 'Only PDF files are accepted.');
    }

    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $fileName = time() . '_' . substr($safe, 0, 80) . '.pdf';

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        respond(false, 'Could not save the uploaded file.');
    }

    $dateVal = $date === '' ? null : $date;

    $stmt = $conn->prepare(
        "INSERT INTO publications (title, type, description, publication_date, file_name)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssss', $title, $type, $desc, $dateVal, $fileName);

    if (!$stmt->execute()) {
        unlink($uploadDir . $fileName);
        respond(false, 'Database error: ' . $stmt->error);
    }

    respond(true, 'Publication added successfully.');
}

/* ---------- EDIT ---------- */
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id    = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $type  = $_POST['type'] ?? 'Publication';
    $desc  = trim($_POST['description'] ?? '');
    $date  = trim($_POST['publication_date'] ?? '');

    if ($title === '') {
        respond(false, 'Title is required.');
    }
    if (!in_array($type, ['Publication', 'Newsletter'], true)) {
        respond(false, 'Invalid type.');
    }
    if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        respond(false, 'Invalid date.');
    }

    $stmt = $conn->prepare("SELECT file_name FROM publications WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if (!$existing) {
        respond(false, 'Publication not found.');
    }

    $fileName = $existing['file_name'];
    $newFile = false;

    /* Optional replacement PDF */
    if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            respond(false, 'The new file could not be uploaded.');
        }
        if ($file['size'] > 100 * 1024 * 1024) {
            respond(false, 'File is too large (100 MB max).');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = function_exists('finfo_open')
            ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])
            : 'application/pdf';

        if ($ext !== 'pdf' || $mime !== 'application/pdf') {
            respond(false, 'Only PDF files are accepted.');
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $fileName = time() . '_' . substr($safe, 0, 80) . '.pdf';

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            respond(false, 'Could not save the uploaded file.');
        }

        $newFile = true;
    }

    $dateVal = $date === '' ? null : $date;

    $stmt = $conn->prepare(
        "UPDATE publications
         SET title = ?, type = ?, description = ?, publication_date = ?, file_name = ?
         WHERE id = ?"
    );
    $stmt->bind_param('sssssi', $title, $type, $desc, $dateVal, $fileName, $id);

    if (!$stmt->execute()) {
        if ($newFile) {
            unlink($uploadDir . $fileName);
        }
        respond(false, 'Database error: ' . $stmt->error);
    }

    /* Remove the old PDF only after the update succeeded */
    if ($newFile) {
        $old = $uploadDir . basename($existing['file_name']);
        if (is_file($old)) {
            unlink($old);
        }
    }

    respond(true, 'Publication updated successfully.');
}

/* ---------- DELETE ---------- */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $conn->prepare("SELECT file_name FROM publications WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        respond(false, 'Publication not found.');
    }

    $stmt = $conn->prepare("DELETE FROM publications WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $path = $uploadDir . basename($row['file_name']);
    if (is_file($path)) {
        unlink($path);
    }

    respond(true, 'Publication deleted.');
}

respond(false, 'Invalid request.');