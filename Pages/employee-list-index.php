<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");
require_once("../status_check.php");
require_once("../system_role_check.php");

$folder_name = "Human Resources";

require_once("../group_role_check.php");

?>

<style>
    html,
    body {
        overflow-x: hidden;
        width: 100%;
        box-sizing: border-box;
        background-color: #eef3f9;
    }
</style>

<head>
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
</head>

<body>
    <?php require_once("../Menu/Navbar.php") ?>
    <div class="container-fluid mt-4 mb-4">
        <?php require("../Lists/AllEmployeeList.php"); ?>
    </div>
    <?php require_once("../logout.php") ?>

    <script>
        // Restore scroll position after page reload
        window.addEventListener('load', function () {
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, scrollPosition);
                sessionStorage.removeItem('scrollPosition'); // Remove after restoring
            }
        });
    </script>

</body>