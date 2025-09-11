<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");
require_once("../status_check.php");
require_once("../system_role_check.php"); 

$config = include('../config.php');
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
    $folders[] = $row["folder_name"];
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
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="./style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
    <style>
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

        .abbreviation {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            /* Adjust as needed */
        }

        a.abbreviation {
            padding: 5px 10px;
            transition: background-color 0.3s ease;
        }

        a.abbreviation:hover {
            background-color: #fec108;
            /* Change to any color you prefer */
            color: white !important;
        }

        /* Disabled button styling */
        a.disabled {
            background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px);
            /* Light gray background */
            color: white !important;
            /* Light gray text */
            pointer-events: none;
            /* Prevent clicks on the disabled button */
            cursor: not-allowed;
            /* Change cursor to not-allowed */
        }

        /* Optional: Hover effect for disabled buttons */
        a.disabled:hover {
            background-color: #f0f0f0;
            color: #b0b0b0;
        }

        .list-unstyled li:hover {
            background-color: #043f9d;
            color: white !important;
        }
    </style>
</head>

<body class="background-color">
    <div class="bg-white shadow-lg">
        <div class="d-flex align-items-center justify-content-between">
            <div class="container-fluid d-flex align-items-center">
                <button class="navbar-toggler btn mx-2 d-md-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa fa-bars fa-lg"></i>
                </button>
                <img src="./../Images/PowerFusionFullLogo.png" class="ms-2 py-3" style="width:8rem">
                <div class="vr mx-2 my-3"></div>
                <h5 id="top-menu-title" style="color: #043f9d;" class="m-0 fw-bold"></h5>
            </div>
            <div class="d-flex align-items-center me-3">
                <a class="d-flex align-items-center justify-content-center text-decoration-none text-dark" href="#"
                    role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-md-flex d-none align-items-center justify-content-end" style="min-width:240px">
                        <span class="me-1 fw-bold d-flex"><?= htmlspecialchars($firstName) ?></span>
                        <span class="me-2 fw-bold d-flex"><?= htmlspecialchars($lastName) ?></span>
                    </div>
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
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink">
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
        </div>
        <div class="d-none d-md-block">
            <div class="d-flex justify-content-center signature-bg-color">
            <a href="<?php echo 'http://' . $serverAddress . '/' . $projectName . '/Pages/index.php'; ?>"
                class="col py-2 text-decoration-none text-white text-center fw-bold border-end abbreviation"
                style="cursor:pointer">
                <i class="fa-solid fa-house me-1 mb-1"></i> Home
            </a>

                <?php
                $folders_abbr = [
                    'AC' => 'Accounts',
                    'AS' => 'Asset',
                    'CAPA' => 'Corrective and Preventive Actions',
                    'EL' => 'Electrical',
                    'EN' => 'Engineering',
                    'ES' => 'Estimating',
                    'HR' => 'Human Resources',
                    'OS' => 'Operations Support',
                    'PJ' => 'Project',
                    'QA' => 'Quality Assurances',
                    'QC' => 'Quality Control',
                    'R&D' => 'Research and Development',
                    'SM' => 'Sheet Metal',
                    'T&T' => 'Test and Tag',
                    'WHS' => 'Work Health and Safety'
                ];

                // Loop through each abbreviation and check if it's in the list of available folders
                foreach ($folders_abbr as $abbr => $full_name) {
                    $folder_exists = in_array($full_name, $folders) || in_array($abbr, $folders);

                    $isDisabled = $folder_exists ? '' : 'disabled';

                    if (htmlspecialchars($abbr) == "AS") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/asset-table.php";
                    } else if (htmlspecialchars($abbr) == "CAPA") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/capa-table.php";
                    } else if (htmlspecialchars($abbr) == "HR") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/employee-list-index.php?status%5B%5D=1&apply_filters=";
                    }  else if (htmlspecialchars($abbr) == "PJ") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/project-table.php";
                    } else if (htmlspecialchars($abbr) == "QA") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/qa-table.php";
                    } else if ($abbr == "T&T") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/cable-table.php";
                    } else if ($abbr == "WHS") {
                        $folder_page = "http://$serverAddress/$projectName/Pages/whs-table.php";
                    } else {
                        $folder_page = "http://$serverAddress/$projectName/Pages/index.php";
                    }
                    ?>
                    <a href="<?php echo $folder_page ?>"
                        class="col py-2 text-decoration-none text-white text-center fw-bold border-end abbreviation <?php echo $isDisabled; ?>"
                        style="cursor:pointer">
                        <span class="short m-0 p-0"><?php echo $abbr; ?></span>
                        <span class="long m-0 p-0 d-none" style="font-size: 12px"><?php echo $full_name; ?></span>
                    </a>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="collapse navbar-collapse text-center d-md-none" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto list-unstyled">
                <?php foreach ($folders as $folder_name): ?>
                    <a href="<?php 
                          if (htmlspecialchars($folder_name) == "Asset") {
                            echo "http://$serverAddress/$projectName/Pages/asset-table.php";
                        } else if (htmlspecialchars($folder_name) == "CAPA") {
                            echo "http://$serverAddress/$projectName/Pages/capa-table.php";
                        } else if (htmlspecialchars($folder_name) == "Human Resources") {
                            echo "http://$serverAddress/$projectName/Pages/employee-list-index.php";
                        }  else if (htmlspecialchars($folder_name) == "Project") {
                            echo "http://$serverAddress/$projectName/Pages/project-table.php";
                        } else if (htmlspecialchars($folder_name) == "Quality Assurances") {
                            echo "http://$serverAddress/$projectName/Pages/qa-table.php";
                        } else if ($folder_name == "Test and Tag") {
                            echo "http://$serverAddress/$projectName/Pages/cable-table.php";
                        } else if ($folder_name == "Work Health and Safety") {
                            echo "http://$serverAddress/$projectName/Pages/whs-table.php";
                        } else {
                            echo "http://$serverAddress/$projectName/Pages/index.php";
                        }
                        ?>" class="text-decoration-none text-dark">
                        <li class=" py-2 p-1 fw-bold d-flex justify-content-center align-items-center">
                            <span class="folder-name"><?= htmlspecialchars($folder_name) ?></span>
                        </li>
                    </a>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Get all elements with the 'abbreviation' class
        const abbreviationItems = document.querySelectorAll('.abbreviation');

        abbreviationItems.forEach(item => {
            const short = item.querySelector('.short');
            const long = item.querySelector('.long');

            // Handle mouseover to show the full text
            item.addEventListener("mouseover", function () {
                long.classList.remove("d-none");
                short.classList.add("d-none");
            });

            // Handle mouseout to show the abbreviated text
            item.addEventListener("mouseout", function () {
                long.classList.add("d-none");
                short.classList.remove("d-none");
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const documentTitle = document.title;
        const topMenuTitle = document.getElementById("top-menu-title");
        topMenuTitle.textContent = documentTitle;
    })
</script>


