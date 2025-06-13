<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once ("./db_connect.php");
require_once("./status_check.php");

// Get the current file to display (if any)
$currentFile = isset($_GET['file']) ? basename($_GET['file']) : '';
$folder = isset($_GET['folder']) ? basename($_GET['folder']) : '';

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
    $baseDirectory = 'D:\FSMBEH-Data\09 - HR\04 - Wage Staff\\';
} elseif ($payroll_type === 'salary') {
    $baseDirectory = 'D:\FSMBEH-Data\09 - HR\05 - Salary Staff\\';
} else {
    echo "Invalid payroll type or employee not found.";
    exit;
}

$filePath = $baseDirectory . $employeeId . "/" . $folder . "/02 - Policies" . "/" . $currentFile;

echo $filePath;

// Check if the file exists
if (file_exists($filePath)) {
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if ($fileExtension === 'pdf') {
        // Serve the PDF file directly
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } elseif ($fileExtension === 'doc' || $fileExtension === 'docx') {
        // Prompt user to download Word documents
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
        // Server image files
        $mimeType = mime_content_type($filePath); // Detect MIME type images
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
    } else {
        echo "Unsupported file type." . $filePath;
    }
} else {
    echo "File does not exist.";
}