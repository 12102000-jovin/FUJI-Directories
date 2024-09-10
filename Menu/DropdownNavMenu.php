<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("./../db_connect.php");
require_once("./../status_check.php");
require_once("./../system_role_check.php");

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

// Prepare the SQL query to avoid SQL injection
$user_details_query = "
    SELECT e.*
    FROM employees e
    JOIN users u ON e.employee_id = u.employee_id
    WHERE u.username = ?
";
$stmt = $conn->prepare($user_details_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_details_result = $stmt->get_result();

// Fetch user details
if ($user_details_result && $user_details_result->num_rows > 0) {
    $row = $user_details_result->fetch_assoc();
    $firstName = $row['first_name'];
    $lastName = $row['last_name'];
    $employeeId = $row['employee_id'];
    $profileImage = $row['profile_image'];
} else {
    $firstName = 'N/A';
    $lastName = 'N/A';
    $employeeId = 'N/A';
    $profileImage = '';
}

// Free up memory
$user_details_result->free();
$folders_result->free();
?>

<head>
    <style>
        .sticky-top-menu {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .list-unstyled li:hover {
            background-color: #043f9d;
            color: white !important;
        }

        /* Custom styles for multi-level dropdown */
        .dropdown-submenu {
            position: relative;
        }

        .dropdown-submenu .dropdown-menu {
            top: 0;
            left: auto;
            right: 100%;
            margin-top: -1px;
        }

        /* Ensure submenus are displayed on hover */
        .dropdown-submenu:hover>.dropdown-menu {
            display: block;
        }

        /* Hide submenus initially */
        .dropdown-menu .dropdown-menu {
            display: none;
        }
    </style>
</head>

<div class="bg-white shadow-lg hide-print">
    <div class="d-flex align-items-center justify-content-between">
        <div class="container-fluid d-flex align-items-center">
            <button class="navbar-toggler btn mx-2 cursor-pointer" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="fa fa-bars fa-lg"></i>
            </button>
            <img src="../Images/FE-logo.png" class="ms-2 py-3" style="width:3rem">
            <div class="vr mx-2 my-3"></div>
            <h5 id="top-menu-title" style="color: #043f9d;" class="m-0 fw-bold"></h5>
        </div>
        <div class="d-flex align-items-center me-3">
            <a class="d-flex align-items-center justify-content-center text-decoration-none text-dark" href="#"
                role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if (!empty($profileImage)): ?>
                    <img src="data:image/jpeg;base64,<?= htmlspecialchars($profileImage) ?>" alt="Profile Image"
                        class="profile-pic img-fluid rounded-circle me-2"
                        style="width: 40px; height: 40px; object-fit: cover;">
                <?php else: ?>
                    <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center me-2"
                        style="width: 40px; height: 40px;">
                        <h6 class="p-0 m-0">
                            <?= strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?>
                        </h6>
                    </div>
                <?php endif; ?>
                <i class="fa-solid fa-caret-down fs-6"></i>
            </a>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                <li><a class="dropdown-item"
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?php echo $employeeId ?>">Profile</a>
                </li>
                <?php if ($systemRole === "admin"): ?>
                    <li><a class="dropdown-item"
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-users.php">Manage
                            User Access</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item"
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/manage-credential.php?employee_id=<?php echo $employeeId ?>">Manage
                        Credential</a></li>

                <?php if ($systemRole === "admin"): ?>
                    <li class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle" href="#">Manage Form Options</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item"
                                    href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/FormOptions/manage-departments.php">Manage
                                    Department</a></li>
                            <li><a class="dropdown-item"
                                    href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/FormOptions/manage-visa.php">Manage
                                    Visa</a></li>
                            <li><a class="dropdown-item"
                                    href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/FormOptions/manage-position.php">Manage
                                    Position</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutModal">Logout</a></li>
            </ul>
        </div>
    </div>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="list-unstyled mt-3 text-center" id="list-menu">
            <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php"
                class="text-decoration-none">
                <li class="py-2 p-1 fw-bold text-dark" id="home-icon">
                    <i class="fa-solid fa-house"></i> <span class="folder-name ms-1"> Home </span>
                </li>
            </a>
            <?php foreach ($folders as $row): ?>
                <?php

                if (htmlspecialchars($row['folder_name']) == "Human Resources") {
                    $folder_page = "http://$serverAddress/$projectName/Pages/hr-index.php";
                } else if (htmlspecialchars($row['folder_name']) == "Quality Assurances") {
                    $folder_page = "http://$serverAddress/$projectName/Pages/qa-index.php";
                } else {
                    $folder_page = "http://$serverAddress/$projectName/Pages/index.php";
                }
                ?>

                <a href="<?php echo $folder_page ?>" class="text-decoration-none text-dark">
                    <li class="py-2 p-1 fw-bold d-flex justify-content-center align-items-center">
                        <span class="folder-name"><?= htmlspecialchars($row['folder_name']) ?></span>
                    </li>
                </a>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const documentTitle = document.title;

        const topMenuTitle = document.getElementById("top-menu-title");
        topMenuTitle.textContent = documentTitle;

        const topMenuTitleSmall = document.getElementById("top-menu-title-small");
        topMenuTitleSmall.textContent = documentTitle;
    })
</script>