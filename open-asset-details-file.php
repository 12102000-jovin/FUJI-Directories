<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the current file to display
$file = isset($_GET['file']) ? basename($_GET['file']) : '';
$assetNo = isset($_GET['asset_no']) ? basename($_GET['asset_no']) : '';
$folder = isset($_GET['folder']) ? basename($_GET['folder']) : '';
$sub_folder = isset($_GET['sub_folder']) ? basename($_GET['sub_folder']) : '';

$baseDirectory = 'D:\FSMBEH-Data\00 - QA\04 - Assets';
$filePath = $baseDirectory . "/" . $assetNo . "/" . $folder . "/" . $sub_folder. "/" .$file;

echo $file;
echo $assetNo;
echo $folder;
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
?>