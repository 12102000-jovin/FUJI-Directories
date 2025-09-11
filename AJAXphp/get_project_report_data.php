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
    $startDateCondition = " AND COALESCE(pd.revised_delivery_date, pd.date) >= '$startDate'";
}
if ($endDate) {
    $endDateCondition = " AND COALESCE(pd.revised_delivery_date, pd.date) <= '$endDate'";
}

// Query to get project details, including invoiced and non-invoiced projects, within the date range
$pj_details_sql = "SELECT pd.*, 
                          p.*, 
                          e.first_name AS approved_first_name, 
                          e.last_name  AS approved_last_name,
                          pe_list.engineers
                   FROM project_details pd
                   JOIN projects p ON pd.project_id = p.project_id
                   LEFT JOIN employees e ON pd.approved_by = e.employee_id
                   LEFT JOIN (
                       SELECT pe_map.project_id, GROUP_CONCAT(pe.first_name, ' ', pe.last_name SEPARATOR ', ') AS engineers
                       FROM employees pe
                       JOIN projects pe_map ON FIND_IN_SET(pe.employee_id, pe_map.project_engineer)
                       GROUP BY pe_map.project_id
                   ) AS pe_list ON pe_list.project_id = pd.project_id
                   WHERE 1=1 
                   $startDateCondition
                   $endDateCondition
                   $projectTypeCondition
                   $paymentTermsCondition
                   ORDER BY COALESCE(pd.revised_delivery_date, pd.date)";

// Execute the query to fetch project details
$pj_details_result = $conn->query($pj_details_sql);

// Initialize an array to store project details for table rows
$projectDetails = array();
$invoicedTotal = 0;
$nonInvoicedTotal = 0;

// Check if there are any results
if ($pj_details_result->num_rows > 0) {
    while ($row = $pj_details_result->fetch_assoc()) {
        $projectDetails[] = $row;  // Add project details to the array
        $total_pj_total_count += $row['sub_total'];  // Accumulate the sub_total

        // Accumulate total for invoiced and non-invoiced projects
        if ($row['invoiced'] == 1) {
            $invoicedTotal += $row['sub_total'];
        } else {
            $nonInvoicedTotal += $row['sub_total'];
        }
    }
}

// Query to sum sub_total for invoiced projects, grouped by month and year
$pj_invoiced_sql = "SELECT 
    MONTH(COALESCE(pd.revised_delivery_date, pd.date)) AS month, 
    YEAR(COALESCE(pd.revised_delivery_date, pd.date)) AS year, 
    SUM(pd.sub_total) AS total_invoiced_sub_total
FROM project_details pd
JOIN projects p ON pd.project_id = p.project_id
WHERE pd.invoiced = '1' 
$startDateCondition
$endDateCondition
$projectTypeCondition
$paymentTermsCondition
GROUP BY YEAR(COALESCE(pd.revised_delivery_date, pd.date)), MONTH(COALESCE(pd.revised_delivery_date, pd.date))
ORDER BY YEAR(COALESCE(pd.revised_delivery_date, pd.date)), MONTH(COALESCE(pd.revised_delivery_date, pd.date))";


// Query to sum sub_total for non-invoiced projects, grouped by month and year
// Query to sum sub_total for non-invoiced projects (invoiced = 0 or NULL), grouped by month and year
$pj_non_invoiced_sql = "SELECT 
    MONTH(COALESCE(pd.revised_delivery_date, pd.date)) AS month, 
    YEAR(COALESCE(pd.revised_delivery_date, pd.date)) AS year, 
    SUM(pd.sub_total) AS total_non_invoiced_sub_total
FROM project_details pd
JOIN projects p ON pd.project_id = p.project_id
WHERE (pd.invoiced = 0 OR pd.invoiced IS NULL)
$startDateCondition
$endDateCondition
$projectTypeCondition
$paymentTermsCondition
GROUP BY YEAR(COALESCE(pd.revised_delivery_date, pd.date)), MONTH(COALESCE(pd.revised_delivery_date, pd.date))
ORDER BY YEAR(COALESCE(pd.revised_delivery_date, pd.date)), MONTH(COALESCE(pd.revised_delivery_date, pd.date))";


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

// Merge and ensure no duplicate months
$mergedData = [];
foreach ($dataPoints8 as $dp8) {
    $mergedData[$dp8['label']]['invoiced'] = $dp8['y'];
}
foreach ($dataPoints9 as $dp9) {
    $mergedData[$dp9['label']]['non_invoiced'] = $dp9['y'];
}

// Sort merged data by month-year (chronologically)
ksort($mergedData);

// Prepare the final sorted data points for the chart
$finalDataPoints8 = [];
$finalDataPoints9 = [];

foreach ($mergedData as $label => $data) {
    $finalDataPoints8[] = ["label" => $label, "y" => $data['invoiced'] ?? 0];
    $finalDataPoints9[] = ["label" => $label, "y" => $data['non_invoiced'] ?? 0];
}

// Calculate total values
$totalInvoiced = $invoicedTotal;
$totalNonInvoiced = $nonInvoicedTotal;

// Prepare data points for Chart 6
$pjTotalDataPoints = [
    ["label" => "Invoiced Projects", "y" => $totalInvoiced],
    ["label" => "Non-Invoiced Projects", "y" => $totalNonInvoiced]
];

// Return data in JSON format for AJAX response
echo json_encode([
    'dataPoints8' => $finalDataPoints8,
    'dataPoints9' => $finalDataPoints9,
    'totalInvoiced' => $totalInvoiced,
    'totalNonInvoiced' => $totalNonInvoiced,
    'projectDetails' => $projectDetails,
    'totalCount' => $total_pj_total_count
]);
?>