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

$whereClause = "(fbn LIKE '%$searchTerm%' OR project_no LIKE '%$searchTerm%' OR serial_numbers LIKE '%$searchTerm%')";

// Apply the same filters as in the main page
if (isset($_GET['apply_filters']) || isset($_GET['export'])) {
    // Status filter
    if (isset($_GET['status']) && is_array($_GET['status'])) {
        $selected_status = $_GET['status'];
        $normal_status = array_filter($selected_status, fn($s) => $s !== 'N/A');
        $has_na = in_array('N/A', $selected_status);

        $conditions = [];
        if (!empty($normal_status)) {
            $status_placeholders = "'" . implode("','", $normal_status) . "'";
            $conditions[] = "`status` IN ($status_placeholders)";
        }
        if ($has_na) {
            $conditions[] = "`status` IS NULL";
        }

        if (!empty($conditions)) {
            $whereClause .= " AND (" . implode(" OR ", $conditions) . ")";
        }
    }

    // rOSD (Correct) Timeframe filter
    if (!empty($_GET['rosd_correct_start']) && !empty($_GET['rosd_correct_end'])) {
        $rosd_correct_start = $_GET['rosd_correct_start'];
        $rosd_correct_end = $_GET['rosd_correct_end'];

        $start_date = date('Y-m-01', strtotime($rosd_correct_start));
        $end_date = date('Y-m-t', strtotime($rosd_correct_end));

        $whereClause .= " AND (
            (approved = 1 AND rOSD_changed IS NOT NULL AND rOSD_changed BETWEEN '$start_date' AND '$end_date')
            OR ( (approved <> 1 OR rOSD_changed IS NULL) 
                AND resolved = 'PO' 
                AND rOSD_po IS NOT NULL 
                AND rOSD_po BETWEEN '$start_date' AND '$end_date')
            OR ( (approved <> 1 OR rOSD_changed IS NULL) 
                AND resolved = 'Forecast' 
                AND rOSD_forecast IS NOT NULL 
                AND rOSD_forecast BETWEEN '$start_date' AND '$end_date')
            OR ( (approved = 0 AND resolved IS NULL) 
                AND rOSD_forecast IS NOT NULL 
                AND rOSD_forecast BETWEEN '$start_date' AND '$end_date')
        )";
    }

    // Estimated Departure Date Timeframe filter
    if (!empty($_GET['estimated_departure_start']) && !empty($_GET['estimated_departure_end'])) {
        $start_date = date('Y-m-01', strtotime($_GET['estimated_departure_start']));
        $end_date = date('Y-m-t', strtotime($_GET['estimated_departure_end']));

        $whereClause .= " AND (
            CASE
                WHEN approved = 1 AND rOSD_changed IS NOT NULL THEN
                    DATE_SUB(
                        rOSD_changed,
                        INTERVAL CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                WHEN resolved = 'PO' AND rOSD_po IS NOT NULL THEN
                    DATE_SUB(
                        rOSD_po,
                        INTERVAL CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                WHEN resolved = 'Forecast' AND rOSD_forecast IS NOT NULL THEN
                    DATE_SUB(
                        rOSD_forecast,
                        INTERVAL CASE
                            WHEN LOWER(freight_type) = 'air' THEN 7
                            WHEN LEFT(fbn,3) IN ('SYD','MEL') THEN 2
                            WHEN LEFT(fbn,3) IN ('AKL') THEN 14
                            WHEN LEFT(fbn,3) IN ('CGK','BKK','SIN','KUL') THEN 28
                            WHEN LEFT(fbn,3) IN ('HYD','BOM','DUB','ICN','PUS','USN','TPE','HKG','NRT','KIX') THEN 56
                            ELSE 0
                        END DAY
                    )
                ELSE NULL
            END BETWEEN '$start_date' AND '$end_date'
        )";
    }

    // Purchase Order Filter
    if (isset($_GET['purchaseOrderFilter'])) {
        $purchaseOrderFilter = $_GET['purchaseOrderFilter'];
        if ($purchaseOrderFilter === "Yes") {
            $whereClause .= " AND aws_po_no IS NOT NULL AND aws_po_no != ''";
        } else if ($purchaseOrderFilter === "No") {
            $whereClause .= " AND (aws_po_no IS NULL OR aws_po_no = '')";
        }
    }
}

// Get all data without pagination
$export_sql = "SELECT * FROM pdc_projects WHERE $whereClause ORDER BY item_number ASC";
$export_result = $conn->query($export_sql);

// Set headers for CSV file download (better compatibility with Numbers/Excel)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="PDC_Projects_' . date('Y-m-d') . '.csv"');
header('Cache-Control: max-age=0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Headers
$headers = [
    'Item No',
    'Project No', 
    'FBN',
    'Site Type',
    'Status',
    'PO',
    'Version',
    'Qty',
    'Cost',
    'rOSD (Forecast)',
    'Red Flag',
    'rOSD (Correct)',
    'Estimated Departure Date',
    'Actual Departure Date',
    'Actual Delivered Date',
    'Customer',
    'Entity PO No',
    'AWS PO No',
    'PO Date',
    'Drawing Status',
    'Serial Numbers',
    'rOSD (PO)',
    'Resolved',
    'rOSD (Resolved)',
    'rOSD (Changed)',
    'Approved',
    'Freight Type',
    'Notes'
];

fputcsv($output, $headers);

if ($export_result->num_rows > 0) {
    while ($row = $export_result->fetch_assoc()) {
        // Calculate rOSD (Correct) - same logic as in your main page
        if ($row['approved'] == "1" && !empty($row['rOSD_changed'])) {
            $rosdCorrectDate = !empty($row['rOSD_changed']) ? date("j F Y", strtotime($row['rOSD_changed'])) : 'N/A';
        } else {
            if ($row['resolved'] === "PO" && !empty($row['rOSD_po'])) {
                $rosdCorrectDate = !empty($row['rOSD_po']) ? date("j F Y", strtotime($row['rOSD_po'])) : 'N/A';
            } elseif ($row['resolved'] === "Forecast" && !empty($row['rOSD_forecast'])) {
                $rosdCorrectDate = !empty($row['rOSD_forecast']) ? date("j F Y", strtotime($row['rOSD_forecast'])) : 'N/A';
            } else {
                $rosdCorrectDate = "N/A";
            }
        }

        // Calculate Estimated Departure Date
        $airport_code = substr(trim(explode(' - ', $row['fbn'])[0]), 0, 3);
        $country_codes = [
            'SYD' => 'AUS', 'MEL' => 'AUS', 'HYD' => 'IND', 'BOM' => 'IND',
            'NRT' => 'JPN', 'KIX' => 'JPN', 'BKK' => 'THA', 'SIN' => 'SGP',
            'CGK' => 'IDS', 'DUB' => 'IRL', 'AKL' => 'NZL', 'ICN' => 'KOR',
            'USN' => 'KOR', 'PUS' => 'KOR', 'HKG' => 'HKG', 'TPE' => 'TWN', 'KUL' => 'MYS'
        ];
        $country = isset($country_codes[$airport_code]) ? $country_codes[$airport_code] : null;

        $estimatedDepartureDate = "N/A";
        $rosdCorrectDateRaw = $row['rOSD_changed'] ?? $row['rOSD_po'] ?? $row['rOSD_forecast'] ?? null;
        
        if (!empty($rosdCorrectDateRaw)) {
            try {
                $date = new DateTime($rosdCorrectDateRaw);
                if (!empty($row['freight_type']) && strtolower($row['freight_type']) === 'air') {
                    $date->modify('-7 days');
                } else {
                    if ($country === "AUS") {
                        $date->modify('-2 days');
                    } elseif ($country === "NZL") {
                        $date->modify('-14 days');
                    } elseif (in_array($country, ["IDS", "THA", "SGP", "MYS"])) {
                        $date->modify('-28 days');
                    } elseif (in_array($country, ["IND", "IRL", "KOR", "TWN", "HKG", "JPN"])) {
                        $date->modify('-56 days');
                    }
                }
                $estimatedDepartureDate = $date->format("j F Y");
            } catch (Exception $e) {
                $estimatedDepartureDate = "N/A";
            }
        }

        // Prepare data row
        $data = [
            $row['item_number'] ?? 'N/A',
            $row['project_no'] ?? 'N/A',
            $row['fbn'] ?? 'N/A',
            $row['site_type'] ?? 'N/A',
            $row['status'] ?? 'N/A',
            (!empty($row['aws_po_no']) ? 'Yes' : 'No'),
            $row['version'] ?? 'N/A',
            $row['qty'] ?? 'N/A',
            isset($row['cost']) ? '$' . number_format($row['cost'], 2) : 'N/A',
            !empty($row['rOSD_forecast']) ? date("j F Y", strtotime($row['rOSD_forecast'])) : 'N/A',
            ($row['conflict'] === "1") ? "Conflict" : "N/A",
            $rosdCorrectDate,
            $estimatedDepartureDate,
            !empty($row['actual_departure_date']) ? date("j F Y", strtotime($row['actual_departure_date'])) : 'N/A',
            !empty($row['actual_delivered_date']) ? date("j F Y", strtotime($row['actual_delivered_date'])) : 'N/A',
            $row['customer'] ?? 'N/A',
            $row['entity_po_no'] ?? 'N/A',
            $row['aws_po_no'] ?? 'N/A',
            !empty($row['purchase_order_date']) ? date("j F Y", strtotime($row['purchase_order_date'])) : 'N/A',
            $row['drawing_status'] ?? 'N/A',
            $row['serial_numbers'] ?? 'N/A',
            !empty($row['rOSD_po']) ? date("j F Y", strtotime($row['rOSD_po'])) : 'N/A',
            $row['resolved'] ?? 'N/A',
            ($row['resolved'] === "PO" && !empty($row['rOSD_po'])) ? date("j F Y", strtotime($row['rOSD_po'])) : 
            (($row['resolved'] === "Forecast" && !empty($row['rOSD_forecast'])) ? date("j F Y", strtotime($row['rOSD_forecast'])) : 'N/A'),
            !empty($row['rOSD_changed']) ? date("j F Y", strtotime($row['rOSD_changed'])) : 'N/A',
            ($row['approved'] === "1" ? "Yes" : ($row['approved'] === "0" ? "No" : "N/A")),
            $row['freight_type'] ?? 'N/A',
            $row['notes'] ?? 'N/A'
        ];

        fputcsv($output, $data);
    }
} else {
    $noData = array_fill(0, count($headers), 'No data found');
    fputcsv($output, $noData);
}

fclose($output);
exit;
?>