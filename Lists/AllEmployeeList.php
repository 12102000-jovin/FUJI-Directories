<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'employee_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Default query where employees are filtered by search term
$whereClause = "(first_name LIKE '%$searchTerm%' OR
    last_name LIKE '%$searchTerm%' OR
    nickname LIKE '%$searchTerm%' OR
    employee_id LIKE '%$searchTerm%' OR
    plate_number LIKE '%$searchTerm%')";

// Arrays to hold selected filter values
$selected_departments = [];
$selected_employment_types = [];
$selected_visa = [];
$selected_payroll_types = [];
$selected_section = [];
$selected_status = [];
$selected_work_shift = [];

$filterApplied = false;  // Variable to check if any filter is applied

if (isset($_GET['apply_filters'])) {
    // Capture the selected departments (department IDs)
    if (isset($_GET['department']) && is_array($_GET['department'])) {
        $selected_departments = $_GET['department'];
        $department_placeholders = implode(',', array_fill(0, count($selected_departments), '?'));
        $whereClause .= " AND department IN ($department_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected employment type
    if (isset($_GET['employment_type']) && is_array($_GET['employment_type'])) {
        $selected_employment_types = $_GET['employment_type'];
        $employment_type_placeholders = implode(',', array_fill(0, count($selected_employment_types), '?'));
        $whereClause .= " AND employment_type IN ($employment_type_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected visa
    if (isset($_GET['visa']) && is_array($_GET['visa'])) {
        $selected_visa = $_GET['visa'];
        $visa_placeholders = implode(',', array_fill(0, count($selected_visa), '?'));
        $whereClause .= " AND visa IN ($visa_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected payroll visa
    if (isset($_GET['payroll_type']) && is_array($_GET['payroll_type'])) {
        $selected_payroll_types = $_GET['payroll_type'];
        $payroll_type_placeholders = implode(',', array_fill(0, count($selected_payroll_types), '?'));
        $whereClause .= " AND payroll_type IN ($payroll_type_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected section 
    if (isset($_GET['section']) && is_array($_GET['section'])) {
        $selected_section = $_GET['section'];
        $section_placeholders = implode(',', array_fill(0, count($selected_section), '?'));
        $whereClause .= " AND section IN ($section_placeholders)";
        $filterApplied = true;
    }

    // Capture the selected work shift
    if (isset($_GET['work_shift']) && is_array($_GET['work_shift'])) {
        $selected_work_shift = $_GET['work_shift'];
        $work_shift_placeholders = implode(',', array_fill(0, count($selected_work_shift), '?'));
        $whereClause .= " AND work_shift IN ($work_shift_placeholders)";
        $filterApplied = true;
    }

    // Check if "active" is set and apply the filter for active/inactive employee
    if (isset(($_GET['status'])) && is_array($_GET['status'])) {
        $selected_status = $_GET['status'];
        $status_placeholders = implode(',', array_fill(0, count($selected_status), '?'));
        $whereClause .= " AND is_active IN ($status_placeholders)";
        $filterApplied = true;
    }

    // Check if "expiredVisa" is set and apply the filter for expired visas
    if (isset($_GET['expiredVisa'])) {
        $current_date = date('Y-m-d');
        $whereClause .= " AND visa_expiry_date < ?";
        $filterApplied = true;
    }
}

// Build the full SQL query with department filtering applied
$employee_list_sql = "SELECT employees.*, department_id, department_name, visa_name
FROM employees 
JOIN department ON department.department_id = employees.department
JOIN visa ON visa.visa_id = employees.visa
WHERE $whereClause 
ORDER BY $sort $order;
";

// Prepare the statement to bind parameters
$stmt = $conn->prepare($employee_list_sql);

// Prepare the types and bind parameters dynamically
$types = '';

// If there are department filters, bind them to the prepared statement
if (!empty($selected_departments)) {
    $types .= str_repeat('i', count($selected_departments)); // 'i' for integer binding
}

// If there are employment_type filters, bind them to the prepared statement
if (!empty($selected_employment_types)) {
    $types .= str_repeat('s', count($selected_employment_types)); // 's' for string binding
}

// If there are visa filters, bind them to the prepared statement
if (!empty($selected_visa)) {
    $types .= str_repeat('i', count($selected_visa)); // 'i' for integer binding
}

// If there are section filters, bind them to the prepared statement
if (!empty($selected_section)) {
    $types .= str_repeat('s', count($selected_section)); // 's' for integer binding
}

// If there are payroll_type filters, bind them to the prepared statement
if (!empty($selected_payroll_types)) {
    $types .= str_repeat('s', count($selected_payroll_types)); // 's' for string binding
}

// If there are work_shift filters, bind them to the prepared statement
if (!empty($selected_work_shift)) {
    $types .= str_repeat('s', count($selected_work_shift)); // 's' for string binding
}

// If there are status filters, bind them to the prepared statement
if (!empty($selected_status)) {
    $types .= str_repeat('i', count($selected_status)); // 'i' for integer binding
}

// If expiredVisa filter is applied, bind the current date
if (isset($_GET['expiredVisa'])) {
    $types .= 's'; // 's' for string (date format)
}

// Bind parameters only if there are types to bind
$params = array_merge($selected_departments, $selected_employment_types, $selected_visa, $selected_payroll_types, $selected_section, $selected_status, $selected_work_shift);
if (isset($_GET['expiredVisa'])) {
    $params[] = $current_date; // Bind the current date for expired visas
}

// Only bind parameters if $types is not empty
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

// Execute the query to fetch the filtered employee data
$stmt->execute();
$employee_list_result = $stmt->get_result();

// Fetch the filtered employees into an array
$employees = [];
if ($employee_list_result->num_rows > 0) {
    while ($row = $employee_list_result->fetch_assoc()) {
        $employees[] = $row;
    }
} else {
    $employees = [];
}

// Adjust the WHERE clause based on the role
if ($role !== "full control") {
    // Exclude payroll_type = 'salary' for non-admin roles
    $whereClause .= " AND payroll_type != 'salary'";
}

// Get total count of filtered results (Total)
$count_sql = "SELECT COUNT(*) as total FROM employees WHERE $whereClause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_count = $count_row['total'];

$count_active_sql = "SELECT COUNT(*) as total_active FROM employees WHERE $whereClause AND is_active = 1";
$count_active_stmt = $conn->prepare($count_active_sql);
if (!empty($types)) {
    $count_active_stmt->bind_param($types, ...$params);
}
$count_active_stmt->execute();
$count_active_result = $count_active_stmt->get_result();
$count_active_row = $count_active_result->fetch_assoc();
$total_active_count = $count_active_row['total_active'];

// Get all URL parameters from $_GET
$urlParams = $_GET;

// ========================= E D I T  D E P A R T M E N T ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["departmentCellToEdit"])) {
    $empIdToEditDepartmentCell = $_POST["empIdToEditDepartmentCell"];
    $departmentCellToEdit = $_POST["departmentCellToEdit"];

    $update_department_sql = "UPDATE employees SET department = ? WHERE employee_id = ?";
    $update_department_result = $conn->prepare($update_department_sql);
    $update_department_result->bind_param("ss", $departmentCellToEdit, $empIdToEditDepartmentCell);

    if ($update_department_result->execute()) {
        echo "<script>window.location.href = window.location.href;</script>";
        exit(); // Stop further execution
    } else {
        echo "Error updating department: " . $update_department_result->error;
    }
}

// ========================= E D I T  E X P I R Y  D A T E ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["expiryDateCellToEdit"])) {
    $empIdToEditExpiryDateCell = $_POST["empIdToEditExpiryDateCell"];
    $expiryDateCellToEdit = $_POST["expiryDateCellToEdit"];

    $update_expiry_date_sql = "UPDATE employees SET visa_expiry_date = ? WHERE employee_id = ?";
    $update_expiry_date_result = $conn->prepare($update_expiry_date_sql);
    $update_expiry_date_result->bind_param("ss", $expiryDateCellToEdit, $empIdToEditExpiryDateCell);

    if ($update_expiry_date_result->execute()) {
        echo "<script>window.location.href = window.location.href;</script>";
        exit(); // Stop further execution
    } else {
        echo "Error updating department: " . $update_expiry_date_result->error;
    }
}

// ========================= A D D  W A G E ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["wageCellToEdit"])) {
    $empIdToEditWageCell = $_POST["empIdToEditWageCell"];
    $wageCellToEdit = $_POST["wageCellToEdit"];
    $wageDateCellToEdit = $_POST["wageDateCellToEdit"];

    $add_wage_sql = "INSERT INTO wages (amount, `date`, employee_id) VALUES (?, ?, ?)";
    $add_wage_result = $conn->prepare($add_wage_sql);
    $add_wage_result->bind_param("dss", $wageCellToEdit, $wageDateCellToEdit, $empIdToEditWageCell);

    // Execute the prepared statement
    if ($add_wage_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        // Redirect to the same URL with parameters
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    } else {
        // Improved error reporting
        echo "Error updating record: " . $conn->error;
    }

}

// ========================= A D D  S A L A R Y ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["salaryCellToEdit"])) {
    $empIdToEditSalaryCell = $_POST["empIdToEditSalaryCell"];
    $salaryCellToEdit = $_POST["salaryCellToEdit"];
    $salaryDateCellToEdit = $_POST["salaryDateCellToEdit"];

    $add_salary_sql = "INSERT INTO salaries (amount, `date`, employee_id) VALUES (?, ?, ?)";
    $add_salary_result = $conn->prepare($add_salary_sql);
    $add_salary_result->bind_param("dss", $salaryCellToEdit, $salaryDateCellToEdit, $empIdToEditSalaryCell);

    // Execute the prepared statement
    if ($add_salary_result->execute()) {

        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        // Redirect to the same URL with parameters
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    } else {
        // Improved error reporting
        echo "Error updating record: " . $conn->error;
    }
}

// ========================= E D I T  S T A T U S ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["statusCellToEdit"])) {
    $empIdToEditStatusCell = $_POST["empIdToEditStatusCell"];
    $statusCellToEdit = $_POST["statusCellToEdit"];

    $update_status_sql = "UPDATE employees SET is_active = ? WHERE employee_id = ?";
    $update_status_result = $conn->prepare($update_status_sql);
    $update_status_result->bind_param("ss", $statusCellToEdit, $empIdToEditStatusCell);

    if ($update_status_result->execute()) {
        echo "<script>window.location.href = window.location.href;</script>";
        exit(); // Stop further execution
    } else {
        echo "Error updating status: " . $update_status_result->error;
    }
}

// ========================= E D I T  W O R K  S H I F T ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["workShiftCellToEdit"])) {
    $empIdToEditWorkShiftCell = $_POST["empIdToEditWorkShiftCell"];
    $workShiftCellToEdit = $_POST["workShiftCellToEdit"];

    $update_work_shift_sql = "UPDATE employees SET work_shift = ? WHERE employee_id = ?";
    $update_work_shift_result = $conn->prepare($update_work_shift_sql);
    $update_work_shift_result->bind_param("ss", $workShiftCellToEdit, $empIdToEditWorkShiftCell);

    if ($update_work_shift_result->execute()) {
        echo "<script>window.location.href = window.location.href;</script>";
        exit(); // Stop further execution
    } else {
        echo "Error updating work shift: " . $update_work_shift_result->error;
    }
}
?>

<head>
    <title>Employees</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }

        .table tbody th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<body class="background-color">
    <div class="container-fluid">
        <div class="row d-flex justify-content-between align-items-center">
            <div class="col-md-6">
                <!-- <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">All
                            Employees
                        </li>
                    </ol>
                </nav> -->
            </div>
            <?php if ($role == "full control" || $role == "modify 1") { ?>
                <div class="col-md-6 d-flex justify-content-start justify-content-md-end align-items-center mt-3 mt-md-0">
                    <!-- <a class="btn btn-primary fw-bold me-2" href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/hr-index.php"> <i class="fa-solid fa-chart-pie"></i> HR Dashboard </a> -->
                    <a class="btn btn-primary fw-bold me-2" type="button" data-bs-toggle="modal"
                        data-bs-target="#HRDashboardModal"> <i class="fa-solid fa-chart-pie"></i> HR Dashboard </a>
                    <a class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="fa-solid fa-user-plus"></i> Add Employee
                    </a>
                </div>
            <?php } ?>
        </div>
        <hr />
        <div class="row">
            <!-- Search Employee Input -->
            <div class="col-12 col-lg-6 mb-3">
                <form method="GET" id="searchForm">
                    <input type="hidden" id="viewInput2" name="view">
                    <div class="d-flex align-items-center">
                        <div class="input-group me-2">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="search" class="form-control" id="search" name="search"
                                placeholder="Search Employee">
                        </div>
                        <button class="btn" type="submit"
                            style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">Search
                        </button>
                        <button class="btn btn-danger ms-1"><a class="dropdown-item" href="#"
                                onclick="clearURLParameters()">Clear</a></button>
                    </div>
                </form>
            </div>
            <!-- Filter and Sort -->
            <div class="col-12 col-lg-6 mb-3">
                <div class="d-flex justify-content-start justify-content-md-end">
                    <div class="d-flex align-items-start gap-1 me-1">
                        <button class="btn btn-primary fw-bold d-flex align-items-center" onclick="printTable()">
                            <small>Print Table</small>
                            <i class="fa-solid fa-print ms-2"></i>
                        </button>

                        <button class="btn btn-secondary fw-bold d-flex align-items-center" id="filterColumnBtn"
                            data-bs-toggle="modal" data-bs-target="#filterTableColumnModal">
                            <small>Filter Column</small>
                            <i class="fa-solid fa-sliders ms-2"></i>
                        </button>

                        <button id="toggleButton" class="btn bg-success text-white fw-bold d-flex align-items-center">
                            <i class="fa-solid fa-table"></i>
                        </button>
                    </div>

                    <!-- Sort by Dropdown -->
                    <form method="get" action="">
                        <div class="dropdown">
                            <button class="btn text-white bg-dark" data-bs-toggle="dropdown" aria-expanded="false">
                                <small class="text-nowrap fw-bold">Sort by <i class="fa-solid fa-sort py-1"></i></small>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="setSort('employee_id', 'asc')">Id <i
                                            class="fa-solid fa-arrow-down-1-9" style="color:#043f9d"></i></a>
                                    <a class="dropdown-item" href="#" onclick="setSort('employee_id', 'desc')">Id <i
                                            class="fa-solid fa-arrow-down-9-1" style="color:#043f9d"></i></a>
                                    <a class="dropdown-item" href="#" onclick="setSort('first_name', 'asc')">Name <i
                                            class="fa-solid fa-arrow-down-a-z" style="color:#043f9d"></i></a>
                                    <a class="dropdown-item" href="#" onclick="setSort('first_name', 'desc')">Name <i
                                            class="fa-solid fa-arrow-down-z-a" style="color:#043f9d"></i></a>
                                </li>
                            </ul>
                        </div>
                    </form>
                    <!-- Filter by Button -->
                    <div class="d-flex align-items-start">
                        <button class="btn text-white ms-1 bg-dark" data-bs-toggle="modal"
                            data-bs-target="#filterEmployeeModal">
                            <small class="text-nowrap fw-bold">Filter by <i class="fa-solid fa-filter py-1"></i></small>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap mb-2">
            <?php
            // Define the mapping of sort keys to display text
            $sortMapping = [
                'employee_id' => 'Employee ID',
                'first_name' => 'Name',
                'asc' => 'ASC',
                'desc' => 'DESC',
            ];
            ?>
            <?php foreach ($urlParams as $key => $value): ?>
                <?php if (!empty($value)): // Only show the span if the value is not empty ?>
                    <?php

                    if ($key === 'order' || $key === 'view') {
                        continue;
                    }

                    // Combine 'sort' and 'order' into a single badge if both exist
                    if ($key === 'sort' && isset($urlParams['order'])) {
                        $sortKey = htmlspecialchars($value);
                        $order = htmlspecialchars($urlParams['order']);

                        // Check if the sort value has a friendly name in the mapping
                        $sortText = $sortMapping[$sortKey] ?? ucfirst($sortKey);
                        $orderText = $sortMapping[$order] ?? ucfirst($order);

                        ?>
                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                            <strong><span class="text-warning">Sort:
                                </span><?php echo $sortText . " (" . ucfirst($orderText) . ")"; ?></strong>
                            <a href="?<?php
                            // Remove 'sort' and 'order' from the URL
                            $filteredParams = $_GET;
                            unset($filteredParams['sort'], $filteredParams['order']);
                            echo http_build_query($filteredParams);
                            ?>" class="text-white ms-1">
                                <i class="fa-solid fa-times"></i>
                            </a>
                        </span>
                        <?php
                        continue; // Skip further processing of 'sort' and 'order'
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
                                    <strong><?php echo htmlspecialchars($department_name); ?></strong>
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
                    } else if ($key === 'employment_type' && is_array($value)) {
                        foreach ($value as $employmentType) {
                            ?>
                                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                        <strong><?php echo htmlspecialchars($employmentType); ?></strong>
                                        <a href="?<?php
                                        // Remove this specific employment_type filter from the URL
                                        $filteredParams = $_GET;
                                        $filteredParams['employment_type'] = array_diff($filteredParams['employment_type'], [$employmentType]);
                                        echo http_build_query($filteredParams);
                                        ?>" class="text-white ms-1">
                                            <i class="fa-solid fa-times"></i>
                                        </a>
                                    </span>
                            <?php
                        }
                    } else if ($key === 'visa' && is_array($value)) {
                        // Map visa IDs to visa names and display each in a separate badge
                        foreach ($value as $visa_id) {
                            // Fetch the visa name for each selected visa ID
                            $visa_sql = "SELECT visa_name FROM visa WHERE visa_id = ?";
                            $stmt = $conn->prepare($visa_sql);
                            $stmt->bind_param("i", $visa_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($row = $result->fetch_assoc()) {
                                $visa_name = $row['visa_name'];
                            }
                            // Display a separate badge for each visa
                            ?>
                                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                            <strong><?php echo htmlspecialchars($visa_name); ?></strong>
                                            <a href="?<?php
                                            // Remove this specific visa filter from the URL
                                            $filteredParams = $_GET;
                                            $filteredParams['visa'] = array_diff($filteredParams['visa'], [$visa_id]);
                                            echo http_build_query($filteredParams);
                                            ?>" class="text-white ms-1">
                                                <i class="fa-solid fa-times"></i>
                                            </a>
                                        </span>
                            <?php
                        }
                    } else if ($key === 'expiredVisa') {
                        // Handle the expiredVisa filter
                        ?>
                                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                            <strong>Expired Visa</strong>
                                            <a href="?<?php
                                            // Remove the expiredVisa filter from the URL
                                            $filteredParams = $_GET;
                                            unset($filteredParams['expiredVisa']);
                                            echo http_build_query($filteredParams);
                                            ?>" class="text-white ms-1">
                                                <i class="fa-solid fa-times"></i>
                                            </a>
                                        </span>
                        <?php
                    } else if ($key === 'payroll_type' && is_array($value)) {
                        foreach ($value as $payrollType) {
                            ?>
                                                <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                    <strong><?php echo htmlspecialchars(ucwords($payrollType)); ?></strong>

                                                    <a href="?<?php
                                                    // Remove this specific payroll_type filter from URL
                                                    $filteredParams = $_GET;
                                                    $filteredParams['payroll_type'] = array_diff($filteredParams['payroll_type'], [$payrollType]);
                                                    echo http_build_query($filteredParams);
                                                    ?>" class="text-white ms-1">
                                                        <i class="fa-solid fa-times fa-"></i>
                                                    </a>
                                                </span>
                            <?php
                        }
                    } else if ($key === 'work_shift' && is_array($value)) {
                        foreach ($value as $payrollType) {
                            ?>
                                                    <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                        <strong><?php echo htmlspecialchars(ucwords($payrollType)); ?></strong>

                                                        <a href="?<?php
                                                        // Remove this specific work_shift filter from URL
                                                        $filteredParams = $_GET;
                                                        $filteredParams['work_shift'] = array_diff($filteredParams['work_shift'], [$payrollType]);
                                                        echo http_build_query($filteredParams);
                                                        ?>" class="text-white ms-1">
                                                            <i class="fa-solid fa-times fa-"></i>
                                                        </a>
                                                    </span>
                            <?php
                        }
                    } else if ($key === 'section' && is_array($value)) {
                        foreach ($value as $section) {
                            ?>
                                                        <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                            <strong><?php echo htmlspecialchars(ucwords($section)); ?></strong>
                                                            <a href="?<?php
                                                            // Remove this specific section filter from URL
                                                            $filteredParams = $_GET;
                                                            $filteredParams['section'] = array_diff($filteredParams['section'], [$section]);
                                                            echo http_build_query($filteredParams);
                                                            ?>" class="text-white ms-1">
                                                                <i class="fa-solid fa-times fa-"></i>
                                                            </a>
                                                        </span>
                            <?php
                        }
                    } else if ($key === 'status' && is_array($value)) {
                        foreach ($value as $status) {
                            ?>
                                                            <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                                                <strong><?php if ($status === "1") {
                                                                    echo "Active";
                                                                } else if ($status === "0") {
                                                                    echo "Inactive";
                                                                } ?></strong>
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
                    } else {
                        // Display other filters
                        if (is_array($value)) {
                            echo htmlspecialchars($key) . ": " . htmlspecialchars(implode(", ", $value));
                        } else {
                            echo htmlspecialchars($key) . ": " . htmlspecialchars($value);
                        }
                    }
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Display message if filters are applied, and show total count or no results message -->
        <?php if ($filterApplied): ?>
            <div
                class="alert <?php echo ($total_count == 0 && $total_active_count == 0) ? 'alert-danger' : 'alert-info'; ?>">
                <?php if ($total_count > 0 || $total_active_count > 0): ?>
                    <strong>Total Results:</strong>
                    <span class="fw-bold text-decoration-underline me-2"> <?php echo $total_count ?></span>
                    <?php echo " (" . $total_active_count ?> Active,
                    <?php echo ($total_count - $total_active_count) ?> Inactive<?php echo ")"; ?>
                <?php else: ?>
                    <strong>No results found for the selected filters.</strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        <div class="row row-cols-1 row-cols-md-3 g-4" id="employeeCardList">
            <?php foreach ($employees as $employee) {
                $profileImage = $employee['profile_image'];
                $firstName = $employee['first_name'];
                $lastName = $employee['last_name'];
                $nickname = $employee['nickname'];
                $employeeId = $employee['employee_id'];
                $visaExpiryDate = $employee['visa_expiry_date'];
                $isActive = $employee['is_active'];
                $lastDate = $employee['last_date'];
                $payrollType = $employee['payroll_type'];

                // Check if the role is not "admin" and the payroll type is "Salary"
                if ($role !== 'full control' && $payrollType == 'salary') {
                    continue; // Skip this employee
                }
                ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <div class="card <?php if ($isActive == 0) {
                        echo "bg-danger bg-opacity-25";
                    } ?>  positive-relative" style="min-height:140px">
                        <?php if ($isActive == 0) { ?>
                            <span class="badge rounded-pill bg-danger position-absolute bottom-0 end-0 m-2"><small>Inactive <?php if (!empty($lastDate)) {
                                echo "(" . date('d M Y', strtotime($lastDate)) . ")";
                            } ?>
                                </small></span>
                        <?php } ?>
                        <div class="card-body d-flex justify-content-between align-items-center position-relative">
                            <div class="col-4 d-flex justify-content-center">
                                <?php if (!empty($profileImage)) { ?>
                                    <!-- Profile image -->
                                    <div class="bg-gradient shadow-lg rounded-circle"
                                        style="width: 80px; height: 80px; overflow: hidden;">
                                        <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                            class="profile-pic img-fluid rounded-circle"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php } else { ?>
                                    <!-- Initials -->
                                    <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                        style="width: 80px; height: 80px;">
                                        <h3 class="p-0 m-0">
                                            <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                        </h3>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="col-8 d-flex flex-column">
                                <!-- Employee details -->
                                <h5 class="card-title fw-bold employee-name">
                                    <?php
                                    echo $firstName . " " . $lastName;
                                    if (!empty($nickname)) {
                                        echo " (" . $nickname . ")";
                                    }
                                    ?>

                                    <?php if ($visaExpiryDate !== null) { ?>
                                        <?php
                                        // Set the timezone to Sydney
                                        $timezone = new DateTimeZone('Australia/Sydney');
                                        $today = new DateTime('now', $timezone);
                                        $today->setTime(0, 0, 0);

                                        $expiryDate = new DateTime($visaExpiryDate, $timezone);
                                        $expiryDate->setTime(0, 0, 0);

                                        // Calculate the difference in days between today and the visa expiry date
                                        $interval = $today->diff($expiryDate);
                                        $daysDifference = $interval->format('%r%a');

                                        if (!function_exists('dayText')) {
                                            function dayText($days)
                                            {
                                                return abs($days) == 1 ? 'day' : 'days';
                                            }
                                        }

                                        // Check if the expiry date is less than 30 days from today
                                        if ($daysDifference == 0) {
                                            echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" 
                                            data-bs-placement="top" title="Visa expired today"></i>';
                                        } else if ($daysDifference < 30 && $daysDifference >= 0) {
                                            echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Visa expired in ' . $daysDifference . ' ' . dayText($daysDifference) . '"></i>';
                                        } else if ($daysDifference < 0) {
                                            echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" 
                                            data-bs-placement="top" title="Visa expired ' . abs($daysDifference) . ' ' . dayText($daysDifference) . ' ago"></i>';
                                        }
                                        ?>
                                    <?php } ?>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted">Employee ID: <?php echo $employeeId; ?></h6>
                                <p class="d-none nickname"><?php echo $nickname ?></p>
                                <a
                                    href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?php echo $employeeId ?>">
                                    <button class="btn btn-dark btn-sm"><small>Profile <i
                                                class="fa-solid fa-up-right-from-square fa-sm"></i></small></button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div style="display: none" id="employeeTableList">
            <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
                <table class="table table-bordered table-striped table-hover print-employee-list" id="employeeTable">
                    <thead>
                        <tr>
                            <th class="py-3 align-middle text-center no-print"></th>
                            <th class="py-3 align-middle text-center employeeIdColumn employeeIdColumnPrint">Employee ID
                            </th>
                            <th class="py-3 align-middle text-center fullNameColumn">Name</th>
                            <th class="py-3 align-middle text-center nicknameColumn">Nickname</th>
                            <th class="py-3 align-middle text-center statusColumn">Status</th>
                            <th class="py-3 align-middle text-center departmentColumn">Department</th>
                            <th class="py-3 align-middle text-center payrollTypeColumn">Payroll Type</th>
                            <th class="py-3 align-middle text-center workShiftColumn">Work Shift</th>
                            <th class="py-3 align-middle text-center startDateColumn">Start Date</th>
                            <th class="py-3 align-middle text-center durationColumn">Duration</th>
                            <th class="py-3 align-middle text-center permanentDateColumn">Permanent Date</th>
                            <th class="py-3 align-middle text-center visaColumn">Visa</th>
                            <th class="py-3 align-middle text-center expiryDateColumn">Visa Expiry Date</th>
                            <th class="py-3 align-middle text-center lastDateColumn">Last Date</th>
                            <th class="py-3 align-middle text-center lockerNumberColumn">Locker Number</th>
                            <th class="py-3 align-middle text-center performanceReviewColumn">Performance Review</th>
                            <?php if ($role == "full control" || $role == "modify 1") { ?>
                                <th class="py-3 align-middle text-center wageSalaryColumn">Wage/Salary</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee) {
                            $profileImage = $employee['profile_image'];
                            $firstName = $employee['first_name'];
                            $lastName = $employee['last_name'];
                            $nickname = $employee['nickname'];
                            $departmentId = $employee['department_id'];
                            $departmentName = $employee['department_name'];
                            $payrollType = $employee['payroll_type'];
                            $startDate = $employee['start_date'];
                            $permanentDate = $employee['permanent_date'];
                            $lastDate = $employee['last_date'];
                            $visaName = $employee['visa_name'];
                            $employeeId = $employee['employee_id'];
                            $visaExpiryDate = $employee['visa_expiry_date'];
                            $isActive = $employee['is_active'];
                            $workShift = $employee['work_shift'];
                            $lockerNumber = $employee['locker_number'];
                            $payrollType = $employee['payroll_type'];

                            // Check if the role is not "admin" and the payroll type is "Salary"
                            if ($role !== 'full control' && $payrollType == 'salary') {
                                continue; // Skip this employee
                            }
                            ?>
                            <tr>
                                <td
                                    class="py-3 align-middle text-center no-print <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                    <a
                                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?php echo $employeeId ?>">
                                        <button class="btn text-dark"><small> <i
                                                    class="fa-solid fa-up-right-from-square"></i></small></button>
                                    </a>
                                </td>
                                <td
                                    class="align-middle text-center employeeIdColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                    <?php echo $employeeId ?>
                                </td>
                                <td
                                    class="align-middle text-center fullNameColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                    <?php echo $firstName . " " . $lastName ?>
                                </td>
                                <td class="align-middle text-center nicknameColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>"
                                    <?php if (!$nickname)
                                        echo 'style="background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;"'; ?>>
                                    <?php echo $nickname ? $nickname : "N/A" ?>
                                </td>
                                <td class="align-middle text-center statusColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>"
                                    ondblclick="editStatus(this)">
                                    <form method="POST" class="edit-status-form" style="display: none">
                                        <div class="d-flex align-items-center">
                                            <input type="hidden" name="empIdToEditStatusCell"
                                                value="<?php echo $employeeId ?>">
                                            <select name="statusCellToEdit" class="form-select"
                                                onchange="this.form.submit()">
                                                <option value="1" <?php if ($isActive == 1) {
                                                    echo 'selected';
                                                } ?>> Active
                                                </option>;
                                                <option value="0" <?php if ($isActive == 0) {
                                                    echo 'selected';
                                                } ?>> Inactive
                                                </option>;
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
                                    if ($isActive == 0) {
                                        echo '<span><small class="badge rounded-pill bg-danger mb-1">Inactive</small></span>';
                                    } else if ($isActive == 1) {
                                        echo '<span><small class="badge rounded-pill bg-success mb-1">Active</small></span>';
                                    }
                                    ?>
                                </td>
                                <td class="align-middle text-center departmentColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>"
                                    ondblclick="editDepartment(this)">
                                    <form method="POST" class="edit-department-form" style="display: none">
                                        <div class="d-flex align-items-center">
                                            <input type="hidden" name="empIdToEditDepartmentCell"
                                                value="<?php echo $employeeId ?>">
                                            <select name="departmentCellToEdit" class="form-select"
                                                onchange="this.form.submit()">
                                                <?php
                                                // Fetch departments from the database
                                                $department_sql = "SELECT department_id, department_name FROM department";
                                                $department_result = $conn->query($department_sql);

                                                if ($department_result->num_rows > 0) {
                                                    while ($row = $department_result->fetch_assoc()) {
                                                        // Check if the department ID matches $department_id and set 'selected' attribute
                                                        $selected = ($row['department_id'] == $departmentId) ? 'selected' : '';
                                                        echo '<option value="' . $row['department_id'] . '" ' . $selected . '>' . $row['department_name'] . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <a class="text-danger mx-2 text-decoration-none" style="cursor:pointer"
                                                onclick="cancelEditDepartment(this)">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </div>
                                            </a>
                                        </div>
                                    </form>
                                    <span><?php echo $departmentName ?></span>
                                </td>
                                <td
                                    class="align-middle text-center payrollTypeColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                    <?php echo ucwords($payrollType); ?>
                                </td>
                                <td class="align-middle text-center workShiftColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>"
                                    ondblclick="editWorkShift(this)" <?php if (!$workShift)
                                        echo 'style="background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;"'; ?>>

                                    <form method="POST" class="edit-work-shift-form" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <input type="hidden" name="empIdToEditWorkShiftCell" value="<?= $employeeId ?>">
                                            <select name="workShiftCellToEdit" class="form-select"
                                                onchange="this.form.submit()">
                                                <option value="Day" <?= ($workShift == "Day") ? 'selected' : '' ?>>Day</option>
                                                <option value="Evening" <?= ($workShift == "Evening") ? 'selected' : '' ?>>
                                                    Evening</option>
                                                <option value="Night" <?= ($workShift == "Night") ? 'selected' : '' ?>>Night
                                                </option>
                                            </select>
                                            <a class="text-danger mx-2 text-decoration-none" style="cursor:pointer"
                                                onclick="cancelEditWorkShift(this)">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </div>
                                            </a>
                                        </div>
                                    </form>

                                    <span><?= $workShift ? $workShift : "N/A" ?></span>
                                </td>

                                <td
                                    class="align-middle text-center startDateColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                    <?php echo date("d F Y", strtotime($startDate)); ?>
                                </td>

                                <?php
                                $startDateObj = new DateTime($startDate);

                                // Check if $lastDate has a value
                                if (!empty($lastDate)) {
                                    $lastDateObj = new DateTime($lastDate); // Convert lastDate to DateTime object
                                    $interval = $startDateObj->diff($lastDateObj); // Calculate difference until $lastDate
                                } else {
                                    $today = new DateTime(); // Get today's date
                                    $interval = $startDateObj->diff($today); // Calculate difference until today's date
                                }

                                $duration = "{$interval->y} years, {$interval->m} months, {$interval->d} days";
                                ?>

                                <td
                                    class="align-middle text-center durationColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                    <?php echo $duration; ?>
                                </td>

                                <td class="align-middle text-center permanentDateColumn" <?php
                                if (empty($permanentDate)) {
                                    echo 'style="font-weight: bold; color: white; background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px);"';
                                }
                                ?>>
                                    <?php
                                    if (!empty($permanentDate)) {
                                        echo date('d F Y', strtotime($permanentDate));
                                    } else {
                                        echo '<span class="text-white">N/A</span>';
                                    }
                                    ?>
                                </td>

                                <td
                                    class="align-middle text-center visaColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                    <?php echo $visaName ?>
                                </td>
                                <?php
                                date_default_timezone_set('Australia/Sydney'); // Set Australian timezone
                                $currentDate = date("Y-m-d");
                                $isExpired = $visaExpiryDate && strtotime($visaExpiryDate) < strtotime($currentDate);
                                ?>

                                <td class="align-middle text-center expiryDateColumn 
                                <?php
                                if (!$isActive && $isExpired) {
                                    echo 'bg-danger text-white'; // Solid red background, white text
                                } elseif (!$isActive) {
                                    echo 'bg-danger bg-opacity-25'; // Light red background for inactive users
                                } elseif ($isExpired) {
                                    echo 'bg-danger text-white'; // Solid red background for expired visa
                                }
                                ?>" <?php if ($visaName !== "Permanent Resident" && $visaName !== "Citizen"): ?>
                                        ondblclick="editExpiryDate(this)" <?php endif; ?>
                                    style="<?php echo (!$visaExpiryDate) ? 'font-weight: bold; color: white; background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px);' : ''; ?>">

                                    <form method="POST" class="edit-expiry-date-form" style="display: none">
                                        <div class="d-flex align-items-center">
                                            <input type="hidden" name="empIdToEditExpiryDateCell"
                                                value="<?php echo $employeeId ?>">
                                            <input type="date" name="expiryDateCellToEdit" class="form-control"
                                                value="<?php echo $visaExpiryDate ?>">
                                            <button class="btn btn-success text-decoration-none ms-2" style="cursor:pointer"
                                                type="submit">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-check"></i>
                                                </div>
                                            </button>
                                            <a class="btn text-danger bg-white mx-2 text-decoration-none"
                                                style="cursor:pointer" onclick="cancelEditExpiryDate(this)">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </div>
                                            </a>
                                        </div>
                                    </form>

                                    <span>
                                        <?php echo $visaExpiryDate ? date("d F Y", strtotime($visaExpiryDate)) : "N/A"; ?></span>
                                </td>
                                <td class="align-middle text-center lastDateColumn 
                                <?php
                                if (!empty($lastDate)) {
                                    echo 'bg-danger text-white';
                                } elseif ($isActive == 0) {
                                    echo 'bg-danger bg-opacity-25';
                                }
                                ?>" <?php
                                if (empty($lastDate)) {
                                    echo 'style="font-weight: bold; color: white; background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px);"';
                                }
                                ?>>
                                    <?php
                                    if (!empty($lastDate)) {
                                        echo date('d F Y', strtotime($lastDate));
                                    } else {
                                        echo '<span class="text-white">N/A</span>';
                                    }
                                    ?>
                                </td>

                                <td class="align-middle text-center lockerNumberColumn" <?php
                                if (empty($lockerNumber)) {
                                    echo 'style="font-weight: bold; color: white; background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px);"';
                                }
                                ?>>
                                    <?php
                                    if (!empty($lockerNumber)) {
                                        echo $lockerNumber;
                                    } else {
                                        echo '<span class="text-white">N/A</span>';
                                    }
                                    ?>
                                </td>
                                <td
                                    class="align-middle text-center performanceReviewColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>">
                                </td>
                                <?php if ($role == "full control" || $role == "modify 1") { ?>
                                    <td class="align-middle text-center payroll-cell wageSalaryColumn <?= $isActive == 0 ? 'bg-danger bg-opacity-25' : '' ?>"
                                        ondblclick="editWageSalary(this)">
                                        <?php if ($payrollType === "wage") { ?>
                                            <?php
                                            // Fetch the latest wage for this employee
                                            $wage_sql = "SELECT amount, date FROM wages 
                                                    WHERE employee_id = ? 
                                                    ORDER BY `date` DESC, wages_id DESC 
                                                    LIMIT 1";

                                            $stmt = $conn->prepare($wage_sql);
                                            $stmt->bind_param("i", $employeeId);
                                            $stmt->execute();
                                            $wage_result = $stmt->get_result();
                                            $wage_row = $wage_result->fetch_assoc();

                                            echo "<span>" . ($wage_row ? "$" . number_format($wage_row['amount'], 2) . "<span class='fw-bold'>/hr</span>" : "N/A") . "</span>";
                                            // Show wage or "N/A" if no wage data
                                            ?>
                                            <form method="POST" class="add-wage-form" style="display: none">
                                                <input type="hidden" name="empIdToEditWageCell" value="<?php echo $employeeId ?>">
                                                <div class="d-flex flex-column align-items-center justify-content-center">
                                                    <table class="table mb-1 pb-0">
                                                        <tr>
                                                            <th><small>Current Wage</small></th>
                                                            <th><small>Effective Date</small></th>
                                                        </tr>
                                                        <tr>
                                                            <td><small><?php echo isset($wage_row['amount']) && $wage_row['amount'] !== '' ? "$" . number_format($wage_row['amount'], 2) : 'N/A'; ?></small>
                                                            </td>
                                                            <td><small><?php echo isset($wage_row['date']) && !empty($wage_row['date']) ? date("d F Y", strtotime($wage_row['date'])) : 'N/A'; ?></small>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <div class="d-flex">
                                                        <div class="d-flex flex-column align-items-start m-1">
                                                            <label for="newWage" class="fw-bold"><small>New Wage</small></label>
                                                            <input type="number" name="wageCellToEdit" step="any"
                                                                class="form-control">
                                                        </div>
                                                        <div class="d-flex flex-column align-items-start m-1">
                                                            <label for="newWageDate" class="fw-bold"><small>Date</small></label>
                                                            <input type="date" name="wageDateCellToEdit" class="form-control"
                                                                value="<?php echo date('Y-m-d'); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="d-flex">
                                                        <button class="btn btn-sm btn-secondary me-1" type="button"
                                                            onclick="cancelEditWage(this)">Cancel</button>
                                                        <button class="btn btn-sm btn-success">Add</button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php } else if ($payrollType === "salary") { ?>
                                                <?php
                                                // Fetch the latest salary for this employee
                                                $salary_sql = "SELECT amount, `date` FROM salaries
                                                    WHERE employee_id = ? 
                                                    ORDER BY `date` DESC, salary_id DESC 
                                                    LIMIT 1";

                                                $stmt = $conn->prepare($salary_sql);
                                                $stmt->bind_param("i", $employeeId);
                                                $stmt->execute();
                                                $salary_result = $stmt->get_result();
                                                $salary_row = $salary_result->fetch_assoc();
                                                echo "<span>" . ($salary_row ? "$" . number_format($salary_row['amount'], 2) . "<span class='fw-bold'>/yr</span>" : "N/A") . "</span>";
                                                // Show salary or "N/A" if no salary data
                                                ?>
                                                <form method="POST" class="add-salary-form" style="display: none">
                                                    <input type="hidden" name="empIdToEditSalaryCell" value="<?php echo $employeeId ?>">
                                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                                        <table class="table mb-1 pb-0">
                                                            <tr>
                                                                <th><small>Current Salary</small></th>
                                                                <th><small>Effective Date</small></th>
                                                            </tr>
                                                            <tr>
                                                                <td><small><?php echo isset($salary_row['amount']) && !empty($salary_row['amount']) ? "$" . number_format($salary_row['amount'], 2) : 'N/A'; ?></small>
                                                                </td>
                                                                <td><small><?php echo isset($salary_row['date']) && !empty($salary_row['date']) ? date("d F Y", strtotime($salary_row['date'])) : 'N/A'; ?></small>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <div class="d-flex">
                                                            <div class="d-flex flex-column align-items-start m-1">
                                                                <label for="newSalary" class="fw-bold"> <small>New
                                                                        Salary</small></label>
                                                                <input type="number" name="salaryCellToEdit" step="any"
                                                                    class="form-control">
                                                            </div>
                                                            <div class="d-flex flex-column align-items-start m-1">
                                                                <label for="newSalaryDate" class="fw-bold"> <small>Date</small></label>
                                                                <input type="date" name="salaryDateCellToEdit" class="form-control"
                                                                    value="<?php echo date('Y-m-d') ?>">
                                                            </div>
                                                        </div>
                                                        <div class="d-flex">
                                                            <button class="btn btn-sm btn-secondary me-1" type="button" class="fw-bold"
                                                                onclick="cancelEditSalary(this)"><small>Cancel</small></button>
                                                            <button class="btn btn-sm btn-success">Add</button>
                                                        </div>
                                                    </div>
                                                </form>
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================= Add Employees Modal ============================= -->
    <div class="modal fade" id="addEmployeeModal" tabindex="1" aria-labelledby="addEmployeeModal" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require("../Form/EmployeeDetailsForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================= Filter Employees Modal ============================= -->
    <div class="modal fade" id="filterEmployeeModal" tabindex="1" aria-labelledby="filterEmployeeModal"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET">
                        <input type="hidden" id="viewInput" name="view">

                        <div class="row">
                            <div class="col-12  <?php echo ($role === "full control") ? "col-lg-3" : "col-lg-4"; ?>">
                                <h5 class="signature-color fw-bold">Department</h5>
                                <?php
                                $department_sql = "SELECT * FROM department";
                                $department_result = $conn->query($department_sql);
                                $selected_departments = isset($_GET['department']) ? $_GET['department'] : [];
                                if ($department_result->num_rows > 0) { ?>
                                    <?php while ($row = $department_result->fetch_assoc()) { ?>
                                        <p class="mb-0 pb-0">
                                            <input type="checkbox" class="form-check-input"
                                                id="department_<?php echo $row['department_id']; ?>" name="department[]"
                                                value="<?php echo $row['department_id']; ?>" <?php echo in_array($row['department_id'], $selected_departments) ? 'checked' : ''; ?> />
                                            <label
                                                for="department_<?php echo $row['department_id']; ?>"><?php echo $row['department_name']; ?></label>
                                        </p>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p>No departments found.</p>
                                <?php } ?>
                            </div>
                            <div
                                class="col-12  <?php echo ($role === "full control") ? "col-lg-2" : "col-lg-3"; ?> mt-3 mt-md-0">
                                <h5 class="signature-color fw-bold mt-4 mt-lg-0">Employment Type</h5>
                                <?php
                                $employment_types = ['Full-Time', 'Part-Time', 'Casual'];
                                $selected_employment_types = isset($_GET['employment_type']) ? $_GET['employment_type'] : [];
                                foreach ($employment_types as $type) {
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="<?php echo strtolower($type); ?>" name="employment_type[]"
                                            value="<?php echo $type; ?>" <?php echo in_array($type, $selected_employment_types) ? 'checked' : ''; ?>>
                                        <label for="<?php echo strtolower($type); ?>"><?php echo $type; ?></label>
                                    </p>
                                <?php } ?>


                                <h5 class="signature-color fw-bold mt-4">Section</h5>
                                <?php
                                $sections = ['Factory', 'Office'];
                                $selected_section = isset($_GET['section']) ? $_GET['section'] : [];
                                foreach ($sections as $section) {
                                    ?>
                                    <p class="mb-0 p-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="<?php echo strtolower($section) ?>" name="section[]"
                                            value="<?php echo $section ?>" <?php echo in_array($section, $selected_section) ? 'checked' : ''; ?>>
                                        <label for="<?php echo strtolower($section); ?>"><?php echo $section; ?></label>
                                    </p>
                                <?php } ?>

                                <h5 class="signature-color fw-bold mt-4">Active</h5>
                                <?php
                                $status = [1, 0];
                                $selected_status = isset($_GET['status']) ? $_GET['status'] : [];
                                foreach ($status as $i) {
                                    ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input" id="<?php echo $i ?>"
                                            name="status[]" value="<?php echo $i ?>" <?php echo in_array($i, $selected_status) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $i; ?>"><?php if ($i === 1) {
                                               echo "Active";
                                           } else if ($i === 0) {
                                               echo "Inactive";
                                           } ?></label>
                                    </p>
                                <?php } ?>
                                <h5 class="signature-color fw-bold mt-4">Work Shift</h5>
                                <?php
                                $workShifts = ['Day', 'Evening', 'Night'];
                                $selected_work_shift = isset($_GET['work_shift']) ? $_GET['work_shift'] : [];
                                foreach ($workShifts as $workShift) {
                                    ?>
                                    <p class="mb-0 p-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="<?php echo strtolower($workShift) ?>" name="work_shift[]"
                                            value="<?php echo $workShift ?>" <?php echo in_array($workShift, $selected_work_shift) ? 'checked' : ''; ?>>
                                        <label for="<?php echo strtolower($workShift); ?>"><?php echo $workShift; ?></label>
                                    </p>
                                <?php } ?>
                            </div>



                            <div class="col-12 <?php echo ($role === "full control") ? "col-lg-4" : "col-lg-5"; ?>">
                                <h5 class="signature-color fw-bold mt-4 mt-lg-0">Visa</h5>
                                <?php
                                $visa_sql = "SELECT * FROM visa";
                                $visa_result = $conn->query($visa_sql);
                                $selected_visas = isset($_GET['visa']) ? $_GET['visa'] : [];
                                if ($visa_result->num_rows > 0) { ?>
                                    <?php while ($row = $visa_result->fetch_assoc()) { ?>
                                        <p class="mb-0 pb-0">
                                            <input type="checkbox" class="form-check-input"
                                                id="visa_<?php echo $row['visa_id']; ?>" name="visa[]"
                                                value="<?php echo $row['visa_id']; ?>" <?php echo in_array($row['visa_id'], $selected_visas) ? 'checked' : ''; ?> />
                                            <label
                                                for="visa_<?php echo $row['visa_id']; ?>"><?php echo $row['visa_name']; ?></label>
                                        </p>
                                    <?php } ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input" id="expired" name="expiredVisa"
                                            value="Expired" <?php echo isset($_GET['expiredVisa']) ? 'checked' : ''; ?>>
                                        <label class="text-danger fw-bold" for="expired">Expired</label>
                                    </p>
                                <?php } else { ?>
                                    <p>No visa found.</p>
                                <?php } ?>
                            </div>

                            <?php if ($role === "full control") { ?>
                                <div
                                    class="col-12 col-md-6 <?php echo ($role === "full control") ? "col-lg-3" : "col-lg-4"; ?> mt-3 mt-lg-0">
                                    <h5 class="signature-color fw-bold mt-4 mt-md-0">Payroll Type</h5>
                                    <?php
                                    $payroll_types = ['wage', 'salary'];
                                    $selected_payroll_types = isset($_GET['payroll_type']) ? $_GET['payroll_type'] : [];
                                    foreach ($payroll_types as $type) {
                                        ?>
                                        <p class="mb-0 pb-0">
                                            <input type="checkbox" class="form-check-input" id="<?php echo $type; ?>"
                                                name="payroll_type[]" value="<?php echo $type; ?>" <?php echo in_array($type, $selected_payroll_types) ? 'checked' : ''; ?>>
                                            <label for="<?php echo $type; ?>"><?php echo ucfirst($type); ?></label>
                                        </p>
                                    <?php } ?>
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

    <!-- ================== Filter Column Modal ================== -->
    <div class="modal fade" id="filterTableColumnModal" tabindex="-1" aria-labelledby="filterTableColumnModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> Filter Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="employeeIdColumn"
                            data-column="employeeIdColumn">
                        <label class="form-check-label" for="employeeIdColumn">
                            Employee Id
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="fullNameColumn"
                            data-column="fullNameColumn">
                        <label class="form-check-label" for="fullNameColumn">
                            Name
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="nicknameColumn"
                            data-column="nicknameColumn">
                        <label class="form-check-label" for="nicknameColumn">
                            Nickname
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="statusColumn"
                            data-column="statusColumn">
                        <label class="form-check-label" for="statusColumn">
                            Status
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="departmentColumn"
                            data-column="departmentColumn">
                        <label class="form-check-label" for="departmentColumn">
                            Department
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="statusColumn"
                            data-column="statusColumn">
                        <label class="form-check-label" for="statusColumn">
                            Status
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="workShiftColumn"
                            data-column="workShiftColumn">
                        <label class="form-check-label" for="workShiftColumn">
                            Work Shift
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="startDateColumn"
                            data-column="startDateColumn">
                        <label class="form-check-label" for="startDateColumn">
                            Start Date
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="durationColumn"
                            data-column="durationColumn">
                        <label class="form-check-label" for="durationColumn">
                            Duration
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="permanentDateColumn"
                            data-column="permanentDateColumn">
                        <label class="form-check-label" for="permanentDateColumn">
                            Permanent Date
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="visaColumn"
                            data-column="visaColumn">
                        <label class="form-check-label" for="visaColumn">
                            Visa
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="expiryDateColumn"
                            data-column="expiryDateColumn">
                        <label class="form-check-label" for="expiryDateColumn">
                            Visa Expiry Date
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="lastDateColumn"
                            data-column="lastDateColumn">
                        <label class="form-check-label" for="lastDateColumn">
                            Last Date
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="lockerNumberColumn"
                            data-column="lockerNumberColumn">
                        <label class="form-check-label" for="lockerNumberColumn">
                            Locker Number
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input column-check-input" type="checkbox" id="performanceReviewColumn"
                            data-column="performanceReviewColumn">
                        <label class="form-check-label" for="performanceReviewColumn">
                            Performance Review
                        </label>
                    </div>
                    <?php if ($role == "full control" || $role == "modify 1") { ?>
                        <div class="form-check">
                            <input class="form-check-input column-check-input" type="checkbox" id="wageSalaryColumn"
                                data-column="wageSalaryColumn">
                            <label class="form-check-label" for="wageSalaryColumn">
                                Wage/Salary
                            </label>
                        </div>
                    <?php } ?>
                    <div class="d-flex justify-content-end" style="cursor:pointer">
                        <button onclick="resetColumnFilter()" class="btn btn-sm btn-danger me-1"> Reset Filter</button>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== HR Dashboard Modal ================== -->
    <div class="modal fade" id="HRDashboardModal" tabindex="-1" aria-labelledby="HRDashboardModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="HRDashboardModalLabel">Human Resources Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body background-color">
                    <?php require_once("../PageContent/hr-index-content.php") ?>
                </div>
            </div>
        </div>
    </div>

</body>

<script>
    // Add an event listener to the search input to detect the " Enter" key
    document.getElementById('searchDocuments').addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { // Prevent default form submission (if needed) and manually trigger the form submit
            event.preventDefault(); document.getElementById('searchForm').submit();
        }
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
    function setSort(sort, order) {
        // Get the current URL
        var url = new URL(window.location.href);

        // Update the 'sort' and 'order' parameters in the URL
        url.searchParams.set('sort', sort);
        url.searchParams.set('order', order);

        // Redirect to the updated URL
        window.location.href = url.toString();
    }

</script>
<script>
    // Enabling the tooltip
    const tooltips = document.querySelectorAll('.tooltips');
    tooltips.forEach(t => {
        new bootstrap.Tooltip(t);
    })
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const employeeCardList = document.getElementById("employeeCardList");
        const employeeTableList = document.getElementById("employeeTableList");
        const viewInput = document.getElementById("viewInput");
        const toggleViewBtn = document.getElementById("toggleButton")
        const filterColumnBtn = document.getElementById("filterColumnBtn");

        // Check the URL for a view preference
        const urlParams = new URLSearchParams(window.location.search);
        let viewPreference = urlParams.get("view") || "card"; // Default to "card"

        updateDisplay(viewPreference);
        viewInput.value = viewPreference; // Update the hidden input value


        document.getElementById("toggleButton").addEventListener("click", function () {
            viewPreference = viewPreference === "card" ? "table" : "card";
            updateDisplay(viewPreference);

            // Update the URL parameter without reloading
            urlParams.set("view", viewPreference);
            window.history.replaceState(null, "", "?" + urlParams.toString());

            // Update the hidden input field
            viewInput.value = viewPreference;
        });

        function updateDisplay(view) {
            document.getElementById("viewInput").value = view;
            document.getElementById("viewInput2").value = view;
            toggleViewBtn.innerHTML =
                (viewPreference === "table"
                    ? "<small> Card </small> <i class='fa-solid fa-address-card ms-1'></i>"
                    : "<small> Table </small> <i class='fa-solid fa-table ms-1'></i>");

            if (view === "card") {
                employeeCardList.style.display = "flex";
                employeeTableList.style.display = "none";
                filterColumnBtn.style.display = "none";
            } else {
                employeeCardList.style.display = "none";
                employeeTableList.style.display = "flex";
                filterColumnBtn.style.display = "flex";
            }
        }
    });
</script>
<script>
    const STORAGE_EXPIRATION_TIME = 8 * 60 * 60 * 1000; // 8 hours in milliseconds

    // Save checkbox state to localStorage with a timestamp
    document.querySelectorAll('.column-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const columnClass = this.getAttribute('data-column');
            const columns = document.querySelectorAll(`.${columnClass}`);

            if (this.checked) {
                columns.forEach(column => column.style.display = '');
                localStorage.setItem(columnClass, 'visible');
            } else {
                columns.forEach(column => column.style.display = 'none');
                localStorage.setItem(columnClass, 'hidden');
            }

            localStorage.setItem(columnClass + '_timestamp', Date.now()); // Save timestamp
        });
    });

    // Initialize checkboxes based on localStorage
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.column-check-input').forEach(checkbox => {
            const columnClass = checkbox.getAttribute('data-column');
            const columns = document.querySelectorAll(`.${columnClass}`);

            const storedVisibility = localStorage.getItem(columnClass);
            const storedTimestamp = localStorage.getItem(columnClass + '_timestamp');
            const currentTime = Date.now();

            // Ensure the timestamp is valid
            if (storedTimestamp && (currentTime - storedTimestamp <= STORAGE_EXPIRATION_TIME)) {
                if (storedVisibility === 'hidden') {
                    columns.forEach(column => column.style.display = 'none');
                    checkbox.checked = false; // Keep it unchecked
                } else {
                    columns.forEach(column => column.style.display = '');
                    checkbox.checked = true;
                }
            } else {
                // Default behavior (unchecked for Wage/Salary, checked for others)
                if (columnClass === "wageSalaryColumn") {
                    columns.forEach(column => column.style.display = 'none'); // Hide initially
                    checkbox.checked = false;
                } else {
                    columns.forEach(column => column.style.display = '');
                    checkbox.checked = true;
                }

                localStorage.removeItem(columnClass);
                localStorage.removeItem(columnClass + '_timestamp');
            }
        });
    });
</script>

<script>
    function resetColumnFilter() {
        // Get all checkboxes
        document.querySelectorAll('.column-check-input').forEach(checkbox => {
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
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".payroll-cell").forEach(cell => {
            if (cell.innerText.trim() === "N/A") {
                cell.style.background = "repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px)";
                cell.style.color = "white";
                cell.style.fontWeight = "bold";
            }
        });
    });
</script>


<script>
    function editDepartment(cell) {
        // Get the form and span elements
        const form = cell.querySelector('.edit-department-form');
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

    function cancelEditDepartment(link) {
        //Find the closes <td> element
        const cell = link.closest('td');
        if (!cell) return;

        // Find the form and span within the <td>
        const form = cell.querySelector('.edit-department-form');
        const span = cell.querySelector('span');

        if (form && span) {
            // Toggle visibility of form and span
            form.style.display = 'none';
            span.style.display = 'inline-block';
        }
    }

    function editExpiryDate(cell) {
        // Get the form, span elements, and current background style
        const form = cell.querySelector('.edit-expiry-date-form');
        const span = cell.querySelector('span');

        // Toggle the form visibility
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
            span.style.display = 'none';

            // Remove background when editing
            cell.dataset.originalBackground = cell.style.background; // Store original background
            cell.style.background = 'none';

            form.querySelector('input[type="date"]').focus();
        } else {
            form.style.display = 'none';
            span.style.display = 'block';

            // Restore original background
            cell.style.background = cell.dataset.originalBackground || '';
        }
    }

    function cancelEditExpiryDate(link) {
        // Get the closest td element
        const cell = link.closest('td');

        // Get the form and span elements
        const form = cell.querySelector('.edit-expiry-date-form');
        const span = cell.querySelector('span');

        // Hide the form, show the span
        form.style.display = 'none';
        span.style.display = 'block';

        // Restore the original background
        cell.style.background = cell.dataset.originalBackground || '';
    }

    function editWageSalary(cell) {
        // Get the form, span elements, and current background style
        const wageForm = cell.querySelector('.add-wage-form');
        const salaryForm = cell.querySelector('.add-salary-form');
        const span = cell.querySelector('span');

        // Check which form exists in the cell
        const isWage = wageForm !== null;
        const isSalary = salaryForm !== null;

        // Determine which form to show
        if (isWage && (wageForm.style.display === 'none' || wageForm.style.display === '')) {
            wageForm.style.display = 'block';
            if (span) span.style.display = 'none';

            // Store original styles
            cell.dataset.originalBackground = cell.style.background || '';
            cell.dataset.originalColor = cell.style.color || '';

            // Remove background and text color
            cell.style.background = 'none';
            cell.style.color = 'inherit';
        } else if (isSalary && (salaryForm.style.display === 'none' || salaryForm.style.display === '')) {
            salaryForm.style.display = 'block';
            if (span) span.style.display = 'none';

            // Store original styles
            cell.dataset.originalBackground = cell.style.background || '';
            cell.dataset.originalColor = cell.style.color || '';

            // Remove background and text color
            cell.style.background = 'none';
            cell.style.color = 'inherit';
        } else {
            // Hide both forms if either is open
            if (isWage) wageForm.style.display = 'none';
            if (isSalary) salaryForm.style.display = 'none';
            if (span) span.style.display = 'block';

            // Restore original styles
            cell.style.background = cell.dataset.originalBackground || '';
            cell.style.color = cell.dataset.originalColor || '';
        }
    }

    function cancelEditWage(link) {
        // Get the closest td element
        const cell = link.closest('td');

        // Get the form and span elements
        const form = cell.querySelector('.add-wage-form');
        const span = cell.querySelector('span');

        // Hide the form, show the span
        form.style.display = 'none';
        span.style.display = 'block';

        // Restore the original background
        cell.style.background = cell.dataset.originalBackground || '';
        cell.style.color = cell.dataset.originalColor || '';
    }

    function cancelEditSalary(link) {
        // Get the closest td element
        const cell = link.closest('td');

        // Get the form and span elements
        const form = cell.querySelector('.add-salary-form');
        const span = cell.querySelector('span');

        // Hide the form, show the span
        form.style.display = 'none';
        span.style.display = 'block';

        // Restore the original background
        cell.style.background = cell.dataset.originalBackground || '';
        cell.style.color = cell.dataset.originalColor || '';
    }

    function editStatus(cell) {
        // Get the form and span elements
        const form = cell.querySelector('.edit-status-form');
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

    function cancelEditStatus(link) {
        // Get the closest td element
        const cell = link.closest('td');

        // Get the form and span elements
        const form = cell.querySelector('.edit-status-form');
        const span = cell.querySelector('span');

        // Hide the form, show the span
        form.style.display = 'none';
        span.style.display = 'block';

        // Restore the original background
        cell.style.background = cell.dataset.originalBackground || '';
        cell.style.color = cell.dataset.originalColor || '';
    }

    function editWorkShift(cell) {
        // Get the form and span elements
        const form = cell.querySelector('.edit-work-shift-form');
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

    function cancelEditWorkShift(link) {
        // Get the closest td element
        const cell = link.closest('td');

        // Get the form and span elements
        const form = cell.querySelector('.edit-work-shift-form');
        const span = cell.querySelector('span');

        // Hide the form, show the span
        form.style.display = 'none';
        span.style.display = 'block';

        // Restore the original background
        cell.style.background = cell.dataset.originalBackground || '';
        cell.style.color = cell.dataset.originalBackground || '';
    }
</script>

<script>
    function printTable() {
        var tableContent = document.getElementById("employeeTable").outerHTML;
        var printWindow = window.open("", "", "width=1200,height=2000");

        printWindow.document.write(`
            <html>
            <head>
                <title>Print Employee List</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
                <style>
                    @media print {
                        .no-print {
                            display: none !important;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        th {
                            background-color: #043f9d !important;
                            color: white !important;
                        }
                        th, td {
                            border: 1px solid black;
                            padding: 8px;
                            text-align: left;
                        }
                        thead {
                            display: table-row-group;
                        }
                    }
                </style>
            </head>
            <body>
                ${tableContent}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() { window.close(); };
                    };
                <\/script>
            </body>
            </html>
        `);

        printWindow.document.close();
    }
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