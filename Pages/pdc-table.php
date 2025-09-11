<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

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
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'pdc_project_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

if ($sort === 'PO') {
    $sort = "(CASE WHEN aws_po_no IS NOT NULL THEN 1 ELSE 0 END)";
} else if ($sort === 'rosdCorrect') {
    $sort = "
    CASE
        WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN rOSD_changed
        WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN rOSD_po
        WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN rOSD_forecast
        ELSE NULL
    END
";
} else if ($sort === 'estimatedDepartureDate') {
    $sort = "
        CASE
            WHEN rOSD_changed IS NOT NULL AND approved = 1 THEN
                CASE
                    WHEN freight_type = 'Air' THEN DATE_SUB(rOSD_changed, INTERVAL 7 DAY)
                    WHEN LEFT(fbn,3) IN ('SYD','MEL','AKL') THEN DATE_SUB(rOSD_changed, INTERVAL 14 DAY)
                    WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN DATE_SUB(rOSD_changed, INTERVAL 28 DAY)
                    WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN DATE_SUB(rOSD_changed, INTERVAL 56 DAY)
                    ELSE rOSD_changed
                END
            WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN
                CASE
                    WHEN freight_type = 'Air' THEN DATE_SUB(rOSD_po, INTERVAL 7 DAY)
                    WHEN LEFT(fbn,3) IN ('SYD','MEL','AKL') THEN DATE_SUB(rOSD_po, INTERVAL 14 DAY)
                    WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN DATE_SUB(rOSD_po, INTERVAL 28 DAY)
                    WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN DATE_SUB(rOSD_po, INTERVAL 56 DAY)
                    ELSE rOSD_po
                END
            WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN
                CASE
                    WHEN freight_type = 'Air' THEN DATE_SUB(rOSD_forecast, INTERVAL 7 DAY)
                    WHEN LEFT(fbn,3) IN ('SYD','MEL','AKL') THEN DATE_SUB(rOSD_forecast, INTERVAL 14 DAY)
                    WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN DATE_SUB(rOSD_forecast, INTERVAL 28 DAY)
                    WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN DATE_SUB(rOSD_forecast, INTERVAL 56 DAY)
                    ELSE rOSD_forecast
                END
            ELSE NULL
        END
    ";
}



// Pagination 
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 30; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$whereClause = "(fbn LIKE '%$searchTerm%' OR project_no LIKE '%$searchTerm%' OR serial_numbers LIKE '%$searchTerm%')";

// Arrays to hold selected filter values
$selected_status = [];

$filterApplied = false;

if (isset($_GET['apply_filters'])) {
    if(isset($_GET['status']) && is_array($_GET['status'])) {
        $selected_status = $_GET['status'];
        $status_placeholders = "'" . implode("','", $selected_status) . "'";
        $whereClause .= " AND `status` IN ($status_placeholders)";
        $filterApplied = true;
    }
}

$project_sql = "SELECT * FROM pdc_projects WHERE $whereClause 
ORDER BY $sort $order 
LIMIT $offset, $records_per_page";
$project_result = $conn->query($project_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM pdc_projects WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM pdc_projects WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

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
    require_once '../db_connect.php'; // Your DB connection

    $fileTmpPath = $_FILES['csvFile']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['csvFile']['name'], PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        echo "<script>alert('Invalid file type. Please upload a .csv file.');</script>";
        return;
    }

    // Get manual import date from form
    $importDate = $_POST['importDate'];

    function convertDate($value)
    {
        $dateObj = DateTime::createFromFormat('d/m/Y', $value);
        return $dateObj ? $dateObj->format('Y-m-d') : null;
    }

    if (($handle = fopen($fileTmpPath, 'r')) !== false) {
        fgetcsv($handle); // Skip header row

        $stmt = $conn->prepare("
            INSERT INTO pdc_projects (fbn, site_type, rOSD_forecast, qty, import_date)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                site_type = VALUES(site_type),
                rOSD_forecast = VALUES(rOSD_forecast),
                qty = VALUES(qty),
                import_date = VALUES(import_date)
        ");

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 4)
                continue;

            // Clean up
            $row = array_map(fn($item) => mb_convert_encoding(trim($item), 'UTF-8', 'auto'), $row);

            [$fbn, $siteType, $rosdForecast, $qty] = $row;

            $rosdForecast = convertDate($rosdForecast); // Convert "d/m/y" to "Y-m-d"

            $stmt->bind_param("sssis", $fbn, $siteType, $rosdForecast, $qty, $importDate);
            $stmt->execute();
        }

        fclose($handle);
        echo "<script>alert('CSV Imported Successfully');</script>";

        // Refresh
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
                                if ($key === 'search') continue; // skip search because it has its own input
                                // If the param is an array (e.g. filters with multiple values), add one hidden input per value
                                if (is_array($value)) {
                                    foreach ($value as $v) {
                                        echo '<input type="hidden" name="'.htmlspecialchars($key).'[]" value="'.htmlspecialchars($v).'">';
                                    }
                                } else {
                                    echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">';
                                }
                            }
                        ?>
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
                        <?php if ($role === "full control") { ?>
                            <!-- Admin Buttons -->
                            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#projectReportModal">
                                <i class="fa-solid fa-square-poll-vertical"></i> Report
                            </button>
                            <a class="btn btn-primary me-2" type="button" data-bs-toggle="modal"
                                data-bs-target="#projectDashboardModal">
                                <i class="fa-solid fa-chart-pie"></i> Dashboard
                            </a>
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
                    </div>
                </div>
            </div>
        </div>

        <?php foreach ($urlParams as $key => $value): ?>
            <?php if (!empty($value)): // Only show the span if the value is not empty ?>
                <?php 
                 if ($key === 'status' && is_array($value)) {
                        foreach ($value as $status) {
                            ?>
                                <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                    <strong><span class="text-warning">Status:
                                    </span><?php echo htmlspecialchars($status); ?></strong>
                                    <a href="?<?php
                                    // Remove this specific status filter from the URL
                                    $filteredParams = $_GET;
                                    $filteredParams['status'] = array_diff($filteredParams['status'], [$status]);
                                    echo http_build_query($filteredParams);
                                    ?>" class="text-white ms-1">
                                        <i class="fa-solid fa-times"></i>
                                    </a>
                                </span>
                            <?php
                        }
                    } ?>
            <?php endif ?>
        <?php endforeach; ?>

        <!-- Display message if filters are applied, and show total count or no results message -->
        <?php if ($filterApplied): ?>
            <div class="alert <?php echo ($total_records == 0) ? 'alert-danger' : 'alert-info'; ?>">
                <?php if ($total_records > 0): ?>
                    <strong>Total Results:</strong> 
                    <span class="fw-bold text-decoration-underline me-2"> <?php echo $total_records ?></span>
                <?php else: ?>
                    <strong>No results found for the selected filters.</strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="container-fluid mb-3 mx-0 px-0">
            <span class="d-block py-2 bg-danger text-white rounded-3 fw-bold text-center">
                Last Updated: <?= $lastUpdate ?>
            </span>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="py-4 align-middle text-center" style="max-width: 100px;">Item Numbers</th>
                        <th class="py-4 align-middle text-center projectNo" style="cursor: pointer"><a onclick="updateSort('project_no', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')">Project No <i class="fa-solid fa-sort fa-md ms-1"></i> </a></th>
                        <th class="py-4 align-middle text-center fbn" style="cursor: pointer"><a onclick="updateSort('fbn', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')">FBN <i class="fa-solid fa-sort fa-md ms-1"></i> </a></th>
                        <th class="py-4 align-middle text-center siteType"><a onclick="updateSort('site_type', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')">Site Type <i class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <!-- <th class="py-4 align-middle text-center">FBN</th> -->
                        <!-- <th class="py-4 align-middle text-center">C-Code</th> -->
                        <th class="py-4 align-middle text-center status" style="cursor: pointer;"> <a onclick="updateSort('status', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> Status <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <!-- <th class="py-4 align-middle text-center" style="min-width:150px">Customer</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:200px">Entity's PO No.</th> -->
                        <!-- <th class="py-4 align-middle text-center">AWS's PO No.</th> -->
                        <th class="py-4 align-middle text-center po" style="cursor: pointer;"> <a onclick="updateSort('PO', '<?= $order == 'asc' ? 'desc'  : 'asc' ?>')"> PO <i class="fa-solid fa-sort fa-ms ms-1"></i></a></th>
                        <!-- <th class="py-4 align-middle text-center" style="min-width:150px">PO Date</th> -->
                        <!-- <th class="py-4 align-middle text-center">Production from PO</th> -->
                        <th class="py-4 align-middle text-center version" style="min-width:280px; cursor: pointer;"> <a onclick="updateSort('Version', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> Version<i class="fa-solid fa-sort fa-ms ms-1"></i></a></th>
                        <!-- <th class="py-4 align-middle text-center" style="min-width:280px;">Drawing Status</th> -->
                        <th class="py-4 align-middle text-center qty" style="cursor:pointer"> <a onclick="updateSort('qty', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> Qty <i class="fa-solid fa-sort fa-ms ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center cost" style="cursor: pointer"> <a onclick="updateSort('cost', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> Cost <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <!-- <th class="py-4 align-middle text-center">Serial Numbers</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (PO)</th> -->
                        <th class="py-4 align-middle text-center rosdForecast" style="min-width:180px; cursor: pointer"> <a onclick="updateSort('rosd_forecast', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> rOSD (Forecast) <i class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center redFlag" style="cursor: pointer"> <a onclick="updateSort('conflict', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> Red Flag <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <!-- <th class="py-4 align-middle text-center">Resolved</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (Resolved)</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (Changed)</th> -->
                        <!-- <th class="py-4 align-middle text-center">Approved</th> -->
                        <th class="py-4 align-middle text-center rosdCorrect" style="min-width:180px; cursor: pointer;"> <a onclick="updateSort('rosdCorrect', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> rOSD (Correct) <i class="fa-solid fa-sort fa-ms ms-1"></i> </a>
                        </th>
                        <!-- <th class="py-4 align-middle text-center">Freight Type</th> -->
                        <th class="py-4 align-middle text-center estimatedDepartureDate" style="min-width:180px; cursor: pointer;"> <a onclick="updateSort('estimatedDepartureDate', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> 
                            Estimated Departure Date <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <th class="py-4 align-middle text-center actualDepartureDate" style="min-width:180px; cursor:pointer;"><a onclick="updateSort('actual_departure_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> Actual
                            Departure Date <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                        <th class="py-4 align-middle text-center actualDeliveredDate" style="min-width:180px; cursor: pointerl"> <a onclick="updateSort('actual_delivered_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"> Actual
                            Delivered Date <i class="fa-solid fa-sort fa-ms ms-1"></i> </a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($projects)) { ?>
                        <?php
                        $item_number = ($page - 1) * $records_per_page + 1;
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
                                    $poBgClass = 'bg-success bg-opacity-50';
                                } elseif (!empty($row['aws_po_no']) && empty($row['entity_po_no'])) {
                                    $poBgClass = 'bg-success bg-opacity-25';
                                }
                                ?>
                                <td class="py-3 align-middle text-center">
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
                                            data-actual-delivered-date="<?= $row['actual_delivered_date'] ?>">
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
                                <td class="py-3 align-middle text-center"><?= $item_number++ ?></td>
                                <td class="py-3 align-middle text-center projectNo" <?= isset($row["project_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['project_no']) ? $row['project_no'] : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center fbn <?php echo $poBgClass ?>"><?= $row["fbn"] ?></td>
                                <td class="py-3 align-middle text-center siteType"><?= $row["site_type"] ?></td>
                                <!-- <td class="py-3 align-middle text-center">
                                    <?php echo $airport_code ?>
                                </td> -->
                                <!-- <td class="py-3 align-middle text-center">
                                    <?php
                                    $country_codes = [
                                        'SYD' => 'AUS',
                                        'MEL' => 'AUS',
                                        'HYD' => 'IND',
                                        'BOM' => 'IND',
                                        'NRT' => 'JPN',
                                        'BKK' => 'THA',
                                        'SIN' => 'SGP',
                                        'CGK' => 'IDS',
                                        'DUB' => 'IRL',
                                        'AKL' => 'NZL',
                                        'ICN' => 'KOR',
                                        'HKG' => 'HGK',
                                        'TPE' => 'TWN',
                                        'KUL' => 'MYS'
                                    ];

                                    echo isset($country_codes[$airport_code]) ? $country_codes[$airport_code] : '';
                                    ?>
                                </td> -->
                                <td class="py-3 align-middle text-center status" <?= isset($row["status"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['status']) ? $row['status'] : "N/A" ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center customerColumn" <?= isset($row["customer"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['customer']) ? $row['customer'] : "N/A" ?>
                                </td> -->
                                <!-- <td class="py-3 align-middle text-center entityPoNoColumn" <?= isset($row["entity_po_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['entity_po_no']) ? $row['entity_po_no'] : "N/A" ?>
                                </td> -->
                                <!-- <td class="py-3 align-middle text-center awsPoNoColumn" <?= isset($row["aws_po_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['aws_po_no']) ? $row['aws_po_no'] : "N/A" ?>
                                </td> -->
                                <td class="py-3 align-middle text-center po">
                                    <?php if ($row['aws_po_no'] !== null) {
                                        echo "Yes";
                                    } else if ($row['aws_po_no'] === null) {
                                        echo "No";
                                    } else {
                                        echo "N/A";
                                    } ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center" <?= isset($row["purchase_order_date"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
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
                                <!-- <td class="py-3 align-middle text-center" style="<?= $bgStyleWeeks ?>">
                                    <?= $weeksDisplay ?>
                                </td> -->

                                <td class="py-3 align-middle text-center version" <?= isset($row["version"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['version']) ? $row['version'] : "N/A" ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center"><?= $row['drawing_status'] ?></td> -->
                                <td class="py-3 align-middle text-center qty" <?= isset($row["qty"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['qty']) ? $row['qty'] : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center cost" <?= isset($row["cost"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['cost']) ? '$' . number_format($row['cost'], 2) : "N/A" ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center serialNumberColumn" <?= isset($row["serial_numbers"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['serial_numbers']) ? $row['serial_numbers'] : "N/A" ?>
                                </td> -->
                                <!-- <td class="py-3 align-middle text-center" <?= isset($row["rOSD_po"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_po']) ? (date("j F Y", strtotime($row['rOSD_po']))) : "N/A" ?>
                                </td> -->
                                <td class="py-3 align-middle text-center rosdForecast" <?= isset($row["rOSD_forecast"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_forecast']) ? (date("j F Y", strtotime($row['rOSD_forecast']))) : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center redFlag <?= ($row['conflict'] === "1") ? 'bg-danger text-white fw-bold' : '' ?>"
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
                                <!-- <td class="py-3 align-middle text-center resolvedColumn" <?= isset($row["resolved"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['resolved']) ? $row['resolved'] : "N/A" ?>
                                </td> -->

                                <!-- <td class="py-3 align-middle text-center rosdResolvedColumn"
                                    style="<?= $bgStyleRosdResolvedDate ?>">
                                    <?= $rosdResolvedDate ?>
                                </td> -->

                                <!-- <td class="py-3 align-middle text-center rosdChangedColumn" <?= isset($row["rOSD_changed"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_changed']) ? (date("j F Y", strtotime($row['rOSD_changed']))) : "N/A" ?>
                                </td> -->
                                <!-- <td class="py-3 align-middle text-center approvedColumn" <?= isset($row["approved"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?php if ($row['approved'] === "0") {
                                        echo "No";
                                    } else if ($row['approved'] === "1") {
                                        echo "Yes";
                                    } else {
                                        echo "N/A";
                                    } ?>
                                </td> -->

                                <td class="py-3 align-middle text-center rosdCorrect" style="<?= $bgStyleRosdCorrectDate ?>">
                                    <?= $rosdCorrectDate ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center freightTypeColumn" <?= isset($row["freight_type"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['freight_type']) ? $row['freight_type'] : "N/A" ?>
                                </td> -->
                                <?php
                                // Determine country code from airport code
                                $country_codes = [
                                    'SYD' => 'AUS',
                                    'MEL' => 'AUS',
                                    'HYD' => 'IND',
                                    'BOM' => 'IND',
                                    'NRT' => 'JPN',
                                    'BKK' => 'THA',
                                    'SIN' => 'SGP',
                                    'CGK' => 'IDS',
                                    'DUB' => 'IRL',
                                    'AKL' => 'NZL',
                                    'ICN' => 'KOR',
                                    'HKG' => 'HKG',
                                    'TPE' => 'TWN',
                                    'KUL' => 'MYS'
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
                                        if ($country === "AUS" || $country === "NZL") {
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

                                <td class="py-3 align-middle text-center estimatedDepartureDate" style="<?= $bgStyleEstimated ?>">
                                    <?= $estimatedDepartureDate ?>
                                </td>


                                <td class="py-3 align-middle text-center actualDepartureDate"
                                    <?= isset($row["actual_departure_date"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['actual_departure_date']) ? (date("j F Y", strtotime($row['actual_departure_date']))) : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center actualDeliveredDate"
                                    <?= isset($row["actual_delivered_date"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['actual_delivered_date']) ? (date("j F Y", strtotime($row['actual_delivered_date']))) : "N/A" ?>
                                </td>
                            </tr>
                            <tr class="collapse bg-light" id="<?= $uniqueId ?>">
                                <td colspan="100%">
                                    <div class="p-3 row">
                                        <div class="col-md-6 d-flex flex-column">
                                            <span><strong>Airport Code: </strong> <?php echo $airport_code ?> </span>
                                            <span><strong>C-Code: </strong> <?php
                                            $country_codes = [
                                                'SYD' => 'AUS',
                                                'MEL' => 'AUS',
                                                'HYD' => 'IND',
                                                'BOM' => 'IND',
                                                'NRT' => 'JPN',
                                                'BKK' => 'THA',
                                                'SIN' => 'SGP',
                                                'CGK' => 'IDS',
                                                'DUB' => 'IRL',
                                                'AKL' => 'NZL',
                                                'ICN' => 'KOR',
                                                'HKG' => 'HGK',
                                                'TPE' => 'TWN',
                                                'KUL' => 'MYS'
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
                        <option value="30" <?php echo $records_per_page == 30 ? 'selected' : ''; ?>>30</option>
                        <option value="40" <?php echo $records_per_page == 40 ? 'selected' : ''; ?>>40</option>
                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
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
                        <input type="date" class="form-control" name="importDate" value="<?php echo date('Y-m-d') ?>" required>

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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter PDC Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET">
                        <?php
                            // Preserve current search param
                            if (!empty($searchTerm)) {
                                echo '<input type="hidden" name="search" value="'.htmlspecialchars($searchTerm).'">';
                            }
                            // Also preserve other params like sort/order/page if needed
                            if (!empty($sort)) {
                                echo '<input type="hidden" name="sort" value="'.htmlspecialchars($sort).'">';
                            }
                            if (!empty($order)) {
                                echo '<input type="hidden" name="order" value="'.htmlspecialchars($order).'">';
                            }
                            if (!empty($page)) {
                                echo '<input type="hidden" name="page" value="'.htmlspecialchars($page).'">';
                            }
                        ?>
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <h5 class="signature-color fw-bold">Status</h5>
                                <?php
                                $statusFilters = ['AWS Removed FF', 'Cancelled', 'Sheet Metal', 'Sheet Metal Assembly', 'Electrical', 'Testing', 'Crated', 'In Transit', 'Delivered/Invoiced'];
                                $selected_status = isset($_GET['status']) ? (array) $_GET['status'] : [];
                                foreach ($statusFilters as $statusFilter) {
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="<?php echo strtolower(str_replace(' ', '', $statusFilter)) ?>"
                                            name="status[]" value="<?php echo $statusFilter ?>"
                                            <?php echo in_array($statusFilter, $selected_status) ? 'checked' : '';?>>
                                            <label for="<?php echo strtolower(str_replace(' ', '', $statusFilter)); ?>">
                                                <?php echo $statusFilter ?>
                                            </label>
                                    </p>
                                <?php } ?>
                            </div>

                            <div class="col-12 col-lg-6">
                                <h5 class="signature-color fw-bold">Timeframe</h5>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                <button class="btn btn-secondary me-1" type="button"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-dark" type="submit" name="apply_filters">Apply Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== R E P O R T  M O D A L ========================== -->
    <div class="modal fade" id="projectReportModal" tabindex="-1" aria-labelledby="projectReportModal" aria-hidden="true">
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
                var estimatedDepartureDate = button.getAttribute('data-estimated-departure-date');
                var actualDepartureDate = button.getAttribute('data-actual-departure-date');
                var actualDeliveredDate = button.getAttribute('data-actual-delivered-date');

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
                var modalEstimatedDepartureDate = myModalEl.querySelector('#estimatedDepartureDateToEdit');
                var modalActualDepartureDate = myModalEl.querySelector('#actualDepartureDateToEdit');
                var modalActualDeliveredDate = myModalEl.querySelector('#actualDeliveredDateToEdit');

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
                modalEstimatedDepartureDate.value = estimatedDepartureDate;
                modalActualDepartureDate.value = actualDepartureDate;
                modalActualDeliveredDate.value = actualDeliveredDate;
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
        function updateSort(sort, order) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
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
</body>

