<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the current file to display (if any)
$currentFile = isset($_GET['file']) ? basename($_GET['file']) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentFile, ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <?php
        if ($currentFile) {
            // Extract the first two digits from the file name
            $prefix = substr($currentFile, 0, 2);
            echo "<p>Extracted prefix: '" . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . "'</p>";

            // Determine the directory based on the prefix
            switch ($prefix) {
                case '00':
                    $directory = 'D:/FSMBEH-Data/00 - QA/00 - Documents/01 - WIP Documents';
                    break;
                case '01':
                    $directory = 'D:/FSMBEH-Data/01 - MN/00 - Documents/01 - WIP Documents';
                    break;
                case '02':
                    $directory = 'D:/FSMBEH-Data/02 - ES/00 - Documents/01 - WIP Documents';
                    break;
                case '03':
                    $directory = 'D:/FSMBEH-Data/03 - AC/00 - Documents/01 - WIP Documents';
                    break;
                case '04':
                    $directory = 'D:/FSMBEH-Data/04 - PJ/01 - Documents PDF';
                    break;
                case '05':
                    $directory = 'D:/FSMBEH-Data/05 - EN/00 - Documents/01 - WIP Documents';
                    break;
                case '06':
                    $directory = 'D:/FSMBEH-Data/06 - EL/00 - Documents/01 - WIP Documents';
                    break;
                case '07':
                    $directory = 'D:/FSMBEH-Data/07 - SM/00 - Documents/01 - WIP Documents';
                    break;
                case '08':
                    $directory = 'D:/FSMBEH-Data/08 - OS/00 - Documents/01 - WIP Documents';
                    break;
                case '09':
                    $directory = 'D:/FSMBEH-Data/09 - HR/00 - Documents/01 - WIP Documents';
                    break;
                case '10':
                    $directory = 'D:/FSMBEH-Data/10 - RD/00 - Documents/01 - WIP Documents';
                    break;
                case '11':
                    $directory = 'D:/FSMBEH-Data/11 - WH/00 - Documents/01 - WIP Documents';
                    break;
                case '12':
                    $directory = 'D:/FSMBEH-Data/12 - QC/00 - Documents/01 - WIP Documents';
                    break;
                case '15':
                    $directory = 'D:/FSMBEH-Data/15 - SP/01 - Documents PDF';
                    break;
                case '16':
                    $directory = 'D:/FSMBEH-Data/16 - CC/01 - Documents PDF';
                    break;
                default:
                    echo "<div class='alert alert-danger' role='alert'>
                        Directory for this file prefix is not defined.
                      </div>";
                    exit;
            }

            // Check if the directory exists and is accessible
            if (!is_dir($directory)) {
                echo "<div class='alert alert-danger' role='alert'>
                    The directory <strong>" . htmlspecialchars($directory, ENT_QUOTES, 'UTF-8') . "</strong> does not exist or is not accessible.
                  </div>";
                exit;
            }

            $filePath = $directory . DIRECTORY_SEPARATOR . $currentFile;

            if (file_exists($filePath) && (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'doc' || strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'docx')) {
                // Serve the DOC/DOCX file directly
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $contentType = $extension === 'doc' ? 'application/msword' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

                header('Content-Type: ' . $contentType);
                header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
                readfile($filePath);
                exit;
            } else {
                echo "<div class='alert alert-warning' role='alert'>
                    File does not exist or is not a DOC/DOCX file.
                  </div>";
            }
        } else {
            // Define the directories array
            $directories = [
                'D:/FSMBEH-Data/00 - QA/01 - Documents DOC' => '00',
                'D:/FSMBEH-Data/02 - ES/01 - Documents DOC' => '02',
                'D:/FSMBEH-Data/03 - AC/01 - Documents DOC' => '03',
                // Add other directories as needed
            ];

            foreach ($directories as $dir => $prefix) {
                if ($handle = opendir($dir)) {
                    echo "<h3>Directory listing for $dir:</h3>";

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
                    echo "<div class='alert alert-danger' role='alert'>
                        Unable to open directory: <strong>" . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . "</strong>
                      </div>";
                }
            }
        }
        ?>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>