<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require '../vendor/autoload.php'; // Include the composer autoload file

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");
require_once("../email_sender.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrive session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build base SQL query with role-based filtering
$whereClause = "(whs_document_id LIKE '%$searchTerm%' OR
    involved_person_name LIKE '%$searchTerm%' OR
    department LIKE '%$searchTerm%')";

// SQL query to retrieve WHS data
$whs_sql = "SELECT * FROM whs WHERE $whereClause";
$whs_result = $conn->query($whs_sql);

// SQL query to count how many active employee for TRIR and LTIFR
$count_active_employee_sql = "SELECT COUNT(*) AS active_employee_count FROM employees WHERE is_active = 1";
$count_active_employee_result = $conn->query($count_active_employee_sql);
$row = mysqli_fetch_assoc($count_active_employee_result);
$active_employee_count = $row['active_employee_count'];

$estimated_working_hours = $active_employee_count * 38 * 52 * 1.1;

// SQL to count how many incident happened //ttm is trailing twelve months
$count_incident_ttm_sql = "SELECT COUNT(*) AS ttm_incident_count FROM whs WHERE incident_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
$count_incident_ttm_result = $conn->query($count_incident_ttm_sql);
$row = mysqli_fetch_assoc($count_incident_ttm_result);
$ttm_incident_count = $row['ttm_incident_count'];

// ========================= D E L E T E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["whsIdToDelete"])) {
    $whsIdToDelete = $_POST["whsIdToDelete"];

    $delete_document_sql = "DELETE FROM whs WHERE whs_id = ?";
    $delete_document_result = $conn->prepare($delete_document_sql);
    $delete_document_result->bind_param("i", $whsIdToDelete);

    if ($delete_document_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();

    } else {
        echo "Error: " . $delete_document_result . "<br>" . $conn->error;
    }
    $delete_document_result->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>WHS Table</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
    <style>
        .table-responsive {
            transform: scale(0.75);
            transform-origin: top left;
            width: 133.3%;
        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
            color: white;
        }

        .pagination .page-link {
            color: black
        }

        .modal-backdrop.show {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            transform: scale(1.333);
            /* Scale to counteract the body's scale */
            transform-origin: top left;
        }
    </style>
</head>

<body class="background-color">
    <?php require("../Menu/DropdownNavMenu.php") ?>

    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/qa-index.php">Quality
                            Assurances</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">WHS Table</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-end mb-3">
                <div class="d-flex align-items-start me-2 mt-0 pt-0">
                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#filterColumnModal">
                        <i class="fa-solid fa-sliders me-1"></i>Filter Column</button>
                </div>
                <div class="btn-group shadow-lg" role="group" aria-label="Zoom Controls">
                    <button class="btn btn-sm btn-light" style="cursor:pointer" onclick="zoom(0.8)"><i
                            class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <button class="btn btn-sm btn-light" style="cursor:pointer" onclick="zoom(1.2)"><i
                            class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <button class="btn btn-sm btn-danger" style="cursor:pointer" onclick="resetZoom()"><small
                            class="fw-bold">Reset</small></button>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="col-8 col-lg-5">
                    <form method="GET" id="searchForm">
                        <div class="d-flex align-items-center">
                            <div class="input-group me-2">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="searchDocuments" name="search"
                                    placeholder="Search Documents" value="<?php echo htmlspecialchars($searchTerm) ?>">
                            </div>
                            <button class="btn" type="submit"
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">Search
                            </button>
                            <button class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="d-flex justify-content-end align-items-center col-4 col-lg-7">
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                            class="fa-solid fa-plus"></i> Add WHS Document</button>
                </div>
            </div>
        </div>

        <div class="signature-bg-color rounded-3 d-flex py-2 mb-2">
            <div class="col-6 d-flex justify-content-center align-items-center">
                <div class="d-flex align-items-center">
                    <span class="text-white me-2 fw-bold">TRIR:
                        <?php echo number_format(($ttm_incident_count * 200000) / $estimated_working_hours, 2) ?> <a
                            role="button" data-bs-toggle="modal" data-bs-target="#ttmTrirModal"
                            id="ttmTrirBreakdownBtn"><i class="fa-solid fa-circle-question text-danger tooltips"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="See TRIR Breakdown"
                                data-estimated-working-hours="<?php echo $estimated_working_hours ?>"
                                data-total-active-employee="<?php echo $active_employee_count ?>"
                                data-ttm-incident-count="<?php echo $ttm_incident_count ?>"
                                style="cursor:pointer"></i></a></span>
                    <div class="text-white d-flex align-items-center">
                        <a role="button" data-bs-toggle="modal" data-bs-target="#trirModal">
                            <i class="fa-solid fa-gear text-info mb-1 tooltips" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="Custom TRIR Calculation"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-6 d-flex justify-content-center align-items-center">
                <div class="d-flex align-items-center">
                    <span class="text-white me-2 fw-bold">LTIFR:
                        <?php echo number_format(($ttm_incident_count / $estimated_working_hours) * 1000000, 2) ?>
                        <a href="" role="button" data-bs-toggle="modal" data-bs-target="#ttmLtifrModal"
                            id="ttmLtifrBreakdownBtn"><i class="fa-solid fa-circle-question text-danger tooltips"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="See LTIFR Breakdown"
                                data-estimated-working-hours="<?php echo $estimated_working_hours ?>"
                                data-total-active-employee="<?php echo $active_employee_count ?>"
                                data-ttm-incident-count="<?php echo $ttm_incident_count ?>"
                                style="cursor:pointer"></i></a>
                    </span>
                    <div class="text-white d-flex align-items-center">
                        <a role="button" data-bs-toggle="modal" data-bs-target="#ltifrModal">
                            <i class="fa-solid fa-gear text-info mb-1 tooltips" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="Custom LTIFR Calculation"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="py-4 align-middle text-center whsDocumentIdColumn" style="min-width:120px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                WHS ID <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center whsDescriptionColumn" style="min-width:300px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Description <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center involvedPersonColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Involved Person Name <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center incidentDateColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Incident Date<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center departmentColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Department<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center statusColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Status<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center nearMissColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Near Miss<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center firstAidGivenColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                First Aid Given<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center medicalTreatmentCaseColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Medical Treatment Case<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center recordableIncidentColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Automated Notifiable/Recordable Incident <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center restrictedWorkCaseColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Restricted Work Case <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center lostTimeCaseColumn" style="min-width:100px;">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Lost Time Case <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center fiveDaysOffColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                More Than 5 Days Off Work <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center insuranceNotifiedColumn" style="min-width: 100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Insurance Notified <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center directorNotifiedColumn" style="min-width: 100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Director Notified <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center dateRaisedColumn" style="min-width: 100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Date Raised <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center dateClosedColumn" style="min-width: 100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Date Closed <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center additionalCommentColumn" style="min-width: 300px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Additional Comments <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($whs_result->num_rows > 0) { ?>
                        <?php while ($row = $whs_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="d-flex">
                                        <button class="btn" data-bs-toggle="modal" data-bs-target="#editDocumentModal"
                                            data-whs-id="<?= $row['whs_id'] ?>"
                                            data-whs-document-id="<?= $row['whs_document_id'] ?>"
                                            data-description="<?= $row['description'] ?>"
                                            data-involved-person-name="<?= $row['involved_person_name'] ?>"
                                            data-incident-date="<?= $row['incident_date'] ?>"
                                            data-department="<?= $row['department'] ?>" data-status="<?= $row['status'] ?>"
                                            data-near-miss="<?= $row['near_miss'] ?>"
                                            data-first-aid-given="<?= $row['first_aid_given'] ?>"
                                            data-medical-treatment-case="<?= $row['medical_treatment_case'] ?>"
                                            data-recordable_incident="<?= $row['recordable_incident'] ?>"
                                            data-restricted-work-case="<?= $row['restricted_work_case'] ?>"
                                            data-lost-time-case="<?= $row['lost_time_case'] ?>"
                                            data-five-days-off="<?= $row['five_days_off'] ?>"
                                            data-insurance-notified="<?= $row['insurance_notified'] ?>"
                                            data-director-notified="<?= $row['director_notified'] ?>"
                                            data-date-raised="<?= $row['date_raised'] ?>"
                                            data-date-closed="<?= $row['date_closed'] ?>"
                                            data-additional-comments="<?= $row['additional_comments'] ?>"> <i
                                                class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                            data-whs-id="<?= $row["whs_id"] ?>"
                                            data-whs-document-id="<?= $row["whs_document_id"] ?>"><i
                                                class="fa-regular fa-trash-can text-danger"></i> </button>
                                    </div>
                                </td>
                                <td class="py-2 align-middle text-center"><?= $row['whs_document_id'] ?></td>
                                <td class="py-2 align-middle text-center"><?= $row['description'] ?></td>
                                <td class="py-2 align-middle text-center"><?= $row['involved_person_name'] ?></td>
                                <td class="py-2 align-middle text-center"><?= $row['incident_date'] ?></td>
                                <td class="py-2 align-middle text-center"><?= $row['department'] ?></td>
                                <td class="py-2 align-middle text-center"><?= $row['status'] ?></td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['near_miss'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['near_miss'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['first_aid_given'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['first_aid_given'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['medical_treatment_case'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['medical_treatment_case'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['recordable_incident'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['recordable_incident'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['restricted_work_case'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['restricted_work_case'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['lost_time_case'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['lost_time_case'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['five_days_off'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['five_days_off'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['insurance_notified'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['insurance_notified'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center text-white
                                <?= $row['director_notified'] == 1 ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $row['director_notified'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center"><?= $row['date_raised'] ?></td>
                                <td class="py-2 align-middle text-center" <?= isset($row['date_closed']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['date_closed']) ? $row['date_closed'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center" <?= isset($row['additional_comments']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['additional_comments']) ? $row['additional_comments'] : "N/A" ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================== Add Document Modal ================== -->
    <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add WHS Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/addWHSDocumentForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================================== T  R  I  R ==================================== -->
    <!-- ================== TRIR Modal Breakdown (TTM) ================== -->
    <div class="modal fade" id="ttmTrirModal" tabindex="-1" aria-labelledby="ttmTrirModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Total Recordable Incident Rate (TRIR)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold pb-0 mb-0">Formula for TRIR:</p>
                    <p class="signature-bg-color rounded-3 fw-bold text-white text-center py-2"><span class="mx-2">TRIR
                            = (Number of Incidents ×
                            200,000) / Total Number of Hours Worked</span></p>
                    <hr>
                    <p class="fw-bold pb-0 mb-0">Calculation of Total Hours Worked:</p>
                    <p class="signature-bg-color rounded-3 fw-bold text-white text-center py-2"><span class="mx-2">Total
                            Hours Worked = Active Employees × Total Full-Time Hours × 52 Weeks × Overtime
                            Increment</span></p>
                    <hr>
                    <p class="fw-bold pb-0 mb-0">Standard Metrics:</p>
                    <ul>
                        <li>Full-Time Hours: <span class="fw-bold signature-color">38 </span> hours per week</li>
                        <li>Weeks in a Year: <span class="fw-bold signature-color">52</span></li>
                        <li>Overtime Increment: <span class="fw-bold signature-color">1.1</span></li>
                    </ul>
                    <hr>

                    <p class="fw-bold pb-0 mb-0">Current Value:</p>
                    <ul>
                        <li>Number of Incidents: <span id="modalTtmIncidentCount"
                                class="fw-bold signature-color"></span></li>

                        <li>Total Hours Worked: <span id="modalEstimatedWorkingHours"
                                class="fw-bold signature-color"></span></li>

                        <li>Total Active Employees: <span id="modalTotalActiveEmployee"
                                class="fw-bold signature-color"></span></li>
                    </ul>
                    <hr>
                    <?php
                    // Get the current month and year
                    $currentMonth = date('F Y'); // e.g., November 2024
                    $previousMonth = date('F Y', strtotime('-1 year')); // e.g., November 2023
                    ?>
                    <div
                        class="signature-bg-color rounded-3 p-3 text-white d-flex justify-content-between align-items-center">
                        <p class="fw-bold pb-0 mb-0">Current TRIR
                            (<?php echo $previousMonth . ' - ' . $currentMonth; ?>) :

                        </p>
                        <h4 class="fw-bold pb-0 mb-0 bg-white signature-color rounded-3 p-2">
                            <?php echo number_format(($ttm_incident_count * 200000) / $estimated_working_hours, 2) ?>
                        </h4>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-secondary mt-3" data-bs-dismiss="modal" aria-label="Close">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== TRIR Modal (Custom) ================== -->
    <div class="modal fade" id="trirModal" tabindex="-1" aria-labelledby="trirModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">TRIR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="trirForm" novalidate>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="hoursWorkTrir" class="fw-bold">Total Hours Worked <i
                                        class="fa-solid fa-circle-question text-danger tooltips"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="Total Hours Worked to Date <?php echo $estimated_working_hours; ?>, with an Active Employee Count of <?php echo $active_employee_count ?>."
                                        style="cursor:pointer"></i></label>
                                <input type="number" name="hoursWorkTrir" class="form-control" id="hoursWorkTrir">
                                <span class="text-danger fw-bold pb-0 mb-0">

                                </span>
                                <div class="invalid-feedback">
                                    Please provide the total hours worked.
                                </div>
                            </div>
                            <div class="form-group col-md-12 mt-3">
                                <label for="startDateTrir" class="fw-bold">Start Date</label>
                                <input type="date" name="startDateTrir" class="form-control" id="startDateTrir">
                                <div class="invalid-feedback">
                                    Please provide the start date.
                                </div>
                            </div>
                            <div class="form-group col-md-12 mt-3">
                                <label for="endDateTrir" class="fw-bold">End Date</label>
                                <input type="date" name="endDateTrir" class="form-control" id="endDateTrir">
                                <div class="invalid-feedback">
                                    Please provide the end date.
                                </div>
                            </div>
                            <div class="d-flex justify-content-center align-items-center mt-3">
                                <button class="btn btn-secondary me-1" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-dark" id="calculateTrirBtn">
                                    Calculate TRIR
                                </button>
                            </div>
                        </div>
                    </form>
                    <!-- Display the TRIR result here -->
                    <div id="trirResultContainer"
                        class="signature-bg-color rounded-3 p-3 text-white d-flex justify-content-between align-items-center d-none mt-3">
                        <div>Total Incident: <span id="modalIncidentCountTrir"
                                class="fw-bold text-decoration-underline"></span></div>
                        <div> TRIR: <span id="modalCalculatedTrir" class="fw-bold text-decoration-underline"></div>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================================== L  T  I  F  R ==================================== -->
    <!-- ================== LTIFR Modal Breakdown (TTM) ================== -->
    <div class="modal fade" id="ttmLtifrModal" tabindex="-1" aria-labelledby="ttmLtifrModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Total Recordable Incident Rate (LTIFR)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold pb-0 mb-0">Formula for LTIFR:</p>
                    <p class="signature-bg-color rounded-3 fw-bold text-white text-center py-2"><span class="mx-2">LTIFR
                            = (Number of Incidents / Total Number of Hours Worked) x 1,000,000</span></p>
                    <hr>
                    <p class="fw-bold pb-0 mb-0">Calculation of Total Hours Worked:</p>
                    <p class="signature-bg-color rounded-3 fw-bold text-white text-center py-2"><span class="mx-2">Total
                            Hours Worked = Active Employees × Total Full-Time Hours × 52 Weeks × Overtime
                            Increment</span></p>
                    <hr>
                    <p class="fw-bold pb-0 mb-0">Standard Metrics:</p>
                    <ul>
                        <li>Full-Time Hours: <span class="fw-bold signature-color">38 </span> hours per week</li>
                        <li>Weeks in a Year: <span class="fw-bold signature-color">52</span></li>
                        <li>Overtime Increment: <span class="fw-bold signature-color">1.1</span></li>
                    </ul>
                    <hr>

                    <p class="fw-bold pb-0 mb-0">Current Value:</p>
                    <ul>
                        <li>Number of Incidents: <span id="modalTtmIncidentCount"
                                class="fw-bold signature-color"></span></li>

                        <li>Total Hours Worked: <span id="modalEstimatedWorkingHours"
                                class="fw-bold signature-color"></span></li>

                        <li>Total Active Employees: <span id="modalTotalActiveEmployee"
                                class="fw-bold signature-color"></span></li>
                    </ul>
                    <hr>
                    <?php
                    // Get the current month and year
                    $currentMonth = date('F Y'); // e.g., November 2024
                    $previousMonth = date('F Y', strtotime('-1 year')); // e.g., November 2023
                    ?>
                    <div
                        class="signature-bg-color rounded-3 p-3 text-white d-flex justify-content-between align-items-center">
                        <p class="fw-bold pb-0 mb-0">Current LTIFR
                            (<?php echo $previousMonth . ' - ' . $currentMonth; ?>) :

                        </p>
                        <h4 class="fw-bold pb-0 mb-0 bg-white signature-color rounded-3 p-2">
                            <?php echo number_format(($ttm_incident_count / $estimated_working_hours) * 1000000, 2) ?>
                        </h4>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-secondary mt-3" data-bs-dismiss="modal" aria-label="Close">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== LTIFR Modal (Custom) ================== -->
    <div class="modal fade" id="ltifrModal" tabindex="-1" aria-labelledby="ltifrModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">LTIFR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="ltifrForm" novalidate>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="hoursWorkLtifr" class="fw-bold">Total Hours Worked <i
                                        class="fa-solid fa-circle-question text-danger tooltips"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="Total Hours Worked to Date <?php echo $estimated_working_hours; ?>, with an Active Employee Count of <?php echo $active_employee_count ?>."
                                        style="cursor:pointer"></i></label>
                                <input type="number" name="hoursWorkLtifr" class="form-control" id="hoursWorkLtifr">
                                <span class="text-danger fw-bold pb-0 mb-0">

                                </span>
                                <div class="invalid-feedback">
                                    Please provide the total hours worked.
                                </div>
                            </div>
                            <div class="form-group col-md-12 mt-3">
                                <label for="startDateLtifr" class="fw-bold">Start Date</label>
                                <input type="date" name="startDateLtifr" class="form-control" id="startDateLtifr">
                                <div class="invalid-feedback">
                                    Please provide the start date.
                                </div>
                            </div>
                            <div class="form-group col-md-12 mt-3">
                                <label for="endDateLtifr" class="fw-bold">End Date</label>
                                <input type="date" name="endDateLtifr" class="form-control" id="endDateLtifr">
                                <div class="invalid-feedback">
                                    Please provide the end date.
                                </div>
                            </div>
                            <div class="d-flex justify-content-center align-items-center mt-3">
                                <button class="btn btn-secondary me-1" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-dark" id="calculateLtifrBtn">
                                    Calculate LTIFR
                                </button>
                            </div>
                        </div>
                    </form>
                    <!-- Display the LTIFR result here -->
                    <div id="ltifrResultContainer"
                        class="signature-bg-color rounded-3 p-3 text-white d-flex justify-content-between align-items-center d-none mt-3">
                        <div>Total Incident: <span id="modalIncidentCountLtifr"
                                class="fw-bold text-decoration-underline"></span></div>
                        <div> LTIFR: <span id="modalCalculatedLtifr" class="fw-bold text-decoration-underline"></div>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Delete Confirmation Modal ================== -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span class="fw-bold" id="whsDocumentToDelete"></span> document?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="whsIdToDelete" id="whsIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Edit WHS Document Modal ================== -->
    <div class="modal fade" id="editDocumentModal" tab-index="-1" aria-labelledby="editDocumentModal"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit WHS Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require("../Form/EditWHSDocumentForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t);
        })
    </script>

    <script>
        document.getElementById('ttmTrirBreakdownBtn').addEventListener('click', function () {
            var ttmTrirModal = document.getElementById('ttmTrirModal');
            var button = this.querySelector('i'); // Get the <i> element inside the button
            var estimatedWorkingHours = button.getAttribute('data-estimated-working-hours');
            var totalActiceEmployee = button.getAttribute('data-total-active-employee');
            var ttmIncidentCount = button.getAttribute('data-ttm-incident-count');

            // Update the modal's content with the extracted data
            var modalEstimatedWorkingHours = ttmTrirModal.querySelector('#modalEstimatedWorkingHours');
            var modalTotalActiveEmployee = ttmTrirModal.querySelector('#modalTotalActiveEmployee')
            var modalTtmIncidentCount = ttmTrirModal.querySelector('#modalTtmIncidentCount')

            modalEstimatedWorkingHours.innerHTML = estimatedWorkingHours;
            modalTotalActiveEmployee.innerHTML = totalActiceEmployee;
            modalTtmIncidentCount.innerHTML = ttmIncidentCount;
        });
    </script>

    <script>
        document.getElementById('ttmLtifrBreakdownBtn').addEventListener('click', function () {
            var ttmLtifrModal = document.getElementById('ttmLtifrModal');
            var button = this.querySelector('i'); // Get the <i> element inside the button
            var estimatedWorkingHours = button.getAttribute('data-estimated-working-hours');
            var totalActiceEmployee = button.getAttribute('data-total-active-employee');
            var ttmIncidentCount = button.getAttribute('data-ttm-incident-count');

            // Update the modal's content with the extracted data
            var modalEstimatedWorkingHours = ttmLtifrModal.querySelector('#modalEstimatedWorkingHours');
            var modalTotalActiveEmployee = ttmLtifrModal.querySelector('#modalTotalActiveEmployee')
            var modalTtmIncidentCount = ttmLtifrModal.querySelector('#modalTtmIncidentCount')

            modalEstimatedWorkingHours.innerHTML = estimatedWorkingHours;
            modalTotalActiveEmployee.innerHTML = totalActiceEmployee;
            modalTtmIncidentCount.innerHTML = ttmIncidentCount;
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calculateTrirBtn = document.getElementById("calculateTrirBtn");

            calculateTrirBtn.addEventListener('click', function (event) {
                event.preventDefault();

                const hoursWorked = document.getElementById("hoursWorkTrir").value;
                const startDate = document.getElementById("startDateTrir").value;
                const endDate = document.getElementById("endDateTrir").value;

                // Perform AJAX request
                fetch('../AJAXphp/calculate_trir_ltifr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        hoursWork: hoursWorked,
                        startDate: startDate,
                        endDate: endDate,
                        trir: "trir",
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Display TRIR and Incident Count in modal
                            document.getElementById("modalCalculatedTrir").textContent = `${data.trir}`;
                            document.getElementById("modalIncidentCountTrir").textContent = `${data.incident_count}`;

                            // Show the result container
                            document.getElementById("trirResultContainer").classList.remove("d-none");
                        } else {
                            console.error("Error:", data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calculateLtifrBtn = document.getElementById("calculateLtifrBtn");

            calculateLtifrBtn.addEventListener('click', function (event) {
                event.preventDefault();

                const hoursWorked = document.getElementById("hoursWorkLtifr").value;
                const startDate = document.getElementById("startDateLtifr").value;
                const endDate = document.getElementById("endDateLtifr").value;

                console.log(hoursWorked)
                console.log(startDate)
                console.log(endDate)
                

                // Perform AJAX request
                fetch('../AJAXphp/calculate_trir_ltifr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        hoursWork: hoursWorked,
                        startDate: startDate,
                        endDate: endDate,
                        ltifr: "ltifr",
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Display LTIFR and Incident Count in modal
                            document.getElementById("modalCalculatedLtifr").textContent = `${data.ltifr}`;
                            document.getElementById("modalIncidentCountLtifr").textContent = `${data.incident_count}`;

                            // Show the result container
                            document.getElementById("ltifrResultContainer").classList.remove("d-none");
                        } else {
                            console.error("Error:", data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
            });
        });
    </script>

    <script>
        function clearURLParameters() {
            // Use the URL API to manipulate the URL
            const url = new URL(window.location.href);
            url.search = ''; // Clear the query string

            // Reload the page with the updated URL 
            window.location.href = url.href;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var whsId = button.getAttribute('data-whs-id');
                var whsDocument = button.getAttribute('data-whs-document-id');

                // Update the modal's content with the extracted info
                var modalWHSIdToDelete = myModalEl.querySelector('#whsIdToDelete');
                var modalWHSDocument = myModalEl.querySelector('#whsDocumentToDelete');
                modalWHSIdToDelete.value = whsId;
                modalWHSDocument.textContent = whsDocument;
            })
        })    
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editDocumentModal');

            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;

                // Extract data from the button attributes
                var whsId = button.getAttribute('data-whs-id');
                var whsDocumentId = button.getAttribute('data-whs-document-id');
                var involvedPersonName = button.getAttribute('data-involved-person-name');
                var description = button.getAttribute('data-description');

                // Update the modal's content with the extracted data
                var modalWhsId = myModalEl.querySelector('#whsIdToEdit');
                var modalWhsDocumentId = myModalEl.querySelector('#whsDocumentIdToEdit');
                var modalInvolvedPersonName = myModalEl.querySelector('#involvedPersonNameToEdit');
                var modalDescription = myModalEl.querySelector('#whsDescriptionToEdit');

                // Assign the extracted values to the modal input fields
                modalWhsId.value = whsId;
                modalWhsDocumentId.value = whsDocumentId;
                modalInvolvedPersonName.value = involvedPersonName;
                modalDescription.value = description;
            })
        })
    </script>
</body>

</html>