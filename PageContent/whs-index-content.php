<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database 
require("../db_connect.php");
require_once("../status_check.php");
$employee_id = $_SESSION['employee_id'];
$username = $_SESSION['username'];

require_once("../system_role_check.php");

$config = include("../config.php");
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// =============================== WHS Table Chart ===============================

// Initalize total count
$total_whs_count = 0;

// WHS Status - Open 
$open_whs_sql = "SELECT COUNT(*) AS whs_open_status FROM whs WHERE `status` = 'Open'";
$open_whs_result = $conn->query($open_whs_sql);
if ($open_whs_result->num_rows > 0) {
    $row = $open_whs_result->fetch_assoc();
    $open_whs_count = $row['whs_open_status'];
    $total_whs_count += $open_whs_count;
} else {
    $open_whs_count = 0;
}

// WHS Status - Closed
$closed_whs_sql = "SELECT COUNT(*) AS whs_closed_status FROM whs WHERE `status` = 'Closed'";
$closed_whs_result = $conn->query($closed_whs_sql);
if ($closed_whs_result->num_rows > 0) {
    $row = $closed_whs_result->fetch_assoc();
    $closed_whs_count = $row['whs_closed_status'];
    $total_whs_count += $closed_whs_count;
} else {
    $closed_whs_count = 0;
}

$open_whs_percentage = ($open_whs_count / $total_whs_count) * 100;
$closed_whs_percentage = ($closed_whs_count / $total_whs_count) * 100;

// Prepare data for CanvasJS
$whsDataPoints = [
    ['label' => 'Open', 'y' => $open_whs_percentage, 'color' => "#dc3547"],
    ['label' => 'Closed', 'y' => $closed_whs_percentage, 'color' => "#27a745"]
];

// Convert PHP array to JSON format for JavaScript
$whsDataPointsJSON = json_encode($whsDataPoints);

// SQL query to count how many active employee for TRIR and LTIFR
$count_active_employee_sql = "SELECT COUNT(*) AS active_employee_count FROM employees WHERE is_active = 1";
$count_active_employee_result = $conn->query($count_active_employee_sql);
$row = mysqli_fetch_assoc($count_active_employee_result);
$active_employee_count = $row['active_employee_count'];

$estimated_working_hours = $active_employee_count * 38 * 52 * 1.1;

// SQL to count how many incident happened //ttm is trailing twelve months
$count_incident_ttm_sql = "SELECT COUNT(*) AS ttm_incident_count FROM whs WHERE incident_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
$count_incident_ttm_result = $conn->query($count_incident_ttm_sql);
$row = mysqli_fetch_assoc($count_incident_ttm_result);
$ttm_incident_count = $row['ttm_incident_count'];

// SQL query to get the latest recorded WHS date
$latest_whs_sql = "SELECT MAX(incident_date) AS latest_incident_date FROM whs WHERE recordable_incident = 1";
$latest_whs_result = $conn->query($latest_whs_sql);

if ($latest_whs_result->num_rows > 0) {
    $row = $latest_whs_result->fetch_assoc();
    $latest_incident_date = $row['latest_incident_date'];
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Work Health and Safety</title>
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
            top: 0;
            z-index: 1000;
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/whs-table.php">WHS Table</a></li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">WHS Dashboard</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="bg-white p-2 rounded-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#whsCollapse" aria-expanded="false" aria-controls="whsCollapse"
                            style="cursor: pointer">
                            WHS
                        </h5>
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/whs-table.php"
                            class="btn btn-dark btn-sm">Table <i class="fa-solid fa-table ms-1"></i></a>
                    </div>
                    <div class="collapse" id="whsCollapse">
                        <div class="card card-body border-0 pb-0 pt-2">
                            <table class="table">
                                <tbody class="pe-none">
                                    <tr>
                                        <td>Open WHS</td>
                                        <td><?php echo isset($open_whs_count) ? $open_whs_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td>Closed WHS</td>
                                        <td><?php echo isset($closed_whs_count) ? $closed_whs_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="color:#043f9d">Total WHS Documents
                                        </td>
                                        <td class="fw-bold" style="color:#043f9d">
                                            <?php echo $total_whs_count ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="signature-bg-color p-1 rounded-3">
                                <div class="d-flex justify-content-between p-1 ps-2 text-white">
                                    <tr> <?php
                                    // Get the current month and year
                                    $currentMonth = date('M Y'); // e.g., November 2024
                                    $previousMonth = date('M Y', strtotime('-1 year')); // e.g., November 2023
                                    ?>
                                        <td>
                                            <div class=" d-flex justify-content-between align-items-center">
                                                <p class="fw-bold pb-0 mb-0"> TRIR
                                                    (<?php echo $previousMonth . ' - ' . $currentMonth; ?>) :
                                                </p>

                                            </div>
                                        </td>
                                        <td>
                                            <h4 class="fw-bold pb-0 mb-0 bg-white signature-color rounded-3 p-1">
                                                <?php echo number_format(($ttm_incident_count * 200000) / $estimated_working_hours, 2) ?>
                                            </h4>
                                        </td>
                                    </tr>
                                </div>
                                <div
                                    class="d-flex justify-content-between signature-bg-color rounded-3 p-1 ps-2 text-white">
                                    <tr>
                                        <td>
                                            <div class=" d-flex justify-content-between align-items-center">
                                                <p class="fw-bold pb-0 mb-0"> LTIFR
                                                    (<?php echo $previousMonth . ' - ' . $currentMonth; ?>) :

                                                </p>

                                            </div>
                                        </td>
                                        <td>
                                            <h4 class="fw-bold pb-0 mb-0 bg-white signature-color rounded-3 p-1">
                                                <?php echo number_format(($ttm_incident_count / $estimated_working_hours) * 1000000, 2) ?>
                                            </h4>
                                        </td>
                                    </tr>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="" id="chartContainer3" style="height: 370px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script>
        window.onload = function () {
            var chart = new CanvasJS.Chart("chartContainer3", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    fontSize: 18,
                },
                data: [{
                    type: "pie",
                    indexLabel: "{label} - {y}%",
                    yValueFormatString: "#,##0.0",
                    showInLegend: false,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($whsDataPoints, JSON_NUMERIC_CHECK); ?>,
                    cornerRadius: 10,
                }]
            })
            chart.render();
        }
    </script>

</body>

</html>