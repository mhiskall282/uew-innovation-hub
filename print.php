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


// =========================================
// RETRIEVE PROJECTS AND ORGANISATIONS
// =========================================

$sql = "SELECT 
            i.`project ID`,
            i.`Project Title`,
            i.`Number of Beneficiaries`,
            i.`Start Date`,
            i.`End Date`,
            GROUP_CONCAT(
                CONCAT(o.organization_name, ' (', po.role, ')')
                SEPARATOR ', '
            ) AS organizations
        FROM `innovation_hub` i
        LEFT JOIN `project_organisations` po
            ON i.`project ID` = po.project_id
        LEFT JOIN `organization` o
            ON po.organization_id = o.organization_id
        GROUP BY 
            i.`project ID`,
            i.`Project Title`,
            i.`Number of Beneficiaries`,
            i.`Start Date`,
            i.`End Date`
        ORDER BY i.`project ID` ASC";


$result = $conn->query($sql);


if (!$result) {
    die("Query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    UEW Innovation Hub | Project Report
</title>


<style>

/* =========================================
   GENERAL
========================================= */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    padding: 40px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f7fc;

    color: #1e293b;

}


/* =========================================
   REPORT CONTAINER
========================================= */

.report-container {

    max-width: 1250px;

    margin: auto;

    background: white;

    border-radius: 16px;

    box-shadow:
        0 8px 30px
        rgba(15,23,42,0.08);

    overflow: hidden;

}


/* =========================================
   REPORT HEADER
========================================= */

.report-header {

    background:
        linear-gradient(
            135deg,
            #102a72,
            #3730a3,
            #6d28d9
        );

    color: white;

    padding: 28px 35px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;

}


/* =========================================
   LOGO
========================================= */

.logo-section {

    display: flex;

    align-items: center;

    gap: 18px;

}

.logo-section img {

    width: 85px;

    height: 85px;

    object-fit: contain;

    background: white;

    border-radius: 12px;

    padding: 6px;

}


.logo-text h1 {

    margin: 0;

    font-size: 27px;

    line-height: 1.2;

}

.logo-text p {

    margin: 7px 0 0;

    font-size: 14px;

    opacity: 0.9;

}


/* =========================================
   REPORT LABEL
========================================= */

.report-label {

    text-align: right;

}

.report-label span {

    display: inline-block;

    padding: 8px 14px;

    border-radius: 20px;

    background:
        rgba(255,255,255,0.15);

    font-size: 11px;

    font-weight: bold;

    letter-spacing: 1.3px;

}


/* =========================================
   REPORT TITLE
========================================= */

.report-title {

    padding: 28px 35px 20px;

    border-bottom:
        1px solid #e2e8f0;

}

.report-title h2 {

    margin: 0;

    color: #1e1b4b;

    font-size: 24px;

}

.report-title p {

    margin: 7px 0 0;

    color: #64748b;

    font-size: 14px;

}


/* =========================================
   REPORT SUMMARY
========================================= */

.report-summary {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    padding: 20px 35px;

    background: #f8faff;

}

.summary-card {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 10px;

    padding: 15px;

}

.summary-card .label {

    color: #64748b;

    font-size: 12px;

    margin-bottom: 5px;

}

.summary-card .value {

    color: #312e81;

    font-size: 20px;

    font-weight: bold;

}


/* =========================================
   TABLE AREA
========================================= */

.table-section {

    padding: 25px 35px 35px;

}

.table-heading {

    margin-bottom: 15px;

}

.table-heading h3 {

    margin: 0;

    color: #1e1b4b;

    font-size: 18px;

}

.table-heading p {

    margin: 5px 0 0;

    color: #64748b;

    font-size: 13px;

}


/* =========================================
   TABLE
========================================= */

.table-wrapper {

    overflow-x: auto;

    border:
        1px solid #dbe3ef;

    border-radius: 10px;

}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 950px;

}

th {

    background:
        linear-gradient(
            135deg,
            #312e81,
            #6d28d9
        );

    color: white;

    padding: 14px 13px;

    text-align: left;

    font-size: 11px;

    letter-spacing: 0.4px;

    white-space: nowrap;

}

td {

    padding: 14px 13px;

    border-bottom:
        1px solid #e2e8f0;

    vertical-align: top;

    font-size: 12px;

    line-height: 1.5;

}

tbody tr:nth-child(even) {

    background: #f8faff;

}

tbody tr:last-child td {

    border-bottom: none;

}


/* =========================================
   PROJECT TITLE
========================================= */

.project-title {

    font-weight: bold;

    color: #312e81;

    min-width: 260px;

}


/* =========================================
   ORGANISATIONS
========================================= */

.organizations {

    min-width: 260px;

    color: #475569;

}


/* =========================================
   BENEFICIARIES
========================================= */

.beneficiaries {

    text-align: center;

    font-weight: bold;

    color: #334155;

    white-space: nowrap;

}


/* =========================================
   DATES
========================================= */

.date {

    white-space: nowrap;

    color: #475569;

}


/* =========================================
   EMPTY RECORDS
========================================= */

.empty-records {

    text-align: center;

    padding: 35px;

    color: #64748b;

}


/* =========================================
   REPORT FOOTER
========================================= */

.report-footer {

    background: #111c44;

    color: #cbd5e1;

    padding: 18px 35px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    font-size: 12px;

    gap: 15px;

}

.report-footer p {

    margin: 0;

}


/* =========================================
   BUTTONS
========================================= */

.button-area {

    max-width: 1250px;

    margin: 0 auto 18px;

    display: flex;

    justify-content: flex-end;

    gap: 10px;

}

.print-button,
.back-button {

    display: inline-block;

    padding: 11px 18px;

    border-radius: 8px;

    text-decoration: none;

    border: none;

    cursor: pointer;

    font-size: 13px;

    font-weight: bold;

}

.print-button {

    background:
        linear-gradient(
            135deg,
            #3730a3,
            #6d28d9
        );

    color: white;

}

.back-button {

    background: #e2e8f0;

    color: #334155;

}

.print-button:hover,
.back-button:hover {

    transform: translateY(-1px);

}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 750px) {

    body {

        padding: 15px;

    }

    .report-header {

        flex-direction: column;

        align-items: flex-start;

    }

    .report-label {

        text-align: left;

    }

    .report-summary {

        grid-template-columns: 1fr;

    }

    .report-footer {

        flex-direction: column;

        align-items: flex-start;

    }

}


/* =========================================
   PRINT
========================================= */

@media print {

    @page {

        size: landscape;

        margin: 12mm;

    }

    body {

        padding: 0;

        background: white;

    }

    .button-area {

        display: none !important;

    }

    .report-container {

        max-width: none;

        box-shadow: none;

        border-radius: 0;

    }

    .report-header {

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    .report-summary {

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    th {

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    .report-footer {

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    table {

        page-break-inside: auto;

    }

    tr {

        page-break-inside: avoid;

        page-break-after: auto;

    }

}

</style>

</head>


<body>


<!-- =========================================
     BUTTONS
========================================= -->

<div class="button-area">

    <a
        href="index.php"
        class="back-button"
    >
        ← Back to Projects
    </a>


    <button
        class="print-button"
        onclick="window.print()"
    >
        🖨️ Print Project Report
    </button>

</div>


<!-- =========================================
     REPORT CONTAINER
========================================= -->

<div class="report-container">


    <!-- =====================================
         REPORT HEADER
    ====================================== -->

    <div class="report-header">


        <div class="logo-section">


            <img
                src="innovation_hub_logo.jpg"
                alt="UEW Innovation Hub Logo"
            >


            <div class="logo-text">

                <h1>
                    UEW Innovation Hub
                </h1>

                <p>
                    Project Management System
                </p>

            </div>


        </div>


        <div class="report-label">

            <span>
                OFFICIAL PROJECT REPORT
            </span>

        </div>


    </div>


    <!-- =====================================
         REPORT TITLE
    ====================================== -->

    <div class="report-title">

        <h2>
            Innovation Hub Project Report
        </h2>

        <p>
            Consolidated record of projects, organisations,
            partnerships and project activities.
        </p>

    </div>


    <!-- =====================================
         SUMMARY
    ====================================== -->

    <div class="report-summary">


        <div class="summary-card">

            <div class="label">
                TOTAL PROJECTS
            </div>

            <div class="value">

                <?php

                echo $result->num_rows;

                ?>

            </div>

        </div>


        <div class="summary-card">

            <div class="label">
                REPORT TYPE
            </div>

            <div class="value">
                Project Records
            </div>

        </div>


        <div class="summary-card">

            <div class="label">
                SYSTEM
            </div>

            <div class="value">
                Innovation Hub
            </div>

        </div>


    </div>


    <!-- =====================================
         TABLE
    ====================================== -->

    <div class="table-section">


        <div class="table-heading">

            <h3>
                Project Records
            </h3>

            <p>
                Details of registered Innovation Hub projects
                and their organisational relationships.
            </p>

        </div>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            PROJECT TITLE
                        </th>

                        <th>
                            ORGANISATIONS & ROLES
                        </th>

                        <th>
                            BENEFICIARIES
                        </th>

                        <th>
                            START DATE
                        </th>

                        <th>
                            END DATE
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if ($result->num_rows > 0) {

                    while ($row = $result->fetch_assoc()) {

                ?>

                    <tr>


                        <!-- PROJECT TITLE -->

                        <td class="project-title">

                            <?php

                            echo htmlspecialchars(
                                $row['Project Title']
                            );

                            ?>

                        </td>


                        <!-- ORGANISATIONS -->

                        <td class="organizations">

                            <?php

                            if (!empty($row['organizations'])) {

                                echo htmlspecialchars(
                                    $row['organizations']
                                );

                            } else {

                                echo "<span style='color:#94a3b8;'>
                                        No organisation linked
                                      </span>";

                            }

                            ?>

                        </td>


                        <!-- BENEFICIARIES -->

                        <td class="beneficiaries">

                            <?php

                            if (
                                $row['Number of Beneficiaries']
                                !== null
                            ) {

                                echo htmlspecialchars(
                                    $row['Number of Beneficiaries']
                                );

                            } else {

                                echo "<span style='color:#94a3b8;'>
                                        Not provided
                                      </span>";

                            }

                            ?>

                        </td>


                        <!-- START DATE -->

                        <td class="date">

                            <?php

                            if (!empty($row['Start Date'])) {

                                echo htmlspecialchars(
                                    $row['Start Date']
                                );

                            } else {

                                echo "<span style='color:#94a3b8;'>
                                        Not provided
                                      </span>";

                            }

                            ?>

                        </td>


                        <!-- END DATE -->

                        <td class="date">

                            <?php

                            if (!empty($row['End Date'])) {

                                echo htmlspecialchars(
                                    $row['End Date']
                                );

                            } else {

                                echo "<span style='color:#94a3b8;'>
                                        Not provided
                                      </span>";

                            }

                            ?>

                        </td>


                    </tr>


                <?php

                    }

                } else {

                ?>


                    <tr>

                        <td colspan="5">

                            <div class="empty-records">

                                No project records found.

                            </div>

                        </td>

                    </tr>


                <?php

                }

                ?>


                </tbody>


            </table>


        </div>


    </div>


    <!-- =====================================
         FOOTER
    ====================================== -->

    <div class="report-footer">

        <p>
            UEW Innovation Hub • Project Management System
        </p>

        <p>
            Project Report
        </p>

    </div>


</div>


</body>

</html>


<?php

$conn->close();

?>
