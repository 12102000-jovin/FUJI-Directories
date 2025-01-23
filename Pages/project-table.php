<?php
ini_set('diplay_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

// Connect to the database
require_once('../db_connect.php');
require_once("../status_check.php");
require_once("../email_sender.php");

$folder_name = "Project";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrieve session data 
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// Get status filter
$statusFilter = isset($_GET['current']) ? $conn->real_escape_string($_GET['current']) : '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'project_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 30; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Search condition
$whereClause = "(project_no LIKE '%$searchTerm%' OR quote_no LIKE '%$searchTerm%' OR project_name LIKE '%$searchTerm%')";

$whereClause .= " AND (current = '$statusFilter' OR '$statusFilter' = '')";

$project_sql = "SELECT * FROM projects WHERE $whereClause ORDER BY $sort $order 
LIMIT $offset, $records_per_page";
$project_result = $conn->query($project_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM projects WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch the project engineer's ID(s) for the project
$project_engineer_ids = $row["project_engineer"];
$engineer_names = [];

if (!empty($project_engineer_ids)) {
    // Split the IDs by comma (in case of multiple IDs)
    $engineer_ids = explode(',', $project_engineer_ids);

    // Query the database to get the names of the engineers
    foreach ($engineer_ids as $engineer_id) {
        $engineer_id = trim($engineer_id); // Remove any extra spaces
        $engineer_sql = "SELECT name FROM employees WHERE employee_id = '$engineer_id'";
        $engineer_result = $conn->query($engineer_sql);

        if ($engineer_result && $engineer_result->num_rows > 0) {
            // Get the engineer's name and add it to the array
            $engineer_row = $engineer_result->fetch_assoc();
            $engineer_names[] = $engineer_row['name'];
        }
    }

    // Join the names with commas if there are multiple
    $engineer_names_list = implode(', ', $engineer_names);
} else {
    $engineer_names_list = "N/A"; // If no engineer is assigned
}


// ========================= D E L E T E  D O C U M E N T =========================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["projectIdToDelete"])) {
    $projectIdToDelete = $_POST["projectIdToDelete"];

    $delete_document_sql = "DELETE FROM projects WHERE project_id = ?";
    $delete_document_result = $conn->prepare($delete_document_sql);
    $delete_document_result->bind_param("i", $projectIdToDelete);

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
    <title>Project Table</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
    <style>
        .canvasjs-chart-credit {
            display: none !important;
        }

        .canvasjs-chart-canvas {
            border-radius: 12px;
        }

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
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Project Table
                    </li>
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
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">
                                Search
                            </button>
                            <button class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </button>

                            <!-- Dropdown Menu for Status -->
                            <button class="btn btn-outline-dark dropdown-toggle ms-2" type="button"
                                id="statusDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo $statusFilter ? $statusFilter : "All Status" ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="statusDropdownMenuButton">
                                <li><a class="dropdown-item dropdown-status-item" href="#"
                                        data-status-filter="All Status">All Status</a></li>
                                <li><a class="dropdown-item dropdown-status-item" href="#"
                                        data-status-filter="Archived">Archived</a></li>
                                <li><a class="dropdown-item dropdown-status-item" href="#"
                                        data-status-filter="By Others">By Others</a></li>
                                <li><a class="dropdown-item dropdown-status-item" href="#"
                                        data-status-filter="In Progress">In Progress</a></li>
                                <li><a class="dropdown-item dropdown-status-item" href="#"
                                        data-status-filter="Completed">Completed</a></li>
                                <li><a class="dropdown-item dropdown-status-item" href="#"
                                        data-status-filter="Cancelled">Cancelled</a></li>
                            </ul>

                            <input type="hidden" id="selectedStatusFilter" name="current">
                        </div>
                    </form>
                </div>
                <?php if ($role === "admin") { ?>
                    <div class="d-flex justify-content-end align-items-center col-4 col-lg-7">
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#projectReportModal">
                            <i class="fa-solid fa-square-poll-vertical"></i> Report</button>
                        <a class="btn btn-primary me-2"
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/pj-index.php"> <i
                                class="fa-solid fa-chart-pie"></i> Dashboard</a>
                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                                class="fa-solid fa-plus"></i> Add Project</button>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <?php if ($role === "admin") { ?>
                            <th></th>
                        <?php } ?>
                        <th class="py-4 align-middle text-center projectNoColumn" style="min-width:120px">
                            <a onclick="updateSort('project_no', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Project No <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center quoteNoColumn" style="min-width: 150px">
                            <a onclick="updateSort('quote_no', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Quote No <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center currentColumn" style="min-width: 120px">
                            <a onclick="updateSort('current', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Status <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center projectNameColumn" style="min-width: 300px">
                            <a onclick="updateSort('project_name','<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Project Name <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center projectTypeColumn" style="min-width: 200px;">
                            <a onclick="updateSort('project_type', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Project Type <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center customerColumn" style="min-width: 200px;">
                            <a onclick="updateSort('customer', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Customer <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center valueColumn" style="min-width: 160px;">
                            <a onclick="updateSort('value', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Value (Ex. GST) <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center paymentTermsColumn" style="min-width: 120px;">
                            <a onclick="updateSort('payment_terms', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Payment Terms <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center projectEngineerColumn" style="min-width: 200px;">
                            <a onclick="updateSort('project_engineer', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Project Engineer <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center customerAddressColumn" style="min-width: 200px;">
                            <a onclick="updateSort('customer_address', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Customer Address <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i></a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($project_result->num_rows > 0) { ?>
                        <?php while ($row = $project_result->fetch_assoc()) {
                            // Fetch the project engineer's ID(s) for the project
                            $project_engineer_ids = $row["project_engineer"];
                            $engineer_names = [];

                            if (!empty($project_engineer_ids)) {
                                // Split the IDs by comma (in case of multiple IDs)
                                $engineer_ids = explode(',', $project_engineer_ids);

                                // Query the database to get the names of the engineers
                                foreach ($engineer_ids as $engineer_id) {
                                    $engineer_id = trim($engineer_id); // Remove any extra spaces
                                    $engineer_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = '$engineer_id'";
                                    $engineer_result = $conn->query($engineer_sql);

                                    if ($engineer_result && $engineer_result->num_rows > 0) {
                                        // Get the engineer's name and add it to the array
                                        $engineer_row = $engineer_result->fetch_assoc();
                                        $engineer_names[] = $engineer_row['first_name'] . " " . $engineer_row['last_name'];
                                    }
                                }

                                // Join the names with commas if there are multiple
                                $engineer_names_list = implode(', ', $engineer_names);
                            } else {
                                $engineer_names_list = NULL; // If no engineer is assigned
                            }
                            ?>
                            <tr>
                                <?php if ($role === "admin") { ?>
                                    <td class="align-middle">
                                        <div class="d-flex">
                                            <button id="editDocumentModalBtn" class="btn" data-bs-toggle="modal"
                                                data-bs-target="#editDocumentModal" data-project-id="<?= $row["project_id"] ?>"
                                                data-project-no="<?= $row["project_no"] ?>" data-quote-no="<?= $row["quote_no"] ?>"
                                                data-current="<?= $row["current"] ?>"
                                                data-project-name="<?= $row["project_name"] ?>"
                                                data-project-type="<?= $row["project_type"] ?>"
                                                data-customer="<?= $row["customer"] ?>" data-value="<?= $row["value"] ?>"
                                                data-payment-terms="<?= $row["payment_terms"] ?>"
                                                data-project-engineer="<?= $row["project_engineer"] ?>"
                                                data-customer-address="<?= $row["customer_address"] ?>"><i
                                                    class="fa-regular fa-pen-to-square"></i></button>
                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                data-project-id="<?= $row["project_id"] ?>"
                                                data-project-no="<?= $row["project_no"] ?>"
                                                data-quote-no="<?= $row["quote_no"] ?>"><i
                                                    class="fa-regular fa-trash-can text-danger"></i></button>
                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                data-project-id="<?= $row["project_id"] ?>"
                                                data-project-name="<?= $row["project_name"] ?>"
                                                data-project-no="<?= $row["project_no"] ?>" data-quote-no="<?= $row["quote_no"] ?>"
                                                data-customer="<?= $row["customer"] ?>"><i
                                                    class="fa-solid fa-file-pen text-warning text-opacity-50"></i></button>
                                        </div>
                                    </td>
                                <?php } ?>
                                <td class="py-3 align-middle text-center projectNoColumn"><a
                                        href="../open-project-folder.php?folder=<?= $row["project_no"] ?>"
                                        target="_blank"><?= $row['project_no'] ?></a></td>
                                <td class="py-3 align-middle text-center quoteNoColumn" <?= isset($row["quote_no"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['quote_no']) ? $row['quote_no'] : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center text-white 
                                    <?php if ($row["current"] === "Archived") {
                                        echo "bg-secondary";
                                    } else if ($row["current"] === "Completed") {
                                        echo "bg-success";
                                    } else if ($row["current"] === "In Progress") {
                                        echo "bg-info";
                                    } else if ($row["current"] === "By Others") {
                                        echo "bg-dark";
                                    } else if ($row["current"] === "Cancelled") {
                                        echo "bg-danger";
                                    }
                                    ?> currentColumn"><?= $row["current"] ?></td>
                                <td class="py-3 align-middle text-center projectNameColumn"><?= $row["project_name"] ?></td>
                                <td class="py-3 align-middle text-center projectTypeColumn"><?= $row["project_type"] ?></td>
                                <td class="py-3 align-middle text-center customerColumn"><?= $row["customer"] ?></td>
                                <td class="py-3 align-middle text-center valueColumn" <?=
                                    isset($row["value"]) && $row["value"] != 0
                                    ? ""
                                    : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'"
                                    ?>>
                                    <?=
                                        isset($row["value"]) && $row["value"] != 0
                                        ? "$" . number_format($row["value"], 2)
                                        : "N/A"
                                        ?>
                                </td>
                                <td class="py-3 align-middle text-center paymentTermsColumn">
                                    <?= $row["payment_terms"] ?>
                                </td>
                                <td class="py-3 align-middle text-center projectEngineerColumn" <?= isset($engineer_names_list) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($engineer_names_list) ? $engineer_names_list : "N/A" ?>
                                </td>
                                <td class="py-3 align-middle text-center customerAddressColumn"
                                    <?= isset($row["customer_address"]) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row["customer_address"]) ? $row["customer_address"] : "N/A" ?>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/AddProjectForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Edit Project Document Modal ================== -->
    <div class="modal fade" id="editDocumentModal" tab-index="-1" aria-labelledby="editDocumentModal"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require("../Form/EditProjectForm.php") ?>
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
                    <p>Are you sure you want to delete project <span class="fw-bold" id="projectNoToDelete"></span>
                        with quote <span class="fw-bold" id="quoteNoToDelete"></span>?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="projectIdToDelete" id="projectIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
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
                        <input class="form-check-input" type="checkbox" id="projectNoColumn"
                            data-column="projectNoColumn">
                        <label class="form-check-label" for="projectNoColumn">
                            Project No
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="quoteNoColumn" data-column="quoteNoColumn">
                        <label class="form-check-label" for="quoteNoColumn">Quote No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="currentColumn" data-column="currentColumn">
                        <label class="form-check-label" for="currentColumn">Status</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="projectNameColumn"
                            data-column="projectNameColumn">
                        <label class="form-check-label" for="projectNameColumn">Project Name</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="projectTypeColumn"
                            data-column="projectTypeColumn">
                        <label class="form-check-label" for="projectTypeColumn">Project Type</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="customerColumn"
                            data-column="customerColumn">
                        <label class="form-check-label" for="customerColumn">Customer</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="valueColumn" data-column="valueColumn">
                        <label class="form-check-label" for="valueColumn">Value</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="paymentTermsColumn"
                            data-column="paymentTermsColumn">
                        <label class="form-check-label" for="paymentTermsColumn">Payment Terms</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="projectEngineerColumn"
                            data-column="projectEngineerColumn">
                        <label class="form-check-label" for="projectEngineerColumn">Project Engineer</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="customerAddressColumn"
                            data-column="customerAddressColumn">
                        <label class="form-check-label" for="customerAddressColumn">Customer Address</label>
                    </div>
                    <div class="d-flex justify-content-end" style="cursor:pointer">
                        <button onclick="resetColumnFilter()" class="btn btn-sm btn-danger me-1"> Reset
                            Filter</button>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailsModal" tab-index="-1" aria-labelledby="detailsModal" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModal">Project Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require("../PageContent/ModalContent/project-details-table.php") ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="projectReportModal" tabindex="-1" aria-labelledby="projectReportModal"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Project Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="true"></button>
                </div>
                <div class="modal-body">
                    <?php require("../PageContent/ModalContent/project-report.php") ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src=" https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

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
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var projectNo = button.getAttribute('data-project-no');
                var quoteNo = button.getAttribute('data-quote-no');
                var projectId = button.getAttribute('data-project-id');

                // Update the modal's content with the extracted info
                var modalProjectNoToDelete = myModalEl.querySelector('#projectNoToDelete');
                var modalQuoteNoToDelete = myModalEl.querySelector('#quoteNoToDelete');
                var modalProjectIdToDelete = myModalEl.querySelector('#projectIdToDelete');
                modalProjectNoToDelete.textContent = projectNo;
                modalQuoteNoToDelete.textContent = quoteNo;
                modalProjectIdToDelete.value = projectId;
            })
        })    
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('detailsModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the Modal
                var projectId = button.getAttribute('data-project-id');
                var projectNo = button.getAttribute('data-project-no');
                var projectName = button.getAttribute('data-project-name');
                var projectCustomer = button.getAttribute('data-customer');
                var quoteNo = button.getAttribute('data-quote-no');

                // Ensure that quoteNo has a value, otherwise set it to "N/A"
                if (!quoteNo || quoteNo.trim() === "") {
                    quoteNo = "N/A";
                }

                var modalProjectId = myModalEl.querySelector('#projectId');
                var modalProjectIdEditAllDate = myModalEl.querySelector('#projectIdEditAllDate');
                var modalProjectNo = myModalEl.querySelector('#projectNo');
                var modalProjectName = myModalEl.querySelector('#projectName');
                var modalProjectCustomer = myModalEl.querySelector('#projectCustomer');
                var modalQuoteNo = myModalEl.querySelector('#quoteNo');

                modalProjectId.value = projectId;
                modalProjectIdEditAllDate.value = projectId;
                modalProjectNo.textContent = projectNo;
                modalProjectName.textContent = projectName;
                modalProjectCustomer.textContent = projectCustomer;
                modalQuoteNo.textContent = quoteNo;
            })
        })
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editDocumentModal');

            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;

                // Extract data from the button attributes
                var projectId = button.getAttribute('data-project-id');
                var projectNo = button.getAttribute('data-project-no');
                var quoteNo = button.getAttribute('data-quote-no');
                var projectName = button.getAttribute('data-project-name');
                var current = button.getAttribute('data-current');
                var projectType = button.getAttribute('data-project-type');
                var customer = button.getAttribute('data-customer');
                var paymentTerms = button.getAttribute('data-payment-terms');
                var projectEngineers = button.getAttribute('data-project-engineer'); // Ensure this attribute is set
                var customerAddress = button.getAttribute('data-customer-address');

                // Update the modal's content with the extracted data
                var modalProjectId = myModalEl.querySelector('#projectIdToEdit');
                var modalProjectNo = myModalEl.querySelector('#projectNoToEdit');
                var modalQuoteNo = myModalEl.querySelector('#quoteNoToEdit');
                var modalProjectName = myModalEl.querySelector('#projectNameToEdit');
                var modalCurrent = myModalEl.querySelector('#currentToEdit');
                var modalProjectType = myModalEl.querySelector('#projectTypeToEdit');
                var modalCustomer = myModalEl.querySelector('#customerToEdit');
                var modalPaymentTerms = myModalEl.querySelector('#paymentTermsToEdit');
                var modalCustomerAddress = myModalEl.querySelector('#customerAddressToEdit');

                // Assign the extracted values to the modal input fields
                modalProjectId.value = projectId;
                modalProjectNo.value = projectNo;
                modalQuoteNo.value = quoteNo;
                modalProjectName.value = projectName;
                modalCurrent.value = current;
                modalProjectType.value = projectType;
                modalCustomer.value = customer;
                modalPaymentTerms.value = paymentTerms;
                modalCustomerAddress.value = customerAddress;

                // Preselect engineers in the dropdown (if any)
                if (projectEngineers) {
                    var selectedEngineers = projectEngineers.split(','); // Assuming a comma-separated list of engineer IDs

                    selectedEngineers.forEach(function (engineerId) {
                        var engineerCheckbox = myModalEl.querySelector('#engineer_' + engineerId);
                        if (engineerCheckbox) {
                            engineerCheckbox.checked = true;
                        }
                    });

                    // Update the button text to reflect the selected engineers
                    updateSelectedEngineersText();
                }
            })
        })

        // Function to update the selected engineers' names on the button
        function updateSelectedEngineersText() {
            const projectEngineerDropdown = document.getElementById("projectEngineerDropdownToEdit");
            const checkboxes = projectEngineerDropdown.querySelectorAll('input[type="checkbox"]');
            const selectedEngineerText = document.getElementById("selectedEngineerToEdit");

            const selected = Array.from(checkboxes).filter(cb => cb.checked);

            const selectedNames = selected.map(cb => {
                const label = cb.nextElementSibling;
                return label ? label.innerHTML : '';
            }).filter(name => name !== '');

            const buttonText = selectedNames.length > 0
                ? selectedNames.join("<br>")
                : "Select Project Engineer(s)";

            if (selectedNames.length > 0) {
                selectedEngineerText.innerHTML = "Selected:<br>" + buttonText;
                selectedEngineerText.style.display = 'block';
            } else {
                selectedEngineerText.innerHTML = '';
                selectedEngineerText.style.display = 'none';
            }
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
        function updateSort(sort, order) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
            window.location.href = url.toString();
        }
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
        // Listen for the modal close event
        $('#detailsModal').on('hidden.bs.modal', function () {
            // Reload the page and keep the parameters in the URL
            location.reload();  // This reloads the page
        });
    </script>
    </div>
</body>