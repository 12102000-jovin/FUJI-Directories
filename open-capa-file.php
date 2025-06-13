<?php

require_once ("./db_connect.php");
require_once("./status_check.php");

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the current file to display (if any)
$currentFile = isset($_GET['file']) ? basename($_GET['file']) : '';
$folder = isset($_GET['folder']) ? basename($_GET['folder']) : '';
$subDir = isset($_GET['dir']) ? basename($_GET['dir']) : '';

$baseDirectory = 'D:\FSMBEH-Data\00 - QA\05 - CAPA';
$filePath = $baseDirectory . "/" . $folder . ($subDir ? '/' . $subDir : '') . "/" . $currentFile;

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
        $mimeType = mime_content_type($filePath); // Detect MIME type for images
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } else {
        echo "Unsupported file type.";
    }
} else {
    echo "File does not exist.";
}