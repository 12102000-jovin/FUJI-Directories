<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once("../db_connect.php");

$config = include('../db_connect.php');

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// SQL Query to retrieve folders
$folders_sql = "SELECT * FROM folders";
$folders_result = $conn->query($folders_sql);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selectedGroups'])) {
    $selectedGroups = $_POST['selectedGroups'];
    $folderId = $_POST['folder_id'];

    // Iterate over the array of the selected group IDs
    foreach ($selectedGroups as $groupId) {
        // Query to add group to folder
        $add_group_to_folder_sql = "INSERT INTO groups_folders (group_id, folder_id) VALUES (?, ?)";
        $add_group_to_folder_result = $conn->prepare($add_group_to_folder_sql);
        $add_group_to_folder_result->bind_param("ii", $groupId, $folderId);
        $add_group_to_folder_result->execute();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['folderNameToEdit'])) {
    $folderNameToEdit = $_POST['folderNameToEdit'];
    $folderIdToEdit = $_POST['folderIdToEdit'];

    // Query to edit folder name
    $edit_folder_name_sql = "UPDATE folders SET folder_name = ? WHERE folder_id = ?";
    $edit_folder_name_result = $conn->prepare($edit_folder_name_sql);
    $edit_folder_name_result->bind_param("si", $folderNameToEdit, $folderIdToEdit);

    // Execute the prepared statement
    if ($edit_folder_name_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
        exit();
    } else {
        echo "Error: " . $edit_folder_name_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['folderIdToDelete'])) {
    $folderIdToDelete = $_POST['folderIdToDelete'];

    // SQL to delete folder from folders table
    $delete_folder_sql = "DELETE FROM folders WHERE folder_id = ?";
    $delete_folder_stmt = $conn->prepare($delete_folder_sql);
    $delete_folder_stmt->bind_param("i", $folderIdToDelete);

    // Execute the prepared statement
    if ($delete_folder_stmt->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
        exit();
    } else {
        $error_message = "Error: " . $conn->error;
        echo "<div class='alert alert-danger'> $error_message </div>";
    }

    // Close the statements
    $delete_folder_stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newFolderName'])) {
    $newFolderName = $_POST['newFolderName'];

    // SQL to add folder to the folders table 
    $add_folder_sql = "INSERT INTO folders (folder_name) VALUES (?)";
    $add_folder_result = $conn->prepare($add_folder_sql);
    $add_folder_result->bind_param("s", $newFolderName);

    // Execute the prepared statement
    if ($add_folder_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '"); </script>';
        exit();
    } else {
        $error_message = "Error: " . $add_folder_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['groupFolderIdToRemove'])) {
    $groupFolderIdToRemove = $_POST['groupFolderIdToRemove'];

    echo $groupFolderIdToRemove;

    // Query to remove group from folder
    $delete_group_from_folder_sql = "DELETE FROM groups_folders WHERE group_folder_id = ?";
    $delete_group_from_folder_result = $conn->prepare($delete_group_from_folder_sql);
    $delete_group_from_folder_result->bind_param("i", $groupFolderIdToRemove);

    // Execute the prepared statement
    if ($delete_group_from_folder_result->execute()) {
        echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $delete_group_from_folder_result . "<br>" . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html>

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
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                </li>
                <li class="breadcrumb-item fw-bold signature-color">Manage Folders</li>
            </ol>
        </nav>
        <div class="row">
            <div class="col-lg-10 order-2 order-lg-1">
                <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                    <table class="table table-hover mb-0 pb-0">
                        <thead>
                            <tr class="text-center">
                                <th class="py-4 align-middle col-md-6"> Folder</th>
                                <th class="py-4 align-middle col-md-6"> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $folders_result->fetch_assoc()): ?>
                                <tr class="text-center">
                                    <form method="POST">
                                        <td class="py-4 align-middle text-center">
                                            <span class="view-mode"><?= $row['folder_name'] ?></span>
                                            <input type="hidden" name="folderIdToEdit" value="<?= $row['folder_id'] ?>" />
                                            <input type="text" class="form-control edit-mode d-none mx-auto"
                                                name="folderNameToEdit" value="<?= $row['folder_name'] ?>"
                                                style="width:80%">
                                        </td>

                                        <div class="py-4 align-middle text-center">
                                            <button class="btn text-warning view-mode" type="button" data-bs-toggle="modal"
                                                data-bs-target="#groupModal<?= $row['folder_id'] ?>">
                                                <i class="fa-solid fa-folder tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Folder Access"></i>
                                            </button>
                                            <button class="btn text-info view-mode" type="button" data-bs-toggle="modal"
                                                data-bs-target="#addGroupToFolderModal<?= $row['folder_id'] ?>">
                                                <i class="fa-solid fa-plus tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Add Group Access"></i>
                                            </button>

                                            <button class="btn edit-btn text-primary view-mode" type="button">
                                                <i class="fa-regular fa-pen-to-square m-1 tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Edit Folder"></i>
                                            </button>

                                            <button class="btn text-danger view-mode" id="deleteFolderBtn" type="button"
                                                data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                data-bs-target="#deleteConfirmationModal"
                                                data-folder-id="<?= $row['folder_id'] ?>">
                                                <i class="fa-solid fa-trash-can m-1 tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Delete Group"></i>
                                            </button>

                                            <div class="edit-mode d-none d-flex justify-content-center">
                                                <button type="submit" class="btn btn-sm px-2 btn-success mx-1">
                                                    <div class="d-flex justify-content-center"><i role="button"
                                                            class="fa-solid fa-check text-white m-1"></i>Edit</div>
                                                </button>
                                                <button type="button" class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                    <div class="d-flex justify-content-center"><i role="button"
                                                            class="fa-solid fa-xmark text-white m-1"></i>Cancel</div>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-2 order-1 order-lg-2">
                <div class="d-none d-lg-block">
                    <div class="bg-light d-flex justify-content-center flex-column">
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-users.php"
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12 mt-3"><i
                                class="fa-solid fa-user fa-3x signature-color"></i><span
                                class="mt-2 signature-color fw-bold"> Manage Users </span></a>
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-groups.php"
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12 mt-3"><i
                                class="fa-solid fa-user-group fa-3x text-dark"></i><span class="mt-2 text-dark fw-bold">
                                Manage Groups </span></a>
                    </div>
                </div>
                <div class="col-12 d-lg-none bg-white rounded-3 p-4 mb-4 shadow-lg">
                    <div class="row">
                        <div class="col-12 col-lg-none bg-white rounded-3 p-4 mb-4 shadow-lg">
                            <div class="row">
                                <div class="col-12 col-md-4 mb-3 mb-md-0">
                                    <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-users.php"
                                        class="btn signature-btn p-3 w-100">
                                        <i class="fa-solid fa-user me-1 fa-lg signature-color"></i>
                                        <span class="signature-color fw-bold">Manage Users</span>
                                    </a>
                                </div>
                                <div class="col-12 col-md-4">
                                    <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-groups.php"
                                        class="btn signature-btn p-3 w-100">
                                        <i class="fa-solid fa-user-group me-1 fa-lg text-dark"></i>
                                        <span class="text-dark fw-bold">Manage Groups</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog"
                aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this user?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="POST">
                                <input type="hidden" name="folderIdToDelete" id="folderIdToDelete">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Folder Modal -->
            <div class="modal fade" id="addFolderModal" tabindex="-1" role="dialog"
                aria-labelledby="addFolderGroupModalLabel" aria-hidden="true">
                <form method="POST">
                    <div class="modal-dialog">
                        <div class="moda-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Folder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="form-group col-md-12 mt-2">
                                        <label for="newFolderName" class="fw-bold">Folder Name</label>
                                        <input type="text" name="newFolderName" class="form-control" id="newFolderName"> 
                                    </div>
                                    <div class="form-group col-md-12 mt-2">
                                        <label for="newFolderPathName" class="fw-bold">Folder Path</label>
                                        <input type="text" name="newFolderPathName" class="form-control" id="newFolderPathName ">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>