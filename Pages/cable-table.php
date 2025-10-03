<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

$folder_name = "Test and Tag";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Set the timezone to Sydney
date_default_timezone_set('Australia/Sydney');

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'cable_no';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 20; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

// Get the test status filter from the URL
$testStatusFilter = isset($_GET['test_status']) ? $_GET['test_status'] : 'all'; // Default to 'all'

// Initialize the WHERE clause with the search term condition
$whereClause = "(cables.cable_no LIKE '%$searchTerm%' OR cable_tags.cable_tag_no LIKE '%$searchTerm%' OR location.location_name LIKE '%$searchTerm%')";

// Add additional filtering based on test status, unless 'all' is selected
// Add additional filtering based on test status, unless 'all' is selected
if ($testStatusFilter != 'all') {
    $currentDate = new DateTime(); // Get the current date

    switch ($testStatusFilter) {
        case 'almost_due':
            // Filter cables that are almost due (due within 30 days)
            $whereClause .= " AND DATE_ADD(cable_tags.test_date, INTERVAL cables.test_frequency MONTH) BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY";
            break;
        case 'already_due':
            // Filter cables that are already due
            $whereClause .= " AND DATE_ADD(cable_tags.test_date, INTERVAL cables.test_frequency MONTH) < CURDATE()";
            break;
        case 'not_tested':
            // Filter cables that have not been tested
            $whereClause .= " AND cable_tags.test_date IS NULL";
            break;
        case 'tested':
            // Filter cables that have been tested
            $whereClause .= " AND cable_tags.test_date IS NOT NULL";
            break;
    }
}

// Final SQL query with the dynamic WHERE clause
$cables_sql = "
    SELECT 
        cables.*, 
        location.location_name,
        MAX(cable_tags.test_date) AS last_date_tested,
        GROUP_CONCAT(cable_tags.cable_tag_no) AS cable_tag_nos
    FROM cables 
    LEFT JOIN cable_tags ON cables.cable_id = cable_tags.cable_id 
    LEFT JOIN location ON cables.location_id = location.location_id
    WHERE $whereClause 
    GROUP BY cables.cable_id ORDER BY $sort $order 
    LIMIT $offset, $records_per_page
";

// Get total number of records based on the filtering
$total_records_sql = " 
    SELECT COUNT(DISTINCT cables.cable_id) AS total
    FROM cables
    LEFT JOIN cable_tags ON cables.cable_id = cable_tags.cable_id 
    LEFT JOIN location ON cables.location_id = location.location_id
    WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Execute the query
$cables_result = $conn->query($cables_sql);



// ========================= D E L E T E  C A B L E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cableIdToDelete"])) {
    $cableIdToDelete = $_POST["cableIdToDelete"];

    $delete_cable_sql = "DELETE FROM cables WHERE cable_id = ?";
    $delete_cable_result = $conn->prepare($delete_cable_sql);
    $delete_cable_result->bind_param("i", $cableIdToDelete);

    if ($delete_cable_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error: " . $delete_cable_result . "<br>" . $conn->error;
    }
    $delete_cable_result->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Test and Tag Table</title>
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
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Test and Tag Table
                    </li>
                </ol>
            </nav> -->
        </div>

        <div class="row mb-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
                <div class="col-8 col-lg-5">
                    <form method="GET" id="searchForm">
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
                            <!-- Dropdown for filtering cable test status -->
                            <div class="dropdown ms-2">
                                <button class="btn btn-outline-dark dropdown-toggle" type="button"
                                    id="testStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    All
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="testStatusDropdown">
                                    <li><a class="dropdown-item status-check" href="#" data-value="all">All</a></li>
                                    <li><a class="dropdown-item status-check" href="#" data-value="almost_due">Almost
                                            Due (30
                                            Days)</a></li>
                                    <li><a class="dropdown-item status-check" href="#" data-value="already_due">Already
                                            Due</a></li>
                                    <li><a class="dropdown-item status-check" href="#" data-value="not_tested">Not
                                            Tested</a></li>
                                    <li><a class="dropdown-item status-check" href="#" data-value="tested">Tested</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
                <div
                    class="d-flex justify-content-center justify-content-sm-end align-items-center col-12 col-sm-4 col-lg-7">
                    <a class="btn btn-primary me-2" type="button" data-bs-toggle="modal"
                        data-bs-target="#cableDashboardModal"> <i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                            class="fa-solid fa-plus"></i> Add Cable</button>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th style="max-width: 50px;"></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('cable_no', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Cable No <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('description', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Description <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('location_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Location <i
                                    class="fa-solid fa-sort famd ms-1"> </i></a></th>
                        <th class=" py-4 align-middle text-center"> <a
                                onclick="updateSort('test_frequency', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Test Frequency <i
                                    class="fa-solid fa-sort fa-md ms-1"> </i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('last_date_tested', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Last Date Tested <i
                                    class="fa-solid fa-sort fa-md ms-1"> </i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('last_date_tested', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Test Due Date <i
                                    class="fa-solid fa-sort fa-md ms-1"> </i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('asset_no', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> FE No. <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('purchase_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Purchase Date <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cables_result->num_rows > 0) { ?>
                        <?php while ($row = $cables_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="align-middle text-center">
                                    <button class="btn text-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteConfirmationModal" data-cable-no="<?= $row['cable_no'] ?>"
                                        data-cable-id="<?= $row['cable_id'] ?>"><i class="fa-regular fa-trash-can"></i></button>
                                    <button class="btn text-signature" data-bs-toggle="modal" data-bs-target="#editCableModal"
                                        data-cable-id="<?= $row['cable_id'] ?>" data-cable-no="<?= $row['cable_no'] ?>"
                                        data-location="<?= $row['location_id'] ?>"
                                        data-test-frequency="<?= $row['test_frequency'] ?>"
                                        data-purchase-date="<?= $row['purchase_date'] ?>"
                                        data-description="<?= $row['description'] ?>" data-asset-no="<?= $row['asset_no'] ?>"><i
                                            class="fa-regular fa-pen-to-square"></i></button>
                                    <button class="btn text-warning" data-bs-toggle="modal" data-bs-target="#cableTestTagModal"
                                        data-cable-id="<?= $row['cable_id'] ?>" data-cable-no="<?= $row['cable_no'] ?>"> <i
                                            class="fa-solid fa-tag"></i></button>
                                    <button class="btn" data-bs-toggle="modal" data-bs-target="#barcodeModal"
                                        data-cable-no="<?= $row['cable_no'] ?>" data-location="<?= $row['location_name'] ?>"
                                        data-test-frequency="<?= $row['test_frequency'] ?>"><i
                                            class="fa-solid fa-barcode"></i></button>
                                </td>
                                <td class="py-3 align-middle text-center"><?= $row['cable_no'] ?></td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['description']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['description']) ? htmlspecialchars($row['description']) : 'N/A' ?>
                                </td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['location_name']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= $row['location_name'] === null ? 'N/A' : $row['location_name'] ?>
                                </td>
                                <td class="py-3 align-middle text-center">
                                    <?= $row['test_frequency'] === '60' ? '5 Years' : $row['test_frequency'] . ' Months' ?>
                                </td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= empty($row['last_date_tested']) ? 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' : '' ?>">
                                    <?php
                                    if ($row['last_date_tested']) {
                                        $lastDateTested = new DateTime($row['last_date_tested']);
                                        echo $lastDateTested->format('d F Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="py-3 align-middle text-center 
                                <?php
                                if (!empty($row['last_date_tested'])) {
                                    $lastDateTested = new DateTime($row['last_date_tested']);
                                    $testFrequency = $row['test_frequency'];

                                    // Add test frequency (in months) to last date tested
                                    $dueDate = $lastDateTested->modify("+$testFrequency months");
                                    $currentDate = new DateTime();

                                    // Calculate the interval between the current date and the due date
                                    $interval = $currentDate->diff($dueDate);

                                    if ($currentDate > $dueDate) {
                                        // Due date has already passed
                                        echo 'bg-danger text-white';
                                    } elseif (($interval->y == 0 && $interval->m == 0) && $interval->invert == 0) {
                                        // Less than a month left (same year and same month)
                                        echo 'bg-danger bg-opacity-25';
                                    }
                                } else {
                                    echo 'bg-secondary text-white'; // Optional for N/A case
                                }
                                ?>"
                                    style="<?= empty($row['last_date_tested']) ? 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' : '' ?>">
                                    <?php
                                    if ($row['last_date_tested']) {
                                        echo $dueDate->format('d F Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>

                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['asset_no']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?php if (isset($row['asset_no'])): ?>
                                        <a href="asset-table.php?search=<?= urlencode($row['asset_no']) ?>">
                                            <?= htmlspecialchars($row['asset_no']) ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>

                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['purchase_date']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?php
                                    if (isset($row['purchase_date']) && $row['purchase_date'] !== NULL) {
                                        // Convert purchase_date to DateTime and format it
                                        $date = new DateTime($row['purchase_date']);
                                        echo htmlspecialchars($date->format('d F Y'));
                                    } else {
                                        echo 'N/A'; // Display N/A if purchase_date is not set
                                    }
                                    ?>
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
                        <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="40" <?php echo $records_per_page == 40 ? 'selected' : ''; ?>>40</option>
                        <option value="80" <?php echo $records_per_page == 80 ? 'selected' : ''; ?>>80</option>
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

    <!-- ================== Cable Test Tag Modal ================== -->
    <div class="modal fade" id="cableTestTagModal" tabindex="-1" aria-labelledby="cableTestTagModal" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cable Test Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <?php require("../PageContent/cable-tag-table.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Barcode Modal ================== -->
    <div class="modal fade" id="barcodeModal" tab-index="-1" aria-labelledby="barcodeModal" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cable Tag Barcode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body signature-bg-color">
                    <h1 id="cableNo" class="d-none"></h1>

                    <div class="d-flex justify-content-center">
                        <div class="d-flex justify-content-center col-md-6">
                            <div id="cableTag" class="m-2 p-0 bg-white">
                                <div class="d-flex flex-column justify-content-center align-items-center"
                                    style="max-width: 250px">
                                    <small id="cableLocation" class="fw-bold mb-2"
                                        style="transform: rotate(180deg); max-width: 210px"></small>
                                    <div style="transform: rotate(180deg); transform-origin: center;">
                                        <svg id="barcode1"></svg>
                                    </div>
                                    <h5 class="fw-bold mb-2" style="transform: rotate(180deg)">CABLE TAG</h5>
                                    <p class="text-center fw-bold" style="width:200px">
                                        -----------------------------------</p>

                                    <h5 class="fw-bold mt-2 mb-0 pb-0">CABLE TAG</h5>
                                    <svg id="barcode2"></svg>
                                    <small
                                        style="font-family: 'IBM Plex Mono', monospace; font-size: 8px; text-align: center; display: block; line-height: 1; max-width: 195px"
                                        class="mt-2 fw-bold">
                                        All plug-in electrical equipment are required to be test-tagged according to
                                        AS/NZA
                                        3760 standards.
                                    </small>
                                    <img class="mt-2" src="../Images/FSMBE-Harwal-Logo.png" style="max-width: 180px">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary me-1" data-bs-dismiss="modal">Close</button>
                    <button onclick="saveAsImage()" class="btn btn-dark" data=""><i
                            class="fa-solid fa-download me-1"></i>Save</button>
                    <button class="btn btn-dark" onclick="printCableTag()"><i
                            class="fa-solid fa-print me-1"></i>Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Add Document Modal ================== -->
    <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Cable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/AddCableForm.php") ?>
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
                    <p>Are you sure you want to delete <span class="fw-bold" id="cableNoToDelete"></span> cable?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="cableIdToDelete" id="cableIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Edit Cable Modal ================== -->
    <div class="modal fade" id="editCableModal" tab-index="-1" aria-labelledby="editCableModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Cable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/EditCableForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Cable Dashboard Modal ================== -->
    <div class="modal fade" id="cableDashboardModal" tabindex="-1" aria-labelledby="cableDashboardModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cableDashboardModalLabel">Test & Tag Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body background-color">
                    <?php require_once("../PageContent/tat-index-content.php") ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>
    <script src="../html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

    <script>
        // Print the cable tag
        function printCableTag() {
            // Select the element to capture (you can change this selector based on what you want to capture)
            var elementToCapture = document.getElementById('cableTag');

            // Get the cable number from the modal
            var cableNo = document.getElementById('cableNo').textContent.trim();

            // Use html2canvas to convert the element to a canvas with a higher scale for better resolution
            html2canvas(elementToCapture, {
                scale: 10 // Increase scale for better resolution (higher scale = better resolution)
            }).then(function (canvas) {
                // Get the data URL of the canvas (in PNG format)
                var imageData = canvas.toDataURL("image/png");

                // Create a new window for printing
                var printWindow = window.open('', '', 'height=600,width=800');

                // Write content to the new window
                printWindow.document.write('<html><head><title>Print Cable Tag</title>');
                printWindow.document.write('<style>');

                // Define the size of the page as the Zebra printer label dimensions (100mm x 150mm)
                printWindow.document.write(`
@page {
    size: 85mm 150mm; /* Zebra label size */
    margin: 0; /* Remove margins */
}
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    background-color: white;
}
#printContent {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}
img {
    width: 100%;
    height: auto;
    max-width: 100%;  /* Ensure image does not exceed label width */
    max-height: 100%; /* Ensure image does not exceed label height */
}
`);

                printWindow.document.write('</style></head><body>');

                // Insert the image into the new window
                printWindow.document.write('<div id="printContent"><img src="' + imageData + '" /></div>');
                printWindow.document.write('</body></html>');

                // Close the document to finish writing to it
                printWindow.document.close();

                // Wait for the content to load and then trigger the print dialog
                printWindow.onload = function () {
                    printWindow.print();
                };

                // Automatically close the window after the print job is finished
                printWindow.onafterprint = function () {
                    printWindow.close();
                };
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
        function saveAsImage() {
            // Select the element to capture (you can change this selector based on what you want to capture)
            var elementToCapture = document.getElementById('cableTag');

            // Get the cable number from the modal
            var cableNo = document.getElementById('cableNo').textContent.trim();

            // Use html2canvas to convert the element to a canvas with a higher scale for better resolution
            html2canvas(elementToCapture, {
                scale: 10 // Increase scale for better resolution (higher scale = better resolution)
            }).then(function (canvas) {
                // Get the data URL of the canvas (in PNG format)
                var imageData = canvas.toDataURL("image/png");

                // Create a temporary link element to trigger the download
                var link = document.createElement('a');
                link.href = imageData;
                link.download = cableNo + '.png';  // The file name will be the cable number

                // Programmatically click the link to trigger the download
                link.click();
            });
        }

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('barcodeModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var cableNo = button.getAttribute('data-cable-no');

                var modalCableNo = myModalEl.querySelector('#cableNo');
                modalCableNo.textContent = cableNo; // Display the ID text

                // Generate the barcode using JsBarcode
                JsBarcode("#barcode1", cableNo, {
                    width: 1.8,            // Barcode width (adjust as needed)
                    height: 75,         // Barcode height (in pixels)
                    margin: 0,           // No margin
                    displayValue: true   // Display the value text below the barcode
                });


                // Generate the barcode using JsBarcode
                JsBarcode("#barcode2", cableNo, {
                    width: 1.8,            // Barcode width (adjust as needed)
                    height: 44,         // Barcode height (in pixels)
                    margin: 0,           // No margin
                    displayValue: true   // Display the value text below the barcode
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('barcodeModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableNo = button.getAttribute('data-cable-no');
                var location = button.getAttribute('data-location');
                var testFrequency = button.getAttribute('data-test-frequency');

                // Update the modal's content with the extracted info
                var modalCableLocation = myModalEl.querySelector('#cableLocation');

                modalCableLocation.textContent = location;

            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('cableTestTagModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableId = button.getAttribute('data-cable-id');
                var cableNo = button.getAttribute('data-cable-no');

                // Update the modal's content with the extracted info
                var modalCableId = myModalEl.querySelector('#testTagCableId');
                var modalCableIdToBeAdded = myModalEl.querySelector('#cableIdToBeAdded');
                var modalCableNo = myModalEl.querySelector('#testTagCableNo');

                modalCableId.textContent = cableId;
                modalCableIdToBeAdded.value = cableId;
                modalCableNo.textContent = cableNo;
            })
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableNo = button.getAttribute('data-cable-no');
                var cableId = button.getAttribute('data-cable-id');

                // Update the modal's content with the extracted info
                var modalCableNo = myModalEl.querySelector('#cableNoToDelete');
                var modalCableId = myModalEl.querySelector('#cableIdToDelete');

                modalCableNo.textContent = cableNo;
                modalCableId.value = cableId;
            })
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editCableModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableNo = button.getAttribute('data-cable-no');
                var cableId = button.getAttribute('data-cable-id');
                var location = button.getAttribute('data-location');
                var testFrequency = button.getAttribute('data-test-frequency');
                var description = button.getAttribute('data-description');
                var purchaseDate = button.getAttribute('data-purchase-date');
                var assetNo = button.getAttribute('data-asset-no');

                // Update the modal's content with the extracted info
                var modalCableNo = myModalEl.querySelector('#cableNoToEdit');
                var modalCableId = myModalEl.querySelector('#cableIdToEdit');
                var modalLocation = myModalEl.querySelector('#locationToEdit');
                var modalTestFrequency = myModalEl.querySelector('#testFrequencyToEdit')
                var modalDescription = myModalEl.querySelector('#descriptionToEdit');
                var modalPurchaseDate = myModalEl.querySelector('#cablePurchaseDateToEdit');
                var modalAssetNo = myModalEl.querySelector('#assetNoToEdit');

                modalCableNo.value = cableNo;
                modalCableId.value = cableId;
                modalLocation.value = location;
                modalTestFrequency.value = testFrequency;
                modalDescription.value = description;
                modalPurchaseDate.value = purchaseDate;
                modalAssetNo.value = assetNo.startsWith("FE") ? assetNo.substring(2) : assetNo;
            });
        })
    </script>

    <script>
        // Listen for the modal close event
        $('#cableTestTagModal').on('hidden.bs.modal', function () {
            // Reload the page and keep the parameters in the URL
            location.reload();  // This reloads the page
        });
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const dropdownButton = document.getElementById("testStatusDropdown");
            const dropdownItems = document.querySelectorAll(".dropdown-menu .status-check");

            // Get test_status from URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentFilter = urlParams.get("test_status") || "all"; // Default to "All"

            // Mapping test_status values to text labels
            const filterLabels = {
                "all": "All",
                "almost_due": "Almost Due (30 Days)",
                "already_due": "Already Due",
                "not_tested": "Not Tested",
                "tested": "Tested"
            };

            // Set the dropdown button text based on current filter
            dropdownButton.textContent = filterLabels[currentFilter] || "All";

            // Function to update the dropdown button text and apply filter
            dropdownItems.forEach(item => {
                item.addEventListener("click", function (event) {
                    event.preventDefault();
                    const selectedValue = this.getAttribute("data-value");

                    // Update button text
                    dropdownButton.textContent = this.textContent;

                    // Apply filter (Redirect with selected test_status)
                    applyTestStatusFilter(selectedValue);
                });
            });
        });

        function applyTestStatusFilter(testStatus) {
            const url = new URL(window.location.href);
            url.searchParams.set("test_status", testStatus);
            window.location.href = url.toString();
        }
    </script>
    <script>
        // Listen for the modal close event
        $('#cableTestTagModal').on('hidden.bs.modal', function () {
            // Disable buttons during reload
            $('button').prop('disabled', true);

            // Reload the page and keep the parameters in the URL
            location.reload();  // This reloads the page
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