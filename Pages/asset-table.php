<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

$folder_name = "Asset";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'asset_no';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Handle sorting for employee name specifically
$orderByClause = "";
switch ($sort) {
    case 'allocated_to':
        // Sort by employee last name, then first name
        $orderByClause = "employees.first_name $order";
        break;
    default:
        $orderByClause = "$sort $order";
        break;
}

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 30; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Search condition
$whereClause = "(asset_no LIKE '%$searchTerm%' 
                OR asset_name LIKE '%$searchTerm%' 
                OR serial_number LIKE '%$searchTerm%' 
                OR operating_system LIKE '%$searchTerm%'
                OR CONCAT(employees.first_name, ' ', employees.last_name) LIKE '%$searchTerm%'
                OR employees.first_name LIKE '%$searchTerm%'
                OR employees.last_name LIKE '%$searchTerm%'
                OR employees.nickname LIKE '%$searchTerm%')";

// Arrays to hold selected filter values
$selected_departments = [];
$selected_status = [];
$selected_account = [];
$selected_whs = [];
$selected_ict = [];
$selected_due_date = [];

$filterApplied = false; // Variable to check if any filter is applied

if (isset($_GET['apply_filters'])) {
    // Capture the selected departments (department IDs)
    if (isset($_GET['department']) && is_array($_GET['department'])) {
        $selected_departments = array_map('intval', $_GET['department']); // ensure safe integers
        $department_placeholders = implode(',', $selected_departments);
        $whereClause .= " AND assets.department_id IN ($department_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected status 
    if (isset($_GET['status']) && is_array($_GET['status'])) {
        $selected_status = $_GET['status'];
        // Escape each value for SQL
        $escaped_status = array_map(function ($s) use ($conn) {
            return "'" . $conn->real_escape_string($s) . "'";
        }, $selected_status);

        $status_placeholders = implode(',', $escaped_status);
        $whereClause .= " AND assets.status IN ($status_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected account
    if (isset($_GET['account']) && is_array($_GET['account'])) {
        $selected_accounts = array_map('intval', $_GET['account']); // ensure safe integers
        $account_placeholders = implode(',', $selected_accounts);
        $whereClause .= " AND assets.accounts_asset IN ($account_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected WHS
    if (isset($_GET['whs']) && is_array($_GET['whs'])) {
        $selected_whs = array_map('intval', $_GET['whs']); // ensure safe integers
        $whs_placeholders = implode(',', $selected_whs);
        $whereClause .= " AND assets.whs_asset IN ($whs_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected ICT
    if (isset($_GET['ict']) && is_array($_GET['ict'])) {
        $selected_ict = array_map('intval', $_GET['ict']); // ensure safe integers
        $ict_placeholders = implode(',', $selected_ict);
        $whereClause .= " AND assets.ict_asset IN ($ict_placeholders)";
        $filterApplied = true;
    }

    date_default_timezone_set('Australia/Sydney');
    $today = date('Y-m-d');
    $next_30_days = date('Y-m-d', strtotime('+30 days'));

    // Capture the due date filter
    if (isset($_GET['due_dates']) && is_array($_GET['due_dates'])) {
        $selected_due_dates = $_GET['due_dates'];
        $today = new DateTime("now", new DateTimeZone("Australia/Sydney"));
        $todayStr = $today->format('Y-m-d');
        $thirtyDaysLater = $today->modify('+30 days')->format('Y-m-d');

        $due_date_conditions = [];

        foreach ($selected_due_dates as $filter) {
            if ($filter === 'Almost Due for Calibration') {
                $due_date_conditions[] = "(SELECT MAX(due_date) FROM asset_details WHERE asset_details.asset_id = assets.asset_id AND categories = 'Calibration') > CURDATE() AND 
                                           (SELECT MAX(due_date) FROM asset_details WHERE asset_details.asset_id = assets.asset_id AND categories = 'Calibration') <= '$thirtyDaysLater'";
            } elseif ($filter === 'Due for Calibration') {
                $due_date_conditions[] = "(SELECT MAX(due_date) FROM asset_details WHERE asset_details.asset_id = assets.asset_id AND categories = 'Calibration') <= CURDATE()";
            } elseif ($filter === 'Almost Due for Maintenance') {
                $due_date_conditions[] = "(SELECT MAX(due_date) FROM asset_details WHERE asset_details.asset_id = assets.asset_id AND categories = 'Maintenance') > CURDATE() AND 
                                           (SELECT MAX(due_date) FROM asset_details WHERE asset_details.asset_id = assets.asset_id AND categories = 'Maintenance') <= '$thirtyDaysLater'";
            } elseif ($filter === 'Due for Maintenance') {
                $due_date_conditions[] = "(SELECT MAX(due_date) FROM asset_details WHERE asset_details.asset_id = assets.asset_id AND categories = 'Maintenance') <= CURDATE()";
            }
        }

        if (!empty($due_date_conditions)) {
            $whereClause .= " AND (" . implode(" OR ", $due_date_conditions) . ")";
            $filterApplied = true;
        }
    }
}

$assets_sql = "SELECT 
                    assets.*, 
                    location.location_name,
                    department.department_name,
                    latest_cal.latest_calibration,
                    latest_main.latest_maintenance,
                    employees.first_name,
                    employees.last_name,
                    employees.nickname
                FROM assets
                LEFT JOIN location 
                    ON assets.location_id = location.location_id
                LEFT JOIN department
                    ON assets.department_id = department.department_id
                LEFT JOIN (
                    SELECT asset_id, MAX(due_date) AS latest_calibration
                    FROM asset_details
                    WHERE categories = 'Calibration'
                    GROUP BY asset_id
                ) AS latest_cal
                    ON assets.asset_id = latest_cal.asset_id
                LEFT JOIN (
                    SELECT asset_id, MAX(due_date) AS latest_maintenance
                    FROM asset_details
                    WHERE categories = 'Maintenance'
                    GROUP BY asset_id
                ) AS latest_main
                    ON assets.asset_id = latest_main.asset_id
                LEFT JOIN employees ON assets.allocated_to = employees.employee_id 
                WHERE $whereClause 
                ORDER BY $orderByClause 
                LIMIT $offset, $records_per_page";

$assets_result = $conn->query($assets_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total 
                      FROM assets 
                      LEFT JOIN employees ON assets.allocated_to = employees.employee_id 
                      WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// ========================= D E L E T E  A S S E T =========================
$directoryBasePath = "D:/FSMBEH-Data/00 - QA/04 - Assets/";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assetIdToDelete"])) {
    $assetIdToDelete = $_POST["assetIdToDelete"];

    // Get asset number (you might need a query for this if it's not passed)
    $get_asset_no_sql = "SELECT asset_no FROM assets WHERE asset_id = ?";
    $get_asset_no_stmt = $conn->prepare($get_asset_no_sql);
    $get_asset_no_stmt->bind_param("i", $assetIdToDelete);
    $get_asset_no_stmt->execute();
    $get_asset_no_stmt->bind_result($asset_no);
    $get_asset_no_stmt->fetch();
    $get_asset_no_stmt->close();

    // Build full directory path
    $directoryPath = $directoryBasePath . $asset_no;

    // First delete the folder
    if (is_dir($directoryPath)) {
        // Function to delete folder recursively
        function deleteFolder($folderPath)
        {
            $files = array_diff(scandir($folderPath), ['.', '..']);
            foreach ($files as $file) {
                $filePath = "$folderPath/$file";
                if (is_dir($filePath)) {
                    deleteFolder($filePath); // recursion
                } else {
                    unlink($filePath);
                }
            }
            return rmdir($folderPath);
        }

        deleteFolder($directoryPath);
    }

    // Then delete the asset from database
    $delete_asset_sql = "DELETE FROM assets WHERE asset_id = ?";
    $delete_asset_result = $conn->prepare($delete_asset_sql);
    $delete_asset_result->bind_param("i", $assetIdToDelete);

    if ($delete_asset_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error: " . $delete_asset_result . "<br>" . $conn->error;
    }
    $delete_asset_result->close();
}


// Get all URL parameters from $_GET
$urlParams = $_GET;

// Get total count of filtered results (Total)
$count_sql = "SELECT COUNT(*) as total FROM assets LEFT JOIN employees ON assets.allocated_to = employees.employee_id WHERE $whereClause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_count = $count_row['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Asset Table</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;700&display=swap" rel="stylesheet">
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
            <!-- <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Asset Table
                    </li>
                </ol>
            </nav> -->
            <!-- <div class="d-flex justify-content-end mb-3">
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
            </div> -->
        </div>

        <div class="row mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="col-8 col-lg-5">
                    <form method="GET" id="searchForm">
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
                        <div class="d-flex align-items-center">
                            <div class="input-group me-2">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="searchDocuments" name="search"
                                    placeholder="Search Documents" value="<?php echo htmlspecialchars($searchTerm) ?>">
                            </div>
                            <button class="btn" type="submit"
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">
                                Search
                            </button>
                            <button class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </button>
                            <button class="btn text-white ms-2 bg-dark" data-bs-toggle="modal" type="button"
                                data-bs-target="#filterAssetModal">
                                <p class="text-nowrap fw-bold mb-0 pb-0">Filter <i class="fa-solid fa-filter py-1"></i>
                                </p>
                            </button>
                        </div>
                    </form>
                </div>
                <?php if ($role === "full control" || $role === "modify 1") { ?>
                    <div class="d-flex justify-content-end align-items-center col-4 col-lg-7">
                        <a class="btn btn-primary me-2" type="button" data-bs-toggle="modal"
                            data-bs-target="#assetDashboardModal"> <i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addAssetModal"> <i
                                class="fa-solid fa-plus"></i> Add Asset</button>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="d-flex flex-wrap mb-2">
            <?php foreach ($urlParams as $key => $value): ?>
                <?php if (!empty($value)): // Only show the span if the value is not empty ?>
                    <?php
                    if ($key === 'order' || $key === 'view') {
                        continue;
                    }

                    // Check if the value is the department filter
                    if ($key === 'search') {
                        // Handle the search filter
                        ?>
                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                            <strong><span class="text-warning">Search:
                                </span><?php echo htmlspecialchars($value); ?></strong>
                            <a href="?<?php
                            // Remove 'Search' from the URL
                            $filteredParams = $_GET;
                            unset($filteredParams['search']);
                            echo http_build_query($filteredParams);
                            ?>" class="text-white ms-1">
                                <i class="fa-solid fa-times"></i>
                            </a>
                        </span>
                        <?php
                    } else if ($key === 'department' && is_array($value)) {
                        // Map department IDs to department names and display each in a separate badge
                        foreach ($value as $department_id) {
                            // Fetch the department name for each selected department ID
                            $department_sql = "SELECT department_name FROM department WHERE department_id = ?";
                            $stmt = $conn->prepare($department_sql);
                            $stmt->bind_param("i", $department_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($row = $result->fetch_assoc()) {
                                $department_name = $row['department_name'];
                            }

                            // Display a separate badge for each department
                            ?>
                                <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                    <strong><span class="text-warning">Department:
                                        </span><?php echo htmlspecialchars($department_name); ?></strong>
                                    <a href="?<?php
                                    // Remove this specific department filter from the URL
                                    $filteredParams = $_GET;
                                    $filteredParams['department'] = array_diff($filteredParams['department'], [$department_id]);
                                    echo http_build_query($filteredParams);
                                    ?>" class="text-white ms-1">
                                        <i class="fa-solid fa-times"></i>
                                    </a>
                                </span>
                            <?php
                        }
                    } else if ($key === 'status' && is_array($value)) {
                        foreach ($value as $status) {
                            ?>
                                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                        <strong><span class="text-warning">Status:
                                            </span><?php echo htmlspecialchars($status) ?></strong>
                                        <a href="?<?php
                                        // Remove this specific status filter from URL
                                        $filteredParams = $_GET;
                                        $filteredParams['status'] = array_diff($filteredParams['status'], [$status]);
                                        echo http_build_query($filteredParams);
                                        ?>" class="text-white ms-1">
                                            <i class="fa-solid fa-times fa-"></i>
                                        </a>
                                    </span>
                            <?php
                        }
                    } else if ($key === 'account' && is_array($value)) {
                        foreach ($value as $account) {
                            ?>
                                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                            <strong><span class="text-warning">Accounts:
                                                </span><?php if ($account === "1") {
                                                    echo "Yes";
                                                } else if ($account === "0") {
                                                    echo "No";
                                                } ?></strong>
                                            <a href="?<?php
                                            // Remove this specific account filter from URL
                                            $filteredParams = $_GET;
                                            $filteredParams['account'] = array_diff($filteredParams['account'], [$account]);
                                            echo http_build_query($filteredParams);
                                            ?>" class="text-white ms-1">
                                                <i class="fa-solid fa-times fa-"></i>
                                            </a>
                                        </span>
                            <?php
                        }
                    } else if ($key === 'whs' && is_array($value)) {
                        foreach ($value as $whs) {
                            ?>
                                            <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                <strong><span class="text-warning">WHS:
                                                    </span><?php if ($whs === "1") {
                                                        echo "Yes";
                                                    } else if ($whs === "0") {
                                                        echo "No";
                                                    } ?></strong>
                                                <a href="?<?php
                                                // Remove this specific whs filter from URL
                                                $filteredParams = $_GET;
                                                $filteredParams['whs'] = array_diff($filteredParams['whs'], [$whs]);
                                                echo http_build_query($filteredParams);
                                                ?>" class="text-white ms-1">
                                                    <i class="fa-solid fa-times fa-"></i>
                                                </a>
                                            </span>
                            <?php
                        }
                    } else if ($key === 'ict' && is_array($value)) {
                        foreach ($value as $ict) {
                            ?>
                                                <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                    <strong><span class="text-warning">ICT:
                                                        </span><?php if ($ict == "1") {
                                                            echo "Yes";
                                                        } else if ($ict === "0") {
                                                            echo "No";
                                                        } ?> </strong>
                                                    <a href="?<?php
                                                    // Remove this specific ict filter from URL
                                                    $filteredParams = $_GET;
                                                    $filteredParams['ict'] = array_diff($filteredParams['ict'], [$ict]);
                                                    echo http_build_query($filteredParams);
                                                    ?>" class="text-white ms-1">
                                                        <i class="fa-solid fa-times fa-"></i>
                                                    </a>
                                                </span>
                            <?php
                        }
                    } else if ($key === 'due_dates' && is_array($value)) {
                        foreach ($value as $due_dates) {
                            ?>
                                                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                        <strong><span class="text-warning">Due:
                                                            </span><?php echo htmlspecialchars($due_dates) ?></strong>
                                                        <a href="?<?php
                                                        // Remove this specific due date filter from URL
                                                        $filteredParams = $_GET;
                                                        $filteredParams['due_dates'] = array_diff($filteredParams['due_dates'], [$due_dates]);
                                                        echo http_build_query($filteredParams);
                                                        ?>" class="text-white ms-1">
                                                            <i class="fa-solid fa-times fa-"></i>
                                                        </a>
                                                    </span>
                            <?php
                        }
                    }
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

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


        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th style="min-width: 150px;"></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('department_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Department <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('asset_no', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> FE No. <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('asset_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Asset Name <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('status', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Status <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('disposal_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Disposal Date <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('allocated_to', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Allocated to <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('serial_number', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Serial Number <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('location_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer"> Asset Location <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center">
                            <as onclick="updateSort('accounts_asset', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer">Accounts <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center">
                            <as onclick="updateSort('cost', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer">Cost <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <!-- <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('whs_asset', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="curdor: pointer">WHS <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th> -->
                        <!-- <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('ict_asset', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="curdor: pointer">ICT <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th> -->
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('purchase_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Purchase Date <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('latest_calibration', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Next Calibration Due <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('latest_maintenance', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Next Maintenance Due <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center">
                            Manual/Work Instruction
                        </th>
                        <th class="py-4 align-middle text-center">
                            Risk Assessment
                        </th>
                        <th class="py-4 align-middle text-center">
                            Notes
                        </th>
                        <?php if ($role === "full control") { ?>
                            <th class="py-4 align-middle text-center">
                                Depreciation Timeframe
                            </th>
                            <th class="py-4 align-middle text-center">
                                Depreciation Percentages
                            </th>
                            <th class="py-4 align-middle text-center">
                                Est. Current Value
                            </th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assets_result->num_rows > 0) { ?>
                        <?php while ($row = $assets_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="align-middle text-center">
                                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                                        <button class="btn text-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteConfirmationModal" data-asset-id="<?= $row["asset_id"] ?>"
                                            data-asset-no="<?= $row["asset_no"] ?>">
                                            <i class="fa-regular fa-trash-can text-danger"></i>
                                        </button>
                                        <button class="btn" data-bs-toggle="modal" data-bs-target="#editAssetModal"
                                            data-asset-id="<?= $row["asset_id"] ?>" data-asset-no="<?= $row["asset_no"] ?>"
                                            data-department-id="<?= $row["department_id"] ?>"
                                            data-asset-name="<?= $row["asset_name"] ?>" data-status="<?= $row["status"] ?>"
                                            data-disposal-date="<?= $row["disposal_date"] ?>"
                                            data-serial-number="<?= $row["serial_number"] ?>" data-cost="<?= $row["cost"] ?>"
                                            data-location="<?= $row["location_id"] ?>"
                                            data-accounts-asset="<?= $row["accounts_asset"] ?>"
                                            data-whs-asset="<?= $row["whs_asset"] ?>" data-ict-asset="<?= $row["ict_asset"] ?>"
                                            data-purchase-date="<?= $row["purchase_date"] ?>" data-notes="<?= $row["notes"] ?>"
                                            data-allocated-to="<?= $row["allocated_to"] ?>"
                                            data-depreciation-timeframe="<?= $row["depreciation_timeframe"] ?>"
                                            data-depreciation-percentage="<?= $row["depreciation_percentage"] ?>">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    <?php } ?>
                                    <a class="btn text-warning" href="asset-details-page.php?asset_no=<?= $row['asset_no'] ?>">
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                    </a>
                                </td>
                                <td class="py-3 align-middle text-center"><?= $row['department_name'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['asset_no'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['asset_name'] ?></td>
                                <td class="py-3 align-middle text-center 
                                    <?php
                                    if ($row['status'] === "Current") {
                                        echo "bg-success text-white";
                                    } else if ($row['status'] === "Obsolete") {
                                        echo "bg-danger bg-opacity-75 text-white";
                                    } else if ($row['status'] === "Disposed") {
                                        echo "bg-danger text-white";
                                    } else if ($row['status'] === "Out for Service / Calibration") {
                                        echo "bg-warning text-white";
                                    }
                                    ?>
                                "><?= $row['status'] ?></td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['disposal_date']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['disposal_date']) && $row['disposal_date'] ? date('j F Y', strtotime($row['disposal_date'])) : 'N/A' ?>
                                </td>
                                <?php
                                // Remove the old employee query section and replace with this simpler version
                                $employee_name = "N/A";

                                if (!empty($row['first_name']) && !empty($row['last_name'])) {
                                    $employee_first_name = $row['first_name'];
                                    $employee_last_name = $row['last_name'];
                                    $employee_nickname = $row['nickname'];

                                    // Combine with nickname if it exists
                                    if (!empty($employee_nickname)) {
                                        $employee_name = $employee_first_name . ' ' . $employee_last_name . ' (' . $employee_nickname . ')';
                                    } else {
                                        $employee_name = $employee_first_name . ' ' . $employee_last_name;
                                    }
                                }
                                ?>

                                <td class="py-3 align-middle text-center"
                                    style="<?= ($employee_name !== 'N/A') ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= ($employee_name !== 'N/A') ? htmlspecialchars($employee_name) : 'N/A' ?>
                                </td>

                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['serial_number']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['serial_number']) ? htmlspecialchars($row['serial_number']) : 'N/A' ?>
                                </td>
                                <td class="py-3 align-middle text-center"><?= $row['location_name'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['accounts_asset'] == 1 ? 'Yes' : 'No' ?></td>
                                <td class="py-3 align-middle text-center" <?= isset($row["cost"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['cost']) ? '$' . number_format($row['cost'], 2) : "N/A" ?>
                                </td>
                                <!-- <td class="py-3 align-middle text-center"><?= $row['whs_asset'] == 1 ? 'Yes' : 'No' ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['ict_asset'] == 1 ? 'Yes' : 'No' ?></td> -->
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['purchase_date']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['purchase_date']) && $row['purchase_date'] ? date('j F Y', strtotime($row['purchase_date'])) : 'N/A' ?>
                                </td>
                                <?php
                                $today = new DateTime('now', new DateTimeZone('Australia/Sydney'));
                                $calClass = '';
                                $calStyle = '';
                                $maintClass = '';
                                $maintStyle = '';

                                // Calibration logic
                                if (isset($row['latest_calibration']) && $row['latest_calibration']) {
                                    $calDate = new DateTime($row['latest_calibration'], new DateTimeZone('Australia/Sydney'));
                                    $diff = $today->diff($calDate)->format('%r%a');
                                    if ($diff < 0) {
                                        $calClass = 'bg-danger text-white';
                                    } elseif ($diff <= 30) {
                                        $calClass = 'bg-danger text-white bg-opacity-50';
                                    }
                                } else {
                                    $calStyle = 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;';
                                }

                                // Maintenance logic
                                if (isset($row['latest_maintenance']) && $row['latest_maintenance']) {
                                    $maintDate = new DateTime($row['latest_maintenance'], new DateTimeZone('Australia/Sydney'));
                                    $diff = $today->diff($maintDate)->format('%r%a');
                                    if ($diff < 0) {
                                        $maintClass = 'bg-danger text-white';
                                    } elseif ($diff <= 30) {
                                        $maintClass = 'bg-danger text-white bg-opacity-50';
                                    }
                                } else {
                                    $maintStyle = 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;';
                                }
                                ?>

                                <!-- Calibration -->
                                <td class="py-3 align-middle text-center <?= $calClass ?>" style="<?= $calStyle ?>">
                                    <?= isset($row['latest_calibration']) && $row['latest_calibration']
                                        ? date('j F Y', strtotime($row['latest_calibration']))
                                        : 'N/A' ?>
                                </td>

                                <!-- Maintenance -->
                                <td class="py-3 align-middle text-center <?= $maintClass ?>" style="<?= $maintStyle ?>">
                                    <?= isset($row['latest_maintenance']) && $row['latest_maintenance']
                                        ? date('j F Y', strtotime($row['latest_maintenance']))
                                        : 'N/A' ?>
                                </td>

                                <td
                                    class="py-3 align-middle text-center text-white <?php echo !empty($row['manual']) ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php if (!empty($row["manual"])) {
                                        echo "Yes";
                                    } else {
                                        echo "No";
                                    } ?>
                                </td>
                                <td
                                    class="py-3 align-middle text-center text-white <?php echo !empty($row['risk_assessment']) ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php if (!empty($row["risk_assessment"])) {
                                        echo "Yes";
                                    } else {
                                        echo "No";
                                    } ?>
                                </td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['notes']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['notes']) ? htmlspecialchars($row['notes']) : 'N/A' ?>
                                </td>
                                <?php if ($role === "full control") { ?>
                                    <td class="py-3 align-middle text-center"
                                        style="<?= isset($row['depreciation_timeframe']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                        <?= isset($row['depreciation_timeframe']) ? htmlspecialchars($row['depreciation_timeframe'] . ' Months') : 'N/A' ?>
                                    </td>
                                    <td class="py-3 align-middle text-center"
                                        style="<?= isset($row['depreciation_percentage']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                        <?= isset($row['depreciation_percentage']) ? htmlspecialchars($row['depreciation_percentage'] . '%') : 'N/A' ?>
                                    </td>
                                    <td class="py-3 align-middle text-center">
                                        <?php
                                        $cost = floatval($row['cost']);
                                        $depreciationRate = floatval($row['depreciation_percentage']);
                                        $depreciationTimeframe = intval($row['depreciation_timeframe']);
                                        $purchaseDate = isset($row['purchase_date']) ? new DateTime($row['purchase_date']) : null;
                                        $today = new DateTime('now', new DateTimeZone('Australia/Sydney'));
                                        $estimatedValue = "N/A";

                                        if ($cost && $depreciationRate && $depreciationTimeframe && $purchaseDate) {
                                            $interval = $purchaseDate->diff($today);
                                            $monthsElapsed = ($interval->y * 12) + $interval->m;

                                            $monthsElapsed = min($monthsElapsed, $depreciationTimeframe); // cap it at the timeframe
                                            $estimatedValueCalc = $cost - ($cost * ($depreciationRate / 100) * ($monthsElapsed / $depreciationTimeframe));
                                            $estimatedValue = '$' . number_format(max($estimatedValueCalc, 0), 2); // prevent negative value
                                        }
                                        echo $estimatedValue;
                                        ?>

                                    </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="22" class="text-center py-3">No assets found</td>
                        </tr>
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

    <!-- ================== Add Asset Modal ================== -->
    <div class="modal fade" id="addAssetModal" tabindex="-1" aria-labelledby="addAssetModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/AddAssetForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Edit Asset Modal ================== -->
    <div class="modal fade" id="editAssetModal" tabindex="-1" aria-labelledby="editAssetModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/EditAssetForm.php") ?>
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
                    <p>Are you sure you want to delete <span class="fw-bold" id="assetNoToDelete"></span> asset?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="assetIdToDelete" id="assetIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Asset Dashboard Modal ================== -->
    <div class="modal fade" id="assetDashboardModal" tab-index="-1" aria-labelledby="assetDashboardModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assetDashboardModalLabel">Asset Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body background-color">
                    <?php require_once("../PageContent/asset-index-content.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Filter Asset Modal ================== -->
    <div class="modal fade" id="filterAssetModal" tabindex="1" aria-labelledby="filterAssetModal" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET">
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
                                <h5 class="signature-color fw-bold">Department</h5>
                                <?php
                                $department_sql = "SELECT * FROM department";
                                $department_result = $conn->query($department_sql);
                                $selected_departments = isset($_GET['department']) ? $_GET['department'] : [];
                                if ($department_result->num_rows > 0) { ?>
                                    <?php while ($row = $department_result->fetch_assoc()) { ?>
                                        <p class="mb-0 pb-0">
                                            <input type="checkbox" class="form-check-input"
                                                id="department_<?php echo $row['department_id'] ?>" name="department[]"
                                                value="<?php echo $row['department_id']; ?>" <?php echo in_array($row['department_id'], $selected_departments) ? 'checked' : ''; ?> />
                                            <label
                                                for="department_<?php echo $row['department_id']; ?>"><?php echo $row['department_name']; ?></label>
                                        </p>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p>No departments found.</p>
                                <?php } ?>
                            </div>
                            <div class="col-12 col-lg-4">
                                <h5 class="signature-color fw-bold mt-4 mt-lg-0">Status</h5>
                                <?php
                                $status = ['Current', 'Obsolete', 'Disposed', 'Out for Service / Calibration'];
                                $selected_status = isset($_GET['status']) ? $_GET['status'] : [];
                                foreach ($status as $stat) {
                                    ?>
                                    <p class="mb-0 p-0">
                                        <input type="checkbox" class="form-check-input" id="<?php echo strtolower($stat) ?>"
                                            name="status[]" value="<?php echo $stat ?>" <?php echo in_array($stat, $selected_status) ? 'checked' : ''; ?>>
                                        <label for="<?php echo strtolower($stat); ?>"><?php echo $stat; ?></label>
                                    </p>
                                <?php } ?>
                                <h5 class="signature-color fw-bold mt-4">Almost / Over Due Date</h5>
                                <?php
                                $due_dates = ['Almost Due for Calibration', 'Almost Due for Maintenance', 'Due for Calibration', 'Due for Maintenance'];
                                $selected_due_date = isset($_GET['due_dates']) ? $_GET['due_dates'] : [];
                                foreach ($due_dates as $due_date) {
                                    ?>
                                    <p class="mb-0 p-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="<?php echo strtolower($due_date) ?>" name="due_dates[]"
                                            value="<?php echo $due_date ?>" <?php echo in_array($due_date, $selected_due_date) ? 'checked' : ''; ?>>
                                        <label for="<?php echo strtolower($due_date); ?>"><?php echo $due_date; ?></label>
                                    </p>
                                <?php } ?>
                            </div>
                            <div class="col-12 col-lg-4">
                                <h5 class="signature-color fw-bold mt-4 mt-lg-0">Accounts</h5>
                                <?php
                                $account = [1, 0];
                                $selected_account = isset($_GET['account']) ? $_GET['account'] : [];
                                foreach ($account as $i) {
                                    $id = "account_$i"; // unique ID
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input" id="<?php echo $id ?>"
                                            name="account[]" value="<?php echo $i ?>" <?php echo in_array($i, $selected_account) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $id; ?>"><?php echo $i === 1 ? "Yes" : "No"; ?></label>
                                    </p>
                                <?php } ?>

                                <h5 class="signature-color fw-bold mt-4">WHS</h5>
                                <?php
                                $whs = [1, 0];
                                $selected_whs = isset($_GET['whs']) ? $_GET['whs'] : [];
                                foreach ($whs as $j) {
                                    $id = "whs_$j"; // unique ID
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input" id="<?php echo $id ?>" name="whs[]"
                                            value="<?php echo $j ?>" <?php echo in_array($j, $selected_whs) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $id; ?>"><?php echo $j === 1 ? "Yes" : "No"; ?></label>
                                    </p>
                                <?php } ?>

                                <h5 class="signature-color fw-bold mt-4">ICT</h5>
                                <?php
                                $ict = [1, 0];
                                $selected_ict = isset($_GET['ict']) ? $_GET['ict'] : [];
                                foreach ($ict as $k) {
                                    $id = "ict_$k"; // unique ID
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input" id="<?php echo $id ?>" name="ict[]"
                                            value="<?php echo $k ?>" <?php echo in_array($k, $selected_ict) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $id; ?>"><?php echo $k === 1 ? "Yes" : "No"; ?></label>
                                    </p>
                                <?php } ?>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
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

    <?php require_once("../logout.php") ?>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editAssetModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var assetNo = button.getAttribute('data-asset-no');

                // Update the modal's content with the extracted info
                var modalAssetNo = myModalEl.querySelector('#assetNo');

                modalAssetNo.value = assetNo;

            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editAssetModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal

                var assetId = button.getAttribute('data-asset-id');
                var assetNo = button.getAttribute('data-asset-no');
                var department = button.getAttribute('data-department-id');
                var assetName = button.getAttribute('data-asset-name');
                var status = button.getAttribute('data-status');
                var disposalDate = button.getAttribute('data-disposal-date');
                var serialNumber = button.getAttribute('data-serial-number');
                var cost = button.getAttribute('data-cost');
                var purchaseDate = button.getAttribute('data-purchase-date');
                var location = button.getAttribute('data-location');
                var notes = button.getAttribute('data-notes');
                var allocatedTo = button.getAttribute('data-allocated-to');
                var depreciationTimeframe = button.getAttribute('data-depreciation-timeframe');
                var depreciationPercentage = button.getAttribute('data-depreciation-percentage');
                var accountsAsset = button.getAttribute('data-accounts-asset');
                var whsAsset = button.getAttribute('data-whs-asset');
                var ictAsset = button.getAttribute('data-ict-asset');


                // Update the modal's content with the extracted info
                var modalAssetId = myModalEl.querySelector('#assetIdToEdit');
                var modalAssetNo = myModalEl.querySelector('#assetNoToEdit');
                var modalDepartment = myModalEl.querySelector('#departmentToEdit');
                var modalAssetName = myModalEl.querySelector('#assetNameToEdit');
                var modalStatus = myModalEl.querySelector('#statusToEdit');
                var modalDisposalDate = myModalEl.querySelector('#disposalDateToEdit');
                var modalSerialNumber = myModalEl.querySelector('#serialNumberToEdit');
                var modalCost = myModalEl.querySelector('#costToEdit');
                var modalAllocatedTo = myModalEl.querySelector('#allocatedToToEdit');
                var modalDepreciationTimeframe = myModalEl.querySelector('#depreciationTimeframeToEdit');
                var modalDepreciationPercentage = myModalEl.querySelector('#depreciationPercentageToEdit');
                var modalPurchaseDate = myModalEl.querySelector('#purchaseDateToEdit');
                var modalLocation = myModalEl.querySelector('#locationToEdit');
                var modalNotes = myModalEl.querySelector('#notesToEdit');

                modalAssetId.value = assetId
                modalAssetNo.value = assetNo.startsWith("FE") ? assetNo.substring(2) : assetNo;
                modalDepartment.value = department;
                modalAssetName.value = assetName;
                modalStatus.value = status;
                modalDisposalDate.value = disposalDate;
                modalSerialNumber.value = serialNumber;
                modalCost.value = cost;
                modalPurchaseDate.value = purchaseDate;
                modalLocation.value = location;
                modalNotes.value = notes;
                modalAllocatedTo.value = allocatedTo;

                if (depreciationTimeframe) {
                    modalDepreciationTimeframe.value = depreciationTimeframe;
                }

                if (depreciationPercentage) {
                    modalDepreciationPercentage.value = depreciationPercentage;
                }


                if (accountsAsset === "1") {
                    document.getElementById("accountsAssetToEditYes").checked = true;
                } else if (accountsAsset === "0") {
                    document.getElementById("accountsAssetToEditNo").checked = true;
                }

                if (whsAsset === "1") {
                    document.getElementById("whsAssetToEditYes").checked = true;
                } else if (whsAsset === "0") {
                    document.getElementById("whsAssetToEditNo").checked = true;
                }

                if (ictAsset === "1") {
                    document.getElementById("ictAssetToEditYes").checked = true;
                } else if (ictAsset === "0") {
                    document.getElementById("ictAssetToEditNo").checked = true;
                }

                const statusSelect = document.getElementById("statusToEdit");
                const disposalDateToEditInput = document.querySelector("#disposalDateToEditInput");
                const disposalDateToEdit = document.querySelector("#disposalDateToEdit");

                function updateDisposalDateToEditFields() {
                    const selectedOptionText = statusSelect.options[statusSelect.selectedIndex].text;

                    if (selectedOptionText === "Disposed") {
                        disposalDateToEdit.required = true;
                        disposalDateToEditInput.classList.remove("d-none");
                        disposalDateToEditInput.classList.add("d-block");
                    } else {
                        disposalDateToEdit.required = false;
                        disposalDateToEdit.value = "";
                        disposalDateToEditInput.classList.add("d-none");
                        disposalDateToEditInput.classList.remove("d-block");
                    }

                    // Attach change event listener
                    statusSelect.addEventListener('change', updateDisposalDateToEditFields);

                    console.log("triggered");
                }
                // Initialize fields based on the currently selected option
                updateDisposalDateToEditFields();
            });
        })
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal

                var assetId = button.getAttribute('data-asset-id');
                var assetNo = button.getAttribute('data-asset-no');

                // Update the modal's content with the extracted info
                var modalAssetId = myModalEl.querySelector('#assetIdToDelete');
                var modalAssetNo = myModalEl.querySelector('#assetNoToDelete');

                modalAssetId.value = assetId;
                modalAssetNo.textContent = assetNo;
            });
        })
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
</body>

</html>