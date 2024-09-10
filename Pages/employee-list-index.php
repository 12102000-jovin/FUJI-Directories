<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once ("../db_connect.php");
require_once ("../status_check.php");
require_once ("../system_role_check.php");

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
    <div class="row">
        <div class="col-auto pe-0 d-none d-md-block sidebar">
            <?php require ("../Menu/SideNavMenu/SideMenu.php") ?>
        </div>
        <div class="col p-0">
            <div class="sticky-top-menu">
                <?php require ("../Menu/SideNavMenu/TopMenu.php") ?>
            </div>
            <div class="container-fluid mt-4 mb-4">
                <?php require ("../Lists/EmployeeList.php"); ?>
            </div>
        </div>
    </div>
    <?php require_once ("../logout.php") ?>
</body>