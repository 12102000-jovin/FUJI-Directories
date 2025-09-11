<?php
header('Content-Type: application/json');
require_once("../db_connect.php");

if (!isset($_GET['employee_id'])) {
    echo json_encode(['error' => 'No employee selected']);
    exit;
}

$employeeId = intval($_GET['employee_id']);

// Get start_date and end_date (default: last 3 months until today)
$raw_start = $_GET['start_date'] ?? null;
$raw_end   = $_GET['end_date'] ?? null;

$default_end = date('Y-m-d'); // today
$default_start = date('Y-m-d', strtotime('-3 months'));

// Validate dates (expecting YYYY-MM-DD). If invalid, use defaults.
$start_date = $default_start;
$end_date = $default_end;

if ($raw_start) {
    $d = DateTime::createFromFormat('Y-m-d', $raw_start);
    if ($d && $d->format('Y-m-d') === $raw_start) {
        $start_date = $raw_start;
    }
}

if ($raw_end) {
    $d = DateTime::createFromFormat('Y-m-d', $raw_end);
    if ($d && $d->format('Y-m-d') === $raw_end) {
        $end_date = $raw_end;
    }
}

// Ensure start_date <= end_date
if (strtotime($start_date) > strtotime($end_date)) {
    // swap
    $temp = $start_date; 
    $start_date = $end_date;
    $end_date = $temp;
}

// --- Latest leave per type (within date range) ---
$sql = "
    SELECT l1.leave_type, l1.hours, l1.updated_date
    FROM leaves l1
    INNER JOIN (
        SELECT leave_type, MAX(updated_date) AS latest_date
        FROM leaves
        WHERE employee_id = ?
          AND updated_date BETWEEN ? AND ?
        GROUP BY leave_type
    ) l2 ON l1.leave_type = l2.leave_type 
        AND l1.updated_date = l2.latest_date
    WHERE l1.employee_id = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'DB prepare error (latest): ' . $conn->error]);
    exit;
}
$stmt->bind_param("issi", $employeeId, $start_date, $end_date, $employeeId);
$stmt->execute();
$result = $stmt->get_result();

$tableRows = [];
while ($row = $result->fetch_assoc()) {
    $tableRows[] = [
        'leave_type'   => $row['leave_type'],
        'hours'        => (float)$row['hours'],
        'updated_date' => date('d F Y', strtotime($row['updated_date']))
    ];
}
$stmt->close();

// --- Full history for chart (within date range) ---
$history_sql = "
    SELECT leave_type, hours, updated_date
    FROM leaves
    WHERE employee_id = ?
      AND updated_date BETWEEN ? AND ?
    ORDER BY updated_date ASC
";
$stmt2 = $conn->prepare($history_sql);
if (!$stmt2) {
    echo json_encode(['error' => 'DB prepare error (history): ' . $conn->error]);
    exit;
}
$stmt2->bind_param("iss", $employeeId, $start_date, $end_date);
$stmt2->execute();
$historyResult = $stmt2->get_result();

$historyData = [];
$allDatesMap = [];
$allTypesMap = [];

while ($row = $historyResult->fetch_assoc()) {
    $type = $row['leave_type'];
    $hours = (float) $row['hours'];
    // Use consistent label for chart x-axis (YYYY-MM-DD for sorting, then format for display)
    $dateKey = date('Y-m-d', strtotime($row['updated_date']));
    $displayDate = date('d M Y', strtotime($row['updated_date']));

    // keep both key and display
    $historyData[$dateKey][$type] = $hours;
    $allDatesMap[$dateKey] = $displayDate;
    $allTypesMap[$type] = true;
}

$stmt2->close();

// Sort dates ascending
ksort($allDatesMap);
$allDatesKeys = array_keys($allDatesMap);
$allDatesDisplay = array_values($allDatesMap);

$allTypes = array_keys($allTypesMap);

// Prepare datasets for chart (each leave type)
$datasets = [];
$colors = [
    'Annual Lve'     => 'rgba(0, 82, 204, 0.7)',
    'Sick/Personal'  => 'rgba(204, 0, 0, 0.7)',
    'LS Leave'   => 'rgba(255, 153, 0, 0.7)',
    'Other'          => 'rgba(0, 153, 102, 0.7)'
];

foreach ($allTypes as $type) {
    $data = [];
    foreach ($allDatesKeys as $dateKey) {
        $data[] = $historyData[$dateKey][$type] ?? 0;
    }
    $datasets[] = [
        'label'           => $type === 'Annual Lve' ? 'Annual Leave' : $type,
        'data'            => $data,
        'backgroundColor' => $colors[$type] ?? 'rgba(128,128,128,0.7)'
    ];
}

// Return as JSON
echo json_encode([
    'table' => $tableRows,
    'chart' => [
        'labels'   => $allDatesDisplay,
        'datasets' => $datasets
    ],
    'default_dates' => [
        'start' => $start_date,
        'end'   => $end_date
    ]
]);