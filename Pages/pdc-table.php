<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

date_default_timezone_set('Australia/Sydney');

$folder_name = "Project";
require_once("../group_role_check.php");

if ($role !== "full control" && $role !== "modify 2") {
    header("Location: http://$serverAddress/$projectName/access_restricted.php");
    exit();
}

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Sorting Variables
$sortKey = isset($_GET['sort']) ? $_GET['sort'] : 'item_number';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

switch ($sortKey) {
    case 'PO':
        $sortSql = "(CASE WHEN aws_po_no IS NOT NULL THEN 1 ELSE 0 END)";
        break;
    case 'rosdCorrect':
        $sortSql = "(CASE
            WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN rOSD_changed
            WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN rOSD_po
            WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN rOSD_forecast
            ELSE NULL
        END)";
        break;
    case 'estimatedDepartureDate':
        $sortSql = "(CASE
                WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN
                    DATE_SUB(rOSD_changed, INTERVAL
                        CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN
                    DATE_SUB(rOSD_po, INTERVAL
                        CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN
                    DATE_SUB(rOSD_forecast, INTERVAL
                        CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                ELSE NULL
            END)";
        break;
    default:
        $allowedColumns = ['item_number', 'project_no', 'fbn', 'site_type', 'status', 'version', 'qty', 'cost', 'rosd_forecast', 'conflict', 'actual_departure_date', 'actual_delivered_date'];
        $sortSql = in_array($sortKey, $allowedColumns) ? $sortKey : 'item_number';
        break;
}

// Pagination 
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 50; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$whereClause = "(fbn LIKE '%$searchTerm%' OR project_no LIKE '%$searchTerm%' OR serial_numbers LIKE '%$searchTerm%')";

// Arrays to hold selected filter values
$selected_status = [];
$selected_version = [];
$rosd_correct_start = '';
$rosd_correct_end = '';
$estimated_departure_start = '';
$estimated_departure_end = '';

$filterApplied = false;

if (isset($_GET['apply_filters'])) {
    // Status filter
    if (isset($_GET['status']) && is_array($_GET['status'])) {
        $selected_status = $_GET['status'];

        // Separate N/A (null) from the normal status values
        $normal_status = array_filter($selected_status, fn($s) => $s !== 'N/A');
        $has_na = in_array('N/A', $selected_status);

        $conditions = [];
        if (!empty($normal_status)) {
            $status_placeholders = "'" . implode("','", $normal_status) . "'";
            $conditions[] = "`status` IN ($status_placeholders)";
        }
        if ($has_na) {
            $conditions[] = "`status` IS NULL";
        }

        if (!empty($conditions)) {
            $whereClause .= " AND (" . implode(" OR ", $conditions) . ")";
            $filterApplied = true;
        }
    }

    // Version filter 
    if (isset($_GET['version']) && is_array($_GET['version'])) {
        $selected_version = $_GET['version'];

        // Separate N/A (null) from the normal version values
        $normal_version = array_filter($selected_version, fn($s) => $s !== 'N/A');
        $has_na = in_array('N/A', $selected_version);

        $conditions = [];
        if (!empty($normal_version)) {
            $version_placeholders = "'" . implode("','", $normal_version) . "'";
            $conditions[] = "`version` IN ($version_placeholders)";
        }
        if ($has_na) {
            $conditions[] = "`version` IS NULL";
        }

        if (!empty($conditions)) {
            $whereClause .= " AND (" . implode(" OR ", $conditions) . ")";
            $filterApplied = true;
        }
    }

    // Airport filter
    if (isset($_GET['airport']) && is_array($_GET['airport'])) {
        $selected_airport = $_GET['airport'];

        if (!empty($selected_airport)) {
            $airport_conditions = [];
            foreach ($selected_airport as $airport) {
                $airport_conditions[] = "fbn LIKE '" . $conn->real_escape_string($airport) . "%'";
            }

            if (!empty($airport_conditions)) {
                $whereClause .= " AND (" . implode(" OR ", $airport_conditions) . ")";
                $filterApplied = true;
            }
        }
    }

    // rOSD (Correct) Timeframe filter - Month only
    if (!empty($_GET['rosd_correct_start']) && !empty($_GET['rosd_correct_end'])) {
        $rosd_correct_start = $_GET['rosd_correct_start'];
        $rosd_correct_end = $_GET['rosd_correct_end'];

        // Convert month inputs to date ranges (first day of start month to last day of end month)
        $start_date = date('Y-m-01', strtotime($rosd_correct_start));
        $end_date = date('Y-m-t', strtotime($rosd_correct_end));

        $whereClause .= " AND (
            -- Case 1: If approved = 1 and rOSD_changed is set, use rOSD_changed
            (approved = 1 AND rOSD_changed IS NOT NULL AND rOSD_changed BETWEEN '$start_date' AND '$end_date')
            
            -- Case 2: Else check PO resolution
            OR ( (approved <> 1 OR rOSD_changed IS NULL) 
                AND resolved = 'PO' 
                AND rOSD_po IS NOT NULL 
                AND rOSD_po BETWEEN '$start_date' AND '$end_date')

            -- Case 3: Else check Forecast resolution
            OR ( (approved <> 1 OR rOSD_changed IS NULL) 
                AND resolved = 'Forecast' 
                AND rOSD_forecast IS NOT NULL 
                AND rOSD_forecast BETWEEN '$start_date' AND '$end_date')

            -- Case 4: Else if not approved and no resolution yet, use forecast
            OR ( (approved = 0 AND resolved IS NULL) 
                AND rOSD_forecast IS NOT NULL 
                AND rOSD_forecast BETWEEN '$start_date' AND '$end_date')
        )";

        $filterApplied = true;
    }

    // Estimated Departure Date Timeframe filter - Month only
    if (!empty($_GET['estimated_departure_start']) && !empty($_GET['estimated_departure_end'])) {
        $start_date = date('Y-m-01', strtotime($_GET['estimated_departure_start']));
        $end_date = date('Y-m-t', strtotime($_GET['estimated_departure_end']));

        $whereClause .= " AND (
            CASE
                -- Priority 1: approved rOSD_changed
                WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN
                    DATE_SUB(
                        rOSD_changed,
                        INTERVAL CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                -- Priority 2: PO rOSD only if resolved = 'PO'
                WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN
                    DATE_SUB(
                        rOSD_po,
                        INTERVAL CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                -- Priority 3: Forecast rOSD only if resolved = 'Forecast'
                WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN
                    DATE_SUB(
                        rOSD_forecast,
                        INTERVAL CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                -- No fallback: if none of the above applies, return NULL
                ELSE NULL
            END BETWEEN '$start_date' AND '$end_date'
        )";

        $filterApplied = true;
    }

    // Purchase Order Filter
    if (isset($_GET['purchaseOrderFilter'])) {
        $purchaseOrderFilter = $_GET['purchaseOrderFilter'];

        if ($purchaseOrderFilter === "Yes") {
            $whereClause .= " AND aws_po_no IS NOT NULL AND aws_po_no != ''";
        } else if ($purchaseOrderFilter === "No") {
            $whereClause .= " AND (aws_po_no IS NULL OR aws_po_no = '')";
        }

        $filterApplied = true;
    }
}

$project_sql = "SELECT * FROM pdc_projects WHERE $whereClause 
ORDER BY $sortSql $order 
LIMIT $offset, $records_per_page";
$project_result = $conn->query($project_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM pdc_projects WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get total cost
$total_cost_sql = "SELECT SUM(cost) AS total_cost FROM pdc_projects WHERE $whereClause";
$total_cost_result = $conn->query($total_cost_sql);
$total_cost = $total_cost_result->fetch_assoc()['total_cost'] ?? 0;

// Get all URL parameters from $_GET
$urlParams = $_GET;

// Fetch results
$projects = [];
if ($project_result->num_rows > 0) {
    while ($row = $project_result->fetch_assoc()) {
        $projects[] = $row;
    }
} else {
    $projects = [];
}

// Import Date
$import_date_sql = "SELECT MAX(import_date) AS last_update FROM pdc_projects";
$import_date_result = $conn->query($import_date_sql);

$lastUpdate = "No Data";
if ($import_date_result && $import_date_result->num_rows > 0) {
    $row = $import_date_result->fetch_assoc();
    if (!empty($row['last_update'])) {
        $lastUpdate = date('j F Y', strtotime($row['last_update']));
    }
}

// ========================= D E L E T E  P D C  P R O J E C T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['fbnIdToDelete'])) {
    $fbnIdToDelete = $_POST["fbnIdToDelete"];

    $delete_document_sql = "DELETE FROM pdc_projects WHERE pdc_project_id = ?";
    $delete_document_result = $conn->prepare($delete_document_sql);
    $delete_document_result->bind_param("i", $fbnIdToDelete);

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

// ========================= A D D  P D C  P R O J E C T (.csv) =========================
if (isset($_POST['import_csv']) && isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {

    require_once '../db_connect.php';

    $fileTmpPath = $_FILES['csvFile']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['csvFile']['name'], PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        echo "<script>alert('Invalid file type. Please upload a .csv file.');</script>";
        return;
    }

    // Manual import date
    $importDate = $_POST['importDate'];

    // Convert date function
    function convertDate($value)
    {
        $formats = ['m/d/Y', 'd/m/Y', 'Y-m-d'];
        foreach ($formats as $format) {
            $dateObj = DateTime::createFromFormat($format, $value);
            if ($dateObj) {
                return $dateObj->format('Y-m-d');
            }
        }
        return null;
    }

    // Get latest item_number
    $result = $conn->query("SELECT item_number FROM pdc_projects ORDER BY item_number DESC LIMIT 1");
    $latestItemNumber = ($row = $result->fetch_assoc()) ? (int)$row['item_number'] : 0;

    if (($handle = fopen($fileTmpPath, 'r')) !== false) {

        fgetcsv($handle); // skip header

        // Prepare statement
        $stmt = $conn->prepare("
            INSERT INTO pdc_projects (item_number, fbn, site_type, rOSD_forecast, qty, version, import_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
            ON DUPLICATE KEY UPDATE 
                site_type = VALUES(site_type),
                rOSD_forecast = VALUES(rOSD_forecast),
                qty = VALUES(qty),
                version = VALUES(version),
                import_date = VALUES(import_date),
                status = CASE 
                    WHEN status IN ('AWS Removed FF', 'Cancelled') THEN NULL
                    ELSE status 
                END
        ");

        // Track imported FBNs
        $importedFBNs = [];

        while (($row = fgetcsv($handle, 2000, ',')) !== false) {

            if (count($row) < 5) continue;

            $row = array_map(fn($item) => mb_convert_encoding(trim($item), 'UTF-8', 'auto'), $row);

            [$fbn, $siteType, $rosdForecast, $qty, $version] = $row;

            // Add to imported list
            $importedFBNs[] = $fbn;

            // Convert date formats
            $rosdForecast = convertDate($rosdForecast);

            // Version mapping
            $versionMap = [
                'V2' => 'V2.0 630A TF Double Door (IEC)',
                'V3' => 'V3.0. 1000A TF (IEC)'
            ];
            $version = $versionMap[$version] ?? $version;

            // Increment item number
            $latestItemNumber++;

            // Bind and insert
            $stmt->bind_param("isssiss", $latestItemNumber, $fbn, $siteType, $rosdForecast, $qty, $version, $importDate);
            $stmt->execute();
        }

        fclose($handle);
        $stmt->close();

        // --------------------------------------------
        // STEP: Mark missing FBNs as "AWS Removed FF"
        // --------------------------------------------
        if (!empty($importedFBNs)) {

            // Escape FBN list
            $escapedList = "'" . implode("','", array_map([$conn, 'real_escape_string'], $importedFBNs)) . "'";

            $conn->query("
                UPDATE pdc_projects
                SET status = 'AWS Removed FF'
                WHERE status IS NULL
                AND fbn NOT IN ($escapedList)
            ");
        }

        echo "<script>alert('CSV Imported Successfully');</script>";

        // Refresh current page
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: $current_url");
        exit();

    } else {
        echo "<script>alert('Failed to open CSV file.');</script>";
    }
}

// ========================= E D I T  A C T U A L  D E P A R T U R E  D A T E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pdcIdToEditActualDepartureDateCell'])) {
    $pdcProjectId = $_POST["pdcIdToEditActualDepartureDateCell"];
    $actualDepartureDate = $_POST["actualDepartureDateCellToEdit"];

    // If date is empty, set to NULL
    $actualDepartureDate = !empty($actualDepartureDate) ? $actualDepartureDate : null;

    $update_sql = "UPDATE pdc_projects SET actual_departure_date = ? WHERE pdc_project_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $actualDepartureDate, $pdcProjectId);

    if ($update_stmt->execute()) {
        // Redirect back to the same page to see the updated value
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating actual departure date: " . $conn->error;
    }
    $update_stmt->close();
}

// ========================= E D I T  A C T U A L  D E L I V E R E D  D A T E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pdcIdToEditActualDeliveredDateCell'])) {
    $pdcProjectId = $_POST["pdcIdToEditActualDeliveredDateCell"];
    $actualDeliveredDate = $_POST["actualDeliveredDateCellToEdit"];

    // If date is empty, set to NULL
    $actualDeliveredDate = !empty($actualDeliveredDate) ? $actualDeliveredDate : null;

    $update_sql = "UPDATE pdc_projects SET actual_delivered_date = ? WHERE pdc_project_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $actualDeliveredDate, $pdcProjectId);

    if ($update_stmt->execute()) {
        // Redirect back to the same page to see the updated value
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) { 
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating actual delivered date: " . $conn->error;
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>PDC Project Table</title>
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

        .btn-check:checked+.btn {
            background-color: #043f9d !important;
            color: #fff;
        }

        .btn-check {
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<body class="background-color">
    <?php require("../Menu/NavBar.php") ?>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <ul class="nav mb-3" role="tablist">
                <li class="nav-item me-4" role="presentation">
                    <a href="http://<?= $serverAddress ?>/<?= $projectName ?>/Pages/project-table.php"
                        class="nav-link border-0 border-bottom <?= basename($_SERVER['PHP_SELF']) === 'project-table.php' ? 'border-3 border-primary text-primary fw-bold' : 'text-secondary' ?>">
                        Projects
                    </a>
                </li>
                <li class="nav-item me-4" role="presentation">
                    <a href="http://<?= $serverAddress ?>/<?= $projectName ?>/Pages/pdc-table.php"
                        class="nav-link border-0 border-bottom <?= basename($_SERVER['PHP_SELF']) === 'pdc-table.php' ? 'border-3 border-primary text-primary fw-bold' : 'text-secondary' ?>">
                        PDC Projects
                    </a>
                </li>
            </ul>
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
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center w-100">
                <!-- Left Section: Search Form and Filter Button -->
                <div class="col-12 col-sm-8 col-lg-5 d-flex justify-content-between align-items-center mb-3 mb-sm-0">
                    <form method="GET" id="searchForm" class="d-flex align-items-center w-100">
                        <?php
                        foreach ($urlParams as $key => $value) {
                            if ($key === 'search')
                                continue; // skip search because it has its own input
                            // If the param is an array (e.g. filters with multiple values), add one hidden input per value
                            if (is_array($value)) {
                                foreach ($value as $v) {
                                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($v) . '">';
                                }
                            } else {
                                echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                            }
                        }
                        ?>
                        <input type="hidden" name="page" value="1">
                        <div class="d-flex align-items-center w-100">
                            <!-- Search Input Group  -->
                            <div class="input-group me-2 flex-grow-1">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="searchDocuments" name="search"
                                    placeholder="Search Documents" value="<?php echo htmlspecialchars($searchTerm) ?>">
                            </div>
                            <!-- Search Button -->
                            <button class="btn" type="submit"
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">
                                Search
                            </button>
                            <!-- Clear Button -->
                            <button class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </button>
                        </div>
                    </form>
                    <!-- Filter Modal Trigger Button -->
                    <button class="btn text-white ms-2 bg-dark" data-bs-toggle="modal"
                        data-bs-target="#filterProjectModal">
                        <p class="text-nowrap fw-bold mb-0 pb-0">Filter by <i class="fa-solid fa-filter py-1"></i></p>
                    </button>
                </div>

                <div
                    class="col-12 col-sm-4 col-lg-7 d-flex justify-content-center justify-content-sm-end align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="d-block p-2 bg-danger text-white rounded-3 fw-bold text-center me-2">
                            Last Updated: <?= $lastUpdate ?>
                        </span>
                        <?php if ($role === "full control" || $role === "modify 2") { ?>
                            <!-- Admin Buttons -->
                            <button class="btn btn-success me-2" data-bs-toggle="modal"
                                data-bs-target="#projectReportModal">
                                <i class="fa-solid fa-square-poll-vertical"></i> Report
                            </button>
                            <button class="btn btn-primary me-2" onclick="exportToExcel()"> Export to Excel
                            </button>
                            <?php if ($role === "full control") { ?>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-dark dropdown-toggle" data-bs-toggle="dropdown"><i
                                            class="fa-solid fa-plus"></i> Add Project</i></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" type="button" data-bs-toggle="modal"
                                            data-bs-target="#addDocumentModal">Add Project (Manual)</a>
                                        <a class="dropdown-item" type="button" data-bs-toggle="modal"
                                            data-bs-target="#addProjectCSV">Import CSV </a>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $rosd_start = $urlParams['rosd_correct_start'] ?? '';
        $rosd_end = $urlParams['rosd_correct_end'] ?? '';
        $estimated_departure_start = $urlParams['estimated_departure_start'] ?? '';
        $estimated_departure_end = $urlParams['estimated_departure_end'] ?? '';

        // Format the dates if they exist
        if ($rosd_start) {
            $startDate = DateTime::createFromFormat('Y-m', $rosd_start);
            $rosd_start_formatted = $startDate ? $startDate->format('M Y') : $rosd_start;
        }

        if ($rosd_end) {
            $endDate = DateTime::createFromFormat('Y-m', $rosd_end);
            $rosd_end_formatted = $endDate ? $endDate->format('M Y') : $rosd_end;
        }

        if ($estimated_departure_start) {
            $startDate = DateTime::createFromFormat('Y-m', $estimated_departure_start);
            $estimated_departure_start_formatted = $startDate ? $startDate->format('M Y') : $estimated_departure_start;
        }

        if ($estimated_departure_end) {
            $endDate = DateTime::createFromFormat('Y-m', $estimated_departure_end);
            $estimated_departure_end_formatted = $endDate ? $endDate->format('M Y') : $estimated_departure_end;
        }
        ?>
        <?php foreach ($urlParams as $key => $value): ?>
            <?php if (!empty($value)): ?>
                <?php
                // Status badges
                if ($key === 'status' && is_array($value)) {
                    foreach ($value as $status) { ?>
                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                            <strong><span class="text-warning">Status: </span><?php echo htmlspecialchars($status); ?></strong>
                            <a href="?<?php
                            $filteredParams = $_GET;
                            $filteredParams['status'] = array_diff($filteredParams['status'], [$status]);
                            echo http_build_query($filteredParams);
                            ?>" class="text-white ms-1">
                                <i class="fa-solid fa-times"></i>
                            </a>
                        </span>
                    <?php }
                }

                // Version badges
                if ($key === 'version' && is_array($value)) {
                    foreach ($value as $version) { ?>
                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                            <strong><span class="text-warning">Version: </span><?php echo htmlspecialchars($version); ?></strong>
                            <a href="?<?php
                            $filteredParams = $_GET;
                            $filteredParams['version'] = array_diff($filteredParams['version'], [$version]);
                            echo http_build_query($filteredParams);
                            ?>" class="text-white ms-1">
                                <i class="fa-solid fa-times"></i>
                            </a>
                        </span>
                    <?php }
                }

                // Airport Filter Badges
                if ($key === 'airport' && is_array($value)) {
                    foreach ($value as $airport) { ?>
                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                            <strong><span class="text-warning">Airport: </span><?php echo htmlspecialchars($airport); ?></strong>
                            <a href="?<?php
                            $filteredParams = $_GET;
                            $filteredParams['airport'] = array_diff($filteredParams['airport'], [$airport]);
                            echo http_build_query($filteredParams);
                            ?>" class="text-white ms-1">
                                <i class="fa-solid fa-times"></i>
                            </a>
                        </span>
                    <?php }
                }

                // Purchase Order Filter Badge - Show even for "Any" values
                if ($key === 'purchaseOrderFilter' && !empty($value)) { ?>
                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                        <strong>
                            <span class="text-warning">Purchase Order: </span>
                            <?php echo htmlspecialchars($value); ?>
                        </strong>
                        <a href="?<?php
                        $filteredParams = $_GET;
                        unset($filteredParams['purchaseOrderFilter']); // remove filter completely
                        echo http_build_query($filteredParams);
                        ?>" class="text-white ms-1">
                            <i class="fa-solid fa-times"></i>
                        </a>
                    </span>
                <?php }

                // ROSD Correct badge 
                if (($key === 'rosd_correct_start' || $key === 'rosd_correct_end') && ($rosd_start_formatted || $rosd_end_formatted)) { ?>
                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                        <strong><span class="text-warning">rosd(Correct): </span>
                            <?php echo htmlspecialchars($rosd_start_formatted); ?> -
                            <?php echo htmlspecialchars($rosd_end_formatted); ?>
                        </strong>
                        <a href="?<?php
                        $filteredParams = $_GET;
                        unset($filteredParams['rosd_correct_start'], $filteredParams['rosd_correct_end']);
                        echo http_build_query($filteredParams);
                        ?>" class="text-white ms-1">
                            <i class="fa-solid fa-times"></i>
                        </a>
                    </span>
                    <?php
                    // prevent multiple badges by unsetting after showing
                    $rosd_start_formatted = $rosd_end_formatted = '';
                }

                // Estimated Departure Date Correct badge 
                if (($key === 'estimated_departure_start' || $key === 'estimated_departure_end') && ($estimated_departure_start_formatted || $estimated_departure_end_formatted)) { ?>
                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                        <strong><span class="text-warning">Est. Departure: </span>
                            <?php echo htmlspecialchars($estimated_departure_start_formatted); ?> -
                            <?php echo htmlspecialchars($estimated_departure_end_formatted); ?>
                        </strong>
                        <a href="?<?php
                        $filteredParams = $_GET;
                        unset($filteredParams['estimated_departure_start'], $filteredParams['estimated_departure_end']);
                        echo http_build_query($filteredParams);
                        ?>" class="text-white ms-1">
                            <i class="fa-solid fa-times"></i>
                        </a>
                    </span>
                    <?php
                    // prevent multiple badges by unsetting after showing
                    $estimated_departure_start_formatted = $estimated_departure_end_formatted = '';
                }
                ?>
            <?php endif ?>
        <?php endforeach; ?>

        <!-- Display message if filters are applied, and show total count or no results message -->
        <?php if ($filterApplied): ?>
            <div class="alert <?php echo ($total_records == 0) ? 'alert-danger' : 'alert-info'; ?>">
                <?php if ($total_records > 0): ?>
                    <strong>Total Results:</strong>
                    <span class="fw-bold text-decoration-underline me-2">
                        <?php echo $total_records ?>
                    </span>

                    <br>

                    <strong>Total Cost:</strong>
                    <span class="fw-bold text-decoration-underline">
                        <?php echo "$" . number_format($total_cost, 2); ?>
                    </span>
                <?php else: ?>
                    <strong>No results found for the selected filters.</strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="py-1 align-middle text-center itemNumber" style="cursor: pointer"><a
                                onclick="updateSort('item_number', '<?= $order ?>')">Item No
                                <i class="fa-solid fa-sort fa-md ms-1"></i> </a></th>
                        <th class="py-1 align-middle text-center projectNo" style="cursor: pointer"><a
                                onclick="updateSort('project_no', '<?= $order ?>')">Project No
                                <i class="fa-solid fa-sort fa-md ms-1"></i> </a></th>
                        <th class="py-1 align-middle text-center fbn" style="cursor: pointer"><a
                                onclick="updateSort('fbn', '<?= $order ?>')">FBN <i
                                    class="fa-solid fa-sort fa-md ms-1"></i> </a></th>
                        <th class="py-1 align-middle text-center siteType"><a
                                onclick="updateSort('site_type', '<?= $order ?>')">Site Type
                                <i class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <!-- <th class="py-1 align-middle text-center">FBN</th> -->
                        <!-- <th class="py-1 align-middle text-center">C-Code</th> -->
                        <th class="py-1 align-middle text-center status" style="cursor: pointer;"> <a
                                onclick="updateSort('status', '<?= $order ?>')"> Status <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <!-- <th class="py-1 align-middle text-center" style="min-width:150px">Customer</th> -->
                        <!-- <th class="py-1 align-middle text-center" style="min-width:200px">Entity's PO No.</th> -->
                        <!-- <th class="py-1 align-middle text-center">AWS's PO No.</th> -->
                        <th class="py-1 align-middle text-center po" style="cursor: pointer;"> <a
                                onclick="updateSort('PO', '<?= $order ?>')"> PO <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a></th>
                        <!-- <th class="py-1 align-middle text-center" style="min-width:150px">PO Date</th> -->
                        <!-- <th class="py-1 align-middle text-center">Production from PO</th> -->
                        <th class="py-1 align-middle text-center version" style="min-width:280px; cursor: pointer;"> <a
                                onclick="updateSort('version', '<?= $order ?>')"> Version<i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a></th>
                        <!-- <th class="py-1 align-middle text-center" style="min-width:280px;">Drawing Status</th> -->
                        <th class="py-1 align-middle text-center qty" style="cursor:pointer"> <a
                                onclick="updateSort('qty', '<?= $order ?>')"> Qty <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a></th>
                        <th class="py-1 align-middle text-center cost" style="cursor: pointer"> <a
                                onclick="updateSort('cost', '<?= $order ?>')"> Cost <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <!-- <th class="py-1 align-middle text-center">Serial Numbers</th> -->
                        <!-- <th class="py-1 align-middle text-center" style="min-width:180px">rOSD (PO)</th> -->
                        <th class="py-1 align-middle text-center rosdForecast" style="min-width:180px; cursor: pointer">
                            <a onclick="updateSort('rosd_forecast', '<?= $order ?>')"> rOSD
                                (Forecast) <i class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-1 align-middle text-center redFlag" style="cursor: pointer"> <a
                                onclick="updateSort('conflict', '<?= $order ?>')"> Red Flag <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <!-- <th class="py-1 align-middle text-center">Resolved</th> -->
                        <!-- <th class="py-1 align-middle text-center" style="min-width:180px">rOSD (Resolved)</th> -->
                        <!-- <th class="py-1 align-middle text-center" style="min-width:180px">rOSD (Changed)</th> -->
                        <!-- <th class="py-1 align-middle text-center">Approved</th> -->
                        <th class="py-1 align-middle text-center rosdCorrect" style="min-width:180px; cursor: pointer;">
                            <a onclick="updateSort('rosdCorrect', '<?= $order ?>')"> rOSD
                                (Correct) <i class="fa-solid fa-sort fa-ms ms-1"></i> </a>
                        </th>
                        <!-- <th class="py-1 align-middle text-center">Freight Type</th> -->
                        <th class="py-1 align-middle text-center estimatedDepartureDate"
                            style="min-width:180px; cursor: pointer;"> <a
                                onclick="updateSort('estimatedDepartureDate', '<?= $order ?>')">
                                Estimated Departure Date <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <th class="py-1 align-middle text-center actualDepartureDate"
                            style="min-width:180px; cursor:pointer;"><a
                                onclick="updateSort('actual_departure_date', '<?= $order ?>')">
                                Actual
                                Departure Date <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <th class="py-1 align-middle text-center actualDeliveredDate"
                            style="min-width:180px; cursor: pointerl"> <a
                                onclick="updateSort('actual_delivered_date', '<?= $order ?>')">
                                Actual
                                Delivered Date <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($projects)) { ?>
                        <?php
                        $fbn_pattern = '/^([A-Za-z]+)(\d+)([A-Za-z]+)(\d+)?$/';
                        $counter = 0;
                        foreach ($projects as $row) {
                            $fbn_raw = $row['fbn'];
                            $uniqueId = 'detailsRow' . $counter;

                            // Split FBN if it contains " - "
                            $fbn_parts = explode(' - ', $fbn_raw);
                            $fbn_main = trim($fbn_parts[0]);

                            // Extract C-Code: first 3 letters of main FBN
                            $airport_code = substr($fbn_main, 0, 3); ?>
                            <tr>
                                <?php
                                // Determine background class based on PO status
                                $poBgClass = '';
                                if (!empty($row['aws_po_no']) && !empty($row['entity_po_no'])) {
                                    $poBgClass = 'bg-success bg-opacity-75';
                                } elseif (!empty($row['aws_po_no']) && empty($row['entity_po_no'])) {
                                    $poBgClass = 'bg-success bg-opacity-25';
                                }
                                ?>
                                <td class="p-1 align-middle text-center">
                                    <div class="d-flex">
                                        <button id="editDocumentModalBtn" class="btn" data-bs-toggle="modal"
                                            data-bs-target="#editDocumentModal"
                                            data-pdc-project-id="<?= $row['pdc_project_id'] ?>"
                                            data-project-no="<?= $row['project_no'] ?>" data-fbn="<?= $row['fbn'] ?>"
                                            data-site-type="<?= $row['site_type'] ?>" data-status="<?= $row['status'] ?>"
                                            data-customer="<?= $row['customer'] ?>"
                                            data-entity-po-no="<?= $row['entity_po_no'] ?>"
                                            data-aws-po-no="<?= $row['aws_po_no'] ?>"
                                            data-po-date="<?= $row['purchase_order_date'] ?>"
                                            data-version="<?= $row['version'] ?>"
                                            data-drawing-status="<?= $row['drawing_status'] ?>"
                                            data-quantity="<?= $row['qty'] ?>" data-cost="<?= $row['cost'] ?>"
                                            data-serial-number="<?= $row['serial_numbers'] ?>"
                                            data-rosd-po="<?= $row['rOSD_po'] ?>"
                                            data-rosd-forecast="<?= $row['rOSD_forecast'] ?>"
                                            data-resolved="<?= $row['resolved'] ?>"
                                            data-rosd-change-approval="<?= $row['rOSD_change_approval'] ?>"
                                            data-rosd-changed="<?= $row["rOSD_changed"] ?>"
                                            data-approved="<?= $row["approved"] ?>"
                                            data-freight-type="<?= $row['freight_type'] ?>"
                                            data-estimated-departure-date="<?= $row['estimated_departure_date'] ?>"
                                            data-actual-departure-date="<?= $row['actual_departure_date'] ?>"
                                            data-actual-delivered-date="<?= $row['actual_delivered_date'] ?>"
                                            data-notes="<?= $row['notes'] ?>">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                            data-fbn="<?= $row["fbn"] ?>" data-pdc-project-id="<?= $row['pdc_project_id'] ?>"><i
                                                class="fa-regular fa-trash-can text-danger"></i></button>
                                        <button class="btn" class="main-row" data-bs-toggle="collapse"
                                            data-bs-target="#<?= $uniqueId ?>" style="cursor: pointer;"><i
                                                class="fa-solid fa-circle-info text-warning"></i></button>
                                    </div>
                                </td>
                                <td class="p-1 align-middle text-center"><?= $row["item_number"] ?></td>
                                <td class="p-1 align-middle text-center projectNo" <?= isset($row["project_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['project_no']) ? $row['project_no'] : "N/A" ?>
                                </td>
                                <td class="p-1 align-middle text-center fbn <?php echo $poBgClass ?>"><?= $row["fbn"] ?></td>
                                <td class="p-1 align-middle text-center siteType" <?= $row['site_type'] === "" ? "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" : "" ?>>
                                    <?= $row['site_type'] !== "" ? htmlspecialchars($row['site_type']) : "N/A" ?>
                                </td>

                                <!-- <td class="p-1 align-middle text-center">
                                    <?php echo $airport_code ?>
                                </td> -->
                                <!-- <td class="p-1 align-middle text-center">
                                    <?php
                                    $country_codes = [
                                        'ABX' => 'AUS',
                                        'SYD' => 'AUS',
                                        'MEL' => 'AUS',
                                        'HYD' => 'IND',
                                        'BOM' => 'IND',
                                        'NRT' => 'JPN',
                                        'KIX' => 'JPN',
                                        'BKK' => 'THA',
                                        'SIN' => 'SGD',
                                        'CGK' => 'IDS',
                                        'DUB' => 'IRL',
                                        'AKL' => 'NZL',
                                        'ICN' => 'KOR',
                                        'USN' => 'KOR',
                                        'PUS' => 'KOR',
                                        'HKG' => 'HKG',
                                        'TPE' => 'TWN',
                                        'KUL' => 'MYS',
                                        'ZHY' => 'CHN'
                                    ];

                                    echo isset($country_codes[$airport_code]) ? $country_codes[$airport_code] : '';
                                    ?>
                                </td> -->
                                <td class="p-1 align-middle text-center status" <?= isset($row["status"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['status']) ? $row['status'] : "N/A" ?>
                                </td>
                                <!-- <td class="p-1 align-middle text-center customerColumn" <?= isset($row["customer"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['customer']) ? $row['customer'] : "N/A" ?>
                                </td> -->
                                <!-- <td class="p-1 align-middle text-center entityPoNoColumn" <?= isset($row["entity_po_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['entity_po_no']) ? $row['entity_po_no'] : "N/A" ?>
                                </td> -->
                                <!-- <td class="p-1 align-middle text-center awsPoNoColumn" <?= isset($row["aws_po_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['aws_po_no']) ? $row['aws_po_no'] : "N/A" ?>
                                </td> -->
                                <td class="p-1 align-middle text-center po">
                                    <?php if ($row['aws_po_no'] !== null) {
                                        echo "Yes";
                                    } else if ($row['aws_po_no'] === null) {
                                        echo "No";
                                    } else {
                                        echo "N/A";
                                    } ?>
                                </td>
                                <!-- <td class="p-1 align-middle text-center" <?= isset($row["purchase_order_date"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['purchase_order_date']) ? (date("j F Y", strtotime($row['purchase_order_date']))) : "N/A" ?>
                                </td> -->
                                <?php
                                // 1. Determine rOSD (Resolved) date first
                                if ($row['resolved'] === "PO" && !empty($row['rOSD_po'])) {
                                    $rosdResolvedDateRaw = $row['rOSD_po'];
                                    $rosdResolvedDate = date("j F Y", strtotime($row['rOSD_po']));
                                } elseif ($row['resolved'] === "Forecast" && !empty($row['rOSD_forecast'])) {
                                    $rosdResolvedDateRaw = $row['rOSD_forecast'];
                                    $rosdResolvedDate = date("j F Y", strtotime($row['rOSD_forecast']));
                                } else {
                                    $rosdResolvedDateRaw = null;
                                    $rosdResolvedDate = "N/A";
                                }

                                // 2. Determine rOSD (Correct) date - priority to changed date if approved
                                if ($row['approved'] == "1" && !empty($row['rOSD_changed'])) {
                                    $rosdCorrectDateRaw = $row['rOSD_changed'];
                                    $rosdCorrectDate = date("j F Y", strtotime($row['rOSD_changed']));
                                    $bgStyleRosdCorrectDate = "";
                                } else {
                                    $rosdCorrectDateRaw = $rosdResolvedDateRaw;
                                    $rosdCorrectDate = $rosdResolvedDate;
                                    $bgStyleRosdCorrectDate = ($rosdResolvedDate === "N/A") ?
                                        "background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;" : "";
                                }

                                // 3. Calculate week difference from PO date to correct date
                                $poDateRaw = !empty($row['purchase_order_date']) ? $row['purchase_order_date'] : null;
                                $endDateForCalculation = $rosdCorrectDateRaw; // Use the correct date (changed or resolved)
                        
                                if (!empty($poDateRaw) && !empty($endDateForCalculation)) {
                                    try {
                                        $start = new DateTime($poDateRaw);
                                        $end = new DateTime($endDateForCalculation);
                                        $interval = $start->diff($end);
                                        $weeksDiff = round($interval->days / 7, 1);
                                        $weeksDisplay = $weeksDiff . " weeks";
                                        $bgStyleWeeks = "";
                                    } catch (Exception $e) {
                                        $weeksDisplay = "Invalid Date";
                                        $bgStyleWeeks = "background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;";
                                    }
                                } else {
                                    $weeksDisplay = "N/A";
                                    $bgStyleWeeks = "background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;";
                                }
                                ?>
                                <!-- <td class="p-1 align-middle text-center" style="<?= $bgStyleWeeks ?>">
                                    <?= $weeksDisplay ?>
                                </td> -->

                                <td class="p-1 align-middle text-center version" <?= isset($row["version"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['version']) ? $row['version'] : "N/A" ?>
                                </td>
                                <!-- <td class="p-1 align-middle text-center"><?= $row['drawing_status'] ?></td> -->
                                <td class="p-1 align-middle text-center qty" <?= isset($row["qty"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['qty']) ? $row['qty'] : "N/A" ?>
                                </td>
                                <td class="p-1 align-middle text-center cost" <?= isset($row["cost"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['cost']) ? '$' . number_format($row['cost'], 2) : "N/A" ?>
                                </td>
                                <!-- <td class="p-1 align-middle text-center serialNumberColumn" <?= isset($row["serial_numbers"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['serial_numbers']) ? $row['serial_numbers'] : "N/A" ?>
                                </td> -->
                                <!-- <td class="p-1 align-middle text-center" <?= isset($row["rOSD_po"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_po']) ? (date("j F Y", strtotime($row['rOSD_po']))) : "N/A" ?>
                                </td> -->
                                <td class="p-1 align-middle text-center rosdForecast" <?= isset($row["rOSD_forecast"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_forecast']) ? (date("j F Y", strtotime($row['rOSD_forecast']))) : "N/A" ?>
                                </td>
                                <td class="p-1 align-middle text-center redFlag <?= ($row['conflict'] === "1") ? 'bg-danger text-white fw-bold' : '' ?>"
                                    <?= ($row['conflict'] === "0" || $row['conflict'] === null) ?
                                        "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'"
                                        : '' ?>>
                                    <?php
                                    if ($row['conflict'] === "0" || $row['conflict'] === null) {
                                        echo "N/A";
                                    } else if ($row['conflict'] === "1") {
                                        echo "Conflict";
                                    }
                                    ?>
                                </td>
                                <!-- <td class="p-1 align-middle text-center resolvedColumn" <?= isset($row["resolved"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['resolved']) ? $row['resolved'] : "N/A" ?>
                                </td> -->

                                <!-- <td class="p-1 align-middle text-center rosdResolvedColumn"
                                    style="<?= $bgStyleRosdResolvedDate ?>">
                                    <?= $rosdResolvedDate ?>
                                </td> -->

                                <!-- <td class="p-1 align-middle text-center rosdChangedColumn" <?= isset($row["rOSD_changed"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_changed']) ? (date("j F Y", strtotime($row['rOSD_changed']))) : "N/A" ?>
                                </td> -->
                                <!-- <td class="p-1 align-middle text-center approvedColumn" <?= isset($row["approved"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?php if ($row['approved'] === "0") {
                                        echo "No";
                                    } else if ($row['approved'] === "1") {
                                        echo "Yes";
                                    } else {
                                        echo "N/A";
                                    } ?>
                                </td> -->

                                <td class="p-1 align-middle text-center rosdCorrect" style="<?= $bgStyleRosdCorrectDate ?>">
                                    <?= $rosdCorrectDate ?>
                                </td>
                                <!-- <td class="p-1 align-middle text-center freightTypeColumn" <?= isset($row["freight_type"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['freight_type']) ? $row['freight_type'] : "N/A" ?>
                                </td> -->
                                <?php
                                // Determine country code from airport code
                                $country_codes = [
                                    'ABX' => 'AUS',
                                    'SYD' => 'AUS',
                                    'MEL' => 'AUS',
                                    'HYD' => 'IND',
                                    'BOM' => 'IND',
                                    'NRT' => 'JPN',
                                    'KIX' => 'JPN',
                                    'BKK' => 'THA',
                                    'SIN' => 'SGD',
                                    'CGK' => 'IDS',
                                    'DUB' => 'IRL',
                                    'AKL' => 'NZL',
                                    'ICN' => 'KOR',
                                    'USN' => 'KOR',
                                    'PUS' => 'KOR',
                                    'HKG' => 'HKG',
                                    'TPE' => 'TWN',
                                    'KUL' => 'MYS',
                                    'ZHY' => 'CHN'
                                ];

                                $airport_code = substr(trim(explode(' - ', $row['fbn'])[0]), 0, 3);
                                $country = isset($country_codes[$airport_code]) ? $country_codes[$airport_code] : null;

                                // Calculate estimated departure date based on Excel formula logic
                                if (!empty($rosdCorrectDateRaw)) {
                                    $date = date_create($rosdCorrectDateRaw);

                                    if (!empty($row['freight_type']) && strtolower($row['freight_type']) === 'air') {
                                        // If freight type is Air, subtract 7 days
                                        date_modify($date, '-7 days');
                                    } else {
                                        // Country-based weeks offset multiplied by 7 to get days
                                        if ($country === "AUS") {
                                            date_modify($date, '-2 days'); // 2 days
                                        } elseif ($country === "NZL") {
                                            date_modify($date, '-14 days'); // 2 weeks × 7
                                        } elseif (in_array($country, ["IDS", "THA", "SGP", "MYS"])) {
                                            date_modify($date, '-28 days'); // 4 weeks × 7
                                        } elseif (in_array($country, ["IND", "IRL", "KOR", "TWN", "HKG", "JPN"])) {
                                            date_modify($date, '-56 days'); // 8 weeks × 7
                                        }
                                    }

                                    $estimatedDepartureDate = date_format($date, "j F Y");
                                    $bgStyleEstimated = "";
                                } else {
                                    // If no rOSD date available, show N/A with gradient
                                    $estimatedDepartureDate = "N/A";
                                    $bgStyleEstimated = "background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;";
                                }
                                ?>

                                <td class="p-1 align-middle text-center estimatedDepartureDate"
                                    style="<?= $bgStyleEstimated ?>">
                                    <?= $estimatedDepartureDate ?>
                                </td>

                                <td class="p-1 align-middle text-center actualDepartureDate"
                                    ondblclick="editActualDepartureDate(this)"
                                    style="<?= isset($row["actual_departure_date"]) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?> cursor:pointer">

                                    <span>
                                        <?= isset($row['actual_departure_date']) ? date("j F Y", strtotime($row['actual_departure_date'])) : "N/A" ?>
                                    </span>

                                    <!-- Edit form (hidden by default) -->
                                    <form method="POST" class="edit-actual-departure-date-form d-none">
                                        <input type="hidden" name="pdcIdToEditActualDepartureDateCell"
                                            value="<?= htmlspecialchars($row['pdc_project_id']) ?>">
                                        <div class="d-flex align-items-center">
                                            <input type="date" name="actualDepartureDateCellToEdit"
                                                class="form-control form-control-sm"
                                                value="<?= $row["actual_departure_date"] ?>">

                                            <button type="submit" class="btn btn-link text-success p-0 border-0 mx-2">
                                                <i class="fa-solid fa-check"></i>
                                            </button>

                                            <a class="text-danger mx-2 text-decoration-none" style="cursor:pointer"
                                                onclick="cancelEditActualDepartureDate(this)">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                        </div>
                                    </form>
                                </td>

                                <td class="p-1 align-middle text-center actualDeliveredDate"
                                    ondblclick="editActualDeliveredDate(this)"
                                    style="<?= isset($row["actual_delivered_date"]) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?> cursor:pointer">

                                    <span>
                                        <?= isset($row['actual_delivered_date']) ? date("j F Y", strtotime($row['actual_delivered_date'])) : "N/A" ?>
                                    </span>

                                    <!-- Edit form (hidden by default) -->
                                    <form method="POST" class="edit-actual-delivered-date-form d-none">
                                        <input type="hidden" name="pdcIdToEditActualDeliveredDateCell"
                                            value="<?= htmlspecialchars($row['pdc_project_id']) ?>">
                                        <div class="d-flex align-items-center">
                                            <input type="date" name="actualDeliveredDateCellToEdit"
                                                class="form-control form-control-sm"
                                                value="<?= $row['actual_delivered_date'] ?>">

                                            <button type="submit" class="btn btn-link text-success p-0 border-0 mx-2">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <a class="text-danger mx-2 text-decoration-none" style="cursor:pointer"
                                                onclick="cancelEditActualDeliveredDate(this)">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse bg-light" id="<?= $uniqueId ?>">
                                <td colspan="100%">
                                    <div class="p-3 row">
                                        <div class="col-md-6 d-flex flex-column">
                                            <span><strong>Airport Code: </strong> <?php echo $airport_code ?> </span>
                                            <span><strong>C-Code: </strong> <?php
                                            $country_codes = [
                                                'ABX' => 'AUS',
                                                'SYD' => 'AUS',
                                                'MEL' => 'AUS',
                                                'HYD' => 'IND',
                                                'BOM' => 'IND',
                                                'NRT' => 'JPN',
                                                'KIX' => 'JPN',
                                                'BKK' => 'THA',
                                                'SIN' => 'SGD',
                                                'CGK' => 'IDS',
                                                'DUB' => 'IRL',
                                                'AKL' => 'NZL',
                                                'ICN' => 'KOR',
                                                'USN' => 'KOR',
                                                'PUS' => 'KOR',
                                                'HKG' => 'HKG',
                                                'TPE' => 'TWN',
                                                'KUL' => 'MYS',
                                                'ZHY' => 'CHN'
                                            ];

                                            echo isset($country_codes[$airport_code]) ? $country_codes[$airport_code] : '';
                                            ?></span>
                                            <span><strong>Customer:
                                                </strong><?= isset($row["customer"]) ? $row["customer"] : "N/A" ?></span>
                                            <span><strong>Entity's PO No: </strong>
                                                <?= isset($row["entity_po_no"]) ? $row["entity_po_no"] : "N/A" ?></span>
                                            <span><strong>AWS's PO No: </strong>
                                                <?= isset($row['aws_po_no']) ? $row['aws_po_no'] : "N/A" ?></span>
                                            <span><strong>PO Date: </strong>
                                                <?= isset($row['purchase_order_date']) ? (date("j F Y", strtotime($row['purchase_order_date']))) : "N/A" ?></span>
                                            <span><strong>Production from PO: </strong>
                                                <?= !empty($weeksDisplay) ? $weeksDisplay : "N/A" ?></span>
                                            <span><strong>Notes:</strong>
                                                <?= isset($row["notes"]) ? $row["notes"] : "N/A" ?>
                                            </span>
                                        </div>

                                        <div class="col-md-6 d-flex flex-column">
                                            <span><strong>Drawing Status: </strong>
                                                <?= isset($row['drawing_status']) ? $row['drawing_status'] : "N/A" ?></span>
                                            <span><strong>Serial Numbers: </strong>
                                                <?= isset($row['serial_numbers']) ? $row['serial_numbers'] : "N/A" ?></span>
                                            <span><strong>rOSD (PO): </strong>
                                                <?= isset($row['rOSD_po']) ? (date("j F Y", strtotime($row['rOSD_po']))) : "N/A" ?></span>
                                            <span><strong>Resolved: </strong>
                                                <?= isset($row['resolved']) ? $row['resolved'] : "N/A" ?></span>
                                            <span><strong>rOSD (Resolved): </strong>
                                                <?= !empty($rosdResolvedDate) ? $rosdResolvedDate : "N/A" ?></span>
                                            <span><strong>rOSD (Changed): </strong>
                                                <?= isset($row['rOSD_changed']) ? (date("j F Y", strtotime($row['rOSD_changed']))) : "N/A" ?></span>

                                            <span><strong>Approved: </strong> <?php if ($row['approved'] === "0") {
                                                echo "No";
                                            } else if ($row['approved'] === "1") {
                                                echo "Yes";
                                            } else {
                                                echo "N/A";
                                            } ?></span>
                                            <span><strong>Freight Type: </strong>
                                                <?= isset($row['freight_type']) ? $row['freight_type'] : "N/A" ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php $counter++;
                        } ?>
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
                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="75" <?php echo $records_per_page == 75 ? 'selected' : ''; ?>>75</option>
                        <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </form>

                <!-- Pagination controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- First Page Button -->
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
                                <a class="page-link" onclick="updatePage(<?php echo $i ?>); return false;">
                                    <?php echo $i; ?> </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $page + 1; ?>); return false;"
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
                                <a class="page-link" onclick="updatePage(<?php echo $total_pages; ?>); return false;"
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
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/AddPDCProjectForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Add Document Modal (CSV) ================== -->
    <div class="modal fade" id="addProjectCSV" tabindex="-1" aria-labelledby="addProjectCSV" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Project (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <label class="form-label fw-bold">PDC Forecast (CSV)</label>
                        <input type="file" class="form-control" name="csvFile" accept=".csv" required>
                        <label class="form-label fw-bold mt-4">Import Date</label>
                        <input type="date" class="form-control" name="importDate" value="<?php echo date('Y-m-d') ?>"
                            required>

                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn-dark" type="submit" name="import_csv">Add Project</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Edit Document Modal ================== -->
    <div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModal" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit PDC Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require("../Form/EditPDCProjectForm.php") ?>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete project with <span class="fw-bold" id="fbnToDelete"></span> FBN?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST">
                        <input type="hidden" name="fbnIdToDelete" id="pdcProjectIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Filter Column Modal ================== -->
    <div class="modal fade" id="filterColumnModal" tabindex="-1" aria-labelledby="filterColumnModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterColumnModalLabel">Filter Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="projectNoCheckbox" data-column="projectNo">
                            <label class="form-check-label" for="projectNoCheckbox">
                                Project No
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value="" id="fbnCheckbox"
                                data-column="fbn">
                            <label class="form-check-label" for="fbnCheckbox">
                                FBN
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="siteTypeCheckbox" data-column="siteType">
                            <label class="form-check-label" for="siteTypeCheckbox">
                                Site Type
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="statusCheckbox" data-column="status">
                            <label class="form-check-label" for="statusCheckbox">
                                Status
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value="" id="poCheckbox"
                                data-column="po">
                            <label class="form-check-label" for="poCheckbox">
                                PO
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="versionCheckbox" data-column="version">
                            <label class="form-check-label" for="versionCheckbox">
                                Version
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="quantityCheckbox" data-column="qty">
                            <label class="form-check-label" for="quantityCheckbox">
                                Qty
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="costCheckbox" data-column="cost">
                            <label class="form-check-label" for="costCheckbox">
                                Cost
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="rosdForecastCheckbox" data-column="rosdForecast">
                            <label class="form-check-label" for="rosdForecastCheckbox">
                                rOSD (Forecast)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="redFlagCheckbox" data-column="redFlag">
                            <label class="form-check-label" for="redFlagCheckbox">
                                Red Flag
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="rosdCorrectCheckbox" data-column="rosdCorrect">
                            <label class="form-check-label" for="rosdCorrectCheckbox">
                                rOSD (Correct)
                            </label>
                        </div>
                        <hr>
                        <div class="form-check">
                            <input class="form-check-input column-check-input delivery-date-checkbox" type="checkbox"
                                value="" id="estimatedDepartureDateCheckbox" data-column="estimatedDepartureDate">
                            <label class="form-check-label" for="estimatedDepartureDateCheckbox">
                                Estimated Departure Date
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input delivery-date-checkbox" type="checkbox"
                                value="" id="actualDepartureDateCheckbox" data-column="actualDepartureDate">
                            <label class="form-check-label" for="actualDepartureDateCheckbox">
                                Actual Departure Date
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input delivery-date-checkbox" type="checkbox"
                                value="" id="actualDeliveredDateCheckbox" data-column="actualDeliveredDate">
                            <label class="form-check-label" for="actualDeliveredDateCheckbox">
                                Actual Delivered Date
                            </label>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3" style="cursor: pointer;">
                        <button onclick="hideDeliveryDates()" class="btn btn-sm btn-secondary me-1">Hide Delivery
                            Dates</button>
                        <button onclick="resetColumnFilter()" class="btn btn-sm btn-danger me-1">Reset Filter</button>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== F I L T E R  M O D A L ========================== -->
    <div class="modal fade" id="filterProjectModal" tabindex="1" aria-labelledby="filterProjectModal"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter PDC Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" id="filterForm">
                        <?php
                        // Preserve current search param
                        if (!empty($searchTerm)) {
                            echo '<input type="hidden" name="search" value="' . htmlspecialchars($searchTerm) . '">';
                        }
                        // Also preserve other params like sort/order/page if needed
                        if (!empty($sort)) {
                            echo '<input type="hidden" name="sort" value="' . htmlspecialchars($sort) . '">';
                        }
                        if (!empty($order)) {
                            echo '<input type="hidden" name="order" value="' . htmlspecialchars($order) . '">';
                        }
                        if (!empty($page)) {
                            echo '<input type="hidden" name="page" value="' . htmlspecialchars($page) . '">';
                        }
                        ?>
                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <h5 class="signature-color fw-bold">Status</h5>
                                <p class="mb-0 pb-0">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                    <label for="selectAll">Select All</label>
                                </p>
                                <?php
                                $statusFilters = ['AWS Removed FF', 'Cancelled', 'Sheet Metal', 'Sheet Metal Assembly', 'Electrical', 'Testing', 'Crated', 'In Transit', 'Delivered/Invoiced', 'N/A'];
                                $selected_status = isset($_GET['status']) ? (array) $_GET['status'] : [];

                                foreach ($statusFilters as $statusFilter) {
                                    $id = 'status_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $statusFilter));
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input statusCheckbox"
                                            id="<?php echo $id; ?>" name="status[]" value="<?php echo $statusFilter; ?>"
                                            <?php echo in_array($statusFilter, $selected_status) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $id; ?>"><?php echo $statusFilter; ?></label>
                                    </p>
                                <?php } ?>
                            </div>

                            <div class="col-12 col-lg-4 mt-4 mt-lg-0">
                                <h5 class="signature-color fw-bold">Version</h5>
                                <?php
                                $versionFilters = ['V1.0 630A TF Single Door (IEC)', 'V1.0 630A TF Double Door (IEC)', 'V1.0 630A TF Double Door (AUS)', 'V2.0 630A TF Double Door (IEC)', 'V2.0 250A TF Double Door (IEC)', 'V2.0 250A BF Double Door (IEC)', 'V3.0. 1000A TF (IEC)', 'N/A'];
                                $selected_version = isset($_GET['version']) ? (array) $_GET['version'] : [];

                                foreach ($versionFilters as $versionFilter) {
                                    $id = 'version_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $versionFilter));
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input versionCheckbox"
                                            id="<?php echo $id; ?>" name="version[]" value="<?php echo $versionFilter; ?>"
                                            <?php echo in_array($versionFilter, $selected_version) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $id; ?>"><?php echo $versionFilter; ?></label>
                                    </p>
                                <?php } ?>

                                <?php
                                // 1. Fetch airport codes
                                $airport_sql = "SELECT DISTINCT LEFT(fbn, 3) AS airport_code 
            FROM pdc_projects 
            WHERE fbn IS NOT NULL AND fbn <> '' 
            ORDER BY airport_code ASC";

                                $airport_result = $conn->query($airport_sql);

                                $airport_codes = [];
                                while ($row = $airport_result->fetch_assoc()) {
                                    $airport_codes[] = $row['airport_code'];
                                }

                                // 2. Get selected airport filter from GET
                                $selected_airport = isset($_GET['airport']) ? (array) $_GET['airport'] : [];

                                // 3. Split airport codes into two columns
                                $airport_count = count($airport_codes);
                                $mid_point = ceil($airport_count / 2);
                                $airport_column1 = array_slice($airport_codes, 0, $mid_point);
                                $airport_column2 = array_slice($airport_codes, $mid_point);
                                ?>

                                <h5 class="signature-color fw-bold mt-4">Airport Code</h5>

                                <div class="row">
                                    <div class="col-6">
                                        <?php foreach ($airport_column1 as $airport):
                                            $id = 'airport_' . strtolower($airport);
                                            ?>
                                            <p class="mb-0 pb-0">
                                                <input type="checkbox" class="form-check-input airportCheckbox"
                                                    id="<?php echo $id; ?>" name="airport[]" value="<?php echo $airport; ?>"
                                                    <?php echo in_array($airport, $selected_airport) ? 'checked' : ''; ?>>
                                                <label for="<?php echo $id; ?>">
                                                    <?php echo $airport; ?>
                                                </label>
                                            </p>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="col-6">
                                        <?php foreach ($airport_column2 as $airport):
                                            $id = 'airport_' . strtolower($airport);
                                            ?>
                                            <p class="mb-0 pb-0">
                                                <input type="checkbox" class="form-check-input airportCheckbox"
                                                    id="<?php echo $id; ?>" name="airport[]" value="<?php echo $airport; ?>"
                                                    <?php echo in_array($airport, $selected_airport) ? 'checked' : ''; ?>>
                                                <label for="<?php echo $id; ?>">
                                                    <?php echo $airport; ?>
                                                </label>
                                            </p>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-4 mt-4 mt-lg-0">
                                <h5 class=" signature-color fw-bold">rOSD (Correct) Timeframe</h5>
                                <div class="d-flex align-items-center mb-2 col-lg-5">
                                    <input type="month" class="form-control me-2" name="rosd_correct_start"
                                        value="<?php echo htmlspecialchars($rosd_correct_start) ?>">
                                    <span class="mx-2"> to </span>
                                    <input type="month" class="form-control ms-2" name="rosd_correct_end"
                                        value="<?php echo htmlspecialchars($rosd_correct_end) ?>">
                                </div>

                                <h5 class="signature-color fw-bold mt-4">Estimated Departure Date Timeframe</h5>
                                <div class="d-flex align-items-center col-lg-5">
                                    <input type="month" class="form-control me-2" name="estimated_departure_start"
                                        value="<?php echo htmlspecialchars($estimated_departure_start) ?>">
                                    <span class="mx-2"> to </span>
                                    <input type="month" class="form-control ms-2" name="estimated_departure_end"
                                        value="<?php echo htmlspecialchars($estimated_departure_end) ?>">
                                </div>

                                <h5 class="signature-color fw-bold mt-4">Purchase Order</h5>
                                <div class="d-flex">
                                    <div class="btn-group" role="group" aria-label="Purchase Order Filter">
                                        <input type="radio" class="btn-check" name="purchaseOrderFilter"
                                            id="purchaseOrderFilterYes" value="Yes" autocomplete="off" <?php if (isset($_GET['purchaseOrderFilter']) && $_GET['purchaseOrderFilter'] === "Yes")
                                                echo "checked"; ?>>
                                        <label class="btn btn-outline-dark" for="purchaseOrderFilterYes">Yes</label>

                                        <input type="radio" class="btn-check" name="purchaseOrderFilter"
                                            id="purchaseOrderFilterNo" value="No" autocomplete="off" <?php if (isset($_GET['purchaseOrderFilter']) && $_GET['purchaseOrderFilter'] === "No")
                                                echo "checked"; ?>>
                                        <label class="btn btn-outline-dark" for="purchaseOrderFilterNo">No</label>

                                        <input type="radio" class="btn-check" name="purchaseOrderFilter"
                                            id="purchaseOrderFilterAny" value="Any" autocomplete="off" <?php if (!isset($_GET['purchaseOrderFilter']) || $_GET['purchaseOrderFilter'] === "Any")
                                                echo "checked"; ?>>
                                        <label class="btn btn-outline-dark" for="purchaseOrderFilterAny">Any</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                <button id="clearFiltersBtn" class="btn btn-danger me-1" type="button">Clear</button>
                                <button class="btn btn-secondary me-1" type="button"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-dark" type="submit" name="apply_filters">Apply
                                    Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== R E P O R T M O D A L ========================== -->
    <div class="modal fade" id="projectReportModal" tabindex="-1" aria-labelledby="projectReportModal"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">PDC Project Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="true"></button>
                </div>
                <div class="modal-body">
                    <?php require("../PageContent/ModalContent/pdc-project-report.php") ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src=" https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

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
                var button = event.relatedTarget;
                var fbn = button.getAttribute('data-fbn');
                var pdcProjectId = button.getAttribute('data-pdc-project-id');

                var modalFbn = myModalEl.querySelector('#fbnToDelete');
                var modalPdcProjectId = myModalEl.querySelector('#pdcProjectIdToDelete');

                modalFbn.textContent = fbn;
                modalPdcProjectId.value = pdcProjectId;
            })
        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editDocumentModal');

            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;

                // Extract data from the button attributes
                var pdcProjectId = button.getAttribute('data-pdc-project-id');
                var projectNo = button.getAttribute('data-project-no');
                var fbn = button.getAttribute('data-fbn');
                var siteType = button.getAttribute('data-site-type');
                var status = button.getAttribute('data-status');
                var customer = button.getAttribute('data-customer');
                var entityPoNo = button.getAttribute('data-entity-po-no');
                var awsPoNo = button.getAttribute('data-aws-po-no');
                var poDate = button.getAttribute('data-po-date');
                var version = button.getAttribute('data-version');
                var drawingStatus = button.getAttribute('data-drawing-status');
                var quantity = button.getAttribute('data-quantity');
                var cost = button.getAttribute('data-cost');
                var serialNumber = button.getAttribute('data-serial-number');
                var rosdPO = button.getAttribute('data-rosd-po');
                var rosdForecast = button.getAttribute('data-rosd-forecast');
                var resolved = button.getAttribute('data-resolved');
                var rosdChangeApproval = button.getAttribute('data-rosd-change-approval');
                var rosdChanged = button.getAttribute('data-rosd-changed');
                var approved = button.getAttribute('data-approved');
                var rosdCorrect = button.getAttribute('data-rosd-correct');
                var freightType = button.getAttribute('data-freight-type');
                var actualDepartureDate = button.getAttribute('data-actual-departure-date');
                var actualDeliveredDate = button.getAttribute('data-actual-delivered-date');
                var notes = button.getAttribute('data-notes');

                // Update the modal's content with the extracted data
                var modalPdcProjectId = myModalEl.querySelector('#pdcProjectIdToEdit');
                var modalProjectNo = myModalEl.querySelector('#projectNoToEdit');
                var modalFbn = myModalEl.querySelector('#fbnToEdit');
                var modalSiteType = myModalEl.querySelector('#siteTypeToEdit');
                var modalStatus = myModalEl.querySelector('#statusToEdit');
                var modalCustomer = myModalEl.querySelector('#customerToEdit');
                var modalEntityPoNo = myModalEl.querySelector('#entityPoNoToEdit');
                var modalAwsPoNo = myModalEl.querySelector('#awsPoNoToEdit');
                var modalPoDate = myModalEl.querySelector('#poDateToEdit');
                var modalVersion = myModalEl.querySelector('#versionToEdit');
                var modalDrawingStatus = myModalEl.querySelector('#drawingStatusToEdit');
                var modalQuantity = myModalEl.querySelector('#quantityToEdit');
                var modalCost = myModalEl.querySelector('#costToEdit');
                var modalSerialNumber = myModalEl.querySelector('#serialNumberToEdit');
                var modalRosdPo = myModalEl.querySelector('#rosdPOToEdit');
                var modalRosdForecast = myModalEl.querySelector('#rosdForecastToEdit');
                var modalResolved = myModalEl.querySelector('#resolvedToEdit');
                var modalRosdResolved = myModalEl.querySelector('#rosdResolvedToEdit');
                var modalRosdChanged = myModalEl.querySelector('#rosdChangedToEdit');
                var modalApproved = myModalEl.querySelector('#rosdCorrectConfirmationToEdit');
                var modalRosdCorrect = myModalEl.querySelector('#rosdCorrectToEdit');
                var modalFreightType = myModalEl.querySelector('#freightTypeToEdit');
                var modalActualDepartureDate = myModalEl.querySelector('#actualDepartureDateToEdit');
                var modalActualDeliveredDate = myModalEl.querySelector('#actualDeliveredDateToEdit');
                var modalNotes = myModalEl.querySelector('#notesToEdit');

                if (resolved === "PO") {
                    modalRosdResolved.value = rosdPO;
                } else if (resolved === "Forecast") {
                    modalRosdResolved.value = rosdForecast;
                }

                if (rosdChangeApproval === "1") {
                    document.getElementById("rosdChangedYesToEdit").checked = true;
                } else if (rosdChangeApproval === "0") {
                    document.getElementById('rosdChangedNoToEdit').checked = true;
                }

                if (approved === "1") {
                    document.getElementById("rosdCorrectYesToEdit").checked = true;
                } else if (approved === "0") {
                    document.getElementById('rosdCorrectNoToEdit').checked = true;
                }

                // Assign the extracted value to the modal input fields
                modalPdcProjectId.value = pdcProjectId;
                modalProjectNo.value = projectNo;
                modalFbn.value = fbn;
                modalSiteType.value = siteType;
                modalStatus.value = status;
                modalCustomer.value = customer;
                modalEntityPoNo.value = entityPoNo;
                modalAwsPoNo.value = awsPoNo;
                modalPoDate.value = poDate;
                modalVersion.value = version;
                modalDrawingStatus.value = drawingStatus;
                modalQuantity.value = quantity;
                modalCost.value = cost;
                modalSerialNumber.value = serialNumber;
                modalRosdPo.value = rosdPO;
                modalRosdForecast.value = rosdForecast;
                modalResolved.value = resolved;
                modalRosdChanged.value = rosdChanged;
                modalRosdCorrect.value = rosdCorrect;
                modalFreightType.value = freightType;
                modalActualDepartureDate.value = actualDepartureDate;
                modalActualDeliveredDate.value = actualDeliveredDate;
                modalNotes.value = notes;
            })
        })
    </script>

    <script>
        const STORAGE_EXPIRATION_TIME = 8 * 60 * 60 * 1000; // 8 hours in milliseconds

        // Save checkbox state to localStorage with a timestamp
        document.querySelectorAll('.column-check-input').forEach(checkbox => {
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
            });
        });

        // Initialize checkboxes based on current column visibility
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.column-check-input').forEach(checkbox => {
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

        function hideDeliveryDates() {
            document.querySelectorAll('.delivery-date-checkbox').forEach(checkbox => {
                checkbox.checked = !checkbox.checked;

                // Trigger the change event manually
                checkbox.dispatchEvent(new Event('change'));
            });
        }

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
        function updateSort(sortKey, currentOrder) {
            const url = new URL(window.location.href);

            // Set the sort parameters
            url.searchParams.set('sort', sortKey);
            url.searchParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');

            // Reset to first page when sorting
            url.searchParams.set('page', '1');

            window.location.href = url.toString();
        }
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
        document.getElementById('clearFiltersBtn').addEventListener('click', function () {
            const form = document.getElementById('filterForm');

            // Clear all checkboxes
            form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Clear all input fields (month type)
            form.querySelectorAll('input[type="month"]').forEach(input => {
                input.value = '';
            });

            // Reset Purchase Order filter to "Any"
            const anyRadio = document.getElementById('purchaseOrderFilterAny');
            if (anyRadio) {
                anyRadio.checked = true;
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAllCheckbox = document.getElementById('selectAll');
            const statusCheckboxes = document.querySelectorAll('.statusCheckbox');

            // Function to update Select All checkbox state
            function updateSelectAllState() {
                const allChecked = Array.from(statusCheckboxes).every(checkbox => checkbox.checked);
                selectAllCheckbox.checked = allChecked;
            }

            // Select All checkbox event
            selectAllCheckbox.addEventListener('change', function () {
                statusCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });

            // Individual status checkbox events
            statusCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllState);
            });

            // Initialize Select All state on page load
            updateSelectAllState();
        });
    </script>

    <script>
        document.getElementById('filterForm').addEventListener('submit', function (e) {
            const rosdStart = this.querySelector('input[name="rosd_correct_start"]').value;
            const rosdEnd = this.querySelector('input[name="rosd_correct_end"]').value;
            const depStart = this.querySelector('input[name="estimated_departure_start"]').value;
            const depEnd = this.querySelector('input[name="estimated_departure_end"]').value;

            // Helper function to compare YYYY-MM format
            function isStartAfterEnd(start, end) {
                return start && end && start > end;
            }

            if (isStartAfterEnd(rosdStart, rosdEnd)) {
                alert("rOSD Correct Timeframe start month cannot be after end month.");
                e.preventDefault(); // stop form submission
                return;
            }

            if (isStartAfterEnd(depStart, depEnd)) {
                alert("Estimated Departure Date Timeframe start month cannot be after end month.");
                e.preventDefault(); // stop form submission
                return;
            }
        });
    </script>

    <script>
        function editActualDepartureDate(cell) {
            const form = cell.querySelector('.edit-actual-departure-date-form');
            const span = cell.querySelector('span');

            if (form && span) {
                // Always show form and hide span on double-click
                form.classList.remove('d-none');
                span.classList.add('d-none');
                form.querySelector('input[type="date"]').focus(); // Fixed: it's input, not select
            }
        }

        function cancelEditActualDepartureDate(link) {
            const form = link.closest('.edit-actual-departure-date-form');
            const cell = form.closest('td');
            const span = cell.querySelector('span');

            if (form && span) {
                // Always hide form and show span on cancel
                form.classList.add('d-none');
                span.classList.remove('d-none');
            }
        }

        function editActualDeliveredDate(cell) {
            const form = cell.querySelector('.edit-actual-delivered-date-form');
            const span = cell.querySelector('span');

            if (form && span) {
                // Always show form and hide span on double-click
                form.classList.remove('d-none');
                span.classList.add('d-none');
                form.querySelector('input[type="date"]').focus(); // Fixed: it's input, not select
            }
        }

        function cancelEditActualDeliveredDate(link) {
            const form = link.closest('.edit-actual-delivered-date-form');
            const cell = form.closest('td');
            const span = cell.querySelector('span');

            if (form && span) {
                // Always hide form and show span on cancel
                form.classList.add('d-none');
                span.classList.remove('d-none');
            }
        }
    </script>

    <script>
        function exportToExcel() {
            // Get all current URL parameters
            const urlParams = new URLSearchParams(window.location.search);

            // Build the export URL with all current filters
            let exportUrl = '../AJAXphp/export_pdc_to_excel.php?' + urlParams.toString();

            // Redirect to export script with all current filters
            window.location.href = exportUrl;
        }
    </script>
</body>