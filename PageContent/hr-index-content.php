<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require("../db_connect.php");
require_once("../status_check.php");
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

require_once("../system_role_check.php");

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
$permanent_employees_sql = "SELECT COUNT(*) AS permanent_count FROM employees WHERE employment_type='Full-Time' AND is_active = 1";
$permanent_employees_result = $conn->query($permanent_employees_sql);

// Query to get the number of part-time employees
$part_time_employees_sql = "SELECT COUNT(*) AS part_time_count FROM employees WHERE employment_type='Part-Time' AND is_active = 1";
$part_time_employees_result = $conn->query($part_time_employees_sql);

// Query to get the number of casual employees
$casual_employees_sql = "SELECT COUNT(*) AS casual_count FROM employees WHERE employment_type='Casual' AND is_active = 1";
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

// =============================== G E N D E R  C H A R T ===============================

// Query to get number of female employees
$female_employee_sql = "SELECT COUNT(*) AS female_count FROM employees WHERE gender = 'female'";
$female_employee_result = $conn->query($female_employee_sql);

// Query to get number of male employees
$male_employee_sql = "SELECT COUNT(*) AS male_count FROM employees WHERE gender = 'male' AND is_active = 1";
$male_employee_result = $conn->query($male_employee_sql);

// Query to get number of female employees
$female_employee_sql = "SELECT COUNT(*) AS female_count FROM employees WHERE gender = 'female' AND is_active = 1";
$female_employee_result = $conn->query($female_employee_sql);


// Initialize variables to gender counts
$female_count = 0;
$male_count = 0;

// Fetch number of female employees
if ($female_employee_result) {
    $row = $female_employee_result->fetch_assoc();
    $female_count = $row["female_count"];
}

// Fetch number of male employees
if ($male_employee_result) {
    $row = $male_employee_result->fetch_assoc();
    $male_count = $row["male_count"];
}

// Calculate percentages for each gender
$female_percentages = ($female_count / $total_employees_count) * 100;
$male_percentages = ($male_count / $total_employees_count) * 100;

// Create genderData array with percentages for each gender
$genderData = array(
    array("label" => "Female", "symbol" => "Female", "y" => $female_percentages, "color" => "#5bc0de"),
    array("label" => "Male", "symbol" => "Male", "y" => $male_percentages, "color" => "#3498db"),
);

// =============================== A C C O U N T S  ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of accounts employees by employement type
$total_accounts_employee_sql = "
    SELECT
        COUNT(*) AS total_accounts_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_accounts_full_time_employees_count, 
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_accounts_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_accounts_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Accounts' AND is_active = 1;
";

$total_accounts_employee_result = $conn->query($total_accounts_employee_sql);

// Initialise variables to accounts by employment type
$total_accounts_employees_count = 0;
$total_accounts_full_time_employees_count = 0;
$total_accounts_part_time_employees_count = 0;
$total_accounts_casual_employees_count = 0;

// Fetch the result
if ($total_accounts_employee_result) {
    $row = $total_accounts_employee_result->fetch_assoc();
    $total_accounts_employees_count = $row['total_accounts_employees_count'];
    $total_accounts_full_time_employees_count = $row['total_accounts_full_time_employees_count'];
    $total_accounts_part_time_employees_count = $row['total_accounts_part_time_employees_count'];
    $total_accounts_casual_employees_count = $row['total_accounts_casual_employees_count'];
}

// =============================== E N G I N E E R I N G  ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of Engineering employees by employment type
$total_engineering_employee_sql = "
    SELECT
        COUNT(*) AS total_engineering_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_engineering_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_engineering_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_engineering_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Engineering' AND is_active = 1;
";

$total_engineering_employee_result = $conn->query($total_engineering_employee_sql);

// Initialise variables to Engineering
$total_engineering_employees_count = 0;
$total_engineering_full_time_employees_count = 0;
$total_engineering_part_time_employees_count = 0;
$total_engineering_casual_employees_count = 0;

// Fetch the result
if ($total_engineering_employee_result) {
    $row = $total_engineering_employee_result->fetch_assoc();
    $total_engineering_employees_count = $row['total_engineering_employees_count'];
    $total_engineering_full_time_employees_count = $row['total_engineering_full_time_employees_count'];
    $total_engineering_part_time_employees_count = $row['total_engineering_part_time_employees_count'];
    $total_engineering_casual_employees_count = $row['total_engineering_casual_employees_count'];
}

// =============================== E S T I M A T I N G  ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of Estimating employees by employment type
$total_estimating_employee_sql = "
    SELECT
        COUNT(*) AS total_estimating_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_estimating_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_estimating_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_estimating_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Estimating' AND is_active = 1;
";

$total_estimating_employee_result = $conn->query($total_estimating_employee_sql);

// Initialise variables to Estimating
$total_estimating_employees_count = 0;
$total_estimating_full_time_employees_count = 0;
$total_estimating_part_time_employees_count = 0;
$total_estimating_casual_employees_count = 0;

// Fetch the result
if ($total_estimating_employee_result) {
    $row = $total_estimating_employee_result->fetch_assoc();
    $total_estimating_employees_count = $row['total_estimating_employees_count'];
    $total_estimating_full_time_employees_count = $row['total_estimating_full_time_employees_count'];
    $total_estimating_part_time_employees_count = $row['total_estimating_part_time_employees_count'];
    $total_estimating_casual_employees_count = $row['total_estimating_casual_employees_count'];
}

// =============================== E L E C T R I C A L  ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of electrical employees by employement type
$total_electrical_employee_sql = "
    SELECT
        COUNT(*) AS total_electrical_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_electrical_full_time_employees_count, 
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_electrical_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_electrical_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Electrical' AND is_active = 1;
";

$total_electrical_employee_result = $conn->query($total_electrical_employee_sql);

// Initialise variables to electrical by employment type
$total_electrical_employees_count = 0;
$total_electrical_full_time_employees_count = 0;
$total_electrical_part_time_employees_count = 0;
$total_electrical_casual_employees_count = 0;

// Fetch the result
if ($total_electrical_employee_result) {
    $row = $total_electrical_employee_result->fetch_assoc();
    $total_electrical_employees_count = $row['total_electrical_employees_count'];
    $total_electrical_full_time_employees_count = $row['total_electrical_full_time_employees_count'];
    $total_electrical_part_time_employees_count = $row['total_electrical_part_time_employees_count'];
    $total_electrical_casual_employees_count = $row['total_electrical_casual_employees_count'];
}

// =============================== S H E E T  M E T A L  ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of sheet metal employees by employment type
$total_sheet_metal_employee_sql = "
    SELECT
        COUNT(*) AS total_sheet_metal_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_sheet_metal_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-TIme' THEN 1 END) AS total_sheet_metal_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_sheet_metal_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Sheet Metal' AND is_active = 1;
";

$total_sheet_metal_employee_result = $conn->query($total_sheet_metal_employee_sql);

// Initialise variables to sheet metal 
$total_sheet_metal_employees_count = 0;
$total_sheet_metal_full_time_employees_count = 0;
$total_sheet_metal_part_time_employees_count = 0;
$total_sheet_metal_casual_employees_count = 0;

// Fetch the result
if ($total_sheet_metal_employee_result) {
    $row = $total_sheet_metal_employee_result->fetch_assoc();
    $total_sheet_metal_employees_count = $row['total_sheet_metal_employees_count'];
    $total_sheet_metal_full_time_employees_count = $row['total_sheet_metal_full_time_employees_count'];
    $total_sheet_metal_part_time_employees_count = $row['total_sheet_metal_part_time_employees_count'];
    $total_sheet_metal_casual_employees_count = $row['total_sheet_metal_casual_employees_count'];
}

// =============================== S I T E  ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of Site employees by employment type
$total_site_employee_sql = "
    SELECT
        COUNT(*) AS total_site_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_site_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_site_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_site_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Site' AND is_active = 1;
";

$total_site_employee_result = $conn->query($total_site_employee_sql);

// Initialise variables to Site
$total_site_employees_count = 0;
$total_site_full_time_employees_count = 0;
$total_site_part_time_employees_count = 0;
$total_site_casual_employees_count = 0;

// Fetch the result
if ($total_site_employee_result) {
    $row = $total_site_employee_result->fetch_assoc();
    $total_site_employees_count = $row['total_site_employees_count'];
    $total_site_full_time_employees_count = $row['total_site_full_time_employees_count'];
    $total_site_part_time_employees_count = $row['total_site_part_time_employees_count'];
    $total_site_casual_employees_count = $row['total_site_casual_employees_count'];
}

// =============================== R & D ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of Research & Development employees by employment type
$total_rnd_employee_sql = "
    SELECT
        COUNT(*) AS total_rnd_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_rnd_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_rnd_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_rnd_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Research & Development' AND is_active = 1;
";

$total_rnd_employee_result = $conn->query($total_rnd_employee_sql);

// Initialise variables to Research & Development
$total_rnd_employees_count = 0;
$total_rnd_full_time_employees_count = 0;
$total_rnd_part_time_employees_count = 0;
$total_rnd_casual_employees_count = 0;

// Fetch the result
if ($total_rnd_employee_result) {
    $row = $total_rnd_employee_result->fetch_assoc();
    $total_rnd_employees_count = $row['total_rnd_employees_count'];
    $total_rnd_full_time_employees_count = $row['total_rnd_full_time_employees_count'];
    $total_rnd_part_time_employees_count = $row['total_rnd_part_time_employees_count'];
    $total_rnd_casual_employees_count = $row['total_rnd_casual_employees_count'];
}


// =============================== O P E R A T I O N S  S U P P O R T ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of operations support employees by employment type
$total_operations_support_employee_sql = "
    SELECT
        COUNT(*) AS total_operations_support_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_operations_support_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_operations_support_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_operations_support_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Operations Support' AND is_active = 1;
";

$total_operations_support_employee_result = $conn->query($total_operations_support_employee_sql);

// Initialise variables to operations support
$total_operations_support_employees_count = 0;
$total_operations_support_full_time_employees_count = 0;
$total_operations_support_part_time_employees_count = 0;
$total_operations_support_casual_employees_count = 0;

// Fetch the result
if ($total_sheet_metal_employee_result) {
    $row = $total_operations_support_employee_result->fetch_assoc();
    $total_operations_support_employees_count = $row['total_operations_support_employees_count'];
    $total_operations_support_full_time_employees_count = $row['total_operations_support_full_time_employees_count'];
    $total_operations_support_part_time_employees_count = $row['total_operations_support_part_time_employees_count'];
    $total_operations_support_casual_employees_count = $row['total_operations_support_casual_employees_count'];
}

// =============================== M A N A G E M E N T ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of Management employees by employment type
$total_management_employee_sql = "
    SELECT
        COUNT(*) AS total_management_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_management_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_management_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_management_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Management' AND is_active = 1;
";

$total_management_employee_result = $conn->query($total_management_employee_sql);

// Initialise variables to Management
$total_management_employees_count = 0;
$total_management_full_time_employees_count = 0;
$total_management_part_time_employees_count = 0;
$total_management_casual_employees_count = 0;

// Fetch the result
if ($total_sheet_metal_employee_result) {
    $row = $total_management_employee_result->fetch_assoc();
    $total_management_employees_count = $row['total_management_employees_count'];
    $total_management_full_time_employees_count = $row['total_management_full_time_employees_count'];
    $total_management_part_time_employees_count = $row['total_management_part_time_employees_count'];
    $total_management_casual_employees_count = $row['total_management_casual_employees_count'];
}

// =============================== C O M M I S S I O N I N G  ( E M P L O Y M E N T  T Y P E ) ===============================

// Query to get the total number of Commissioning employees by employment type
$total_commissioning_employee_sql = "
    SELECT
        COUNT(*) AS total_commissioning_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Full-Time' THEN 1 END) AS total_commissioning_full_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Part-Time' THEN 1 END) AS total_commissioning_part_time_employees_count,
        COUNT(CASE WHEN employees.employment_type = 'Casual' THEN 1 END) AS total_commissioning_casual_employees_count
    FROM employees
    JOIN department ON department.department_id = employees.department
    WHERE department.department_name = 'Commissioning' AND is_active = 1;
";

$total_commissioning_employee_result = $conn->query($total_commissioning_employee_sql);

// Initialise variables to Commissioning
$total_commissioning_employees_count = 0;
$total_commissioning_full_time_employees_count = 0;
$total_commissioning_part_time_employees_count = 0;
$total_commissioning_casual_employees_count = 0;

// Fetch the result
if ($total_commissioning_employee_result) {
    $row = $total_commissioning_employee_result->fetch_assoc();
    $total_commissioning_employees_count = $row['total_commissioning_employees_count'];
    $total_commissioning_full_time_employees_count = $row['total_commissioning_full_time_employees_count'];
    $total_commissioning_part_time_employees_count = $row['total_commissioning_part_time_employees_count'];
    $total_commissioning_casual_employees_count = $row['total_commissioning_casual_employees_count'];
}

// =============================== T O T A L  E M P L O Y E E  ( E A C H  M O N T H ) ===============================
// Query to count total employees for each month
$employee_each_month_sql = "
SELECT
  DATE_FORMAT(months.month, '%Y-%m') AS month,
  COUNT(e.employee_id) AS total_employees_each_month
FROM
  (
    SELECT
        DATE_FORMAT(DATE_SUB(LAST_DAY(CURDATE()), INTERVAL seq MONTH), '%Y-%m-%d') AS month
    FROM
      (
        SELECT 0 AS seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
        UNION SELECT 10 UNION SELECT 11
      ) AS seqs
  ) AS months
LEFT JOIN employees e
  ON e.start_date <= LAST_DAY(months.month)
     AND (e.last_date > LAST_DAY(months.month) OR e.last_date IS NULL)
GROUP BY months.month
ORDER BY months.month;
";


// Query to count new employees (joined) each month
$new_employees_sql = "
SELECT
  DATE_FORMAT(start_date, '%Y-%m') AS month,
  GROUP_CONCAT(first_name ORDER BY start_date) AS employee_names,
  COUNT(employee_id) AS new_employees
FROM employees
WHERE start_date IS NOT NULL
  AND start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
GROUP BY month
ORDER BY month;
";


// Query to count employees who left (left) each month
$left_employees_sql = "
SELECT
  DATE_FORMAT(last_date, '%Y-%m') AS month,
  GROUP_CONCAT(first_name ORDER BY last_date) AS employee_names,
  COUNT(employee_id) AS left_employees
FROM employees
WHERE last_date IS NOT NULL
  AND last_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
  AND is_active = 0
GROUP BY month
ORDER BY month;
";


// Run the queries
$employee_each_month_result = $conn->query($employee_each_month_sql);
$new_employees_result = $conn->query($new_employees_sql);
$left_employees_result = $conn->query($left_employees_sql);

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

for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = $month;
    $totalEmployees[] = isset($total_employees_data[$month]) ? (int) $total_employees_data[$month] : 0;
    $newEmployees[] = isset($new_employees_data[$month]) ? (int) $new_employees_data[$month]['new_employees'] : 0;
    $leftEmployees[] = isset($left_employees_data[$month]) ? (int) $left_employees_data[$month]['left_employees'] : 0;
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
            <!-- <div class="d-flex justify-content-between align-items-center mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item active" style="color:#043f9d" aria-current="page"><a
                                href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/employee-list-index.php">All
                                Employees</a>
                        </li>
                        <li class="breadcrumb-item active fw-bold" style="color:#043f9d">HR Dashboard</li>

                    </ol>
                </nav>
                <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/employee-list-index.php"
                    class="btn btn-success"> <i class="fa-solid fa-users"></i> All
                    Employees </a>
            </div> -->

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col" class="text-center"></th>
                        <th scope="col" class="text-center">ACC</th>
                        <th scope="col" class="text-center">EN</th>
                        <th scope="col" class="text-center">ES</th>
                        <th scope="col" class="text-center">EL</th>
                        <th scope="col" class="text-center">SM</th>
                        <th scope="col" class="text-center">Site</th>
                        <th scope="col" class="text-center">R&D</th>
                        <th scope="col" class="text-center">OS</th>
                        <th scope="col" class="text-center">MN</th>
                        <th scope="col" class="text-center">Comm</th>
                        <th scope="col" class="text-center">Sub-Total</th>
                        <th scope="col" class="text-center">Total Employees</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row" class="text-center">Full-Time</th>
                        <td class="text-center"><?php echo $total_accounts_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_engineering_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_estimating_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_electrical_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_sheet_metal_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_site_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_rnd_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_operations_support_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_management_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_commissioning_full_time_employees_count ?></td>
                        <td class="text-center"><?php echo $permanent_count ?></td>
                        <td class="text-center align-middle text-white" rowspan="4" style="background-color: #5bc1df">
                            <h1><?php echo $total_employees_count ?>
                        </td>
                        </h1>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-center">Part-Time</th>
                        <td class="text-center"><?php echo $total_accounts_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_engineering_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_estimating_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_electrical_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_sheet_metal_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_site_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_rnd_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_operations_support_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_management_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $total_commissioning_part_time_employees_count ?></td>
                        <td class="text-center"><?php echo $part_time_count ?></td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-center">Casual</th>
                        <td class="text-center"><?php echo $total_accounts_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_engineering_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_estimating_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_electrical_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_sheet_metal_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_site_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_rnd_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_operations_support_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_management_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $total_commissioning_casual_employees_count ?></td>
                        <td class="text-center"><?php echo $casual_count ?></td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-center">Full-Time (%)</th>
                        <td class="text-center">
                            <?php
                            if ($total_accounts_employees_count > 0) {
                                echo round(($total_accounts_full_time_employees_count / $total_accounts_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_engineering_employees_count > 0) {
                                echo round(($total_engineering_full_time_employees_count / $total_engineering_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_estimating_employees_count > 0) {
                                echo round(($total_estimating_full_time_employees_count / $total_estimating_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_electrical_employees_count > 0) {
                                echo round(($total_electrical_full_time_employees_count / $total_electrical_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_sheet_metal_employees_count > 0) {
                                echo round(($total_sheet_metal_full_time_employees_count / $total_sheet_metal_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_site_employees_count > 0) {
                                echo round(($total_site_full_time_employees_count / $total_site_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_rnd_employees_count > 0) {
                                echo round(($total_rnd_full_time_employees_count / $total_rnd_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_operations_support_employees_count > 0) {
                                echo round(($total_operations_support_full_time_employees_count / $total_operations_support_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_management_employees_count > 0) {
                                echo round(($total_management_full_time_employees_count / $total_management_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_commissioning_employees_count > 0) {
                                echo round(($total_commissioning_full_time_employees_count / $total_commissioning_employees_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if ($total_active_count > 0) {
                                echo round(($permanent_count / $total_active_count) * 100, 2) . "%";
                            } else {
                                echo "0%"; // Prevent division by zero
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex justify-content-center align-items-center mb-3 background-color shadow-lg py-4 rounded-3 d-none"
                id="filterEmployeeDashboard">
                <div class="row col-8">
                    <div class="col-6">
                        <label for="startMonth" class="fw-bold">Start Month: </label>
                        <input type="month" id="startMonth" name="startMonth" class="form-control">
                    </div>
                    <div class="col-6">
                        <label for="endMonth" class="fw-bold">End Month: </label>
                        <input type="month" id="endMonth" name="endMonth" class="form-control">
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-danger fw-bold me-1" id="resetFilter">Reset</button>
                        <button class="btn btn-secondary fw-bold me-1" id="hideFilterEmployeeDashboard">Cancel</button>
                        <button id="filterButton" class="btn btn-dark fw-bold">Filter</button>
                    </div>
                </div>
            </div>

            <div>
                <div
                    class="d-flex flex-column flex-md-row align-items-center justify-content-between p-3 signature-bg-color text-white rounded-top-3">
                    <div class="d-flex flex-column flex-md-row align-items-center my-2 my-md-0">
                        <span class="fw-bold">Timeframe: </span>
                        <h6 class="ms-1 fw-bold mb-0 pb-0" id="startMonthText"></h6>
                        <span class="mx-2">-</span>
                        <h6 class="ms-1 fw-bold mb-0 pb-0" id="endMonthText"></h6>
                    </div>
                    <div class="d-flex flex-column flex-md-row align-items-center my-2 my-md-0">
                        <button class="btn btn-dark btn-sm fw-bold ms-2" id="showEmployeeChangesTable">Show Table <i
                                class="fa-solid fa-table"></i></button>
                        <button class="btn btn-light btn-sm fw-bold ms-2" id="showFilterEmployeeDashboard">Edit<i
                                class="fa-regular fa-pen-to-square ms-1"></i></button>
                        <button class="btn btn-success btn-sm fw-bold ms-2" id="refreshButton">Refresh<i
                                class="fa-solid fa-arrows-rotate ms-1"></i></button>
                    </div>
                </div>

                <canvas class="p-3 shadow-lg rounded-bottom-3 mb-3" id="employeeChart" width="1000"
                    height="300"></canvas>
            </div>

            <table class="table table-bordered table-striped text-center table-hover d-none" id="employeeChangesTable">
                <thead class="table-primary">
                    <tr>
                        <th>Month</th>
                        <th>Total Employees</th>
                        <th>New Employees</th>
                        <th>Employees Left</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($i = 11; $i >= 0; $i--) {
                        $month = date('Y-m', strtotime("-$i months"));
                        $month_display = date('F Y', strtotime("-$i months"));

                        $total_employees = isset($total_employees_data[$month]) ? $total_employees_data[$month] : 0;
                        $new_employees = isset($new_employees_data[$month]) ? $new_employees_data[$month]['new_employees'] : 0;
                        $left_employees = isset($left_employees_data[$month]) ? $left_employees_data[$month]['left_employees'] : 0;

                        $changes = $new_employees - $left_employees;

                        // Font Awesome icons for changes
                        if ($changes > 0) {
                            $changes_display = "<span class='text-success'><i class='fa-solid fa-caret-up'></i> $changes</span>";
                        } elseif ($changes < 0) {
                            $changes_display = "<span class='text-danger'><i class='fa-solid fa-caret-down'></i> " . abs($changes) . "</span>";
                        } else {
                            $changes_display = "<span class='text-muted'><i class='fa-solid fa-minus'></i> 0</span>";
                        }

                        echo "<tr>";
                        echo "<td>$month_display</td>";
                        echo "<td>$total_employees</td>";
                        echo "<td>$new_employees</td>";
                        echo "<td>$left_employees</td>";
                        echo "<td>$changes_display</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

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
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 mt-lg-0 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-bs-toggle="collapse"
                            data-bs-target="#genderCollapse" aria-expanded="false" aria-controls="genderCollapse"
                            style="cursor: pointer;">
                            Gender
                        </h4>
                        <div class="collapse" id="genderCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Male</td>
                                            <td><?php echo $male_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Female</td>
                                            <td><?php echo $female_count ?></td>
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
                        <div id="chartContainer6" style="height: 370px;"></div>
                    </div>
                </div>
            </div>

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
                                                <?php echo $total_electrical_full_time_employees_count ?>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var HRDashboardModal = document.getElementById("HRDashboardModal");

            HRDashboardModal.addEventListener("shown.bs.modal", function () {
                var chart = new CanvasJS.Chart("chartContainer", {
                    theme: "light2",
                    animationEnabled: true,
                    title: {
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

                var chart6 = new CanvasJS.Chart("chartContainer6", {
                    theme: "light2",
                    animationEnabled: true,
                    data: [{
                        type: "pie",
                        indexLabel: "{symbol} - {y}",
                        yValueFormatString: "#,##0.0\"%\"",
                        showInLegend: true,
                        legendText: "{label} : {y}",
                        dataPoints: <?php echo json_encode($genderData, JSON_NUMERIC_CHECK); ?>
                    }]
                });

                chart.render();
                chart2.render();
                chart6.render();
            });
        });
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
    <script>
        const ctx = document.getElementById('employeeChart').getContext('2d');
        const employeeChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [
                    {
                        label: 'Total Employees',
                        data: <?= json_encode($totalEmployees) ?>,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'New Employees',
                        data: <?= json_encode($newEmployees) ?>,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Employees Left',
                        data: <?= json_encode($leftEmployees) ?>,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Employees'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterEmployeeDashboard = document.getElementById("filterEmployeeDashboard");
            const filterButton = document.getElementById("filterButton");
            const showFilterEmployeeDashboard = document.getElementById("showFilterEmployeeDashboard");
            const hideFilterEmployeeDashboard = document.getElementById("hideFilterEmployeeDashboard");
            const refreshButton = document.getElementById("refreshButton");
            const resetFilter = document.getElementById("resetFilter");
            const showEmployeeChangesTable = document.getElementById("showEmployeeChangesTable");
            const employeeChangesTable = document.getElementById("employeeChangesTable");

            // Default timeframe on page load
            let { startMonth, endMonth } = getDefaultTimeframe();
            updateHeader(startMonth, endMonth); // Update header right after the page loads

            // Store the last entered values for start and end month
            let lastStartMonth = startMonth;
            let lastEndMonth = endMonth;

            function getDefaultTimeframe() {
                const now = new Date();
                const endMonth = now.toISOString().slice(0, 7);
                const startDate = new Date(now.getFullYear(), now.getMonth() - 10, 1);
                const startMonth = startDate.toISOString().slice(0, 7);
                return { startMonth, endMonth };
            }

            function updateHeader(startMonth, endMonth) {
                const formatMonth = (month) => {
                    const date = new Date(month + '-01');
                    return date.toLocaleString('default', { month: 'long', year: 'numeric' });
                };

                const startFormatted = formatMonth(startMonth);
                const endFormatted = formatMonth(endMonth);

                document.getElementById('startMonthText').innerText = startFormatted;
                document.getElementById('endMonthText').innerText = endFormatted;
            }

            // Show filter and set values based on the most recent inputs
            showFilterEmployeeDashboard.addEventListener("click", function () {
                document.getElementById('startMonth').value = lastStartMonth; // Use the most recent inputted value
                document.getElementById('endMonth').value = lastEndMonth; // Use the most recent inputted value
                filterEmployeeDashboard.classList.remove("d-none");
                showFilterEmployeeDashboard.classList.add("d-none");
            });

            // Reset to default timeframe (TTM) when reset filter is clicked
            resetFilter.addEventListener("click", function () {
                const { startMonth, endMonth } = getDefaultTimeframe();
                document.getElementById('startMonth').value = startMonth;
                document.getElementById('endMonth').value = endMonth;
                updateHeader(startMonth, endMonth);

                // Also update the stored values to reflect the reset
                lastStartMonth = startMonth;
                lastEndMonth = endMonth;
            });

            // Hide the filter section
            hideFilterEmployeeDashboard.addEventListener("click", function () {
                filterEmployeeDashboard.classList.add("d-none");
                showFilterEmployeeDashboard.classList.remove("d-none");
            });

            // Refresh button: use the current values in the input fields
            refreshButton.addEventListener("click", function () {
                const startMonth = document.getElementById('startMonth').value;
                const endMonth = document.getElementById('endMonth').value;

                if (startMonth && endMonth) {
                    updateHeader(startMonth, endMonth);
                    filterData(startMonth, endMonth);

                    // Update the stored values to reflect the refresh action
                    lastStartMonth = startMonth;
                    lastEndMonth = endMonth;
                } else {
                    console.warn("Start or end month is empty. Skipping update.");
                }

                filterEmployeeDashboard.classList.add("d-none");
                showFilterEmployeeDashboard.classList.remove("d-none");
            });

            // Filter button: apply the filter with the current values
            filterButton.addEventListener("click", function () {
                const startMonth = document.getElementById('startMonth').value;
                const endMonth = document.getElementById('endMonth').value;
                updateHeader(startMonth, endMonth);
                filterData(startMonth, endMonth);

                // Update the stored values after applying the filter
                lastStartMonth = startMonth;
                lastEndMonth = endMonth;

                filterEmployeeDashboard.classList.add("d-none");
                showFilterEmployeeDashboard.classList.remove("d-none");
            });

            showEmployeeChangesTable.addEventListener("click", function () {
                employeeChangesTable.classList.toggle("d-none");
                if (employeeChangesTable.classList.contains("d-none")) {
                    showEmployeeChangesTable.innerHTML = 'Show Table <i class="fa-solid fa-table"></i>';
                } else {
                    showEmployeeChangesTable.innerHTML = 'Hide Table <i class="fa-solid fa-table"></i>';
                }
            });
        });



        function filterData(startMonth, endMonth) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "../AJAXphp/get_employee_dashboard_data.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('AJAX Response:', response);  // Log parsed response
                        updateChart(response);
                        updateTable(response);
                    } catch (e) {
                        console.error('JSON parsing error:', e);
                        console.error('Raw response text:', xhr.responseText); // This is what you want to check!
                    }
                } else {
                    console.error('AJAX failed with status:', xhr.status);
                }
            };

            xhr.onerror = function () {
                console.error("AJAX error occurred.");
            };
            xhr.send("startMonth=" + encodeURIComponent(startMonth) + "&endMonth=" + encodeURIComponent(endMonth));
        }

        function updateChart(data) {
            // Log the data structure to verify
            console.log('Updating chart with data:', data);

            // Make sure the data contains these properties and they are arrays
            if (data && Array.isArray(data.months) && Array.isArray(data.totalEmployees) &&
                Array.isArray(data.newEmployees) && Array.isArray(data.leftEmployees)) {

                // Update the chart with new data
                employeeChart.data.labels = data.months;
                employeeChart.data.datasets[0].data = data.totalEmployees;
                employeeChart.data.datasets[1].data = data.newEmployees;
                employeeChart.data.datasets[2].data = data.leftEmployees;

                // Update the chart
                employeeChart.update();

                // Update the table content
                updateTable(data);
            } else {
                console.error('Invalid data structure:', data);
            }
        }

        function updateTable(data) {
            // Select the specific table body using its ID
            const tbody = document.querySelector("#employeeChangesTable tbody");

            // Clear the current table content
            tbody.innerHTML = '';

            // Loop through the data and build the table rows
            data.months.forEach((month, index) => {
                // Extract data for each row
                const totalEmployees = data.totalEmployees[index] || 0;
                const newEmployees = data.newEmployees[index] || 0;
                const leftEmployees = data.leftEmployees[index] || 0;
                const changes = newEmployees - leftEmployees;

                // Determine the change icon
                let changesDisplay = '';
                if (changes > 0) {
                    changesDisplay = `<span class='text-success'><i class='fa-solid fa-caret-up'></i> ${changes}</span>`;
                } else if (changes < 0) {
                    changesDisplay = `<span class='text-danger'><i class='fa-solid fa-caret-down'></i> ${Math.abs(changes)}</span>`;
                } else {
                    changesDisplay = `<span class='text-muted'><i class='fa-solid fa-minus'></i> 0</span>`;
                }

                // Create a table row for each month
                const monthDisplay = new Date(month + '-01').toLocaleString('default', { month: 'long', year: 'numeric' });

                const row = document.createElement('tr');
                row.innerHTML = `
            <td>${monthDisplay}</td>
            <td>${totalEmployees}</td>
            <td>${newEmployees}</td>
            <td>${leftEmployees}</td>
            <td>${changesDisplay}</td>
        `;

                // Append the row to the specific table body
                tbody.appendChild(row);
            });
        }

    </script>


</body>

</html>