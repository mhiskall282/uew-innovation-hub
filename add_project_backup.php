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

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

     $project_title = $_POST['project_title'];
$organization_id = $_POST['organization_id'];
$role = $_POST['role'];
$beneficiaries = $_POST['beneficiaries'];
$date = $_POST['date'];
     // Insert project into database
$sql = "INSERT INTO `innovation_hub`
        (`Project Tittle`,
         `Number of Beneficiaries`,
         `Date`)
        VALUES (?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "sis",
    $project_title,
    $beneficiaries,
    $date
);

    if ($stmt->execute()) {

    // Get the ID of the project that was just created
    $project_id = $conn->insert_id;

    // Save the organization and its role
    $org_sql = "INSERT INTO project_organisations
                (project_id, organization_id, role)
                VALUES (?, ?, ?)";

    $org_stmt = $conn->prepare($org_sql);

    $org_stmt->bind_param(
        "iis",
        $project_id,
        $organization_id,
        $role
    );

    if ($org_stmt->execute()) {

        echo "<script>
                alert('Project added successfully!');
                window.location.href = 'index.php';
              </script>";
        exit();

    } else {

        $message = "Organization error: " . $org_stmt->error;
    }

} else {

    $message = "Project error: " . $stmt->error;
}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add New Project</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #1f4e79;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .container {
            width: 60%;
            margin: 30px auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 12px 20px;
            background-color: #1f4e79;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #163a5c;
        }

        .message {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e8f5e9;
        }

        .back {
            margin-top: 20px;
            display: inline-block;
        }

    </style>

</head>

<body>

<div class="header">

    <h1>UEW Innovation Hub</h1>

    <p>Add New Project</p>

</div>

<div class="container">

    <h2>Project Information</h2>

    <?php

    if ($message != "") {
        echo "<div class='message'>$message</div>";
    }

    ?>

    <form method="POST">

        <label>Project Title</label>

        <input
            type="text"
            name="project_title"
            required
        >

        <label>Organization</label>

<select name="organization_id">

    <option value="">-- Select Organization --</option>

    <?php

    $org_sql = "SELECT organization_id, organization_name
                FROM organization
                ORDER BY organization_name ASC";

    $org_result = $conn->query($org_sql);

    while ($org = $org_result->fetch_assoc()) {

        echo "<option value='" . $org['organization_id'] . "'>"
             . $org['organization_name'] .
             "</option>";

    }

    ?>

</select>
<label>Role</label>

<select name="role">

    <option value="">-- Select Role --</option>
    <option value="Sponsor">Sponsor</option>
    <option value="Partner">Partner</option>
    <option value="Organizer">Organizer</option>

</select>

        <label>Number of Beneficiaries</label>

        <input
            type="number"
            name="beneficiaries"
            min="0"
        >

        <label>Date</label>

        <input
            type="date"
            name="date"
        >

        <button type="submit">
            Add Project
        </button>

    </form>

    <a class="back" href="index.php">
        ← Back to Project Database
    </a>

</div>

</body>

</html>

<?php

$conn->close();

?>