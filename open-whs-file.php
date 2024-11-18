<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the current file to display
$currentFile = isset($_GET['file']) ? basename($_GET['file']) : '';
$folder = isset($_GET['folder']) ? basename($_GET['folder']) : '';
$employeeId = isset($_GET['employee_id']) ? basename($_GET['employee_id']) : '';
$subDir = isset($_GET['dir']) ? basename($_GET['dir']) : '';

$baseDirectory = 'D:\FSMBEH-Data\09 - HR\04 - Wage Staff\\';
$filePath = $baseDirectory . $employeeId . "/" . $folder . ($subDir ? '/' . $subDir : '') . "/" . $currentFile;

// Check if the file exists
if (file_exists($fildePath)) {
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if ($fileExtension === 'pdf') {
        // Serve the PDF file directly
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline, filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } else if ($fileExtension === 'doc' || $fileExtension === 'docx') {
        // Prompt user to download Word documents
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } else {
        echo "Unsupported file type.";
    }
} else {
    echo "File does not exist.";
}
