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

// Retrieve projects and their organisations
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$sql = "SELECT 
            i.`project ID`,
            i.`Project Tittle`,
            i.`Number of Beneficiaries`,
            i.`Date`,
            GROUP_CONCAT(
                CONCAT(o.organization_name, ' (', po.role, ')')
                SEPARATOR ', '
            ) AS organizations
        FROM `innovation_hub` i
        LEFT JOIN `project_organisations` po
            ON i.`project ID` = po.project_id
        LEFT JOIN `organization` o
            ON po.organization_id = o.organization_id
        WHERE i.`Project Tittle` LIKE '%$search%'
           OR i.`project ID` LIKE '%$search%'
           OR o.organization_name LIKE '%$search%'
        GROUP BY 
            i.`project ID`,
            i.`Project Tittle`,
            i.`Number of Beneficiaries`,
            i.`Date`
        ORDER BY i.`project ID` ASC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UEW Innovation Hub - Project Database</title>

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
            padding: 30px;
            text-align: center;
        }
        .logo {
    width: 180px;
    height: auto;
    margin-bottom: 15px;
}

        .header h1 {
            margin: 0;
            font-size: 30px;
        }

        .header p {
            margin-top: 10px;
            font-size: 17px;
        }

        .container {
            width: 90%;
            margin: 30px auto;
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #1f4e79;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background-color: #f1f5f9;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
        }
.add-button {
    display: inline-block;
    margin-bottom: 20px;
    padding: 12px 18px;
    background-color: #1f4e79;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}

.add-button:hover {
    background-color: #163a5c;
}
.print-button {
    display: inline-block;
    margin-bottom: 20px;
    margin-left: 10px;
    padding: 12px 18px;
    background-color: #2e7d32;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
}

 .print-button:hover {
    background-color: #1b5e20;
}

@media print {

    .add-button,
    .print-button,
    form,
    .footer,
    .actions-column {
        display: none;
    }

}

}
}
    </style>

</head>

<body>

    <div class="header">

    <img src="innovation_hub_logo.jpg" class="logo" alt="UEW Innovation Hub Logo">

    <h1>UEW Innovation Hub</h1>

    <p>Project and Sponsor Database</p>

</div>


    <div class="container">
        <?php

$count_sql = "SELECT COUNT(*) AS total_projects FROM `innovation_hub`";

$count_result = $conn->query($count_sql);

$count_row = $count_result->fetch_assoc();

$total_projects = $count_row['total_projects'];

?>
<div style="
    background-color: #e8f1f8;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
">
    <strong>Total Projects:</strong>
    <?php echo $total_projects; ?>
</div>

        <h2>Project Records</h2>
        <a href="add_project.php" class="add-button">➕ Add New Project</a>
        <button onclick="window.print()" class="print-button">🖨️ Print Database</button>
        <form method="GET" style="margin-bottom: 20px;">

    <input
        type="text"
        name="search"
        placeholder="Search project or sponsor..."
        value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>"
        style="padding: 10px; width: 60%;"
    >

    <button
        type="submit"
        style="padding: 10px 20px;"
    >
        Search
    </button>

</form>

        <table>

            <tr>

                <th>Project ID</th>

                <th>Project Title</th>

                <th>Sponsor(s) / Partner(s)</th>

                <th>Number of Beneficiaries</th>

                <th>Date</th>
                <th class="actions-column">Actions</th>

            </tr>


            <?php

            if ($result->num_rows > 0) {

                while ($row = $result->fetch_assoc()) {

                    echo "<tr>";

                    echo "<td>" . $row['project ID'] . "</td>";

                    echo "<td>" . $row['Project Tittle'] . "</td>";

                   echo "<td>" . ($row['organizations'] ?? '') . "</td>";

                    echo "<td>" . $row['Number of Beneficiaries'] . "</td>";

                    echo "<td>" . $row['Date'] . "</td>";
                    echo "<td class='actions-column'>
        <a href='edit_project.php?id=" . $row['project ID'] . "'>Edit</a>
        |
        <a href='delete_project.php?id=" . $row['project ID'] . "' 
           onclick=\"return confirm('Are you sure you want to delete this project?');\">
           Delete
        </a>
      </td>";

                }

            } else {

                echo "<tr>";

                echo "<td colspan='6'>No projects found.</td>";

                echo "</tr>";

            }

            ?>

        </table>

    </div>


    <div class="footer">

        UEW Innovation Hub Project Database

    </div>

</body>

</html>

<?php

$conn->close();

?>