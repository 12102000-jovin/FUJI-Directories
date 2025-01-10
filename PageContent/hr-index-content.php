<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require("../db_connect.php");
require_once("../status_check.php");
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

require_once ("../system_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// =============================== D E P A R T M E N T  C H A R T ===============================

$departments_sql = "SELECT department_id, department_name FROM department";
$departments_result = $conn->query($departments_sql);

$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[$row['department_id']] = $row['department_name'];
}


$department_counts = [];
$total_employees_count = 0;

foreach ($departments as $department_id => $department_name) {
    $count_sql = "SELECT COUNT(*) AS department_count FROM employees WHERE department='$department_id' AND is_active = 1";
    $count_result = $conn->query($count_sql);

    if ($count_result) {
        $row = $count_result->fetch_assoc();
        $department_counts[$department_name] = $row['department_count'];
        $total_employees_count += $row['department_count'];
    }
}

$dataPoints = [];
$colors = [
    "#5bc0de", // Light Blue
    "#3498db", // Medium Blue
    "#2980b9", // Darker Blue
    "#004d6e", // Even Darker Blue
    "#9ecae1", // Very Light Blue
    "#00aaff", // Sky Blue
    "#0073e6", // Azure Blue
    "#0047ab", // Royal Blue
    "#1e90ff", // Dodger Blue
    "#4682b4", // Steel Blue
    "#4169e1", // Royal Blue
    "#87cefa", // Light Sky Blue
    "#00bfff", // Deep Sky Blue
    "#5f9ea0", // Cadet Blue
    "#87ceeb", // Sky Blue
    "#b0e0e6"  // Powder Blue
];

// Add more colors if needed
$i = 0;

foreach ($department_counts as $department_name => $count) {
    $percentage = ($count / $total_employees_count) * 100;
    $dataPoints[] = array(
        "label" => $department_name,
        "symbol" => $department_name,
        "y" => $percentage,
        "color" => $colors[$i % count($colors)]
    );
    $i++;
}


// =============================== E M P L O Y M E N T  T Y P E  C H A R T ===============================

// Query to get the number of permanent employees
$permanent_employees_sql = "SELECT COUNT(*) AS permanent_count FROM employees WHERE employment_type='Full-Time'";
$permanent_employees_result = $conn->query($permanent_employees_sql);

// Query to get the number of part-time employees
$part_time_employees_sql = "SELECT COUNT(*) AS part_time_count FROM employees WHERE employment_type='Part-Time'";
$part_time_employees_result = $conn->query($part_time_employees_sql);

// Query to get the number of casual employees
$casual_employees_sql = "SELECT COUNT(*) AS casual_count FROM employees WHERE employment_type='Casual'";
$casual_employees_result = $conn->query($casual_employees_sql);

// Initialize variables to employment type counts
$permanent_count = 0;
$part_time_count = 0;
$casual_count = 0;

// Fetch number of permanent employees
if ($permanent_employees_result) {
    $row = $permanent_employees_result->fetch_assoc();
    $permanent_count = $row["permanent_count"];
}

// Fetch number of part-time employees
if ($part_time_employees_result) {
    $row = $part_time_employees_result->fetch_assoc();
    $part_time_count = $row["part_time_count"];
}

// Fetch number of casual employees
if ($casual_employees_result) {
    $row = $casual_employees_result->fetch_assoc();
    $casual_count = $row["casual_count"];
}

// Calculate percentages for each employment type
$permanent_percentage = ($permanent_count / $total_employees_count) * 100;
$part_time_percentage = ($part_time_count / $total_employees_count) * 100;
$casual_percentage = ($casual_count / $total_employees_count) * 100;

// Create employmentTypeData array with percentages for each employment type
$employmentTypeData = array(
    array("label" => "Full-Time", "symbol" => "Full-Time", "y" => $permanent_percentage, "color" => "#5bc0de"),
    array("label" => "Part-Time", "symbol" => "Part-Time", "y" => $part_time_percentage, "color" => "#3498db"),
    array("label" => "Casual", "symbol" => "Casual", "y" => $casual_percentage, "color" => "#2980b9"),
);

// =============================== S E C T I O N  C H A R T  ( E L E C T R I C A L )===============================

// Query to get the total number of electrical employees
$total_electrical_employees_sql = "SELECT COUNT(*) AS total_electrical_employees_count FROM employees WHERE department = 'Electrical'";
$total_electrical_employees_result = $conn->query($total_electrical_employees_sql);

// Query to get the number of panel section employees
$panel_section_employees_sql = "SELECT COUNT(*) AS panel_section_count FROM employees WHERE department = 'Electrical' AND section='Panel'";
$panel_section_employees_result = $conn->query($panel_section_employees_sql);

// Query to get the number of roof employees
$roof_section_employees_sql = "SELECT COUNT(*) AS roof_section_count FROM employees WHERE department = 'Electrical' AND section='Roof'";
$roof_section_employees_result = $conn->query($roof_section_employees_sql);

// Initialize variables to electrical department section counts
$total_electrical_employees_count = 0;
$panel_section_count = 0;
$roof_section_count = 0;

// Fetch total number of electrical employees
if ($total_electrical_employees_result) {
    $row = $total_electrical_employees_result->fetch_assoc();
    $total_electrical_employees_count = $row['total_electrical_employees_count'];
}

// Fetch number of panel employees
if ($panel_section_employees_result) {
    $row = $panel_section_employees_result->fetch_assoc();
    $panel_section_count = $row["panel_section_count"];
}

// Fetch number of roof employees
if ($roof_section_employees_result) {
    $row = $roof_section_employees_result->fetch_assoc();
    $roof_section_count = $row["roof_section_count"];
}

// Calculate percentages for each section
// $panel_percentage = ($panel_section_count / $total_electrical_employees_count) * 100;
// $roof_percentage = ($roof_section_count / $total_electrical_employees_count) * 100;

// echo $total_electrical_employees_count;

// Create Electrical Section Data array with percentages for each section
$electricalSectionData = array(
    // array("label" => "Panel", "symbol" => "Panel", "y" => $panel_percentage, "color" => "#5bc0de"),
    // array("label" => "Roof", "symbol" => "Roof", "y" => $roof_percentage, "color" => "#2980b9"),
);

// =============================== S E C T I O N  C H A R T  ( S H E E T  M E T A L ) ===============================

// Query to get the total of sheet metal employees
$total_sheet_metal_employees_sql = "SELECT COUNT(*) total_sheet_metal_employees_count FROM employees WHERE department = 'Sheet Metal'";
$total_sheet_metal_employees_result = $conn->query($total_sheet_metal_employees_sql);

// Query to get the number of programmer section employees
$programmer_section_employees_sql = "SELECT COUNT(*) AS programmer_section_count FROM employees WHERE department = 'Sheet Metal' AND section='Programmer'";
$programmer_section_employees_result = $conn->query($programmer_section_employees_sql);

// Query to get the total of painter section employees
$painter_section_employees_sql = "SELECT COUNT(*) AS painter_section_count FROM employees WHERE department = 'Sheet Metal' AND section='Painter'";
$painter_section_employees_result = $conn->query($painter_section_employees_sql);

// Initialise variables to sheet metal department section counts
$total_sheet_metal_employees_count = 0;
$programmer_section_count = 0;
$painter_section_count = 0;

// Fetch total number of sheet metal employees
if ($total_sheet_metal_employees_result) {
    $row = $total_sheet_metal_employees_result->fetch_assoc();
    $total_sheet_metal_employees_count = $row["total_sheet_metal_employees_count"];
}

// Fetch number of programmer employees
if ($programmer_section_employees_result) {
    $row = $programmer_section_employees_result->fetch_assoc();
    $programmer_section_count = $row["programmer_section_count"];
}

// Fetch number of painter employees
if ($painter_section_employees_result) {
    $row = $painter_section_employees_result->fetch_assoc();
    $painter_section_count = $row["painter_section_count"];
}

// Calculate percentages for each section
// $programmer_percentage = ($programmer_section_count / $total_sheet_metal_employees_count) * 100;
// $painter_percentage = ($painter_section_count / $total_sheet_metal_employees_count) * 100;

// Create Sheet Metal Section Data array with percentages for each section
$sheetMetalSectionData = array(
    // array("label" => "Programmer", "symbol" => "Programmer", "y" => $programmer_percentage, "color" => "#5bc0de"),
    // array("label" => "Painter", "symbol" => "Painter", "y" => $painter_percentage, "color" => "#2980b9"),
);

// =============================== S E C T I O N  C H A R T  ( O F F I C E ) ===============================

// Query to get the total of office employees
$total_office_employees_sql = "SELECT COUNT(*) total_office_employees_count FROM employees WHERE department = 'Office'";
$total_office_employees_result = $conn->query($total_office_employees_sql);

// Query to get the number of engineer section employees
$engineer_section_employees_sql = "SELECT COUNT(*) AS engineer_section_count FROM employees WHERE department = 'Office' AND section='Engineer'";
$engineer_section_employees_result = $conn->query($engineer_section_employees_sql);

// Query to get the number of accountant section employees
$accountant_section_employees_sql = "SELECT COUNT(*) AS accountant_section_count FROM employees WHERE department = 'Office' AND section='Accountant'";
$accountant_section_employees_result = $conn->query($accountant_section_employees_sql);

// Initialise variables to office department section
$total_office_employees_count = 0;
$engineer_section_count = 0;
$accountant_section_count = 0;

// Fetch total number of office employees
if ($total_office_employees_result) {
    $row = $total_office_employees_result->fetch_assoc();
    $total_office_employees_count = $row["total_office_employees_count"];
}

// Fetch number of engineer employees
if ($engineer_section_employees_result) {
    $row = $engineer_section_employees_result->fetch_assoc();
    $engineer_section_count = $row["engineer_section_count"];
}

// Fetch number of accountant employees
if ($accountant_section_employees_result) {
    $row = $accountant_section_employees_result->fetch_assoc();
    $accountant_section_count = $row["accountant_section_count"];
}

// Calculate percentages for each section
// $engineer_percentage = ($engineer_section_count / $total_office_employees_count) * 100;
// $accountant_percentage = ($accountant_section_count / $total_office_employees_count) * 100;

// Create Office section data array with percentages for each section
$officeSectionData = array(
    // array("label" => "Engineer", "symbol" => "Engineer", "y" => $engineer_percentage, "color" => "#5bc0de"),
    // array("label" => "Accountant", "symbol" => "Accountant", "y" => $accountant_percentage, "color" => "#2980b9"),
);


// SQL Query to get the folders
$folders_sql = "
    SELECT DISTINCT f.*
    FROM folders f
    JOIN groups_folders gf ON f.folder_id = gf.folder_id
    JOIN users_groups ug ON gf.group_id = ug.group_id
    JOIN users u ON ug.user_id = u.user_id
    JOIN employees e ON e.employee_id = u.employee_id
    WHERE e.employee_id = ?
";
$stmt = $conn->prepare($folders_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$folders_result = $stmt->get_result();

// Store the folders in an array
$folders = [];
while ($row = $folders_result->fetch_assoc()) {
    $folders[] = $row;
}

// Prepare the SQL query to avoid SQL injection
$user_details_query = "
    SELECT e.*
    FROM employees e
    JOIN users u ON e.employee_id = u.employee_id
    WHERE u.username = ?
";
$stmt = $conn->prepare($user_details_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_details_result = $stmt->get_result();

// Fetch user details
if ($user_details_result && $user_details_result->num_rows > 0) {
    $row = $user_details_result->fetch_assoc();
    $firstName = $row['first_name'];
    $lastName = $row['last_name'];
    $employeeId = $row['employee_id'];
    $profileImage = $row['profile_image'];
} else {
    $firstName = 'N/A';
    $lastName = 'N/A';
    $employeeId = 'N/A';
    $profileImage = '';
}


// Free up memory
$user_details_result->free();
$folders_result->free();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Human Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <style>
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

        body {
            overflow-x: hidden;
            width: 100%;
            background-color: #eef3f9;
        }

        #side-menu {
            width: 3.5rem;
            transition: width 0.2s ease;
            position: sticky;
            /* Make the sidebar sticky */
            top: 0;
            /* Ensure the sidebar takes the full viewport height */
            z-index: 1000;
            /* Make sure the sidebar is above other content */
        }

        .list-unstyled li:hover {
            background-color: #043f9d;
            color: white !important;
        }

        .sticky-top-menu {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="background-color">
    <div class="container-fluid mt-3 mb-5">
        <div class="mx-md-0 mx-2">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <nav aria-label="breadcrumb ">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">HR
                            Dashboard</li>
                    </ol>
                </nav>
                <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/employee-list-index.php"
                    class="btn btn-success"> <i class="fa-solid fa-users"></i> All
                    Employees </a>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <div class="bg-white p-2 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#departmentCollapse" aria-expanded="false"
                            aria-controls="departmentCollapse" style="cursor: pointer;">
                            Departments
                        </h4>
                        <div class="collapse" id="departmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <?php foreach ($department_counts as $department => $count): ?>
                                            <tr>
                                                <td><?php echo $department ?></td>
                                                <td><?php echo $count ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees</td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div class="" id="chartContainer" style="height: 370px;"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 mt-lg-0 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#employmentTypeCollapse" aria-expanded="false"
                            aria-controls="employmentTypeCollapse" style="cursor: pointer;">
                            Employment Type
                        </h4>
                        <div class="collapse" id="employmentTypeCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Full-Time</td>
                                            <td><?php echo $permanent_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Part-Time</td>
                                            <td><?php echo $part_time_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Casual</td>
                                            <td><?php echo $casual_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees
                                            </td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer2" style="height: 370px;"></div>
                    </div>
                </div>
            </div>
            <hr class="mt-5" />
            <!-- <h3 class="fw-bold">Section</h3>
            <div class="row">
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#electricalDepartmentCollapse" aria-expanded="false"
                            aria-controls="electricalDepartmentCollapse" style="cursor: pointer;">
                            Electrical Department
                        </h4>
                        <div class="collapse" id="electricalDepartmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Panel</td>
                                            <td><?php echo $panel_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Roof</td>
                                            <td><?php echo $roof_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees
                                            </td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_electrical_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer3" style="height: 370px;"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#sheetMetalDepartmentCollapse" aria-expanded="false"
                            aria-controls="sheetMetalDepartmentCollapse" style="cursor: pointer;">
                            Sheet Metal Department
                        </h4>
                        <div class="collapse" id="sheetMetalDepartmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Painter</td>
                                            <td><?php echo $painter_section_count ?> </td>
                                        </tr>
                                        <tr>
                                            <td>Programmer</td>
                                            <td><?php echo $programmer_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees
                                            </td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_sheet_metal_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer4" style="height: 370px;"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 rounded-3">
                        <div>
                            <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                                data-bs-target="#officeDepartmentCollapse" aria-expanded="false"
                                aria-controls="officeDepartmentCollapse" style="cursor: pointer;">
                                Office Department
                            </h4>
                        </div>
                        <div class="collapse" id="officeDepartmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Engineer</td>
                                            <td><?php echo $engineer_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Accountant</td>
                                            <td><?php echo $accountant_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees
                                            </td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_office_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer5" style="height: 370px;"></div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>

    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

    <script>
        window.onload = function () {
            var chart = new CanvasJS.Chart("chartContainer", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    // text: "Total Employees: <?php echo $total_employees_count ?>",
                    fontSize: 18,
                },
                data: [{
                    type: "doughnut",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: false,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>,
                    cornerRadius: 10,
                }]
            });

            var chart2 = new CanvasJS.Chart("chartContainer2", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    // text: "Total Employees: <?php echo $total_employees_count ?>",
                    fontSize: 18,
                },
                data: [{
                    type: "pie",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: false,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($employmentTypeData, JSON_NUMERIC_CHECK); ?>,
                }]
            });

            var chart3 = new CanvasJS.Chart("chartContainer3", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    // text: "Total Employees: <?php echo $total_employees_count ?>",
                    fontSize: 18,
                },
                data: [{
                    type: "pie",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: true,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($electricalSectionData, JSON_NUMERIC_CHECK); ?>,
                }]
            });

            var chart4 = new CanvasJS.Chart("chartContainer4", {
                theme: "light2",
                animationEnabled: true,
                data: [{
                    type: "pie",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: true,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($sheetMetalSectionData, JSON_NUMERIC_CHECK); ?>
                }]
            })

            var chart5 = new CanvasJS.Chart("chartContainer5", {
                theme: "light2",
                animationEnabled: true,
                data: [{
                    type: "pie",
                    indexLabel: "{symbol} = {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: true,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($officeSectionData, JSON_NUMERIC_CHECK); ?>
                }]
            })

            chart.render();
            chart2.render();
            // chart3.render();
            // chart4.render();
            // chart5.render();
        }
    </script>
    <script>
        const menuToggle = document.getElementById("side-menu");
        const closeMenu = document.getElementById("close-menu");
        const folderListFullName = document.getElementsByClassName("folder-name");
        const folderListInitial = document.getElementsByClassName("folder-initials");

        function toggleNav() {
            if (menuToggle.style.width === "250px") {
                menuToggle.style.width = "64px";
                closeMenu.classList.add("d-none");

                // Hide full names and show initials
                for (let i = 0; i < folderListFullName.length; i++) {
                    folderListFullName[i].classList.add("d-none");
                    folderListInitial[i].classList.remove("d-none");
                }
            } else {
                menuToggle.style.width = "250px";
                closeMenu.classList.remove("d-none");

                // Show full names and hide initials
                for (let i = 0; i < folderListFullName.length; i++) {
                    folderListFullName[i].classList.remove("d-none");
                    folderListInitial[i].classList.add("d-none");
                }
            }
        }

        function closeNav() {
            menuToggle.style.width = "64px";
            closeMenu.classList.add("d-none");

            // Ensure initials are shown when menu is closed
            for (let i = 0; i < folderListFullName.length; i++) {
                folderListFullName[i].classList.add("d-none");
                folderListInitial[i].classList.remove("d-none");
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const documentTitle = document.title;

            const topMenuTitle = document.getElementById("top-menu-title");
            topMenuTitle.textContent = documentTitle;

            const topMenuTitleSmall = document.getElementById("top-menu-title-small");
            topMenuTitleSmall.textContent = documentTitle;
        })

    </script>
</body>

</html>