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

// =============================== A S S E T S  C H A R T ===============================

$departments_sql = "SELECT department_id, department_name FROM department";
$departments_result = $conn->query($departments_sql);

$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[$row['department_id']] = $row['department_name'];
}

$department_counts = [];
$total_assets_count = 0;

foreach ($departments as $department_id => $department_name) {
    $count_sql = "SELECT COUNT(*) AS department_count FROM assets WHERE department_id='$department_id'";
    $count_result = $conn->query($count_sql);

    if ($count_result) {
        $row = $count_result->fetch_assoc();
        $department_counts[$department_name] = $row['department_count'];
        $total_assets_count += $row['department_count'];
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
    $dataPoints[] = array(
        "label" => $department_name,
        "symbol" => $department_name,
        "y" => $count, // Use the count directly instead of calculating percentage
        "color" => $colors[$i % count($colors)]
    );
    $i++;
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Assets</title>
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
        <!-- <div class="mx-md-0 mx-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Assets Dashboard</li>
                    </ol>
                </nav>
            </div>
        </div> -->
        <div class="row">
            <div class="col-lg-4">
                <div class="bg-white p-2 rounded-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#assetCollapse" aria-expanded="false" aria-controls="whsCollapse"
                            style="cursor: pointer">
                            Assets
                        </h5>
                        <!-- <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/asset-table.php"
                            class="btn btn-dark btn-sm">Table <i class="fa-solid fa-table ms-1"></i></a> -->
                    </div>
                    <div class="collapse show" id="assetCollapse">
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
                                        <td class="fw-bold" style="color:#043f9d">Total Assets</td>
                                        <td class="fw-bold" style="color:#043f9d">
                                            <?php echo $total_assets_count ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="" id="chartContainer" style="height: 370px;"></div>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var assetDashboardModal = document.getElementById("assetDashboardModal");

        assetDashboardModal.addEventListener("shown.bs.modal", function () {
            var chart = new CanvasJS.Chart("chartContainer", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    // text: "Total Assets: <?php echo $total_assets_count ?>",
                    fontSize: 18,
                },
                data: [{
                    type: "doughnut",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##",
                    showInLegend: false,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>,
                    cornerRadius: 10,
                }]
            });

            chart.render();
          
        });
    });
</script>

</html>