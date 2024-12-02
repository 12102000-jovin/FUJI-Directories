<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
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
    employee_id LIKE '%$searchTerm%')";

// Arrays to hold selected filter values
$selected_departments = [];
$selected_employment_types = [];
$selected_visa = [];
$selected_payroll_types = [];
$selected_status = [];

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
$employee_list_sql = "SELECT * FROM employees WHERE $whereClause ORDER BY $sort $order";

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

// If there are payroll_type filters, bind them to the prepared statement
if (!empty($selected_payroll_types)) {
    $types .= str_repeat('s', count($selected_payroll_types)); // 's' for string binding
}

// If there are payroll_type filters, bind them to the prepared statement
if (!empty($selected_status)) {
    $types .= str_repeat('i', count($selected_status)); // 'i' for integer binding
}

// If expiredVisa filter is applied, bind the current date
if (isset($_GET['expiredVisa'])) {
    $types .= 's'; // 's' for string (date format)
}

// Bind parameters only if there are types to bind
$params = array_merge($selected_departments, $selected_employment_types, $selected_visa, $selected_payroll_types, $selected_status);
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

// Get total count of filtered results (count query)
$count_sql = "SELECT COUNT(*) as total FROM employees WHERE $whereClause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_count = $count_row['total'];

// Get all URL parameters from $_GET
$urlParams = $_GET;
?>

<head>
    <title>Employees</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body class="background-color">
    <div class="container-fluid">
        <div class="row d-flex justify-content-between align-items-center">
            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/hr-index.php">HR
                                Dashboard</a></li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">All
                            Employees
                        </li>
                    </ol>
                </nav>
            </div>
            <?php if ($role == "admin") { ?>
                <div class="col-md-6 d-flex justify-content-start justify-content-md-end align-items-center mt-3 mt-md-0">
                    <a class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
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

                    if ($key === 'order') {
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
                    if ($key === 'search'){ 
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
                    }else if ($key === 'department' && is_array($value)) {
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
                    } else if ($key === 'status' && is_array($value)) { 
                        foreach ($value as $status){ 
                            ?>
                                <span class="badge rounded-pill signature-bg-color text-white me-2 mb-2">
                                    <strong><?php if ($status === "1") { echo "Active"; } else if ($status === "0")  {echo "Inactive"; } ?></strong>
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
            <?php if ($total_count > 0): ?>
                <div class="alert alert-info">
                    <strong>Total Results:</strong> <?php echo $total_count; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>No results found for the selected filters.</strong>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($employees as $employee) {
                $profileImage = $employee['profile_image'];
                $firstName = $employee['first_name'];
                $lastName = $employee['last_name'];
                $nickname = $employee['nickname'];
                $employeeId = $employee['employee_id'];
                $visaExpiryDate = $employee['visa_expiry_date'];
                $isActive = $employee['is_active'];
                $payrollType = $employee['payroll_type'];

                // Check if the role is not "admin" and the payroll type is "Salary"
                if ($role !== 'admin' && $payrollType == 'salary') {
                    continue; // Skip this employee
                }
                ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <div class="card positive-relative" style="min-height:140px">
                        <?php if ($isActive == 0) { ?>
                            <span class="badge rounded-pill bg-danger position-absolute bottom-0 end-0 m-2">Inactive</span>
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
                    <div class="row">
                        <div class="col-12  <?php echo ($role === "admin") ? "col-lg-3" : "col-lg-4"; ?>">
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
                                            value="<?php echo $row['department_id']; ?>" 
                                            <?php echo in_array($row['department_id'], $selected_departments) ? 'checked' : ''; ?> />
                                        <label for="department_<?php echo $row['department_id']; ?>"><?php echo $row['department_name']; ?></label>
                                    </p>
                                <?php } ?>
                            <?php } else { ?>
                                <p>No departments found.</p>
                            <?php } ?>
                        </div>
                        <div class="col-12  <?php echo ($role === "admin") ? "col-lg-3" : "col-lg-4"; ?> mt-3 mt-md-0">
                            <h5 class="signature-color fw-bold mt-4 mt-lg-0">Employment Type</h5>
                            <?php
                            $employment_types = ['Full-Time', 'Part-Time', 'Casual'];
                            $selected_employment_types = isset($_GET['employment_type']) ? $_GET['employment_type'] : [];
                            foreach ($employment_types as $type) {
                            ?>
                                <p class="mb-0 pb-0">
                                    <input type="checkbox" class="form-check-input" id="<?php echo strtolower($type); ?>"
                                        name="employment_type[]" value="<?php echo $type; ?>"
                                        <?php echo in_array($type, $selected_employment_types) ? 'checked' : ''; ?>>
                                    <label for="<?php echo strtolower($type); ?>"><?php echo $type; ?></label>
                                </p>
                            <?php } ?>
                        </div>
                        <div class="col-12 <?php echo ($role === "admin") ? "col-lg-3" : "col-lg-4"; ?>">
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
                                            value="<?php echo $row['visa_id']; ?>"
                                            <?php echo in_array($row['visa_id'], $selected_visas) ? 'checked' : ''; ?> />
                                        <label for="visa_<?php echo $row['visa_id']; ?>"><?php echo $row['visa_name']; ?></label>
                                    </p>
                                <?php } ?>
                                <p class="mb-0 pb-0">
                                    <input type="checkbox" class="form-check-input" id="expired" name="expiredVisa"
                                        value="Expired" 
                                        <?php echo isset($_GET['expiredVisa']) ? 'checked' : ''; ?>>
                                    <label class="text-danger fw-bold" for="expired">Expired</label>
                                </p>
                            <?php } else { ?>
                                <p>No visa found.</p>
                            <?php } ?>
                        </div>

                        <?php if ($role === "admin") { ?>
                            <div
                                class="col-12 col-md-6 <?php echo ($role === "admin") ? "col-lg-3" : "col-lg-4"; ?> mt-3 mt-lg-0">
                                <h5 class="signature-color fw-bold mt-4 mt-md-0">Payroll Type</h5>
                                <?php
                                $payroll_types = ['wage', 'salary'];
                                $selected_payroll_types = isset($_GET['payroll_type']) ? $_GET['payroll_type'] : [];
                                foreach ($payroll_types as $type) {
                                ?>
                                    <p class="mb-0 pb-0">
                                        <input type="checkbox" class="form-check-input" id="<?php echo $type; ?>"
                                            name="payroll_type[]" value="<?php echo $type; ?>"
                                            <?php echo in_array($type, $selected_payroll_types) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $type; ?>"><?php echo ucfirst($type); ?></label>
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
                                            name="status[]" value="<?php echo $i ?>"
                                            <?php echo in_array($i, $selected_status) ? 'checked' : ''; ?>>
                                        <label for="<?php echo $i; ?>"><?php if ($i === 1) { echo "Active"; } else if ($i === 0)  {echo "Inactive"; } ?></label>
                                    </p>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <div class="d-flex justify-content-center mt-4">
                            <button class="btn btn-secondary me-1" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-dark" type="submit" name="apply_filters">Apply Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>

<script>
    // Add an event listener to the search input to detect the " Enter" key
    document.getElementById('searchDocuments').addEventListener('keydown', function (event) {
        if
            (event.key === 'Enter') { // Prevent default form submission (if needed) and manually trigger
                            the form submit event.preventDefault(); document.getElementById('searchForm').submit();
        }
    }); </script>

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
</body>