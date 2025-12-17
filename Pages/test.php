<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("../db_connect.php");
$config = include('../config.php');

if ($_SERVER["RQUEST_METHOD"] === "POST" && isset($_POST['folderNameToEdit'])) {
    $folderNameToEdit = $_POST['folderNameToEdit'];
    $folderIdToEdit = $_POST['folderIdToEdit'];
    $edit_folder_name_sql = "UPDATE folders SET folder_name = ? WHERE folder_id = ?";
    if ($edit_folder_name_result->execute()) {
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['folderIdToDelete'])) {
    $folderIdToDelete = $_POST['folderIdToDelete'];
    $delete_folder_sql = "DELETE FROM folders WHERE folder_id = ?";
    $delete_folder_stmt = $conn->prepare($delete_folder_sql);
    $delete_folder_stmt->bind_param("i", $folderIdToDelete);

    if ($delete_folder_stmt->execute()) {
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newFolderName'])) {
    $newFolderName = $_POST['newFolderName'];
    $add_folder_sql = "INSERT INTO folders (folder_name) VALUES(?)";
    $add_folder_result = $conn->prepare($add_folder_sql);
    $add_folder_result->bind_param("s", $newFolderName);

    if ($add_folder_result->execute()) {
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['groupFolderIdToRemove'])) {
    $groupFolderIdToRemove = $_POST['groupFolderIdToRemove'];
    $delete_group_from_folder_sql = "DELETE FROM groups_folders WHERE group_folder_id = ?";
    $delete_group_from_folder_result = $conn->prepare($delete_group_from_folder_sql);
    $delete_group_from_folder_result->bind_param("i", $groupFolderIdToRemove);

    if ($delete_group_from_folder_result->execute()) {
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Manage Folders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico">

    <style>
        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<body class="background-color">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 order-2 order-lg-1">
                <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                    <table class="table table-hover mb-0 pb-0">
                        <thead>
                            <tr class="text-center"></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-2 order-1 order-lg-2"></div>
        </div>
        <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog"
            aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>