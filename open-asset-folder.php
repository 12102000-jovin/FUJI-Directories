<?php
require_once("./db_connect.php");
require_once("./status_check.php");
?>

<head>
    <title>Asset Folder</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
</head>

<body class="background-color">
    <?php
    // Get asset number and sub_folder from URL parameters
    $asset_no = isset($_GET['folder']) ? basename($_GET['folder']) : '';
    $sub_folder = isset($_GET['sub_folder']) ? basename($_GET['sub_folder']) : '';

    // Set the base directory path
    $baseDirectory = 'D:/FSMBEH-Data/00 - QA/04 - Assets';
    $directory = $baseDirectory . '/' . $asset_no . '/' . $sub_folder;

    // Handle search query if provided
    $searchQuery = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';

    // Check if the directory exists
    if (is_dir($directory)) {
        // Scan the directory for files and folders
        $filesAndDirs = scandir($directory);

        // Filter files based on search query
        if ($searchQuery) {
            $filesAndDirs = array_filter($filesAndDirs, function ($item) use ($searchQuery) {
                return stripos($item, $searchQuery) !== false;
            });
        }

        echo "<div class='container mt-3'>";
        echo "<div class='mb-3'>";
        echo '<button class="btn btn-sm signature-btn mb-4" id="closeButton"> <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Close Tab</button>';
        echo "<h5> Directory: <span class='text-decoration-underline'>$directory</span> </h5>";

        // Search form
        echo '<div class="mb-5">';
        echo '<form method="get" class="d-flex w-100">';
        echo '<input type="hidden" name="folder" value="' . htmlspecialchars($asset_no) . '">';
        echo '<input type="hidden" name="sub_folder" value="' . htmlspecialchars($sub_folder) . '">';
        echo '<div class="input-group me-2">';
        echo '<span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>';
        echo '<input type="search" class="form-control" id="search" name="search" placeholder="Search" value="' . htmlspecialchars($searchQuery) . '">';
        echo '</div>';
        echo '<button class="btn btn-primary" type="submit">Search</button>';
        echo '</form>';
        echo '</div>';

        // Back button
        echo "<a href='?folder=" . urlencode($asset_no) . "' class='btn btn-sm btn-secondary'><i class='fas fa-arrow-left'></i> Back</a>";
        echo "</div>";

        // List files and directories
        echo "<div class='list-group rounded-3'>";
        foreach ($filesAndDirs as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $itemPath = $directory . '/' . $item;
            $itemName = htmlspecialchars($item);
            $fileExtension = strtolower(pathinfo($item, PATHINFO_EXTENSION));

            if (is_dir($itemPath)) {
                echo "<a href='" . $_SERVER['PHP_SELF'] . "?folder=" . urlencode($asset_no) . "&sub_folder=" . urlencode($sub_folder) . "&dir=" . urlencode($item) . "' class='list-group-item list-group-item-action d-flex align-items-center'>";
                echo "<i class='fa-solid fa-folder text-warning me-2'></i><span class='me-2'>" . $itemName . "</span>";
                echo "</a>";
            } else {
                $fileUrl = 'open-asset-details-file.php?file=' . urlencode($item) . "&folder=" . urlencode($asset_no) . "&sub_folder=" . urlencode($sub_folder);

                // Determine the icon based on file extension
                if ($fileExtension === 'pdf') {
                    $icon = 'fa-file-pdf text-danger';
                } elseif ($fileExtension === 'doc' || $fileExtension === 'docx') {
                    $icon = 'fa-file-word text-primary';
                } elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $icon = 'fa-file-image text-success';
                } else {
                    $icon = 'fa-file';
                }

                echo "<a href='" . $fileUrl . "' class='list-group-item list-group-item-action d-flex align-items-center' target='_blank'>";
                echo "<i class='fa-solid " . $icon . " me-2'></i><span class='me-2'>" . $itemName . "</span>";
                echo "</a>";
            }
        }

        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='container mt-3'>";
        echo "<div class='alert alert-danger' role='alert'>Directory does not exist.</div>";
        echo "</div>";
    }
    ?>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("closeButton").addEventListener("click", function () {
        window.close();
    });
</script>