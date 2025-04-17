<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");
require_once("../status_check.php");

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
    <?php require_once("../Menu/NavBar.php") ?>
    <div class="container-fluid mt-4">
        <?php require("../PageContent/manage-credential-content.php"); ?>
    </div>

    <?php require_once("../logout.php") ?>
</body>