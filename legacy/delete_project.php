<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

/* =========================================================
   DELETE PROJECT PROCESSOR
   UEW INNOVATION HUB
========================================================= */

header('Content-Type: application/json');


// =========================================================
// DATABASE CONNECTION
// =========================================================

$servername = "localhost";
$username   = "root";
$password   = "mysql";
$database   = "project_mysql";

$conn = new mysqli(
    $servername,
    $username,
    $password,
    $database
);


// Check database connection
if ($conn->connect_error) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;
}


// =========================================================
// GET PROJECT ID
// =========================================================

$id = isset($_POST['id'])
    ? intval($_POST['id'])
    : 0;


// Check whether a valid ID was received
if ($id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid project ID."
    ]);

    $conn->close();
    exit;
}


// =========================================================
// START TRANSACTION
// =========================================================

$conn->begin_transaction();


try {

    // =====================================================
    // STEP 1: DELETE ORGANISATION RELATIONSHIPS
    // =====================================================

    $sql_relationships = "
        DELETE FROM `project_organisations`
        WHERE `project_id` = ?
    ";

    $stmt_relationships = $conn->prepare($sql_relationships);

    if (!$stmt_relationships) {
        throw new Exception(
            "Could not prepare organisation relationship deletion."
        );
    }

    $stmt_relationships->bind_param("i", $id);

    if (!$stmt_relationships->execute()) {
        throw new Exception(
            "Could not delete project organisation relationships."
        );
    }

    $stmt_relationships->close();


    // =====================================================
    // STEP 2: DELETE THE PROJECT
    // =====================================================

    $sql_project = "
        DELETE FROM `innovation_hub`
        WHERE `project ID` = ?
    ";

    $stmt_project = $conn->prepare($sql_project);

    if (!$stmt_project) {
        throw new Exception(
            "Could not prepare project deletion."
        );
    }

    $stmt_project->bind_param("i", $id);

    if (!$stmt_project->execute()) {
        throw new Exception(
            "Could not delete the project."
        );
    }


    // =====================================================
    // CHECK WHETHER PROJECT ACTUALLY EXISTED
    // =====================================================

    if ($stmt_project->affected_rows === 0) {

        $stmt_project->close();

        throw new Exception(
            "Project not found or has already been deleted."
        );
    }

    $stmt_project->close();


    // =====================================================
    // COMMIT THE DELETION
    // =====================================================

    $conn->commit();


    // =====================================================
    // RETURN JSON SUCCESS RESPONSE
    // =====================================================

    echo json_encode([
        "success" => true,
        "message" => "Project deleted successfully."
    ]);

} catch (Exception $e) {

    // =====================================================
    // ROLLBACK IF SOMETHING GOES WRONG
    // =====================================================

    $conn->rollback();


    // =====================================================
    // RETURN JSON ERROR RESPONSE
    // =====================================================

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}


// =========================================================
// CLOSE DATABASE CONNECTION
// =========================================================

$conn->close();

?>