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

// =============================== QA Table Chart ===============================

// Initialize total count
$total_qa_document_count = 0;

// QA Accounts
$qa_accounts_sql = "SELECT COUNT(*) AS qa_accounts_count FROM quality_assurance WHERE department = 'Accounts'";
$qa_accounts_result = $conn->query($qa_accounts_sql);
if ($qa_accounts_result->num_rows > 0) {
    $row = $qa_accounts_result->fetch_assoc();
    $qa_accounts_count = $row['qa_accounts_count'];
    $total_qa_document_count += $qa_accounts_count;
} else {
    $qa_accounts_count = 0;
}

// QA Engineering
$qa_engineering_sql = "SELECT COUNT(*) AS qa_engineering_count FROM quality_assurance WHERE department = 'Engineering'";
$qa_engineering_result = $conn->query($qa_engineering_sql);
if ($qa_engineering_result->num_rows > 0) {
    $row = $qa_engineering_result->fetch_assoc();
    $qa_engineering_count = $row['qa_engineering_count'];
    $total_qa_document_count += $qa_engineering_count;
} else {
    $qa_engineering_count = 0;
}

// QA Estimating
$qa_estimating_sql = "SELECT COUNT(*) AS qa_estimating_count FROM quality_assurance WHERE department = 'Estimating'";
$qa_estimating_result = $conn->query($qa_estimating_sql);
if ($qa_estimating_result->num_rows > 0) {
    $row = $qa_estimating_result->fetch_assoc();
    $qa_estimating_count = $row['qa_estimating_count'];
    $total_qa_document_count += $qa_estimating_count;
} else {
    $qa_estimating_count = 0;
}

// QA Electrical
$qa_electrical_sql = "SELECT COUNT(*) AS qa_electrical_count FROM quality_assurance WHERE department = 'Electrical'";
$qa_electrical_result = $conn->query($qa_electrical_sql);
if ($qa_electrical_result->num_rows > 0) {
    $row = $qa_electrical_result->fetch_assoc();
    $qa_electrical_count = $row['qa_electrical_count'];
    $total_qa_document_count += $qa_electrical_count;
} else {
    $qa_electrical_count = 0;
}

// QA Human Resources
$qa_human_resources_sql = "SELECT COUNT(*) AS qa_human_resources_count FROM quality_assurance WHERE department = 'Human Resources'";
$qa_human_resources_result = $conn->query($qa_human_resources_sql);
if ($qa_human_resources_result->num_rows > 0) {
    $row = $qa_human_resources_result->fetch_assoc();
    $qa_human_resources_count = $row['qa_human_resources_count'];
    $total_qa_document_count += $qa_human_resources_count;
} else {
    $qa_human_resources_count = 0;
}

// QA Management
$qa_management_sql = "SELECT COUNT(*) AS qa_management_count FROM quality_assurance WHERE department = 'Management'";
$qa_management_result = $conn->query($qa_management_sql);
if ($qa_management_result->num_rows > 0) {
    $row = $qa_management_result->fetch_assoc();
    $qa_management_count = $row['qa_management_count'];
    $total_qa_document_count += $qa_management_count;
} else {
    $qa_management_count = 0;
}

// QA Operations Support
$qa_operations_support_sql = "SELECT COUNT(*) AS qa_operations_support_count FROM quality_assurance WHERE department = 'Operations Support'";
$qa_operations_support_result = $conn->query($qa_operations_support_sql);
if ($qa_operations_support_result->num_rows > 0) {
    $row = $qa_operations_support_result->fetch_assoc();
    $qa_operations_support_count = $row['qa_operations_support_count'];
    $total_qa_document_count += $qa_operations_support_count;
} else {
    $qa_operations_support_count = 0;
}

// QA Quality Assurance
$qa_quality_assurance_sql = "SELECT COUNT(*) AS qa_quality_assurance_count FROM quality_assurance WHERE department = 'Quality Assurance'";
$qa_quality_assurance_result = $conn->query($qa_quality_assurance_sql);
if ($qa_quality_assurance_result->num_rows > 0) {
    $row = $qa_quality_assurance_result->fetch_assoc();
    $qa_quality_assurance_count = $row['qa_quality_assurance_count'];
    $total_qa_document_count += $qa_quality_assurance_count;
} else {
    $qa_quality_assurance_count = 0;
}

// QA Quality Control
$qa_quality_control_sql = "SELECT COUNT(*) AS qa_quality_control_count FROM quality_assurance WHERE department = 'Quality Control'";
$qa_quality_control_result = $conn->query($qa_quality_control_sql);
if ($qa_quality_control_result->num_rows > 0) {
    $row = $qa_quality_control_result->fetch_assoc();
    $qa_quality_control_count = $row['qa_quality_control_count'];
    $total_qa_document_count += $qa_quality_control_count;
} else {
    $qa_quality_control_count = 0;
}

// QA Research & Development
$qa_research_development_sql = "SELECT COUNT(*) AS qa_research_development_count FROM quality_assurance WHERE department = 'Research & Development'";
$qa_research_development_result = $conn->query($qa_research_development_sql);
if ($qa_research_development_result->num_rows > 0) {
    $row = $qa_research_development_result->fetch_assoc();
    $qa_research_development_count = $row['qa_research_development_count'];
    $total_qa_document_count += $qa_research_development_count;
} else {
    $qa_research_development_count = 0;
}

// QA Sheet Metal
$qa_sheet_metal_sql = "SELECT COUNT(*) AS qa_sheet_metal_count FROM quality_assurance WHERE department = 'Sheet Metal'";
$qa_sheet_metal_result = $conn->query($qa_sheet_metal_sql);
if ($qa_sheet_metal_result->num_rows > 0) {
    $row = $qa_sheet_metal_result->fetch_assoc();
    $qa_sheet_metal_count = $row['qa_sheet_metal_count'];
    $total_qa_document_count += $qa_sheet_metal_count;
} else {
    $qa_sheet_metal_count = 0;
}

// QA Special Projects
$qa_special_project_sql = "SELECT COUNT(*) AS qa_special_project_count FROM quality_assurance WHERE department = 'Special Projects'";
$qa_special_project_result = $conn->query($qa_special_project_sql);
if ($qa_special_project_result->num_rows > 0) {
    $row = $qa_special_project_result->fetch_assoc();
    $qa_special_project_count = $row['qa_special_project_count'];
    $total_qa_document_count += $qa_special_project_count;
} else {
    $qa_special_project_count = 0;
}

// QA Work, Health and Safety
$qa_whs_sql = "SELECT COUNT(*) AS qa_whs_count FROM quality_assurance WHERE department = 'Work, Health and Safety'";
$qa_whs_result = $conn->query($qa_whs_sql);
if ($qa_whs_result->num_rows > 0) {
    $row = $qa_whs_result->fetch_assoc();
    $qa_whs_count = $row['qa_whs_count'];
    $total_qa_document_count += $qa_whs_count;
} else {
    $qa_whs_count = 0;
}

// Check total QA document count
$qaDataPoints = [];
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

if ($total_qa_document_count > 0) {
    // Array of departments
    $departments = [
        "Accounts" => $qa_accounts_count,
        "Engineering" => $qa_engineering_count,
        "Estimating" => $qa_estimating_count,
        "Electrical" => $qa_electrical_count,
        "Human Resources" => $qa_human_resources_count,
        "Management" => $qa_management_count,
        "Operations Support" => $qa_operations_support_count,
        "Quality Assurance" => $qa_quality_assurance_count,
        "Quality Control" => $qa_quality_control_count,
        "Research & Development" => $qa_research_development_count,
        "Sheet Metal" => $qa_sheet_metal_count,
        "Special Projects" => $qa_special_project_count,
        "Work, Health and Safety" => $qa_whs_count
    ];

    // Iterate through departments to create data points with colors
    $index = 0; // To track colors
    foreach ($departments as $label => $count) {
        $percentage = ($count / $total_qa_document_count) * 100;
        $colorIndex = $index % count($colors); // Ensure we loop back to the start of the color array
        $qaDataPoints[] = [
            "label" => $label,
            "y" => $percentage,
            "color" => $colors[$colorIndex] // Assign color
        ];
        $index++;
    }
}

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
    <title>Quality Assurances</title>
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
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Quality
                            Assurances</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="bg-white p-2 rounded-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#qaCollapse" aria-expanded="false" aria-controls="qaCollapse"
                            style="cursor: pointer;">
                            Quality Assurances
                        </h5>
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/qa-table.php"
                            class="btn btn-dark btn-sm">Table<i class="fa-solid fa-table ms-1"></i></a>
                    </div>
                    <div class="collapse" id="qaCollapse">
                        <div class="card card-body border-0 pb-0 pt-2">
                            <table class="table">
                                <tbody class="pe-none">
                                    <tr>
                                        <td>Accounts</td>
                                        <td><?php echo isset($qa_accounts_count) ? $qa_accounts_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Engineering</td>
                                        <td><?php echo isset($qa_engineering_count) ? $qa_engineering_count : '0'; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Estimating</td>
                                        <td><?php echo isset($qa_estimating_count) ? $qa_estimating_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Electrical</td>
                                        <td><?php echo isset($qa_electrical_count) ? $qa_electrical_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Human Resources</td>
                                        <td><?php echo isset($qa_human_resources_count) ? $qa_human_resources_count : '0'; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Management</td>
                                        <td><?php echo isset($qa_management_count) ? $qa_management_count : '0'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Operations Support</td>
                                        <td><?php echo isset($qa_operations_support_count) ? $qa_operations_support_count : '0' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Quality Assurances</td>
                                        <td><?php echo isset($qa_quality_assurance_count) ? $qa_quality_assurance_count : '0' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Quality Control</td>
                                        <td><?php echo isset($qa_quality_control_count) ? $qa_quality_control_count : '0' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Research & Development</td>
                                        <td><?php echo isset($qa_research_development_count) ? $qa_research_development_count : '0' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Sheet Metal</td>
                                        <td><?php echo isset($qa_sheet_metal_count) ? $qa_sheet_metal_count : '0' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Work, Health and Safety</td>
                                        <td><?php echo isset($qa_whs_count) ? $qa_whs_count : '0' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="color:#043f9d">Total QA
                                        </td>
                                        <td class="fw-bold" style="color:#043f9d">
                                            <?php echo $total_qa_document_count ?>
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
                            data-bs-target="#capaCollapse" aria-expanded="false" aria-controls="capaCollapse"
                            style="cursor: pointer;">
                            CAPA
                        </h5>

                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/capa-table.php"
                            class="btn btn-dark btn-sm">Table<i class="fa-solid fa-table ms-1"></i></a>
                    </div>
                    <div class="collapse" id="capaCollapse">
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
        window.onload = function () {
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
                    dataPoints: <?php echo json_encode($qaDataPoints, JSON_NUMERIC_CHECK); ?>,
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
                    dataPoints: <?php echo json_encode($capaDataPoints, JSON_NUMERIC_CHECK); ?>,
                    cornerRadius: 10,
                }]
            })

            chart.render();
            chart2.render();
        }
    </script>

</body>

</html>