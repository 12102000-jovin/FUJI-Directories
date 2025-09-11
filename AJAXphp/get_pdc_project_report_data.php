<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require("../db_connect.php");

function getMonthsBetween($start, $end)
{
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $endDate->modify('first day of next month');
    $months = [];
    while ($startDate < $endDate) {
        $months[] = $startDate->format('Y-m');
        $startDate->modify('+1 month');
    }
    return $months;
}

$filterType = $_GET['filterType'] ?? 'month';
$poFilter = $_GET['poFilter'] ?? ''; 
$response = ['labels' => [], 'totalPDCProjects' => [], 'fbnsPDC' => [], 'totalQtyRosd' => [], 'fbnsRosd' => []];

// ================== PDC Projects ==================
// Add PO filter condition to the WHERE clause
$poCondition = "";
if ($poFilter === 'yes') {
    $poCondition = "AND aws_po_no IS NOT NULL AND aws_po_no != ''";
} elseif ($poFilter === 'no') {
    $poCondition = "AND (aws_po_no IS NULL OR aws_po_no = '')";
}


if ($filterType === 'month') {
    $startMonth = $_GET['startMonth'] ?? null;
    $endMonth = $_GET['endMonth'] ?? null;
    if (!$startMonth || !$endMonth || $startMonth > $endMonth) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid month filter parameters']);
        exit;
    }
    $months = getMonthsBetween($startMonth . '-01', $endMonth . '-01');
} elseif ($filterType === 'quarter') {
    function quarterToMonths($qStr)
    {
        list($year, $q) = explode('-Q', $qStr);
        $qNum = intval($q);
        $startMonth = ($qNum - 1) * 3 + 1;
        $endMonth = $startMonth + 2;
        return [sprintf('%04d-%02d', $year, $startMonth), sprintf('%04d-%02d', $year, $endMonth)];
    }
    $startQ = $_GET['startQuarter'] ?? null;
    $endQ = $_GET['endQuarter'] ?? null;
    if (!$startQ || !$endQ) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid quarter filter parameters']);
        exit;
    }
    list($startQStart, $startQEnd) = quarterToMonths($startQ);
    list($endQStart, $endQEnd) = quarterToMonths($endQ);
    $rangeStart = min($startQStart, $endQStart);
    $rangeEnd = max($startQEnd, $endQEnd);
    $months = getMonthsBetween($rangeStart . '-01', $rangeEnd . '-01');
} elseif ($filterType === 'year') {
    $startYear = $_GET['startYear'] ?? null;
    $endYear = $_GET['endYear'] ?? null;
    if (!$startYear || !$endYear || $startYear > $endYear) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid year range']);
        exit;
    }
    $months = [];
    for ($y = (int) $startYear; $y <= (int) $endYear; $y++) {
        for ($m = 1; $m <= 12; $m++) {
            $months[] = sprintf('%04d-%02d', $y, $m);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filterType']);
    exit;
}

// ================== PDC Projects ==================
$startDateStr = $months[0] . '-01';
$lastMonth = end($months);
$lastDay = date('t', strtotime($lastMonth . '-01'));
$endDateStr = $lastMonth . '-' . $lastDay;
$pdc_sql = "
SELECT month, SUM(qty) AS total_qty, GROUP_CONCAT(fbn ORDER BY fbn SEPARATOR ', ') AS fbns
FROM (
    SELECT 
        qty,
        fbn,
        status,
        aws_po_no,
        DATE_FORMAT(
            CASE
                WHEN LOWER(freight_type) = 'air' THEN
                    CASE 
                        WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN DATE_SUB(rOSD_changed, INTERVAL 7 DAY)
                        WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN DATE_SUB(rOSD_po, INTERVAL 7 DAY)
                        WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN DATE_SUB(rOSD_forecast, INTERVAL 7 DAY)
                        ELSE NULL
                    END
                ELSE
                    CASE 
                        WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN DATE_SUB(rOSD_changed, 
                            INTERVAL 
                                CASE 
                                    WHEN SUBSTRING(fbn,1,3) IN ('SYD','MEL','AKL') THEN 2*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('CGK','BKK','SIN','KUL') THEN 4*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN 8*7
                                    ELSE 0
                                END DAY
                        )
                        WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN DATE_SUB(rOSD_po, 
                            INTERVAL 
                                CASE 
                                    WHEN SUBSTRING(fbn,1,3) IN ('SYD','MEL','AKL') THEN 2*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('CGK','BKK','SIN','KUL') THEN 4*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN 8*7
                                    ELSE 0
                                END DAY
                        )
                        WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN DATE_SUB(rOSD_forecast, 
                            INTERVAL 
                                CASE 
                                    WHEN SUBSTRING(fbn,1,3) IN ('SYD','MEL','AKL') THEN 2*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('CGK','BKK','SIN','KUL') THEN 4*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN 8*7
                                    ELSE 0
                                END DAY
                        )
                        ELSE NULL
                    END
            END
        , '%Y-%m') AS month,
        DATE(
            CASE
                WHEN LOWER(freight_type) = 'air' THEN
                    CASE 
                        WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN DATE_SUB(rOSD_changed, INTERVAL 7 DAY)
                        WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN DATE_SUB(rOSD_po, INTERVAL 7 DAY)
                        WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN DATE_SUB(rOSD_forecast, INTERVAL 7 DAY)
                        ELSE NULL
                    END
                ELSE
                    CASE 
                        WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN DATE_SUB(rOSD_changed, 
                            INTERVAL 
                                CASE 
                                    WHEN SUBSTRING(fbn,1,3) IN ('SYD','MEL','AKL') THEN 2*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('CGK','BKK','SIN','KUL') THEN 4*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN 8*7
                                    ELSE 0
                                END DAY
                        )
                        WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN DATE_SUB(rOSD_po, 
                            INTERVAL 
                                CASE 
                                    WHEN SUBSTRING(fbn,1,3) IN ('SYD','MEL','AKL') THEN 2*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('CGK','BKK','SIN','KUL') THEN 4*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN 8*7
                                    ELSE 0
                                END DAY
                        )
                        WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN DATE_SUB(rOSD_forecast, 
                            INTERVAL 
                                CASE 
                                    WHEN SUBSTRING(fbn,1,3) IN ('SYD','MEL','AKL') THEN 2*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('CGK','BKK','SIN','KUL') THEN 4*7
                                    WHEN SUBSTRING(fbn,1,3) IN ('HYD','BOM','DUB','ICN','TPE','HKG','NRT') THEN 8*7
                                    ELSE 0
                                END DAY
                        )
                        ELSE NULL
                    END
            END
        ) AS estimated_departure_date
    FROM pdc_projects
    WHERE (status IS NULL OR status NOT IN ('AWS Removed FF','Cancelled'))
    $poCondition
) AS derived
WHERE estimated_departure_date BETWEEN ? AND ?
GROUP BY month;
";

$stmt = $conn->prepare($pdc_sql);
$stmt->bind_param("ss", $startDateStr, $endDateStr);
$stmt->execute();
$pdc_result = $stmt->get_result();
$total_pdc_project_data = [];
while ($row = $pdc_result->fetch_assoc()) {
    $total_pdc_project_data[$row['month']] = ['total_qty' => (int) $row['total_qty'], 'fbns' => $row['fbns']];
}
$stmt->close();

// ================== ROSD Correct ==================
$rosd_sql = "
SELECT 
    fbn, 
    qty, 
    resolved,
    aws_po_no,
    -- Determine the correct rOSD date
    CASE 
        WHEN approved = '1' AND rOSD_changed IS NOT NULL THEN rOSD_changed
        WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN rOSD_po
        WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN rOSD_forecast
        ELSE NULL
    END AS rOSD_correct
FROM pdc_projects
WHERE 
    (status IS NULL OR status NOT IN ('AWS Removed FF','Cancelled'))
    AND (
        (resolved='PO' AND rOSD_po IS NOT NULL)
        OR (resolved='Forecast' AND rOSD_forecast IS NOT NULL)
        OR (approved='1' AND rOSD_changed IS NOT NULL)
    )
    $poCondition;
";
$stmt = $conn->prepare($rosd_sql);
$stmt->execute();
$rosd_result = $stmt->get_result();
$rosdCorrectData = [];

while ($row = $rosd_result->fetch_assoc()) {
    if (!empty($row['rOSD_correct'])) {
        $dateKey = date('Y-m', strtotime($row['rOSD_correct']));
        $rosdCorrectData[$dateKey]['total_qty'] = $rosdCorrectData[$dateKey]['total_qty'] ?? 0;
        $rosdCorrectData[$dateKey]['total_qty'] += $row['qty'];
        $rosdCorrectData[$dateKey]['fbns'] = $rosdCorrectData[$dateKey]['fbns'] ?? [];
        $rosdCorrectData[$dateKey]['fbns'][] = $row['fbn'];
    }
}
$stmt->close();

// ============ Aggregate by filterType ============
$groupedLabels = [];
$groupedTotalPDC = [];
$groupedFbnsPDC = [];
$groupedTotalRosd = [];
$groupedFbnsRosd = [];
if ($filterType === 'month') {
    foreach ($months as $m) {
        $groupedLabels[] = $m;
        $groupedTotalPDC[] = $total_pdc_project_data[$m]['total_qty'] ?? 0;
        $groupedFbnsPDC[] = $total_pdc_project_data[$m]['fbns'] ?? '';
        $groupedTotalRosd[] = $rosdCorrectData[$m]['total_qty'] ?? 0;
        $groupedFbnsRosd[] = isset($rosdCorrectData[$m]) ? implode(', ', $rosdCorrectData[$m]['fbns']) : '';
    }
} elseif ($filterType === 'quarter') {
    $quarters = [];
    foreach ($months as $m) {
        $year = substr($m, 0, 4);
        $month = (int) substr($m, 5, 2);
        $q = 'Q' . ceil($month / 3);
        $key = "$year-$q";
        $quarters[$key]['totalPDC'] = ($quarters[$key]['totalPDC'] ?? 0) + ($total_pdc_project_data[$m]['total_qty'] ?? 0);
        $quarters[$key]['fbnsPDC'] = ($quarters[$key]['fbnsPDC'] ?? []) + (isset($total_pdc_project_data[$m]['fbns']) ? explode(', ', $total_pdc_project_data[$m]['fbns']) : []);
        $quarters[$key]['totalRosd'] = ($quarters[$key]['totalRosd'] ?? 0) + ($rosdCorrectData[$m]['total_qty'] ?? 0);
        $quarters[$key]['fbnsRosd'] = ($quarters[$key]['fbnsRosd'] ?? []) + (isset($rosdCorrectData[$m]) ? $rosdCorrectData[$m]['fbns'] : []);
    }
    foreach ($quarters as $q => $data) {
        $groupedLabels[] = $q;
        $groupedTotalPDC[] = $data['totalPDC'];
        $groupedFbnsPDC[] = implode(', ', $data['fbnsPDC']);
        $groupedTotalRosd[] = $data['totalRosd'];
        $groupedFbnsRosd[] = implode(', ', $data['fbnsRosd']);
    }
} elseif ($filterType === 'year') {
    $years = [];
    foreach ($months as $m) {
        $y = substr($m, 0, 4);
        $years[$y]['totalPDC'] = ($years[$y]['totalPDC'] ?? 0) + ($total_pdc_project_data[$m]['total_qty'] ?? 0);
        $years[$y]['fbnsPDC'] = ($years[$y]['fbnsPDC'] ?? []) + (isset($total_pdc_project_data[$m]['fbns']) ? explode(', ', $total_pdc_project_data[$m]['fbns']) : []);
        $years[$y]['totalRosd'] = ($years[$y]['totalRosd'] ?? 0) + ($rosdCorrectData[$m]['total_qty'] ?? 0);
        $years[$y]['fbnsRosd'] = ($years[$y]['fbnsRosd'] ?? []) + (isset($rosdCorrectData[$m]) ? $rosdCorrectData[$m]['fbns'] : []);
    }
    foreach ($years as $y => $data) {
        $groupedLabels[] = $y;
        $groupedTotalPDC[] = $data['totalPDC'];
        $groupedFbnsPDC[] = implode(', ', $data['fbnsPDC']);
        $groupedTotalRosd[] = $data['totalRosd'];
        $groupedFbnsRosd[] = implode(', ', $data['fbnsRosd']);
    }
}

$response['labels'] = $groupedLabels;
$response['totalPDCProjects'] = $groupedTotalPDC;
$response['fbnsPDC'] = $groupedFbnsPDC;
$response['totalQtyRosd'] = $groupedTotalRosd;
$response['fbnsRosd'] = $groupedFbnsRosd;

echo json_encode($response);
