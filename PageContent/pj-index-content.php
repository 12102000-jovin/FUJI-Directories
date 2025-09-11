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

// =============================== Project Table Chart (S T A T U S)===============================
$total_pj_document_count = 0;

// PJ Archived
$pj_archived_sql = "SELECT COUNT(*) AS pj_archived_count FROM projects WHERE current = 'Archived'";
$pj_archived_result = $conn->query($pj_archived_sql);
if ($pj_archived_result->num_rows > 0) {
    $row = $pj_archived_result->fetch_assoc();
    $pj_archived_count = $row['pj_archived_count'];
    $total_pj_document_count += $pj_archived_count;
} else {
    $pj_archived_count = 0;
}

// PJ By Others
$pj_by_others_sql = "SELECT COUNT(*) AS pj_by_others_count FROM projects WHERE current = 'By Others'";
$pj_by_others_result = $conn->query($pj_by_others_sql);
if ($pj_by_others_result->num_rows > 0) {
    $row = $pj_by_others_result->fetch_assoc();
    $pj_by_others_count = $row['pj_by_others_count'];
    $total_pj_document_count += $pj_by_others_count;
} else {
    $pj_by_others_count = 0;
}

// PJ In Progress
$pj_in_progress_sql = "SELECT COUNT(*) AS pj_in_progress_count FROM projects WHERE current = 'In Progress'";
$pj_in_progress_result = $conn->query($pj_in_progress_sql);
if ($pj_in_progress_result->num_rows > 0) {
    $row = $pj_in_progress_result->fetch_assoc();
    $pj_in_progress_count = $row['pj_in_progress_count'];
    $total_pj_document_count += $pj_in_progress_count;
} else {
    $pj_in_progress_count = 0;
}

// PJ Completed
$pj_completed_sql = "SELECT COUNT(*) AS pj_completed_count FROM projects WHERE current = 'Completed'";
$pj_completed_result = $conn->query($pj_completed_sql);
if ($pj_completed_result->num_rows > 0) {
    $row = $pj_completed_result->fetch_assoc();
    $pj_completed_count = $row['pj_completed_count'];
    $total_pj_document_count += $pj_completed_count;
} else {
    $pj_completed_count = 0;
}

// PJ Cancelled
$pj_cancelled_sql = "SELECT COUNT(*) AS pj_cancelled_count FROM projects WHERE current = 'Cancelled'";
$pj_cancelled_result = $conn->query($pj_cancelled_sql);
if ($pj_cancelled_result->num_rows > 0) {
    $row = $pj_cancelled_result->fetch_assoc();
    $pj_cancelled_count = $row['pj_cancelled_count'];
    $total_pj_document_count += $pj_cancelled_count;
} else {
    $pj_completed_count = 0;
}

// Check total PJ document count
$pjDataPoints = [];
$colors = [
    "#3498db", // Medium Blue
    "#2980b9", // Darker Blue
    "#004d6e", // Even Darker Blue
    "#9ecae1", // Very Light Blue
    "#5bc0de", // Light Blue
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

if ($total_pj_document_count > 0) {
    // Array of status
    $status = [
        "Archived" => $pj_archived_count,
        "By Others" => $pj_by_others_count,
        "In Progress" => $pj_in_progress_count,
        "Completed" => $pj_completed_count,
        "Cancelled" => $pj_cancelled_count
    ];

    // Iterate through status to create data points with colors
    $index = 0; // To track colors
    foreach ($status as $label => $count) {
        $percentage = ($count / $total_pj_document_count) * 100;
        $colorIndex = $index % count($colors); // Ensure we loop back to the start of the color array
        $pjStatusDataPoints[] = [
            "label" => $label,
            "y" => $percentage,
            "color" => $colors[$colorIndex] // Assign color
        ];
        $index++;
    }
}

// =============================== Project Table Chart (T Y P E) ===============================
$total_pj_document_type_count = 0;

$pj_local_sql = "SELECT COUNT(*) AS pj_local_count FROM projects WHERE project_type = 'Local'";
$pj_local_result = $conn->query($pj_local_sql);
if ($pj_local_result->num_rows > 0) {
    $row = $pj_local_result->fetch_assoc();
    $pj_local_count = $row['pj_local_count'];
    $total_pj_document_type_count += $pj_local_count;
} else {
    $pj_local_count = 0;
}

$pj_sitework_sql = "SELECT COUNT(*) AS pj_sitework_count FROM projects WHERE project_type = 'Sitework'";
$pj_sitework_result = $conn->query($pj_sitework_sql);
if ($pj_sitework_result->num_rows > 0) {
    $row = $pj_sitework_result->fetch_assoc();
    $pj_sitework_count = $row['pj_sitework_count'];
    $total_pj_document_type_count += $pj_sitework_count;
} else {
    $pj_sitework_count = 0;
}

$pj_commissioning_sql = "SELECT COUNT(*) AS pj_commissioning_count FROM projects WHERE project_type = 'IOR & Commissioning'";
$pj_commissioning_result = $conn->query($pj_commissioning_sql);
if ($pj_commissioning_result->num_rows > 0) {
    $row = $pj_commissioning_result->fetch_assoc();
    $pj_commissioning_count = $row['pj_commissioning_count'];
    $total_pj_document_type_count += $pj_commissioning_count;
} else {
    $pj_commissioning_count = 0;
}

$pj_export_sql = "SELECT COUNT(*) AS pj_export_count FROM projects WHERE project_type = 'Export'";
$pj_export_result = $conn->query($pj_export_sql);
if ($pj_export_result->num_rows > 0) {
    $row = $pj_export_result->fetch_assoc();
    $pj_export_count = $row['pj_export_count'];
    $total_pj_document_type_count += $pj_export_count;
} else {
    $pj_export_count = 0;
}

$pj_rd_sql = "SELECT COUNT(*) AS pj_rd_count FROM projects WHERE project_type = 'R&D'";
$pj_rd_result = $conn->query($pj_rd_sql);
if ($pj_rd_result->num_rows > 0) {
    $row = $pj_rd_result->fetch_assoc();
    $pj_rd_count = $row['pj_rd_count'];
    $total_pj_document_type_count += $pj_rd_count;
} else {
    $pj_rd_count = 0;
}

$pj_service_sql = "SELECT COUNT(*) AS pj_service_count FROM projects WHERE project_type = 'Service'";
$pj_service_result = $conn->query($pj_service_sql);
if ($pj_service_result->num_rows > 0) {
    $row = $pj_service_result->fetch_assoc();
    $pj_service_count = $row['pj_service_count'];
    $total_pj_document_type_count += $pj_service_count;
} else {
    $pj_service_count;
}

$pj_pdc_international_sql = "SELECT COUNT(*) AS pj_pdc_international_count FROM projects WHERE project_type = 'PDC - International'";
$pj_pdc_international_result = $conn->query($pj_pdc_international_sql);
if ($pj_pdc_international_result->num_rows > 0) {
    $row = $pj_pdc_international_result->fetch_assoc();
    $pj_pdc_international_count = $row['pj_pdc_international_count'];
    $total_pj_document_type_count += $pj_pdc_international_count;
} else {
    $pj_pdc_international_count;
}

$pj_pdc_local_sql = "SELECT COUNT(*) AS pj_pdc_local_count FROM projects WHERE project_type = 'PDC - Local'";
$pj_pdc_local_result = $conn->query($pj_pdc_local_sql);
if ($pj_pdc_local_result->num_rows > 0) {
    $row = $pj_pdc_local_result->fetch_assoc();
    $pj_pdc_local_count = $row['pj_pdc_local_count'];
    $total_pj_document_type_count += $pj_pdc_local_count;
} else {
    $pj_pdc_local_count;
}

if ($total_pj_document_type_count > 0) {
    // Array of types
    $types = [
        "Local" => $pj_local_count,
        "Sitework" => $pj_sitework_count,
        "IOR & Commissioning" => $pj_commissioning_count,
        "Export" => $pj_export_count,
        "R&D" => $pj_rd_count,
        "Service" => $pj_service_count,
        "PDC - International" => $pj_pdc_international_count,
        "PDC - Local" => $pj_pdc_local_count
    ];

    // Iterate through types to create data points with colors
    $index = 0; // To track colors
    foreach ($types as $label => $count) {
        $percentage = ($count / $total_pj_document_count) * 100;
        $colorIndex = $index % count($colors); // Ensure we loop back to the start of the color array
        $pjTypeDataPoints[] = [
            "label" => $label,
            "y" => $percentage,
            "color" => $colors[$colorIndex] // Assign color
        ];
        $index++;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
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
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/project-table.php">Project
                                Table</a></li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Project
                            Dashboard
                        </li>
                    </ol>
                </nav>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#projectReportModal">Report <i
                        class="fa-solid fa-square-poll-vertical"></i></button>
            </div> -->
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="bg-white p-2 rounded-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#projectStatusCollapse" aria-expanded="false"
                            aria-control="projectStatusCollapse" style="cursor:pointer">
                            Status
                        </h5>
                        <!-- <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/project-table.php"
                            class="btn btn-dark btn-sm">Table<i class="fa-solid fa-table ms-1"></i></a> -->
                    </div>
                    <div class="collapse show" id="projectStatusCollapse">
                        <div class="card card-body border-0 pb-0 pt-2">
                            <table class="table">
                                <tbody class="pe-none">
                                    <tr>
                                        <td>Archived</td>
                                        <td><?php echo isset($pj_archived_count) ? $pj_archived_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>By Others</td>
                                        <td><?php echo isset($pj_by_others_count) ? $pj_by_others_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>In Progress</td>
                                        <td><?php echo isset($pj_in_progress_count) ? $pj_in_progress_count : '0'; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Completed</td>
                                        <td><?php echo isset($pj_completed_count) ? $pj_completed_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Cancelled</td>
                                        <td><?php echo isset($pj_cancelled_count) ? $pj_cancelled_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="color:#043f9d">Total Projects
                                        </td>
                                        <td class="fw-bold" style="color:#043f9d">
                                            <?php echo $total_pj_document_count ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="" id="chartContainer" style="height: 370px;"></div>
                </div>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                <div class="bg-white p-2 rounded-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#projectTypeCollapse" aria-expanded="false"
                            aria-control="projectTypeCollapse" style="cursor:pointer">
                            Type
                        </h5>
                        <!-- <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/project-table.php"
                            class="btn btn-dark btn-sm">Table<i class="fa-solid fa-table ms-1"></i></a> -->
                    </div>
                    <div class="collapse show" id="projectTypeCollapse">
                        <div class="card card-body border-0 pb-0 pt-2">
                            <table class="table">
                                <tbody class="pe-none">
                                    <tr>
                                        <td>Local</td>
                                        <td><?php echo isset($pj_local_count) ? $pj_local_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td>Sitework</td>
                                        <td><?php echo isset($pj_sitework_count) ? $pj_sitework_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td>IOR & Commissioning</td>
                                        <td><?php echo isset($pj_commissioning_count) ? $pj_commissioning_count : '0' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Export</td>
                                        <td><?php echo isset($pj_export_count) ? $pj_export_count : '0' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>R&D</td>
                                        <td><?php echo isset($pj_rd_count) ? $pj_rd_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td>Service</td>
                                        <td><?php echo isset($pj_service_count) ? $pj_service_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td>PDC - International</td>
                                        <td><?php echo isset($pj_pdc_international_count) ? $pj_pdc_international_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td>PDC - Local</td>
                                        <td><?php echo isset($pj_pdc_local_count) ? $pj_pdc_local_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="color:#043f9d">Total Projects
                                        </td>
                                        <td class="fw-bold" style="color:#043f9d">
                                            <?php echo $total_pj_document_type_count ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="" id="chartContainer2" style="height: 370px;"></div>
                </div>
            </div>
            <caption></caption>
        </div>
    </div>
</body>

</html>

<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var projectDashboardModal = document.getElementById("projectDashboardModal");

        projectDashboardModal.addEventListener("shown.bs.modal", function () {
            var chart = new CanvasJS.Chart("chartContainer", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    fontSize: 18,
                },
                data: [{
                    type: "doughnut",
                    indexLabel: "{label} - {y}%",
                    yValueFormatString: "#,##0.0",
                    showInLegend: false,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($pjStatusDataPoints, JSON_NUMERIC_CHECK); ?>,
                    cornerRadius: 10,
                }]
            });

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
                    dataPoints: <?php echo json_encode($pjTypeDataPoints, JSON_NUMERIC_CHECK); ?>,
                    cornerRadius: 10,
                }]
            })

            chart.render();
            chart2.render();
        });
    });
</script>