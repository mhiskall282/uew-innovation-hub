 <?php
require_once __DIR__ . '/auth.php';
require_admin(true);
/* =========================================================
   DATABASE CONNECTION
========================================================= */

$servername = "localhost";
$username = "root";
$password = "mysql";
$database = "project_mysql";

$conn = new mysqli(
    $servername,
    $username,
    $password,
    $database
);


/* =========================================================
   CHECK DATABASE CONNECTION
========================================================= */

if ($conn->connect_error) {

    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);

    exit();
}


/* =========================================================
   CHECK AJAX REQUEST
========================================================= */

$is_ajax =
    isset($_POST['ajax']) &&
    $_POST['ajax'] === '1';


/* =========================================================
   IF NOT POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    if ($is_ajax) {

        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.'
        ]);

        exit();
    }

    header('Location: index.php');
    exit();
}


/* =========================================================
   GET FORM DATA
========================================================= */

$project_title =
    trim($_POST['project_title'] ?? '');

$organization_ids =
    $_POST['organization_id'] ?? [];

$new_organizations =
    $_POST['new_organization'] ?? [];

$roles =
    $_POST['role'] ?? [];

$beneficiaries =
    (int)($_POST['beneficiaries'] ?? 0);

$start_date = !empty($_POST['start_date'])
    ? $_POST['start_date']
    : null;

$end_date = !empty($_POST['end_date'])
    ? $_POST['end_date']
    : null;

/* =========================================================
   VALIDATION
========================================================= */

if ($project_title === '') {

    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'message' => 'Project title is required.'
    ]);

    exit();
}


if (empty($roles)) {

    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'message' => 'Please add at least one organisation and role.'
    ]);

    exit();
}


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


$uploaded_report = null;


try {


    /* =====================================================
       1. INSERT PROJECT
    ===================================================== */

    $sql = "INSERT INTO `innovation_hub`
            (
                `Project Title`,
                `Number of Beneficiaries`,
                `Start Date`,
                `End Date`
            )
            VALUES (?, ?, ?, ?)";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        throw new Exception(
            $conn->error
        );
    }


    $stmt->bind_param(
        "siss",
        $project_title,
        $beneficiaries,
        $start_date,
        $end_date
    );


    if (!$stmt->execute()) {

        throw new Exception(
            $stmt->error
        );
    }


    $project_id =
        $conn->insert_id;


    $stmt->close();


    /* =====================================================
       2. PREPARE ORGANIZATION INSERT
    ===================================================== */

    $org_insert_sql =
        "INSERT INTO organization
        (organization_name)
        VALUES (?)";


    $org_insert_stmt =
        $conn->prepare(
            $org_insert_sql
        );


    if (!$org_insert_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    /* =====================================================
       3. PREPARE RELATIONSHIP INSERT
    ===================================================== */

    $relation_sql =
        "INSERT INTO project_organisations
        (
            project_id,
            organization_id,
            role
        )
        VALUES (?, ?, ?)";


    $relation_stmt =
        $conn->prepare(
            $relation_sql
        );


    if (!$relation_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    /* =====================================================
       4. PROCESS ORGANIZATIONS
    ===================================================== */

    for (
        $i = 0;
        $i < count($roles);
        $i++
    ) {


        $organization_id =
            $organization_ids[$i] ?? '';


        $new_organization =
            trim(
                $new_organizations[$i] ?? ''
            );


        $role =
            trim(
                $roles[$i] ?? ''
            );


        /* =================================================
           CHECK ROLE
        ================================================= */

        if ($role === '') {

            throw new Exception(
                'Please select a role for every organisation.'
            );
        }


        /* =================================================
           NEW ORGANIZATION
        ================================================= */

        if ($new_organization !== '') {


            /*
             * Check whether organization
             * already exists.
             */

            $check_sql =
                "SELECT organization_id
                 FROM organization
                 WHERE organization_name = ?
                 LIMIT 1";


            $check_stmt =
                $conn->prepare(
                    $check_sql
                );


            if (!$check_stmt) {

                throw new Exception(
                    $conn->error
                );
            }


            $check_stmt->bind_param(
                "s",
                $new_organization
            );


            $check_stmt->execute();


            $check_result =
                $check_stmt->get_result();


            $existing =
                $check_result->fetch_assoc();


            $check_stmt->close();


            /* =============================================
               ORGANIZATION ALREADY EXISTS
            ============================================= */

            if ($existing) {

                $organization_id =
                    $existing['organization_id'];

            }


            /* =============================================
               CREATE NEW ORGANIZATION
            ============================================= */

            else {

                $org_insert_stmt->bind_param(
                    "s",
                    $new_organization
                );


                if (
                    !$org_insert_stmt->execute()
                ) {

                    throw new Exception(
                        $org_insert_stmt->error
                    );
                }


                $organization_id =
                    $conn->insert_id;
            }
        }


        /* =================================================
           MAKE SURE ORGANIZATION EXISTS
        ================================================= */

        if (empty($organization_id)) {

            throw new Exception(
                'Please select or enter an organisation.'
            );
        }


        /* =================================================
           INSERT PROJECT-ORGANIZATION RELATION
        ================================================= */

        $relation_stmt->bind_param(
            "iis",
            $project_id,
            $organization_id,
            $role
        );


        if (
            !$relation_stmt->execute()
        ) {

            throw new Exception(
                $relation_stmt->error
            );
        }

    }


    $org_insert_stmt->close();

    $relation_stmt->close();


    /* =====================================================
       5. HANDLE PROJECT REPORT
    ===================================================== */

    if (
        isset($_FILES['project_report']) &&
        $_FILES['project_report']['error']
        !== UPLOAD_ERR_NO_FILE
    ) {


        /* ================================================
           CHECK UPLOAD ERROR
        ================================================ */

        if (
            $_FILES['project_report']['error']
            !== UPLOAD_ERR_OK
        ) {

            throw new Exception(
                'There was an error uploading the project report.'
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


        /* ================================================
           ONLY PDF
        ================================================ */

        if ($file_ext !== 'pdf') {

            throw new Exception(
                'Only PDF files are allowed for project reports.'
            );
        }


        /* ================================================
           REPORT DIRECTORY
        ================================================ */

        $upload_dir =
            __DIR__ . DIRECTORY_SEPARATOR . 'reports';


        if (!is_dir($upload_dir)) {

            if (
                !mkdir(
                    $upload_dir,
                    0755,
                    true
                )
            ) {

                throw new Exception(
                    'Unable to create the reports folder.'
                );
            }
        }


        /* ================================================
           CREATE UNIQUE FILE NAME
        ================================================ */

        $new_report =
            'project_' .
            $project_id .
            '_' .
            time() .
            '.pdf';


        $destination =
            $upload_dir .
            DIRECTORY_SEPARATOR .
            $new_report;


        /* ================================================
           MOVE PDF
        ================================================ */

        if (
            !move_uploaded_file(
                $file_tmp,
                $destination
            )
        ) {

            throw new Exception(
                'Failed to save the project report.'
            );
        }


        $uploaded_report =
            $destination;


        /* ================================================
           SAVE REPORT NAME IN DATABASE
        ================================================ */

        $report_sql =
            "UPDATE `innovation_hub`
             SET `Project Report` = ?
             WHERE `project ID` = ?";


        $report_stmt =
            $conn->prepare(
                $report_sql
            );


        if (!$report_stmt) {

            throw new Exception(
                $conn->error
            );
        }


        $report_stmt->bind_param(
            "si",
            $new_report,
            $project_id
        );


        if (
            !$report_stmt->execute()
        ) {

            throw new Exception(
                $report_stmt->error
            );
        }


        $report_stmt->close();
    }


    /* =====================================================
       6. COMMIT TRANSACTION
    ===================================================== */

    $conn->commit();


    /* =====================================================
       7. RETURN JSON SUCCESS
    ===================================================== */

    header(
        'Content-Type: application/json'
    );


    echo json_encode([
        'success' => true,
        'message' => 'Project added successfully.',
        'project_id' => $project_id
    ]);


    exit();


}


/* =========================================================
   ERROR HANDLING
========================================================= */

catch (Exception $e) {


    /* =====================================================
       ROLLBACK DATABASE
    ===================================================== */

    $conn->rollback();


    /* =====================================================
       DELETE UPLOADED REPORT IF SOMETHING FAILED
    ===================================================== */

    if (
        $uploaded_report !== null &&
        file_exists($uploaded_report)
    ) {

        unlink($uploaded_report);
    }


    /* =====================================================
       RETURN JSON ERROR
    ===================================================== */

    header(
        'Content-Type: application/json'
    );


    echo json_encode([
        'success' => false,
        'message' => 'Error adding project: ' .
                     $e->getMessage()
    ]);


    exit();
}


/* =========================================================
   CLOSE DATABASE
========================================================= */

$conn->close();

?>