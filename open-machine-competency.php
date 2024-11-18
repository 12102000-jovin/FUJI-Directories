<?php

$config = include('config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

require_once("db_connect.php");

$machine_competency_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '11-WH-WI-%'";
$machine_competency_result = $conn->query($machine_competency_sql);

// Store document names in an associative array
$documentNames = [];
if ($machine_competency_result) {
    while ($row = $machine_competency_result->fetch_assoc()) {
        $documentNames[$row['qa_document']] = $row['document_name'];
    }
}

// Assuming $employeeId is defined and valid
// Set the base directory for the employee
$baseDirectory = '../';are

// Check if the directory exists
if (is_dir($baseDirectory)) {
    $files = scandir($baseDirectory);
    $hasCompetencyFiles = false;

    foreach ($files as $file) {
        // Remove the .pdf extension from the file name for comparison
        $fileWithoutExtension = pathinfo($file, PATHINFO_FILENAME);

        if (is_file($baseDirectory . $file) && strpos($fileWithoutExtension, '11-WH-WI') !== false) {
            $hasCompetencyFiles = true;

            // Display document_name if $fileWithoutExtension matches a key in $documentNames
            $displayName = isset($documentNames[$fileWithoutExtension]) ? $documentNames[$fileWithoutExtension] : $fileWithoutExtension;

            echo '<ul><li><a href="http://' . htmlspecialchars($serverAddress) . '/' . htmlspecialchars($projectName) . '/open-machine-competency-file.php?employee_id=' .
                htmlspecialchars($employeeId) . '&folder=01 - Induction and Training Documents&file=' . htmlspecialchars($file) . '" 
                target="_blank" 
                class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                <i class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>' . htmlspecialchars($displayName) .
                '</a><br></li></ul>';
        }
    }

    if (!$hasCompetencyFiles) {
        echo "No Machine Competency Register File";
    }
} else {
    echo "Directory does not exist.";
}
?>