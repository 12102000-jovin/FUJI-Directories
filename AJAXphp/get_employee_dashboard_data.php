<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Include the database connection file
require("../db_connect.php");

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the start and end months from the request (if provided)
$startMonth = isset($_POST['startMonth']) ? $_POST['startMonth'] : date('Y-m', strtotime('-11 months'));
$endMonth = isset($_POST['endMonth']) ? $_POST['endMonth'] : date('Y-m');
$endDate = (new DateTime($endMonth . '-01'))->modify('last day of this month')->format('Y-m-d');

// =============================== T O T A L  E M P L O Y E E  ( E A C H  M O N T H ) ===============================
// Query to count total employees for each month within the selected timeframe
$employee_each_month_sql = "
WITH RECURSIVE months AS (
  SELECT DATE_FORMAT('$startMonth-01', '%Y-%m-01') AS month
  UNION ALL
  SELECT DATE_ADD(month, INTERVAL 1 MONTH)
  FROM months
  WHERE month < DATE_FORMAT('$endMonth-01', '%Y-%m-01')
)

SELECT
  DATE_FORMAT(months.month, '%Y-%m') AS month,
  COUNT(e.employee_id) AS total_employees_each_month
FROM months
LEFT JOIN employees e
  ON e.start_date <= LAST_DAY(months.month)
     AND (e.last_date > LAST_DAY(months.month) OR e.last_date IS NULL)
GROUP BY months.month
ORDER BY months.month;
";


// Query to count new employees (joined) each month within the selected timeframe
$new_employees_sql = "
SELECT
  DATE_FORMAT(start_date, '%Y-%m') AS month,
  GROUP_CONCAT(first_name ORDER BY start_date) AS employee_names,
  COUNT(employee_id) AS new_employees
FROM employees
WHERE start_date IS NOT NULL
  AND start_date >= '$startMonth-01' 
  AND start_date <= '$endDate'
GROUP BY month
ORDER BY month;
";

// Query to count employees who left (left) each month within the selected timeframe
$left_employees_sql = "
SELECT
  DATE_FORMAT(last_date, '%Y-%m') AS month,
  GROUP_CONCAT(first_name ORDER BY last_date) AS employee_names,
  COUNT(employee_id) AS left_employees
FROM employees
WHERE last_date IS NOT NULL
  AND last_date >= '$startMonth-01' 
  AND last_date <= '$endDate'
  AND is_active = 0
GROUP BY month
ORDER BY month;
";

// Run the queries with error checking
$employee_each_month_result = $conn->query($employee_each_month_sql);
if (!$employee_each_month_result) {
    echo json_encode(['error' => 'Error in employee_each_month query: ' . $conn->error]);
    exit;
}

$new_employees_result = $conn->query($new_employees_sql);
if (!$new_employees_result) {
    echo json_encode(['error' => 'Error in new_employees query: ' . $conn->error]);
    exit;
}

$left_employees_result = $conn->query($left_employees_sql);
if (!$left_employees_result) {
    echo json_encode(['error' => 'Error in left_employees query: ' . $conn->error]);
    exit;
}

// Prepare the data arrays to merge
$total_employees_data = [];
while ($row = $employee_each_month_result->fetch_assoc()) {
    $total_employees_data[$row['month']] = $row['total_employees_each_month'];
}

$new_employees_data = [];
while ($row = $new_employees_result->fetch_assoc()) {
    $new_employees_data[$row['month']] = [
        'employee_names' => $row['employee_names'],
        'new_employees' => $row['new_employees']
    ];
}

$left_employees_data = [];
while ($row = $left_employees_result->fetch_assoc()) {
    $left_employees_data[$row['month']] = [
        'employee_names' => $row['employee_names'],
        'left_employees' => $row['left_employees']
    ];
}

// Prepare chart data arrays
$months = [];
$totalEmployees = [];
$newEmployees = [];
$leftEmployees = [];

$currentMonth = $startMonth;
while (strtotime($currentMonth) <= strtotime($endMonth)) {
    $months[] = $currentMonth;
    $totalEmployees[] = isset($total_employees_data[$currentMonth]) ? (int) $total_employees_data[$currentMonth] : 0;
    $newEmployees[] = isset($new_employees_data[$currentMonth]) ? (int) $new_employees_data[$currentMonth]['new_employees'] : 0;
    $leftEmployees[] = isset($left_employees_data[$currentMonth]) ? (int) $left_employees_data[$currentMonth]['left_employees'] : 0;

    $currentMonth = date('Y-m', strtotime("$currentMonth +1 month"));
}

// Prepare the final data to be sent back as JSON
$response = [
    'months' => $months,
    'totalEmployees' => $totalEmployees,
    'newEmployees' => $newEmployees,
    'leftEmployees' => $leftEmployees
];

// Send the response as JSON
echo json_encode($response);
?>