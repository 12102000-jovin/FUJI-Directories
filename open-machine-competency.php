<?php

$config = include('config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Assuming $employeeId is defined and valid
// Set the base directory for the employee
$baseDirectory = 'D:\FSMBEH-Data\09 - HR\04 - Wage Staff\\' . $employeeId . '\01 - Induction and Training Documents\\';

// Check if the directory exists
if (is_dir($baseDirectory)) {
    // Scan the directory for files
    $files = scandir($baseDirectory);
    $hasCompetencyFiles = false; // Flag to track if we found any competency files

    // Filter and display files that contain '11-WH-WI-' in the filename
    foreach ($files as $file) {
        if (is_file($baseDirectory . $file) && strpos($file, '11-WH-WI-') !== false) {
            $hasCompetencyFiles = true; // Set flag to true if a file is found
            // Corrected echo statement for the file link
            echo '<ul><li><a href="http://' . htmlspecialchars($serverAddress) . '/' . htmlspecialchars($projectName) . '/open-machine-competency-file.php?employee_id=' . 
                htmlspecialchars($employeeId) . '&folder=01 - Induction and Training Documents&file=' . htmlspecialchars($file) . '" 
                target="_blank" 
                class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                <i class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>' . htmlspecialchars($file) . 
                '</a><br></li></ul>';
        }
    }

    // If no competency files were found, display the message
    if (!$hasCompetencyFiles) {
        echo "No Machine Competency Register File";
    }
} else {
    echo "Directory does not exist.";
}
?>

