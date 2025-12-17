<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

date_default_timezone_set('Australia/Sydney');

$folder_name = "Project";
require_once("../group_role_check.php");

if ($role !== "full control" && $role !== "modify 2") {
    header("Location: http://$serverAddress/$projectName/access_restricted.php");
    exit();
}

// Get search term and filters from GET parameters
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Base WHERE condition
$whereClause = "(project_no LIKE '%$searchTerm%' OR quote_no LIKE '%$searchTerm%' OR project_name LIKE '%$searchTerm%' OR customer LIKE '%$searchTerm%')";

// Apply the same filters as in the main page
// Status Filter
if (isset($_GET['status']) && is_array($_GET['status'])) {
    $selected_status = $_GET['status'];
    $status_placeholders = "'" . implode("','", $selected_status) . "'";
    $whereClause .= " AND `current` IN ($status_placeholders)";
}

// Project Type Filter
if (isset($_GET['projectType']) && is_array($_GET['projectType'])) {
    $selected_project_type = $_GET['projectType'];
    $project_type_placeholders = "'" . implode("','", $selected_project_type) . "'";
    $whereClause .= " AND project_type IN ($project_type_placeholders)";
}

// Payment Terms Filter
if (isset($_GET['paymentTerms']) && is_array($_GET['paymentTerms'])) {
    $selected_payment_terms = $_GET['paymentTerms'];
    $payment_terms_placeholders = "'" . implode("','", $selected_payment_terms) . "'";
    $whereClause .= " AND payment_terms IN ($payment_terms_placeholders)";
}

// Invoice Filter
if (isset($_GET['invoiceFilter']) && is_array($_GET['invoiceFilter'])) {
    $invoiceFilter = $_GET['invoiceFilter'];

    if (in_array('invoiced', $invoiceFilter) && !in_array('not_invoiced', $invoiceFilter)) {
        $whereClause .= " AND projects.project_id IN (
            SELECT project_id FROM project_details 
            GROUP BY project_id 
            HAVING MIN(COALESCE(invoiced, 0)) = 1 AND MAX(COALESCE(invoiced, 0)) = 1
        )"; 
    }

    if (in_array('not_invoiced', $invoiceFilter) && !in_array('invoiced', $invoiceFilter)) {
        $whereClause .= " AND projects.project_id IN (
            SELECT project_id FROM project_details 
            GROUP BY project_id 
            HAVING MAX(COALESCE(invoiced, 0)) = 0
        )";
    }
}

// Get all data without pagination - using the same structure as your main query
$export_sql = "
SELECT 
    projects.*, 
    MIN(CASE 
            WHEN project_details.revised_delivery_date IS NOT NULL THEN project_details.revised_delivery_date
            ELSE project_details.date
        END) AS earliest_effective_date,
    MAX(project_details.date) AS latest_estimated_date,
    MIN(project_details.revised_delivery_date) AS earliest_revised_delivery_date,
    MAX(project_details.revised_delivery_date) AS latest_revised_delivery_date,
    MIN(CASE WHEN project_details.invoiced IS NOT NULL THEN project_details.invoiced ELSE 0 END) AS min_invoiced,
    MAX(project_details.invoiced) AS max_invoiced
FROM 
    projects
LEFT JOIN 
    project_details ON projects.project_id = project_details.project_id
WHERE 
    $whereClause
GROUP BY 
    projects.project_id
ORDER BY 
    projects.project_no ASC
";

$export_result = $conn->query($export_sql);

// Set headers for CSV file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Projects_' . date('Y-m-d') . '.csv"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Headers - match your table columns
$headers = [
    'Project No',
    'Quote No',
    'Status',
    'Project Name',
    'Project Type',
    'Earliest Estimated Time',
    'Customer',
    'Value (Ex. GST)',
    'Payment Terms',
    'Project Engineer',
    'Site Address'
];

fputcsv($output, $headers);

if ($export_result && $export_result->num_rows > 0) {
    while ($row = $export_result->fetch_assoc()) {
        
        // Format the data to match your table display
        $earliestDate = isset($row["earliest_effective_date"]) && $row["earliest_effective_date"] != 0 
            ? date("j F Y", strtotime($row["earliest_effective_date"]))
            : "N/A";
            
        $value = isset($row["value"]) && $row["value"] != 0
            ? "$" . number_format($row["value"], 2)
            : "N/A";
            
        $quoteNo = isset($row['quote_no']) ? $row['quote_no'] : "N/A";
        $customerAddress = isset($row["customer_address"]) ? $row["customer_address"] : "N/A";
        
        // Get engineer names (you may want to implement this similar to your main page)
        $engineerNames = "N/A";
        if (!empty($row["project_engineer"])) {
            $engineer_ids = explode(',', $row["project_engineer"]);
            $engineer_names = [];
            foreach ($engineer_ids as $engineer_id) {
                $engineer_id = trim($engineer_id);
                $engineer_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = '$engineer_id'";
                $engineer_result = $conn->query($engineer_sql);
                if ($engineer_result && $engineer_result->num_rows > 0) {
                    $engineer_row = $engineer_result->fetch_assoc();
                    $engineer_names[] = $engineer_row['first_name'] . " " . $engineer_row['last_name'];
                }
            }
            $engineerNames = implode(', ', $engineer_names);
        }

        $data = [
            $row['project_no'] ?? 'N/A',
            $quoteNo,
            $row['current'] ?? 'N/A',
            $row['project_name'] ?? 'N/A',
            $row['project_type'] ?? 'N/A',
            $earliestDate,
            $row['customer'] ?? 'N/A',
            $value,
            $row['payment_terms'] ?? 'N/A',
            $engineerNames,
            $customerAddress
        ];

        fputcsv($output, $data);
    }
} else {
    // No data found
    fputcsv($output, ['No projects found matching the current filters']);
}

fclose($output);
exit;
?>