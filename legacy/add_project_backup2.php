 ```php
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

$message = "";


/* =========================================================
   PROCESS FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $project_title = $_POST['project_title'] ?? '';
    $organization_ids = $_POST['organization_id'] ?? [];
    $new_organizations = $_POST['new_organization'] ?? [];
    $roles = $_POST['role'] ?? [];
    $beneficiaries = $_POST['beneficiaries'] ?? 0;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Start transaction
    $conn->begin_transaction();

    try {

        /* =================================================
           1. INSERT PROJECT
        ================================================= */

        $sql = "INSERT INTO `innovation_hub`
                (`Project Title`,
                 `Number of Beneficiaries`,
                 `Start Date`,
                 `End Date`)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "siss",
            $project_title,
            $beneficiaries,
            $start_date,
            $end_date
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Get newly created project ID
        $project_id = $conn->insert_id;

        $stmt->close();


        /* =================================================
           2. PREPARE NEW ORGANIZATION INSERT
        ================================================= */

        $org_insert_sql = "INSERT INTO organization
                           (organization_name)
                           VALUES (?)";

        $org_insert_stmt = $conn->prepare($org_insert_sql);

        if (!$org_insert_stmt) {
            throw new Exception($conn->error);
        }


        /* =================================================
           3. PREPARE PROJECT-ORGANIZATION RELATIONSHIP
        ================================================= */

        $org_relation_sql = "INSERT INTO project_organisations
                             (project_id, organization_id, role)
                             VALUES (?, ?, ?)";

        $org_relation_stmt = $conn->prepare($org_relation_sql);

        if (!$org_relation_stmt) {
            throw new Exception($conn->error);
        }


        /* =================================================
           4. PROCESS ORGANIZATIONS
        ================================================= */

        for ($i = 0; $i < count($roles); $i++) {

            $organization_id = $organization_ids[$i] ?? '';
            $new_organization = trim($new_organizations[$i] ?? '');
            $role = $roles[$i] ?? '';


            /*
             * If the user entered a new organization,
             * check whether it already exists.
             */

            if ($new_organization !== '') {

                $check_org_sql = "SELECT organization_id
                                  FROM organization
                                  WHERE organization_name = ?
                                  LIMIT 1";

                $check_org_stmt = $conn->prepare($check_org_sql);

                if (!$check_org_stmt) {
                    throw new Exception($conn->error);
                }

                $check_org_stmt->bind_param(
                    "s",
                    $new_organization
                );

                $check_org_stmt->execute();

                $check_org_result =
                    $check_org_stmt->get_result();

                $existing_org =
                    $check_org_result->fetch_assoc();

                $check_org_stmt->close();


                if ($existing_org) {

                    // Organization already exists
                    $organization_id =
                        $existing_org['organization_id'];

                } else {

                    // Create the new organization
                    $org_insert_stmt->bind_param(
                        "s",
                        $new_organization
                    );

                    if (!$org_insert_stmt->execute()) {
                        throw new Exception(
                            $org_insert_stmt->error
                        );
                    }

                    $organization_id =
                        $conn->insert_id;
                }
            }


            /*
             * Make sure the user either selected
             * or entered an organization.
             */

            if (empty($organization_id)) {
                throw new Exception(
                    "Please select or enter an organisation."
                );
            }


            /*
             * Save the project-organization relationship.
             */

            $org_relation_stmt->bind_param(
                "iis",
                $project_id,
                $organization_id,
                $role
            );

            if (!$org_relation_stmt->execute()) {
                throw new Exception(
                    $org_relation_stmt->error
                );
            }
        }

        $org_insert_stmt->close();
        $org_relation_stmt->close();


        /* =================================================
           5. HANDLE PROJECT REPORT
        ================================================= */

        if (
            isset($_FILES['project_report']) &&
            $_FILES['project_report']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['project_report']['error']
                !== UPLOAD_ERR_OK
            ) {
                throw new Exception(
                    "There was an error uploading the project report."
                );
            }

            $file_name =
                $_FILES['project_report']['name'];

            $file_tmp =
                $_FILES['project_report']['tmp_name'];

            $file_ext =
                strtolower(
                    pathinfo(
                        $file_name,
                        PATHINFO_EXTENSION
                    )
                );


            // Only PDF files are allowed
            if ($file_ext !== "pdf") {
                throw new Exception(
                    "Only PDF files are allowed for project reports."
                );
            }


            // Reports folder
            $upload_dir = "reports/";


            // Create reports folder if it doesn't exist
            if (!is_dir($upload_dir)) {

                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception(
                        "Unable to create the reports folder."
                    );
                }
            }


            // Generate unique filename
            $new_report =
                "project_" .
                $project_id .
                "_" .
                time() .
                ".pdf";


            $destination =
                $upload_dir . $new_report;


            // Move uploaded PDF
            if (
                !move_uploaded_file(
                    $file_tmp,
                    $destination
                )
            ) {
                throw new Exception(
                    "Failed to save the project report."
                );
            }


            // Save report filename in database
            $report_sql = "UPDATE `innovation_hub`
                           SET `Project Report` = ?
                           WHERE `project ID` = ?";

            $report_stmt =
                $conn->prepare($report_sql);

            if (!$report_stmt) {
                throw new Exception($conn->error);
            }

            $report_stmt->bind_param(
                "si",
                $new_report,
                $project_id
            );

            if (!$report_stmt->execute()) {
                throw new Exception(
                    $report_stmt->error
                );
            }

            $report_stmt->close();
        }


        /* =================================================
           6. SAVE EVERYTHING
        ================================================= */

        $conn->commit();

        echo "<script>
                alert('Project added successfully!');
                window.location.href = 'index.php';
              </script>";

        exit();


    } catch (Exception $e) {

        // Undo database changes if something goes wrong
        $conn->rollback();

        $message =
            "Error adding project: " .
            htmlspecialchars($e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add New Project | UEW Innovation Hub</title>

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


        /* =========================
           FORM CARD
        ========================= */

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


        input[type="file"] {
            padding: 11px;
            cursor: pointer;
        }


        .field {
            margin-bottom: 20px;
        }


        /* =========================
           ORGANIZATION ROW
        ========================= */

        .organization-row {
            display: grid;
            grid-template-columns: 1fr 220px;
            gap: 15px;
            padding: 18px;
            margin-bottom: 12px;
            background: #f8f9ff;
            border: 1px solid #e2e5ff;
            border-radius: 12px;
        }


        .organization-row select {
            background: white;
        }


        .add-organization {
            border: 1px dashed #6366f1;
            background: #f5f3ff;
            color: #4338ca;
            padding: 11px 18px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 3px;
            transition: 0.3s;
        }


        .add-organization:hover {
            background: #ede9fe;
        }


        /* =========================
           NEW ORGANIZATION
        ========================= */

        .new-org-btn {
            border: none;
            background: transparent;
            color: #4f46e5;
            padding: 8px 0 0;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }


        .new-org-btn:hover {
            text-decoration: underline;
        }


        .new-org-box {
            margin-top: 12px;
            padding: 12px;
            background: white;
            border: 1px solid #ddd6fe;
            border-radius: 8px;
        }


        .new-org-box input {
            margin-top: 5px;
        }


        /* =========================
           TWO COLUMN FIELDS
        ========================= */

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }


        /* =========================
           REPORT
        ========================= */

        .report-box {
            background: #f8f9ff;
            border: 1px solid #e2e5ff;
            border-radius: 12px;
            padding: 18px;
        }


        .report-box small {
            display: block;
            margin-top: 8px;
            color: #6b7280;
            font-size: 12px;
        }


        /* =========================
           MESSAGE
        ========================= */

        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            background: #fff4f4;
            color: #b91c1c;
            border-left: 4px solid #dc2626;
            border-radius: 7px;
        }


        /* =========================
           FORM ACTIONS
        ========================= */

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }


        .submit-btn {
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


        .submit-btn:hover {
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


            .organization-row {
                grid-template-columns: 1fr;
            }


            .two-column {
                grid-template-columns: 1fr;
            }


            .form-actions {
                flex-direction: column-reverse;
                gap: 18px;
                align-items: stretch;
            }


            .submit-btn {
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

        <a href="index.php">
            Projects
        </a>

        <a
            href="add_project.php"
            class="active"
        >
            Add Project
        </a>

    </div>

</nav>


<!-- =========================
     HERO
========================= -->

<section class="hero">

    <div class="hero-content">

        <small>
            PROJECT MANAGEMENT SYSTEM
        </small>

        <h1>
            Add New Project
        </h1>

        <p>
            Enter project information, organisations and project roles.
        </p>

    </div>

</section>


<!-- =========================
     MAIN CONTENT
========================= -->

<div class="container">

    <div class="form-card">


        <div class="form-header">

            <h2>
                Project Information
            </h2>

            <p>
                Complete the form below to add a new project to the UEW Innovation Hub database.
            </p>

        </div>


        <?php

        if ($message != "") {

            echo "<div class='message'>$message</div>";

        }

        ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- =========================
                 PROJECT DETAILS
            ========================= -->

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
                    placeholder="Enter the project title"
                    required
                >

            </div>


            <!-- =========================
                 ORGANIZATIONS
            ========================= -->

            <div class="section-title">

                <span class="section-number">
                    2
                </span>

                Organisations & Roles

            </div>


            <div id="organization-container">


                <!-- FIRST ORGANIZATION -->

                <div class="organization-row">


                    <div>

                        <label>
                            Organisation
                        </label>


                        <select
                            name="organization_id[]"
                            class="organization-select"
                        >

                            <option value="">
                                -- Select Existing Organisation --
                            </option>


                            <?php

                            $org_sql =
                                "SELECT organization_id,
                                        organization_name
                                 FROM organization
                                 ORDER BY organization_name ASC";

                            $org_result =
                                $conn->query($org_sql);


                            while (
                                $org =
                                $org_result->fetch_assoc()
                            ) {

                                echo "<option value='" .
                                     $org['organization_id'] .
                                     "'>" .
                                     htmlspecialchars(
                                         $org['organization_name']
                                     ) .
                                     "</option>";
                            }

                            ?>

                        </select>


                        <button
                            type="button"
                            class="new-org-btn"
                            onclick="toggleNewOrganisation(this)"
                        >
                            + Enter New Organisation
                        </button>


                        <div
                            class="new-org-box"
                            style="display:none;"
                        >

                            <label>
                                New Organisation Name
                            </label>


                            <input
                                type="text"
                                name="new_organization[]"
                                placeholder="Enter organisation or sponsor name"
                            >

                        </div>

                    </div>


                    <div>

                        <label>
                            Role
                        </label>


                        <select
                            name="role[]"
                            required
                        >

                            <option value="">
                                -- Select Role --
                            </option>


                            <option value="Sponsor">
                                Sponsor
                            </option>


                            <option value="Partner">
                                Partner
                            </option>


                            <option value="Organizer">
                                Organizer
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- ADD ANOTHER ORGANIZATION -->

            <button
                type="button"
                class="add-organization"
                onclick="addOrganization()"
            >
                + Add Another Organisation
            </button>


            <!-- =========================
                 PROJECT INFORMATION
            ========================= -->

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
                    >

                </div>


                <div class="field">

                    <label>
                        End Date
                    </label>


                    <input
                        type="date"
                        name="end_date"
                    >

                </div>

            </div>


            <!-- =========================
                 PROJECT REPORT
            ========================= -->

            <div class="section-title">

                <span class="section-number">
                    4
                </span>

                Project Report

            </div>


            <div class="report-box">

                <label
                    for="project_report"
                >
                    Project Report (PDF)
                </label>


                <input
                    type="file"
                    id="project_report"
                    name="project_report"
                    accept=".pdf,application/pdf"
                >


                <small>
                    Upload the project report in PDF format.
                    This is optional.
                </small>

            </div>


            <!-- =========================
                 ACTIONS
            ========================= -->

            <div class="form-actions">


                <a
                    class="back"
                    href="index.php"
                >
                    ← Back to Project Records
                </a>


                <button
                    type="submit"
                    class="submit-btn"
                >
                    ✓ Add Project
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


<script>


/* =========================================================
   ADD ANOTHER ORGANIZATION ROW
========================================================= */

function addOrganization() {

    const container =
        document.getElementById(
            "organization-container"
        );


    const row =
        document.createElement("div");


    row.className =
        "organization-row";


    row.innerHTML = `

        <div>

            <label>
                Organisation
            </label>


            <select
                name="organization_id[]"
                class="organization-select"
            >

                <option value="">
                    -- Select Existing Organisation --
                </option>

                <?php

                $org_result =
                    $conn->query($org_sql);


                while (
                    $org =
                    $org_result->fetch_assoc()
                ) {

                    echo "<option value='" .
                         $org['organization_id'] .
                         "'>" .
                         htmlspecialchars(
                             $org['organization_name']
                         ) .
                         "</option>";
                }

                ?>

            </select>


            <button
                type="button"
                class="new-org-btn"
                onclick="toggleNewOrganisation(this)"
            >
                + Enter New Organisation
            </button>


            <div
                class="new-org-box"
                style="display:none;"
            >

                <label>
                    New Organisation Name
                </label>


                <input
                    type="text"
                    name="new_organization[]"
                    placeholder="Enter organisation or sponsor name"
                >

            </div>

        </div>


        <div>

            <label>
                Role
            </label>


            <select
                name="role[]"
                required
            >

                <option value="">
                    -- Select Role --
                </option>


                <option value="Sponsor">
                    Sponsor
                </option>


                <option value="Partner">
                    Partner
                </option>


                <option value="Organizer">
                    Organizer
                </option>

            </select>

        </div>

    `;


    container.appendChild(row);

}


/* =========================================================
   TOGGLE NEW ORGANIZATION INPUT
========================================================= */

function toggleNewOrganisation(button) {

    const parent =
        button.parentElement;


    const select =
        parent.querySelector(
            ".organization-select"
        );


    const newBox =
        parent.querySelector(
            ".new-org-box"
        );


    const newInput =
        newBox.querySelector(
            "input"
        );


    if (
        newBox.style.display ===
        "none"
    ) {

        // Show new organization input

        newBox.style.display =
            "block";


        // Clear existing organization

        select.value =
            "";


        // Disable existing organization select

        select.disabled =
            true;


        // Require new organization name

        newInput.required =
            true;


        button.textContent =
            "− Use Existing Organisation";


    } else {

        // Hide new organization input

        newBox.style.display =
            "none";


        // Enable existing organization select

        select.disabled =
            false;


        // New organization no longer required

        newInput.required =
            false;


        newInput.value =
            "";


        button.textContent =
            "+ Enter New Organisation";
    }

}

</script>


<?php

$conn->close();

?>

</body>

</html>
```
