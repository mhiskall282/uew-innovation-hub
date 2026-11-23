 <?php
require_once __DIR__ . '/auth.php';
require_admin(true);

/* =========================================================
   EDIT PROJECT PROCESSOR - UEW INNOVATION HUB
   (supports existing AND new organisations, like Add Project)
========================================================= */

header('Content-Type: application/json; charset=UTF-8');

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

/* ---------- Database ---------- */

$conn = new mysqli("localhost", "root", "mysql", "project_mysql");

if ($conn->connect_error) {
    respond(false, 'Database connection failed.');
}

$conn->set_charset('utf8mb4');

/* ---------- Request checks ---------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request.');
}

/* A file bigger than post_max_size empties $_POST completely */
if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
    respond(false, 'The uploaded file is larger than the server allows.');
}

$id = (int)($_POST['id'] ?? $_POST['project_id'] ?? 0);

if ($id <= 0) {
    respond(false, 'Invalid project ID.');
}

/* ---------- Form data ---------- */

$project_title = trim($_POST['project_title'] ?? '');
$beneficiaries = (int)($_POST['beneficiaries'] ?? 0);
$start_date    = !empty($_POST['start_date']) ? trim($_POST['start_date']) : null;
$end_date      = !empty($_POST['end_date'])   ? trim($_POST['end_date'])   : null;

$organization_ids  = $_POST['organization_id']  ?? [];
$new_organizations = $_POST['new_organization'] ?? [];
$roles             = $_POST['role']             ?? [];

foreach (['organization_ids', 'new_organizations', 'roles'] as $name) {
    if (!is_array($$name)) {
        $$name = [$$name];
    }
}

/* ---------- Validation ---------- */

if ($project_title === '') {
    respond(false, 'Project title is required.');
}

if ($beneficiaries < 0) {
    respond(false, 'Number of beneficiaries cannot be negative.');
}

foreach ([$start_date, $end_date] as $d) {
    if ($d !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        respond(false, 'Invalid date format.');
    }
}

if ($start_date !== null && $end_date !== null && $end_date < $start_date) {
    respond(false, 'The end date cannot be before the start date.');
}

if (count($roles) === 0) {
    respond(false, 'Please add at least one organisation and role.');
}

$allowed_roles = ['Sponsor', 'Partner', 'Organizer'];

$new_report = '';
$destination = '';
$current_report = '';

$conn->begin_transaction();

try {

    /* =====================================================
       1. CHECK PROJECT EXISTS + GET CURRENT REPORT
    ===================================================== */

    $stmt = $conn->prepare("SELECT `Project Report` FROM `innovation_hub` WHERE `project ID` = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        throw new Exception('Project not found.');
    }

    $current_report = (string)($existing['Project Report'] ?? '');

    /* =====================================================
       2. UPDATE PROJECT INFORMATION
    ===================================================== */

    $stmt = $conn->prepare(
        "UPDATE `innovation_hub`
         SET `Project Title` = ?, `Number of Beneficiaries` = ?, `Start Date` = ?, `End Date` = ?
         WHERE `project ID` = ?"
    );
    $stmt->bind_param('sissi', $project_title, $beneficiaries, $start_date, $end_date, $id);
    $stmt->execute();
    $stmt->close();

    /* =====================================================
       3. REPLACE ORGANISATION RELATIONSHIPS
    ===================================================== */

    $stmt = $conn->prepare("DELETE FROM `project_organisations` WHERE project_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $find_org = $conn->prepare("SELECT organization_id FROM organization WHERE organization_name = ? LIMIT 1");
    $add_org  = $conn->prepare("INSERT INTO organization (organization_name) VALUES (?)");
    $link_org = $conn->prepare("INSERT INTO project_organisations (project_id, organization_id, role) VALUES (?, ?, ?)");

    $seen = [];

    foreach ($roles as $i => $role_raw) {

        if (is_array($role_raw)) {
            throw new Exception('Invalid organisation data received.');
        }

        $role = trim((string)$role_raw);

        if (!in_array($role, $allowed_roles, true)) {
            throw new Exception('Please select a valid role for every organisation.');
        }

        $org_id   = (int)($organization_ids[$i] ?? 0);
        $new_name = trim((string)($new_organizations[$i] ?? ''));

        /* A typed new organisation: reuse it if it exists, otherwise create it */
        if ($new_name !== '') {

            $find_org->bind_param('s', $new_name);
            $find_org->execute();
            $found = $find_org->get_result()->fetch_assoc();

            if ($found) {
                $org_id = (int)$found['organization_id'];
            } else {
                $add_org->bind_param('s', $new_name);
                $add_org->execute();
                $org_id = (int)$conn->insert_id;
            }
        }

        if ($org_id <= 0) {
            throw new Exception('Please select or enter an organisation for every row.');
        }

        /* Skip exact duplicates (same organisation + same role) */
        $key = $org_id . '|' . $role;

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;

        $link_org->bind_param('iis', $id, $org_id, $role);
        $link_org->execute();
    }

    $find_org->close();
    $add_org->close();
    $link_org->close();

    /* =====================================================
       4. OPTIONAL: REPLACE PROJECT REPORT (PDF)
    ===================================================== */

    if (isset($_FILES['project_report']) && $_FILES['project_report']['error'] !== UPLOAD_ERR_NO_FILE) {

        $file = $_FILES['project_report'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('There was an error uploading the project report.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $mime = function_exists('finfo_open')
            ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])
            : 'application/pdf';

        if ($ext !== 'pdf' || $mime !== 'application/pdf') {
            throw new Exception('Only PDF files are allowed for project reports.');
        }

        $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'reports';

        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception('Unable to create the reports folder.');
        }

        $new_report  = 'project_' . $id . '_' . time() . '.pdf';
        $destination = $upload_dir . DIRECTORY_SEPARATOR . $new_report;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $new_report = '';
            throw new Exception('Failed to save the project report.');
        }

        $stmt = $conn->prepare("UPDATE `innovation_hub` SET `Project Report` = ? WHERE `project ID` = ?");
        $stmt->bind_param('si', $new_report, $id);
        $stmt->execute();
        $stmt->close();
    }

    /* =====================================================
       5. COMMIT, THEN REMOVE THE OLD REPORT FILE
    ===================================================== */

    $conn->commit();

    if ($new_report !== '' && $current_report !== '' && $current_report !== $new_report) {

        $old = __DIR__ . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . basename($current_report);

        if (is_file($old)) {
            @unlink($old);
        }
    }

    respond(true, 'Project updated successfully.');

} catch (Exception $e) {

    $conn->rollback();

    if ($destination !== '' && is_file($destination)) {
        @unlink($destination);
    }

    respond(false, 'Error updating project: ' . $e->getMessage());
}