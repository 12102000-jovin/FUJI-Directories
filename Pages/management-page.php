<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once '../db_connect.php';
require_once '../status_check.php';

$folder_name = "Management";
require_once "../group_role_check.php";

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// =============================== EMPLOYEE COUNTS ===============================
$departments = ['Accounts', 'Engineering', 'Sales / Estimating', 'Electrical', 'Quality Control', 'Sheet Metal', 'Site', 'Research & Development', 'Operations Support', 'Management', 'Commissioning'];
$departmentCounts = array_fill_keys($departments, 0);

// Query all department counts at once
$sql = "
    SELECT d.department_name, COUNT(*) AS total
    FROM employees e
    JOIN department d ON d.department_id = e.department
    WHERE e.is_active = 1
      AND d.department_name IN ('Accounts', 'Engineering', 'Sales / Estimating', 'Electrical', 'Quality Control', 'Sheet Metal', 'Site', 'Research & Development', 'Operations Support', 'Management', 'Commissioning')
    GROUP BY d.department_name
";

if ($result = $conn->query($sql)) {
    $totalEmployees = array_sum($departmentCounts);
    while ($row = $result->fetch_assoc()) {
        $name = $row['department_name'];
        if (isset($departmentCounts[$name])) {
            $departmentCounts[$name] = $row['total'];
        }
    }
}

// =============================== FLOOR SPACE COUNTS ===============================
$floorSpaceNames = [];
$floorSpaceAreas = [];

$floor_space_sql = "SELECT * FROM floor_space";
$floor_space_result = $conn->query($floor_space_sql);

while ($row = $floor_space_result->fetch_assoc()) {
    $floorSpaceNames[] = $row['space_name'];
    $floorSpaceAreas[] = floatval($row['area']);
}

// Saving floor space data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'save_floor_space') {
    if (!empty($_POST['floor_space']) && is_array($_POST['floor_space'])) {
        foreach ($_POST['floor_space'] as $id => $value) {
            $id = intval($id);
            $area = floatval($value);
            $update_sql = "UPDATE floor_space SET area = ? WHERE floor_space_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("di", $area, $id);
            $stmt->execute();
        }
        echo "success";
    } else {
        echo "no data";
    }
    exit;
}


// =============================== P D C  P R O J E C T S ===============================
$current_month_str = date("M");
$current_month = date("n");
$current_year = date("Y");

// ===================== OTHER PROJECTS (IOR & Local) =====================
$other_project_sql = "SELECT 
                        p.project_id,
                        p.project_name,
                        p.project_type,
                        pd.sub_total AS project_detail_value,
                        pd.date,
                        pd.revised_delivery_date
                    FROM projects p
                    LEFT JOIN project_details pd ON p.project_id = pd.project_id
                    WHERE p.project_type IN ('IOR & Commissioning','Local')
                      AND pd.sub_total IS NOT NULL AND pd.sub_total > 0
                    ORDER BY p.project_type, pd.date ASC";

$other_project_result = mysqli_query($conn, $other_project_sql);
$projects = [];

while ($row = mysqli_fetch_assoc($other_project_result)) {
    $effectiveDate = $row['revised_delivery_date'] ?: $row['date'];
    if (!$effectiveDate)
        continue;

    $timestamp = strtotime($effectiveDate);
    $projectYear = intval(date("Y", $timestamp));
    $projectMonth = intval(date("n", $timestamp));
    $formattedDate = date("j M Y", $timestamp);

    $projects[] = [
        'code' => $row['project_type'],
        'year' => $projectYear,
        'month' => $projectMonth,
        'cost' => floatval($row['project_detail_value']),
        'fbn' => $row['project_name'],
        'rosdCorrect' => $formattedDate,
        'type' => 'other'
    ];
}

// ===================== PDC PROJECTS =====================
$pdc_project_sql = "SELECT fbn, version, resolved, rOSD_po, rOSD_forecast, rOSD_changed, approved, cost 
                    FROM pdc_projects 
                    WHERE fbn IS NOT NULL 
                      AND aws_po_no IS NOT NULL 
                      AND (status NOT IN ('AWS Removed FF','Cancelled') OR status IS NULL)
                      AND cost IS NOT NULL";

$pdc_project_result = mysqli_query($conn, $pdc_project_sql);

while ($row = mysqli_fetch_assoc($pdc_project_result)) {
    $fbn = strtoupper($row['fbn']);
    $version = $row['version'];
    $cost = floatval($row['cost']);
    if (!$version || $cost <= 0)
        continue;

    // Map country code
    $prefix = substr($fbn, 0, 3);
    $countryMap = [
        'ABX' => 'AUS',
        'SYD' => 'AUS',
        'MEL' => 'AUS',
        'HYD' => 'IND',
        'BOM' => 'IND',
        'NRT' => 'JPN',
        'KIX' => 'JPN',
        'BKK' => 'THA',
        'SIN' => 'SGD',
        'CGK' => 'IDS',
        'DUB' => 'IRL',
        'AKL' => 'NZL',
        'ICN' => 'KOR',
        'USN' => 'KOR',
        'PUS' => 'KOR',
        'HKG' => 'HKG',
        'TPE' => 'TWN',
        'KUL' => 'MYS',
        'ZHY' => 'CHN'
    ];
    if (!isset($countryMap[$prefix]))
        continue;
    $country = $countryMap[$prefix];

    // Extract version, skip V1
    preg_match('/(V\d+\.\d+)/', $version, $match);
    if (empty($match[1]) || strpos($match[1], 'V1') === 0)
        continue;
    $ver = $match[1];
    $code = "PDC-{$ver}-{$country}";

    // Determine rOSD Correct Date
    if ($row['approved'] == "1" && !empty($row['rOSD_changed'])) {
        $rosdCorrectDateRaw = $row['rOSD_changed'];
    } elseif ($row['resolved'] === "PO" && !empty($row['rOSD_po'])) {
        $rosdCorrectDateRaw = $row['rOSD_po'];
    } elseif ($row['resolved'] === "Forecast" && !empty($row['rOSD_forecast'])) {
        $rosdCorrectDateRaw = $row['rOSD_forecast'];
    } else
        continue;

    $timestamp = strtotime($rosdCorrectDateRaw);
    $year = intval(date("Y", $timestamp));
    $month = intval(date("n", $timestamp));
    $formattedDate = date("j M Y", $timestamp);

    $projects[] = [
        'code' => $code,
        'year' => $year,
        'month' => $month,      // IMPORTANT for timeline
        'cost' => $cost,
        'fbn' => $fbn,
        'rosdCorrect' => $formattedDate,
        'type' => 'pdc'
    ];
}

// ===================== DYNAMIC COLUMNS =====================
$years = array_unique(array_column($projects, 'year'));
sort($years);
$columns = ["{$current_month_str}-Dec {$current_year}"];
foreach ($years as $year) {
    if ($year > $current_year)
        $columns[] = $year;
}

// ===================== INITIALIZE TOTALS =====================
$codes = array_unique(array_column($projects, 'code'));
sort($codes);

$totals = [];
$fbns_per_code_col = [];
$grandTotal = 0;

foreach ($codes as $code) {
    $totals[$code] = array_fill_keys($columns, 0);
    $fbns_per_code_col[$code] = array_fill_keys($columns, []);
}

// ===================== ASSIGN COSTS =====================
foreach ($projects as $project) {
    $code = $project['code'];
    $year = $project['year'];
    $month = $project['month'];
    $cost = $project['cost'];
    $fbn = $project['fbn'];

    // Skip zero cost
    if ($cost <= 0)
        continue;

    // Determine column
    if ($year == $current_year) {
        if ($month < $current_month)
            continue; // skip past months
        $col = "{$current_month_str}-Dec {$current_year}"; // only first column
    } elseif ($year > $current_year) {
        $col = $year; // future years = full year, no month filtering
    } else {
        continue; // skip past years
    }

    $totals[$code][$col] += $cost;
    $fbns_per_code_col[$code][$col][] = $fbn;
    $grandTotal += $cost;
}

// ===================== FILTER CODES WITH NO COST =====================
$codes = array_filter($codes, function ($code) use ($totals) {
    return array_sum($totals[$code]) > 0;
});
sort($codes);

// ===================== PDC PROJECTS (No PO) =====================
$pdc_project_no_po_sql = "SELECT fbn, version, resolved, rOSD_po, rOSD_forecast, rOSD_changed, approved, cost 
                    FROM pdc_projects 
                    WHERE fbn IS NOT NULL 
                      AND (status NOT IN ('AWS Removed FF','Cancelled') OR status IS NULL)
                      AND cost IS NOT NULL";
$pdc_project_no_po_result = mysqli_query($conn, $pdc_project_no_po_sql);

$projects_no_po = [];

while ($row = mysqli_fetch_assoc($pdc_project_no_po_result)) {
    $fbn = strtoupper($row['fbn']);
    $version = $row['version'];
    $cost = floatval($row['cost']);
    if (!$version || $cost <= 0)
        continue;

    // Map country code
    $prefix = substr($fbn, 0, 3);
    $countryMap = [
        'ABX' => 'AUS',
        'SYD' => 'AUS',
        'MEL' => 'AUS',
        'HYD' => 'IND',
        'BOM' => 'IND',
        'NRT' => 'JPN',
        'KIX' => 'JPN',
        'BKK' => 'THA',
        'SIN' => 'SGD',
        'CGK' => 'IDS',
        'DUB' => 'IRL',
        'AKL' => 'NZL',
        'ICN' => 'KOR',
        'USN' => 'KOR',
        'PUS' => 'KOR',
        'HKG' => 'HKG',
        'TPE' => 'TWN',
        'KUL' => 'MYS',
        'ZHY' => 'CHN'
    ];
    if (!isset($countryMap[$prefix]))
        continue;
    $country = $countryMap[$prefix];

    // Extract version, skip V1
    preg_match('/(V\d+\.\d+)/', $version, $match);
    if (empty($match[1]) || strpos($match[1], 'V1') === 0)
        continue;
    $ver = $match[1];
    $code = "PDC-{$ver}-{$country}";

    // Determine rOSD Correct Date
    if ($row['approved'] == "1" && !empty($row['rOSD_changed'])) {
        $rosdCorrectDateRaw = $row['rOSD_changed'];
    } elseif ($row['resolved'] === "PO" && !empty($row['rOSD_po'])) {
        $rosdCorrectDateRaw = $row['rOSD_po'];
    } elseif ($row['resolved'] === "Forecast" && !empty($row['rOSD_forecast'])) {
        $rosdCorrectDateRaw = $row['rOSD_forecast'];
    } else
        continue;

    $timestamp = strtotime($rosdCorrectDateRaw);
    $year = intval(date("Y", $timestamp));
    $month = intval(date("n", $timestamp));
    $formattedDate = date("j M Y", $timestamp);

    $projects_no_po[] = [
        'code' => $code,
        'year' => $year,
        'month' => $month,
        'cost' => $cost,
        'fbn' => $fbn,
        'rosdCorrect' => $formattedDate,
        'type' => 'pdc'
    ];
}

// ===================== DYNAMIC COLUMNS =====================
$years_no_po = array_unique(array_column($projects_no_po, 'year'));
sort($years_no_po);
$columns_no_po = ["{$current_month_str}-Dec {$current_year}"];
foreach ($years_no_po as $year) {
    if ($year > $current_year)
        $columns_no_po[] = $year;
}

// ===================== INITIALIZE TOTALS =====================
$codes_no_po = array_unique(array_column($projects_no_po, 'code'));
sort($codes_no_po);

$totals_no_po = [];
$fbns_per_code_col_no_po = [];
$grandTotal_no_po = 0;

foreach ($codes_no_po as $code) {
    $totals_no_po[$code] = array_fill_keys($columns_no_po, 0);
    $fbns_per_code_col_no_po[$code] = array_fill_keys($columns_no_po, []);
}

// ===================== ASSIGN COSTS =====================
foreach ($projects_no_po as $project) {
    $code = $project['code'];
    $year = $project['year'];
    $month = $project['month'];
    $cost = $project['cost'];
    $fbn = $project['fbn'];

    if ($cost <= 0)
        continue;

    if ($year == $current_year) {
        if ($month < $current_month)
            continue; // skip past months
        $col = "{$current_month_str}-Dec {$current_year}";
    } elseif ($year > $current_year) {
        $col = $year;
    } else {
        continue;
    }

    $totals_no_po[$code][$col] += $cost;
    $fbns_per_code_col_no_po[$code][$col][] = $fbn;
    $grandTotal_no_po += $cost;
}

// ===================== FILTER CODES WITH NO COST =====================
$codes_no_po = array_filter($codes_no_po, function ($code) use ($totals_no_po) {
    return array_sum($totals_no_po[$code]) > 0;
});
sort($codes_no_po);

// =============================== SAVE BUSINESS REPORT ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'save_business_report') {
    // Temporarily turn off error display for clean JSON response
    ini_set('display_errors', 0);

    header('Content-Type: application/json');

    try {
        // Prepare the data for insertion - REMOVE real_escape_string
        $significant_order = $_POST['significantOrder'] ?? '';
        $synergy_with_fe = $_POST['synergyWithFe'] ?? '';
        $market_situation = $_POST['marketSituation'] ?? '';
        $key_competitors = $_POST['keyCompetitors'] ?? '';
        $new_business_ideas = $_POST['newBusinessIdeas'] ?? '';
        $business_strategies = $_POST['businessStrategies'] ?? '';
        $legal_matters = $_POST['legalMatters'] ?? '';
        $marketing_activities = $_POST['marketingActivities'] ?? '';
        $delivery_schedule = $_POST['deliverySchedule'] ?? '';
        $working_condition = $_POST['workingCondition'] ?? '';
        $key_hr_movement = $_POST['keyHrMovement'] ?? '';
        $hiring_plan = $_POST['hiringPlan'] ?? '';
        $inventory = $_POST['inventory'] ?? '';
        $supplier_procurement = $_POST['supplierProcurement'] ?? '';
        $capital_investment = $_POST['capitalInvestment'] ?? '';
        $intercompany_coordination = $_POST['intercompanyCoordination'] ?? '';
        $target_sales = $_POST['targetSales'] ?? '';
        $cash_flow = $_POST['cashFlow'] ?? '';
        $debt_collection_status = $_POST['debtCollectionStatus'] ?? '';
        $current_month = date('Y-m-d');

        // Use INSERT ... ON DUPLICATE KEY UPDATE
        $sql = "INSERT INTO business_report (
            significant_order, synergy_with_fe, market_situation, key_competitors,
            new_business_ideas, business_strategies, legal_matters, marketing_activities,
            delivery_schedule, working_condition, key_hr_movement, hiring_plan,
            inventory, supplier_procurement, capital_investment, intercompany_coordination,
            target_sales, cash_flow, debt_collection_status, report_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            significant_order = VALUES(significant_order),
            synergy_with_fe = VALUES(synergy_with_fe),
            market_situation = VALUES(market_situation),
            key_competitors = VALUES(key_competitors),
            new_business_ideas = VALUES(new_business_ideas),
            business_strategies = VALUES(business_strategies),
            legal_matters = VALUES(legal_matters),
            marketing_activities = VALUES(marketing_activities),
            delivery_schedule = VALUES(delivery_schedule),
            working_condition = VALUES(working_condition),
            key_hr_movement = VALUES(key_hr_movement),
            hiring_plan = VALUES(hiring_plan),
            inventory = VALUES(inventory),
            supplier_procurement = VALUES(supplier_procurement),
            capital_investment = VALUES(capital_investment),
            intercompany_coordination = VALUES(intercompany_coordination),
            target_sales = VALUES(target_sales),
            cash_flow = VALUES(cash_flow),
            debt_collection_status = VALUES(debt_collection_status)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssssssssssssssssss",
            $significant_order,
            $synergy_with_fe,
            $market_situation,
            $key_competitors,
            $new_business_ideas,
            $business_strategies,
            $legal_matters,
            $marketing_activities,
            $delivery_schedule,
            $working_condition,
            $key_hr_movement,
            $hiring_plan,
            $inventory,
            $supplier_procurement,
            $capital_investment,
            $intercompany_coordination,
            $target_sales,
            $cash_flow,
            $debt_collection_status,
            $current_month
        );

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Report saved successfully!']);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

    } catch (Exception $e) {
        error_log("Save business report error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error saving report: ' . $e->getMessage()]);
    }

    // Restore error settings
    ini_set('display_errors', 1);
    exit;
}

// =============================== LOAD EXISTING REPORT DATA ===============================
// Load the latest report data (most recent date)
$load_sql = "SELECT * FROM business_report ORDER BY report_date DESC, business_report_id DESC LIMIT 1";
$load_result = $conn->query($load_sql);
$report_data = $load_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
            border: none;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
            color: white;
        }

        .pagination .page-link {
            color: black;
        }

        .nav-tabs {
            border: none !important;
        }

        /* Inactive tabs */
        .custom-tabs .nav-link {
            color: #043f9d;
        }

        /* Active tab */
        .custom-tabs .nav-link.active {
            color: white !important;
            background-color: #043f9d !important;
        }

        /* Hover effect for inactive tabs */
        .custom-tabs .nav-link:hover {
            background-color: #d0d9f5;
        }
    </style>
</head>

<body class="background-color">
    <?php require("../Menu/NavBar.php") ?>
    <div class="container mb-5 mt-5 rounded-3 bg-white p-5 position-relative">
        <button class="position-absolute top-0 end-0 m-2 btn btn-dark" onclick="printReport()">
            <i class="fa-solid fa-print me-1"></i>Print
        </button>

        <!-- Replace the existing save button -->
        <button type="button" class="btn btn-lg btn-success position-fixed bottom-0 end-0 m-3" id="saveReportBtn">
            <i class="fa-solid fa-save me-1"></i> Save Report
        </button>

        <div class="p-3 text-center">
            <h3 class="fw-bold mb-4 signature-color text-decoration-underline">Corporate Sales Objectives & Key Results
            </h3>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs custom-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="order-tab" data-bs-toggle="tab" data-bs-target="#order"
                    type="button" role="tab" aria-controls="order" aria-selected="true">
                    Order / Sales
                </button>
            </li>
            <li class="nav-item custom-tabs" role="presentation">
                <button class="nav-link" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button"
                    role="tab" aria-controls="business" aria-selected="false">
                    Business Situation & Strategies
                </button>
            </li>
            <li class="nav-item custom-tabs" role="presentation">
                <button class="nav-link" id="operation-tab" data-bs-toggle="tab" data-bs-target="#operation"
                    type="button" role="tab" aria-controls="operation" aria-selected="false">
                    Operation & HR Plans
                </button>
            </li>
            <li class="nav-item custom-tabs" role="presentation">
                <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance" type="button"
                    role="tab" aria-controls="finance" aria-selected="false">
                    Finance
                </button>
            </li>
        </ul>

        <hr>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="myTabContent">
            <div class="tab-pane fade show active" id="order" role="tabpanel" aria-labelledby="order-tab">

                <h6 class="mt-4 fw-bold">Order Book up-to-date</h6>
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <h5 class="fw-bold signature-color">Sales (Purchase Order Received)</h5>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Product Type</th>
                                <?php foreach ($columns as $col): ?>
                                    <th class="text-center"><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center">Sub-Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- IOR & Commissioning -->
                            <tr class="bg-light">
                                <td>IOR & Commissioning</td>
                                <?php foreach ($columns as $col): ?>
                                    <td class="text-center">
                                        <?= (isset($totals['IOR & Commissioning'][$col]) && $totals['IOR & Commissioning'][$col] != 0)
                                            ? "$" . number_format($totals['IOR & Commissioning'][$col], 0)
                                            : '-' ?><br>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center fw-bold">
                                    <?= isset($totals['IOR & Commissioning'])
                                        ? "$" . number_format(array_sum($totals['IOR & Commissioning']), 0)
                                        : '-' ?>
                                </td>
                            </tr>

                            <!-- Local -->
                            <tr class="bg-light">
                                <td>Local</td>
                                <?php foreach ($columns as $col): ?>
                                    <td class="text-center">
                                        <?= (isset($totals['Local'][$col]) && $totals['Local'][$col] != 0)
                                            ? "$" . number_format($totals['Local'][$col], 0)
                                            : '-' ?><br>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center fw-bold">
                                    <?= isset($totals['Local'])
                                        ? "$" . number_format(array_sum($totals['Local']), 0)
                                        : '-' ?>
                                </td>
                            </tr>

                            <!-- PDC Projects -->
                            <?php
                            $total_pdc_sales = 0; // accumulator for all PDC rows
                            foreach ($codes as $code):
                                if (in_array($code, ['IOR & Commissioning', 'Local']))
                                    continue;

                                // Skip PDC codes that have all zero values
                                $nonzero = array_sum($totals[$code]);
                                if ($nonzero == 0)
                                    continue;

                                $subtotal = 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($code) ?></td>
                                    <?php foreach ($columns as $col):
                                        $value = $totals[$code][$col];
                                        $subtotal += $value;
                                        ?>
                                        <td class="text-center">
                                            <?= ($value > 0)
                                                ? "$" . number_format($value, 0)
                                                : '-' ?><br>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center fw-bold">
                                        <?= ($subtotal > 0) ? "$" . number_format($subtotal, 0) : '-' ?>
                                    </td>
                                </tr>
                                <?php
                                $total_pdc_sales += $subtotal;
                            endforeach;
                            ?>

                            <!-- GRAND TOTAL -->
                            <tr class="fw-bold">
                                <td>Grand Total</td>
                                <?php foreach ($columns as $col):
                                    $colTotal = array_sum(array_column($totals, $col));
                                    ?>
                                    <td class="text-center"><?= "$" . number_format($colTotal, 0) ?></td>
                                <?php endforeach; ?>
                                <td class="text-center"><?= "$" . number_format($grandTotal, 0) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- SUMMARY TABLE -->
                    <table class="table table-bordered mt-4">
                        <tbody>
                            <tr>
                                <td class="col-6 text-white fw-bold text-center mb-0 pb-0"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Total PDC Sales
                                </td>
                                <td class="col-8 text-center fw-bold">
                                    <?= "$" . number_format($total_pdc_sales, 0) ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-white fw-bold text-center mb-0 pb-0"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Total Local Sales
                                </td>
                                <td class="text-center fw-bold">
                                    <?= isset($totals['Local']) ? "$" . number_format(array_sum($totals['Local']), 0) : '-' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-white fw-bold text-center mb-0 pb-0"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Total Import Sales
                                </td>
                                <td class="text-center fw-bold">
                                    <?= isset($totals['IOR & Commissioning']) ? "$" . number_format(array_sum($totals['IOR & Commissioning']), 0) : '-' ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        <div class="col-md-6 mt-4 mb-4">
                            <canvas id="salesChart" style="max-height: 350px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3 bg-primary bg-opacity-10 p-3 mt-4">
                    <h5 class="fw-bold signature-color">Total Forecast Sales (Official From AWS)</h5>
                    <?php
                    // --- Compute Grand Totals ---
                    $grandTotal_no_po = 0;

                    // Compute grand total per column
                    $columnTotals = [];
                    foreach ($columns_no_po as $col) {
                        $columnTotals[$col] = 0;

                        // Sum PDC projects
                        foreach ($totals_no_po as $code => $values) {
                            $columnTotals[$col] += $values[$col] ?? 0;
                        }

                        // Add Local & IOR
                        $columnTotals[$col] += $totals['Local'][$col] ?? 0;
                        $columnTotals[$col] += $totals['IOR & Commissioning'][$col] ?? 0;

                        // Add to overall grand total
                        $grandTotal_no_po += $columnTotals[$col];
                    }
                    ?>

                    <!-- MAIN TABLE -->
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Product Type</th>
                                <?php foreach ($columns_no_po as $col): ?>
                                    <th class="text-center"><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center">Sub-Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- IOR & Commissioning -->
                            <tr class="bg-light">
                                <td>IOR & Commissioning</td>
                                <?php foreach ($columns_no_po as $col): ?>
                                    <td class="text-center">
                                        <?= isset($totals['IOR & Commissioning'][$col]) && $totals['IOR & Commissioning'][$col] != 0
                                            ? "$" . number_format($totals['IOR & Commissioning'][$col], 0)
                                            : '-' ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center fw-bold">
                                    <?= isset($totals['IOR & Commissioning'])
                                        ? "$" . number_format(array_sum($totals['IOR & Commissioning']), 0)
                                        : '-' ?>
                                </td>
                            </tr>

                            <!-- Local -->
                            <tr class="bg-light">
                                <td>Local</td>
                                <?php foreach ($columns_no_po as $col): ?>
                                    <td class="text-center">
                                        <?= isset($totals['Local'][$col]) && $totals['Local'][$col] != 0
                                            ? "$" . number_format($totals['Local'][$col], 0)
                                            : '-' ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center fw-bold">
                                    <?= isset($totals['Local'])
                                        ? "$" . number_format(array_sum($totals['Local']), 0)
                                        : '-' ?>
                                </td>
                            </tr>

                            <!-- PDC Projects -->
                            <?php foreach ($codes_no_po as $code):
                                if (in_array($code, ['IOR & Commissioning', 'Local']))
                                    continue;

                                $subtotal = array_sum($totals_no_po[$code]);
                                if ($subtotal == 0)
                                    continue; // skip if all zeros
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($code) ?></td>
                                    <?php foreach ($columns_no_po as $col):
                                        $value = $totals_no_po[$code][$col] ?? 0;
                                        ?>
                                        <td class="text-center"><?= $value > 0 ? "$" . number_format($value, 0) : '-' ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-center fw-bold">
                                        <?= $subtotal > 0 ? "$" . number_format($subtotal, 0) : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- GRAND TOTAL -->
                            <tr class="fw-bold">
                                <td>Grand Total</td>
                                <?php foreach ($columns_no_po as $col): ?>
                                    <td class="text-center"><?= "$" . number_format($columnTotals[$col], 0) ?></td>
                                <?php endforeach; ?>
                                <td class="text-center"><?= "$" . number_format($grandTotal_no_po, 0) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- SUMMARY TABLE -->
                    <?php
                    $total_pdc_v2 = 0;
                    $total_pdc_v3 = 0;
                    $total_local = isset($totals['Local']) ? array_sum($totals['Local']) : 0;
                    $total_ior = isset($totals['IOR & Commissioning']) ? array_sum($totals['IOR & Commissioning']) : 0;

                    foreach ($totals_no_po as $code => $values) {
                        $subtotal = array_sum($values);

                        if (stripos($code, 'PDC-V2.0') !== false) {
                            $total_pdc_v2 += $subtotal;
                        } elseif (stripos($code, 'PDC-V3.0') !== false) {
                            $total_pdc_v3 += $subtotal;
                        }
                    }

                    // Grand total for summary
                    $grand_total_summary = $total_pdc_v2 + $total_pdc_v3 + $total_local + $total_ior;
                    ?>

                    <table class="table table-bordered mt-4">
                        <tbody>
                            <tr>
                                <td class="col-6 text-white fw-bold text-center"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Total PDC V2.0 Sales
                                </td>
                                <td class="col-8 text-center fw-bold"><?= "$" . number_format($total_pdc_v2, 0) ?></td>
                            </tr>
                            <tr>
                                <td class="col-6 text-white fw-bold text-center"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Total PDC V3.0 Sales
                                </td>
                                <td class="col-8 text-center fw-bold"><?= "$" . number_format($total_pdc_v3, 0) ?></td>
                            </tr>
                            <tr>
                                <td class="text-white fw-bold text-center"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Total Local Sales
                                </td>
                                <td class="text-center fw-bold"><?= "$" . number_format($total_local, 0) ?></td>
                            </tr>
                            <tr>
                                <td class="text-white fw-bold text-center"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Total IOR & Commissioning Sales
                                </td>
                                <td class="text-center fw-bold"><?= "$" . number_format($total_ior, 0) ?></td>
                            </tr>
                            <!-- <tr>
                                <td class="text-white fw-bold text-center"
                                    style="background-color: #043f9d; border: 1px solid #043f9d;">
                                    Grand Total Sales
                                </td>
                                <td class="text-end fw-bold"><?= "$" . number_format($grand_total_summary, 0) ?></td>
                            </tr> -->
                        </tbody>
                    </table>

                </div>
                <h6 class="mt-4 fw-bold"> Any significant order with high risk / low profitability</h6>
                <textarea name="significantOrder" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['significant_order'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Synergy with FE </h6>
                <textarea name="synergyWithFe" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['synergy_with_fe'] ?? '') ?></textarea>
            </div>

            <div class="tab-pane fade" id="business" role="tabpanel" aria-labelledby="business-tab">
                <h6 class="mt-4 fw-bold">Market situation in specific market</h6>
                <textarea name="marketSituation" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['market_situation'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Key competitors in specific market</h6>
                <textarea name="keyCompetitors" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['key_competitors'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> New business ideas </h6>
                <textarea name="newBusinessIdeas" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['new_business_ideas'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Specific business strategies </h6>
                <textarea name="businessStrategies" id=""
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['business_strategies'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Legal matters, if any </h6>
                <textarea name="legalMatters" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['legal_matters'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Marketing activities - ongoing / plan </h6>
                <textarea name="marketingActivities" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['marketing_activities'] ?? '') ?></textarea>
            </div>

            <?php
            $totalEmployees = array_sum($departmentCounts);
            ?>

            <div class="tab-pane fade" id="operation" role="tabpanel" aria-labelledby="operation-tab">
                <h6 class="mt-4 fw-bold"> Delivery schedule </h6>
                <textarea name="deliverySchedule" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['delivery_schedule'] ?? '') ?></textarea>
                <h6 class="mt-4 fw-bold"> Manpower </h6>
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <div class="row align-items-center">

                        <!-- Table -->
                        <div class="col-lg-6">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Manpower</th>
                                        <th class="text-center">No.</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Total Row -->
                                    <tr class="fw-bold">
                                        <td>Total Manpower</td>
                                        <td class="text-center"><small><?php echo $totalEmployees; ?></small></td>
                                        <td class="text-center"><small>100%</small></td>
                                    </tr>

                                    <!-- Department Rows -->
                                    <?php foreach ($departmentCounts as $dept => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dept); ?></td>
                                            <td class="text-center"><small><?php echo $count; ?></td></small>
                                            <td class="text-center">
                                                <small>
                                                    <?php echo number_format(($count / $totalEmployees) * 100, 1) . '%'; ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Optional extra category -->
                                    <tr class="fw-bold">
                                        <td>Production Support Area</td>
                                        <td class="text-center">
                                            <small><?php echo $departmentCounts['Operations Support'] ?? 0; ?></small>
                                        </td>
                                        <td class="text-center">
                                            <small>
                                                <?php
                                                $opsSupport = $departmentCounts['Operations Support'] ?? 0;
                                                echo number_format(($opsSupport / $totalEmployees) * 100, 1) . '%';
                                                ?>
                                            </small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Chart -->
                        <div class="col-lg-6 text-center">
                            <canvas id="manpowerChart" style="max-width: 100%; height: 200px;"></canvas>
                        </div>
                    </div>
                </div>

                <h6 class="mt-4 fw-bold"> Floor space and production capacity </h6>
                <div class="rounded-3 bg-primary bg-opacity-10 p-3 position-relative">
                    <button class="position-absolute top-0 end-0 m-2 btn btn-dark" id="saveFloorSpace"><i
                            class="fa-solid fa-save me-1"></i>Save</button>
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <table class="table table-bordered table-hover rounded-3 mb-0" id="">
                                <thead>
                                    <tr>
                                        <th>Production</th>
                                        <th class="text-center" style="width: 80px">A (m<sup>2</sup>)</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Floor Space</td>
                                        <td class="text-center">
                                            <small id="totalFloorSpace">0</small>
                                        </td>
                                        <td class="text-center"><small>100%</small></td>
                                    </tr>

                                    <?php
                                    $floor_space_sql = "SELECT * FROM floor_space";
                                    $floor_space_result = $conn->query($floor_space_sql);

                                    while ($row = $floor_space_result->fetch_assoc()) {
                                        $space_name = htmlspecialchars($row['space_name']);
                                        $area_value = $row['area'] ?? '';
                                        ?>
                                        <tr class="align-middle">
                                            <td>
                                                <p class="mb-0 pb-0"><?php echo $space_name; ?></p>
                                            </td>
                                            <td class="text-center">
                                                <input type="text" name="floor_space[<?php echo $row['floor_space_id']; ?>]"
                                                    value="<?php echo $area_value; ?>"
                                                    class="text-center form-control-sm floor-input"
                                                    style="border: none; width: 70px;" onchange="updatePercentages()">
                                            </td>
                                            <td class="text-center"><small class="percentage-cell"></small></td>
                                        </tr>
                                    <?php } ?>

                                    <tr>
                                        <td>Production Support Area</td>
                                        <td class="text-center"><small id="productionSupport">0</small></td>
                                        <td class="text-center"><small class="percentage-cell"></small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6 text-center">
                            <canvas id="floorSpaceChart" style="max-width: 100%; height: 250px;"></canvas>
                        </div>
                    </div>
                </div>

                <h6 class="mt-4 fw-bold"> Working condition </h6>
                <textarea name="workingCondition" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['working_condition'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Key HR movement </h6>
                <textarea name="keyHrMovement" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['key_hr_movement'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Hiring plan </h6>
                <textarea name="hiringPlan" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['hiring_plan'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Inventory </h6>
                <textarea name="inventory" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['inventory'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Supplier / Procurement </h6>
                <textarea name="supplierProcurement" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['supplier_procurement'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Capital investment </h6>
                <textarea name="capitalInvestment" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['capital_investment'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Coordination with intercompany </h6>
                <textarea name="intercompanyCoordination" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['intercompany_coordination'] ?? '') ?></textarea>
            </div>

            <div class="tab-pane fade" id="finance" role="tabpanel" aria-labelledby="finance-tab">
                <h6 class="mt-4 fw-bold"> Sales & P&L on target? </h6>
                <textarea name="targetSales" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['target_sales'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Cash flow </h6>
                <textarea name="cashFlow" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['cash_flow'] ?? '') ?></textarea>

                <h6 class="mt-4 fw-bold"> Debt collection status </h6>
                <textarea name="debtCollectionStatus" id="" rows="2"
                    class="form-control auto-expand"><?= htmlspecialchars($report_data['debt_collection_status'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end align-items-center mt-5">
            <h4 class="mb-0 pb-0 me-1 fw-bold">
                <?php
                echo date("F Y");
                9
                    ?>
                -
            </h4>
            <img src="../Images/FSMBE-Harwal-logo.png" width="200">
        </div>
    </div>

    <script>
        // Listen for tab change
        document.addEventListener("DOMContentLoaded", function () {
            var triggerTabList = document.querySelectorAll('#myTab button[data-bs-toggle="tab"]');

            triggerTabList.forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', function (event) {
                    // Save the active tab ID to localStorage
                    localStorage.setItem('activeTab', event.target.getAttribute('data-bs-target'));
                });
            });

            // On page load, check if an active tab is saved
            var activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                var tabTrigger = document.querySelector('#myTab button[data-bs-target="' + activeTab + '"]');
                if (tabTrigger) {
                    var tab = new bootstrap.Tab(tabTrigger);
                    tab.show();
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Chart data from PHP
            const departments = <?php echo json_encode(array_keys($departmentCounts)); ?>;
            const counts = <?php echo json_encode(array_values($departmentCounts)); ?>;

            const ctx = document.getElementById('manpowerChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: departments,
                    datasets: [{
                        label: 'Employee Distribution',
                        data: counts,
                        backgroundColor: [
                            '#043f9d', '#1e56a0', '#3c79c0', '#6aa9e9', '#90c2f1',
                            '#b5d5f8', '#f1c232', '#e67e22', '#27ae60', '#9b59b6', '#c0392b'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { boxWidth: 15, font: { size: 13 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let total = context.chart._metasets[0].total;
                                    let value = context.parsed;
                                    let percentage = ((value / total) * 100).toFixed(1) + '%';
                                    return `${context.label}: ${value} (${percentage})`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <script>
        let floorSpaceChart = null; // Global reference to the chart

        function renderFloorSpaceChart(names, areas) {
            // Filter out zero or empty areas if needed
            const filteredNames = [];
            const filteredAreas = [];
            areas.forEach((area, index) => {
                if (area > 0) {
                    filteredNames.push(names[index]);
                    filteredAreas.push(area);
                }
            });

            const ctx = document.getElementById('floorSpaceChart').getContext('2d');

            if (floorSpaceChart) {
                // Update existing chart
                floorSpaceChart.data.labels = filteredNames;
                floorSpaceChart.data.datasets[0].data = filteredAreas;
                floorSpaceChart.update();
            } else {
                // Create chart for the first time 
                floorSpaceChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: filteredNames,
                        datasets: [{
                            label: 'Floor Space Distribution (m²)',
                            data: filteredAreas,
                            backgroundColor: [
                                '#043f9d', '#1e56a0', '#3c79c0', '#6aa9e9',
                                '#90c2f1', '#f1c232', '#e67e22', '#27ae60',
                                '#9b59b6', '#c0392b', '#f39c12', '#7f8c8d'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { boxWidth: 15, font: { size: 13 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        let total = context.chart._metasets[0].total;
                                        let value = context.parsed;
                                        let percentage = ((value / total) * 100).toFixed(1) + '%';
                                        return `${context.label}: ${value} m² (${percentage})`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // Initial render on page load
        document.addEventListener("DOMContentLoaded", function () {
            const floorSpaceNames = <?php echo json_encode($floorSpaceNames); ?>;
            const floorSpaceAreas = <?php echo json_encode($floorSpaceAreas); ?>;
            renderFloorSpaceChart(floorSpaceNames, floorSpaceAreas);
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // PHP totals converted into JS variables
            const totalPDC = <?= json_encode($total_pdc_sales) ?>;
            const totalLocal = <?= json_encode(isset($totals['Local']) ? array_sum($totals['Local']) : 0) ?>;
            const totalIOR = <?= json_encode(isset($totals['IOR & Commissioning']) ? array_sum($totals['IOR & Commissioning']) : 0) ?>;

            const labels = ['PDC Sales', 'Local Sales', 'Import Sales'];
            const data = [totalPDC, totalLocal, totalIOR];

            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales Distribution',
                        data: data,
                        backgroundColor: ['#043f9d', '#577fb3', '#0575f5'],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                font: { size: 13 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let total = context.chart._metasets[0].total;
                                    let value = context.parsed;
                                    let percentage = ((value / total) * 100).toFixed(1) + '%';
                                    return `${context.label}: $${value.toLocaleString()} (${percentage})`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <script>
        function updatePercentages() {
            let total = 0;         // Total of all floor areas
            let prodSupport = 0;   // Production Support Area
            let names = [];
            let areas = [];

            $('.floor-input').each(function () {
                const row = $(this).closest('tr');
                const name = row.find('td:first').text().trim();
                const value = parseFloat($(this).val()) || 0;

                total += value;

                if (name !== "Direct Production (Assembly)") {
                    prodSupport += value;
                }

                names.push(name);
                areas.push(value);
            });

            // Update read-only small text
            $('#productionSupport').text(prodSupport);
            $('#totalFloorSpace').text(total);

            // Update percentages
            $('tr').each(function () {
                const row = $(this);
                const rowName = row.find('td:first').text().trim();
                const input = row.find('input');
                let percent = 0;

                if (rowName === "Production Support Area") {
                    percent = (prodSupport / total * 100).toFixed(2);
                } else if (rowName !== "Total Floor Space") {
                    percent = parseFloat(input.val()) || 0;
                    percent = (percent / total * 100).toFixed(2);
                }

                row.find('.percentage-cell').html(percent + '%');
            });

            // Update chart
            renderFloorSpaceChart(names.concat(['Production Support Area']), areas.concat([prodSupport]));
        }

        $(document).ready(function () {
            // Initial calculation on page load
            updatePercentages();

            $('#saveFloorSpace').on('click', function () {
                const data = {};
                $('input[name^="floor_space"]').each(function () {
                    const id = $(this).attr('name').match(/\d+/)[0];
                    const value = $(this).val();
                    data[id] = value;
                });

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: { floor_space: data, action: 'save_floor_space' },
                    success: function (response) {
                        alert('Floor space saved successfully!');
                        updatePercentages(); // recalc after save
                    },
                    error: function (xhr, status, error) {
                        alert('Error saving floor space: ' + error);
                    }
                });
            });
        });
    </script>

    <script>// Save Report Functionality
        document.getElementById('saveReportBtn').addEventListener('click', function () {
            saveBusinessReport();
        });

        function saveBusinessReport() {
            const saveBtn = document.getElementById('saveReportBtn');
            const originalText = saveBtn.innerHTML;

            // Show loading state
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Saving...';
            saveBtn.disabled = true;

            // Collect all form data
            const formData = {
                action: 'save_business_report',
                significantOrder: document.querySelector('textarea[name="significantOrder"]').value,
                synergyWithFe: document.querySelector('textarea[name="synergyWithFe"]').value,
                marketSituation: document.querySelector('textarea[name="marketSituation"]').value,
                keyCompetitors: document.querySelector('textarea[name="keyCompetitors"]').value,
                newBusinessIdeas: document.querySelector('textarea[name="newBusinessIdeas"]').value,
                businessStrategies: document.querySelector('textarea[name="businessStrategies"]').value,
                legalMatters: document.querySelector('textarea[name="legalMatters"]').value,
                marketingActivities: document.querySelector('textarea[name="marketingActivities"]').value,
                deliverySchedule: document.querySelector('textarea[name="deliverySchedule"]').value,
                workingCondition: document.querySelector('textarea[name="workingCondition"]').value,
                keyHrMovement: document.querySelector('textarea[name="keyHrMovement"]').value,
                hiringPlan: document.querySelector('textarea[name="hiringPlan"]').value,
                inventory: document.querySelector('textarea[name="inventory"]').value,
                supplierProcurement: document.querySelector('textarea[name="supplierProcurement"]').value,
                capitalInvestment: document.querySelector('textarea[name="capitalInvestment"]').value,
                intercompanyCoordination: document.querySelector('textarea[name="intercompanyCoordination"]').value,
                targetSales: document.querySelector('textarea[name="targetSales"]').value,
                cashFlow: document.querySelector('textarea[name="cashFlow"]').value,
                debtCollectionStatus: document.querySelector('textarea[name="debtCollectionStatus"]').value
            };

            // Send AJAX request
            $.ajax({
                url: '', // Empty string means current file
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        showNotification('Report saved successfully!', 'success');
                    } else {
                        showNotification('Error saving report: ' + response.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    showNotification('Error saving report: ' + error, 'error');
                },
                complete: function () {
                    // Restore button state
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                }
            });
        }

        // Notification function
        function showNotification(message, type) {
            // Remove existing notifications
            $('.custom-notification').remove();

            const notification = $(`
        <div class="custom-notification position-fixed top-0 end-0 m-3 p-3 rounded shadow-lg ${type === 'success' ? 'bg-success' : 'bg-danger'} text-white" 
             style="z-index: 9999; min-width: 300px;">
            <div class="d-flex align-items-center">
                <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
                <span>${message}</span>
            </div>
        </div>
    `);

            $('body').append(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);
        }

        // Optional: Auto-save when leaving the page
        window.addEventListener('beforeunload', function (e) {
            // You can add auto-save logic here if needed
            saveBusinessReport();
        });
    </script>

    <script>
        function printReport() {
            // Show loading state
            const originalButton = document.querySelector('button[onclick="printReport()"]');
            const originalText = originalButton.innerHTML;
            originalButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Generating Print...';
            originalButton.disabled = true;

            // Activate all tabs temporarily to render charts
            activateAllTabsForPrint().then(() => {
                // Generate chart images from all tabs
                generateAllChartImages().then((chartImages) => {
                    const tabContents = document.querySelectorAll('.tab-pane');
                    const coverImageUrl = '../Images/FE-Biz-Report.png';
                    const companyLogo = '../Images/FSMBE-Harwal-logo.png';

                    let printContent = createStructuredPrintDocument(tabContents, coverImageUrl, companyLogo, chartImages);
                    openPrintWindow(printContent);

                    // Restore original button state
                    originalButton.innerHTML = originalText;
                    originalButton.disabled = false;
                }).catch(error => {
                    console.error('Error generating charts:', error);
                    originalButton.innerHTML = originalText;
                    originalButton.disabled = false;
                    alert('Error generating print. Please try again.');
                });
            });
        }

        function activateAllTabsForPrint() {
            return new Promise((resolve) => {
                // Store current active tab
                const currentActiveTab = document.querySelector('.nav-link.active');
                const currentActivePane = document.querySelector('.tab-pane.active');

                // Activate each tab briefly to render charts
                const tabs = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');
                let tabsProcessed = 0;

                tabs.forEach((tab, index) => {
                    setTimeout(() => {
                        // Activate tab
                        const tabInstance = new bootstrap.Tab(tab);
                        tabInstance.show();

                        // Wait for tab to render
                        setTimeout(() => {
                            tabsProcessed++;

                            // When all tabs processed, restore original state and resolve
                            if (tabsProcessed === tabs.length) {
                                setTimeout(() => {
                                    if (currentActiveTab) {
                                        const originalTab = new bootstrap.Tab(currentActiveTab);
                                        originalTab.show();
                                    }
                                    resolve();
                                }, 500);
                            }
                        }, 800); // Wait for charts to render
                    }, index * 1000); // Stagger tab activation
                });

                // Fallback if no tabs
                if (tabs.length === 0) {
                    resolve();
                }
            });
        }

        function generateAllChartImages() {
            return new Promise((resolve) => {
                const chartImages = {};
                const chartSelectors = [
                    'salesChart',
                    'manpowerChart',
                    'floorSpaceChart'
                ];

                let attempts = 0;
                const maxAttempts = 3;

                function captureCharts() {
                    chartSelectors.forEach(selector => {
                        const canvas = document.getElementById(selector);
                        if (canvas) {
                            try {
                                // Check if canvas has valid dimensions
                                if (canvas.width > 0 && canvas.height > 0) {
                                    // Use the original canvas directly
                                    chartImages[selector] = canvas.toDataURL('image/png', 1.0);
                                } else {
                                    console.warn(`Canvas ${selector} has zero dimensions`);
                                }
                            } catch (error) {
                                console.error(`Error capturing ${selector}:`, error);
                            }
                        }
                    });

                    // If we have at least one chart or max attempts reached, resolve
                    if (Object.keys(chartImages).length > 0 || attempts >= maxAttempts) {
                        resolve(chartImages);
                    } else {
                        // Retry after a delay
                        attempts++;
                        setTimeout(captureCharts, 500);
                    }
                }

                // Start capturing
                captureCharts();
            });
        }

        function createStructuredPrintDocument(tabContents, coverImageUrl, companyLogo, chartImages) {
            const currentDate = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const generatedDate = new Date().toLocaleDateString();
            const currentMonth = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            return `
    <html>
        <head>
            <title>Corporate Sales Objectives & Key Results - ${currentDate}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <style>
                ${getStructuredPrintStyles(currentDate)}
            </style>
        </head>
        <body>
            <!-- Page 1: Cover Page -->
            <div class="cover-page">
                <div class="cover-container">
                    <div class="cover-content">
                        <div class="cover-text">
                            <h1 class="cover-title">Corporate Sales Objectives & Key Results</h1>
                            <div class="cover-period">${currentDate}</div>
                            <div class="cover-generated">Generated on: ${generatedDate}</div>
                            <img src="${companyLogo}" class="mt-3" style="height: 14mm; width: auto;" alt="Company Logo">
                        </div>
                        <div class="cover-image">
                            <img src="${coverImageUrl}" class="cover-img" alt="Business Report" onerror="this.style.display='none'">
                        </div>
                    </div>
                </div>
                <div class="page-footer">
                    <div class="footer-content">
                        <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                        <span class="page-number">1</span>
                        <span class="current-month">${currentMonth}</span>
                    </div>
                </div>
            </div>

            <!-- Page 2: Table of Contents -->
            <div class="toc-page">
                <div class="page-content">
                    <div class="page-header">
                        <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                        <div class="header-right">Table of Contents</div>
                    </div>
                    <div class="toc-container">
  
                        <div class="toc-content mt-3">
                            <div class="toc-section main-section">
                                <span class="toc-item">ORDER / SALES</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Order Book up-to-date</span>
                                <span class="toc-page-number">3</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Any significant order with high risk / low profitability</span>
                                <span class="toc-page-number">5</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Synergy with FE</span>
                                <span class="toc-page-number">5</span>
                            </div>

                            <div class="toc-section main-section">
                                <span class="toc-item">Business Situation & Strategies</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Market situation in specific market</span>
                                <span class="toc-page-number">5</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Key competitors in specific market</span>
                                <span class="toc-page-number">5</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">New business ideas</span>
                                <span class="toc-page-number">5</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Specific business strategies</span>
                                <span class="toc-page-number">6</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Legal matters, if any</span>
                                <span class="toc-page-number">6</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Marketing activities - ongoing / plan</span>
                                <span class="toc-page-number">6</span>
                            </div>
                            
                            <div class="toc-section main-section">
                                <span class="toc-item">Operation & HR Plans</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Delivery schedule</span>
                                <span class="toc-page-number">7</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Manpower</span>
                                <span class="toc-page-number">7</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Floor space and production capacity</span>
                                <span class="toc-page-number">8</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Working condition</span>
                                <span class="toc-page-number">9</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Key HR movement</span>
                                <span class="toc-page-number">9</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Hiring plan</span>
                                <span class="toc-page-number">9</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Inventory</span>
                                <span class="toc-page-number">10</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Supplier / Procurement</span>
                                <span class="toc-page-number">10</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Capital investment</span>
                                <span class="toc-page-number">10</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Coordination with intercompany</span>
                                <span class="toc-page-number">10</span>
                            </div>
                            
                            <div class="toc-section main-section">
                                <span class="toc-item">Finance</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Sales & P&L on target?</span>
                                <span class="toc-page-number">11</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Cash flow</span>
                                <span class="toc-page-number">11</span>
                            </div>
                            <div class="toc-subsection">
                                <span class="toc-item">Debt collection status</span>
                                <span class="toc-page-number">11</span>
                            </div>                
                        </div>
                    </div>
                </div>
                <div class="page-footer">
                    <div class="footer-content">
                        <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                        <span class="page-number">2</span>
                        <span class="current-month">${currentMonth}</span>
                    </div>
                </div>
            </div>

            <!-- Content Pages - Strict Layout -->
            <div class="print-container">
                <!-- Page 3: Order Book & Sales Breakdown -->
                <div class="content-page page-3">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Orders & Sales Plan</div>
                        </div>
                        ${generatePage3Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">3</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 4: Forecast & Synergy -->
                <div class="content-page page-4">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Orders & Sales Plan</div>
                        </div>
                        ${generatePage4Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">4</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 5: Market Analysis -->
                <div class="content-page page-5">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Business Situation & Strategies</div>
                        </div>
                        ${generatePage5Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">5</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 6: Strategies & Activities -->
                <div class="content-page page-6">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Business Situation & Strategies</div>
                        </div>
                        ${generatePage6Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">6</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 7: Operations & HR -->
                <div class="content-page page-7">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Operations</div>
                        </div>
                        ${generatePage7Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">7</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 8: Floor Space -->
                <div class="content-page page-8">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Operations</div>
                        </div>
                        ${generatePage8Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">8</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 9: HR & Working Conditions -->
                <div class="content-page page-9">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Operations</div>
                        </div>
                        ${generatePage9Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">9</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 10: Inventory & Procurement -->
                <div class="content-page page-10">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Operations</div>
                        </div>
                        ${generatePage10Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">10</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>

                <!-- Page 11: Financial Overview -->
                <div class="content-page page-11">
                    <div class="page-content">
                        <div class="page-header">
                            <div class="header-left">FSMBE Management Monthly BIZ Report</div>
                            <div class="header-right">Finance</div>
                        </div>
                        ${generatePage11Content(tabContents, chartImages)}
                    </div>
                    <div class="page-footer">
                        <div class="footer-content">
                            <img src="${companyLogo}" class="footer-logo" alt="Company Logo">
                            <span class="page-number">11</span>
                            <span class="current-month">${currentMonth}</span>
                        </div>
                    </div>
                </div>
            </div>
        </body>
    </html>
    `;
        }

        function getStructuredPrintStyles(currentDate) {
            return `
    @media print {
        @page {
            margin: 15mm 10mm 15mm 10mm;
            size: A4 landscape;
        }

        @page :first {
            margin: 0mm;
            size: A4 landscape;
        }

        @page :nth(2) {
            margin: 15mm 10mm 15mm 10mm;
            size: A4 landscape;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            color: #333 !important;
            font-family: "Segoe UI", "Arial", sans-serif !important;
            font-size: 10px !important;
            line-height: 1.2 !important;
            position: relative;
        }

        /* Page Structure */
        .cover-page, .toc-page, .content-page {
            position: relative;
            width: 100%;
            height: 100vh;
            page-break-after: always;
        }

        .page-content {
            padding: 5mm 8mm 12mm 5mm;
            height: calc(100vh - 12mm);
            overflow: hidden;
        }

        /* Page Header - Split Layout */
        .page-header {
            background: linear-gradient(135deg, #043f9d, #1e56a0) !important;
            color: white !important;
            padding: 6px 10px !important;
            border-radius: 4px !important;
            margin: 0 0 4mm 0 !important;
            font-size: 11px !important;
            font-weight: bold !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            flex: 1;
            text-align: left;
        }

        .header-right {
            flex: 1;
            text-align: right;
        }

        /* Page Footer - Compact */
        .page-footer {
            height: 12mm;
            padding: 2mm 10mm 10mm 10mm;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .footer-logo {
            height: 6mm;
            width: auto;
        }

        .page-number {
            font-size: 9px !important;
            color: #666 !important;
            font-weight: 500;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .current-month {
            font-size: 9px !important;
            color: #666 !important;
            font-weight: 500;
        }

        /* Hide footer from cover */
        .cover-page .page-footer {
            display: none !important;
        }

        /* Cover Page Styles */
        .cover-page {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .cover-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cover-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 90%;
            max-width: 250mm;
            gap: 25px;
        }

        .cover-text {
            flex: 1;
            text-align: left;
        }

        .cover-title {
            font-size: 28px !important;
            font-weight: bold !important;
            color: #043f9d !important;
            margin: 0 0 12px 0 !important;
            line-height: 1.1 !important;
            text-transform: uppercase;
        }

        .cover-period {
            font-size: 18px !important;
            color: #666 !important;
            margin: 0 0 6px 0 !important;
            font-weight: 500;
        }

        .cover-generated {
            font-size: 12px !important;
            color: #999 !important;
            font-style: italic;
        }

        .cover-image {
            flex: 1;
            text-align: center;
        }

        .cover-img {
            max-width: 100% !important;
            max-height: 140mm !important;
            height: auto !important;
            border-radius: 6px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        /* Table of Contents Styles */
        .toc-page {
            background: white;
        }

        .toc-container {
            width: 100%;
            height: calc(100% - 15mm); /* Account for header height */
            overflow: hidden;
        }

        .toc-title {
            font-size: 16px !important;
            font-weight: bold !important;
            color: #043f9d !important;
            text-align: center !important;
            margin: 3mm 0 4mm 0 !important;
            padding-bottom: 1.5mm;
            border-bottom: 1.5px solid #043f9d;
        }

        .toc-content {
            display: flex;
            flex-direction: column;
            gap: 0.5mm;
            height: calc(100% - 15mm);
            overflow-y: auto;
        }

        .toc-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1mm 0.5mm;
            border-bottom: 0.3px solid #e0e0e0;
            font-size: 9px !important;
            page-break-inside: avoid;
            min-height: 3mm;
        }

        .toc-subsection {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8mm 0.5mm 0.8mm 3mm;
            border-bottom: 0.2px solid #f0f0f0;
            font-size: 8px !important;
            page-break-inside: avoid;
            min-height: 2.5mm;
        }

        .main-section {
            background-color: #f0f4ff !important;
            font-weight: 600 !important;
            border-left: 2px solid #043f9d;
            margin-top: 0.3mm;
        }

        .toc-item {
            color: #333 !important;
            flex: 1;
        }

        .main-section .toc-item {
            font-weight: 600 !important;
            color: #043f9d !important;
        }

        .toc-page-number {
            font-weight: 500 !important;
            color: #043f9d !important;
            font-size: 8px !important;
            min-width: 8px;
            text-align: right;
        }

        /* Content Pages */
        .print-container {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Section Styling */
        .content-section {
            background: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 4px !important;
            padding: 2mm !important;
            margin-bottom: 3mm !important;
            page-break-inside: avoid;
        }

        .section-title {
            color: #043f9d !important;
            font-weight: 600 !important;
            font-size: 11px !important;
            margin: 0 0 1.5mm 0 !important;
            border-bottom: 1px solid #043f9d;
            padding-bottom: 0.8mm;
        }

        /* Compact Tables with Larger Font */
        .print-table {
            width: 100% !important;
            page-break-inside: avoid !important;
            border-collapse: collapse !important;
            font-size: 9px !important;
            margin: 1.5mm 0 !important;
        }

        .print-table th:first-child {
            text-align: left !important;
        }

        .print-table th:nth-child(n+2),
        .print-table td:nth-child(n+2) {
            text-align: center !important;
        }

        .print-table th {
            background: #043f9d !important;
            color: white !important;
            padding: 5px 4px !important;
            border: 1px solid #043f9d !important;
            font-weight: 600 !important;
            text-align: center !important;
            font-size: 9px !important;
        }

        .print-table td {
            padding: 4px 3px !important;
            border: 1px solid #ddd !important;
            vertical-align: middle !important;
            font-size: 9px !important;
        }

        .table-summary {
            background: #e9ecef !important;
            font-weight: 600 !important;
        }

        /* Charts - Different Sizes */
        .chart-container {
            page-break-inside: avoid !important;
            margin: 2mm 0 !important;
            text-align: center !important;
            padding: 1mm;
        }

        .chart-title {
            color: #043f9d !important;
            font-weight: 600 !important;
            margin-bottom: 1mm !important;
            font-size: 9px !important;
        }

        /* Small chart for sales breakdown */
        .chart-image.small {
            max-width: 100% !important;
            height: auto !important;
            max-height: 35mm !important;
            display: block !important;
            margin: 0 auto !important;
        }

        /* Large charts for manpower and floor space */
        .chart-image.large {
            max-width: 100% !important;
            height: auto !important;
            max-height: 70mm !important;
            display: block !important;
            margin: 0 auto !important;
        }

        /* Text Content - Very Compact for Significant Orders & Synergy */
        .content-heading {
            font-weight: 600 !important;
            font-size: 10px !important;
            color: #333 !important;
            margin: 1.5mm 0 1mm 0 !important;
        }

        .print-textarea {
            min-height: 6mm !important;
            max-height: 6mm !important;
            padding: 0.8mm !important;
            border: 1px solid #ccc !important;
            border-radius: 3px !important;
            background: white !important;
            margin-bottom: 1.5mm !important;
            font-size: 9px !important;
            line-height: 1.1 !important;
            white-space: pre-wrap !important;
            word-wrap: break-word !important;
            resize: none !important;
            overflow: hidden !important;
            height: 6mm !important;
        }

        .print-textarea.expandable {
            min-height: 15mm !important;
            max-height: 50mm !important;
            height: auto !important;
            flex-grow: 1;
            white-space: pre-wrap !important;  /* This preserves newlines and spaces */
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        /* Layouts */
        .two-column {
            display: flex !important;
            gap: 3mm !important;
            margin-bottom: 3mm !important;
            align-items: stretch;
        }

        .two-column.large {
            gap: 4mm !important;
            margin-bottom: 4mm !important;
        }

        .column {
            flex: 1 !important;
            min-width: 0 !important;
            display: flex;
            flex-direction: column;
        }

        .full-width {
            width: 100% !important;
            margin-bottom: 3mm !important;
        }

        .single-full-width {
            width: 100% !important;
            margin-bottom: 2mm !important;
        }

        /* Hide interactive elements */
        .nav-tabs, .btn, .position-fixed, .position-absolute {
            display: none !important;
        }

        /* Force colors in print */
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Prevent content overflow and page breaks */
        .content-section, .print-table, .chart-container {
            max-width: 100% !important;
            overflow: hidden !important;
            page-break-inside: avoid !important;
        }

        /* Ensure TOC fits on one page */
        .toc-page {
            height: 100vh !important;
        }

        .toc-content {
            max-height: calc(100vh - 30mm) !important;
            overflow: hidden !important;
        }

        /* Prevent textareas from creating new pages */
        .print-textarea {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
    `;
        }

        // Page Content Generation Functions
        function generatePage3Content(tabContents, chartImages) {
            const orderTab = Array.from(tabContents).find(tab => tab.id === 'order');
            if (!orderTab) return '<div>Content not available</div>';

            const orderBookTable = extractOrderBookTable(orderTab);
            const salesSummaryTable = extractSalesSummaryTable(orderTab);
            const salesChartContent = chartImages.salesChart ?
                `<div class="chart-container">
            <div class="chart-title">Sales Breakdown</div>
            <img src="${chartImages.salesChart}" class="chart-image small" alt="Sales Breakdown Chart">
         </div>` :
                '<div class="chart-container"><div class="chart-title">Sales Breakdown Chart</div><div>Chart not available</div></div>';

            return `
        <div class="content-section full-width">
            <div class="section-title">Sales (Purchase Order Received)</div>
            ${orderBookTable}
        </div>
        
        <div class="content-section full-width">
            <div class="section-title">Sales Summary</div>
            ${salesSummaryTable}
        </div>
        
        <div class="content-section full-width">
            <div class="section-title">Sales Breakdown Graph</div>
            ${salesChartContent}
        </div>
    `;
        }

        function generatePage4Content(tabContents, chartImages) {
            const orderTab = Array.from(tabContents).find(tab => tab.id === 'order');
            if (!orderTab) return '<div>Content not available</div>';

            const forecastTable = extractForecastTable(orderTab);
            const forecastSummaryTable = extractForecastSummaryTable(orderTab);

            return `
        <div class="content-section full-width">
            <div class="section-title">Total Forecast Sales (Official from AWS)</div>
            ${forecastTable}
        </div>
        
        <div class="content-section full-width">
            <div class="section-title">Forecast Summary</div>
            ${forecastSummaryTable}
        </div>
    `;
        }

        function generatePage5Content(tabContents, chartImages) {
            const orderTab = Array.from(tabContents).find(tab => tab.id === 'order');
            const businessTab = Array.from(tabContents).find(tab => tab.id === 'business');
            if (!businessTab) return '<div>Content not available</div>';

            const significantOrders = extractTextareaContent(orderTab, 'significantOrder');
            const synergyWithFe = extractTextareaContent(orderTab, 'synergyWithFe');
            const marketSituation = extractTextareaContent(businessTab, 'marketSituation');
            const keyCompetitors = extractTextareaContent(businessTab, 'keyCompetitors');
            const newBusinessIdeas = extractTextareaContent(businessTab, 'newBusinessIdeas');

            return `
        <div class="content-section full-width">
            <div class="section-title">Any significant order with high risk / low profitability</div>
            <div class="print-textarea">${significantOrders}</div>
        </div>

        <div class="content-section full-width">
            <div class="section-title">Synergy with FE</div>
            <div class="print-textarea">${synergyWithFe}</div>
        </div>

        <div class="content-section">
            <div class="section-title">Market situation in specific market</div>
            <div class="print-textarea expandable">${marketSituation}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Key competitors in specific market</div>
            <div class="print-textarea expandable">${keyCompetitors}</div>
        </div>
        <div class="content-section">
            <div class="section-title">New business ideas</div>
            <div class="print-textarea expandable">${newBusinessIdeas}</div>
        </div>
    `;
        }

        function generatePage6Content(tabContents, chartImages) {
            const businessTab = Array.from(tabContents).find(tab => tab.id === 'business');
            if (!businessTab) return '<div>Content not available</div>';

            const businessStrategies = extractTextareaContent(businessTab, 'businessStrategies');
            const legalMatters = extractTextareaContent(businessTab, 'legalMatters');
            const marketingActivities = extractTextareaContent(businessTab, 'marketingActivities');

            return `
        <div class="content-section">
            <div class="section-title">Specific business strategies</div>
            <div class="print-textarea expandable">${businessStrategies}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Legal matters, if any</div>
            <div class="print-textarea expandable">${legalMatters}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Marketing sctivities - ongoing / plan</div>
            <div class="print-textarea expandable">${marketingActivities}</div>
        </div>
    `;
        }

        function generatePage7Content(tabContents, chartImages) {
            const operationTab = Array.from(tabContents).find(tab => tab.id === 'operation');
            if (!operationTab) return '<div>Content not available</div>';

            const deliverySchedule = extractTextareaContent(operationTab, 'deliverySchedule');
            const manpowerTable = extractManpowerTable(operationTab);
            const manpowerChart = chartImages.manpowerChart ?
                `<div class="chart-container">
            <div class="chart-title">Manpower Distribution</div>
            <img src="${chartImages.manpowerChart}" class="chart-image large" alt="Manpower Chart">
         </div>` :
                '<div class="chart-container"><div class="chart-title">Manpower Chart</div><div>Chart not available</div></div>';

            return `
        <div class="">
            <div class="section-title">Delivery schedule</div>
            <div class="print-textarea expandable">${deliverySchedule}</div>
        </div>
        <div class="two-column large">
            <div class="column">
                <div class="content-section" style="flex: 1; min-height: 80mm;">
                    <div class="section-title">Manpower</div>
                    ${manpowerTable}
                </div>
            </div>
            <div class="column">
                <div class="content-section" style="flex: 1; min-height: 80mm;">
                    <div class="section-title">Manpower Chart</div>
                    ${manpowerChart}
                </div>
            </div>
        </div>
    `;
        }

        function generatePage8Content(tabContents, chartImages) {
            const floorSpaceTable = generateFloorSpaceTable();
            const floorSpaceChart = chartImages.floorSpaceChart ?
                `<div class="chart-container">
            <div class="chart-title">Floor Space Allocation</div>
            <img src="${chartImages.floorSpaceChart}" class="chart-image large" alt="Floor Space Chart">
         </div>` :
                '<div class="chart-container"><div class="chart-title">Floor Space Chart</div><div>Chart not available</div></div>';

            return `
        <div class="two-column large">
            <div class="column">
                <div class="content-section" style="flex: 1; min-height: 80mm;">
                    <div class="section-title">Floor Space Table</div>
                    ${floorSpaceTable}
                </div>
            </div>
            <div class="column">
                <div class="content-section" style="flex: 1; min-height: 80mm;">
                    <div class="section-title">Floor Space Chart</div>
                    ${floorSpaceChart}
                </div>
            </div>
        </div>
    `;
        }

        function generatePage9Content(tabContents, chartImages) {
            const operationTab = Array.from(tabContents).find(tab => tab.id === 'operation');
            if (!operationTab) return '<div>Content not available</div>';

            const workingCondition = extractTextareaContent(operationTab, 'workingCondition');
            const keyHrMovement = extractTextareaContent(operationTab, 'keyHrMovement');
            const hiringPlan = extractTextareaContent(operationTab, 'hiringPlan');

            return `
        <div class="content-section">
            <div class="section-title">Working condition</div>
            <div class="print-textarea expandable">${workingCondition}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Key HR movement</div>
            <div class="print-textarea expandable">${keyHrMovement}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Hiring plan</div>
            <div class="print-textarea expandable">${hiringPlan}</div>
        </div>
    `;
        }

        function generatePage10Content(tabContents, chartImages) {
            const operationTab = Array.from(tabContents).find(tab => tab.id === 'operation');
            if (!operationTab) return '<div>Content not available</div>';

            const inventory = extractTextareaContent(operationTab, 'inventory');
            const supplierProcurement = extractTextareaContent(operationTab, 'supplierProcurement');
            const capitalInvestment = extractTextareaContent(operationTab, 'capitalInvestment');
            const intercompanyCoordination = extractTextareaContent(operationTab, 'intercompanyCoordination');

            return `
        <div class="content-section">
            <div class="section-title">Inventory</div>
            <div class="print-textarea expandable">${inventory}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Supplier / Procurement</div>
            <div class="print-textarea expandable">${supplierProcurement}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Capital investment</div>
            <div class="print-textarea expandable">${capitalInvestment}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Coordination with intercompany</div>
            <div class="print-textarea expandable">${intercompanyCoordination}</div>
        </div>
    `;
        }

        function generatePage11Content(tabContents, chartImages) {
            const financeTab = Array.from(tabContents).find(tab => tab.id === 'finance');
            if (!financeTab) return '<div>Content not available</div>';

            const targetSales = extractTextareaContent(financeTab, 'targetSales');
            const cashFlow = extractTextareaContent(financeTab, 'cashFlow');
            const debtCollectionStatus = extractTextareaContent(financeTab, 'debtCollectionStatus');

            return `
        <div class="content-section">
            <div class="section-title">Sales & P&L on Target?</div>
            <div class="print-textarea expandable">${targetSales}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Cashflow</div>
            <div class="print-textarea expandable">${cashFlow}</div>
        </div>
        <div class="content-section">
            <div class="section-title">Debt collection status</div>
            <div class="print-textarea expandable">${debtCollectionStatus}</div>
        </div>
    `;
        }

        // Table Extraction Functions
        function extractOrderBookTable(tab) {
            const tables = tab.querySelectorAll('table');
            const orderBookTable = tables[0];
            if (orderBookTable) {
                const clone = orderBookTable.cloneNode(true);
                removeInteractiveElements(clone);
                clone.classList.add('print-table');
                return clone.outerHTML;
            }
            return 'Order book table not found';
        }

        function extractSalesSummaryTable(tab) {
            const tables = tab.querySelectorAll('table');
            if (tables.length > 1) {
                const summaryTable = tables[1];
                const clone = summaryTable.cloneNode(true);
                removeInteractiveElements(clone);
                clone.classList.add('print-table');
                return clone.outerHTML;
            }
            return 'Sales summary table not found';
        }

        function extractForecastTable(tab) {
            const tables = tab.querySelectorAll('table');
            if (tables.length > 2) {
                const forecastTable = tables[2];
                const clone = forecastTable.cloneNode(true);
                removeInteractiveElements(clone);
                clone.classList.add('print-table');
                return clone.outerHTML;
            }
            return 'Forecast table not found';
        }

        function extractForecastSummaryTable(tab) {
            const tables = tab.querySelectorAll('table');
            if (tables.length > 3) {
                const forecastSummaryTable = tables[3];
                const clone = forecastSummaryTable.cloneNode(true);
                removeInteractiveElements(clone);
                clone.classList.add('print-table');
                return clone.outerHTML;
            }
            return 'Forecast summary table not found';
        }

        function extractManpowerTable(tab) {
            const tables = tab.querySelectorAll('table');
            const manpowerTable = tables[0];
            if (manpowerTable) {
                const clone = manpowerTable.cloneNode(true);
                removeInteractiveElements(clone);
                clone.classList.add('print-table');
                return clone.outerHTML;
            }
            return 'Manpower table not found';
        }

        function generateFloorSpaceTable() {
            // Use the PHP arrays directly from the page
            const floorSpaceNames = <?php echo json_encode($floorSpaceNames); ?>;
            const floorSpaceAreas = <?php echo json_encode($floorSpaceAreas); ?>;

            let tableHTML = `
        <table class="print-table">
            <thead>
                <tr>
                    <th>Production</th>
                    <th class="text-center">A (m²)</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
    `;

            let totalArea = 0;
            floorSpaceAreas.forEach(area => {
                totalArea += parseFloat(area) || 0;
            });

            floorSpaceNames.forEach((name, index) => {
                const area = parseFloat(floorSpaceAreas[index]) || 0;
                const percentage = totalArea > 0 ? ((area / totalArea) * 100).toFixed(1) : 0;

                tableHTML += `
            <tr>
                <td>${name}</td>
                <td class="text-center">${area}</td>
                <td class="text-center">${percentage}%</td>
            </tr>
        `;
            });

            // Add total row
            tableHTML += `
            <tr class="table-summary">
                <td>Total Floor Space</td>
                <td class="text-center">${totalArea}</td>
                <td class="text-center">100%</td>
            </tr>
        </tbody>
    </table>
    `;

            return tableHTML;
        }

        // Helper Functions
        function extractTextareaContent(tab, textareaName) {
            const textarea = tab.querySelector(`textarea[name="${textareaName}"]`);
            if (textarea) {
                // Replace escaped newlines with actual newlines and clean up the content
                let content = textarea.value || 'Not specified';

                // Replace escaped newlines (\\n) with actual newlines
                content = content.replace(/\\\\n/g, '\n');

                // Replace literal \n with actual newlines (in case they're stored literally)
                content = content.replace(/\\n/g, '\n');

                // Replace <br> tags with newlines (if any HTML breaks are present)
                content = content.replace(/<br\s*\/?>/gi, '\n');

                return content;
            }
            return 'Not specified';
        }

        function removeInteractiveElements(element) {
            const elementsToRemove = element.querySelectorAll(
                'button, .btn, .position-absolute, .position-fixed, .nav-tabs, .fa-solid, .form-control-sm'
            );
            elementsToRemove.forEach(el => el.remove());
        }

        function openPrintWindow(printContent) {
            const printWindow = window.open('', '_blank', 'width=1200,height=800,scrollbars=yes');

            printWindow.document.write(printContent);
            printWindow.document.close();

            // Wait for content to load before printing
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();

                // Close window after print
                printWindow.onafterprint = function () {
                    setTimeout(() => {
                        printWindow.close();
                    }, 100);
                };
            }, 1000);
        }
    </script>

    <script>
        // Function to auto-expand a textarea
        function autoExpandTextarea(el) {
            el.style.height = 'auto'; // reset
            el.style.height = el.scrollHeight + 'px'; // expand
        }

        // Auto-expand visible textareas on page load (first tab)
        document.querySelectorAll('.tab-pane.active textarea.auto-expand').forEach(autoExpandTextarea);

        // Hook into Bootstrap tab change event
        var tabEl = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEl.forEach(btn => {
            btn.addEventListener('shown.bs.tab', function (e) {
                // e.target is the newly activated tab button
                var targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
                if (targetPane) {
                    targetPane.querySelectorAll('textarea.auto-expand').forEach(autoExpandTextarea);
                }
            });
        });

        // Optional: auto-expand while typing
        document.querySelectorAll('textarea.auto-expand').forEach(textarea => {
            textarea.addEventListener('input', () => autoExpandTextarea(textarea));
        });
    </script>
</body>

</html>