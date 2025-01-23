<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("./../db_connect.php");
require_once("./../status_check.php");
require_once("../system_role_check.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrieve session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

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

<div class="sticky-top-menu">
    <div class="bg-white">
        <div class="d-flex align-items-center justify-content-between ms-2 me-4 ps-md-0 ps-3">
            <div class="d-flex align-items-center d-md-none">
                <button class="navbar-toggler btn mx-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa fa-bars fa-lg"></i>
                </button>
                <img src="../Images/FE-logo.png" class="ms-2 py-3" style="width:3rem">
                <div class="vr mx-2 my-3"></div>
                <h5 id="top-menu-title-small" style="color: #043f9d;" class="m-0 fw-bold"></h5>
            </div>
            <h5 class="m-0 fw-bold d-none d-md-flex py-3" style="color: #043f9d;" id="top-menu-title"></h5>
            <a class="d-flex align-items-center justify-content-center text-decoration-none text-dark" href="#"
                role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="me-2 fw-bold d-none d-md-flex"><?= htmlspecialchars($firstName . " " . $lastName) ?></span>
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
                            User Access</a>
                    </li>
                    <li><a class="dropdown-item"
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/manage-allowances.php">Manage
                            Allowances</a></li>
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
                            <li><a class="dropdown-item"
                                    href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/FormOptions/manage-location.php">Manage
                                    Location</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutModal">Logout</a></li>
            </ul>
        </div>
        <div class="collapse navbar-collapse d-md-none" id="navbarSupportedContent">
            <ul class="list-unstyled mt-3 text-center" id="list-menu">
                <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php"
                    class="text-decoration-none">
                    <li class="mx-2 py-2 p-1 rounded fw-bold text-dark" id="home-icon">
                        <i class="fa-solid fa-house"></i> <span class="folder-name ms-1"> Home </span>
                    </li>
                </a>
                <?php foreach ($folders as $row): ?>
                    <?php
                    $initials = implode('', array_map(fn($word) => strtoupper($word[0]), explode(' ', $row['folder_name'])));
                    if (htmlspecialchars($row['folder_name']) == "Human Resources") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/employee-list-index.php";
                    } else if (htmlspecialchars($row['folder_name']) == "Quality Assurances") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/qa-index.php";
                    } else if (htmlspecialchars($row['folder_name']) == "Project") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/project-table.php";
                    } else if (htmlspecialchars($row['folder_name']) == "Work Health and Safety") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/whs-table.php";
                    } else if (htmlspecialchars($row['folder_name']) == "Asset") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/asset-index.php";
                    } else {
                        $folder_page = "http://$serverAddress/$projectName/Pages/index.php";
                    }
                    ?>
                    <a href="<?php echo $folder_page ?>" class="text-decoration-none text-dark">
                        <li class="mx-2 py-2 p-1 rounded fw-bold d-flex justify-content-center align-items-center">
                            <span class="folder-name"><?= htmlspecialchars($row['folder_name']) ?></span>
                        </li>
                    </a>
                <?php endforeach; ?>
            </ul>
        </div>
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