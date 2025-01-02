<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("./../db_connect.php");
require_once("./../status_check.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrieve session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// SQL Query to get the folders
$folders_sql = "
    SELECT DISTINCT f.*
    FROM folders f
    JOIN groups_folders gf ON f.folder_id = gf.folder_id
    JOIN users_groups ug ON gf.group_id = ug.group_id
    JOIN users u ON ug.user_id = u.user_id
    JOIN employees e ON e.employee_id = u.employee_id
    WHERE e.employee_id = ?
";
$stmt = $conn->prepare($folders_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$folders_result = $stmt->get_result();

// Store the folders in an array
$folders = [];
while ($row = $folders_result->fetch_assoc()) {
    $folders[] = $row;
}

$folders_result->free();
?>

<head>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            box-sizing: border-box;
        }

        .list-unstyled li:hover {
            background-color: #043f9d;
            color: white !important;
        }

        #side-menu {
            width: 3.5rem;
            transition: width 0.2s ease;
        }

        .sidebar {
            position: sticky;
            top: 0;
            width: 64px;
            transition: width 0.2s ease;
            display: flex;
            flex-direction: column;
            z-index: 1002;
        }
    </style>
</head>

<div class="col-auto pe-0 sidebar">
    <div class="d-flex flex-column bg-white min-vh-100 shadow-lg" id="side-menu" style="width:64px">
        <div class="d-flex justify-content-between align-items-center mt-1" id="menu-toggle">
            <img src="../Images/FE-logo.png" class="m-2" style="width:3rem; cursor:pointer" onclick="toggleNav()">
            <i class="btn fa-solid fa-xmark me-3 close-button d-none" id="close-menu" onclick="closeNav()"></i>
        </div>
        <ul class="list-unstyled mt-1" id="list-menu">
            <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php"
                class="text-decoration-none">
                <li class="mx-2 py-2 p-1 rounded fw-bold text-dark d-flex justify-content-center align-items-center"
                    id="home-icon">
                    <i class="fa-solid fa-house"></i> <span class="folder-name d-none ms-1"> Home </span>
                </li>
            </a>
            <?php foreach ($folders as $row): ?>
                <?php
                $folder_name = htmlspecialchars($row['folder_name']); // Sanitize once for reuse
                $words = explode(' ', $folder_name);
                $initials = '';

                // Determine initials
                if (strtolower($folder_name) === 'work health and safety') {
                    $initials = 'WHS';
                } elseif (strtolower($folder_name) === 'project') {
                    $initials = 'PJ';
                } else {
                    foreach ($words as $word) {
                        $initials .= strtoupper($word[0]); // Get first letter of each word
                    }
                }

                // Determine folder page
                switch (strtolower($folder_name)) {
                    case 'human resources':
                        $folder_page = "http://$serverAddress/$projectName/Pages/hr-index.php";
                        break;
                    case 'quality assurances':
                        $folder_page = "http://$serverAddress/$projectName/Pages/qa-index.php";
                        break;
                    case 'project':
                        $folder_page = "http://$serverAddress/$projectName/Pages/pj-index.php";
                        break;
                    case 'work health and safety':
                        $folder_page = "http://$serverAddress/$projectName/Pages/whs-index.php";
                        break;
                    case 'test and tag':
                        $folder_page = "http://$serverAddress/$projectName/Pages/test-tag-table.php";
                        break;
                    default:
                        $folder_page = "http://$serverAddress/$projectName/Pages/index.php";
                        break;
                }
                ?>
                <a href="<?php echo $folder_page ?>" class="text-decoration-none text-dark">
                    <li
                        class="mx-2 py-2 p-1 rounded fw-bold d-flex justify-content-center align-items-center side-menu-folder-list">
                        <span class="folder-initials"><?= htmlspecialchars($initials) ?></span>
                        <div class="d-flex justify-content-start">
                            <span class="folder-name d-none"><?= $folder_name ?></span>
                        </div>
                    </li>
                </a>
            <?php endforeach; ?>

        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const menuToggle = document.getElementById("side-menu");
    const closeMenu = document.getElementById("close-menu");
    const folderListFullName = document.getElementsByClassName("folder-name");
    const folderListInitial = document.getElementsByClassName("folder-initials");

    function toggleNav() {
        if (menuToggle.style.width === "250px") {
            menuToggle.style.width = "64px";
            closeMenu.classList.add("d-none");

            // Hide full names and show initials
            for (let i = 0; i < folderListFullName.length; i++) {
                folderListFullName[i].classList.add("d-none");
                folderListInitial[i].classList.remove("d-none");
            }
        } else {
            menuToggle.style.width = "250px";
            closeMenu.classList.remove("d-none");

            // Show full names and hide initials
            for (let i = 0; i < folderListFullName.length; i++) {
                folderListFullName[i].classList.remove("d-none");
                folderListInitial[i].classList.add("d-none");
            }
        }
    }

    function closeNav() {
        menuToggle.style.width = "64px";
        closeMenu.classList.add("d-none");

        // Ensure initials are shown when menu is closed
        for (let i = 0; i < folderListFullName.length; i++) {
            folderListFullName[i].classList.add("d-none");
            folderListInitial[i].classList.remove("d-none");
        }
    }
</script>