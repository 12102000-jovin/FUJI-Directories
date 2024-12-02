<?php

$config = include('config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

require_once("db_connect.php");

$machine_competency_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '11-WH-WI-%'";
$machine_competency_result = $conn->query($machine_competency_sql);

// Store document names in an associative array (qa_document => document_name)
$documentNames = [];
if ($machine_competency_result) {
    while ($row = $machine_competency_result->fetch_assoc()) {
        $documentNames[$row['qa_document']] = $row['document_name'];
    }
}

// Assuming $employeeId is defined and valid
// Set the base directory for the employee
$baseDirectory = 'D:\FSMBEH-Data\09 - HR\04 - Wage Staff\\' . $employeeId . '\01 - Induction and Training Documents';

// Check if the directory exists
if (is_dir($baseDirectory)) {
    $files = scandir($baseDirectory);
    $hasCompetencyFiles = false;

    // Iterate through files in the directory
    foreach ($files as $file) {
        // Remove the .pdf extension from the file name for comparison
        $fileWithoutExtension = pathinfo($file, PATHINFO_FILENAME);

        // Check if the file matches the pattern '11-WH-WI-%' and is a file
        if (is_file($baseDirectory . '\\' . $file) && strpos($fileWithoutExtension, '11-WH-WI') !== false) {
            $hasCompetencyFiles = true;

            // Check if this file name matches any document in the database
            // We compare the last part of the file name after the timestamp (e.g., "11-WH-WI-001")
            $documentKey = substr($fileWithoutExtension, strpos($fileWithoutExtension, '11-WH-WI'));

            // Display the document name and the file link if we find a match
            if (isset($documentNames[$documentKey])) {
                echo '<ul><li class="mb-0 pb-0"><a href="http://' . htmlspecialchars($serverAddress) . '/' . htmlspecialchars($projectName) . '/open-machine-competency-file.php?employee_id=' . 
                     htmlspecialchars($employeeId) . '&folder=01 - Induction and Training Documents&file=' . htmlspecialchars($file) . '" 
                     target="_blank" 
                     class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                     <i class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>' . htmlspecialchars($documentNames[$documentKey]) . 
                     '</a><br></li></ul>';
            }
        }
    }

    // If no matching files found, display a message
    if (!$hasCompetencyFiles) {
        echo "No Machine Competency Register File";
    }
} else {
    echo "Directory does not exist.";
}
?>
