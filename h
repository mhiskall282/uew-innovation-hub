  <?php

$servername = "localhost";
$username = "root";
$password = "mysl";
$database = "project_mysql";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


/* =========================================================
   DASHBOARD STATISTICS
========================================================= */

$total_projects = 0;
$total_organisations = 0;
$total_beneficiaries = 0;
$linked_projects = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM innovation_hub"
);

if ($result) {
    $row = $result->fetch_assoc();
    $total_projects = (int)$row['total'];
}


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM organization"
);

if ($result) {
    $row = $result->fetch_assoc();
    $total_organisations = (int)$row['total'];
}


$result = $conn->query(
    "SELECT COALESCE(SUM(`Number of Beneficiaries`), 0) AS total
     FROM innovation_hub"
);

if ($result) {
    $row = $result->fetch_assoc();
    $total_beneficiaries = (int)$row['total'];
}


$result = $conn->query(
    "SELECT COUNT(DISTINCT project_id) AS total
     FROM project_organisations"
);

if ($result) {
    $row = $result->fetch_assoc();
    $linked_projects = (int)$row['total'];
}


/* =========================================================
   LOAD ORGANISATIONS
========================================================= */

$organizations = [];

$org_result = $conn->query(
    "SELECT organization_id, organization_name
     FROM organization
     ORDER BY organization_name ASC"
);

if ($org_result) {

    while ($org = $org_result->fetch_assoc()) {
        $organizations[] = $org;
    }
}


/* =========================================================
   LOAD PROJECTS
========================================================= */

$sql = "SELECT
            i.`project ID`,
            i.`Project Title`,
            i.`Number of Beneficiaries`,
            i.`Start Date`,
            i.`End Date`,
            i.`Project Report`,

            GROUP_CONCAT(
                CONCAT(
                    o.organization_name,
                    ' (',
                    po.role,
                    ')'
                )
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
            i.`End Date`,
            i.`Project Report`

        ORDER BY
            i.`project ID` ASC";

$result = $conn->query($sql);

$projects = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $project_id = (int)$row['project ID'];

        /*
         * Get all organization relationships separately.
         * This allows the Edit modal to handle multiple
         * organizations and roles.
         */

        $relationship_sql = "SELECT
                                po.organization_id,
                                po.role,
                                o.organization_name
                             FROM project_organisations po
                             LEFT JOIN organization o
                                ON po.organization_id = o.organization_id
                             WHERE po.project_id = ?
                             ORDER BY po.role, o.organization_name";

        $relationship_stmt = $conn->prepare($relationship_sql);

        $relationship_data = [];

        if ($relationship_stmt) {

            $relationship_stmt->bind_param(
                "i",
                $project_id
            );

            $relationship_stmt->execute();

            $relationship_result =
                $relationship_stmt->get_result();

            while (
                $relationship =
                $relationship_result->fetch_assoc()
            ) {

                $relationship_data[] = [
                    'organization_id' =>
                        (int)$relationship['organization_id'],

                    'organization_name' =>
                        $relationship['organization_name'],

                    'role' =>
                        $relationship['role']
                ];
            }

            $relationship_stmt->close();
        }


        $row['relationships'] =
            $relationship_data;

        $projects[] = $row;
    }
}


/* =========================================================
   GROUP PROJECTS BY YEAR
========================================================= */

$projects_by_year = [];

foreach ($projects as $project) {

    $start_date = $project['Start Date'];

    if (!empty($start_date)) {

        $year = date(
            "Y",
            strtotime($start_date)
        );

    } else {

        $year = "No Date";
    }

    $projects_by_year[$year][] = $project;
}


/* =========================================================
   SORT YEARS
========================================================= */

uksort(
    $projects_by_year,
    function ($a, $b) {

        if ($a === "No Date") {
            return 1;
        }

        if ($b === "No Date") {
            return -1;
        }

        return (int)$b - (int)$a;
    }
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    UEW Innovation Hub | Project Records
</title>


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

:root {

    --blue: #102a72;
    --blue-light: #3157d5;

    --purple: #6d28d9;
    --purple-light: #8b5cf6;

    --gold: #f59e0b;
    --gold-light: #fbbf24;

    --dark: #111827;
    --text: #374151;
    --muted: #6b7280;

    --background: #f5f7fb;
    --white: #ffffff;

    --border: #e5e7eb;

    --danger: #dc2626;
    --success: #15803d;

    --shadow:
        0 10px 30px rgba(15, 23, 42, 0.08);
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        var(--background);

    color:
        var(--text);

    font-size: 16px;
}


/* =========================================================
   NAVIGATION
========================================================= */

.navbar {

    position: sticky;

    top: 0;

    z-index: 2000;

    min-height: 76px;

    padding:
        10px 5%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    color: white;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            var(--purple)
        );

    box-shadow:
        0 5px 20px
        rgba(0,0,0,0.15);
}

.brand {

    display: flex;

    align-items: center;

    gap: 13px;

    min-width: 230px;
}

.brand img {

    width: 52px;

    height: 52px;

    object-fit: contain;

    background: white;

    border-radius: 12px;

    padding: 4px;
}

.brand-text h2 {

    margin: 0;

    font-size: 20px;
}

.brand-text p {

    margin: 3px 0 0;

    font-size: 13px;

    opacity: 0.85;
}

.nav-links {

    display: flex;

    align-items: center;

    gap: 6px;

    flex-wrap: wrap;

    justify-content: flex-end;
}

.nav-links a {

    color: white;

    text-decoration: none;

    padding: 11px 16px;

    border-radius: 9px;

    font-size: 15px;

    transition:
        background .25s,
        transform .25s;
}

.nav-links a:hover {

    background:
        rgba(255,255,255,.15);

    transform:
        translateY(-1px);
}

.nav-links .active {

    background:
        rgba(255,255,255,.20);
}


/* =========================================================
   HERO
========================================================= */

.hero {

    position: relative;

    overflow: hidden;

    color: white;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            #4338ca 55%,
            var(--purple)
        );

    padding:
        58px 5% 65px;
}

.hero::before {

    content: "";

    position: absolute;

    width: 330px;

    height: 330px;

    right: -100px;

    top: -150px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);
}

.hero::after {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    left: -80px;

    bottom: -100px;

    border-radius: 50%;

    background:
        rgba(245,158,11,.14);
}

.hero-content {

    position: relative;

    z-index: 2;

    max-width: 1180px;

    margin: auto;
}

.hero small {

    letter-spacing: 2px;

    font-weight: bold;

    font-size: 13px;

    opacity: .85;
}

.hero h1 {

    margin:
        12px 0 10px;

    font-size: 42px;

    line-height: 1.1;
}

.hero p {

    max-width: 700px;

    margin: 0;

    font-size: 18px;

    line-height: 1.6;

    opacity: .9;
}

.hero-actions {

    display: flex;

    gap: 12px;

    margin-top: 25px;

    flex-wrap: wrap;
}


/* =========================================================
   BUTTONS
========================================================= */

.btn {

    border: none;

    border-radius: 10px;

    padding:
        13px 19px;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    transition:
        transform .2s,
        box-shadow .2s,
        background .2s;
}

.btn:hover {

    transform:
        translateY(-2px);
}

.btn-gold {

    background:
        var(--gold);

    color:
        #1f2937;

    box-shadow:
        0 7px 18px
        rgba(245,158,11,.25);
}

.btn-gold:hover {

    background:
        var(--gold-light);
}

.btn-white {

    background:
        white;

    color:
        var(--purple);

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.12);
}

.btn-primary {

    background:
        linear-gradient(
            135deg,
            var(--blue),
            var(--purple)
        );

    color:
        white;
}

.btn-danger {

    background:
        var(--danger);

    color:
        white;
}

.btn-secondary {

    background:
        #eef2ff;

    color:
        var(--purple);
}


/* =========================================================
   MAIN
========================================================= */

.container {

    width: 90%;

    max-width: 1180px;

    margin:
        35px auto 60px;
}


/* =========================================================
   STATS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-top: -35px;

    position: relative;

    z-index: 5;
}

.stat-card {

    background:
        white;

    border-radius: 15px;

    padding:
        22px;

    box-shadow:
        var(--shadow);

    border:
        1px solid var(--border);

    transition:
        transform .25s;
}

.stat-card:hover {

    transform:
        translateY(-4px);
}

.stat-label {

    color:
        var(--muted);

    font-size: 14px;

    margin-bottom: 8px;
}

.stat-value {

    color:
        var(--dark);

    font-size: 28px;

    font-weight:
        800;
}

.stat-icon {

    float: right;

    font-size: 25px;
}


/* =========================================================
   SEARCH PANEL
========================================================= */

.search-panel {

    margin-top: 30px;

    background:
        white;

    border:
        1px solid var(--border);

    border-radius: 15px;

    padding: 20px;

    box-shadow:
        var(--shadow);
}

.search-row {

    display: flex;

    gap: 10px;
}

.search-input {

    flex: 1;

    padding:
        14px 16px;

    border:
        1px solid #d1d5db;

    border-radius: 10px;

    font-size: 16px;

    outline: none;
}

.search-input:focus {

    border-color:
        var(--purple);

    box-shadow:
        0 0 0 3px
        rgba(109,40,217,.10);
}

.search-info {

    margin-top: 10px;

    color:
        var(--muted);

    font-size: 14px;
}


/* =========================================================
   YEAR GROUP
========================================================= */

.year-group {

    margin-top: 30px;

    background:
        white;

    border:
        1px solid var(--border);

    border-radius: 15px;

    overflow: hidden;

    box-shadow:
        var(--shadow);
}

.year-header {

    padding:
        18px 22px;

    background:
        linear-gradient(
            90deg,
            #eef2ff,
            #faf5ff
        );

    border-bottom:
        1px solid var(--border);

    display: flex;

    justify-content: space-between;

    align-items: center;

    cursor: pointer;
}

.year-header h2 {

    margin: 0;

    color:
        var(--blue);

    font-size: 21px;
}

.year-count {

    background:
        var(--gold);

    color:
        #1f2937;

    padding:
        5px 10px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;
}

.year-content {

    padding: 20px;
}


/* =========================================================
   PROJECT CARD
========================================================= */

.project-card {

    position: relative;

    background:
        white;

    border:
        1px solid var(--border);

    border-radius: 13px;

    padding:
        21px;

    margin-bottom: 15px;

    cursor: pointer;

    transition:
        transform .25s,
        box-shadow .25s,
        border-color .25s;

    animation:
        cardIn .45s ease both;
}

.project-card:last-child {

    margin-bottom: 0;
}

.project-card:hover {

    transform:
        translateY(-3px);

    border-color:
        #c4b5fd;

    box-shadow:
        0 12px 28px
        rgba(109,40,217,.10);
}

.project-top {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    align-items: flex-start;
}

.project-title {

    margin: 0;

    color:
        var(--dark);

    font-size: 20px;

    line-height: 1.3;
}

.project-id {

    display: inline-block;

    margin-top: 7px;

    color:
        var(--purple);

    background:
        #f5f3ff;

    padding:
        4px 9px;

    border-radius: 6px;

    font-size: 12px;

    font-weight: bold;
}

.project-details {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;

    margin-top: 18px;
}

.detail {

    background:
        #f9fafb;

    border-radius: 9px;

    padding:
        11px;
}

.detail-label {

    color:
        var(--muted);

    font-size: 12px;

    margin-bottom: 4px;
}

.detail-value {

    color:
        var(--dark);

    font-size: 14px;

    font-weight: bold;
}

.organisations {

    margin-top: 15px;

    padding-top: 14px;

    border-top:
        1px solid var(--border);
}

.org-label {

    color:
        var(--muted);

    font-size: 12px;

    margin-bottom: 8px;
}

.org-list {

    display: flex;

    flex-wrap: wrap;

    gap: 7px;
}

.org-badge {

    display: inline-block;

    background:
        #eef2ff;

    color:
        #4338ca;

    padding:
        7px 10px;

    border-radius: 7px;

    font-size: 13px;

    font-weight: bold;
}

.card-actions {

    display: flex;

    gap: 7px;

    margin-top: 17px;

    padding-top: 14px;

    border-top:
        1px solid var(--border);
}

.action-btn {

    border: none;

    border-radius: 8px;

    padding:
        9px 13px;

    font-size: 13px;

    font-weight: bold;

    cursor: pointer;

    text-decoration: none;
}

.action-view {

    background:
        #fff7ed;

    color:
        #c2410c;
}

.action-edit {

    background:
        #eef2ff;

    color:
        #4338ca;
}

.action-delete {

    background:
        #fef2f2;

    color:
        #b91c1c;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {

    text-align: center;

    padding:
        50px 20px;

    color:
        var(--muted);
}

.empty-state-icon {

    font-size: 45px;

    margin-bottom: 10px;
}

.empty-state h3 {

    margin:
        0 0 7px;

    color:
        var(--dark);
}


/* =========================================================
   MODAL
========================================================= */

.modal {

    display: none;

    position: fixed;

    inset: 0;

    z-index: 5000;

    background:
        rgba(15,23,42,.68);

    backdrop-filter:
        blur(4px);

    padding:
        25px;

    align-items: center;

    justify-content: center;
}

.modal.show {

    display: flex;

    animation:
        fadeIn .2s ease;
}

.modal-box {

    width:
        min(760px, 100%);

    max-height:
        92vh;

    overflow-y:
        auto;

    background:
        white;

    border-radius:
        18px;

    box-shadow:
        0 25px 70px
        rgba(0,0,0,.25);

    animation:
        modalIn .25s ease;
}

.modal-large {

    width:
        min(900px, 100%);
}

.modal-header {

    position: sticky;

    top: 0;

    z-index: 3;

    padding:
        20px 24px;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            var(--purple)
        );

    color:
        white;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.modal-header h2 {

    margin: 0;

    font-size: 21px;
}

.modal-close {

    border: none;

    width: 38px;

    height: 38px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.15);

    color:
        white;

    font-size: 22px;

    cursor: pointer;
}

.modal-body {

    padding:
        25px;
}

.modal-footer {

    padding:
        18px 25px;

    border-top:
        1px solid var(--border);

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    background:
        #fafafa;
}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 17px;
}

.form-full {

    grid-column:
        1 / -1;
}

.field {

    margin-bottom: 17px;
}

.field label {

    display: block;

    margin-bottom: 7px;

    color:
        var(--dark);

    font-size: 14px;

    font-weight: bold;
}

.field input,
.field select {

    width: 100%;

    padding:
        13px 14px;

    border:
        1px solid #d1d5db;

    border-radius: 9px;

    background:
        white;

    font-size: 15px;

    outline: none;
}

.field input:focus,
.field select:focus {

    border-color:
        var(--purple);

    box-shadow:
        0 0 0 3px
        rgba(109,40,217,.10);
}

.organization-row {

    padding:
        16px;

    margin-bottom: 12px;

    background:
        #f8f7ff;

    border:
        1px solid #ddd6fe;

    border-radius:
        11px;
}

.organization-grid {

    display: grid;

    grid-template-columns:
        1fr 190px;

    gap: 12px;
}

.org-tools {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-top: 8px;
}

.new-org-toggle {

    border: none;

    background: none;

    padding: 0;

    color:
        var(--purple);

    font-weight: bold;

    cursor: pointer;

    font-size: 13px;
}

.remove-org {

    border: none;

    background:
        #fee2e2;

    color:
        #b91c1c;

    border-radius:
        7px;

    padding:
        6px 9px;

    cursor: pointer;

    font-weight: bold;
}

.new-org-box {

    margin-top: 10px;

    display: none;
}

.new-org-box.show {

    display: block;
}

.add-org-btn {

    width: 100%;

    border:
        1px dashed var(--purple);

    background:
        #faf5ff;

    color:
        var(--purple);

    padding:
        11px;

    border-radius:
        9px;

    cursor: pointer;

    font-weight: bold;
}

.report-box {

    background:
        #fffaf0;

    border:
        1px solid #fde68a;

    border-radius:
        11px;

    padding:
        15px;
}

.current-report {

    margin-bottom:
        12px;

    padding:
        11px;

    background:
        white;

    border-radius:
        8px;

    border:
        1px solid #fde68a;

    font-size:
        14px;
}

.form-note {

    margin-top:
        7px;

    color:
        var(--muted);

    font-size:
        12px;
}


/* =========================================================
   VIEW MODAL
========================================================= */

.view-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 13px;
}

.view-item {

    padding:
        15px;

    background:
        #f8fafc;

    border:
        1px solid var(--border);

    border-radius:
        10px;
}

.view-item.full {

    grid-column:
        1 / -1;
}

.view-label {

    color:
        var(--muted);

    font-size:
        12px;

    margin-bottom:
        5px;
}

.view-value {

    color:
        var(--dark);

    font-size:
        16px;

    font-weight:
        bold;
}

.view-organizations {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        8px;

    margin-top:
        8px;
}

.view-org {

    padding:
        8px 10px;

    background:
        #eef2ff;

    color:
        #4338ca;

    border-radius:
        8px;

    font-size:
        13px;

    font-weight:
        bold;
}

.report-actions {

    display:
        flex;

    gap:
        9px;

    flex-wrap:
        wrap;

    margin-top:
        10px;
}


/* =========================================================
   LOADING
========================================================= */

.loading {

    text-align:
        center;

    padding:
        25px;

    color:
        var(--purple);

    font-weight:
        bold;
}


/* =========================================================
   FOOTER
========================================================= */

footer {

    background:
        #111c44;

    color:
        white;

    text-align:
        center;

    padding:
        18px;

    font-size:
        13px;
}

footer p {

    margin:
        0;

    opacity:
        .75;
}


/* =========================================================
   ANIMATIONS
========================================================= */

@keyframes fadeIn {

    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes modalIn {

    from {
        opacity: 0;
        transform:
            translateY(20px)
            scale(.98);
    }

    to {
        opacity: 1;
        transform:
            translateY(0)
            scale(1);
    }
}

@keyframes cardIn {

    from {
        opacity: 0;
        transform:
            translateY(10px);
    }

    to {
        opacity: 1;
        transform:
            translateY(0);
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 850px) {

    .navbar {

        flex-direction:
            column;

        align-items:
            stretch;
    }

    .brand {

        justify-content:
            center;
    }

    .nav-links {

        justify-content:
            center;
    }

    .hero h1 {

        font-size:
            34px;
    }

    .stats {

        grid-template-columns:
            1fr 1fr;
    }

    .project-details {

        grid-template-columns:
            1fr;
    }

    .organization-grid {

        grid-template-columns:
            1fr;
    }

    .form-grid {

        grid-template-columns:
            1fr;
    }

    .form-full {

        grid-column:
            auto;
    }
}

@media (max-width: 550px) {

    .hero {

        padding:
            45px 5%;
    }

    .hero h1 {

        font-size:
            29px;
    }

    .hero p {

        font-size:
            16px;
    }

    .stats {

        grid-template-columns:
            1fr;
    }

    .search-row {

        flex-direction:
            column;
    }

    .project-top {

        flex-direction:
            column;
    }

    .card-actions {

        flex-wrap:
            wrap;
    }

    .view-grid {

        grid-template-columns:
            1fr;
    }

    .view-item.full {

        grid-column:
            auto;
    }

    .modal {

        padding:
            10px;
    }

    .modal-body {

        padding:
            18px;
    }
}
/* =========================================================
   REPORT VIEWER MODAL
========================================================= */

.report-modal {
    z-index: 9999 !important;
}

.report-modal-box {
    width: 92%;
    max-width: 1200px;
    height: 90vh;
    display: flex;
    flex-direction: column;
}
.report-viewer {
    flex: 1;
    min-height: 0;
    background: #525252;
    overflow: hidden;
}

.report-viewer iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}
/* =========================================================
   PRINT PROJECTS MODAL
========================================================= */

#printModal {
    z-index: 9999 !important;
}


.print-section {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #ffffff;
}


.print-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 18px;
}


.print-section-header h3 {
    margin: 0 0 5px;
}


.print-section-header p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}


.print-table-wrapper {
    width: 100%;
    overflow-x: auto;
}


.print-projects-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}


.print-projects-table th,
.print-projects-table td {
    padding: 12px;
    border: 1px solid #e5e7eb;
    text-align: left;
    vertical-align: top;
}


.print-projects-table th {
    font-weight: 700;
    background: #f3f4f6;
}


.print-projects-table tbody tr:hover {
    background: #f9fafb;
}


.print-project-select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    margin-bottom: 20px;
}


.print-preview {
    display: none;
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #ffffff;
}


.print-preview h3 {
    margin-top: 0;
}


.print-preview-row {
    margin-bottom: 12px;
}


.print-preview-label {
    font-weight: 700;
}


.print-individual-actions {
    margin-top: 20px;
    text-align: right;
}
/* =========================================================
   PUBLICATIONS MODAL
========================================================= */

#publicationsModal {

    display: none;

    position: fixed;

    inset: 0;

    z-index: 9999;

    background: rgba(0, 0, 0, 0.55);

    align-items: center;

    justify-content: center;

    padding: 20px;

}


.publications-modal-content {

    width: min(1100px, 95%);

    max-height: 90vh;

    overflow-y: auto;

    background: #ffffff;

    border-radius: 16px;

    padding: 25px;

    box-shadow: 0 20px 60px rgba(0,0,0,0.25);

}


.modal-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 25px;

}


.modal-header h2 {

    margin: 0;

}


.modal-header p {

    margin-top: 6px;

    color: #64748b;

}


.modal-close {

    border: none;

    background: transparent;

    font-size: 30px;

    cursor: pointer;

}


.publications-toolbar {

    display: flex;

    gap: 12px;

    flex-wrap: wrap;

    margin-bottom: 25px;

}


.publications-toolbar input {

    flex: 1;

    min-width: 220px;

    padding: 12px 15px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

}


.publications-toolbar select {

    padding: 12px 15px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

}


.add-publication-btn {

    padding: 12px 18px;

    border: none;

    border-radius: 8px;

    background: #3730a3;

    color: white;

    cursor: pointer;

}


.publications-container {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(280px, 1fr));

    gap: 20px;

}

</style>

</head>


<body>


<!-- =====================================================
     NAVIGATION
===================================================== -->

<nav class="navbar">

    <div class="brand">

        <img
            src="innovation_hub_logo.jpg"
            alt="UEW Innovation Hub"
        >

        <div class="brand-text">

            <h2>
                UEW Innovation Hub
            </h2>

            <p>
                Project Management System
            </p>

        </div>

    </div>


    <div class="nav-links">

        <a href="home.php">
            Home
        </a>

        <a href="index.php" class="active">
            Projects
        </a>
 <a href="#" onclick="openAddModal(); return false;">
    + Add Project
</a>

<a href="#" onclick="openPublicationsModal(); return false;">
    Publications
</a>
    </div>

</nav>


<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="hero-content">

        <small>
            PROJECT MANAGEMENT SYSTEM
        </small>
         <h1>
            Project Records
        </h1>
 <div class="hero-actions">

            <button
                class="btn btn-gold"
                onclick="openAddModal()"
            >
                + Add New Project
            </button>

        <button
    type="button"
    class="btn btn-white"
    onclick="openPrintModal()"
>
    🖨 Print Projects
</button>

        </div>

    </div>

</section>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container">


    <!-- =================================================
         STATISTICS
    ================================================= -->

    <section class="stats">

        <div class="stat-card">

            <span class="stat-icon">
                📁
            </span>

            <div class="stat-label">
                Total Projects
            </div>

            <div class="stat-value">
                <?php echo number_format($total_projects); ?>
            </div>

        </div>


        <div class="stat-card">

            <span class="stat-icon">
                🏢
            </span>

            <div class="stat-label">
                Organisations
            </div>

            <div class="stat-value">
                <?php echo number_format($total_organisations); ?>
            </div>

        </div>


        <div class="stat-card">

            <span class="stat-icon">
                👥
            </span>

            <div class="stat-label">
                Beneficiaries
            </div>

            <div class="stat-value">
                <?php echo number_format($total_beneficiaries); ?>
            </div>

        </div>


        <div class="stat-card">

            <span class="stat-icon">
                🔗
            </span>

            <div class="stat-label">
                Linked Projects
            </div>

            <div class="stat-value">
                <?php echo number_format($linked_projects); ?>
            </div>

        </div>

    </section>


    <!-- =================================================
         SEARCH
    ================================================= -->

    <section class="search-panel">

        <div class="search-row">

            <input
                type="text"
                id="searchInput"
                class="search-input"
                placeholder="Search by project ID, title or organisation..."
                oninput="searchProjects()"
            >

            <button
                class="btn btn-secondary"
                onclick="clearSearch()"
                type="button"
            >
                Clear
            </button>

        </div>

        <div
            class="search-info"
            id="searchInfo"
        >
            Showing
            <?php echo count($projects); ?>
            project(s)
        </div>

    </section>


    <!-- =================================================
         PROJECTS
    ================================================= -->

    <div id="projectArea">

    <?php if (empty($projects)): ?>

        <div class="empty-state">

            <div class="empty-state-icon">
                📂
            </div>

            <h3>
                No projects found
            </h3>

            <p>
                Add your first Innovation Hub project.
            </p>

            <button
                class="btn btn-primary"
                onclick="openAddModal()"
            >
                + Add Project
            </button>

        </div>
         
            <button
                type="button"
                class="modal-close"
                onclick="closePublicationsModal()"
            >
                &times;
            </button>

        </div>


        <!-- SEARCH AND FILTER -->

        <div class="publications-toolbar">

            <input
                type="text"
                id="publicationSearch"
                placeholder="Search publications..."
                onkeyup="searchPublications()"
            >


            <select
                id="publicationTypeFilter"
                onchange="filterPublications()"
            >

                <option value="all">
                    All
                </option>

                <option value="Publication">
                    Publications
                </option>

                <option value="Newsletter">
                    Newsletters
                </option>

            </select>


            <button
                type="button"
                onclick="openAddPublicationModal()"
                class="add-publication-btn"
            >
                + Add Publication
            </button>

        </div>


        <!-- PUBLICATIONS WILL APPEAR HERE -->

        <div
            id="publicationsContainer"
            class="publications-container"
        >

            <div class="publications-loading">
                Loading publications...
            </div>

        </div>

    </div>

</div>

    <?php else: ?>


        <?php foreach ($projects_by_year as $year => $year_projects): ?>

            <section
                class="year-group"
                data-year-group="<?php echo htmlspecialchars($year); ?>"
            >

                <div
                    class="year-header"
                    onclick="toggleYear(this)"
                >

                    <h2>
                        <?php echo htmlspecialchars($year); ?>
                    </h2>

                    <span class="year-count">

                        <?php
                        echo count($year_projects);
                        ?>
                        Project<?php
                        echo count($year_projects) == 1 ? '' : 's';
                        ?>

                    </span>

                </div>


                <div class="year-content">

                <?php foreach ($year_projects as $project): ?>

                    <?php

                    $project_id =
                        (int)$project['project ID'];

                    $search_text =
                        $project_id
                        . ' '
                        . $project['Project Title']
                        . ' '
                        . $project['organizations'];

                    ?>


                    <article
                        class="project-card"
                        data-search="<?php
                            echo htmlspecialchars(
                                strtolower($search_text),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        onclick="openViewModal(<?php echo $project_id; ?>)"
                    >


                        <div class="project-top">

                            <div>

                                <h3 class="project-title">

                                    <?php
                                    echo htmlspecialchars(
                                        $project['Project Title']
                                    );
                                    ?>

                                </h3>

                                <span class="project-id">

                                    Project ID:
                                    <?php echo $project_id; ?>

                                </span>

                            </div>

                        </div>


                        <div class="project-details">


                            <div class="detail">

                                <div class="detail-label">
                                    Beneficiaries
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo number_format(
                                        (int)$project[
                                            'Number of Beneficiaries'
                                        ]
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="detail">

                                <div class="detail-label">
                                    Start Date
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo !empty(
                                        $project['Start Date']
                                    )
                                    ?
                                    htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $project['Start Date']
                                            )
                                        )
                                    )
                                    :
                                    'Not specified';

                                    ?>

                                </div>

                            </div>


                            <div class="detail">

                                <div class="detail-label">
                                    End Date
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo !empty(
                                        $project['End Date']
                                    )
                                    ?
                                    htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $project['End Date']
                                            )
                                        )
                                    )
                                    :
                                    'Not specified';

                                    ?>

                                </div>

                            </div>


                        </div>


                        <div class="organisations">

                            <div class="org-label">
                                Organisations & Roles
                            </div>

                            <div class="org-list">

                            <?php if (!empty($project['relationships'])): ?>

                                <?php foreach ($project['relationships'] as $rel): ?>

                                    <span class="org-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $rel['organization_name']
                                        );
                                        ?>

                                        —

                                        <?php
                                        echo htmlspecialchars(
                                            $rel['role']
                                        );
                                        ?>

                                    </span>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <span
                                    class="org-badge"
                                    style="background:#f3f4f6;color:#6b7280;"
                                >
                                    No organisation linked
                                </span>

                            <?php endif; ?>

                            </div>

                        </div>


                        <div
                            class="card-actions"
                            onclick="event.stopPropagation();"
                        >

                            <button
                                class="action-btn action-view"
                                type="button"
                                onclick="openViewModal(<?php echo $project_id; ?>)"
                            >
                                👁 View
                            </button>


                            <button
                                class="action-btn action-edit"
                                type="button"
                                onclick="openEditModal(<?php echo $project_id; ?>)"
                            >
                                ✏️ Edit
                            </button>


                            <button
                                class="action-btn action-delete"
                                type="button"
                                onclick="openDeleteModal(<?php echo $project_id; ?>, <?php echo htmlspecialchars(json_encode($project['Project Title']), ENT_QUOTES, 'UTF-8'); ?>)"
                            >
                                🗑 Delete
                            </button>

                        </div>

                    </article>

                <?php endforeach; ?>

                </div>

            </section>

        <?php endforeach; ?>


        <div
            id="noSearchResults"
            class="empty-state"
            style="display:none;"
        >

            <div class="empty-state-icon">
                🔎
            </div>

            <h3>
                No matching projects
            </h3>

            <p>
                Try a different project title, ID or organisation.
            </p>

        </div>


    <?php endif; ?>

    </div>

</main>
<!-- =========================================================
     PUBLICATIONS MODAL
========================================================= -->

<div
    id="publicationsModal"
    class="modal"
    onclick="closeModalOutside(event, 'publicationsModal')"
>

    <div class="modal-content publications-modal-content">

        <div class="modal-header">

            <div>
                <h2>Publications</h2>

                <p>
                    Newsletters and publications from the
                    UEW Innovation Hub
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closePublicationsModal()"
            >
                &times;
            </button>

        </div>

        <div class="publications-toolbar">

            <input
                type="text"
                id="publicationSearch"
                placeholder="Search publications..."
                onkeyup="searchPublications()"
            >

            <select
                id="publicationTypeFilter"
                onchange="filterPublications()"
            >
                <option value="all">
                    All
                </option>

                <option value="Publication">
                    Publications
                </option>

                <option value="Newsletter">
                    Newsletters
                </option>
            </select>

            <button
                type="button"
                onclick="openAddPublicationModal()"
                class="add-publication-btn"
            >
                + Add Publication
            </button>

        </div>

        <div
            id="publicationsContainer"
            class="publications-container"
        >

            <div class="publications-loading">
                Loading publications...
            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     VIEW PROJECT MODAL
===================================================== -->

<div
    class="modal"
    id="viewModal"
    onclick="closeModalOutside(event, 'viewModal')"
>

    <div class="modal-box modal-large">

        <div class="modal-header">

            <h2>
                Project Details
            </h2>

            <button
                class="modal-close"
                onclick="closeModal('viewModal')"
                type="button"
            >
                ×
            </button>

        </div>


        <div
            class="modal-body"
            id="viewModalBody"
        >

            <div class="loading">
                Loading project...
            </div>

        </div>


        <div class="modal-footer">

            <button
                class="btn btn-secondary"
                onclick="closeModal('viewModal')"
                type="button"
            >
                Close
            </button>

            <button
                class="btn btn-primary"
                id="viewEditButton"
                type="button"
            >
                ✏️ Edit Project
            </button>

        </div>

    </div>

</div>
 <!-- =====================================================
     VIEW PROJECT MODAL
===================================================== -->

<div
    class="modal"
    id="viewModal"
    onclick="closeModalOutside(event, 'viewModal')"
>

    <div class="modal-box modal-large">

        <div class="modal-header">

            <h2>
                Project Details
            </h2>

            <button
                class="modal-close"
                onclick="closeModal('viewModal')"
                type="button"
            >
                ×
            </button>

        </div>

        <div
            class="modal-body"
            id="viewModalBody"
        >

            <div class="loading">
                Loading project...
            </div>

        </div>

        <div class="modal-footer">

            <button
                class="btn btn-secondary"
                onclick="closeModal('viewModal')"
                type="button"
            >
                Close
            </button>

            <button
                class="btn btn-primary"
                id="viewEditButton"
                type="button"
            >
                ✏️ Edit Project
            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     VIEW REPORT MODAL
===================================================== -->

<div
    class="modal report-modal"
    id="reportModal"
    onclick="closeModalOutside(event, 'reportModal')"
>

    <div class="modal-box report-modal-box">

        <div class="modal-header">

            <h2>
                📄 Project Report
            </h2>

            <button
                class="modal-close"
                onclick="closeReportModal()"
                type="button"
            >
                ×
            </button>

        </div>

        <div class="report-viewer">

            <iframe
                id="reportFrame"
                src=""
                title="Project Report"
            ></iframe>

        </div>

        <div class="modal-footer">

            <a
                id="reportDownloadButton"
                href="#"
                download
                class="btn btn-primary"
            >
                ⬇ Download Report
            </a>

            <button
                class="btn btn-secondary"
                onclick="closeReportModal()"
                type="button"
            >
                Close
            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     PRINT PROJECTS MODAL
===================================================== -->

<div
    class="modal"
    id="printModal"
    onclick="closeModalOutside(event, 'printModal')"
>

    <div class="modal-box modal-large">

        <div class="modal-header">

            <h2>
                🖨 Print Projects
            </h2>

            <button
                class="modal-close"
                onclick="closePrintModal()"
                type="button"
            >
                ×
            </button>

        </div>

        <div class="modal-body">

            <!-- =========================================
                 ALL PROJECTS
            ========================================== -->

            <div class="print-section">

                <div class="print-section-header">

                    <div>

                        <h3>
                            All Projects
                        </h3>

                        <p>
                            View all projects in table form
                            before printing.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="printAllProjects()"
                    >
                        🖨 Print All Projects
                    </button>

                </div>

                <div class="print-table-wrapper">

                    <table class="print-projects-table">

                        <thead>

                            <tr>

                                <th>
                                    Project ID
                                </th>

                                <th>
                                    Project Title
                                </th>

                                <th>
                                    Beneficiaries
                                </th>

                                <th>
                                    Start Date
                                </th>

                                <th>
                                    End Date
                                </th>

                                <th>
                                    Organisations
                                </th>

                            </tr>

                        </thead>

                        <tbody
                            id="printProjectsTableBody"
                        >
                        </tbody>

                    </table>

                </div>

            </div>


            <!-- =========================================
                 INDIVIDUAL PROJECT
            ========================================== -->

            <div class="print-section">

                <div class="print-section-header">

                    <div>

                        <h3>
                            Individual Project
                        </h3>

                        <p>
                            Select a project to preview
                            and print its details.
                        </p>

                    </div>

                </div>

                <select
                    id="printProjectSelect"
                    class="print-project-select"
                >

                    <option value="">
                        -- Select a Project --
                    </option>

                </select>

                <div
                    id="printPreview"
                    class="print-preview"
                >

                    <p>
                        Select a project to see its
                        preview.
                    </p>

                </div>

                <div class="print-individual-actions">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="printSelectedProjectButton"
                        disabled
                    >
                        🖨 Print Selected Project
                    </button>

                </div>

            </div>

        </div>

        <div class="modal-footer">

            <button
                class="btn btn-secondary"
                onclick="closePrintModal()"
                type="button"
            >
                Close
            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     ADD PROJECT MODAL
===================================================== -->

<div
    class="modal"
    id="addModal"
    onclick="closeModalOutside(event, 'addModal')"
>

    <div class="modal-box modal-large">

        <form
            id="addProjectForm"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="ajax"
                value="1"
            >

            <div class="modal-header">

                <h2>
                    + Add New Project
                </h2>

                <button
                    class="modal-close"
                    type="button"
                    onclick="closeModal('addModal')"
                >
                    ×
                </button>

            </div>

            <div class="modal-body">

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

                <h3>
                    Organisations & Roles
                </h3>

                <div id="addOrganizationContainer">

                    <?php
                    includeOrganizationRow(
                        'add',
                        0,
                        $organizations
                    );
                    ?>

                </div>

                <button
                    type="button"
                    class="add-org-btn"
                    onclick="addOrganizationRow('add')"
                >
                    + Add Another Organisation
                </button>

                <h3 style="margin-top:25px;">
                    Project Information
                </h3>

                <div class="form-grid">

                    <div class="field">

                        <label>
                            Number of Beneficiaries
                        </label>

                        <input
                            type="number"
                            name="beneficiaries"
                            min="0"
                            value="0"
                        >

                    </div>

                    <div></div>

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

                <h3>
                    Project Report
                </h3>

                <div class="report-box">

                    <div class="field">

                        <label>
                            Project Report (PDF)
                        </label>

                        <input
                            type="file"
                            name="project_report"
                            accept=".pdf,application/pdf"
                        >

                        <div class="form-note">
                            Optional. Only PDF files are accepted.
                        </div>

                    </div>

                </div>

                <div
                    id="addMessage"
                    style="display:none;margin-top:15px;"
                ></div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('addModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="addSubmitButton"
                >
                    ✓ Add Project
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     EDIT PROJECT MODAL
===================================================== -->

<div
    class="modal"
    id="editModal"
    onclick="closeModalOutside(event, 'editModal')"
>

    <div class="modal-box modal-large">

        <form
            id="editProjectForm"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="ajax"
                value="1"
            >

            <input
                type="hidden"
                name="id"
                id="editProjectId"
            >

            <div class="modal-header">

                <h2>
                    ✏️ Edit Project
                </h2>

                <button
                    class="modal-close"
                    type="button"
                    onclick="closeModal('editModal')"
                >
                    ×
                </button>

            </div>

            <div
                class="modal-body"
                id="editModalBody"
            >

                <div class="loading">
                    Loading project...
                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal('editModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="editSubmitButton"
                >
                    ✓ Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     DELETE MODAL
===================================================== -->

<div
    class="modal"
    id="deleteModal"
    onclick="closeModalOutside(event, 'deleteModal')"
>

    <div class="modal-box">

        <div class="modal-header">

            <h2>
                Delete Project
            </h2>

            <button
                class="modal-close"
                onclick="closeModal('deleteModal')"
                type="button"
            >
                ×
            </button>

        </div>

        <div class="modal-body">

            <div
                style="
                    text-align:center;
                    font-size:45px;
                    margin-bottom:10px;
                "
            >
                ⚠️
            </div>

            <h3
                style="
                    text-align:center;
                    margin:0 0 10px;
                "
            >
                Are you sure?
            </h3>

            <p
                style="
                    text-align:center;
                    color:#6b7280;
                    line-height:1.6;
                "
            >

                You are about to delete:

                <strong
                    id="deleteProjectTitle"
                >
                </strong>

                <br>

                This will remove the project and
                its organisation relationships.

            </p>

            <div
                id="deleteMessage"
                style="display:none;"
            ></div>

        </div>

        <div class="modal-footer">

            <button
                type="button"
                class="btn btn-secondary"
                onclick="closeModal('deleteModal')"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn btn-danger"
                id="confirmDeleteButton"
            >
                Yes, Delete
            </button>

        </div>

    </div>

</div>

<footer>

    <p>
        UEW Innovation Hub • Project Management System
    </p>

</footer>


<script>
/* =========================================================
   PUBLICATIONS MODAL
========================================================= */
  function openPublicationsModal() {

    openModal('publicationsModal');

}

function closePublicationsModal() {

    closeModal('publicationsModal');

}
/* =========================================================
   PROJECT DATA FROM PHP
========================================================= */

const projects = <?php

echo json_encode(
    $projects,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

?>;

const organizations = <?php

echo json_encode(
    $organizations,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

?>;


/* =========================================================
   MODAL HELPERS
========================================================= */

function openModal(id) {

    const modal =
        document.getElementById(id);

    modal.classList.add('show');

    document.body.style.overflow =
        'hidden';
}


function closeModal(id) {

    const modal =
        document.getElementById(id);

    modal.classList.remove('show');


    /*
     * If another modal is still open,
     * keep the page locked.
     */

    if (
        document.querySelector(
            '.modal.show'
        )
    ) {

        document.body.style.overflow =
            'hidden';

    } else {

        document.body.style.overflow =
            '';
    }
}


function closeModalOutside(event, id) {

    if (event.target.id === id) {

        closeModal(id);
    }
}


document.addEventListener(
    'keydown',
    function(event) {

        if (event.key === 'Escape') {

            document.querySelectorAll(
                '.modal.show'
            ).forEach(function(modal) {

                modal.classList.remove(
                    'show'
                );

            });

            document.body.style.overflow =
                '';
        }

    }
);


/* =========================================================
   FIND PROJECT
========================================================= */

function findProject(id) {

    return projects.find(
        project =>
            Number(project['project ID']) ===
            Number(id)
    );
}


/* =========================================================
   VIEW PROJECT
========================================================= */

function openViewModal(id) {

    const project =
        findProject(id);

    if (!project) {
        return;
    }

    const body =
        document.getElementById(
            'viewModalBody'
        );


    let organizationsHTML = '';


    if (
        project.relationships &&
        project.relationships.length
    ) {

        organizationsHTML =
            '<div class="view-organizations">';

        project.relationships.forEach(
            function(org) {

                organizationsHTML +=
                    '<span class="view-org">' +
                    escapeHTML(
                        org.organization_name
                    ) +
                    ' — ' +
                    escapeHTML(
                        org.role
                    ) +
                    '</span>';

            }
        );

        organizationsHTML +=
            '</div>';

    } else {

        organizationsHTML =
            '<p style="color:#6b7280;">' +
            'No organisation linked.' +
            '</p>';
    }


    let reportHTML = '';


    if (project['Project Report']) {

        const report =
            project['Project Report'];

        reportHTML = `

            <div class="report-actions">

                <button
    type="button"
    class="btn btn-secondary"
    onclick="openReportModal('${escapeAttribute(report)}')"
>
    👁 View Report
</button>

            </div>

        `;

    } else {

        reportHTML = `

            <p
                style="
                    color:#6b7280;
                    margin:8px 0 0;
                "
            >
                No project report uploaded.
            </p>

        `;
    }


    body.innerHTML = `

        <div class="view-grid">

            <div class="view-item">

                <div class="view-label">
                    Project ID
                </div>

                <div class="view-value">
                    ${escapeHTML(project['project ID'])}
                </div>

            </div>


            <div class="view-item">

                <div class="view-label">
                    Beneficiaries
                </div>

                <div class="view-value">
                    ${Number(
                        project['Number of Beneficiaries'] || 0
                    ).toLocaleString()}
                </div>

            </div>


            <div class="view-item full">

                <div class="view-label">
                    Project Title
                </div>

                <div class="view-value">
                    ${escapeHTML(project['Project Title'])}
                </div>

            </div>


            <div class="view-item">

                <div class="view-label">
                    Start Date
                </div>

                <div class="view-value">
                    ${formatDate(project['Start Date'])}
                </div>

            </div>


            <div class="view-item">

                <div class="view-label">
                    End Date
                </div>

                <div class="view-value">
                    ${formatDate(project['End Date'])}
                </div>

            </div>


            <div class="view-item full">

                <div class="view-label">
                    Organisations & Roles
                </div>

                ${organizationsHTML}

            </div>


            <div class="view-item full">

                <div class="view-label">
                    Project Report
                </div>

                ${reportHTML}

            </div>

        </div>

    `;


    document.getElementById(
        'viewEditButton'
    ).onclick = function() {

        closeModal('viewModal');

        openEditModal(id);
    };


    openModal('viewModal');
}

 /* =========================================================
   REPORT MODAL
========================================================= */

function openReportModal(report) {

    if (!report) {
        return;
    }

    const reportURL =
        'reports/' +
        encodeURIComponent(report);

    const frame =
        document.getElementById(
            'reportFrame'
        );

    const downloadButton =
        document.getElementById(
            'reportDownloadButton'
        );

    frame.src = reportURL;

    downloadButton.href =
        reportURL;

    document.getElementById(
        'reportModal'
    ).classList.add('show');

    document.body.style.overflow =
        'hidden';
}


function closeReportModal() { 


    const modal =
        document.getElementById(
            'reportModal'
        );

    const frame =
        document.getElementById(
            'reportFrame'
        );

    modal.classList.remove('show');

    frame.src = '';

    /*
     * Keep the page locked if
     * another modal is still open.
     */

    if (
        document.querySelector(
            '.modal.show'
        )
    ) {

        document.body.style.overflow =
            'hidden';

    } else {

        document.body.style.overflow =
            '';
    }
}
/* =========================================================
   PRINT PROJECTS
========================================================= */

function openPrintModal() {

    const tableBody =
        document.getElementById(
            'printProjectsTableBody'
        );

    const projectSelect =
        document.getElementById(
            'printProjectSelect'
        );

    const preview =
        document.getElementById(
            'printPreview'
        );

    const printButton =
        document.getElementById(
            'printSelectedProjectButton'
        );


    /*
     * Clear old data
     */

    tableBody.innerHTML = '';

    projectSelect.innerHTML = `
        <option value="">
            -- Select a Project --
        </option>
    `;

    preview.style.display = 'none';

    preview.innerHTML = `
        <p>
            Select a project to see its preview.
        </p>
    `;

    printButton.disabled = true;


    /*
     * Check projects
     */

    if (
        !projects ||
        projects.length === 0
    ) {

        tableBody.innerHTML = `

            <tr>

                <td
                    colspan="6"
                    style="
                        text-align:center;
                        color:#6b7280;
                    "
                >
                    No projects available.
                </td>

            </tr>

        `;

        openModal('printModal');

        return;
    }


    /*
     * Add every project
     * to the table and dropdown.
     */

    projects.forEach(
        function(project) {

            let organisations = '—';


            if (
                project.relationships &&
                project.relationships.length
            ) {

                organisations =
                    project.relationships
                        .map(
                            function(org) {

                                return (
                                    escapeHTML(
                                        org.organization_name
                                    ) +
                                    ' — ' +
                                    escapeHTML(
                                        org.role
                                    )
                                );

                            }
                        )
                        .join('<br>');

            }


            /*
             * Add to All Projects table
             */

            tableBody.innerHTML += `

                <tr>

                    <td>
                        ${escapeHTML(
                            project['project ID']
                        )}
                    </td>

                    <td>
                        ${escapeHTML(
                            project['Project Title']
                        )}
                    </td>

                    <td>
                        ${Number(
                            project[
                                'Number of Beneficiaries'
                            ] || 0
                        ).toLocaleString()}
                    </td>

                    <td>
                        ${formatDate(
                            project['Start Date']
                        )}
                    </td>

                    <td>
                        ${formatDate(
                            project['End Date']
                        )}
                    </td>

                    <td>
                        ${organisations}
                    </td>

                </tr>

            `;


            /*
             * Add to individual
             * project dropdown
             */

            projectSelect.innerHTML += `

                <option
                    value="${escapeAttribute(
                        project['project ID']
                    )}"
                >
                    ${escapeHTML(
                        project['Project Title']
                    )}
                </option>

            `;

        }
    );


    /*
     * Project selection
     */

    projectSelect.onchange =
        function() {

            showPrintPreview(
                this.value
            );

        };


    /*
     * Print selected project
     */

    printButton.onclick =
        function() {

            printSelectedProject();

        };


    openModal('printModal');
}


/* =========================================================
   CLOSE PRINT MODAL
========================================================= */

function closePrintModal() {

    const modal =
        document.getElementById(
            'printModal'
        );

    modal.classList.remove('show');


    if (
        document.querySelector(
            '.modal.show'
        )
    ) {

        document.body.style.overflow =
            'hidden';

    } else {

        document.body.style.overflow =
            '';
    }
}


/* =========================================================
   SHOW PRINT PREVIEW
========================================================= */

function showPrintPreview(id) {

    const preview =
        document.getElementById(
            'printPreview'
        );

    const printButton =
        document.getElementById(
            'printSelectedProjectButton'
        );


    if (!id) {

        preview.style.display =
            'none';

        printButton.disabled =
            true;

        return;
    }


    const project =
        findProject(id);


    if (!project) {

        preview.style.display =
            'none';

        printButton.disabled =
            true;

        return;
    }


    let organisationsHTML =
        '<p>No organisation linked.</p>';


    if (
        project.relationships &&
        project.relationships.length
    ) {

        organisationsHTML =
            project.relationships
                .map(
                    function(org) {

                        return `
                            <div>
                                <strong>
                                    ${escapeHTML(
                                        org.organization_name
                                    )}
                                </strong>

                                —
                                ${escapeHTML(
                                    org.role
                                )}
                            </div>
                        `;

                    }
                )
                .join('');

    }


    const reportHTML =
        project['Project Report']
        ?
        `
            <p>
                <strong>Project Report:</strong>
                ${escapeHTML(
                    project['Project Report']
                )}
            </p>
        `
        :
        `
            <p>
                <strong>Project Report:</strong>
                No report uploaded.
            </p>
        `;


    preview.innerHTML = `

        <h3>
            ${escapeHTML(
                project['Project Title']
            )}
        </h3>


        <div class="print-preview-row">

            <span class="print-preview-label">
                Project ID:
            </span>

            ${escapeHTML(
                project['project ID']
            )}

        </div>


        <div class="print-preview-row">

            <span class="print-preview-label">
                Number of Beneficiaries:
            </span>

            ${Number(
                project[
                    'Number of Beneficiaries'
                ] || 0
            ).toLocaleString()}

        </div>


        <div class="print-preview-row">

            <span class="print-preview-label">
                Start Date:
            </span>

            ${formatDate(
                project['Start Date']
            )}

        </div>


        <div class="print-preview-row">

            <span class="print-preview-label">
                End Date:
            </span>

            ${formatDate(
                project['End Date']
            )}

        </div>


        <div class="print-preview-row">

            <span class="print-preview-label">
                Organisations & Roles:
            </span>

            <div style="margin-top:6px;">
                ${organisationsHTML}
            </div>

        </div>


        <div class="print-preview-row">

            ${reportHTML}

        </div>

    `;


    preview.style.display =
        'block';

    printButton.disabled =
        false;
}


/* =========================================================
   PRINT ALL PROJECTS
========================================================= */

function printAllProjects() {

    if (
        !projects ||
        projects.length === 0
    ) {

        return;
    }


    let rows = '';


    projects.forEach(
        function(project) {

            let organisations =
                '—';


            if (
                project.relationships &&
                project.relationships.length
            ) {

                organisations =
                    project.relationships
                        .map(
                            function(org) {

                                return (
                                    escapeHTML(
                                        org.organization_name
                                    ) +
                                    ' — ' +
                                    escapeHTML(
                                        org.role
                                    )
                                );

                            }
                        )
                        .join('<br>');

            }


            rows += `

                <tr>

                    <td>
                        ${escapeHTML(
                            project['project ID']
                        )}
                    </td>

                    <td>
                        ${escapeHTML(
                            project['Project Title']
                        )}
                    </td>

                    <td>
                        ${Number(
                            project[
                                'Number of Beneficiaries'
                            ] || 0
                        ).toLocaleString()}
                    </td>

                    <td>
                        ${formatDate(
                            project['Start Date']
                        )}
                    </td>

                    <td>
                        ${formatDate(
                            project['End Date']
                        )}
                    </td>

                    <td>
                        ${organisations}
                    </td>

                </tr>

            `;

        }
    );


    const printWindow =
        window.open(
            '',
            '_blank'
        );


    if (!printWindow) {

        alert(
            'Please allow pop-ups in your browser to print projects.'
        );

        return;
    }


    printWindow.document.write(`

        <!DOCTYPE html>

        <html>

        <head>

            <title>
                UEW Innovation Hub - All Projects
            </title>

            <style>

                body {
                    font-family: Arial, sans-serif;
                    padding: 30px;
                    color: #111827;
                }

                h1 {
                    text-align: center;
                    margin-bottom: 5px;
                }

                .subtitle {
                    text-align: center;
                    color: #6b7280;
                    margin-bottom: 25px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                th,
                td {
                    border: 1px solid #333;
                    padding: 8px;
                    text-align: left;
                    vertical-align: top;
                }

                th {
                    background: #f3f4f6;
                }

                @media print {

                    body {
                        padding: 10px;
                    }

                }

            </style>

        </head>

        <body>

            <h1>
                UEW Innovation Hub
            </h1>

            <div class="subtitle">
                All Projects
            </div>

            <table>

                <thead>

                    <tr>

                        <th>
                            Project ID
                        </th>

                        <th>
                            Project Title
                        </th>

                        <th>
                            Beneficiaries
                        </th>

                        <th>
                            Start Date
                        </th>

                        <th>
                            End Date
                        </th>

                        <th>
                            Organisations
                        </th>

                    </tr>

                </thead>

                <tbody>

                    ${rows}

                </tbody>

            </table>

        </body>

        </html>

    `);


    printWindow.document.close();


    printWindow.focus();


    setTimeout(
        function() {

            printWindow.print();

        },
        300
    );
}


/* =========================================================
   PRINT SELECTED PROJECT
========================================================= */

function printSelectedProject() {

    const select =
        document.getElementById(
            'printProjectSelect'
        );


    const id =
        select.value;


    if (!id) {

        return;
    }


    const project =
        findProject(id);


    if (!project) {

        return;
    }


    let organisationsHTML =
        'No organisation linked.';


    if (
        project.relationships &&
        project.relationships.length
    ) {

        organisationsHTML =
            project.relationships
                .map(
                    function(org) {

                        return `
                            <div>
                                ${escapeHTML(
                                    org.organization_name
                                )}
                                —
                                ${escapeHTML(
                                    org.role
                                )}
                            </div>
                        `;

                    }
                )
                .join('');

    }


    const reportHTML =
        project['Project Report']
        ?
        `
            <div class="detail">

                <strong>
                    Project Report:
                </strong>

                ${escapeHTML(
                    project['Project Report']
                )}

            </div>
        `
        :
        `
            <div class="detail">

                <strong>
                    Project Report:
                </strong>

                No report uploaded.

            </div>
        `;


    const printWindow =
        window.open(
            '',
            '_blank'
        );


    if (!printWindow) {

        alert(
            'Please allow pop-ups in your browser to print the project.'
        );

        return;
    }


    printWindow.document.write(`

        <!DOCTYPE html>

        <html>

        <head>

            <title>
                ${escapeHTML(
                    project['Project Title']
                )}
            </title>

            <style>

                body {
                    font-family: Arial, sans-serif;
                    padding: 40px;
                    color: #111827;
                }

                h1 {
                    text-align: center;
                    margin-bottom: 5px;
                }

                .subtitle {
                    text-align: center;
                    color: #6b7280;
                    margin-bottom: 35px;
                }

                .detail {
                    padding: 12px 0;
                    border-bottom: 1px solid #e5e7eb;
                    line-height: 1.6;
                }

                .organisation {
                    margin-bottom: 5px;
                }

                @media print {

                    body {
                        padding: 20px;
                    }

                }

            </style>

        </head>

        <body>

            <h1>
                UEW Innovation Hub
            </h1>

            <div class="subtitle">
                Project Details
            </div>


            <div class="detail">

                <strong>
                    Project ID:
                </strong>

                ${escapeHTML(
                    project['project ID']
                )}

            </div>


            <div class="detail">

                <strong>
                    Project Title:
                </strong>

                ${escapeHTML(
                    project['Project Title']
                )}

            </div>


            <div class="detail">

                <strong>
                    Number of Beneficiaries:
                </strong>

                ${Number(
                    project[
                        'Number of Beneficiaries'
                    ] || 0
                ).toLocaleString()}

            </div>


            <div class="detail">

                <strong>
                    Start Date:
                </strong>

                ${formatDate(
                    project['Start Date']
                )}

            </div>


            <div class="detail">

                <strong>
                    End Date:
                </strong>

                ${formatDate(
                    project['End Date']
                )}

            </div>


            <div class="detail">

                <strong>
                    Organisations & Roles:
                </strong>

                <div style="margin-top:8px;">
                    ${organisationsHTML}
                </div>

            </div>


            ${reportHTML}

        </body>

        </html>

    `);


    printWindow.document.close();


    printWindow.focus();


    setTimeout(
        function() {

            printWindow.print();

        },
        300
    );
}
/* =========================================================
   ADD PROJECT
========================================================= */

function openAddModal() {

    const form =
        document.getElementById(
            'addProjectForm'
        );

    form.reset();


    const container =
        document.getElementById(
            'addOrganizationContainer'
        );

    container.innerHTML =
        createOrganizationRowHTML(
            'add',
            0
        );


    document.getElementById(
        'addMessage'
    ).style.display = 'none';


    openModal('addModal');
}


/* =========================================================
   CREATE ORGANIZATION ROW
========================================================= */

function createOrganizationRowHTML(
    type,
    index,
    selectedId = '',
    selectedRole = '',
    existingNewOrg = ''
) {

    let options = `

        <option value="">
            -- Select Existing Organisation --
        </option>

    `;


    organizations.forEach(
        function(org) {

            const selected =
                String(
                    org.organization_id
                ) === String(selectedId)
                ? 'selected'
                : '';

            options += `

                <option
                    value="${escapeAttribute(
                        org.organization_id
                    )}"
                    ${selected}
                >
                    ${escapeHTML(
                        org.organization_name
                    )}
                </option>

            `;
        }
    );


    const sponsorSelected =
        selectedRole === 'Sponsor'
        ? 'selected'
        : '';

    const partnerSelected =
        selectedRole === 'Partner'
        ? 'selected'
        : '';

    const organizerSelected =
        selectedRole === 'Organizer'
        ? 'selected'
        : '';


    return `

        <div
            class="organization-row"
            data-org-row="${type}-${index}"
        >

            <div class="organization-grid">

                <div>

                    <div class="field">

                        <label>
                            Organisation
                        </label>

                        <select
                            name="organization_id[]"
                            class="${type}-organization-select"
                        >

                            ${options}

                        </select>

                    </div>


                    <button
                        type="button"
                        class="new-org-toggle"
                        onclick="toggleNewOrganization(this)"
                    >
                        + Enter New Organisation
                    </button>


                    <div class="new-org-box">

                        <div class="field">

                            <label>
                                New Organisation Name
                            </label>

                            <input
                                type="text"
                                name="new_organization[]"
                                value="${escapeAttribute(
                                    existingNewOrg
                                )}"
                                placeholder="Enter organisation name"
                            >

                        </div>

                    </div>

                </div>


                <div>

                    <div class="field">

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

                            <option
                                value="Sponsor"
                                ${sponsorSelected}
                            >
                                Sponsor
                            </option>

                            <option
                                value="Partner"
                                ${partnerSelected}
                            >
                                Partner
                            </option>

                            <option
                                value="Organizer"
                                ${organizerSelected}
                            >
                                Organizer
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <div class="org-tools">

                <span
                    style="
                        font-size:12px;
                        color:#6b7280;
                    "
                >
                    Select an existing organisation or enter a new one.
                </span>


                ${
                    index > 0
                    ?
                    `
                    <button
                        type="button"
                        class="remove-org"
                        onclick="removeOrganizationRow(this)"
                    >
                        Remove
                    </button>
                    `
                    :
                    ''
                }

            </div>

        </div>

    `;
}


/* =========================================================
   ADD ORGANIZATION ROW
========================================================= */

function addOrganizationRow(type) {

    const container =
        document.getElementById(
            type === 'add'
            ?
            'addOrganizationContainer'
            :
            'editOrganizationContainer'
        );


    const index =
        container.children.length;


    container.insertAdjacentHTML(
        'beforeend',
        createOrganizationRowHTML(
            type,
            index
        )
    );
}


/* =========================================================
   REMOVE ORGANIZATION ROW
========================================================= */

function removeOrganizationRow(button) {

    const row =
        button.closest(
            '.organization-row'
        );

    if (row) {

        row.remove();
    }
}


/* =========================================================
   NEW ORGANIZATION
========================================================= */

function toggleNewOrganization(button) {

    const row =
        button.closest(
            '.organization-row'
        );

    const select =
        row.querySelector(
            'select[name="organization_id[]"]'
        );

    const box =
        row.querySelector(
            '.new-org-box'
        );

    const input =
        box.querySelector(
            'input[name="new_organization[]"]'
        );


    if (
        box.classList.contains('show')
    ) {

        box.classList.remove('show');

        select.disabled = false;

        input.value = '';

        input.required = false;

        button.textContent =
            '+ Enter New Organisation';

    } else {

        box.classList.add('show');

        select.value = '';

        select.disabled = true;

        input.required = true;

        button.textContent =
            '− Use Existing Organisation';
    }
}


/* =========================================================
   ADD FORM SUBMISSION
========================================================= */

document.getElementById(
    'addProjectForm'
).addEventListener(
    'submit',
    async function(event) {

        event.preventDefault();


        const form = this;

        const button =
            document.getElementById(
                'addSubmitButton'
            );

        const message =
            document.getElementById(
                'addMessage'
            );


        button.disabled = true;

        button.textContent =
            'Adding Project...';


        message.style.display =
            'none';


        try {

            const formData =
    new FormData(form);

formData.append(
    'ajax',
    '1'
);


const response =
    await fetch(
        'add_project.php',
        {
            method: 'POST',
            body: formData
        }
    );

const responseText =
    await response.text();

console.log(
    'ADD PHP RESPONSE:',
    responseText
);

let data;

try {

    data = JSON.parse(responseText);

} catch (error) {

    throw new Error(
        'PHP returned: ' +
        (responseText || '[EMPTY RESPONSE]')
    );
}


            message.innerHTML = `

                <div
                    style="
                        padding:13px;
                        background:#f0fdf4;
                        color:#166534;
                        border-left:4px solid #22c55e;
                        border-radius:7px;
                    "
                >
                    ✓ ${escapeHTML(data.message)}
                </div>

            `;

            message.style.display =
                'block';


            setTimeout(
                function() {

                    window.location.reload();

                },
                700
            );


        } catch (error) {

            message.innerHTML = `

                <div
                    style="
                        padding:13px;
                        background:#fef2f2;
                        color:#991b1b;
                        border-left:4px solid #ef4444;
                        border-radius:7px;
                    "
                >
                    ${escapeHTML(error.message)}
                </div>

            `;

            message.style.display =
                'block';

            button.disabled = false;

            button.textContent =
                '✓ Add Project';
        }

    }
);


/* =========================================================
   EDIT PROJECT
========================================================= */

function openEditModal(id) {

    const project =
        findProject(id);

    if (!project) {
        return;
    }


    document.getElementById(
        'editProjectId'
    ).value = id;


    const body =
        document.getElementById(
            'editModalBody'
        );


    let organizationsHTML = '';

    if (
        project.relationships &&
        project.relationships.length
    ) {

        project.relationships.forEach(
            function(org, index) {

                organizationsHTML +=
                    createOrganizationRowHTML(
                        'edit',
                        index,
                        org.organization_id,
                        org.role
                    );

            }
        );

    } else {

        organizationsHTML =
            createOrganizationRowHTML(
                'edit',
                0
            );
    }


    body.innerHTML = `

        <div class="field">

            <label>
                Project ID
            </label>

            <input
                type="text"
                value="${escapeAttribute(
                    project['project ID']
                )}"
                disabled
            >

        </div>


        <div class="field">

            <label>
                Project Title
            </label>

            <input
                type="text"
                name="project_title"
                value="${escapeAttribute(
                    project['Project Title']
                )}"
                required
            >

        </div>


        <h3>
            Organisations & Roles
        </h3>


        <div id="editOrganizationContainer">

            ${organizationsHTML}

        </div>


        <button
            type="button"
            class="add-org-btn"
            onclick="addOrganizationRow('edit')"
        >
            + Add Another Organisation
        </button>


        <h3 style="margin-top:25px;">
            Project Information
        </h3>


        <div class="form-grid">

            <div class="field">

                <label>
                    Number of Beneficiaries
                </label>

                <input
                    type="number"
                    name="beneficiaries"
                    min="0"
                    value="${escapeAttribute(
                        project[
                            'Number of Beneficiaries'
                        ] || 0
                    )}"
                >

            </div>


            <div></div>


            <div class="field">

                <label>
                    Start Date
                </label>

                <input
                    type="date"
                    name="start_date"
                    value="${escapeAttribute(
                        project['Start Date'] || ''
                    )}"
                >

            </div>


            <div class="field">

                <label>
                    End Date
                </label>

                <input
                    type="date"
                    name="end_date"
                    value="${escapeAttribute(
                        project['End Date'] || ''
                    )}"
                >

            </div>

        </div>


        <h3>
            Project Report
        </h3>


        <div class="report-box">

            ${
                project['Project Report']
                ?
                `
                <div class="current-report">

                    📄 Current report:

                    <strong>
                        ${escapeHTML(
                            project['Project Report']
                        )}
                    </strong>

                    <div class="report-actions">

                        <button
    type="button"
    class="btn btn-secondary"
    onclick="openReportModal('${escapeAttribute(project['Project Report'])}')"
>
    👁 View Report
</button>

                    </div>

                </div>
                `
                :
                `
                <div class="current-report">

                    📄 No report has been uploaded yet.

                </div>
                `
            }


            <div class="field">

                <label>
                    Replace Project Report
                </label>

                <input
                    type="file"
                    name="project_report"
                    accept=".pdf,application/pdf"
                >

                <div class="form-note">
                    Leave this empty to keep the current report.
                </div>

            </div>

        </div>


        <div
            id="editMessage"
            style="display:none;margin-top:15px;"
        ></div>

    `;


    openModal('editModal');
}


 /* =========================================================
   EDIT FORM SUBMISSION
========================================================= */

document.getElementById('editProjectForm').addEventListener(
    'submit',
    async function(event) {

        event.preventDefault();

        const form = this;

        const button =
            form.querySelector('button[type="submit"]');

        const message =
            document.getElementById('editMessage');

        button.disabled = true;

        button.textContent =
            'Saving Changes...';

        try {

            const formData =
                new FormData(form);

            /* Tell edit_project.php this is an AJAX request */
            formData.append('ajax', '1');

            const response =
                await fetch(
                    'edit_project.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );

            const responseText = await response.text();

console.log("EDIT PHP RESPONSE:", responseText);

if (!responseText.trim()) {
    throw new Error("edit_project.php returned an empty response.");
}

let data;

try {
    data = JSON.parse(responseText);
} catch (error) {
    throw new Error(
        "edit_project.php returned invalid JSON: " + responseText
    );
}

            if (!data.success) {

                throw new Error(
                    data.message ||
                    'Unable to update project.'
                );
            }

            message.innerHTML = `

                <div
                    style="
                        padding:13px;
                        background:#f0fdf4;
                        color:#166534;
                        border-left:4px solid #22c55e;
                        border-radius:7px;
                    "
                >
                    ✓ ${escapeHTML(data.message)}
                </div>

            `;

            message.style.display =
                'block';

            setTimeout(
                function() {

                    window.location.reload();

                },
                700
            );

        } catch (error) {

            message.innerHTML = `

                <div
                    style="
                        padding:13px;
                        background:#fef2f2;
                        color:#991b1b;
                        border-left:4px solid #ef4444;
                        border-radius:7px;
                    "
                >
                    ${escapeHTML(error.message)}
                </div>

            `;

            message.style.display =
                'block';

            button.disabled = false;

            button.textContent =
                '✓ Save Changes';
        }

    }
);


/* =========================================================
   DELETE
========================================================= */

let deleteProjectId = 0;


function openDeleteModal(
    id,
    title
) {

    deleteProjectId = id;


    document.getElementById(
        'deleteProjectTitle'
    ).textContent = title;


    document.getElementById(
        'deleteMessage'
    ).style.display = 'none';


    openModal('deleteModal');
}


document.getElementById(
    'confirmDeleteButton'
).addEventListener(
    'click',
    async function() {

        const button = this;

        const message =
            document.getElementById(
                'deleteMessage'
            );


        button.disabled = true;

        button.textContent =
            'Deleting...';


        try {

            const formData =
                new FormData();

            formData.append(
                'ajax',
                '1'
            );

            formData.append(
                'id',
                deleteProjectId
            );


            const response =
                await fetch(
                    'delete_project.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );


            const data =
                await response.json();


            if (!data.success) {

                throw new Error(
                    data.message ||
                    'Unable to delete project.'
                );
            }


            message.innerHTML = `

                <div
                    style="
                        padding:13px;
                        background:#f0fdf4;
                        color:#166534;
                        border-left:4px solid #22c55e;
                        border-radius:7px;
                    "
                >
                    ✓ ${escapeHTML(data.message)}
                </div>

            `;

            message.style.display =
                'block';


            setTimeout(
                function() {

                    window.location.reload();

                },
                700
            );


        } catch (error) {

            message.innerHTML = `

                <div
                    style="
                        padding:13px;
                        background:#fef2f2;
                        color:#991b1b;
                        border-left:4px solid #ef4444;
                        border-radius:7px;
                    "
                >
                    ${escapeHTML(error.message)}
                </div>

            `;

            message.style.display =
                'block';


            button.disabled = false;

            button.textContent =
                'Yes, Delete';
        }

    }
);


/* =========================================================
   SEARCH
========================================================= */

function searchProjects() {

    const query =
        document.getElementById(
            'searchInput'
        ).value
        .trim()
        .toLowerCase();


    const cards =
        document.querySelectorAll(
            '.project-card'
        );

    let visible =
        0;


    cards.forEach(
        function(card) {

            const text =
                card.dataset.search ||
                '';


            if (
                text.includes(query)
            ) {

                card.style.display =
                    '';

                visible++;

            } else {

                card.style.display =
                    'none';
            }

        }
    );


    document.querySelectorAll(
        '.year-group'
    ).forEach(
        function(group) {

            const visibleCards =
                group.querySelectorAll(
                    '.project-card:not([style*="display: none"])'
                );

            group.style.display =
                visibleCards.length
                ? ''
                : 'none';
        }
    );


    const noResults =
        document.getElementById(
            'noSearchResults'
        );


    if (noResults) {

        noResults.style.display =
            visible === 0
            ? 'block'
            : 'none';
    }


    document.getElementById(
        'searchInfo'
    ).textContent =
        'Showing ' +
        visible +
        ' project' +
        (visible === 1 ? '' : 's');
}


function clearSearch() {

    document.getElementById(
        'searchInput'
    ).value = '';

    searchProjects();
}


/* =========================================================
   YEAR TOGGLE
========================================================= */

function toggleYear(header) {

    const content =
        header.nextElementSibling;


    if (
        content.style.display ===
        'none'
    ) {

        content.style.display =
            'block';

    } else {

        content.style.display =
            'none';
    }
}


/* =========================================================
   FORMAT DATE
========================================================= */

function formatDate(date) {

    if (!date) {

        return 'Not specified';
    }


    const parts =
        date.split('-');


    if (parts.length !== 3) {

        return escapeHTML(date);
    }


    return (
        parts[2] +
        ' ' +
        new Date(
            date + 'T00:00:00'
        ).toLocaleString(
            'en',
            {
                month: 'short'
            }
        ) +
        ' ' +
        parts[0]
    );
}


/* =========================================================
   SECURITY HELPERS
========================================================= */

function escapeHTML(value) {

    if (value === null ||
        value === undefined) {

        return '';
    }


    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


function escapeAttribute(value) {

    return escapeHTML(value);
}

</script>


</body>

</html>

<?php

$conn->close();


/* =========================================================
   ORGANIZATION ROW FUNCTION
========================================================= */

function includeOrganizationRow(
    $type,
    $index,
    $organizations
) {

    ?>

    <div
        class="organization-row"
        data-org-row="<?php
            echo htmlspecialchars(
                $type . '-' . $index
            );
        ?>"
    >

        <div class="organization-grid">

            <div>

                <div class="field">

                    <label>
                        Organisation
                    </label>

                    <select
                        name="organization_id[]"
                        class="<?php
                            echo $type;
                        ?>-organization-select"
                    >

                        <option value="">
                            -- Select Existing Organisation --
                        </option>

                        <?php foreach (
                            $organizations
                            as $org
                        ): ?>

                            <option
                                value="<?php
                                    echo $org[
                                        'organization_id'
                                    ];
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $org[
                                        'organization_name'
                                    ]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <button
                    type="button"
                    class="new-org-toggle"
                    onclick="toggleNewOrganization(this)"
                >
                    + Enter New Organisation
                </button>


                <div class="new-org-box">

                    <div class="field">

                        <label>
                            New Organisation Name
                        </label>

                        <input
                            type="text"
                            name="new_organization[]"
                            placeholder="Enter organisation name"
                        >

                    </div>

                </div>

            </div>


            <div>

                <div class="field">

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


        <div class="org-tools">

            <span
                style="
                    font-size:12px;
                    color:#6b7280;
                "
            >
                Select an existing organisation or enter a new one.
            </span>

        </div>

    </div>
    <!-- =========================================================
     PUBLICATIONS MODAL
========================================================= -->

<div
    id="publicationsModal"
    class="modal"
    onclick="closeModalOutside(event, 'publicationsModal')"
>

    <div class="modal-content">

        <div class="modal-header">

            <div>

                <h2>
                    Publications
                </h2>

                <p>
                    Newsletters and publications
                    from the UEW Innovation Hub
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closePublicationsModal()"
            >
                &times;
            </button>

        </div>


        <div class="publications-toolbar">

            <input
                type="text"
                id="publicationSearch"
                placeholder="Search publications..."
            >


            <select
                id="publicationTypeFilter"
            >

                <option value="all">
                    All
                </option>

                <option value="Publication">
                    Publications
                </option>

                <option value="Newsletter">
                    Newsletters
                </option>

            </select>


            <button
                type="button"
                class="btn btn-primary"
                onclick="openAddPublicationModal()"
            >
                + Add Publication
            </button>

        </div>


        <div id="publicationsContainer">

            <div
                style="
                    text-align:center;
                    padding:40px;
                    color:#6b7280;
                "
            >

                No publications have been
                added yet.

            </div>

        </div>

    </div>

</div>

    <?php
}

?>

<?php

$servername = "localhost";
$username = "root";
$password = "mysl";
$database = "project_mysql";

$conn = new mysqli(
    $servername,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {

    if (
        isset($_POST['ajax']) &&
        $_POST['ajax'] === '1'
    ) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed.'
        ]);

        exit();
    }

    die("Connection failed: " . $conn->connect_error);
}


$is_ajax =
    isset($_POST['ajax']) &&
    $_POST['ajax'] === '1';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.'
        ]);

        exit();
    }

    
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

$start_date =
    $_POST['start_date'] ?? '';

$end_date =
    $_POST['end_date'] ?? '';


/* =========================================================
   VALIDATION
========================================================= */

if ($project_title === '') {

    $message =
        'Project title is required.';

    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' => $message
        ]);

        exit();
    }

    die($message);
}


if (empty($roles)) {

    $message =
        'Please add at least one organisation and role.';

    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' => $message
        ]);

        exit();
    }

    die($message);
}


/* =========================================================
   TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       1. INSERT PROJECT
    ===================================================== */

    $sql = "INSERT INTO innovation_hub
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


        if ($role === '') {

            throw new Exception(
                'Please select a role for every organisation.'
            );
        }


        /* =================================================
           NEW ORGANIZATION
        ================================================= */

        if (
            $new_organization !== ''
        ) {

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


            if ($existing) {

                $organization_id =
                    $existing[
                        'organization_id'
                    ];

            } else {

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
           VALIDATE ORGANIZATION
        ================================================= */

        if (
            empty($organization_id)
        ) {

            throw new Exception(
                'Please select or enter an organisation for every row.'
            );
        }


        /* =================================================
           SAVE RELATIONSHIP
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
       5. PROJECT REPORT
    ===================================================== */

    if (
        isset($_FILES['project_report']) &&
        $_FILES['project_report']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {


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


        if ($file_ext !== 'pdf') {

            throw new Exception(
                'Only PDF files are allowed for project reports.'
            );
        }


        $upload_dir =
            'reports/';


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


        $new_report =
            'project_' .
            $project_id .
            '_' .
            time() .
            '.pdf';


        $destination =
            $upload_dir .
            $new_report;


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


        $report_sql =
            "UPDATE innovation_hub
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
       6. COMMIT
    ===================================================== */

    $conn->commit();


    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => true,
            'message' =>
                'Project added successfully!',
            'project_id' =>
                $project_id
        ]);

        exit();
    }


    header(
        'Location: index.php'
    );

    exit();


} catch (Exception $e) {


    $conn->rollback();


    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' =>
                $e->getMessage()
        ]);

        exit();
    }


    die(
        'Error adding project: ' .
        htmlspecialchars(
            $e->getMessage()
        )
    );
}


$conn->close();

?>

 

 
<?php

$servername = "localhost";
$username = "root";
$password = "mysl";
$database = "project_mysql";

$conn = new mysqli(
    $servername,
    $username,
    $password,
    $database
);


if ($conn->connect_error) {

    header(
        'Content-Type: application/json'
    );

    echo json_encode([
        'success' => false,
        'message' =>
            'Database connection failed.'
    ]);

    exit();
}


$is_ajax =
    isset($_POST['ajax']) &&
    $_POST['ajax'] === '1';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.'
        ]);

        exit();
    }


    header(
        'Location: index.php'
    );

    exit();
}


/* =========================================================
   GET DATA
========================================================= */

$id =
    (int)($_POST['id'] ?? 0);


$project_title =
    trim(
        $_POST['project_title'] ?? ''
    );


$organization_ids =
    $_POST['organization_id'] ?? [];


$new_organizations =
    $_POST['new_organization'] ?? [];


$roles =
    $_POST['role'] ?? [];


$beneficiaries =
    (int)(
        $_POST['beneficiaries'] ?? 0
    );


$start_date =
    $_POST['start_date'] ?? '';


$end_date =
    $_POST['end_date'] ?? '';


/* =========================================================
   VALIDATION
========================================================= */

if ($id <= 0) {

    throw_response(
        'Invalid project ID.'
    );
}


if ($project_title === '') {

    throw_response(
        'Project title is required.'
    );
}


if (empty($roles)) {

    throw_response(
        'At least one organisation and role is required.'
    );
}


/* =========================================================
   GET CURRENT REPORT
========================================================= */

$current_report = '';


$current_report_sql =
    "SELECT `Project Report`
     FROM innovation_hub
     WHERE `project ID` = ?";


$current_report_stmt =
    $conn->prepare(
        $current_report_sql
    );


if (!$current_report_stmt) {

    throw_response(
        $conn->error
    );
}


$current_report_stmt->bind_param(
    "i",
    $id
);


$current_report_stmt->execute();


$current_report_result =
    $current_report_stmt->get_result();


$current_report_data =
    $current_report_result->fetch_assoc();


$current_report =
    $current_report_data[
        'Project Report'
    ] ?? '';


$current_report_stmt->close();


/* =========================================================
   CHECK PROJECT EXISTS
========================================================= */

$exists_stmt =
    $conn->prepare(
        "SELECT `project ID`
         FROM innovation_hub
         WHERE `project ID` = ?"
    );


if (!$exists_stmt) {

    throw_response(
        $conn->error
    );
}


$exists_stmt->bind_param(
    "i",
    $id
);


$exists_stmt->execute();


$exists_result =
    $exists_stmt->get_result();


if ($exists_result->num_rows === 0) {

    $exists_stmt->close();

    throw_response(
        'Project not found.'
    );
}


$exists_stmt->close();


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       1. UPDATE PROJECT
    ===================================================== */

    $update_sql =
        "UPDATE innovation_hub

         SET
            `Project Title` = ?,
            `Number of Beneficiaries` = ?,
            `Start Date` = ?,
            `End Date` = ?

         WHERE `project ID` = ?";


    $update_stmt =
        $conn->prepare(
            $update_sql
        );


    if (!$update_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    $update_stmt->bind_param(
        "sissi",
        $project_title,
        $beneficiaries,
        $start_date,
        $end_date,
        $id
    );


    if (
        !$update_stmt->execute()
    ) {

        throw new Exception(
            $update_stmt->error
        );
    }


    $update_stmt->close();


    /* =====================================================
       2. HANDLE NEW REPORT
    ===================================================== */

    $new_report =
        $current_report;


    if (
        isset($_FILES['project_report']) &&
        $_FILES['project_report']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {


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


        if (
            $file_ext !== 'pdf'
        ) {

            throw new Exception(
                'Only PDF files are allowed for project reports.'
            );
        }


        $upload_dir =
            'reports/';


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


        $new_report =
            'project_' .
            $id .
            '_' .
            time() .
            '.pdf';


        $destination =
            $upload_dir .
            $new_report;


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


        $report_sql =
            "UPDATE innovation_hub
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
            $id
        );


        if (
            !$report_stmt->execute()
        ) {

            throw new Exception(
                $report_stmt->error
            );
        }


        $report_stmt->close();


        /*
         * Delete the old report file after
         * the new one has been saved.
         */

        if (
            $current_report !== '' &&
            $current_report !== $new_report
        ) {

            $old_file =
                'reports/' .
                $current_report;


            if (
                file_exists($old_file)
            ) {

                @unlink(
                    $old_file
                );
            }
        }
    }


    /* =====================================================
       3. REMOVE OLD RELATIONSHIPS
    ===================================================== */

    $delete_relations_sql =
        "DELETE FROM project_organisations
         WHERE project_id = ?";


    $delete_relations_stmt =
        $conn->prepare(
            $delete_relations_sql
        );


    if (!$delete_relations_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    $delete_relations_stmt->bind_param(
        "i",
        $id
    );


    if (
        !$delete_relations_stmt->execute()
    ) {

        throw new Exception(
            $delete_relations_stmt->error
        );
    }


    $delete_relations_stmt->close();


    /* =====================================================
       4. PREPARE ORGANIZATION INSERT
    ===================================================== */

    $org_insert_stmt =
        $conn->prepare(
            "INSERT INTO organization
             (organization_name)
             VALUES (?)"
        );


    if (!$org_insert_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    /* =====================================================
       5. PREPARE RELATIONSHIP
    ===================================================== */

    $relation_stmt =
        $conn->prepare(
            "INSERT INTO project_organisations
             (
                project_id,
                organization_id,
                role
             )
             VALUES (?, ?, ?)"
        );


    if (!$relation_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    /* =====================================================
       6. SAVE ORGANIZATIONS
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


        if ($role === '') {

            throw new Exception(
                'Please select a role for every organisation.'
            );
        }


        /* =================================================
           NEW ORGANIZATION
        ================================================= */

        if (
            $new_organization !== ''
        ) {

            $check_stmt =
                $conn->prepare(
                    "SELECT organization_id
                     FROM organization
                     WHERE organization_name = ?
                     LIMIT 1"
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


            if ($existing) {

                $organization_id =
                    $existing[
                        'organization_id'
                    ];

            } else {

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


        if (
            empty($organization_id)
        ) {

            throw new Exception(
                'Please select or enter an organisation for every row.'
            );
        }


        /* =================================================
           SAVE RELATIONSHIP
        ================================================= */

        $relation_stmt->bind_param(
            "iis",
            $id,
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
       7. COMMIT
    ===================================================== */

    $conn->commit();


    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => true,
            'message' =>
                'Project updated successfully!',
            'project_id' =>
                $id
        ]);

        exit();
    }


    header(
        'Location: index.php'
    );

    exit();


} catch (Exception $e) {


    $conn->rollback();


    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' =>
                $e->getMessage()
        ]);

        exit();
    }


    die(
        'Error updating project: ' .
        htmlspecialchars(
            $e->getMessage()
        )
    );
}


/* =========================================================
   RESPONSE FUNCTION
========================================================= */

function throw_response(
    $message
) {

    global $is_ajax;


    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' => $message
        ]);

        exit();
    }


    die(
        htmlspecialchars(
            $message
        )
    );
}


$conn->close();

?>
 
<?php

$servername = "localhost";
$username = "root";
$password = "mysl";
$database = "project_mysql";


$conn = new mysqli(
    $servername,
    $username,
    $password,
    $database
);


$is_ajax =
    isset($_POST['ajax']) &&
    $_POST['ajax'] === '1';


if ($conn->connect_error) {

    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'message' =>
                'Database connection failed.'
        ]);

        exit();
    }

    die(
        'Connection failed: ' .
        $conn->connect_error
    );
}


$id =
    (int)(
        $_POST['id']
        ??
        $_GET['id']
        ??
        0
    );


if ($id <= 0) {

    send_response(
        false,
        'Invalid project ID.'
    );
}


/* =========================================================
   GET REPORT NAME BEFORE DELETE
========================================================= */

$report_stmt =
    $conn->prepare(
        "SELECT `Project Report`
         FROM innovation_hub
         WHERE `project ID` = ?"
    );


if (!$report_stmt) {

    send_response(
        false,
        $conn->error
    );
}


$report_stmt->bind_param(
    "i",
    $id
);


$report_stmt->execute();


$report_result =
    $report_stmt->get_result();


$report_data =
    $report_result->fetch_assoc();


if (!$report_data) {

    $report_stmt->close();

    send_response(
        false,
        'Project not found.'
    );
}


$report =
    $report_data[
        'Project Report'
    ] ?? '';


$report_stmt->close();


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       1. DELETE RELATIONSHIPS
    ===================================================== */

    $relation_stmt =
        $conn->prepare(
            "DELETE FROM project_organisations
             WHERE project_id = ?"
        );


    if (!$relation_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    $relation_stmt->bind_param(
        "i",
        $id
    );


    if (
        !$relation_stmt->execute()
    ) {

        throw new Exception(
            $relation_stmt->error
        );
    }


    $relation_stmt->close();


    /* =====================================================
       2. DELETE PROJECT
    ===================================================== */

    $project_stmt =
        $conn->prepare(
            "DELETE FROM innovation_hub
             WHERE `project ID` = ?"
        );


    if (!$project_stmt) {

        throw new Exception(
            $conn->error
        );
    }


    $project_stmt->bind_param(
        "i",
        $id
    );


    if (
        !$project_stmt->execute()
    ) {

        throw new Exception(
            $project_stmt->error
        );
    }


    if (
        $project_stmt->affected_rows === 0
    ) {

        throw new Exception(
            'Project could not be deleted.'
        );
    }


    $project_stmt->close();


    /* =====================================================
       3. COMMIT
    ===================================================== */

    $conn->commit();


    /* =====================================================
       4. DELETE REPORT FILE
    ===================================================== */

    if ($report !== '') {

        $report_file =
            'reports/' .
            $report;


        if (
            file_exists($report_file)
        ) {

            @unlink(
                $report_file
            );
        }
    }


    send_response(
        true,
        'Project deleted successfully.'
    );


} catch (Exception $e) {


    $conn->rollback();


    send_response(
        false,
        $e->getMessage()
    );
}


/* =========================================================
   RESPONSE
========================================================= */

function send_response(
    $success,
    $message
) {

    global $is_ajax;


    if ($is_ajax) {

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);

        exit();
    }


    if ($success) {

        header(
            'Location: index.php'
        );

        exit();
    }


    die(
        htmlspecialchars(
            $message
        )
    );
}


$conn->close();

?>
 