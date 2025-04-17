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

// =============================== CAPA Table Chart ===============================

// Initialize total count
$total_capa_count = 0;

// CAPA Status - Open
$open_capa_sql = "SELECT COUNT(*) AS open_status FROM capa WHERE `status` = 'Open'";
$open_capa_result = $conn->query($open_capa_sql);
if ($open_capa_result->num_rows > 0) {
    $row = $open_capa_result->fetch_assoc();
    $open_capa_count = $row['open_status'];
    $total_capa_count += $open_capa_count;
} else {
    $open_capa_count = 0;
}

// CAPA Status - Closed 
$closed_capa_sql = "SELECT COUNT(*) AS closed_status FROM capa WHERE `status` = 'Closed'";
$closed_capa_result = $conn->query($closed_capa_sql);
if ($closed_capa_result->num_rows > 0) {
    $row = $closed_capa_result->fetch_assoc();
    $closed_capa_count = $row['closed_status'];
    $total_capa_count += $closed_capa_count;
} else {
    $closed_capa_count = 0;
}

$open_capa_percentage = ($open_capa_count / $total_capa_count) * 100;
$closed_capa_percentage = ($closed_capa_count / $total_capa_count) * 100;

// Prepare data for CanvasJS
$capaDataPoints = [
    ['label' => 'Open', 'y' => $open_capa_percentage, 'color' => "#dc3547"],
    ['label' => 'Closed', 'y' => $closed_capa_percentage, 'color' => "#27a745"]
];

// Convert PHP array to JSON format for JavaScript
$capaDataPointsJSON = json_encode($capaDataPoints);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Corrective and Preventive Action (CAPA)</title>
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
            <!-- <div class="d-flex justify-content-between align-items-center mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/CAPA-table.php">CAPA Table</a></li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">CAPA
                            Dashboard</li>
                    </ol>
                </nav>
            </div> -->
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="bg-white p-2 rounded-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#capaCollapse" aria-expanded="false" aria-controls="capaCollapse"
                            style="cursor: pointer;">
                            CAPA
                        </h5>

                        <!-- <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/capa-table.php"
                            class="btn btn-dark btn-sm">Table<i class="fa-solid fa-table ms-1"></i></a> -->
                    </div>
                    <div class="collapse show" id="capaCollapse">
                        <div class="card card-body border-0 pb-0 pt-2">
                            <table class="table">
                                <tbody class="pe-none">
                                    <tr>
                                        <td>Open CAPA</td>
                                        <td><?php echo isset($open_capa_count) ? $open_capa_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Closed CAPA</td>
                                        <td><?php echo isset($closed_capa_count) ? $closed_capa_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="color:#043f9d">Total CAPA Documents
                                        </td>
                                        <td class="fw-bold" style="color:#043f9d">
                                            <?php echo $total_capa_count ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="" id="chartContainer2" style="height: 370px;"></div>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var capaDashboardModal = document.getElementById("capaDashboardModal");

            capaDashboardModal.addEventListener("shown.bs.modal", function () {
                var chart2 = new CanvasJS.Chart("chartContainer2", {
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
                        dataPoints: <?php echo json_encode($capaDataPoints, JSON_NUMERIC_CHECK); ?>,
                        cornerRadius: 10,
                    }]
                })
                chart2.render();
            });
        })
    </script>

</body>

</html>