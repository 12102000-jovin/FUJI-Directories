<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require '../vendor/autoload.php'; // Include the Composer autoload file

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");
require_once "../email_sender.php";

$folder_name = "Quality Assurances";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrieve session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// Get status filter
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'capa_document_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 10; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build base SQL query with role-based filtering
$whereClause = "(capa_document_id LIKE '%$searchTerm%')";

$whereClause .= " AND (status = '$statusFilter' OR '$statusFilter' = '')";

// SQL query to retrieve CAPA data
$capa_sql = "SELECT capa.*, 
                    capa_owner.email AS capa_owner_email, 
                    assigned_to.email AS assigned_to_email
             FROM capa 
             LEFT JOIN employees AS capa_owner ON capa.capa_owner = capa_owner.employee_id
             LEFT JOIN employees AS assigned_to ON capa.assigned_to = assigned_to.employee_id
             WHERE $whereClause
             ORDER BY $sort $order 
             LIMIT $offset, $records_per_page";
$capa_result = $conn->query($capa_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM capa WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Create new email sender object
$emailSender = new emailSender();


// ========================= D E L E T E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["capaIdToDelete"])) {
    $capaIdToDelete = $_POST["capaIdToDelete"];

    $delete_document_sql = "DELETE FROM capa WHERE capa_id = ?";
    $delete_document_result = $conn->prepare($delete_document_sql);
    $delete_document_result->bind_param("i", $capaIdToDelete);

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
    <title>CAPA Table</title>
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
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">CAPA Table</li>
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
                            <button class="btn btn-outline-dark dropdown-toggle ms-2" type="button"
                                id="statusDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo $statusFilter ? $statusFilter : "All Status" ?>
                            </button>
                            <div class="dropdown">
                                <form method="GET" action="">
                                    <input type="hidden" id="selectedStatusFilter" name="status" value="">
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
                <?php if ($role === "admin") { ?>
                    <div class="d-flex justify-content-end align-items-center col-4 col-lg-7">
                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                                class="fa-solid fa-plus"></i> Add CAPA Document</button>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <?php if ($role === "admin") { ?>
                            <th></th>
                        <?php } ?>
                        <th class="py-4 align-middle text-center capaDocumentIdColumn" style="min-width:120px">
                            <a onclick="updateSort('capa_document_id', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                CAPA ID <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center dateRaisedColumn" style="min-width:100px">
                            <a onclick="updateSort('date_raised', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Date Raised<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center capaDescriptionColumn" style="min-width:300px">
                            <a onclick="updateSort('capa_description', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Description<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center severityColumn" style="min-width:100px">
                            <a onclick="updateSort('severity', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Severity<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center raisedAgainstColumn" style="min-width:150px">
                            <a onclick="updateSort('raised_against', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Raised Against <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center capaOwnerColumn" style="min-width:140px">
                            <a onclick="updateSort('capa_owner', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Owner<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center statusColumn" style="min-width:100px">
                            <a onclick="updateSort('status', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Status<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center assignedToColumn" style="min-width:150px">
                            <a onclick="updateSort('assigned_to', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Assigned to<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center mainSourceTypeColumn" style="min-width:200px">
                            <a onclick="updateSort('main_source_type', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Main Source Type<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center productOrServiceColumn" style="min-width:160px">
                            <a onclick="updateSort('product_or_service', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Product/Service <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center mainFaultCategoryColumn" style="min-width:200px">
                            <a onclick="updateSort('main_fault_category', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Main Fault Category <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center targetCloseDateColumn" style="min-width:150px">
                            <a onclick="updateSort('target_close_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Target Close Date <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center daysLeftColumn" style="min-width:200px">
                            <a onclick="updateSort('days_left', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Days Left <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center dateClosedColumn" style="min-width:100px">
                            <a onclick="updateSort('date_closed', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Date Closed <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center keyTakeawayColumn" style="min-width:200px">
                            <a onclick="updateSort('key_takeaways', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Key Takeaways <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center additionalCommentColumn" style="min-width:200px">
                            <a onclick="updateSort('additional_comments', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">
                                Additional Comments <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($capa_result->num_rows > 0) { ?>
                        <?php while ($row = $capa_result->fetch_assoc()) { ?>
                            <tr>
                                <?php if ($role === "admin") { ?>
                                    <td class="py-2 align-middle text-center ">
                                        <div class="d-flex">
                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#editDocumentModal"
                                                data-capa-id="<?= $row['capa_id'] ?>"
                                                data-capa-document-id="<?= $row['capa_document_id'] ?>"
                                                data-date-raised="<?= $row['date_raised'] ?>"
                                                data-capa-description="<?= $row['capa_description'] ?>"
                                                data-severity="<?= $row['severity'] ?>" data-capa-status="<?= $row['status'] ?>"
                                                data-raised-against="<?= $row['raised_against'] ?>"
                                                data-capa-owner="<?= $row['capa_owner'] ?>"
                                                data-assigned-to="<?= $row['assigned_to'] ?>"
                                                data-main-source-type="<?= $row['main_source_type'] ?>"
                                                data-product-or-service="<?= $row['product_or_service'] ?>"
                                                data-main-fault-category="<?= $row['main_fault_category'] ?>"
                                                data-target-close-date="<?= $row['target_close_date'] ?>"
                                                data-date-closed="<?= $row['date_closed'] ?>"
                                                data-key-takeaways="<?= $row['key_takeaways'] ?>"
                                                data-additional-comments="<?= $row['additional_comments'] ?>"
                                                data-capa-owner-email="<?= $row['capa_owner_email'] ?>"
                                                data-assigned-to-email="<?= $row['assigned_to_email'] ?>">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>

                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                data-capa-id="<?= $row["capa_id"] ?>"
                                                data-capa-document-id="<?= $row["capa_document_id"] ?>"><i
                                                    class="fa-regular fa-trash-can text-danger"></i></button>
                                        </div>
                                    </td>
                                <?php } ?>
                                <td class="py-2 align-middle text-center capaDocumentIdColumn"><a
                                        href="../open-capa-folder.php?folder=<?= $row["capa_document_id"] ?>"
                                        target="_blank"><?= $row['capa_document_id'] ?></a></td>
                                <td class="py-2 align-middle text-center dateRaisedColumn"><?= $row['date_raised'] ?></td>
                                <td class="py-2 align-middle capaDescriptionColumn"><?= $row['capa_description'] ?></td>
                                <td class="py-2 align-middle text-center severityColumn
                                <?php
                                if ($row['severity'] === "Major" || $row['severity'] === "Minor" || $row['severity'] === "Catastrophic") {
                                    echo "text-white";
                                } else {
                                    echo "text-dark";
                                }
                                ?>" <?php
                                if ($row['severity'] === "Major") {
                                    echo 'style="background-color: #ff7e04"';
                                } else if ($row["severity"] === "Minor") {
                                    echo 'style="background-color: #fcd968"';
                                } else if ($row["severity"] === "Catastrophic") {
                                    echo 'style="background-color: #dc3545"';
                                } else {
                                    echo 'style="background-color: #ffffff"';
                                }
                                ?>>
                                    <?= $row['severity'] ?>
                                </td>
                                <td class="py-2 align-middle text-center raisedAgainstColumn"><?= $row['raised_against'] ?></td>

                                <?php
                                $employee_id = $row['capa_owner'];
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
                                <td class="py-2 align-middle text-center capaOwnerColumn" <?= ($employee_name === "N/A") ? "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" : "" ?>>
                                    <?= htmlspecialchars($employee_name) ?>
                                </td>

                                <td class="py-2 align-middle text-center statusColumn
                                    <?php if ($row['status'] === "Open") {
                                        echo 'bg-danger text-white';
                                    } else if ($row['status'] === "Closed") {
                                        echo 'bg-success text-white';
                                    } ?>">
                                    <?= $row['status'] ?>
                                </td>

                                <?php
                                $assignedToEmployeeId = $row['assigned_to'];
                                $assignedToEmployeeName = "N/A";

                                if (isset($assignedToEmployeeId)) {
                                    // Prepare and execute the query to fetch employee name
                                    $assignedToStmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
                                    $assignedToStmt->bind_param("i", $assignedToEmployeeId);
                                    $assignedToStmt->execute();
                                    $assignedToResult = $assignedToStmt->get_result();

                                    // Fetch the employee name
                                    if ($assignedToRowEmployee = $assignedToResult->fetch_assoc()) {
                                        $assigned_to_first_name = $assignedToRowEmployee['first_name'];
                                        $assigned_to_last_name = $assignedToRowEmployee['last_name'];
                                        $assignedToEmployeeName = $assigned_to_first_name . ' ' . $assigned_to_last_name; // Combine first and last name
                                    }
                                }
                                ?>

                                <td class="py-2 align-middle text-center assignedToColumn" <?= ($assignedToEmployeeName === "N/A") ? "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" : "" ?>>
                                    <?= htmlspecialchars($assignedToEmployeeName) ?>
                                </td>
                                <td class="py-2 align-middle text-center mainSourceTypeColumn"><?= $row['main_source_type'] ?>
                                </td>
                                <td class="py-2 align-middle text-center productOrServiceColumn">
                                    <?= isset($row['product_or_service']) ? $row['product_or_service'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center mainFaultCategoryColumn"
                                    <?= isset($row['main_fault_category']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['main_fault_category']) ? $row['main_fault_category'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center targetCloseDateColumn"><?= $row['target_close_date'] ?>
                                </td>
                                <td class="py-2 align-middle text-center daysLeftColumn
                                <?php
                                // Define the class based on the value of daysLeft
                                if (!empty($row['target_close_date']) && $row['status'] === "Open") {
                                    $targetCloseDate = new DateTime($row['target_close_date']);
                                    $currentDate = new DateTime();
                                    $interval = $currentDate->diff($targetCloseDate);
                                    $daysLeft = $interval->format('%r%a');

                                    // Check if overdue
                                    if ($daysLeft <= 0) {
                                        echo 'text-white bg-danger'; // Red text class if overdue or 0 days left
                                    } else if ($daysLeft <= 30) {
                                        echo 'text-white bg-warning';
                                    }

                                }
                                ?>
                                " <?php if ($row['status'] === "Closed") {
                                    echo "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'";
                                } ?>>
                                    <?php
                                    if (!empty($row['target_close_date']) && $row['status'] === "Open") {
                                        // Output the message based on daysLeft
                                        if ($daysLeft < 0) {
                                            echo "Overdue By " . abs($daysLeft) . " days";
                                        } else {
                                            echo "$daysLeft" . " Days Remaining";
                                        }
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </td>

                                <td class="py-2 align-middle text-center dateClosedColumn" <?= isset($row['date_closed']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['date_closed']) ? $row['date_closed'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center keyTakeawayColumn" <?= isset($row['key_takeaways']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['key_takeaways']) ? $row['key_takeaways'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center additionalCommentColumn"
                                    <?= isset($row['additional_comments']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['additional_comments']) ? $row['additional_comments'] : "N/A" ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="17" class="text-center">No records found</td>
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
                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="30" <?php echo $records_per_page == 30 ? 'selected' : ''; ?>>30</option>
                    </select>
                </form>

                <!-- Pagination controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
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
                    </ul>
                </nav>
            </div>
        </div>

        <!-- ================== Add CAPA Document Modal ================== -->
        <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModal"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add CAPA Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php require("../Form/AddCAPADocumentForm.php") ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== Edit CAPA Document Modal ================== -->
        <div class="modal fade" id="editDocumentModal" tab-index="-1" aria-labelledby="editDocumentModal"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit CAPA Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php require("../Form/EditCAPADocumentForm.php") ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== Delete CAPA Document Modal ================== -->
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
                        <p>Are you sure you want to delete <span class="fw-bold" id="capaDocumentToDelete"></span>
                            document?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <!-- Add form submission for the deletion here -->
                        <form method="POST">
                            <input type="hidden" name="capaIdToDelete" id="capaIdToDelete">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== Filter Column Modal ================== -->
        <div class="modal fade" id="filterColumnModal" tab-index="-1" aria-labelledby="filterColumnModal"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterColumnModalLabel">Filter Column</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="capaDocumentIdColumn"
                                    data-column="capaDocumentIdColumn">
                                <label class="form-check-label" for="capaDocumentIdColumn">
                                    CAPA Id
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="dateRaisedColumn"
                                    data-column="dateRaisedColumn">
                                <label class="form-check-label" for="dateRaisedColumn">
                                    Date Raised
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="capaDescriptionColumn"
                                    data-column="capaDescriptionColumn">
                                <label class="form-check-label" for="capaDescriptionColumn">
                                    Description
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="severityColumn"
                                    data-column="severityColumn">
                                <label class="form-check-label" for="severityColumn">
                                    Severity
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="raisedAgainstColumn"
                                    data-column="raisedAgainstColumn">
                                <label class="form-check-label" for="raisedAgainstColumn">
                                    Raised Against
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="capaOwnerColumn"
                                    data-column="capaOwnerColumn">
                                <label class="form-check-label" for="capaOwnerColumn">
                                    Owner
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="statusColumn"
                                    data-column="statusColumn">
                                <label class="form-check-label" for="statusColumn">Status</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="assignedToColumn"
                                    data-column="assignedToColumn">
                                <label class="form-check-label" for="assignedToColumn">Assigned To</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="mainSourceTypeColumn"
                                    data-column="mainSourceTypeColumn">
                                <label class="form-check-label" for="mainSourceTypeColumn">Main Source Type</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="productOrServiceColumn"
                                    data-column="productOrServiceColumn">
                                <label class="form-check-label" for="productOrServiceColumn">Product/Service</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="mainFaultCategoryColumn"
                                    data-column="mainFaultCategoryColumn">
                                <label class="form-check-label" for="mainFaultCategoryColumn">Main Fault
                                    Category</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="targetCloseDateColumn"
                                    data-column="targetCloseDateColumn">
                                <label class="form-check-label" for="targetCloseDateColumn">Target Close Date</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="daysLeftColumn"
                                    data-column="daysLeftColumn">
                                <label class="form-check-label" for="daysLeftColumn">Days Left</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="dateClosedColumn"
                                    data-column="dateClosedColumn">
                                <label class="form-check-label" for="dateClosedColumn">Date Closed</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="keyTakeawayColumn"
                                    data-column="keyTakeawayColumn">
                                <label class="form-check-label" for="keyTakeawayColumn">Key Takeaway</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="additionalCommentColumn"
                                    data-column="additionalCommentColumn">
                                <label class="form-check-label" for="additionalCommentColumn">Additional Comment</label>
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
        </div>
    </div>

    <?php require_once("../logout.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
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
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var capaId = button.getAttribute('data-capa-id'); // Extract info from data-* attributes
                var capaDocument = button.getAttribute('data-capa-document-id');

                // Update the modal's content with the extracted info
                var modalCAPAIdToDelete = myModalEl.querySelector('#capaIdToDelete');
                var modalCAPADocument = myModalEl.querySelector('#capaDocumentToDelete');
                modalCAPAIdToDelete.value = capaId;
                modalCAPADocument.textContent = capaDocument;
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editDocumentModal');

            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;

                // Extract data from the button attributes
                var capaId = button.getAttribute('data-capa-id');
                var capaDocumentId = button.getAttribute('data-capa-document-id');
                var dateRaised = button.getAttribute('data-date-raised');
                var capaDescription = button.getAttribute('data-capa-description');
                var severity = button.getAttribute('data-severity');
                var status = button.getAttribute('data-capa-status');
                var raisedAgainst = button.getAttribute('data-raised-against');
                var capaOwner = button.getAttribute('data-capa-owner');
                var assignedTo = button.getAttribute('data-assigned-to');
                var mainSourceType = button.getAttribute('data-main-source-type');
                var productOrService = button.getAttribute('data-product-or-service');
                var mainFaultCategory = button.getAttribute('data-main-fault-category');
                var targetCloseDate = button.getAttribute('data-target-close-date');
                var dateClosed = button.getAttribute('data-date-closed');
                var keyTakeaways = button.getAttribute('data-key-takeaways');
                var additionalComments = button.getAttribute('data-additional-comments');
                var capaOwnerEmail = button.getAttribute('data-capa-owner-email');
                var assignedToEmail = button.getAttribute('data-assigned-to-email');

                // Update the modal's content with the extracted data
                var modalCapaId = myModalEl.querySelector('#capaIdToEdit');
                var modalCapaId2 = myModalEl.querySelector('#capaIdToEdit2');
                var modalCapaId3 = myModalEl.querySelector('#capaIdToEdit3');
                var modalCapaDocumentId = myModalEl.querySelector('#capaDocumentIdToEdit');
                var modalDateRaised = myModalEl.querySelector('#dateRaisedToEdit');
                var modalCapaDescription = myModalEl.querySelector('#capaDescriptionToEdit');
                var modalSeverity = myModalEl.querySelector('#severityToEdit');
                var modalStatus = myModalEl.querySelector('#capaStatus');
                var modalRaisedAgainst = myModalEl.querySelector('#raisedAgainstToEdit');
                var modalCapaOwner = myModalEl.querySelector('#capaOwnerToEdit');
                var modalAssignedTo = myModalEl.querySelector('#assignedToToEdit');
                var modalMainSourceType = myModalEl.querySelector('#mainSourceTypeToEdit');
                var modalProductOrService = myModalEl.querySelector('#productOrServiceToEdit');
                var modalMainFaultCategory = myModalEl.querySelector('#mainFaultCategoryToEdit');
                var modalTargetCloseDate = myModalEl.querySelector('#targetCloseDateToEdit');
                var modalDateClosed = myModalEl.querySelector('#dateClosed');
                var modalKeyTakeaways = myModalEl.querySelector('#keyTakeawaysToEdit');
                var modalKeyAdditionalComments = myModalEl.querySelector('#additionalCommentsToEdit');
                var modalCapaOwnerEmail = myModalEl.querySelector('#capaOwnerEmailEdit');
                var modalAssignedToEmail = myModalEl.querySelector('#assignedToEmailEdit');

                // Assign the extracted values to the modal input fields
                modalCapaId.value = capaId;
                modalCapaId2.value = capaId;
                modalCapaId3.value = capaId;
                modalCapaDocumentId.value = capaDocumentId;
                modalDateRaised.value = dateRaised;
                modalCapaDescription.value = capaDescription;
                modalSeverity.value = severity;
                modalStatus.innerHTML = status;
                modalRaisedAgainst.value = raisedAgainst;
                modalCapaOwner.value = capaOwner;
                modalAssignedTo.value = assignedTo;
                modalMainSourceType.value = mainSourceType;
                modalProductOrService.value = productOrService;
                modalMainFaultCategory.value = mainFaultCategory;
                modalTargetCloseDate.value = targetCloseDate;
                if (dateClosed !== null && dateClosed !== '') {
                    modalDateClosed.value = dateClosed;
                }

                modalKeyTakeaways.value = keyTakeaways;
                modalKeyAdditionalComments.value = additionalComments;
                modalCapaOwnerEmail.value = capaOwnerEmail;
                modalAssignedToEmail.value = assignedToEmail;

                // Show or hide the form based on the status
                var openForm = myModalEl.querySelector('#openForm');
                var closeForm = myModalEl.querySelector('#closeForm');
                var resultText = myModalEl.querySelector('#result'); // Assuming you want to display a message here
                var openCapaBtn = myModalEl.querySelector('#openCapaBtn');
                var cancelOpenCapaBtn = myModalEl.querySelector('#cancelOpenCapaBtn')

                if (status !== "Open") {
                    openForm.style.display = 'none'; // Hide the form
                    // closeForm.style.display = 'none'; // Hide the close form
                    resultText.innerHTML = "This CAPA document has been closed."; // Set the message
                    resultText.classList.remove('d-none'); // Show the message
                    openCapaBtn.classList.remove('d-none');
                    cancelOpenCapaBtn.classList.remove('d-none');
                } else {
                    openForm.style.display = 'block'; // Show the form
                    // closeForm.style.display = 'none'; // Hide the close form
                    resultText.classList.add('d-none'); // Hide the message
                    openCapaBtn.classList.add('d-none');
                    cancelOpenCapaBtn.classList.add('d-none');
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
        function updateSort(sort, order) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
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
            });
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
</body>

</html>