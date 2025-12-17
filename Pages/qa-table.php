<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");

$folder_name = "Quality Assurances";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrieve session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'qa_document';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET["recordsPerPage"]) ? intval($_GET["recordsPerPage"]) : 30; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; //Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Get status filter
$statusFilter = isset($_GET['statusFilter']) ? $conn->real_escape_string($_GET['statusFilter']) : '';

// Get revision status filter
// $revisionStatusFilter = isset($_GET['revisionStatusFilter']) ? $conn->real_escape_string($_GET['revisionStatusFilter']) : '';

// Get department filter
// $departmentFilter = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';

// Build base SQL query with role-based filtering
$whereClause = "(qa_document LIKE '%$searchTerm%' OR
    document_name LIKE '%$searchTerm%' OR
    document_description LIKE '%$searchTerm%' OR
    department LIKE '%$searchTerm%')";

// Arrays to hold selected filter values
$selected_departments = [];
$selected_revision_status = [];
$selected_types = [];
$selected_revision_age = [];

$filterApplied = false;

if (isset($_GET['apply_filters'])) {
    if (isset($_GET['department']) && is_array($_GET['department'])) {
        // Use the selected departments from the form
        $selected_departments = $_GET['department'];
        $departments_placeholders = "'" . implode("','", $selected_departments) . "'";
        $whereClause .= " AND department IN ($departments_placeholders)";
        $filterApplied = true;
    }

    if (isset($_GET['revisionStatus']) && is_array($_GET['revisionStatus'])) {
        $selected_revision_status = $_GET['revisionStatus'];

        // Build SQL condition based on last_updated
        $revisionConditions = [];
        foreach ($selected_revision_status as $status) {
            if ($status === "Current") {
                $revisionConditions[] = "DATEDIFF(CURDATE(), last_updated) <= 300";
            } else if ($status === "Urgent Revision Required") {
                $revisionConditions[] = "DATEDIFF(CURDATE(), last_updated) > 300";
            }
        }
        if (!empty($revisionConditions)) {
            $whereClause .= " AND (" . implode(" OR ", $revisionConditions) . ")";
        }

        $filterApplied = true;
    }

    if (isset($_GET['type']) && is_array($_GET['type'])) {
        // Use the selected revision status from the form
        $selected_type = $_GET['type'];
        $type_placeholders = "'" . implode("','", $selected_type) . "'";
        $whereClause .= " AND `type` IN ($type_placeholders)";
        $filterApplied = true;
    }

    if (isset($_GET['status']) && is_array($_GET['status'])) {
        // Use the selected revision status from the form
        $selected_status = $_GET['status'];
        $status_placeholders = "'" . implode("','", $selected_status) . "'";
        $whereClause .= " AND `status` IN ($status_placeholders)";
        $filterApplied = true;
    }

    if (isset($_GET['iso9001']) && is_array($_GET['iso9001'])) {
        // Use the selected revision iso9001 from the form
        $selected_iso9001 = $_GET['iso9001'];
        $iso9001_placeholders = "'" . implode("','", $selected_iso9001) . "'";
        $whereClause .= " AND `iso_9001` IN ($iso9001_placeholders)";
        $filterApplied = true;
    }
}

if ($role !== "full control") {
    $whereClause .= " AND status = 'Approved'";
}

// Add filters for status and revision status
// $whereClause .= " AND (status = '$statusFilter' OR '$statusFilter' = '')";
// $whereClause .= " AND (revision_status = '$revisionStatusFilter' OR '$revisionStatusFilter' = '')";
// $whereClause .= " AND (department = '$departmentFilter' OR '$departmentFilter' = '')";

// SQL Query to retrieve QA details with LIMIT for pagination
$qa_sql = "SELECT * FROM quality_assurance WHERE $whereClause
    ORDER BY $sort $order 
    LIMIT $offset, $records_per_page";
$qa_result = $conn->query($qa_sql);

// Fetch results
$qaDocuments = [];
if ($qa_result->num_rows > 0) {
    while ($row = $qa_result->fetch_assoc()) {
        $qaDocuments[] = $row;
    }
} else {
    $qaDocuments = [];
}

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM quality_assurance WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get all URL parameters from $_GET
$urlParams = $_GET;


// ========================= D E L E T E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["qaIdToDelete"])) {
    $qaIdToDelete = $_POST["qaIdToDelete"];

    $delete_document_sql = "DELETE FROM quality_assurance WHERE qa_id = ?";
    $delete_document_result = $conn->prepare($delete_document_sql);
    $delete_document_result->bind_param("i", $qaIdToDelete);

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

// ========================= E D I T  S T A T U S ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["statusCellToEdit"])) {
    $qaIdToEdit = $_POST["qaIdToEditStatusCell"];
    $statusToEdit = $_POST["statusCellToEdit"];

    $update_status_sql = "UPDATE quality_assurance SET status = ? WHERE qa_id = ?";
    $update_status_result = $conn->prepare($update_status_sql);
    $update_status_result->bind_param("si", $statusToEdit, $qaIdToEdit);

    if ($update_status_result->execute()) {
        // Redirect to the same URL to refresh data
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating status: " . $update_status_result->error;
    }
}

// ========================= E D I T  R E V I S I O N  S T A T U S =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revisionStatusCellToEdit"])) {
    $qaIdToEdit = $_POST["qaIdToEditRevisionStatusCell"];
    $revisionStatusToEdit = $_POST["revisionStatusCellToEdit"];

    $update_revision_status_sql = "UPDATE quality_assurance SET revision_status = ? WHERE qa_id = ?";
    $update_revision_status_result = $conn->prepare($update_revision_status_sql);
    $update_revision_status_result->bind_param("si", $revisionStatusToEdit, $qaIdToEdit);

    if ($update_revision_status_result->execute()) {
        // Redirect to the same URL to refresh data
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating status: " . $update_revision_status_result->error;
    }
}

// ========================= E D I T  R E V I S I O N  N U M B E R  =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revisionNumberCellToEdit"])) {
    $qaIdToEdit = $_POST["qaIdToEditRevisionNumberCell"];
    $revisionNumberToEdit = $_POST["revisionNumberCellToEdit"];

    // Set Timezone to Sydney
    $timezone = new DateTimeZone('Australia/Sydney');
    // Create a DateTime object with the current time in the Sydney timezone
    $todayDateTime = new DateTime('now', $timezone);
    // Format the date as 'Y-m-d'
    $todayDate = $todayDateTime->format('Y-m-d');

    $revisionStatus = "Normal";

    $update_revision_number_sql = "UPDATE quality_assurance SET rev_no = ?, last_updated = ?, revision_status = ? WHERE qa_id = ?";
    $update_revision_status_result = $conn->prepare($update_revision_number_sql);
    $update_revision_status_result->bind_param("sssi", $revisionNumberToEdit, $todayDate, $revisionStatus, $qaIdToEdit);

    if ($update_revision_status_result->execute()) {
        // Redirect to the same URL to refresh data
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating status: " . $update_revision_status_result->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quality Assurances</title>
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

        .canvasjs-chart-credit {
            display: none !important;
        }

        .canvasjs-chart-canvas {
            border-radius: 12px;
        }

        #chartContainer {
            border-radius: 12px;
        }

        .nav-underline .nav-item .nav-link {
            color: black;
        }

        .nav-underline .nav-item .nav-link.active {
            color: #043f9d;
        }

        .nav-underline .nav-item .nav-link:hover {
            background-color: #043f9d;
            color: white;
            border-bottom: 2px solid #54B4D3;
            /* border-radius: 10px; */

        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }

        /* Style for checked state */
        .btn-check:checked+.btn-custom {
            background-color: #043f9d !important;
            border-color: #043f9d !important;
            color: white !important;
        }

        /* Optional: Adjust hover state if needed */
        .btn-custom:hover {
            background-color: #032b6b;
            border-color: #032b6b;
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
            color: white;
        }

        .pagination .page-link {
            color: black
        }

        .table-container {
            width: 100%;
            height: 100%;
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
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">QA Table</li>
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
                <!-- Search & Filter Section -->
                <div class="col-12 col-sm-8 col-lg-5 d-flex justify-content-between align-items-center mb-3 mb-sm-0">
                    <form method="GET" id="searchForm" class="d-flex align-items-center w-100">
                        <div class="input-group me-2 flex-grow-1">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="search" class="form-control" id="searchDocuments" name="search"
                                placeholder="Search Documents" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        <button class="btn" type="submit"
                            style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">Search</button>
                        <button class="btn btn-danger ms-2"><a class="dropdown-item" href="#"
                                onclick="clearURLParameters()">Clear</a></button>
                    </form>
                    <!-- Filter Modal Trigger Button -->
                    <button class="btn text-white ms-2 bg-dark" data-bs-toggle="modal"
                        data-bs-target="#filterDocumentModal">
                        <p class="text-nowrap fw-bold mb-0 pb-0">Filter <i class="fa-solid fa-filter py-1"></i></p>
                    </button>

                    <button class="btn btn-success ms-2" id="printCheckedDocuments">Print</button>
                </div>

                <!-- Add Document Button (Admin only) -->
                <?php if ($role === "full control") { ?>
                    <div
                        class="d-flex justify-content-center justify-content-sm-end align-items-center col-12 col-sm-4 col-lg-7">
                        <a class="btn btn-primary me-2" type="button" data-bs-toggle="modal"
                            data-bs-target="#qaDashboardModal"> <i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                                class="fa-solid fa-plus"></i> Add Document</button>
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php foreach ($urlParams as $key => $value): ?>
            <?php if (!empty($value)): // Only show the span if the value is not empty ?>
                <?php
                // Check if the value is the search filter
                if ($key === 'search') {
                    // Handle the search filter
                    ?>
                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                        <strong><span class="text-warning">Search:</span> <?php echo htmlspecialchars($value); ?></strong>
                        <a href="?<?php
                        // Remove 'Search' from the URL
                        $filteredParams = $_GET;
                        unset($filteredParams['search']);
                        echo http_build_query($filteredParams);
                        ?>" class="text-white ms-1">
                            <i class="fa-solid fa-times"></i>
                        </a>
                    </span>
                <?php } else if ($key === 'status' && is_array($value)) {
                    foreach ($value as $status) {
                        ?>
                            <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                <strong><span class="text-warning">Status:</span> <?php echo htmlspecialchars($status); ?></strong>
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
                } else if ($key === 'department' && is_array($value)) {
                    foreach ($value as $department) {
                        ?>
                                <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                    <strong><span class="text-warning">Department:</span> <?php echo htmlspecialchars($department); ?></strong>
                                    <a href="?<?php
                                    // Remove this specific department filter from the URL
                                    $filteredParams = $_GET;
                                    $filteredParams['department'] = array_diff($filteredParams['department'], [$department]);
                                    echo http_build_query($filteredParams);
                                    ?>" class="text-white ms-1">
                                        <i class="fa-solid fa-times"></i>
                                    </a>
                                </span>
                        <?php
                    }
                } else if ($key === 'revisionStatus' && is_array($value)) {
                    foreach ($value as $revisionStatus) {
                        ?>
                                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                        <strong><span class="text-warning">Revision Status:</span>
                                <?php echo htmlspecialchars($revisionStatus); ?></strong>
                                        <a href="?<?php
                                        // Remove this specific revisionStatus filter from the URL
                                        $filteredParams = $_GET;
                                        $filteredParams['revisionStatus'] = array_diff($filteredParams['revisionStatus'], [$revisionStatus]);
                                        echo http_build_query($filteredParams);
                                        ?>" class="text-white ms-1">
                                            <i class="fa-solid fa-times"></i>
                                        </a>
                                    </span>
                        <?php
                    }
                } else if ($key === 'type' && is_array($value)) {
                    foreach ($value as $type) {
                        ?>
                                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                            <strong><span class="text-warning">Type:</span> <?php echo htmlspecialchars($type); ?></strong>
                                            <a href="?<?php
                                            // Remove this specific type filter from the URL
                                            $filteredParams = $_GET;
                                            $filteredParams['type'] = array_diff($filteredParams['type'], [$type]);
                                            echo http_build_query($filteredParams);
                                            ?>" class="text-white ms-1">
                                                <i class="fa-solid fa-times"></i>
                                            </a>
                                        </span>
                        <?php
                    }
                } else if ($key === 'iso9001' && is_array($value)) {
                    foreach ($value as $iso9001) {
                        ?>
                                            <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                <strong><span class="text-warning">ISO9001:</span> <?php echo ($iso9001 == '1') ? 'Yes' : 'No'; ?></strong>
                                                <a href="?<?php
                                                // Remove this specific iso9001 filter from the URL
                                                $filteredParams = $_GET;
                                                $filteredParams['iso9001'] = array_diff($filteredParams['iso9001'], [$iso9001]);
                                                echo http_build_query($filteredParams);
                                                ?>" class="text-white ms-1">
                                                    <i class="fa-solid fa-times"></i>
                                                </a>
                                            </span>
                        <?php
                    }
                }
                ?>
            <?php endif; ?>
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

        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <?php if ($role === "full control") { ?>
                            <th></th>
                        <?php } ?>
                        <th class="py-4 align-middle text-center qaDocument" style="min-width:200px">
                            <a onclick="updateSort('qa_document', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                QA Document <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle documentName" style="min-width:200px">
                            <a onclick="updateSort('document_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                Document Name<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle documentDescription" style="min-width:400px">
                            <a onclick="updateSort('document_description', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                Document Description <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <?php if ($role === "full control") { ?>
                            <th class="py-4 align-middle text-center revNo" style="min-width:100px">
                                <a onclick="updateSort('rev_no','<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor: pointer;">
                                    Rev No. <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle text-center revisionStatus">
                                <!-- <div class="dropdown">
                                    <a class="text-decoration-none text-white" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Revision Status<i class="fa-solid fa-sort fa-md ms-2"></i>
                                    </a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item"
                                            onclick="updateSort('revision_status','<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                            class="text-decoration-none text-white" style="cursor:pointer">
                                            <?php if ($order === 'asc') { ?>
                                                <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort Z-A
                                            <?php } else if ($order === 'desc') { ?>
                                                    <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort A-Z
                                            <?php } ?>
                                        </a>
                                        <a class="dropdown-item" data-bs-toggle="modal"
                                            data-bs-target="#filterRevisionStatusModal">
                                            <i class="fa-solid fa-filter me-2 signature-color"></i>Sort By Revision Status
                                        </a>
                                    </div>
                                </div> -->
                                <a onclick="updateSort('last_updated', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Revision Status <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <!-- <th class="py-4 align-middle text-center wipDocLink" style="min-width:200px">
                                <a onclick="updateSort('wip_doc_link', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor: pointer;">
                                    WIP Doc Link <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th> -->
                            </td>
                        <?php } ?>
                        <th class="py-4 align-middle text-center department" style="min-width:200px">
                            <a onclick="updateSort('department', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                Department<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center type">
                            <a onclick="updateSort('Type', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                Type<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>

                        <?php if ($role === "full control") { ?>
                            <th class="py-4 align-middle text-center owner">
                                <a onclick="updateSort('Owner', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Owner<i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 text-center align-middle status">
                                <!-- <div class="dropdown">
                                    <a class="text-decoration-none text-white" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Status<i class="fa-solid fa-sort fa-md ms-2"></i>
                                    </a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item"
                                            onclick="updateSort('Status','<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                            class="text-decoration-none text-white" style="cursor:pointer">
                                            <?php if ($order === 'asc') { ?>
                                                <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort Z-A
                                            <?php } else if ($order === 'desc') { ?>
                                                    <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort A-Z
                                            <?php } ?>
                                        </a>
                                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#filterStatusModal">
                                            <i class="fa-solid fa-filter me-2 signature-color"></i>Sort By Status
                                        </a>
                                    </div>
                                </div> -->
                                <a onclick="updateSort('status', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Status <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle text-center approvedBy" style="min-width:200px">
                                <a onclick="updateSort('approved_by', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Approved By<i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle text-center lastUpdated" style="min-width:200px">
                                <a onclick="updateSort('last_updated', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Last Updated <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle text-center iso9001" style="min-width:120px">
                                <a onclick="updateSort('iso_9001', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    ISO 9001 <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($qaDocuments)) { ?>
                        <?php foreach ($qaDocuments as $row) { ?>
                            <?php if ($role !== "full control" && $row['status'] !== "Approved") {
                                continue; // Skip this iteration if not full control and status is not "Approved"
                            } ?>
                            
                            <tr class="document-row">
                                <?php if ($role === "full control") { ?>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#editDocumentModal"
                                                data-qa-id="<?= $row["qa_id"] ?>" data-qa-document="<?= $row["qa_document"] ?>"
                                                data-document-name="<?= $row["document_name"] ?>"
                                                data-document-description="<?= $row["document_description"] ?>"
                                                data-rev-no="<?= $row["rev_no"] ?>" data-department="<?= $row["department"] ?>"
                                                data-type="<?= $row["type"] ?>" data-owner="<?= $row["owner"] ?>"
                                                data-status="<?= $row["status"] ?>" data-approved-by="<?= $row["approved_by"] ?>"
                                                data-last-updated="<?= $row["last_updated"] ?>"
                                                data-revision-status="<?= $row["revision_status"] ?>"
                                                data-iso-9001="<?= $row["iso_9001"] ?>">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>

                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                data-qa-id="<?= $row["qa_id"] ?>" data-qa-document="<?= $row["qa_document"] ?>"><i
                                                    class="fa-regular fa-trash-can text-danger"></i></button>

                                            <input class="form-check-input mb-1" type="checkbox" value="" id="flexCheckDefault">
                                        </div>
                                    </td>
                                <?php } ?>
                                <td class="py-2 align-middle text-center document-title qaDocument">
                                    <!-- <form class="document-form" method="POST">
                                        <input type="hidden" value=<?= $row['qa_document'] ?> name="qa_document">
                                        <a href="download.php?file=<?= urlencode($row['qa_document']) ?>.pdf" target="_blank"
                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                            <?= htmlspecialchars($row["qa_document"], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </form> -->
                                    <form class="document-form" method="POST">
                                        <input type="hidden" value=<?= $row['qa_document'] ?> name="qa_document">
                                        <a href="pdf.php?file=<?= urlencode($row['qa_document']) ?>.pdf" target="_blank"
                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                            <?= htmlspecialchars($row["qa_document"], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </form>
                                </td>
                                <td class="py-2 align-middle document-name documentName">
                                    <?= $row["document_name"] ?>
                                </td>
                                <td class="py-2 align-middle documentDescription">
                                    <?= $row["document_description"] ?>
                                </td>
                                <?php if ($role === "full control") { ?>
                                    <td class="py-2 align-middle text-center revNo" ondblclick="editRevNo(this)">
                                        <form method="POST" class="edit-revision-number-form" style="display:none">
                                            <div class="d-flex align-items-center">
                                                <input type="hidden" name="qaIdToEditRevisionNumberCell"
                                                    value="<?= $row["qa_id"] ?>">
                                                <select name="revisionNumberCellToEdit" class="form-select" style="min-width: 80px;"
                                                    onchange="this.form.submit()">
                                                    <?php
                                                    for ($i = 1; $i <= 99; $i++) {
                                                        // Format the number with leading zeros (e.g., 0 -> R01, 9 -> R09, 10 -> R10)
                                                        $rev = "R" . str_pad($i, 2, "0", STR_PAD_LEFT);
                                                        echo "<option value=\"$rev\"" . ($row['rev_no'] === $rev ? ' selected' : '') . ">$rev</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <a class="text-danger mx-2 text-decoration-none" style="cursor:pointer"
                                                    onclick="cancelEditRevisionNumber(this)">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </div>
                                                </a>
                                            </div>
                                        </form>
                                        <span><?= $row["rev_no"] ?></span>
                                    </td>
                                    <!-- <td class="py-2 align-middle text-center revisionStatus" ondblclick="editRevisionStatus(this)">
                                        <div>
                                            <form method="POST" class="edit-revision-status-form d-none">
                                                <div class="d-flex align-items-center">
                                                    <input type="hidden" name="qaIdToEditRevisionStatusCell"
                                                        value="<?= $row['qa_id'] ?>">
                                                    <select name="revisionStatusCellToEdit" class="form-select"
                                                        onchange="this.form.submit()" style="min-width:180px">
                                                        <option value="Normal" <?= $row['revision_status'] === 'Normal' ? 'selected' : '' ?>>Normal</option>
                                                        <option value="Revision Required" <?= $row['revision_status'] === 'Revision Required' ? 'selected' : '' ?>>Revision Required</option>
                                                        <option value="Urgent Revision Required"
                                                            <?= $row['revision_status'] === 'Urgent Revision Required' ? 'selected' : '' ?>>Urgent Revision Required</option>
                                                    </select>
                                                    <a class="text-danger mx-2 text-decoration-none" style="cursor: pointer"
                                                        onclick="cancelEditRevisionStatus(this)">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fa-solid fa-xmark"></i>
                                                        </div>
                                                    </a>
                                                </div>
                                            </form>
                                            <span class="badge 
                                    <?php if ($row["revision_status"] === "Normal") {
                                        echo "bg-success";
                                    } else if ($row["revision_status"] === "Revision Required") {
                                        echo "bg-warning";
                                    } else if ($row["revision_status"] === "Urgent Revision Required") {
                                        echo "bg-danger";
                                    }
                                    ?> rounded-pill w-100">
                                                <?= $row["revision_status"] ?></span>
                                        </div>
                                    </td> -->
                                    <td class="py-2 align-middle text-center">
                                        <?php
                                        $lastUpdated = strtotime($row["last_updated"]);
                                        $today = time();
                                        $daysDiff = ($today - $lastUpdated) / (60 * 60 * 24);
                                        ?>

                                        <?php if ($daysDiff > 300): ?>
                                            <span class="badge bg-danger rounded-pill w-100 fw-bold">Urgent Revision Required</span>
                                        <?php else: ?>
                                            <span class="badge bg-success rounded-pill w-100 fw-bold">Current</span>
                                        <?php endif; ?>
                                    </td>



                                <?php } ?>

                                <td class="py-2 align-middle text-center department">
                                    <?= $row["department"] ?>
                                </td>
                                <td class="py-2 align-middle text-center type">
                                    <?= $row["type"] ?>
                                </td>
                                <?php if ($role === "full control") { ?>
                                    <td class="py-2 align-middle text-center owner" style="min-width:100px">
                                        <?= $row["owner"] ?>
                                    </td>
                                    <td class="py-2 align-middle text-center status" ondblclick="editStatus(this)">
                                        <form method="POST" class="edit-status-form d-none">
                                            <div class="d-flex align-items-center">
                                                <input type="hidden" name="qaIdToEditStatusCell"
                                                    value="<?= htmlspecialchars($row['qa_id']) ?>">
                                                <select name="statusCellToEdit" class="form-select" style="min-width: 150px"
                                                    onchange="this.form.submit()">
                                                    <option value="Approved" <?= $row['status'] === 'Approved' ? 'selected' : '' ?>>
                                                        Approved</option>
                                                    <option value="Need to review" <?= $row['status'] === 'Need to review' ? 'selected' : '' ?>>Need to review</option>
                                                    <option value="In progress" <?= $row['status'] === 'In progress' ? 'selected' : '' ?>>In progress</option>
                                                    <option value="To be created" <?= $row['status'] === 'To be created' ? 'selected' : '' ?>>To be created</option>
                                                    <option value="Pending approval" <?= $row['status'] === 'Pending approval' ? 'selected' : '' ?>>Pending approval</option>
                                                    <option value="Not approved yet" <?= $row['status'] === 'Not approved yet' ? 'selected' : '' ?>>Not approved yet</option>
                                                    <option value="Revision/Creation requested"
                                                        <?= $row['status'] === 'Revision/Creation requested' ? 'selected' : '' ?>>
                                                        Revision/Creation Requested</option>
                                                    <option value="N/A" <?= $row['status'] === 'N/A' ? 'selected' : '' ?>>
                                                        N/A</option>
                                                </select>
                                                <a class="text-danger mx-2 text-decoration-none" style="cursor:pointer"
                                                    onclick="cancelEditStatus(this)">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </div>
                                                </a>
                                            </div>
                                        </form>
                                        <?php
                                        $status = htmlspecialchars($row['status']);
                                        $badgeClasses = [
                                            'Approved' => 'bg-success',
                                            'Need to review' => 'bg-light text-dark border',
                                            'In progress' => 'bg-warning',
                                            'To be created' => 'bg-secondary',
                                            'Pending approval' => 'bg-primary',
                                            'Not approved yet' => 'bg-info',
                                            'Revision/Creation requested' => 'bg-danger'
                                        ];
                                        $badgeClass = isset($badgeClasses[$status]) ? $badgeClasses[$status] : '';
                                        ?>

                                        <?php if ($status === 'N/A'): ?>
                                            <span><?= $status ?></span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill w-100 text-center <?= $badgeClass ?>">
                                                <?= $status ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="py-2 align-middle text-center approvedBy">
                                        <?= $row["approved_by"] ?>
                                    </td>
                                    <td class="py-2 align-middle text-center lastUpdated">
                                        <?= date("j F Y", strtotime($row["last_updated"])) ?>
                                    </td>
                                    <td class="py-2 align-middle text-center iso9001">
                                        <?php if ($row["iso_9001"] == 1) { ?>
                                            Yes
                                        <?php } else if ($row["iso_9001"] == 0) { ?>
                                                No
                                        <?php } else { ?>
                                                N/A
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="14" class="text-center">No records found</td>
                        </tr>
                    <?php } ?>
            </table>
            <div class="d-flex justify-content-end mt-3 pe-2">
                <div class="d-flex align-items-center me-2">
                    <p>Rows Per Page: </p>
                </div>

                <form method="GET" class="me-2">
                    <select class="form-select" id="recordsPerPage" name="recordsPerPage"
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
                                <a class="page-link" onclick="updatePage(<?php echo $page - 1; ?>); return false;"
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

                        // $page_range = 3; // Number of pages to display at a time
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
                                <a class="page-link" onclick="updatePage(<?php echo $total_pages; ?>); return false;"
                                    aria-label="last" style="cursor: pointer">
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
                    <h5 class="modal-title">Add QA Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/addQADocumentForm.php") ?>
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
                    <p>Are you sure you want to delete <span class="fw-bold" id="qaDocumentToDelete"></span> document?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="qaIdToDelete" id="qaIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ================== Edit Document Modal ================== -->
    <div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit QA Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/EditQADocumentForm.php") ?>
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
                                id="qaDocumentCheckBox" data-column="qaDocument">
                            <label class="form-check-label" for="qaDocumentCheckBox">
                                QA Document
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="documentNameCheckBox" data-column="documentName">
                            <label class="form-check-label" for="documentNameCheckBox">
                                Document Name
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="documentDescriptionCheckBox" data-column="documentDescription">
                            <label class="form-check-label" for="documentDescriptionCheckBox">
                                Document Description
                            </label>
                        </div>
                        <?php if ($role === "full control") { ?>
                            <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value=""
                                    id="revNoCheckBox" data-column="revNo">
                                <label class="form-check-label" for="revNoCheckBox">
                                    Rev No.
                                </label>
                            </div>
                            <!-- <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value="" id="wipDocumentCheckBox"
                                    data-column="wipDocLink">
                                <label class="form-check-label" for="wipDocumentCheckBox">
                                    WIP Doc Link
                                </label>
                            </div> -->
                        <?php } ?>

                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="departmentCheckBox" data-column="department">
                            <label class="form-check-label" for="departmentCheckBox">
                                Department
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" value=""
                                id="typeCheckBox" data-column="type">
                            <label class="form-check-label" for="typeCheckBox">
                                Type
                            </label>
                        </div>
                        <?php if ($role === "full control") { ?>
                            <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value=""
                                    id="ownerCheckBox" data-column="owner">
                                <label class="form-check-label" for="ownerCheckBox">
                                    Owner
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value=""
                                    id="statusCheckBox" data-column="status">
                                <label class="form-check-label" for="statusCheckBox">
                                    Status
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value=""
                                    id="approvedByCheckBox" data-column="approvedBy">
                                <label class="form-check-label" for="approvedByCheckBox">
                                    Approved By
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value=""
                                    id="lastUpdatedCheckBox" data-column="lastUpdated">
                                <label class="form-check-label" for="lastUpdatedCheckBox">
                                    Last Updated
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value=""
                                    id="revisionStatusCheckBox" data-column="revisionStatus">
                                <label class="form-check-label" for="revisionStatusCheckBox">
                                    Revision Status
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input column-check-input" type="checkbox" value=""
                                    id="iso9001CheckBox" data-column="iso9001">
                                <label class="form-check-label" for="iso9001CheckBox">
                                    ISO 9001
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="d-flex justify-content-end" style="cursor:pointer">
                        <button onclick="resetColumnFilter()" class="btn btn-sm btn-danger me-1"> Reset Filter</button>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Done</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- ================== Filter by Status Modal ================== -->
    <div class="modal fade" id="filterStatusModal" tabindex="-1" aria-labelledby="filterStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterStatusModalLabel">Filter by Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="">
                        <!-- Preserve URL parameters -->
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                        <input type="hidden" name="recordsPerPage" value="<?= htmlspecialchars($records_per_page) ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">

                        <div class="mb-3">
                            <label for="statusFilter" class="form-label">Select Status</label>
                            <select id="statusFilter" name="statusFilter" class="form-select">
                                <option value="">All</option>
                                <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved
                                </option>
                                <option value="Need to review" <?= $statusFilter === 'Need to review' ? 'selected' : '' ?>>
                                    Need to review</option>
                                <option value="In progress" <?= $statusFilter === 'In progress' ? 'selected' : '' ?>>In
                                    progress</option>
                                <option value="To be created" <?= $statusFilter === 'To be created' ? 'selected' : '' ?>>To
                                    be created</option>
                                <option value="Pending approval" <?= $statusFilter === 'Pending approval' ? 'selected' : '' ?>>Pending approval</option>
                                <option value="Not approved yet" <?= $statusFilter === 'Not approved yet' ? 'selected' : '' ?>>Not approved yet</option>
                                <option value="Revision/Creation requested" <?= $statusFilter === 'Revision/Creation requested' ? 'selected' : '' ?>>Revision/Creation requested</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn signature-btn">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ================== Filter by Revision Status Modal ==================  -->
    <div class="modal fade" id="filterRevisionStatusModal" tabindex="-1"
        aria-labelledby="filterRevisionStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterStatusModalLabel">Filter by Revision Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                        <input type="hidden" name="recordsPerPage" value="<?= htmlspecialchars($records_per_page) ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">

                        <div class="mb-3">
                            <label for="revisionStatusFilter" class="form-label">Select Revision Status</label>
                            <select id="revisionStatusFilter" name="revisionStatusFilter" class="form-select">
                                <option value="">All</option>
                                <option value="Normal" <?= $revisionStatusFilter === 'Normal' ? 'selected' : '' ?>>Normal
                                </option>
                                <option value="Revision Required" <?= $revisionStatusFilter === 'Revision Required' ? 'selected' : '' ?>>Revision Required</option>
                                <option value="Urgent Revision Required" <?= $revisionStatusFilter === 'Urgent Revision Required' ? 'selected' : '' ?>>Urgent Revision Required</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn signature-btn">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Filter All Document Modal ==================  -->
    <div class="modal fade" id="filterDocumentModal" tabindex="1" aria-labelledby="filterDocumentModal"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter QA Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET">
                        <div class="row">
                            <div class="col-12 <?php echo ($role === 'full control') ? 'col-lg-3' : 'col-lg-6' ?>">
                                <h5 class="signature-color fw-bold">Department</h5>
                                <?php
                                // Hardcoded list of departments
                                $departments = ['Quality Assurance', 'Management', 'Accounts', 'Estimating', 'Projects', 'Engineering', 'Electrical', 'Sheet Metal', 'Operations Support', 'Human Resources', 'Research & Development', 'Work, Health and Safety', 'Quality Control', 'Special Projects', 'Company Compliance']; // Customize the department list here
                                
                                // Sort the departments alphabetically
                                sort($departments);

                                // Get the selected departments from the GET request
                                $selected_departments = isset($_GET['department']) ? $_GET['department'] : [];

                                // Loop through the hardcoded departments
                                foreach ($departments as $department) {
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="department_<?php echo strtolower($department); ?>" name="department[]"
                                            value="<?php echo $department; ?>" <?php echo in_array($department, $selected_departments) ? 'checked' : ''; ?> />
                                        <label
                                            for="department_<?php echo strtolower($department); ?>"><?php echo $department; ?></label>
                                    </p>
                                    <?php
                                }
                                ?>
                            </div>

                            <?php if ($role === "full control") { ?>
                                <div
                                    class="col-12 <?php echo ($role === 'full control') ? 'col-lg-3' : 'col-lg-4' ?> mt-3 mt-md-0">
                                    <h5 class="signature-color fw-bold">Revision Status</h5>
                                    <?php
$revisionStatusFilters = ['Current', 'Urgent Revision Required'];
$selected_revision_status = isset($_GET['revisionStatus']) ? (array) $_GET['revisionStatus'] : [];

foreach ($revisionStatusFilters as $revisionStatusFilter) {
    ?>
    <p class="mb-0 pb-0">
        <input type="checkbox" class="form-check-input"
               id="<?php echo strtolower(str_replace(' ', '', $revisionStatusFilter)); ?>"
               name="revisionStatus[]" value="<?php echo $revisionStatusFilter ?>" 
               <?php echo in_array($revisionStatusFilter, $selected_revision_status) ? 'checked' : ''; ?>>
        <label for="<?php echo strtolower(str_replace(' ', '', $revisionStatusFilter)); ?>">
            <?php echo $revisionStatusFilter ?>
        </label>
    </p>
    <?php
}
?>

                                    <h5 class="signature-color fw-bold mt-3">ISO9001</h5>
                                    <?php
                                    $iso9001Filters = ['1', '0'];
                                    $selected_iso9001 = isset($_GET['iso9001']) ? (array) $_GET['iso9001'] : [];
                                    foreach ($iso9001Filters as $iso9001Filter) {
                                        // Set the label to "Yes" or "No" based on the value
                                        $label = ($iso9001Filter == '1') ? 'Yes' : 'No';
                                        ?>
                                        <p class="mb-0 pb-0">
                                            <input type="checkbox" class="form-check-input"
                                                id="<?php echo strtolower(str_replace(' ', '', $iso9001Filter)); ?>"
                                                name="iso9001[]" value="<?php echo $iso9001Filter ?>" <?php echo in_array($iso9001Filter, $selected_iso9001) ? 'checked' : ''; ?>>
                                            <label for="<?php echo strtolower(str_replace(' ', '', $iso9001Filter)); ?>">
                                                <?php echo $label; ?>
                                            </label>
                                        </p>
                                        <?php
                                    }
                                    ?>
                                </div>
                            <?php } ?>

                            <div
                                class="col-12 <?php echo ($role === 'full control') ? 'col-lg-3' : 'col-lg-6' ?> mt-3 mt-md-0">
                                <h5 class="signature-color fw-bold">Type</h5>
                                <?php
                                $typeFilters = ['Additional Duties', 'CAPA', 'Employee Record', 'External Documents', 'Form', 'Internal Documents', 'Job Description', 'Manuals', 'Manufacturing Process', 'Policy', 'Process/Procedure', 'Quiz', 'Risk Assessment', 'Work Instruction'];

                                $selected_types = isset(($_GET['type'])) ? (array) $_GET['type'] : [];
                                foreach ($typeFilters as $typeFilter) {
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="<?php echo strtolower(str_replace(' ', '', $typeFilter)); ?>" name="type[]"
                                            value="<?php echo $typeFilter ?>" <?php echo in_array($typeFilter, $selected_types) ? 'checked' : ''; ?>>
                                        <label for="<?php echo strtolower(str_replace(' ', '', $typeFilter)); ?>">
                                            <?php echo $typeFilter ?>
                                        </label>
                                    </p>
                                    <?php
                                }
                                ?>
                            </div>

                            <?php if ($role === "full control") { ?>
                                <div
                                    class="col-12 <?php echo ($role === 'full control') ? 'col-lg-3' : 'col-lg-4' ?> mt-3 mt-md-0">
                                    <h5 class="signature-color fw-bold">Status</h5>
                                    <?php
                                    $statusFilters = ['Approved', 'Need to review', 'In progress', 'To be created', 'Pending approval', 'Not approved yet', 'Revision/Creation requested', 'N/A'];

                                    $selected_status = isset(($_GET['status'])) ? (array) $_GET['status'] : [];
                                    foreach ($statusFilters as $statusFilter) {
                                        ?>
                                        <p class="mb-0 pb-0">
                                            <input type="checkbox" class="form-check-input"
                                                id="<?php echo strtolower(str_replace(' ', '', $statusFilter)); ?>"
                                                name="status[]" value="<?php echo $statusFilter ?>" <?php echo in_array($statusFilter, $selected_status) ? 'checked' : ''; ?>>
                                            <label for="<?php echo strtolower(str_replace(' ', '', $statusFilter)); ?>">
                                                <?php echo $statusFilter ?>
                                            </label>
                                        </p>
                                        <?php
                                    }
                                    ?>
                                </div>
                            <?php } ?>

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

    <!-- ================== QA Dashboard Modal ================== -->
    <div class="modal fade" id="qaDashboardModal" tab-index="-1" aria-labelledby="qaDashboardModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaDashboardModalLabel">QA Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body background-color">
                    <?php require_once("../PageContent/qa-index-content.php") ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var qaId = button.getAttribute('data-qa-id'); // Extract info from data-* attributes
                var qaDocument = button.getAttribute('data-qa-document');

                // Update the modal's content with the extracted info
                var modalQaIdToDelete = myModalEl.querySelector('#qaIdToDelete');
                var modalQaDocument = myModalEl.querySelector('#qaDocumentToDelete');
                modalQaIdToDelete.value = qaId;
                modalQaDocument.textContent = qaDocument;
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editDocumentModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var qaId = button.getAttribute('data-qa-id');
                var qaDocument = button.getAttribute('data-qa-document');
                var documentName = button.getAttribute('data-document-name');
                var documentDescription = button.getAttribute('data-document-description');
                var revNo = button.getAttribute('data-rev-no');
                var department = button.getAttribute('data-department');
                var type = button.getAttribute('data-type');
                var owner = button.getAttribute('data-owner');
                var status = button.getAttribute('data-status');
                var approvedBy = button.getAttribute('data-approved-by');
                var lastUpdated = button.getAttribute('data-last-updated');
                // var revisionStatus = button.getAttribute('data-revision-status');
                var iso9001 = button.getAttribute('data-iso-9001');

                // Update the modal's content with the extracted info
                var modalQaId = myModalEl.querySelector('#qaIdToEdit')
                var modalQaDocument = myModalEl.querySelector('#qaDocumentToEdit');
                var modalDocumentName = myModalEl.querySelector('#documentNameToEdit');
                var modalDocumentDescription = myModalEl.querySelector('#documentDescriptionToEdit');
                var modalRevNo = myModalEl.querySelector('#revNoToEdit');
                var modalDepartment = myModalEl.querySelector('#departmentToEdit');
                var modalType = myModalEl.querySelector('#typeToEdit');
                var modalOwner = myModalEl.querySelector('#ownerToEdit');
                var modalStatus = myModalEl.querySelector('#statusToEdit');
                var modalApprovedBy = myModalEl.querySelector('#approvedByToEdit');
                var modalLastUpdated = myModalEl.querySelector('#lastUpdatedToEdit');
                // var modalRevisionStatus = myModalEl.querySelector('#revisionStatusToEdit');
                var iso9001YesEdit = document.getElementById('iso9001YesToEdit');
                var iso9001NoEdit = document.getElementById('iso9001NoToEdit');

                modalQaId.value = qaId;
                modalQaDocument.value = qaDocument;
                modalDocumentName.value = documentName;
                modalDocumentDescription.value = documentDescription;
                modalRevNo.value = revNo;
                modalDepartment.value = department;
                modalType.value = type;
                modalOwner.value = owner;
                modalStatus.value = status;
                modalApprovedBy.value = approvedBy;
                modalLastUpdated.value = lastUpdated;
                // modalRevisionStatus.value = revisionStatus;
                if (iso9001 === "1") {
                    iso9001YesToEdit.checked = true;
                } else {
                    iso9001NoToEdit.checked = true;
                }
            });
        });
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
        function updateSearchQuery(department) {
            const url = new URL(window.location.href);
            url.searchParams.set('search', department);
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
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t);
        })
    </script>
    <script>
        // Add event listeners to all forms with the class 'document-form'
        document.querySelectorAll('.document-form').forEach(form => {
            form.addEventListener('submit', function () {
                // Show the loading indicator
                document.getElementById('loading-indicator').style.display = 'flex';
            });
        });

        // Add event listeners to all forms with the class 'wip_document'
        // document.querySelectorAll('.wip_document').forEach(form => {
        //     form.addEventListener('submit', function () {
        //         // Show the loading indicator
        //         document.getElementById('loading-indicator').style.display = 'flex';
        //     });
        // });
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
    </script>
    <script>
        function editStatus(cell) {
            const form = cell.querySelector('.edit-status-form');
            const span = cell.querySelector('span');

            if (form.classList.contains('d-none')) {
                form.classList.remove('d-none');
                span.classList.add('d-none');
                form.querySelector('select').focus();
            } else {
                form.classList.add('d-none');
                span.classList.remove('d-none');
            }
        }

        function cancelEditStatus(link) {
            const cell = link.closest('td');
            if (!cell) return;

            const form = cell.querySelector('.edit-status-form');
            const span = cell.querySelector('span');

            if (form && span) {
                form.classList.add('d-none');
                span.classList.remove('d-none');
            }
        }

        function editRevisionStatus(cell) {
            // Get the form and span elements
            const form = cell.querySelector('.edit-revision-status-form');
            const span = cell.querySelector('span');

            // Toggle the form visibility
            if (form.classList.contains('d-none')) {
                form.classList.remove('d-none');
                span.classList.add('d-none');
                form.querySelector('select').focus();
            } else {
                form.classList.add('d-none');
                span.classList.remove('d-none');
            }
        }

        function cancelEditRevisionStatus(link) {
            // Find the closest <td> element
            const cell = link.closest('td');
            if (!cell) return;

            // Find the form and span within the <td>
            const form = cell.querySelector('.edit-revision-status-form');
            const span = cell.querySelector('span');

            if (form && span) {
                // Toggle visibility of form and span
                form.classList.add('d-none');
                span.classList.remove('d-none');
            }
        }

        function editRevNo(cell) {
            // Get the form and span elements
            const form = cell.querySelector('.edit-revision-number-form');
            const span = cell.querySelector('span');

            // Toggle the form visibility
            if (form.style.display === 'none') {
                form.style.display = 'block';
                span.style.display = 'none';
                form.querySelector('select').focus();
            } else {
                form.style.display = 'none';
                form.style.display = 'block';
            }
        }

        function cancelEditRevisionNumber(link) {
            //Find the closes <td> element
            const cell = link.closest('td');
            if (!cell) return;

            // Find the form and span within the <td>
            const form = cell.querySelector('.edit-revision-number-form');
            const span = cell.querySelector('span');

            if (form && span) {
                // Toggle visibility of form and span
                form.style.display = 'none';
                span.style.display = 'inline-block';
            }
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
        document.addEventListener("DOMContentLoaded", function () {
            const documentTitle = document.title;

            const topMenuTitle = document.getElementById("top-menu-title");
            topMenuTitle.textContent = documentTitle;

            const topMenuTitleSmall = document.getElementById("top-menu-title-small");
            topMenuTitleSmall.textContent = documentTitle;
        })
    </script>
    <!-- <script>
        function copyDirectoryPath(i) {
            var wipDocLink = i.closest('td').querySelector('input').value;
            var icon = i;

            // Create a temporary input element to copy the content from
            var tempInput = document.createElement('input');
            tempInput.value = wipDocLink;
            document.body.appendChild(tempInput);

            // Select the content of the temporary input
            tempInput.select();
            tempInput.setSelectionRange(0, 99999);  // For mobile devices

            try {
                // Execute the copy command
                document.execCommand('copy');
                console.log('Text copied successfully');

                // Update the icon to show success and hide it
                icon.style.visibility = 'hidden';
                icon.insertAdjacentHTML('afterend', '<small class="text-success m-0 p-0 fw-bold">Copied</small>');

                // After 2 seconds, remove the "Copied" text and show the icon again
                setTimeout(function () {
                    icon.style.visibility = 'visible';
                    icon.nextElementSibling.remove();  // Remove the "Copied" text
                }, 800); // 800 milliseconds = 0.8 seconds
            } catch (err) {
                console.error('Unable to copy text', err);
            }

            // Remove the temporary input from the document
            document.body.removeChild(tempInput);
        }
    </script> -->
    <script>
        document.querySelectorAll('.dropdown-menu .dropdown-department-item').forEach(item => {
            item.addEventListener('click', function (event) {
                event.preventDefault(); // Prevent default anchor click behavior
                let department = this.getAttribute('data-department-filter');
                if (department === "All Departments") {
                    document.getElementById('selectedDepartmentFilter').value = "";
                } else {
                    document.getElementById('selectedDepartmentFilter').value = department;
                }
                this.closest('form').submit(); // Submit the form
            });
        });
    </script>
    <script>
        // Add an event listener to the search input to detect the "Enter" key
        document.getElementById('searchDocuments').addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                // Prevent default form submission (if needed) and manually trigger the form submit
                event.preventDefault();
                document.getElementById('searchForm').submit();
            }
        });
    </script>
    <script>
        // Restore scroll position after page reload
        window.addEventListener('load', function () {
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, scrollPosition);
                sessionStorage.removeItem('scrollPosition'); // Remove after restoring
            }
        });
    </script>
</body>

</html>