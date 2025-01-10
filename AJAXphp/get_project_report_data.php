<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require("./../db_connect.php");
require_once("../status_check.php");

// =============================== Project Chart ===============================

$total_pj_total_count = 0;

// Get the start and end date from the POST request
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : null;
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : null;
$projectType = isset($_POST['projectType']) && $_POST['projectType'] !== "" ? $_POST['projectType'] : null;
$paymentTerms = isset($_POST['paymentTerms']) && $_POST['paymentTerms'] !== "" ? $_POST['paymentTerms'] : null;


// Initialize project type condition
$projectTypeCondition = "";
if ($projectType) {
    $projectTypeCondition = " AND p.project_type = '$projectType'"; // Add project type filter
}

$paymentTermsCondition = "";
if ($paymentTerms) {
    $paymentTermsCondition = " AND p.payment_terms = '$paymentTerms'"; // Add project type filter
}

$startDateCondition = "";
$endDateCondition = "";

if ($startDate) {
    $startDateCondition = " AND date >= '$startDate'"; // Add start date filter
}

if ($endDate) {
    $endDateCondition = " AND date <= '$endDate'"; // Add end date filter
}


// Query to get project details, including invoiced and non-invoiced projects, within the date range
$pj_details_sql = "SELECT pd.*, p.* 
                   FROM project_details pd
                   JOIN projects p ON pd.project_id = p.project_id
                   WHERE 1=1 
                   $startDateCondition
                   $endDateCondition
                   $projectTypeCondition
                   $paymentTermsCondition
                   ORDER BY pd.date";


// Execute the query to fetch project details
$pj_details_result = $conn->query($pj_details_sql);

$total_pj_total_count = 0;

// Initialize an array to store project details for table rows
$projectDetails = array();

// Check if there are any results
if ($pj_details_result->num_rows > 0) {
    while ($row = $pj_details_result->fetch_assoc()) {
        $projectDetails[] = $row;  // Add project details to the array
        $total_pj_total_count += $row['sub_total'];  // Accumulate the sub_total
    }
}

// Query to sum sub_total for invoiced projects, grouped by month and year
$pj_invoiced_sql = "SELECT 
                        MONTH(pd.date) AS month, 
                        YEAR(pd.date) AS year, 
                        SUM(pd.sub_total) AS total_invoiced_sub_total
                    FROM project_details pd
                    JOIN projects p ON pd.project_id = p.project_id
                    WHERE pd.invoiced = '1' 
                    $startDateCondition
                    $endDateCondition
                    $projectTypeCondition
                    $paymentTermsCondition
                    GROUP BY YEAR(pd.date), MONTH(pd.date)
                    ORDER BY YEAR(pd.date), MONTH(pd.date)";

// Query to sum sub_total for non-invoiced projects, grouped by month and year
$pj_non_invoiced_sql = "SELECT 
                            MONTH(pd.date) AS month, 
                            YEAR(pd.date) AS year, 
                            SUM(pd.sub_total) AS total_non_invoiced_sub_total
                        FROM project_details pd
                        JOIN projects p ON pd.project_id = p.project_id
                        WHERE pd.invoiced = '0' 
                        $startDateCondition
                        $endDateCondition
                        $projectTypeCondition
                        $paymentTermsCondition
                        GROUP BY YEAR(pd.date), MONTH(pd.date)
                        ORDER BY YEAR(pd.date), MONTH(pd.date)";

$total_pj_total_count = 0;

// Initialize empty arrays for data points
$dataPoints8 = array();  // Invoiced projects data points
$dataPoints9 = array();  // Non-invoiced projects data points

// Arrays to store the months and years for invoiced and non-invoiced data
$invoiced_months_years = array();
$non_invoiced_months_years = array();

// Execute the query for invoiced projects
$pj_invoiced_result = $conn->query($pj_invoiced_sql);
if ($pj_invoiced_result->num_rows > 0) {
    while ($row = $pj_invoiced_result->fetch_assoc()) {
        $month = $row['month'];
        $year = $row['year'];
        $total_invoiced_sub_total = $row['total_invoiced_sub_total'];

        // Combine the month and year for the label (e.g., Dec 2024)
        $monthName = date("M", mktime(0, 0, 0, $month, 10));  // Convert month number to month name
        $label = $monthName . ' ' . $year;  // Concatenate month and year

        // Add the data point for the current month and year
        $dataPoints8[] = array("label" => $label, "y" => $total_invoiced_sub_total);

        // Keep track of the month-year combination for invoiced projects
        $invoiced_months_years[] = $label;

        // Update the total for invoiced projects
        $total_pj_total_count += $total_invoiced_sub_total;
    }
}

// Execute the query for non-invoiced projects
$pj_non_invoiced_result = $conn->query($pj_non_invoiced_sql);
if ($pj_non_invoiced_result->num_rows > 0) {
    while ($row = $pj_non_invoiced_result->fetch_assoc()) {
        $month = $row['month'];
        $year = $row['year'];
        $total_non_invoiced_sub_total = $row['total_non_invoiced_sub_total'];

        // Combine the month and year for the label (e.g., Dec 2024)
        $monthName = date("M", mktime(0, 0, 0, $month, 10));  // Convert month number to month name
        $label = $monthName . ' ' . $year;  // Concatenate month and year

        // Add the data point for the current month and year
        $dataPoints9[] = array("label" => $label, "y" => $total_non_invoiced_sub_total);

        // Keep track of the month-year combination for non-invoiced projects
        $non_invoiced_months_years[] = $label;
    }
}

// Combine all months and years from both invoiced and non-invoiced data
$all_months_years = array_merge($invoiced_months_years, $non_invoiced_months_years);
$all_months_years = array_unique($all_months_years);  // Remove duplicates

// Add missing months/years with 0 value
foreach ($all_months_years as $month_year) {
    // Check if this month-year combination exists in invoiced data
    if (!in_array($month_year, $invoiced_months_years)) {
        $dataPoints8[] = array("label" => $month_year, "y" => 0);
    }
    // Check if this month-year combination exists in non-invoiced data
    if (!in_array($month_year, $non_invoiced_months_years)) {
        $dataPoints9[] = array("label" => $month_year, "y" => 0);
    }
}

// Sort the data points by month-year to ensure they appear in the correct order
usort($dataPoints8, function ($a, $b) {
    return strcmp($a['label'], $b['label']);
});
usort($dataPoints9, function ($a, $b) {
    return strcmp($a['label'], $b['label']);
});

// Calculate total values
$totalInvoiced = array_sum(array_column($dataPoints8, 'y'));
$totalNonInvoiced = array_sum(array_column($dataPoints9, 'y'));

// Prepare data points for Chart 6
$pjTotalDataPoints = [
    ["label" => "Invoiced Projects", "y" => $totalInvoiced],
    ["label" => "Non-Invoiced Projects", "y" => $totalNonInvoiced]
];

// Return data in JSON format for AJAX response
echo json_encode([
    'dataPoints8' => $dataPoints8,
    'dataPoints9' => $dataPoints9,
    'totalInvoiced' => $totalInvoiced,
    'totalNonInvoiced' => $totalNonInvoiced,
    'projectDetails' => $projectDetails,
    'totalCount' => $total_pj_total_count
]);

?>