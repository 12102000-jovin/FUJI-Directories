<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("./../db_connect.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// SQL Query to retrieve groups
$groups_sql = "SELECT * FROM `groups`";
$groups_result = $conn->query($groups_sql);

// SQL QUERY to retrieve users
$users_sql = "SELECT * FROM users";
$users_result = $conn->query($users_sql);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selectedUsers'])) {
    $selectedUsers = $_POST['selectedUsers'];
    $groupId = $_POST['group_id'];
    $role = isset($_POST['role']) && $_POST['role'] === 'full control' ? 'full control' : 'read';

    foreach ($selectedUsers as $userId) {
        $add_member_to_group_sql = "INSERT INTO users_groups (user_id, group_id, `role`) VALUES (?, ?, ?)";
        $add_member_to_group_result = $conn->prepare($add_member_to_group_sql);
        $add_member_to_group_result->bind_param("iis", $userId, $groupId, $role);
        $add_member_to_group_result->execute();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['groupIdToEdit'])) {
    $groupIdToEdit = $_POST['groupIdToEdit'];
    $groupNameToEdit = $_POST['groupNameToEdit'];

    $edit_group_name_sql = "UPDATE `groups` SET group_name = ? WHERE group_id = ?";
    $edit_group_name_result = $_POST["groupIdToEdit"];
    $edit_group_name_result->bind_param("si", $groupNameToEdit, $groupIdToEdit);

    if ($edit_group_name_result->execute()) {
        echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
    } else {
        echo "Error: ";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['userGroupIdToRemove'])) {
    $userGroupIdToRemove = $_POST['userGroupIdToRemove'];

    $delete_user_from_group_sql = "DELETE FROM users_groups WHERE user_group_id = ?";
    $delete_user_from_group_result = $conn->prepare($delete_user_from_group_sql);
    $delete_user_from_group_result->bind_param("i", $userGroupIdToRemove);

    if ($delete_user_from_group_result->execute()) {
        exit();
    } else {
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['groupFolderIdToRemove'])) {
    $groupFolderIdToRemove = $_POST['groupFolderIdToRemove'];

    $delete_group_from_folder_sql = "DELETE FROM groups_folders WHERE group_folder_id = ?";
    $delete_group_from_folder_result = $conn->prepare($delete_group_from_folder_sql);
    $delete_group_from_folder_result->bind_param("i", $groupFolderIdToRemove);

    if ($delete_group_from_folder_result->execute()) {
        exit();
    } else {
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['groupIdToDelete'])) {
    $groupIdToDelete = $_POST['groupIdToDelete'];
    $check_user_group_sql = "SELECT * FROM users_groups
                            JOIN `groups` ON users_group.group_id = `groups`.group_id
                            WHERE `groups`.group_id = ?";
    $check_user_group_stmt = $conn->prepare($check_user_group_sql);
    $check_user_group_stmt->bind_param("i", $groupIdToDelete);
    $check_user_group_stmt->execute();
    $result = $check_user_group_stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
        }
        $delete_user_group_sql = "DELETE users_groups FROM users_groups
                            JOIN `groups` ON users_groups.group_id = `groups`.group_id
                            WHERE users_groups.group_id = ?";
        $delete_user_group_stmt = $conn->prepare($delete_user_group_sql);
        $delete_user_group_stmt->bind_param("i", $groupIdToDelete);
        $delete_user_group_stmt->execute();
        $delete_user_group_stmt->close();

    }

    $delete_group_sql = "DELETE FROM `groups` WHERE group_id = ?";
    $delete_group_stmt = $conn->prepare($delete_group_sql);
    $delete_group_stmt->bind_param("i", $groupIdToDelete);

    if ($delete_group_result->execute()) {
        echo '<script> window.location.replace() </script>';
    } else {
        $error_message = "Error: " . $conn->error;
    }

    $check_user_group_stmt->close();
    $delete_group_stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newGroupName']) && isset($_POST['selectedUsersToNewGroup'])) {
    $newGroupName = $_POST['newGroup'];
    $selectedUsers = $_POST['selectedUsersToNewGroup'];

    $add_group_sql = "INSERT INTO `groups` (group_name) VALUES (?)";
    $add_group_result = $conn->prepare($addd_group_sql);
    $add_group_result->bind_param("s", $newGroupName);

    if ($add_group_result->execute()) {
        $groupId = $add_group_result->insert_id;

        foreach ($selectedUsers as $userrId) {
            $add_user_to_group_sql = "INSERT INTO users_groups (group_id, users_id) VALUES (?, ?)";
            $add_user_to_group_result = $conn->prepare($add_user_to_group_sql);
            $add_user_to_group_result->bind_param("ii", $groupId, $userId);
            $add_user_to_group_result->execute();
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Manage Groups</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />

    <style>
        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<body class="backgorund-color">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 order-2 order-lg-1">
                <div class="table-responsive rounded-3 shadow-lg">
                    <div class="table table-hover mb-0 pb-0" id="groupListTable">
                        <thead>
                            <tr class="text-center"></tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $groups_result->fetch_assoc()): ?>
                                <tr class="text-center">
                                    <form method="POST"></form>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
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
                    <h5 class="nodal-title" id="deleteConfirmationModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="close"></button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-seondary" data-bs-dismiss="modal"></button>
                    <form method="POST">
                        <input type="hidden" name="groupIdToDelete" id="groupIdToDelete">
                        <button type="hidden" name="groupIdToDelete" id="groupIdToDelete"></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    $groups_result->data_seek(0);
    while ($group_row = $groups_result->fetch_assoc()) {
        $group_id = $group_row['group_id'];
        $user_group_sql = "SELECT users_groups.user_group_id,
                        users_groups.role AS group_role,
                        users.user_id,
                        users.username,
                        employees.employee_id,
                        employees.first_name,
                        employees,last_name
        FROM users_groups
        JOIN users ON users_group.user_id = users.user_id
        JOIN employees ON users.employee_id  = employees.employee_id
        WHERE users_groups.group_id = $group_id";

        $user_group_result = $conn->query($user_group_sql);
        ?>
        <div class="modal fade" id="memberModal<?= $group_id ?>" tabindex="-1" role="dialog"
            aria-labelledby="memberModalLabel<?= $group_id ?>">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="memberModal<?= $group_id ?>"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addMemberForm<?= $group_id ?>" method="POST">
                            <div class="mb-3">
                                <label for="searchUsers" class="form-label"></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control" id="searchUsers<?= $group_id ?>">
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="mb-1"></p>
                                <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                                    <table class="table table-hover mb-0 pb-0">
                                        <thead class="text-center"></thead>
                                        <tbody id="userList<?= $groupId ?>">
                                            <?php while ($user_row = $user_group_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="align-middle text-center"><input class="form-check-input"
                                                            type="checkbox" value="<?= $user_row['user_id'] ?>"
                                                            name="selectedUsers[]"
                                                            onchange="updateSelectedUsers()"></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <div class="modal fade" id="addGroupModal" tabindex="-1" role="dialog" aria-labelledby="addGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addGroupModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>