<?php 
require_once ("db_connect.php");
require_once("status_check.php");
?>

<head>
    <title> <?php echo $folder ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
</head>

<body class="background-color">
    <?php
    $folder = isset($_GET['folder']) ? basename($_GET['folder']) : '';
    $searchQuery = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';

    $baseDirectory = 'D:\FSMBEH-Data\04 - PJ\03 - Projects';
    $directory = realpath($baseDirectory . DIRECTORY_SEPARATOR . $folder);

    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '';
    $fullDirectory = realpath($directory . ($currentDir ? DIRECTORY_SEPARATOR . $currentDir : ''));

    // Ensure the directory exists and is within the base directory
    if ($fullDirectory && strpos($fullDirectory, $baseDirectory) === 0 && is_dir($fullDirectory)) {
        $filesAndDirs = scandir($fullDirectory);

        // Extract parent directory for navigation
        $parentDir = dirname($fullDirectory);
        $parentDirName = basename($parentDir);
        $currentDirName = basename($fullDirectory);

        if ($searchQuery) {
            $filesAndDirs = array_filter($filesAndDirs, function ($item) use ($searchQuery) {
                return stripos($item, $searchQuery) !== false;
            });
        }

        echo "<div class='container mt-3'>";
        echo "<div class='mb-3'>";
        echo '<button class="btn btn-sm signature-btn mb-4" id="closeButton"><i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Close Tab</button>';
        echo "<h5>Directory: <span class='text-decoration-underline'>$fullDirectory</span></h5>";

        echo '<div class="mb-5">';
        echo '<form method="get" class="d-flex w-100">';
        echo '<input type="hidden" name="folder" value="' . htmlspecialchars($folder) . '">';
        echo '<input type="hidden" name="dir" value="' . htmlspecialchars($currentDir) . '">';
        echo '<div class="input-group me-2">';
        echo '<span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>';
        echo '<input type="search" class="form-control" id="search" name="search" placeholder="Search" value="' . htmlspecialchars($searchQuery) . '">';
        echo '</div>';
        echo '<button class="btn btn-primary" type="submit">Search</button>';
        echo '</form>';
        echo '</div>';

        echo '<div class="d-flex justify-content-between align-items-center">';
        if ($currentDir) {
            $parentDirPath = dirname($currentDir);
            echo "<a href='?folder=" . urlencode($folder) . "&dir=" . urlencode($parentDirPath) . "' class='btn btn-sm btn-secondary'><i class='fas fa-arrow-left'></i> 

Back</a>";
        }

        echo '<div class="mb-3">
                <button id="toggleGrid" class="btn btn-sm btn-outline-primary ms-2"> 
                    <i class="fa-solid fa-grip me-2"></i>Grid View
                </button>
            </div>';

        echo "</div>";
        echo "</div>";

        echo "<div class='view-container list-view'>";
        echo "<div class='list-group rounded-3'>";

        foreach ($filesAndDirs as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $itemPath = realpath($fullDirectory . DIRECTORY_SEPARATOR . $item);
            $itemUrl = '?folder=' . urlencode($folder) . '&dir=' . urlencode($currentDir . DIRECTORY_SEPARATOR . $item) . '&search=' . urlencode($searchQuery);
            $itemName = htmlspecialchars($item);
            $fileExtension = strtolower(pathinfo($item, PATHINFO_EXTENSION));

            if (is_dir($itemPath)) {
                echo "<a href='" . $itemUrl . "' class='list-group-item list-group-item-action d-flex align-items-center'>";
                echo "<i class='fa-solid fa-folder text-warning me-2'></i><span class='me-2'>" . $itemName . "</span>";
                echo "</a>";
            } else {
                $fileUrl = 'open-project-file.php?file=' . urlencode($item) . "&folder=" . urlencode($folder) . "&dir=" . urlencode($currentDir);

                $icon = match ($fileExtension) {
                    'pdf' => 'fa-file-pdf text-danger',
                    'doc', 'docx' => 'fa-file-word text-primary',
                    'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image text-success',
                    'dwg', => 'fa-solid fa-compass-drafting text-primary',
                    'xls', => 'fa-solid fa-file-excel',
                    'xlsx', => 'fa-solid fa-file-excel text-success',
                    default => 'fa-file',
                };

                echo "<div class='list-group-item list-group-item-action d-flex align-items-center'>";  // Use a div for non-clickable container
                echo "  <i class='fa-solid " . $icon . " me-2'></i>";  // Icon
                echo "  <span class='me-2'>" . $itemName . "</span>";  // Item name
                
                if ($fileExtension === "dwg") {
                    // Prepare the file path for ARES
                    $aresPath = str_replace("\\", "/", $itemPath); // Convert backslashes to forward slashes
                    $aresPath = preg_replace('/^[A-Za-z]:/', 'Z:', $aresPath); // Replace any drive letter with Z:
                    
                    // Remove the first folder after the drive letter
                    $aresPath = preg_replace('/^Z:\/[^\/]+\//', 'Z:/', $aresPath); // Remove first folder after Z:/
                    
                    // Add the button to open in ARES, aligned to the right of the same row
                    echo "  <a href='ares://" . $aresPath . "' class='btn btn-sm btn-danger ms-auto'>Open in Ares</a>";
                } else {
                    // For non-dwg files, make the row clickable
                    $fileUrl = 'open-project-file.php?file=' . urlencode($item) . "&folder=" . urlencode($folder) . "&dir=" . urlencode($currentDir);
                    echo "  <a href='" . $fileUrl . "' class='stretched-link'></a>";  // Make the row clickable with stretched-link
                }
                
                echo "</div>";  // Close the div
            }
        }

        echo "</div>";
        echo "</div>";

        // Grid View (hidden initially)
        echo "<div class='view-container grid-view d-none'>";
        echo "<div class='row row-cols-1 row-cols-md-4 g-4'>";
        foreach ($filesAndDirs as $item) {
            if ($item === '.' || $item === '..') continue;
        
            $itemPath = realpath($fullDirectory . DIRECTORY_SEPARATOR . $item);
            $itemUrl = '?folder=' . urlencode($folder) . '&dir=' . urlencode($currentDir . DIRECTORY_SEPARATOR . $item) . '&search=' . urlencode($searchQuery);
            $itemName = htmlspecialchars($item);
            $fileExtension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        
            echo "<div class='col'>";
            echo "<div class='card shadow-sm h-100'>";
            echo "<div class='card-body d-flex justify-content-center align-items-center h-100 text-center'>"; // center all contents
        
            echo "<div class='d-flex flex-column align-items-center justify-content-center'>"; // wrap everything in a centered column
        
            if (is_dir($itemPath)) {
                echo "<a href='" . $itemUrl . "' class='text-decoration-none text-dark'>";
                echo "<i class='fa-solid fa-folder text-warning fa-3x mb-2'></i>";
                echo "<p class='mb-0'><small>" . $itemName . "</small></p>";
                echo "</a>";
            } else {
                $fileUrl = 'open-project-file.php?file=' . urlencode($item) . "&folder=" . urlencode($folder) . "&dir=" . urlencode($currentDir);
                echo "<a href='" . $fileUrl . "' class='text-decoration-none text-dark'>";
        
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo "<img src='" . htmlspecialchars($fileUrl) . "' class='img-fluid mb-2' alt='" . $itemPath . "' style='max-height: 150px; object-fit: cover;'>";
                } else {
                    $icon = match ($fileExtension) {
                        'pdf' => 'fa-file-pdf text-danger',
                        'doc', 'docx' => 'fa-file-word text-primary',
                        'dwg' => 'fa-solid fa-compass-drafting text-primary',
                        default => 'fa-file',
                    };
                    echo "<i class='fa-solid " . $icon . " fa-3x mb-2'></i>";
                }
        
                echo "<p class='mb-0 w-100' style='word-break: break-word;'><small>" . $itemName . "</small></p>";
                echo "</a>";
            }
        
            echo "</div>"; // close inner column
            echo "</div>"; // close card-body
            echo "</div>"; // close card
            echo "</div>"; // close col
        }
        echo "</div>"; // close row
        echo "</div>"; // close view-container
        


    } else {
        echo "<div class='container mt-3'>";
        echo "<div class='alert alert-danger' role='alert'>Directory does not exist or access is restricted.</div>";
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

<script>
    document.getElementById("closeButton").addEventListener("click", function () {
        window.close();
    });

    document.getElementById("toggleGrid").addEventListener("click", function () {
        const listView = document.querySelector(".list-view");
        const gridView = document.querySelector(".grid-view");
        const btn = document.getElementById("toggleGrid");

        const isList = !listView.classList.contains("d-none");

        if (isList) {
            listView.classList.add("d-none");
            gridView.classList.remove("d-none");
            btn.innerHTML = '<i class="fa-solid fa-bars me-2"></i>List View';
        } else {
            gridView.classList.add("d-none");
            listView.classList.remove("d-none");
            btn.innerHTML = '<i class="fa-solid fa-grip me-2"></i>Grid View';
        }
    });

</script>