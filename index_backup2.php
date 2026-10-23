 <?php

$servername = "localhost";
$username = "root";
$password = "mysql";
$database = "project_mysql";

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get Project ID from URL
$id = $_GET['id'] ?? 0;

if (!$id) {
    die("Invalid project ID.");
}


/* =========================================================
   PROCESS FORM SUBMISSION
========================================================= */
// Get current project report
$current_report_sql = "SELECT `Project Report`
                       FROM `innovation_hub`
                       WHERE `project ID` = ?";

$current_report_stmt = $conn->prepare($current_report_sql);

if (!$current_report_stmt) {
    die("Error checking current report: " . $conn->error);
}

$current_report_stmt->bind_param("i", $id);
$current_report_stmt->execute();

$current_report_result = $current_report_stmt->get_result();
$current_report_data = $current_report_result->fetch_assoc();

$current_report = $current_report_data['Project Report'] ?? '';

$current_report_stmt->close();
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $project_title = $_POST['project_title'];
    $beneficiaries = $_POST['beneficiaries'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $organization_id = $_POST['organization_id'];
    $role = $_POST['role'];

    // Start transaction
    $conn->begin_transaction();

    try {

        /* -------------------------------------------------
           1. UPDATE PROJECT DETAILS
        ------------------------------------------------- */

        $update_sql = "UPDATE `innovation_hub`
                       SET `Project Title` = ?,
                           `Number of Beneficiaries` = ?,
                           `Start Date` = ?,
                           `End Date` = ?
                       WHERE `project ID` = ?";

        $update_stmt = $conn->prepare($update_sql);

        if (!$update_stmt) {
            throw new Exception($conn->error);
        }

        $update_stmt->bind_param(
            "sissi",
            $project_title,
            $beneficiaries,
            $start_date,
            $end_date,
            $id
        );

        if (!$update_stmt->execute()) {
            throw new Exception($update_stmt->error);
        }

        $update_stmt->close();
                /* -------------------------------------------------
           1B. HANDLE PROJECT REPORT
        ------------------------------------------------- */

        $new_report = $current_report;

        if (
            isset($_FILES['project_report']) &&
            $_FILES['project_report']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['project_report']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("There was an error uploading the project report.");
            }

            $file_name = $_FILES['project_report']['name'];
            $file_tmp = $_FILES['project_report']['tmp_name'];

            $file_ext = strtolower(
                pathinfo($file_name, PATHINFO_EXTENSION)
            );

            if ($file_ext !== "pdf") {
                throw new Exception("Only PDF files are allowed for project reports.");
            }

            $upload_dir = "reports/";

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception("Unable to create the reports folder.");
                }
            }

            $new_report = "project_" . $id . "_" . time() . ".pdf";

            $destination = $upload_dir . $new_report;

            if (!move_uploaded_file($file_tmp, $destination)) {
                throw new Exception("Failed to save the project report.");
            }

            // Update report filename in database
            $report_sql = "UPDATE `innovation_hub`
                           SET `Project Report` = ?
                           WHERE `project ID` = ?";

            $report_stmt = $conn->prepare($report_sql);

            if (!$report_stmt) {
                throw new Exception($conn->error);
            }

            $report_stmt->bind_param(
                "si",
                $new_report,
                $id
            );

            if (!$report_stmt->execute()) {
                throw new Exception($report_stmt->error);
            }

            $report_stmt->close();
        }



        /* -------------------------------------------------
           2. GET CURRENT ORGANIZATION RELATIONSHIP
        ------------------------------------------------- */

        $check_sql = "SELECT organization_id
                      FROM `project_organisations`
                      WHERE project_id = ?
                      LIMIT 1";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {
            throw new Exception($conn->error);
        }

        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        $existing_relationship = $check_result->fetch_assoc();

        $check_stmt->close();


        /* -------------------------------------------------
           3. UPDATE OR INSERT ORGANIZATION RELATIONSHIP
        ------------------------------------------------- */

        if ($existing_relationship) {

            $old_organization_id = $existing_relationship['organization_id'];

            $relationship_sql = "UPDATE `project_organisations`
                                 SET organization_id = ?,
                                     role = ?
                                 WHERE project_id = ?
                                 AND organization_id = ?";

            $relationship_stmt = $conn->prepare($relationship_sql);

            if (!$relationship_stmt) {
                throw new Exception($conn->error);
            }

            $relationship_stmt->bind_param(
                "isii",
                $organization_id,
                $role,
                $id,
                $old_organization_id
            );

            if (!$relationship_stmt->execute()) {
                throw new Exception($relationship_stmt->error);
            }

            $relationship_stmt->close();

        } else {

            // No organization relationship exists yet,
            // so create one.

            $relationship_sql = "INSERT INTO `project_organisations`
                                 (project_id, organization_id, role)
                                 VALUES (?, ?, ?)";

            $relationship_stmt = $conn->prepare($relationship_sql);

            if (!$relationship_stmt) {
                throw new Exception($conn->error);
            }

            $relationship_stmt->bind_param(
                "iis",
                $id,
                $organization_id,
                $role
            );

            if (!$relationship_stmt->execute()) {
                throw new Exception($relationship_stmt->error);
            }

            $relationship_stmt->close();
        }


        /* -------------------------------------------------
           4. SAVE EVERYTHING
        ------------------------------------------------- */

        $conn->commit();

        header("Location: index.php");
        exit();

    } catch (Exception $e) {

        $conn->rollback();

        die("Error updating project: " . $e->getMessage());
    }
}


/* =========================================================
   GET PROJECT DETAILS
========================================================= */

$sql = "SELECT *
        FROM `innovation_hub`
        WHERE `project ID` = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Project not found.");
}

$project = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   GET CURRENT ORGANIZATION RELATIONSHIP
========================================================= */

$relationship_sql = "SELECT po.organization_id,
                            po.role,
                            o.organization_name
                     FROM `project_organisations` po
                     LEFT JOIN `organization` o
                     ON po.organization_id = o.organization_id
                     WHERE po.project_id = ?
                     LIMIT 1";

$relationship_stmt = $conn->prepare($relationship_sql);

$relationship_stmt->bind_param("i", $id);

$relationship_stmt->execute();

$relationship_result = $relationship_stmt->get_result();

$relationship = $relationship_result->fetch_assoc();

$relationship_stmt->close();


$current_organization_id = $relationship['organization_id'] ?? '';
$current_role = $relationship['role'] ?? '';


/* =========================================================
   GET ALL ORGANIZATIONS
========================================================= */

$organization_sql = "SELECT organization_id, organization_name
                     FROM `organization`
                     ORDER BY organization_name ASC";

$organization_result = $conn->query($organization_sql);

if (!$organization_result) {
    die("Error loading organizations: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Project | UEW Innovation Hub</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fc;
            color: #1f2937;
        }

        /* =========================
           NAVIGATION
        ========================= */

        .navbar {
    background: linear-gradient(135deg, #102a72, #4f46e5);
    color: white;
    padding: 14px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);

    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            background: white;
            border-radius: 10px;
            padding: 4px;
        }

        .brand-text h2 {
            margin: 0;
            font-size: 20px;
        }

        .brand-text p {
            margin: 3px 0 0;
            font-size: 12px;
            opacity: 0.85;
        }

        .nav-links {
            display: flex;
            gap: 10px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }

        .nav-links a:hover,
        .nav-links .active {
            background: rgba(255,255,255,0.18);
        }


        /* =========================
           HERO
        ========================= */

        .hero {
    background: linear-gradient(135deg, #172f7a, #6366f1);
    margin-top: 80px;
    color: white;
    padding: 45px 6%;
    position: relative;
    overflow: hidden;
}

        .hero::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            right: -80px;
            top: -100px;
        }

        .hero-content {
            max-width: 1100px;
            margin: auto;
            position: relative;
            z-index: 1;
        }

        .hero small {
            letter-spacing: 2px;
            font-weight: bold;
            opacity: 0.85;
        }

        .hero h1 {
            margin: 10px 0 8px;
            font-size: 34px;
        }

        .hero p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }


        /* =========================
           MAIN CONTAINER
        ========================= */

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 35px auto 60px;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 8px 30px rgba(31, 41, 55, 0.08);
            border: 1px solid #e5e7eb;
        }

        .form-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
        }

        .form-header h2 {
            margin: 0 0 7px;
            color: #172f7a;
            font-size: 24px;
        }

        .form-header p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }


        /* =========================
           PROJECT ID
        ========================= */

        .project-id {
            background: #f5f3ff;
            border: 1px solid #e2e5ff;
            padding: 12px 15px;
            border-radius: 9px;
            margin-bottom: 25px;
            color: #4338ca;
            font-size: 14px;
            font-weight: bold;
        }


        /* =========================
           FORM SECTIONS
        ========================= */

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 25px 0 15px;
            color: #172f7a;
            font-size: 17px;
            font-weight: bold;
        }

        .section-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #172f7a, #6366f1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #374151;
            font-size: 14px;
        }

        .field {
            margin-bottom: 20px;
        }

        input,
        select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #fff;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        input:focus,
        select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.12);
        }


        /* =========================
           DATE FIELDS
        ========================= */

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }


        /* =========================
           NOTICE
        ========================= */

        .notice {
            background: #f8f9ff;
            border-left: 4px solid #6366f1;
            padding: 14px 16px;
            margin-bottom: 25px;
            border-radius: 7px;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.5;
        }


        /* =========================
           ACTIONS
        ========================= */

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .save-btn {
            border: none;
            background: linear-gradient(135deg, #172f7a, #6366f1);
            color: white;
            padding: 13px 25px;
            border-radius: 9px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(79,70,229,0.25);
        }

        .save-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 18px rgba(79,70,229,0.35);
        }

        .back {
            color: #4f46e5;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .back:hover {
            text-decoration: underline;
        }


        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #111c44;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 13px;
        }

        footer p {
            margin: 0;
            opacity: 0.8;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 768px) {

            .navbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero h1 {
                font-size: 27px;
            }

            .container {
                width: 94%;
            }

            .form-card {
                padding: 22px;
            }

            .two-column {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column-reverse;
                gap: 18px;
                align-items: stretch;
            }

            .save-btn {
                width: 100%;
            }

            .back {
                text-align: center;
            }
        }

    </style>

</head>

<body>


<!-- =========================
     NAVIGATION
========================= -->

<nav class="navbar">

    <div class="brand">

        <img
            src="innovation_hub_logo.jpg"
            alt="UEW Innovation Hub Logo"
        >

        <div class="brand-text">

            <h2>UEW Innovation Hub</h2>

            <p>Project Management System</p>

        </div>

    </div>


    <div class="nav-links">

        <a href="home.php">
            Home
        </a>

        <a href="index.php" class="active">
            Projects
        </a>

        <a href="add_project.php">
            Add Project
        </a>

    </div>

</nav>


<!-- =========================
     HERO
========================= -->

<section class="hero">

    <div class="hero-content">

        <small>PROJECT MANAGEMENT SYSTEM</small>

        <h1>Edit Project</h1>

        <p>
            Update the information of an existing UEW Innovation Hub project.
        </p>

    </div>

</section>


<!-- =========================
     MAIN CONTENT
========================= -->

<div class="container">

    <div class="form-card">

        <div class="form-header">

            <h2>Edit Project Information</h2>

            <p>
                Make the necessary changes to the project details below.
            </p>

        </div>


        <div class="project-id">

            Project ID:
            <?php echo htmlspecialchars($project['project ID']); ?>

        </div>


        <div class="notice">

            <strong>Note:</strong>
            You can now update the organization and its role together
            with the project information.

        </div>


        <form method="POST" enctype="multipart/form-data">


            <!-- PROJECT DETAILS -->

            <div class="section-title">

                <span class="section-number">
                    1
                </span>

                Project Details

            </div>


            <div class="field">

                <label>
                    Project Title
                </label>

                <input
                    type="text"
                    name="project_title"
                    value="<?php echo htmlspecialchars($project['Project Title']); ?>"
                    required
                >

            </div>


            <!-- ORGANIZATION -->

            <div class="section-title">

                <span class="section-number">
                    2
                </span>

                Organization Details

            </div>


            <div class="field">

                <label>
                    Organization
                </label>

                <select name="organization_id" required>

                    <option value="">
                        -- Select Organization --
                    </option>

                    <?php while ($organization = $organization_result->fetch_assoc()) { ?>

                        <option
                            value="<?php echo $organization['organization_id']; ?>"
                            <?php
                            if ($organization['organization_id'] == $current_organization_id) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php echo htmlspecialchars($organization['organization_name']); ?>

                        </option>

                    <?php } ?>

                </select>

            </div>


            <div class="field">

                <label>
                    Role
                </label>

                <select name="role" required>

                    <option value="">
                        -- Select Role --
                    </option>

                    <option
    value="Partner"
    <?php
    if ($current_role == "Partner") {
        echo "selected";
    }
    ?>
>
    Partner
</option>

<option
    value="Organizer"
    <?php
    if ($current_role == "Organizer") {
        echo "selected";
    }
    ?>
>
    Organizer
</option>

                    >
                        Sponsor
                    </option>

                    <option
                        value="Partner"
                        <?php
                        if ($current_role == "Partner") {
                            echo "selected";
                        }
                        ?>
                    >
                        Partner
                    </option>

                </select>

            </div>


            <!-- PROJECT INFORMATION -->

            <div class="section-title">

                <span class="section-number">
                    3
                </span>

                Project Information

            </div>


            <div class="field">

                <label>
                    Number of Beneficiaries
                </label>

                <input
                    type="number"
                    name="beneficiaries"
                    value="<?php echo htmlspecialchars($project['Number of Beneficiaries'] ?? ''); ?>"
                    min="0"
                    placeholder="Enter number of beneficiaries"
                >

            </div>


            <div class="two-column">


                <div class="field">

                    <label>
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        value="<?php echo htmlspecialchars($project['Start Date'] ?? ''); ?>"
                    >

                </div>


                <div class="field">

                    <label>
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        value="<?php echo htmlspecialchars($project['End Date'] ?? ''); ?>"
                    >

                </div>


            </div>


            <!-- ACTIONS -->
                         <!-- PROJECT REPORT -->

            <div class="section-title">

                <span class="section-number">
                    4
                </span>

                Project Report

            </div>

            <div class="field">

                <label>
                    Current Project Report
                </label>

                <?php if (!empty($project['Project Report'])): ?>

                    <div class="notice">
                        📄 Current report:
                        <strong>
                            <?php echo htmlspecialchars($project['Project Report']); ?>
                        </strong>
                    </div>

                    <label for="project_report">
                        Replace Report (Optional)
                    </label>

                    <input
                        type="file"
                        id="project_report"
                        name="project_report"
                        accept=".pdf,application/pdf"
                    >

                    <small style="display:block; margin-top:8px; color:#6b7280;">
                        Leave this empty if you want to keep the current report.
                    </small>

                <?php else: ?>

                    <div class="notice">
                        📄 No report has been uploaded for this project yet.
                    </div>

                    <input
                        type="file"
                        id="project_report"
                        name="project_report"
                        accept=".pdf,application/pdf"
                    >

                    <small style="display:block; margin-top:8px; color:#6b7280;">
                        Upload the project report in PDF format.
                    </small>

                <?php endif; ?>

            </div>

            <div class="form-actions">

                <a
                    class="back"
                    href="index.php"
                >
                    ← Back to Project Records
                </a>


                <button
                    type="submit"
                    class="save-btn"
                >
                    ✓ Save Changes
                </button>

            </div>


        </form>

    </div>

</div>


<!-- =========================
     FOOTER
========================= -->

<footer>

    <p>
        UEW Innovation Hub • Project Management System
    </p>

</footer>


</body>

</html>

<?php

$conn->close();

?>