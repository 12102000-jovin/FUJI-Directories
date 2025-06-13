<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../vendor/autoload.php'; // Include the composer autoload file

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");
require_once("../email_sender.php");

$folder_name = "Work Health and Safety";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrive session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// Get status filter
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'whs_document_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 10; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Search condition
$whereClause = "(whs_document_id LIKE '%$searchTerm%' OR
    involved_person_name LIKE '%$searchTerm%' OR
    department LIKE '%$searchTerm%')";

$whereClause .= " AND (status = '$statusFilter' OR '$statusFilter' = '')";

// SQL query to retrieve WHS data
$whs_sql = "SELECT * FROM whs WHERE $whereClause ORDER BY $sort $order 
LIMIT $offset, $records_per_page";
$whs_result = $conn->query($whs_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM whs WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

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

// SQL to count how many incident happened //ttm is trailing twelve months
$count_lost_time_incident_ttm_sql = "SELECT COUNT(*) AS lost_time_ttm_incident_count FROM whs WHERE incident_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND lost_time_case = 1";
$count_lost_time_incident_ttm_result = $conn->query($count_lost_time_incident_ttm_sql);
$row = mysqli_fetch_assoc($count_lost_time_incident_ttm_result);
$lost_time_ttm_incident_count = $row['lost_time_ttm_incident_count'];

// SQL query to get the latest recorded WHS date
$latest_whs_sql = "SELECT MAX(incident_date) AS latest_incident_date FROM whs WHERE recordable_incident = 1";
$latest_whs_result = $conn->query($latest_whs_sql);

if ($latest_whs_result->num_rows > 0) {
    $row = $latest_whs_result->fetch_assoc();
    $latest_incident_date = $row['latest_incident_date'];
}

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
    <?php require("../Menu/NavBar.php") ?>

    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-end align-items-center">
            <!-- <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">WHS Table</li>
                </ol>
            </nav> -->
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
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
                <div class="col-12 col-sm-8 col-lg-5 d-flex justify-content-between align-items-center mb-3 mb-sm-0">
                    <form method="GET" id="searchForm" class="d-flex align-items-center w-100">
                        <div class="d-flex align-items-center">
                            <div class="input-group me-2 flex-grow-1">
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
                            <button class="btn btn-outline-dark dropdown-toggle ms-2" type="button"
                                id="statusDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo $statusFilter ? $statusFilter : "All Status" ?>
                            </button>
                            <div class="dropdown">
                                <form method="GET">
                                    <input type="hidden" id="selectedStatusFilter" name="status">
                                    <ul class="dropdown-menu" aria-labelledby="statusDropdownMenuButton">
                                        <li><a class="dropdown-item dropdown-status-item" href="#"
                                                data-status-filter="All Status">All Status</a></li>
                                        <li><a class="dropdown-item dropdown-status-item" href="#"
                                                data-status-filter="Open">Open</a></li>
                                        <li><a class="dropdown-item dropdown-status-item" href="#"
                                                data-status-filter="Closed">Closed</a></li>
                                    </ul>
                                </form>
                            </div>
                        </div>
                    </form>
                </div>
                <?php if ($role === "full control") { ?>
                    <div
                        class="d-flex justify-content-center justify-content-sm-end align-items-center col-12 col-sm-4 col-lg-7">
                        <a class="btn btn-primary me-2" type="button" data-bs-toggle="modal"
                            data-bs-target="#whsDashboardModal"> <i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                                class="fa-solid fa-plus"></i> Add WHS</button>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="bg-dark rounded-3 d-flex flex-column py-2 mb-2">
            <div class="row mx-2">
                <!-- TRIR Section -->
                <div
                    class="col-12 col-md-4 d-flex justify-content-start justify-content-md-center align-items-start align-items-md-center mb-3 mb-md-0">
                    <div class="d-flex flex-column">
                        <p class="text-white fw-bold mb-1">TRIR</p>
                        <div class="d-flex align-items-center">
                            <h2 class="text-white fw-bold mb-0 me-2">
                                <?php echo number_format(($ttm_incident_count * 200000) / $estimated_working_hours, 2); ?>
                            </h2>
                            <div class="d-flex">
                                <a style="text-decoration: none" role="button" data-bs-toggle="modal"
                                    data-bs-target="#ttmTrirModal" id="ttmTrirBreakdownBtn"
                                    class="d-flex align-items-center text-danger me-2 tooltips" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="See TRIR Breakdown"
                                    data-estimated-working-hours="<?php echo $estimated_working_hours ?>"
                                    data-total-active-employee="<?php echo $active_employee_count ?>"
                                    data-ttm-incident-count="<?php echo $ttm_incident_count ?>">
                                    <i class="fa-solid fa-circle-question"></i>
                                </a>
                                <a style="text-decoration: none" role="button" data-bs-toggle="modal"
                                    data-bs-target="#trirModal" class="d-flex align-items-center text-info tooltips"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Custom TRIR Calculation">
                                    <i class="fa-solid fa-gear"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LTIFR Section -->
                <div
                    class="col-12 col-md-4 d-flex justify-content-start justify-content-md-center align-items-start align-items-md-center mb-3 mb-md-0">
                    <div class="d-flex flex-column">
                        <p class="text-white fw-bold mb-1">LTIFR</p>
                        <div class="d-flex align-items-center">
                            <h2 class="text-white fw-bold mb-0 me-2">
                                <?php echo number_format(($lost_time_ttm_incident_count / $estimated_working_hours) * 1000000, 2); ?>
                            </h2>
                            <div class="d-flex align-items-center">
                                <a style="text-decoration: none" role="button" data-bs-toggle="modal"
                                    data-bs-target="#ttmLtifrModal" id="ttmLtifrBreakdownBtn"
                                    class="d-flex align-items-center text-danger me-2 tooltips" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="See LTIFR Breakdown"
                                    data-estimated-working-hours="<?php echo $estimated_working_hours ?>"
                                    data-total-active-employee="<?php echo $active_employee_count ?>"
                                    data-ttm-incident-count="<?php echo $lost_time_ttm_incident_count ?>">
                                    <i class="fa-solid fa-circle-question"></i>
                                </a>
                                <a style="text-decoration: none" role="button" data-bs-toggle="modal"
                                    data-bs-target="#ltifrModal" class="d-flex align-items-center text-info tooltips"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Custom LTIFR Calculation">
                                    <i class="fa-solid fa-gear"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Days Since Last Incident Section -->
                <div
                    class="col-12 col-md-4 d-flex justify-content-start justify-content-md-center align-items-start align-items-md-center">
                    <?php
                    date_default_timezone_set('Australia/Sydney');
                    $today = new DateTime();
                    $incident_date = new DateTime($latest_incident_date ?? 'now');
                    $interval = $today->diff($incident_date);
                    $days_difference = $interval->days;
                    ?>
                    <div class="d-flex flex-column">
                        <p class="text-white mb-1 pb-0 fw-bold">Days Since Last Incident</p>
                        <h2 class="text-white mb-0 pb-0">
                            <?php echo $days_difference . ' ' . ($days_difference > 1 ? "days" : "day"); ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <?php if ($role === "full control") { ?>
                            <th></th>
                        <?php } ?>
                        <th class="py-4 align-middle text-center whsDocumentIdColumn" style="min-width:120px">
                            <a onclick="updateSort('whs_document_id', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                WHS ID <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>

                        <th class="py-4 align-middle text-center whsDescriptionColumn" style="min-width:300px">
                            <a onclick="updateSort('description', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Description <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center involvedPersonNameColumn" style="min-width:100px">
                            <a onclick="updateSort('involved_person_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Involved Person Name <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center incidentDateColumn" style="min-width:180px">
                            <a onclick="updateSort('incident_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Incident Date<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center departmentColumn" style="min-width:100px">
                            <a onclick="updateSort('department', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Department<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center statusColumn" style="min-width:100px">
                            <a onclick="updateSort('status', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Status<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center nearMissColumn" style="min-width:100px">
                            <a onclick="updateSort('near_miss', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Near Miss<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center firstAidGivenColumn" style="min-width:100px">
                            <a onclick="updateSort('first_aid_given', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                First Aid Given<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center medicalTreatmentCaseColumn" style="min-width:100px">
                            <a onclick="updateSort('medical_treatment_case', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Medical Treatment Case<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center recordableIncidentColumn" style="min-width:100px">
                            <a onclick="updateSort('recordable_incident', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Automated Notifiable/Recordable Incident <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center restrictedWorkCaseColumn" style="min-width:100px">
                            <a onclick="updateSort('restricted_work_case', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Restricted Work Case <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center lostTimeCaseColumn" style="min-width:100px;">
                            <a onclick="updateSort('lost_time_case', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Lost Time Case <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center fiveDaysOffColumn" style="min-width:100px">
                            <a onclick="updateSort('five_days_off', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                More Than 5 Days Off Work <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center insuranceNotifiedColumn" style="min-width: 100px">
                            <a onclick="updateSort('insurance_notified', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Insurance Notified <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center directorNotifiedColumn" style="min-width: 100px">
                            <a onclick="updateSort('director_notified', '<?= $order == 'asc' ? 'desc' : 'asc' ?>) "
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Director Notified <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center dateRaisedColumn" style="min-width: 180px">
                            <a onclick="updateSort('date_raised', '<?= $order == 'asc' ? 'desc' : 'asc' ?>') "
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Date Raised <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center dateClosedColumn" style="min-width: 180px">
                            <a onclick="updateSort('date_closed', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Date Closed <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center additionalCommentsColumn" style="min-width: 300px">
                            <a onclick="updateSort('additional_comments', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Additional Comments <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($whs_result->num_rows > 0) { ?>
                        <?php while ($row = $whs_result->fetch_assoc()) { ?>
                            <tr>
                                <?php if ($role === "full control") { ?>
                                    <td class="align-middle">
                                        <div class="d-flex">
                                            <button id="editDocumentModalBtn" class="btn" data-bs-toggle="modal"
                                                data-bs-target="#editDocumentModal" data-whs-id="<?= $row['whs_id'] ?>"
                                                data-whs-document-id="<?= $row['whs_document_id'] ?>"
                                                data-description="<?= $row['description'] ?>" data-status="<?= $row['status'] ?>"
                                                data-involved-person-name="<?= $row['involved_person_name'] ?>"
                                                data-incident-date="<?= $row['incident_date'] ?>"
                                                data-department="<?= $row['department'] ?>"
                                                data-near-miss="<?= $row['near_miss'] ?>"
                                                data-first-aid-given="<?= $row['first_aid_given'] ?>"
                                                data-medical-treatment-case="<?= $row['medical_treatment_case'] ?>"
                                                data-recordable-incident="<?= $row['recordable_incident'] ?>"
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
                                <?php } ?>

                                <?php
                                $employee_id = $row['involved_person_name'];
                                $employee_name = "N/A"; // Default value
                        
                                if (isset($employee_id)) {
                                    // Prepare and execute the query to fetch employee name
                                    $stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
                                    $stmt->bind_param("i", $employee_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    // Fetch the employee name
                                    if ($rowEmployee = $result->fetch_assoc()) {
                                        $employee_first_name = $rowEmployee['first_name'];
                                        $employee_last_name = $rowEmployee['last_name'];
                                        $employee_name = $employee_first_name . ' ' . $employee_last_name; // Combine first and last name
                                    }
                                }
                                ?>

                                <td class="py-2 align-middle text-center whsDocumentIdColumn"><a
                                        href="../open-whs-folder.php?employee_id=<?= $row['involved_person_name'] ?>&folder=06 - Work Compensation"
                                        target="_blank"><?= $row['whs_document_id'] ?></a></td>
                                <td class="py-2 align-middle text-center whsDescriptionColumn"><?= $row['description'] ?></td>
                                <td class="py-2 align-middle text-center involvedPersonNameColumn">
                                    <?= $employee_name ?>
                                </td>
                                <td class="py-2 align-middle text-center incidentDateColumn">
                                    <?= date("j F Y", strtotime($row['incident_date'])) ?>
                                </td>
                                <td class="py-2 align-middle text-center departmentColumn"><?= $row['department'] ?></td>
                                <td
                                    class="py-2 align-middle text-center statusColumn <?= $row['status'] == "Open" ? 'bg-danger text-white ' : 'bg-success text-white' ?>">
                                    <?= $row['status'] ?>
                                </td>
                                <td class="py-2 align-middle text-center nearMissColumn
                                <?= $row['near_miss'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['near_miss'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center firstAidGivenColumn
                                <?= $row['first_aid_given'] == 1 ? 'bg-danger text-white ' : '' ?>">
                                    <?= $row['first_aid_given'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center medicalTreatmentCaseColumn
                                <?= $row['medical_treatment_case'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['medical_treatment_case'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center recordableIncidentColumn
                                <?= $row['recordable_incident'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['recordable_incident'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center restrictedWorkCaseColumn
                                <?= $row['restricted_work_case'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['restricted_work_case'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center lostTimeCaseColumn
                                <?= $row['lost_time_case'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['lost_time_case'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center fiveDaysOffColumn
                                <?= $row['five_days_off'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['five_days_off'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center insuranceNotifiedColumn
                                <?= $row['insurance_notified'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['insurance_notified'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center directorNotifiedColumn
                                <?= $row['director_notified'] == 1 ? 'bg-danger text-white' : '' ?>">
                                    <?= $row['director_notified'] == 1 ? 'Yes' : 'No' ?>
                                </td>
                                <td class="py-2 align-middle text-center dateRaisedColumn">
                                    <?= date("j F Y", strtotime($row['date_raised'])) ?>
                                </td>
                                <td class="py-2 align-middle text-center dateClosedColumn" <?= isset($row['date_closed']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['date_closed']) ? date("j F Y", strtotime($row['date_closed'])) : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center additionalCommentsColumn"
                                    <?= isset($row['additional_comments']) && !empty(trim($row['additional_comments']))
                                        ? ""
                                        : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['additional_comments']) && !empty(trim($row['additional_comments'])) ? $row['additional_comments'] : "N/A" ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-end mt-3 pe-2">
                <div class="d-flex align-items-center me-2">
                    <p>Rows Per Page: </p>
                </div>

                <form method="GET" class="me-2">
                    <select class="form-select" name="recordsPerPage" id="recordsPerPage"
                        onchange="updateURLWithRecordsPerPage()">
                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="30" <?php echo $records_per_page == 30 ? 'selected' : ''; ?>>30</option>
                    </select>
                </form>

                <!-- Pagination controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- First Page Button  -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(1); return false;" aria-label="First"
                                    style="cursor: pointer">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;&laquo;</span>
                            </li>
                        <?php endif; ?>
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $page - 1 ?>); return false;"
                                    aria-label="Previous" style="cursor: pointer">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        if ($page === 1 || ($page === $total_pages)) {
                            $page_range = 2;
                        } else {
                            $page_range = 3;
                        }

                        $start_page = max(1, $page - floor($page_range / 2)); // Calculate start page
                        $end_page = min($total_pages, $start_page + $page_range - 1); // Calculate end page
                        
                        // Adjust start page if it goes below 1
                        if ($end_page - $start_page < $page_range - 1) {
                            $start_page = max(1, $end_page - $page_range + 1);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>" style="cursor: pointer">
                                <a class="page-link"
                                    onclick="updatePage(<?php echo $i ?>); return false"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" onclick="updatePage(<?php echo $page + 1; ?>); return false;"
                                    aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Last Page Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $total_pages ?>); return false;"
                                    aria-label="Last" style="cursor: pointer">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
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
                                <div class="d-flex justify-content-between">
                                    <label for="hoursWorkTrir" class="fw-bold">Total Hours Worked <i
                                            class="fa-solid fa-circle-question text-danger tooltips"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Total Hours Worked to Date <?php echo $estimated_working_hours; ?>, with an Active Employee Count of <?php echo $active_employee_count ?>."
                                            style="cursor:pointer"></i></label>
                                    <a role="button" id="showTotalActiveEmployeeTrirInput" class="text-decoration-link">
                                        <small class="pb-0 mb-0">Calculate with total active employee</small></a>
                                </div>
                                <input type="number" name="hoursWorkTrir" class="form-control" id="hoursWorkTrir">

                                <div class="mt-3 d-none" id="totalActiveEmployeeTrirInput">
                                    <div class="d-flex justify-content-between">
                                        <label for="totalActiveEmployeeTrir" class="fw-bold">Total Active
                                            Employees</label>
                                        <a role="button" id="hideTotalActiveEmployeeTrirInput"
                                            class="text-decoration-link">
                                            <small class="pb-0 mb-0">Calculate with total hours</small></a>
                                    </div>
                                    <input type="number" name="totalActiveEmployeeTrir" class="form-control"
                                        id="totalActiveEmployeeTrir">
                                </div>

                                <div class="d-none mt-3">
                                    <label for="totalEmployeesTrir" class="fw-bold">Total Active Employees</label>
                                    <input type="number" name="totalEmployeesTrir" class="form-control"
                                        id="totalEmployeesTrir">
                                </div>
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
                    <h5 class="modal-title">Lost Time Injury Frequency Rates (LTIFR)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold pb-0 mb-0">Formula for LTIFR:</p>
                    <p class="signature-bg-color rounded-3 fw-bold text-white text-center py-2"><span class="mx-2">LTIFR
                            = (Number of Lost Time Incidents / Total Number of Hours Worked) x 1,000,000</span></p>
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
                        <li>Number of Lost Time Incidents: <span id="modalTtmIncidentCount"
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
                            <?php echo number_format(($lost_time_ttm_incident_count / $estimated_working_hours) * 1000000, 2) ?>
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
                                <div class="d-flex justify-content-between">
                                    <label for="hoursWorkLtifr" class="fw-bold">Total Hours Worked <i
                                            class="fa-solid fa-circle-question text-danger tooltips"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Total Hours Worked to Date <?php echo $estimated_working_hours; ?>, with an Active Employee Count of <?php echo $active_employee_count ?>."
                                            style="cursor:pointer"></i></label>
                                    <a role="button" id="showTotalActiveEmployeeLtifrInput"
                                        class="text-decoration-link">
                                        <small class="pb-0 mb-0">Calculate with total active employee</small></a>
                                </div>
                                <input type="number" name="hoursWorkLtifr" class="form-control" id="hoursWorkLtifr">

                                <div class="invalid-feedback">
                                    Please provide the total hours worked.
                                </div>
                            </div>

                            <div class="mt-3 d-none" id="totalActiveEmployeeLtifrInput">
                                <div class="d-flex justify-content-between">
                                    <label for="totalActiveEmployeeLtifr" class="fw-bold">
                                        Total Active Employee
                                    </label>
                                    <a role="button" id="hideTotalActiveEmployeeLtifrInput"
                                        class="text-decoration-link">
                                        <small class="pb-0 mb-0">Calculate with total hours</small>
                                    </a>
                                </div>
                                <input type="number" name="totalActiveEmployeeLtifr" class="form-control"
                                    id="totalActiveEmployeeLtifr">
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

    <!-- ================== WHS Dashboard Modal ================== -->
    <div class="modal fade" id="whsDashboardModal" tabindex="-1" aria-labelledby="whsDashboardModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="whsDashboardModalLabel">WHS Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body background-color">
                    <?php require_once("../PageContent/whs-index-content.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Filter Document Modal ================== -->
    <div class="modal fade" id="filterColumnModal" tab-index="-1" aria-labelledby="filterColumnModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterColumnModalLabel">Filter Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="whsDocumentIdColumn"
                            data-column="whsDocumentIdColumn">
                        <label class="form-check-label" for="whsDocumentIdColumn">
                            WHS ID
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="whsDescriptionColumn"
                            data-column="whsDescriptionColumn">
                        <label class="form-check-label" for="whsDescriptionColumn">
                            Description
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="involvedPersonNameColumn"
                            data-column="involvedPersonNameColumn">
                        <label class="form-check-label" for="involvedPersonNameColumn">Involved Person Name</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="incidentDateColumn"
                            data-column="incidentDateColumn">
                        <label class="form-check-label" for="incidentDateColumn">Incident Date</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="departmentColumn"
                            data-column="departmentColumn">
                        <label class="form-check-label" for="departmentColumn">Department</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="statusColumn" data-column="statusColumn">
                        <label class="form-check-label" for="statusColumn">Status</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="nearMissColumn"
                            data-column="nearMissColumn">
                        <label class="form-check-label" for="nearMissColumn">Near Miss</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="firstAidGivenColumn"
                            data-column="firstAidGivenColumn">
                        <label class="form-check-label" for="firstAidGivenColumn">First Aid Given</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="medicalTreatmentCaseColumn"
                            data-column="medicalTreatmentCaseColumn">
                        <label class="form-check-label" for="medicalTreatmentCaseColumn">Medical Treatment Case</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="recordableIncidentColumn"
                            data-column="recordableIncidentColumn">
                        <label class="form-check-label" for="recordableIncidentColumn">Automated Notifiable / Recordable
                            Incident</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="restrictedWorkCaseColumn"
                            data-column="restrictedWorkCaseColumn">
                        <label class="form-check-label" for="restrictedWorkCaseColumn">Restricted Work Case</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="lostTimeCaseColumn"
                            data-column="lostTimeCaseColumn">
                        <label class="form-check-label" for="lostTimeCaseColumn">Lost Time Case</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="fiveDaysOffColumn"
                            data-column="fiveDaysOffColumn">
                        <label class="form-check-label" for="fiveDaysOffColumn">More Than 5 Days Off Work</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="insuranceNotifiedColumn"
                            data-column="insuranceNotifiedColumn">
                        <label class="form-check-label" for="insuranceNotifiedColumn">Insurance Notified</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="directorNotifiedColumn"
                            data-column="directorNotifiedColumn">
                        <label class="form-check-label" for="directorNotifiedColumn">Director Notified</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="dateRaisedColumn"
                            data-column="dateRaisedColumn">
                        <label class="form-check-label" for="dateRaisedColumn">Date Raised</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="dateClosedColumn"
                            data-column="dateClosedColumn">
                        <label class="form-check-label" for="dateClosedColumn">Date Closed</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="additionalCommentsColumn"
                            data-column="additionalCommentsColumn">
                        <label class="form-check-label" for="additionalCommentsColumn">Additional Comments</label>
                    </div>
                    <div class="d-flex justify-content-end" style="cursor:pointer">
                        <button onclick="resetColumnFilter()" class="btn btn-sm btn-danger me-1"> Reset Filter</button>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

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
            var button = this; // Get the <i> element inside the button
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
            var button = this; // Get the <i> element inside the button
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
                var status = button.getAttribute('data-status');
                var involvedPersonName = button.getAttribute('data-involved-person-name');
                var incidentDate = button.getAttribute('data-incident-date');
                var dateRaised = button.getAttribute('data-date-raised');
                var department = button.getAttribute('data-department');
                var nearMiss = button.getAttribute('data-near-miss');
                var firstAidGiven = button.getAttribute('data-first-aid-given');
                var medicalTreatmentCase = button.getAttribute('data-medical-treatment-case');
                var recordableIncident = button.getAttribute('data-recordable-incident');
                var restrictedWorkCase = button.getAttribute('data-restricted-work-case');
                var lostTimeCase = button.getAttribute('data-lost-time-case');
                var fiveDaysOff = button.getAttribute('data-five-days-off');
                var insuranceNotified = button.getAttribute('data-insurance-notified');
                var directorNotified = button.getAttribute('data-director-notified');
                var dateClosed = button.getAttribute('data-date-closed');
                var additionalComments = button.getAttribute('data-additional-comments');

                // Update the modal's content with the extracted data
                var modalWhsId = myModalEl.querySelector('#whsIdToEdit');
                var modalWhsId2 = myModalEl.querySelector('#whsIdToEdit2');
                var modalWhsId3 = myModalEl.querySelector('#whsIdToEdit3');
                var modalWhsDocumentId = myModalEl.querySelector('#whsDocumentIdToEdit');
                var modalInvolvedPersonName = myModalEl.querySelector('#involvedPersonNameToEdit');
                var modalDescription = myModalEl.querySelector('#whsDescriptionToEdit');
                var modalIncidentDate = myModalEl.querySelector('#incidentDateToEdit');
                var modalDateRaised = myModalEl.querySelector('#dateRaisedToEdit');
                var modalDepartment = myModalEl.querySelector('#departmentToEdit');
                var modalStatus = myModalEl.querySelector('#whsStatus');
                var modalDateClosed = myModalEl.querySelector('#dateClosedToEdit');
                var modalAdditionalComments = myModalEl.querySelector('#additionalCommentsToEdit');

                // Assign the extracted values to the modal input fields
                modalWhsId.value = whsId;
                modalWhsId2.value = whsId;
                modalWhsId3.value = whsId;
                modalWhsDocumentId.value = whsDocumentId;
                modalInvolvedPersonName.value = involvedPersonName;
                modalDescription.value = description;
                modalIncidentDate.value = incidentDate;
                modalDateRaised.value = dateRaised;
                modalDepartment.value = department;
                modalStatus.innerHTML = status;
                if (dateClosed !== null && dateClosed !== '') {
                    modalDateClosed.value = dateClosed;
                }
                modalAdditionalComments.value = additionalComments;

                if (nearMiss === "1") {
                    document.getElementById("nearMissToEditYes").checked = true;
                } else if (nearMiss === "0") {
                    document.getElementById("nearMissToEditNo").checked = true;
                }

                if (firstAidGiven === "1") {
                    document.getElementById("firstAidGivenToEditYes").checked = true;
                } else if (firstAidGiven === "0") {
                    document.getElementById("firstAidGivenToEditNo").checked = true;
                }

                if (medicalTreatmentCase === "1") {
                    document.getElementById("medicalTreatmentCaseToEditYes").checked = true;
                } else if (medicalTreatmentCase === "0") {
                    document.getElementById("medicalTreatmentCaseToEditNo").checked = true;
                }

                // if (recordableIncident === "1") {
                //     document.getElementById("recordableIncidentToEditYes").checked = true;
                // } else if (recordableIncident === "0") {
                //     document.getElementById("recordableIncidentToEditNo").checked = true;
                // }

                if (restrictedWorkCase === "1") {
                    document.getElementById("restrictedWorkCaseToEditYes").checked = true;
                } else if (restrictedWorkCase === "0") {
                    document.getElementById("restrictedWorkCaseToEditNo").checked = true;
                }

                if (lostTimeCase === "1") {
                    document.getElementById("lostTimeCaseToEditYes").checked = true;
                } else if (lostTimeCase === "0") {
                    document.getElementById("lostTimeCaseToEditNo").checked = true;
                }

                if (fiveDaysOff === "1") {
                    document.getElementById("fiveDaysOffToEditYes").checked = true;
                } else if (fiveDaysOff === "0") {
                    document.getElementById("fiveDaysOffToEditNo").checked = true;
                }

                if (insuranceNotified === "1") {
                    document.getElementById("insuranceNotifiedToEditYes").checked = true;
                } else if (insuranceNotified === "0") {
                    document.getElementById("insuranceNotifiedToEditNo").checked = true;
                }

                if (directorNotified === "1") {
                    document.getElementById("directorNotifiedToEditYes").checked = true;
                } else if (directorNotified === "0") {
                    document.getElementById("directorNotifiedToEditNo").checked = true;
                }

                // Show or hide the form based on the status
                var openForm = myModalEl.querySelector('#openForm');

                var documentCloseText = myModalEl.querySelector('#documentCloseText');

                console.log(status);

                if (status !== "Open") {
                    openForm.style.display = 'none'; //Hide the form
                    documentCloseText.innerHTML = "This WHS document has been closed."; // Set the message
                    documentCloseText.classList.remove('d-none'); // Show the message
                    openWhsBtn.classList.remove('d-none');
                    cancelOpenWhsBtn.classList.remove('d-none');
                } else {
                    openForm.style.display = 'block';
                    documentCloseText.classList.add('d-none'); // Hide the message
                    openWhsBtn.classList.add('d-none');
                    cancelOpenWhsBtn.classList.add('d-none');
                }
            })
        })
    </script>

    <script>
        function updatePage(page) {
            // Check if page number is valid
            if (page < 1) return;

            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
    </script>
    <script>
        function updateURLWithRecordsPerPage() {
            const selectElement = document.getElementById('recordsPerPage');
            const recordsPerPage = selectElement.value;
            const url = new URL(window.location.href);
            url.searchParams.set('recordsPerPage', recordsPerPage);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }
    </script>
    <script>
        const STORAGE_EXPIRATION_TIME = 8 * 60 * 60 * 1000; // 8 hours in milliseconds

        // Save checkbox state to localStorage with a timestamp
        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const columnClass = this.getAttribute('data-column');
                const columns = document.querySelectorAll(`.${columnClass}`);
                columns.forEach(column => {
                    if (this.checked) {
                        column.style.display = '';
                        localStorage.setItem(columnClass, 'visible');
                    } else {
                        column.style.display = 'none';
                        localStorage.setItem(columnClass, 'hidden');
                    }
                });
                localStorage.setItem(columnClass + '_timestamp', Date.now()); // Save current timestamp
            })
        });

        // Initialize checkboxes based on current column visibility
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                const columnClass = checkbox.getAttribute('data-column');
                const columns = document.querySelectorAll(`.${columnClass}`);

                // Retrieve stored visibility state and timestamp
                const storedVisibility = localStorage.getItem(columnClass);
                const storedTimestamp = localStorage.getItem(columnClass + '_timestamp');
                const currentTime = Date.now();

                // Check if stored timestamp is within the expiration time
                if (storedTimestamp && (currentTime - storedTimestamp <= STORAGE_EXPIRATION_TIME)) {
                    if (storedVisibility === 'hidden') {
                        columns.forEach(column => column.style.display = 'none');
                        checkbox.checked = false;
                    } else {
                        columns.forEach(column => column.style.display = '');
                        checkbox.checked = true;
                    }
                } else {
                    // Clear the localStorage if timestamp is expired
                    localStorage.removeItem(columnClass);
                    localStorage.removeItem(columnClass + '_timestamp');
                    columns.forEach(column => column.style.display = '');
                    checkbox.checked = true;
                }
            });
        });
    </script>
    <script>
        document.querySelectorAll('.dropdown-menu .dropdown-status-item').forEach(item => {
            item.addEventListener('click', function (event) {
                event.preventDefault(); // Prevent default anchor click behavior
                let status = this.getAttribute('data-status-filter');
                if (status === "All Status") {
                    document.getElementById('selectedStatusFilter').value = "";
                } else {
                    document.getElementById('selectedStatusFilter').value = status;
                }
                this.closest('form').submit();
            })
        })
    </script>
    <script>
        function updateSort(sort, order) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
            window.location.href = url.toString();
        }
    </script>
    <script>
        // Load saved zoom level from localStorage or use default
        let currentZoom = parseFloat(localStorage.getItem('zoomLevel')) || 1;

        // Apply the saved zoom level
        document.body.style.zoom = currentZoom;

        function zoom(factor) {
            currentZoom *= factor;
            document.body.style.zoom = currentZoom;

            // Save the new zoom level to localStorage
            localStorage.setItem('zoomLevel', currentZoom);
        }

        function resetZoom() {
            currentZoom = 1;
            document.body.style.zoom = currentZoom;

            // Remove the zoom level from localStorage
            localStorage.removeItem('zoomLevel');
        }

        // Optional: Reset zoom level on page load
        window.addEventListener('load', () => {
            document.body.style.zoom = currentZoom;
        });
    </script>
    <script>
        // TRIR hours worked input and total active employees
        document.getElementById('showTotalActiveEmployeeTrirInput').addEventListener('click', function () {
            document.getElementById('showTotalActiveEmployeeTrirInput').classList.add('d-none');
            document.getElementById('totalActiveEmployeeTrirInput').classList.remove('d-none');
            document.getElementById('hoursWorkTrir').disabled = true;
        })

        document.getElementById('hideTotalActiveEmployeeTrirInput').addEventListener('click', function () {
            document.getElementById('showTotalActiveEmployeeTrirInput').classList.remove('d-none');
            document.getElementById('totalActiveEmployeeTrirInput').classList.add('d-none');
            document.getElementById('hoursWorkTrir').disabled = false;
        })

        document.getElementById('totalActiveEmployeeTrirInput').addEventListener('input', function () {
            let totalActiveEmployeeTrirInputValue = document.getElementById('totalActiveEmployeeTrir').value;
            document.getElementById('hoursWorkTrir').value = (totalActiveEmployeeTrirInputValue * 38 * 52 * 1.1).toFixed(2);
        })

        document.getElementById('trirModal').addEventListener('hide.bs.modal', function () {
            // Run your function when the modal is dismissed
            document.getElementById('showTotalActiveEmployeeTrirInput').classList.remove('d-none');
            document.getElementById('totalActiveEmployeeTrirInput').classList.add('d-none');
            document.getElementById('hoursWorkTrir').disabled = false;
        });

        // LTIFR hours worked input and total active employees
        document.getElementById('showTotalActiveEmployeeLtifrInput').addEventListener('click', function () {
            document.getElementById('showTotalActiveEmployeeLtifrInput').classList.add('d-none');
            document.getElementById('totalActiveEmployeeLtifrInput').classList.remove('d-none');
            document.getElementById('hoursWorkLtifr').disabled = true;
        })

        document.getElementById('hideTotalActiveEmployeeLtifrInput').addEventListener('click', function () {
            document.getElementById('showTotalActiveEmployeeLtifrInput').classList.remove('d-none');
            document.getElementById('totalActiveEmployeeLtifrInput').classList.add('d-none');
            document.getElementById('hoursWorkLtifr').disabled = false;
        })

        document.getElementById('totalActiveEmployeeLtifrInput').addEventListener('input', function () {
            let totalActiveEmployeeLtifrInputValue = document.getElementById('totalActiveEmployeeLtifr').value;
            document.getElementById('hoursWorkLtifr').value = (totalActiveEmployeeLtifrInputValue * 38 * 52 * 1.1).toFixed(2);
        })

        document.getElementById('ltifrModal').addEventListener('hide.bs.modal', function () {
            // Run your function when the modal is dismissed
            document.getElementById('showTotalActiveEmployeeLtifrInput').classList.remove("d-none");
            document.getElementById('totalActiveEmployeeLtifrInput').classList.add('d-none');
            document.getElementById('hoursWorkLtifr').disabled = false;
        })
    </script>
    <script>
        function resetColumnFilter() {
            // Get all checkboxes
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                // Check each checkbox
                checkbox.checked = true;

                // Get the column class associated with the checkbox
                const columnClass = checkbox.getAttribute('data-column');

                // Get all columns with that class
                const columns = document.querySelectorAll(`.${columnClass}`);

                // Show all columns
                columns.forEach(column => {
                    column.style.display = '';
                });

                // Also update localStorage to reflect the reset state
                localStorage.setItem(columnClass, 'visible');
                localStorage.removeItem(columnClass + '_timestamp'); // Clear the timestamp
            });
        }
    </script>
</body>

</html>