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

// Get Project ID from the URL
$id = $_GET['id'];

$sql = "SELECT * FROM `innovation_hub` WHERE `project ID` = $id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $project = $result->fetch_assoc();

    echo "Project found!";
echo "<br>Project ID: " . $project['project ID'];
echo "<br>Project Title: " . $project['Project Tittle'];
echo "<br>Number of Beneficiaries: " . $project['Number of Beneficiaries'];
echo "<br>Date: " . $project['Date'];
} else {
    echo "Project not found.";
}

?>