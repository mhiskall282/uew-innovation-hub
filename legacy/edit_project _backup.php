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

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $project_title = $_POST['project_title'];
    $beneficiaries = $_POST['beneficiaries'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Update the project
    $update_sql = "UPDATE `innovation_hub`
                   SET `Project Title` = ?,
                       `Number of Beneficiaries` = ?,
                       `Start Date` = ?,
                       `End Date` = ?
                   WHERE `project ID` = ?";

    $update_stmt = $conn->prepare($update_sql);

    $update_stmt->bind_param(
        "sissi",
        $project_title,
        $beneficiaries,
        $start_date,
        $end_date,
        $id
    );

    if ($update_stmt->execute()) {

        // Return to main page
        header("Location: index.php");
        exit();

    } else {

        echo "Error updating project: " . $update_stmt->error;

    }

    $update_stmt->close();
}

// Retrieve the selected project
$sql = "SELECT * FROM `innovation_hub`
        WHERE `project ID` = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    die("Project not found.");

}

$project = $result->fetch_assoc();

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

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #fff;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        input:focus {
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
            Organisations and their roles are managed separately
            through the project organisation relationships.

        </div>


        <form method="POST">


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


            <!-- PROJECT INFORMATION -->

            <div class="section-title">

                <span class="section-number">
                    2
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

$stmt->close();
$conn->close();

?>
