<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the current file to display (if any)
$currentFile = isset($_GET['file']) ? basename($_GET['file']) : '';

if ($currentFile) {
    // Extract the first two digits from the file name
    $prefix = substr($currentFile, 0, 2);
    echo "Extracted prefix: '" . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . "'<br>";

    // Determine the directory based on the prefix
    switch ($prefix) {
        case '00':
            $directory = 'D:/FSMBEH-Data/00 - QA/01 - Documents PDF';
            break;
        case '02':
            $directory = 'D:/FSMBEH-Data/02 - ES/01 - Documents PDF';
            break;
        case '03':
            $directory = 'D:/FSMBEH-Data/03 - AC/01 - Documents PDF';
            break;
        case '05':
            $directory = 'D:/FSMBEH-Data/05 - EN/01 - Documents PDF';
            break;
        case '06':
            $directory = 'D:/FSMBEH-Data/06 - EL/01 - Documents PDF';
            break;
        case '07':
            $directory = 'D:/FSMBEH-Data/07 - SM/01 - Documents PDF';
            break;
        case '08':
            $directory = 'D:/FSMBEH-Data/08 - OS/01 - Documents PDF';
            break;
        case '09':
            $directory = 'D:/FSMBEH-Data/09 - HR/01 - Documents PDF';
            break;
        case '10':
            $directory = 'D:/FSMBEH-Data/10 - RD/01 - Documents PDF';
            break;
        case '11':
            $directory = 'D:/FSMBEH-Data/11 - WH/01 - Documents PDF';
            break;
        case '12s':
            $directory = 'D:/FSMBEH-Data/12 - QC/01 - Documents PDF';
            break;
        // Add more cases as needed for other prefixes
        default:
            echo "Directory for this file prefix is not defined.";
            exit;
    }

    // Check if the directory exists and is accessible
    if (!is_dir($directory)) {
        echo "The directory $directory does not exist or is not accessible.";
        exit;
    }

    $filePath = $directory . DIRECTORY_SEPARATOR . $currentFile;

    if (file_exists($filePath) && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
        // Serve the PDF file directly
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } else {
        echo "File does not exist.";
    }
} else {

    foreach ($directories as $dir => $prefix) {
        if ($handle = opendir($dir)) {
            echo "Directory listing for $dir:<br>";

            // Read each entry in the directory
            while (false !== ($entry = readdir($handle))) {
                // Skip '.' and '..' entries
                if ($entry !== '.' && $entry !== '..' && is_file($dir . DIRECTORY_SEPARATOR . $entry)) {
                    // Link to files for display
                    echo '<a href="?file=' . urlencode($entry) . '">' . htmlspecialchars($entry, ENT_QUOTES, 'UTF-8') . '</a><br>';
                }
            }

            // Close the directory handle
            closedir($handle);
        } else {
            echo "Unable to open directory: $dir";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($currentFile, ENT_QUOTES, 'UTF-8'); ?></title>
</head>

<body>
</body>

</html>