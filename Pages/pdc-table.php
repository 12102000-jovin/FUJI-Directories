<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

$folder_name = "Project";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$project_sql = "SELECT * FROM pdc_projects";
$project_result = $conn->query($project_sql);

// Fetch results
$projects = [];
if ($project_result->num_rows > 0) {
    while ($row = $project_result->fetch_assoc()) {
        $projects[] = $row;
    }
} else {
    $projects = [];
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
                    <button class="btn text-white ms-2 bg-dark">
                        <p class="text-nowrap fw-bold mb-0 pb-0">Filter by <i class="fa-solid fa-filter py-1"></i></p>
                    </button>
                </div>
                <div
                    class="col-12 col-sm-4 col-lg-7 d-flex justify-content-center justify-content-sm-end align-items-center">
                    <div class="d-flex align-items-center">
                        <?php if ($role === "full control") { ?>
                            <!-- Admin Buttons -->
                            <button class="btn btn-success me-2" data-bs-toggle="modal">
                                <i class="fa-solid fa-square-poll-vertical"></i> Report
                            </button>
                            <a class="btn btn-primary me-2" type="button" data-bs-toggle="modal"
                                data-bs-target="#projectDashboardModal">
                                <i class="fa-solid fa-chart-pie"></i> Dashboard
                            </a>
                            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                                <i class="fa-solid fa-plus"></i> Add Project
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="py-4 align-middle text-center" style="max-width: 100px;">Item Numbers</th>
                        <th class="py-4 align-middle text-center">Project No</th>
                        <th class="py-4 align-middle text-center">FBN</th>
                        <th class="py-4 align-middle text-center">Site Type</th>
                        <!-- <th class="py-4 align-middle text-center">FBN</th> -->
                        <!-- <th class="py-4 align-middle text-center">C-Code</th> -->
                        <th class="py-4 align-middle text-center">Status</th>
                        <!-- <th class="py-4 align-middle text-center" style="min-width:150px">Customer</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:200px">Entity's PO No.</th> -->
                        <!-- <th class="py-4 align-middle text-center">AWS's PO No.</th> -->
                        <th class="py-4 align-middle text-center">PO</th>
                        <!-- <th class="py-4 align-middle text-center" style="min-width:150px">PO Date</th> -->
                        <!-- <th class="py-4 align-middle text-center">Production from PO</th> -->
                        <th class="py-4 align-middle text-center" style="min-width:280px">Version</th>
                        <!-- <th class="py-4 align-middle text-center" style="min-width:280px;">Drawing Status</th> -->
                        <th class="py-4 align-middle text-center">Qty</th>
                        <th class="py-4 align-middle text-center">Cost</th>
                        <!-- <th class="py-4 align-middle text-center">Serial Numbers</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (PO)</th> -->
                        <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (Forecast)</th>
                        <th class="py-4 align-middle text-center">Red Flag</th>
                        <!-- <th class="py-4 align-middle text-center">Resolved</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (Resolved)</th> -->
                        <!-- <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (Changed)</th> -->
                        <!-- <th class="py-4 align-middle text-center">Approved</th> -->
                        <th class="py-4 align-middle text-center" style="min-width:180px">rOSD (Correct)</th>
                        <!-- <th class="py-4 align-middle text-center">Freight Type</th> -->
                        <th class="py-4 align-middle text-center" style="min-width:180px">Estimated Departure Date</th>
                        <th class="py-4 align-middle text-center" style="min-width:180px">Actual Departure Date</th>
                        <th class="py-4 align-middle text-center" style="min-width:180px">Actual Delivered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($projects)) { ?>
                        <?php
                        $item_number = 1;
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
                                <td class="py-3 align-middle text-center projectNoColumn" <?= isset($row["project_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['project_no']) ? $row['project_no'] : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center"><?= $row["fbn"] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row["site_type"] ?></td>
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
                                <td class="py-3 align-middle text-center statusColumn" <?= isset($row["status"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
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
                                <td class="py-3 align-middle text-center purchaseOrderColumn">
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

                                <td class="py-3 align-middle text-center"><?= $row['version'] ?></td>
                                <!-- <td class="py-3 align-middle text-center"><?= $row['drawing_status'] ?></td> -->
                                <td class="py-3 align-middle text-center"><?= $row['qty'] ?></td>
                                <td class="py-3 align-middle text-center" <?= isset($row["cost"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['cost']) ? '$' . number_format($row['cost'], 2) : "N/A" ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center serialNumberColumn" <?= isset($row["serial_numbers"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['serial_numbers']) ? $row['serial_numbers'] : "N/A" ?>
                                </td> -->
                                <!-- <td class="py-3 align-middle text-center" <?= isset($row["rOSD_po"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_po']) ? (date("j F Y", strtotime($row['rOSD_po']))) : "N/A" ?>
                                </td> -->
                                <td class="py-3 align-middle text-center" <?= isset($row["rOSD_forecast"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['rOSD_forecast']) ? (date("j F Y", strtotime($row['rOSD_forecast']))) : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center <?= ($row['conflict'] === "1") ? 'bg-danger text-white fw-bold' : '' ?>"
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

                                <td class="py-3 align-middle text-center rosdResolvedColumn"
                                    style="<?= $bgStyleRosdCorrectDate ?>">
                                    <?= $rosdCorrectDate ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center freightTypeColumn" <?= isset($row["freight_type"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['freight_type']) ? $row['freight_type'] : "N/A" ?>
                                </td> -->
                                <td class="py-3 align-middle text-center estimatedDepartureDateColumn"
                                    <?= isset($row["estimated_departure_date"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['estimated_departure_date']) ? (date("j F Y", strtotime($row['estimated_departure_date']))) : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center actualDepartureDateColumn"
                                    <?= isset($row["actual_departure_date"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['actual_departure_date']) ? (date("j F Y", strtotime($row['actual_departure_date']))) : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center actualDeliveredDateColumn"
                                    <?= isset($row["actual_delivered_date"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['actual_delivered_date']) ? (date("j F Y", strtotime($row['actual_delivered_date']))) : "N/A" ?>
                                </td>
                            </tr>
                            <tr class="collapse bg-light" id="<?= $uniqueId ?>">
                                <td colspan="100%">
                                    <div class="p-3 row">
                                        <div class="col-md-4 d-flex flex-column">
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
                                            <span><strong>Customer:</strong> <?= htmlspecialchars($row['customer']) ?></span>
                                            <span><strong>Entity's PO No: </strong>
                                                <?= isset($row["entity_po_no"]) ? $row["entity_po_no"] : "N/A" ?></span>
                                            <span><strong>AWS's PO No: </strong>
                                                <?= isset($row['aws_po_no']) ? $row['aws_po_no'] : "N/A" ?></span>
                                            <span><strong>PO Date: </strong>
                                                <?= isset($row['purchase_order_date']) ? (date("j F Y", strtotime($row['purchase_order_date']))) : "N/A" ?></span>
                                            <span><strong>Production from PO: </strong>
                                                <?= !empty($weeksDisplay) ? $weeksDisplay : "N/A" ?></span>
                                        </div>
                                        <div class="col-md-4 d-flex flex-column">
                                            <span><strong>Drawing Status: </strong>
                                                <?= isset($row['drawing_status']) ? $row['drawing_status'] : "N/A" ?></span>
                                            <span><strong>Qty: </strong> <?= isset($row["qty"]) ? $row['qty'] : "N/A" ?></span>
                                            <span><strong>Serial Numbers: </strong>
                                                <?= isset($row['serial_numbers']) ? $row['serial_numbers'] : "N/A" ?></span>
                                        </div>
                                        <div class="col-md-4 d-flex flex-column">
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
</body>