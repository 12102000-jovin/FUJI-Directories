<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("./../db_connect.php");
require_once("./../status_check.php");
require_once("../system_role_check.php");

$systemRole = $_SESSION['systemRole'];

if ($systemRole != "admin") {
    echo "<script>
    window.location.href = 'http://$serverAddress/$projectName/access_restricted.php';
  </script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Location</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="../Images/FE-logo-icon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../style.css">
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
    <?php require_once("../Menu/NavBar.php") ?>
    <div class="container-fluid mt-3">
        <?php require("./../PageContent/ManageFormOptionsContent/manage-location-content.php"); ?>
    </div>
    <?php require_once("./../logout.php") ?>

</body>

</html>