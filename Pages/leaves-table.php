<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../vendor/autoload.php'; // Include the Composer autoload file

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");

$folder_name = "Human Resources";
require_once("../group_role_check.php");

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'employee_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 30;
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
$offset = ($page - 1) * $records_per_page;

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Base WHERE condition
$whereClause = "(e.employee_id LIKE '%$searchTerm%' OR e.first_name LIKE '%$searchTerm%' OR e.last_name LIKE '%$searchTerm%' OR e.nickname LIKE '%$searchTerm%')";

$whereClause .= "AND e.is_active = 1";

// Adjust the WHERE clause based on the role
if ($role !== "full control" && $role !== "modify 1") {
    // Exclude payroll_type = 'salary' for non-admin roles
    $whereClause .= " AND payroll_type != 'salary'";
}

// Further restrict based on the position name
if ($position_name == "Sheet Metal Manager") {
    $whereClause .= " AND department_name = 'Sheet Metal'";
} else if ($position_name == "Sheet Metal Production Supervisor") {
    $whereClause .= " AND department_name = 'Sheet Metal'";
} else if ($position_name == "Operations Support Manager") {
    $whereClause .= " AND department_name = 'Operations Support'";
} else if ($position_name == "Engineering Manager") {
    $whereClause .= " AND department_name = 'Engineering'";
} else if ($position_name == "Electrical") {
    $whereClause .= " AND department_name = 'Electrical'";
}

$current_leaves_sql = "
SELECT 
    e.employee_id, 
    e.first_name, 
    e.last_name, 
    e.nickname, 
    e.department, 
    d.department_name,
    annual.hours AS annual_hours,
    sick.hours AS sick_hours,
    ls.hours AS long_service_hours,
    GREATEST(
        COALESCE(annual.updated_date, '0000-00-00'),
        COALESCE(sick.updated_date, '0000-00-00'),
        COALESCE(ls.updated_date, '0000-00-00')
    ) AS latest_updated
FROM employees e
JOIN department d ON e.department = d.department_id
LEFT JOIN (
    SELECT l1.employee_id, l1.hours, l1.updated_date
    FROM leaves l1
    INNER JOIN (
        SELECT employee_id, MAX(updated_date) AS latest_date
        FROM leaves
        WHERE leave_type = 'Annual Lve'
        GROUP BY employee_id
    ) l2 ON l1.employee_id = l2.employee_id AND l1.updated_date = l2.latest_date
    WHERE l1.leave_type = 'Annual Lve'
) annual ON e.employee_id = annual.employee_id
LEFT JOIN (
    SELECT l1.employee_id, l1.hours, l1.updated_date
    FROM leaves l1
    INNER JOIN (
        SELECT employee_id, MAX(updated_date) AS latest_date
        FROM leaves
        WHERE leave_type = 'Sick/Personal'
        GROUP BY employee_id
    ) l2 ON l1.employee_id = l2.employee_id AND l1.updated_date = l2.latest_date
    WHERE l1.leave_type = 'Sick/Personal'
) sick ON e.employee_id = sick.employee_id
LEFT JOIN (
    SELECT l1.employee_id, l1.hours, l1.updated_date
    FROM leaves l1
    INNER JOIN (
        SELECT employee_id, MAX(updated_date) AS latest_date
        FROM leaves
        WHERE leave_type = 'LS Leave'
        GROUP BY employee_id
    ) l2 ON l1.employee_id = l2.employee_id AND l1.updated_date = l2.latest_date
    WHERE l1.leave_type = 'LS Leave'
) ls ON e.employee_id = ls.employee_id
WHERE $whereClause
ORDER BY $sort $order
LIMIT $offset, $records_per_page
";

$current_leaves_result = $conn->query($current_leaves_sql);

if (isset($_POST['import_csv']) && isset($_FILES['leaveFile']) && $_FILES['leaveFile']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['leaveFile']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['leaveFile']['name'], PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        echo "<script>alert('Invalid file type. Please upload a .csv file.');</script>";
        return;
    }

    function convertDate($value)
    {
        $dateObj = DateTime::createFromFormat('d/m/Y', $value);
        return $dateObj ? $dateObj->format('Y-m-d') : null;
    }

    if (($handle = fopen($fileTmpPath, 'r')) !== false) {
        fgetcsv($handle); // Skip header row

        $stmt = $conn->prepare("
            INSERT INTO leaves (employee_id, leave_type, hours, updated_date)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                hours = VALUES(hours),  
                updated_date = VALUES(updated_date)
        ");

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 5) {
                continue; // skip incomplete rows
            }

            $row = array_map(fn($item) => mb_convert_encoding(trim($item), 'UTF-8', 'auto'), $row);

            // CSV order: Emp Code | Name | Leave Type | Projected Date | Total
            [$employeeId, $name, $leaveType, $updatedDate, $hours] = $row;

            // Pad employee ID to 3 digits
            $employeeId = str_pad($employeeId, 3, "0", STR_PAD_LEFT);

            // Fix date format (CSV uses d/m/y like 8/10/25)
            $updatedDate = DateTime::createFromFormat('j/n/Y', $updatedDate);
            $updatedDate = $updatedDate ? $updatedDate->format('Y-m-d') : null;

            $stmt->bind_param("ssss", $employeeId, $leaveType, $hours, $updatedDate);
            $stmt->execute();
        }

        fclose($handle);
        echo "<script>alert('CSV imported successfully.');</script>";

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

$total_records_sql = "
SELECT COUNT(DISTINCT e.employee_id) AS total
FROM employees e
LEFT JOIN leaves l ON e.employee_id = l.employee_id
JOIN department d ON e.department = d.department_id
WHERE $whereClause
";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Leaves Table</title>
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
        <div class="d-flex justify-content-end align-items-center">
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

        <div class="row mb-3 mt-3">
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
                            <div class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
                <?php if ($role === "full control" || $role = "modify 1") { ?>
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addLeaveModal"> <i
                            class="fa-solid fa-plus"></i>
                        Add Leave</button>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <?php if ($role === "full control" || $role === "modify 1") { ?>
                            <th style="min-width: 50px;"> </th>
                        <?php } ?>
                        <th class="py-4 align-middle text-center" style="cursor: pointer;">
                            <a onclick="updateSort('employee_id', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">Employee Id <i
                                    class="fa-solid fa-sort fa-md ms-1"></i> </a>
                        </th>
                        <th class="py-4 align-middle text-center" style="cursor: pointer;">
                            <a onclick="updateSort('first_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">Employee Name <i
                                    class="fa-solid fa-sort fa-md ms-1"></i> </a>
                        </th>
                        <th class="py-4 align-middle text-center" style="cursor: pointer;">
                            <a onclick="updateSort('annual_hours', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">Annual <i
                                    class="fa-solid fa-sort fa-md ms-1"></i> </a>
                        </th>
                        <th class="py-4 align-middle text-center" style="cursor: pointer;">
                            <a onclick="updateSort('sick_hours', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">Personal <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center" style="cursor: pointer">
                            <a onclick="updateSort('long_service_hours', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">Long Service <i
                                    class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle text-center" style="cursor: pointer">
                            <a onclick="updateSort('latest_updated', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">Updated Date <i
                                    class="fa-solid fa-sort fa-ms ms-1"></i> </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($current_leaves_result->num_rows > 0) { ?>
                        <?php while ($row = $current_leaves_result->fetch_assoc()) { ?>
                            <?php
                            $departmentName = $row['department_name'];
                            if ($role === "modify 1") {
                                if (($position_name === 'Sheet Metal Manager' && $departmentName != 'Sheet Metal') || ($position_name === 'Sheet Metal Production Supervisor' && $departmentName != 'Sheet Metal')) {
                                    continue; // Show only Sheet Metal department employees
                                } else if ($position_name === 'Operations Support Manager' && $departmentName != 'Operations Support') {
                                    continue;
                                } else if ($position_name === 'Engineering Manager' && $departmentName != 'Engineering') {
                                    continue;
                                } else if ($position_name === 'Electrical Manager' && $departmentName != 'Electrical') {
                                    continue;
                                }
                            } ?>
                            <tr>
                                <?php if ($role === "full control" || $role === "modify 1") { ?>
                                    <td class="py-3 align-middle text-center">
                                        <a href="#" class="open-leave-history" data-bs-toggle="modal"
                                            data-bs-target="#leaveHistoryModal" data-employee-id="<?= $row['employee_id'] ?>"
                                            data-full-name="<?= $row["first_name"] ?>         <?= $row["last_name"] ?>">
                                            <i class="fa-solid fa-clock-rotate-left text-warning"></i>
                                        </a>
                                    </td>
                                <?php } ?>
                                <td class="py-3 align-middle text-center"><?= $row["employee_id"] ?></td>
                                <td class="py-3 align-middle text-center">
                                    <?= $row["first_name"] ?>         <?= $row["last_name"] ?>
                                    <?php if (!empty($row['nickname'])): ?>
                                        (<?= $row['nickname'] ?>)
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-center"
                                    style="<?= isset($row['annual_hours']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= $row["annual_hours"] ?? 'N/A' ?>
                                </td>
                                <td class="py-3 text-center"
                                    style="<?= isset($row['sick_hours']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= $row["sick_hours"] ?? 'N/A' ?>
                                </td>
                                <td class="py-3 text-center"
                                    style="<?= isset($row['long_service_hours']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= $row["long_service_hours"] ?? 'N/A' ?>
                                </td>
                                <td class="py-3 text-center"
                                    style="<?= isset($row['latest_updated']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= !empty($row["latest_updated"]) ? date('d F Y', strtotime($row["latest_updated"])) : 'N/A' ?>
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
                        
                        // Adjust start page if it goe below 1
                        if ($end_page - $start_page < $page_range - 1) {
                            $start_page = max(1, $end_page - $page_range + 1);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : '' ?>" style="cursor: pointer">
                                <a class="page-link" onclick="updatePage(<?php echo $i ?>); return false;">
                                    <?php echo $i; ?></a>
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

    <div class="modal fade" id="addLeaveModal" tabindex="-1" aria-labelledby="addLeaveModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Leave File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group col-md-12">
                            <label for="leaveFile" class="fw-bold">Leave File (.csv)</label>
                            <input name="leaveFile" class="form-control" type="file" required />
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            <button class="btn btn-dark" name="import_csv">Add Leave</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== L E A V E S  H I S T O R Y  M O D A L ========================== -->
    <div class="modal fade" id="leaveHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="leaveHistoryContent">
                    <div class="container">
                        <div class="row d-flex justify-content-center align-items-end mb-4">
                            <div class="container-fluid">
                                <h4
                                    class="mt-4 mb-4 fw-bold signature-color text-center signature-bg-color text-white py-2 rounded-3">
                                    <span id="employeeNameLeaveLabel" class="form-control-static"></span> -
                                    <span id="employeeIdLeaveLabel" class="form-control-static"></span>
                                </h4>
                            </div>
                            <div class="col-md-5 mb-2 mb-md-0">
                                <label for="startDateFilter" class="fw-bold">Start Date:</label>
                                <input type="date" id="startDateFilter" class="form-control">
                            </div>
                            <div class="col-md-5 mb-2 mb-md-0">
                                <label for="endDateFilter" class="fw-bold">End Date:</label>
                                <input type="date" id="endDateFilter" class="form-control">
                            </div>
                            <div class="col-md-2 text-center">
                                <button class="btn btn-dark w-100" id="applyLeaveTimeframeFilter">Apply Filter</button>
                            </div>
                        </div>

                        <!-- Chart -->
                        <canvas id="leaveHistoryChart" style="height: 100px; margin-below: 20px;"></canvas>
                        <!-- Table will be loaded here -->
                        <div id="leaveHistoryTable"></div>

                        <div class="d-flex justify-content-center mt-4">
                            <button class="btn btn-secondary" data-bs-dismiss="modal"> Close </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php"); ?>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src=" https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

    <script>
        function clearURLParameters() {
            // Use the URL API to manipulate the URL
            const url = new URL(window.location.href);
            url.search = '';

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
        document.addEventListener("DOMContentLoaded", function () {
            const startInput = document.getElementById('startDateFilter');
            const endInput = document.getElementById('endDateFilter');
            const applyBtn = document.getElementById('applyLeaveTimeframeFilter');
            const tableContainer = document.getElementById('leaveHistoryTable');
            const chartCanvas = document.getElementById('leaveHistoryChart');
            let currentEmployeeId = null;

            // Helper: fetch and render
            async function loadLeaveHistory(employeeId, startDate = null, endDate = null) {
                currentEmployeeId = employeeId;
                // Build query
                const params = new URLSearchParams({ employee_id: employeeId });
                if (startDate) params.set('start_date', startDate);
                if (endDate) params.set('end_date', endDate);

                try {
                    const res = await fetch("../AJAXphp/fetch_leave_history.php?" + params.toString());
                    const data = await res.json();

                    if (data.error) {
                        tableContainer.innerHTML = "<div class='text-danger'>Error: " + data.error + "</div>";
                        return;
                    }

                    // Populate start/end inputs with default_dates from server (so they reflect actual used range)
                    if (data.default_dates) {
                        if (startInput) startInput.value = data.default_dates.start || '';
                        if (endInput) endInput.value = data.default_dates.end || '';
                    }

                    // Render table
                    if (Array.isArray(data.table) && data.table.length > 0) {
                        let html = "<table class='table table-bordered mt-3'><thead class='bg-signature'><tr><th>Leave Type</th><th>Hours</th><th>Updated Date</th></tr></thead><tbody>";
                        data.table.forEach(row => {
                            let typeLabel = row.leave_type;

                            // Map leave types
                            if (typeLabel === "Annual Lve") {
                                typeLabel = "Annual";
                            } else if (typeLabel === "Sick/Personal") {
                                typeLabel = "Personal";
                            } else if (typeLabel === "LS Leave") {
                                typeLabel = "Long Service"
                            }

                            html += `<tr>
        <td>${typeLabel}</td>
        <td>${parseFloat(row.hours).toFixed(4)}</td>
        <td>${row.updated_date}</td>
    </tr>`;
                        });

                        html += "</tbody></table>";
                        tableContainer.innerHTML = html;
                    } else {
                        tableContainer.innerHTML = "<div>No leave history found.</div>";
                    }

                    // Render chart (side-by-side bars)
                    if (!chartCanvas) return;
                    const ctx = chartCanvas.getContext('2d');
                    if (window.leaveChart) {
                        window.leaveChart.destroy();
                    }

                    // ensure datasets labels mapping (if needed)
                    const datasets = (data.chart.datasets || []).map(ds => {
                        // keep ds as-is but ensure label mapping if any
                        if (ds.label === 'Annual Lve') ds.label = 'Annual Leave';
                        if (ds.label === 'Sick/Personal') ds.label = 'Personal Leave';
                        if (ds.label === 'LS Leave') ds.label = 'Long Service Leave';
                        return ds;
                    });

                    window.leaveChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.chart.labels || [],
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const val = context.raw ?? 0;
                                            return (context.dataset.label || '') + ': ' + Number(val).toFixed(4);
                                        }
                                    }
                                },
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                x: {
                                    stacked: false,
                                    ticks: { autoSkip: false }
                                },
                                y: {
                                    stacked: false,
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                } catch (err) {
                    tableContainer.innerHTML = "<div class='text-danger'>Failed to load data.</div>";
                    console.error(err);
                }
            }

            // Attach click handlers to open-leave-history buttons (they exist in table rows)
            document.querySelectorAll(".open-leave-history").forEach(btn => {
                btn.addEventListener("click", function () {
                    const employeeId = this.dataset.employeeId;
                    // Load default range (server will default to last 3 months)
                    loadLeaveHistory(employeeId);
                });
            });

            // Apply filter button
            if (applyBtn) {
                applyBtn.addEventListener("click", function (e) {
                    e.preventDefault();
                    if (!currentEmployeeId) return; // nothing loaded yet
                    const start = startInput.value ? startInput.value : null;
                    const end = endInput.value ? endInput.value : null;
                    loadLeaveHistory(currentEmployeeId, start, end);
                });
            }

            // Optional: when modal is hidden, destroy chart to free memory
            const leaveModal = document.getElementById('leaveHistoryModal');
            if (leaveModal) {
                leaveModal.addEventListener('hidden.bs.modal', function () {
                    if (window.leaveChart) {
                        window.leaveChart.destroy();
                        window.leaveChart = null;
                    }
                    tableContainer.innerHTML = '';
                    if (startInput) startInput.value = '';
                    if (endInput) endInput.value = '';
                    currentEmployeeId = null;
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const leaveHistoryModal = document.getElementById('leaveHistoryModal');
            leaveHistoryModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that trigger the modal
                var employeeFullName = button.getAttribute('data-full-name');
                var employeeId = button.getAttribute('data-employee-id');

                var employeeIdLeaveLabel = document.getElementById('employeeIdLeaveLabel');
                var employeeNameLeaveLabel = document.getElementById('employeeNameLeaveLabel');

                employeeIdLeaveLabel.innerHTML = employeeId;
                employeeNameLeaveLabel.innerHTML = employeeFullName;

                console.log(employeeFullName);
            })
        })
    </script>
</body>