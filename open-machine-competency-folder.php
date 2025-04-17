<?php
$config = include('config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

require_once("db_connect.php");

// Get payroll_type for the given employeeId
$employeeId = $_GET['employee_id'] ?? null; // Ensure employeeId is received
$payroll_type = null;

if ($employeeId) {
    $payroll_query = "SELECT payroll_type FROM employees WHERE employee_id = ?";
    $stmt = $conn->prepare($payroll_query);
    $stmt->bind_param("s", $employeeId);
    $stmt->execute();
    $stmt->bind_result($payroll_type);
    $stmt->fetch();
    $stmt->close();
}

// Set base directory based on payroll type
if ($payroll_type === 'wage') {
    $baseDirectory = 'D:\FSMBEH-Data\09 - HR\04 - Wage Staff\\' . $employeeId . '\01 - Induction and Training Documents';
} elseif ($payroll_type === 'salary') {
    $baseDirectory = 'D:\FSMBEH-Data\09 - HR\05 - Salary Staff\\' . $employeeId . '\01 - Induction and Training Documents';
} else {
    echo "Invalid payroll type or employee not found.";
    exit;
}

// Query QA documents
$machine_competency_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '11-WH-WI-%'";
$machine_competency_result = $conn->query($machine_competency_sql);

// Store document names in an associative array (qa_document => document_name)
$documentNames = [];
if ($machine_competency_result) {
    while ($row = $machine_competency_result->fetch_assoc()) {
        $documentNames[$row['qa_document']] = $row['document_name'];
    }
}

// Check if directory exists
if (is_dir($baseDirectory)) {
    $files = scandir($baseDirectory);
    $sortedDocuments = [];

    foreach ($files as $file) {
        $fullFilePath = $baseDirectory . '\\' . $file;

        // Ensure it's a valid file (not a directory)
        if (is_file($fullFilePath)) {
            // Extract `11-WH-WI-###` format using regex
            if (preg_match('/(11-WH-WI-\d+)/', $file, $matches)) {
                $documentKey = $matches[1];

                if (isset($documentNames[$documentKey])) {
                    $sortedDocuments[$documentKey] = [
                        'file' => $file,
                        'documentName' => $documentNames[$documentKey]
                    ];
                }
            }
        }
    }

    // Sort by document key
    ksort($sortedDocuments);

    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-hover mb-0 pb-0">';
    echo '<thead><tr><th>Document Id</th><th>Document Name</th></tr></thead>';
    echo '<tbody>';

    foreach ($sortedDocuments as $documentKey => $document) {
        echo '<tr class="my-0 py-0">';
        echo '<td class="my-0 py-0 align-middle"><a href="http://' . htmlspecialchars($serverAddress) . '/' . htmlspecialchars($projectName) . '/open-machine-competency-

file.php?employee_id=' . htmlspecialchars($employeeId) . '&folder=01 - Induction and Training Documents&file=' . htmlspecialchars($document['file']) . '" target="_blank" 

class="btn btn-link text-decoration-underline fw-bold">' . htmlspecialchars($documentKey) . '</a></td>';
        echo '<td class="my-0 py-0 align-middle">' . htmlspecialchars($document['documentName']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
    
    if (empty($sortedDocuments)) {
        echo "<p class='m-0 p-0'>No Machine Competency File</p>";
    }
} else {
    echo "<p class='m-0 p-0'>Directory does not exist.</p>";
}
?>

