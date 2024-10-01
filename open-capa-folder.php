<head>
    <title> <?php echo $folder . " - " . $employeeId ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
</head>

<body class="background-color">
    <?php
    $employeeId = isset($_GET['employee_id']) ? basename($_GET['employee_id']) : '';
    $folder = isset($_GET['folder']) ? basename($_GET['folder']) : '';
    $searchQuery = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';

    $baseDirectory = '../../../../Employees/';
    $directory = $baseDirectory . $employeeId . "/" . $folder;

    $currentDir = isset($_GET['dir']) ? basename($_GET['dir']) : '';
    $fullDirectory = $directory . ($currentDir ? '/' . $currentDir : '');

    // Ensure the directory exists
    if (is_dir($fullDirectory)) {
        // Get all files and directories within the specified directory
        $filesAndDirs = scandir($fullDirectory);

        // Extract parent directory for navigation
        $parentDir = dirname($fullDirectory);
        $parentDirName = basename($parentDir);
        $currentDirName = basename($fullDirectory);

        // Filter results based on search query
        if ($searchQuery) {
            $filesAndDirs = array_filter($filesAndDirs, function ($item) use ($searchQuery) {
                return stripos($item, $searchQuery) !== false;
            });
        }

        echo "<div class='container mt-3'>"; // Container for Bootstrap styling
        echo "<div class='mb-3'>";
        echo '<button class="btn btn-sm signature-btn mb-4" id="closeButton"> <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Close Tab</button>';
        echo "<h5>  Directory: <span class='text-decoration-underline'>$fullDirectory </span> </h5>";

        echo '<div class="mb-5">';
        echo '<form method="get" class="d-flex w-100">';
        echo '<input type="hidden" name="employee_id" value="' . htmlspecialchars($employeeId) . '">';
        echo '<input type="hidden" name="folder" value="' . htmlspecialchars($folder) . '">';
        echo '<input type="hidden" name="dir" value="' . htmlspecialchars($currentDir) . '">';
        echo '<div class="input-group me-2">';
        echo '<span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>';
        echo '<input type="search" class="form-control" id="search" name="search" placeholder="Search" value="' . htmlspecialchars($searchQuery) . '">';
        echo '</div>';
        echo '<button class="btn btn-primary" type="submit">Search</button>';
        echo '</form>';
        echo '</div>';

        if ($currentDir && $currentDir !== ".") {
            // Link to go back to the parent directory
            echo "<a href='?employee_id=" . urlencode($employeeId) . "&folder=" . urlencode($folder) . "&dir=" . urlencode(dirname($currentDir)) . "' class='btn btn-sm btn-secondary'><i class='fas fa-arrow-left'></i> Back</a>";
        }
        echo "</div>";

        echo "<div class='list-group rounded-3'>";
        foreach ($filesAndDirs as $item) {
            // Skip the current (.) and parent (..) directories
            if ($item === '.' || $item === '..')
                continue;

            $itemPath = $fullDirectory . '/' . $item;
            $itemUrl = '?employee_id=' . urlencode($employeeId) . '&folder=' . urlencode($folder) . '&dir=' . urlencode($currentDir . '/' . $item) . '&search=' . urlencode($searchQuery);
            $itemName = htmlspecialchars($item);
            $fileExtension = strtolower(pathinfo($item, PATHINFO_EXTENSION));

            if (is_dir($itemPath)) {
                echo "<a href='" . $itemUrl . "' class='list-group-item list-group-item-action d-flex align-items-center'>";
                echo "<i class='fa-solid fa-folder text-warning me-2'></i><span class='me-2'>" . $itemName . "</span>";
                echo "</a>";
            } else {
                $fileUrl = 'open-file.php?file=' . urlencode($item) . "&folder=" . urlencode($folder) . "&employee_id=" . urlencode($employeeId) . "&dir=" . urlencode($currentDir);

                // Determine the icon based on file extension
                if ($fileExtension === 'pdf') {
                    $icon = 'fa-file-pdf text-danger'; // PDF icon
                } elseif ($fileExtension === 'doc' || $fileExtension === 'docx') {
                    $icon = 'fa-file-word text-primary'; // Word icon
                } else {
                    $icon = 'fa-file'; // Default icon for other file types
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