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

// Get Project ID
$id = $_GET['id'];


// =========================================
// STEP 1: DELETE ORGANISATION RELATIONSHIPS
// =========================================

$sql_relationships = "DELETE FROM `project_organisations`
                      WHERE `project_id` = ?";

$stmt_relationships = $conn->prepare($sql_relationships);

$stmt_relationships->bind_param("i", $id);

$stmt_relationships->execute();

$stmt_relationships->close();


// =========================================
// STEP 2: DELETE THE PROJECT
// =========================================

$sql_project = "DELETE FROM `innovation_hub`
                WHERE `project ID` = ?";

$stmt_project = $conn->prepare($sql_project);

$stmt_project->bind_param("i", $id);


if ($stmt_project->execute()) {

    // Return to main page
    header("Location: index.php");
    exit();

} else {

    echo "Error deleting project: " . $stmt_project->error;

}


$stmt_project->close();

$conn->close();

?>
```
