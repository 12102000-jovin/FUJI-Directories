<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once ("../db_connect.php");

$config = include ('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrieve session data
$role = $_SESSION['role'] ?? '';

// Default query to fetch all employees
$employee_list_sql = "SELECT * FROM employees";
$employee_list_result = $conn->query($employee_list_sql);

// Check if there are any employees
if ($employee_list_result->num_rows > 0) {
    // Fetch all employees into an array
    $employees = [];
    while ($row = $employee_list_result->fetch_assoc()) {
        $employees[] = $row;
    }
} else {
    $employees = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Employees</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .card {
            height: 100%;
        }

        .nav-pills .nav-link.active,
        .nav-pills .show>.nav-link {
            background-color: #eef3f9 !important;
            /* Background color for active tab */
            color: #043f9d !important;
            font-weight: bold;
            border-bottom: 2px solid #043f9d !important;
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
    </style>
    <script>
        // Initial setup to show 8 employees on each tab
        document.addEventListener('DOMContentLoaded', function () {
            initializeEmployees('all-employees', 8);
            initializeEmployees('active-employees', 8);
            initializeEmployees('inactive-employees', 8);
            initializeEmployees('wages-employees', 8);
            initializeEmployees('salary-employees', 8);
        });
    </script>
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
            <div class="col-md-6 d-flex justify-content-start justify-content-md-end align-items-center mt-3 mt-md-0">
                <a class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fa-solid fa-user-plus"></i> Add Employee
                </a>
            </div>
        </div>

        <hr />
        <div class="row d-flex align-items-center justify-content-center">
            <div class="col-12 col-md-6">
                <ul class="nav nav-pills mb-3" id="employee-filter" role="tablist">
                    <?php if ($role == "admin") { ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-dark rounded-0" id="all-employees-tab" data-bs-toggle="pill"
                                data-bs-target="#all-employees" type="button" role="tab" aria-controls="all-employees"
                                aria-selected="true"><small>All Employees</small></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-dark rounded-0" id="active-employees-tab" data-bs-toggle="pill"
                                data-bs-target="#active-employees" type="button" role="tab" aria-controls="active-employees"
                                aria-selected="false"><small>Active Employees</small></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-dark rounded-0" id="inactive-employees-tab" data-bs-toggle="pill"
                                data-bs-target="#inactive-employees" type="button" role="tab"
                                aria-controls="inactive-employees" aria-selected="false"><small>Inactive
                                    Employees</small>
                            </button>
                        </li>
                    <?php } ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $role === "general" ? "active" : "" ?> text-dark rounded-0"
                            id="wages-employees-tab" data-bs-toggle="pill" data-bs-target="#wages-employees"
                            type="button" role="tab" aria-controls="wages-employees"
                            aria-selected="false"><small><?php echo $role === "general" ? "All Employees" : "Wage Employees" ?></small></button>
                    </li>
                    <?php if ($role == "admin") { ?>
                        <li class="nav-item" role="presentaion">
                            <button class="nav-link text-dark rounded-0" id="salary-employees-tab" data-bs-toggle="pill"
                                data-bs-target="#salary-employees" type="button" role="tab" aria-controls="salary-employees"
                                aria-selected="false"><small>Salary Employees</small>
                            </button>
                        </li>
                    <?php } ?>
                </ul>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-start">
                    <div class="input-group mb-3 me-2">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" class="form-control" id="searchEmployee" name="searchEmployee"
                            placeholder="Search Employee" oninput="filterEmployees(this.value)">
                    </div>
                    <div class="d-flex">
                        <div class="dropdown">
                            <button class="btn text-white dropdown-toggle" data-bs-toggle="dropdown"
                                aria-expanded="false" style="background-color:#043f9d;">
                                <small class="text-nowrap fw-bold">Sort by</small>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="sortByName('asc', 'all-employees')">Name
                                        <i class="fa-solid fa-arrow-down-a-z" style="color:#043f9d"></i></a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="sortByName('desc', 'all-employees')">Name
                                        <i class="fa-solid fa-arrow-down-z-a" style="color:#043f9d"></i></a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="sortByEmployeeID('asc', 'all-employees')">Id <i
                                            class="fa-solid fa-arrow-down-1-9" style="color:#043f9d"></i></a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="sortByEmployeeID('desc', 'all-employees')">Id <i
                                            class="fa-solid fa-arrow-down-9-1" style="color:#043f9d"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="employee-list">

            <?php if ($role == "admin") { ?>
                <!-- Tab panes for employees -->
                <div class="tab-pane fade show active" id="all-employees" role="tabpanel"
                    aria-labelledby="all-employees-tab">
                    <div class="row row-cols-1 row-cols-md-3 g-4" id="employee-cards">
                        <?php foreach ($employees as $employee) {
                            $profileImage = $employee['profile_image'];
                            $firstName = $employee['first_name'];
                            $lastName = $employee['last_name'];
                            $nickname = $employee['nickname'];
                            $employeeId = $employee['employee_id'];
                            $visaExpiryDate = $employee['visa_expiry_date'];
                            $isActive = $employee['is_active'];
                            $payrollType = $employee['payroll_type'];
                            ?>
                            <div class="col-12 col-sm-6 col-md-3 employee-card">
                                <div class="card position-relative">
                                    <?php if ($isActive == 0) { ?>
                                        <span
                                            class="badge rounded-pill bg-danger position-absolute top-0 start-0 m-2">Inactive</span>
                                    <?php } ?>
                                    <div
                                        class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
                                        <?php if (!empty($profileImage)) { ?>
                                            <!-- Profile image -->
                                            <div class="bg-gradient shadow-lg rounded-circle"
                                                style="width: 100px; height: 100px; overflow: hidden;">
                                                <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                                    class="profile-pic img-fluid rounded-circle"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        <?php } else { ?>
                                            <!-- Initials -->
                                            <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                                style="width: 100px; height: 100px;">
                                                <h3 class="p-0 m-0">
                                                    <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                                </h3>
                                            </div>
                                        <?php } ?>
                                        <!-- Employee details -->
                                        <h5 class="card-title fw-bold mt-2 employee-name">
                                            <?php echo $firstName . " " . $lastName; ?>
                                            <?php if ($visaExpiryDate !== null) { ?>
                                                <?php
                                                $today = new DateTime();
                                                $expiryDate = new DateTime($visaExpiryDate);
                                                $interval = $today->diff($expiryDate);
                                                $daysDifference = $interval->format('%r%a');

                                                // Check if the expiry date is less than 30 days from today
                                                if ($daysDifference < 30 && $daysDifference >= 0) {
                                                    echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" 
                                        data-bs-placement="top" title="Visa expired in ' . $daysDifference . ' days "></i>';
                                                } else if ($daysDifference < 0) {
                                                    echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" 
                                                data-bs-placement="top" title="Visa expired ' . abs($daysDifference) . ' days ago"></i>';
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
                        <?php } ?>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-outline-primary see-more-button"
                            style="border: 1px solid #043f9d; color: #043f9d;"
                            onmouseover="this.style.backgroundColor='#043f9d'; this.style.color='white';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#043f9d';"
                            onclick="loadMoreEmployees('all-employees', 8)">Load
                            More</button>
                    </div>
                </div>

                <!-- Tab panes for active-employees -->
                <div class="tab-pane fade" id="active-employees" role="tabpanel" aria-labelledby="active-employees-tab">
                    <div class="row row-cols-1 row-cols-md-3 g-4" id="active-employee-cards">
                        <?php foreach ($employees as $employee) {
                            if ($employee['is_active']) { // Assuming 'is_active' field in your database
                                $profileImage = $employee['profile_image'];
                                $firstName = $employee['first_name'];
                                $lastName = $employee['last_name'];
                                $employeeId = $employee['employee_id'];
                                $isActive = $employee['is_active'];
                                $nickname = $employee['nickname'];
                                ?>
                                <div class="col-12 col-sm-6 col-md-3 employee-card">
                                    <div class="card">
                                        <?php if ($isActive == 0) { ?>
                                            <span
                                                class="badge rounded-pill bg-danger position-absolute top-0 start-0 m-2">Inactive</span>
                                        <?php } ?>
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
                                            <?php if (!empty($profileImage)) { ?>
                                                <!-- Profile image -->
                                                <div class="bg-gradient shadow-lg rounded-circle"
                                                    style="width: 100px; height: 100px; overflow: hidden;">
                                                    <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                                        class="profile-pic img-fluid rounded-circle"
                                                        style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                            <?php } else { ?>
                                                <!-- Initials -->
                                                <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                                    style="width: 100px; height: 100px;">
                                                    <h3 class="p-0 m-0">
                                                        <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                                    </h3>
                                                </div>
                                            <?php } ?>
                                            <!-- Employee details -->
                                            <h5 class="card-title fw-bold mt-2 employee-name">
                                                <?php echo $firstName . " " . $lastName; ?>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 text-muted">Employee ID: <?php echo $employeeId; ?></h6>
                                            <p class="nickname"><?php echo $nickname ?></p>
                                            <a
                                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?php echo $employeeId ?>">
                                                <button class="btn btn-dark btn-sm"><small>Profile <i
                                                            class="fa-solid fa-up-right-from-square fa-sm"></i></small></button>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php }
                        } ?>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-outline-primary see-more-button"
                            style="border: 1px solid #043f9d; color: #043f9d;"
                            onmouseover="this.style.backgroundColor='#043f9d'; this.style.color='white';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#043f9d';"
                            onclick="loadMoreEmployees('active-employees', 8)">Load More</button>
                    </div>
                </div>

                <!-- Tab panes for inactive-employees -->
                <div class="tab-pane fade" id="inactive-employees" role="tabpanel" aria-labelledby="inactive-employees-tab">
                    <div class="row row-cols-1 row-cols-md-3 g-4" id="inactive-employee-cards">
                        <?php foreach ($employees as $employee) {
                            if (!$employee['is_active']) { // Assuming 'is_active' field in your database
                                $profileImage = $employee['profile_image'];
                                $firstName = $employee['first_name'];
                                $lastName = $employee['last_name'];
                                $employeeId = $employee['employee_id'];
                                $isActive = $employee['is_active'];
                                $nickname = $employee['nickname'];
                                ?>
                                <div class="col-12 col-sm-6 col-md-3 employee-card">
                                    <div class="card position-relative">
                                        <?php if ($isActive == 0) { ?>
                                            <span
                                                class="badge rounded-pill bg-danger position-absolute top-0 start-0 m-2">Inactive</span>
                                        <?php } ?>
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
                                            <?php if (!empty($profileImage)) { ?>
                                                <!-- Profile image -->
                                                <div class="bg-gradient shadow-lg rounded-circle"
                                                    style="width: 100px; height: 100px; overflow: hidden;">
                                                    <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                                        class="profile-pic img-fluid rounded-circle"
                                                        style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                            <?php } else { ?>
                                                <!-- Initials -->
                                                <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                                    style="width: 100px; height: 100px;">
                                                    <h3 class="p-0 m-0">
                                                        <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                                    </h3>
                                                </div>
                                            <?php } ?>
                                            <!-- Employee details -->
                                            <h5 class="card-title fw-bold mt-2 employee-name">
                                                <?php echo $firstName . " " . $lastName; ?>
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
                            <?php }
                        } ?>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-outline-primary see-more-button"
                            style="border: 1px solid #043f9d; color: #043f9d;"
                            onmouseover="this.style.backgroundColor='#043f9d'; this.style.color='white';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#043f9d';"
                            onclick="loadMoreEmployees('inactive-employees', 8)">
                            Load More
                        </button>
                    </div>
                </div>

            <?php } ?>

            <!-- Tab panes for wages-employees -->
            <div class="tab-pane fade <?php echo $role === "general" ? "show active" : "" ?>" id="wages-employees"
                role="tabpanel" aria-labelledby="wages-employees-tab">
                <div class="row row-cols-1 row-cols-md-3 g-4" id="wages-employee-cards">
                    <?php foreach ($employees as $employee) {
                        if ($employee['payroll_type'] === 'wage') { // Filter employees with payrollType = "wages"
                            $profileImage = $employee['profile_image'];
                            $firstName = $employee['first_name'];
                            $lastName = $employee['last_name'];
                            $employeeId = $employee['employee_id'];
                            $isActive = $employee['is_active'];
                            $visaExpiryDate = $employee['visa_expiry_date'];
                            $nickname = $employee['nickname'];
                            ?>
                            <div class="col-12 col-sm-6 col-md-3 employee-card">
                                <div class="card position-relative">
                                    <div
                                        class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
                                        <?php if (!empty($profileImage)) { ?>
                                            <!-- Profile image -->
                                            <div class="bg-gradient shadow-lg rounded-circle"
                                                style="width: 100px; height: 100px; overflow: hidden;">
                                                <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                                    class="profile-pic img-fluid rounded-circle"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        <?php } else { ?>
                                            <!-- Initials -->
                                            <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                                style="width: 100px; height: 100px;">
                                                <h3 class="p-0 m-0">
                                                    <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                                </h3>
                                            </div>
                                        <?php } ?>
                                        <!-- Employee details -->
                                        <h5 class="card-title fw-bold mt-2 employee-name">
                                            <?php echo $firstName . " " . $lastName; ?>
                                            <?php if ($visaExpiryDate !== null) {
                                                $today = new DateTime();
                                                $expiryDate = new DateTime($visaExpiryDate);
                                                $interval = $today->diff($expiryDate);
                                                $daysDifference = $interval->format('%r%a');
                                                if ($daysDifference < 30 && $daysDifference >= 0) {
                                                    echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" data-bs-placement="top" title="Visa expires in ' . $daysDifference . ' days"></i>';
                                                } elseif ($daysDifference < 0) {
                                                    echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" data-bs-placement="top" title="Visa expired ' . abs($daysDifference) . ' days ago"></i>';
                                                }
                                            } ?>
                                        </h5>
                                        <h6 class="card-subtitle mb-2 text-muted">Employee ID: <?php echo $employeeId; ?></h6>
                                        <p class="d-none nickname"><?php echo $nickname; ?></p>
                                        <a
                                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?php echo $employeeId ?>">
                                            <button class="btn btn-dark btn-sm">
                                                <small>Profile <i class="fa-solid fa-up-right-from-square fa-sm"></i></small>
                                            </button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    } ?>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <button class="btn btn-outline-primary see-more-button"
                        style="border: 1px solid #043f9d; color: #043f9d;"
                        onmouseover="this.style.backgroundColor='#043f9d'; this.style.color='white';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#043f9d';"
                        onclick="loadMoreEmployees('wages-employees', 8)">Load More</button>
                </div>
            </div>

            <?php if ($role == "admin") { ?>
                <!-- Tab panes for salary-employees -->
                <div class="tab-pane fade" id="salary-employees" role="tabpanel" aria-labelledby="salary-employees-tab">
                    <div class="row row-cols-1 row-cols-md-3 g-4" id="salary-employee-cards">
                        <?php foreach ($employees as $employee) {
                            if ($employee['payroll_type'] === 'salary') {
                                $profileImage = $employee['profile_image'];
                                $firstName = $employee['first_name'];
                                $lastName = $employee['last_name'];
                                $employeeId = $employee['employee_id'];
                                $isActive = $employee['is_active'];
                                $visaExpiryDate = $employee['visa_expiry_date'];
                                $nickname = $employee['nickname'];
                                ?>
                                <div class="col-12 col-sm-6 col-md-3 employee-card">
                                    <div class="card position-relative">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
                                            <?php if (!empty($profileImage)) { ?>
                                                <!-- Profile image -->
                                                <div class="bg-gradient shadow-lg rounded-circle"
                                                    style="width: 100px; height: 100px; overflow: hidden;">
                                                    <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                                        class="profile-pic img-fluid rounded-circle"
                                                        style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                            <?php } else { ?>
                                                <!-- Initials -->
                                                <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                                    style="width: 100px; height: 100px;">
                                                    <h3 class="p-0 m-0">
                                                        <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                                    </h3>
                                                </div>
                                            <?php } ?>
                                            <!-- Employee details -->
                                            <h5 class="card-title fw-bold mt-2 employee-name">
                                                <?php echo $firstName . " " . $lastName; ?>
                                                <?php if ($visaExpiryDate !== null) { ?>
                                                    <?php
                                                    $today = new DateTime();
                                                    $expiryDate = new DateTime($visaExpiryDate);
                                                    $interval = $today->diff($expiryDate);
                                                    $daysDifference = $interval->format('%r%a');

                                                    // Check if the expiry date is less than 30 days from today
                                                    if ($daysDifference < 30 && $daysDifference >= 0) {
                                                        echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" 
                                data-bs-placement="top" title="Visa expired in ' . $daysDifference . ' days "></i>';
                                                    } else if ($daysDifference < 0) {
                                                        echo '<i class="fa-shake fa-solid fa-circle-exclamation text-danger tooltips" data-bs-toggle="tooltip" 
                                    data-bs-placement="top" title="Visa expired ' . abs($daysDifference) . ' days ago"></i>';
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
                            <?php }
                        } ?>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-outline-primary see-more-button"
                            style="border: 1px solid #043f9d; color: #043f9d;"
                            onmouseover="this.style.backgroundColor='#043f9d'; this.style.color='white';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#043f9d';"
                            onclick="loadMoreEmployees('salary-employees', 8)">Load More</button>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="modal fade" id="addEmployeeModal" tabindex="1" aria-labelledby="addEmployeeModal"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ninthMonthPerformanceReviewModalLabel">Add Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php require ("../Form/EmployeeDetailsForm.php") ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to sort employees by name
        function sortByName(order) {
            sortCards('employee-cards', order, 'name');
            sortCards('active-employee-cards', order, 'name');
            sortCards('inactive-employee-cards', order, 'name');
            sortCards('wages-employee-cards', order, 'name');
            sortCards('salary-employee-cards', order, 'name');
        }

        // Function to sort employees by ID
        function sortByEmployeeID(order) {
            sortCards('employee-cards', order, 'id');
            sortCards('active-employee-cards', order, 'id');
            sortCards('inactive-employee-cards', order, 'id');
            sortCards('wages-employee-cards', order, 'id');
            sortCards('salary-employee-cards', order, 'id');
        }

        function sortCards(containerId, order, sortBy) {
            const cardsContainer = document.getElementById(containerId);
            let cards = Array.from(cardsContainer.children);

            cards.sort((cardA, cardB) => {
                let valueA, valueB;
                if (sortBy === 'name') {
                    valueA = cardA.querySelector('.employee-name').innerText.trim().toLowerCase();
                    valueB = cardB.querySelector('.employee-name').innerText.trim().toLowerCase();
                } else {
                    valueA = cardA.querySelector('.card-subtitle').innerText.trim().toLowerCase().replace('employee id: ', '');
                    valueB = cardB.querySelector('.card-subtitle').innerText.trim().toLowerCase().replace('employee id: ', '');
                }

                if (order === 'asc') {
                    return valueA.localeCompare(valueB);
                } else {
                    return valueB.localeCompare(valueA);
                }
            });

            // Re-append sorted cards to the container
            cards.forEach(card => cardsContainer.appendChild(card));
        }

        // Function to filter employees based on search term
        function filterEmployees(searchTerm) {
            const cards = document.querySelectorAll('.employee-card');

            cards.forEach(card => {
                const name = card.querySelector('.card-title').innerText.toLowerCase();
                const id = card.querySelector('.card-subtitle').innerText.toLowerCase();
                const nickname = card.querySelector('.nickname').innerText.toLowerCase();

                if (name.includes(searchTerm.toLowerCase()) || id.includes(searchTerm.toLowerCase()) || nickname.includes(searchTerm.toLowerCase())) {
                    card.style.display = 'block'; // Show the card
                    hideLoadMoreButton();
                } else {
                    card.style.display = 'none'; // Hide the card
                }
            });

            // Reinitialize to show only 8 employees when search term is cleared
            if (searchTerm === '') {
                initializeEmployees('all-employees', 8);
                initializeEmployees('active-employees', 8);
                initializeEmployees('inactive-employees', 8);
                initializeEmployees('wages-employees', 8);
                initializeEmployees('salary-employees', 8);
            } else if (searchTerm !== '') {
                hideLoadMoreButton();
            }
        }
    </script>

    <script>
        // Function to initialize and show employees based on current tab
        function initializeEmployees(tabId, numToShow) {
            const tabContent = document.getElementById(tabId); 
            if (!tabContent) {
                console.error(`Element with ID ${tabId} not found.`);
                return; // Exit the function if the element is not found
            }
            const employeeCards = tabContent.querySelectorAll('.employee-card');
            let count = 0;

            // Loop through each employee card in the current tab
            employeeCards.forEach((card, index) => {
                if (index < numToShow && card.style.display !== 'none') {
                    card.style.display = 'block'; // Show the card
                    count++;
                } else {
                    card.style.display = 'none'; // Hide the card
                }
            });

            // Show or hide "See More" button based on remaining employees
            const seeMoreButton = tabContent.querySelector('.see-more-button');
            if (count < employeeCards.length) {
                seeMoreButton.style.display = 'block'; // Show the button if there are more employees to show
            } else {
                seeMoreButton.style.display = 'none'; // Hide the button if all employees are shown
            }
        }

        // Function to load more employees when "See More" button is clicked
        function loadMoreEmployees(tabId, numToLoad) {
            const tabContent = document.getElementById(tabId);
            const employeeCards = tabContent.querySelectorAll('.employee-card');
            let numShown = 0;

            // Count currently shown employees
            employeeCards.forEach(card => {
                if (card.style.display !== 'none') {
                    numShown++;
                }
            });

            // Show additional employees
            for (let i = numShown; i < numShown + numToLoad && i < employeeCards.length; i++) {
                employeeCards[i].style.display = 'block';
            }

            // Update "See More" button visibility
            const seeMoreButton = tabContent.querySelector('.see-more-button');
            if (numShown + numToLoad >= employeeCards.length) {
                seeMoreButton.style.display = 'none'; // Hide the button if all employees are shown
            }
        }

        // Function to hide "Load More" button
        function hideLoadMoreButton() {
            const seeMoreButton = document.querySelectorAll('.see-more-button');
            seeMoreButton.forEach(button => {
                button.style.display = 'none';
            });
        }

        // Initial setup to show 8 employees on each tab
        document.addEventListener('DOMContentLoaded', function () {
            initializeEmployees('all-employees', 8);
            initializeEmployees('active-employees', 8);
            initializeEmployees('inactive-employees', 8);
            initializeEmployees('wages-employees', 8);
            initializeEmployees('salary-employees', 8);
        });
    </script>

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t);
        })
    </script>

</body>

</html>