<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once ("../db_connect.php");
require_once ("../status_check.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FUJI Directories</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="../Images/FE-logo-icon.ico" type="image/x-icon">
    <style>
        html,
        body {
            overflow-x: hidden;
            width: 100%;
            box-sizing: border-box;
            background-color: #eef3f9;
        }
    </style>
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
            <div class="container-fluid mt-4">
                <?php require ("../PageContent/home-index-content.php"); ?>
            </div>
        </div>
    </div>
    <?php require_once ("../logout.php") ?>

</body>

</html>