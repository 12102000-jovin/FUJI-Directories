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

    // Iterate over the array of selected user IDs
    foreach ($selectedUsers as $userId) {
        // Query to add users to group 
        $add_member_to_group_sql = "INSERT INTO users_groups (user_id, group_id, `role`) VALUES (?, ?, ?)";
        $add_member_to_group_result = $conn->prepare($add_member_to_group_sql);
        $add_member_to_group_result->bind_param("iis", $userId, $groupId, $role);
        $add_member_to_group_result->execute();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['groupIdToEdit'])) {
    $groupIdToEdit = $_POST['groupIdToEdit'];
    $groupNameToEdit = $_POST['groupNameToEdit'];

    echo $groupIdToEdit . " " . $groupNameToEdit;

    // Query to edit group name 
    $edit_group_name_sql = "UPDATE `groups` SET group_name = ? WHERE group_id = ?";
    $edit_group_name_result = $conn->prepare($edit_group_name_sql);
    $edit_group_name_result->bind_param("si", $groupNameToEdit, $groupIdToEdit);

    // Execute the prepared statement 
    if ($edit_group_name_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $edit_group_name_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['userGroupIdToRemove'])) {
    $userGroupIdToRemove = $_POST['userGroupIdToRemove'];

    echo $userGroupIdToRemove;

    // Query to remove employee from the group
    $delete_user_from_group_sql = "DELETE FROM users_groups WHERE user_group_id = ?";
    $delete_user_from_group_result = $conn->prepare($delete_user_from_group_sql);
    $delete_user_from_group_result->bind_param("i", $userGroupIdToRemove);

    // Execute the prepared statement
    if ($delete_user_from_group_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $delete_user_from_group_result . "<br>" . $conn->error;
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
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $delete_group_from_folder_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['groupIdToDelete'])) {
    $groupIdToDelete = $_POST['groupIdToDelete'];

    // Check if the group has any memeber
    $check_user_group_sql = "SELECT * FROM users_groups 
                            JOIN `groups` ON users_groups.group_id = `groups`.group_id 
                            WHERE `groups`.group_id = ?";
    $check_user_group_stmt = $conn->prepare($check_user_group_sql);
    $check_user_group_stmt->bind_param("i", $groupIdToDelete);
    $check_user_group_stmt->execute();
    $result = $check_user_group_stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "User ID: " . htmlspecialchars($row['user_id']) . "<br>";
            echo "User Group ID: " . htmlspecialchars($row['user_group_id']) . "<br>";
            echo "Group ID: " . htmlspecialchars($row['group_id']) . "<br>";
        }

        // Delete groups from users_groups 
        $delete_user_group_sql = "DELETE users_groups FROM users_groups
                                JOIN `groups` ON users_groups.group_id = `groups`.group_id
                                WHERE users_groups.group_id = ?";
        $delete_user_group_stmt = $conn->prepare($delete_user_group_sql);
        $delete_user_group_stmt->bind_param("i", $groupIdToDelete);
        $delete_user_group_stmt->execute();
        $delete_user_group_stmt->close();
    }

    // Delete group from groups table
    $delete_group_sql = "DELETE FROM `groups` WHERE group_id = ?";
    $delete_group_stmt = $conn->prepare($delete_group_sql);
    $delete_group_stmt->bind_param("i", $groupIdToDelete);

    // Execute the prepared statement
    if ($delete_group_stmt->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        $error_message = "Error: " . $conn->error;
        echo "<div class='alert alert-danger'>$error_message</div>";
    }

    // Close the statements
    $check_user_group_stmt->close();
    $delete_group_stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newGroupName']) && isset($_POST['selectedUsersToNewGroup'])) {
    $newGroupName = $_POST['newGroupName'];
    $selectedUsers = $_POST['selectedUsersToNewGroup']; // Change the name here

    // SQL to add group to the groups table
    $add_group_sql = "INSERT INTO `groups` (group_name) VALUES (?)";
    $add_group_result = $conn->prepare($add_group_sql);
    $add_group_result->bind_param("s", $newGroupName);

    // Execute the prepared statement
    if ($add_group_result->execute()) {
        // Get the newly created groupId
        $groupId = $add_group_result->insert_id;

        // Insert selected users into group_users table
        foreach ($selectedUsers as $userId) {
            $add_user_to_group_sql = "INSERT INTO users_groups (group_id, user_id) VALUES (?, ?)";
            $add_user_to_group_result = $conn->prepare($add_user_to_group_sql);
            $add_user_to_group_result->bind_param("ii", $groupId, $userId);
            $add_user_to_group_result->execute();
        }

        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $add_group_result->error;
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

<body class="background-color">
    <div class="container-fluid">
        <!-- <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                </li>
                <li class="breadcrumb-item fw-bold signature-color">Manage Groups</li>
            </ol>
        </nav> -->
        <div class="row">
            <div class="col-lg-10 order-2 order-lg-1">
                <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                    <table class="table table-hover mb-0 pb-0" id="groupListTable">
                        <thead>
                            <tr class="text-center">
                                <th class="py-4 align-middle col-md-6">Group</th>
                                <th class="py-4 align-middle col-md-6">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $groups_result->fetch_assoc()): ?>
                                <tr class="text-center">
                                    <form method="POST">
                                        <td class="py-4 align-middle text-center">
                                            <span class=" view-mode"><?= $row['group_name'] ?></span>
                                            <input type="hidden" name="groupIdToEdit" value="<?= $row['group_id'] ?>" />
                                            <input type="text" class="form-control edit-mode d-none mx-auto"
                                                name="groupNameToEdit" value="<?= $row['group_name'] ?>" style="width:80%">
                                        </td>
                                        <td class="py-4 align-middle text-center">
                                            <button class="btn text-warning view-mode" type="button" data-bs-toggle="modal"
                                                data-bs-target="#folderModal<?= $row['group_id'] ?>">
                                                <i class="fa-solid fa-folder tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Folder Access"></i>
                                            </button>
                                            <button class="btn viewMemberBtn text-success view-mode" type="button"
                                                data-bs-toggle="modal" data-bs-target="#memberModal<?= $row['group_id'] ?>">
                                                <i class="fa-solid fa-user-group tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Group Member"></i>
                                            </button>
                                            <button class="btn text-info view-mode" type="button" data-bs-toggle="modal"
                                                data-bs-target="#addMemberToGroupModal<?= $row['group_id'] ?>">
                                                <i class="fa-solid fa-plus tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Add Member"></i>
                                            </button>
                                            <button class="btn edit-btn text-primary view-mode" type="button">
                                                <i class=" fa-regular fa-pen-to-square m-1 tooltips"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Group"></i>
                                            </button>
                                            <button class="btn text-danger view-mode" id="deleteGroupBtn" type="buttossn"
                                                data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                data-group-id="<?= $row['group_id'] ?>">
                                                <i class="fa-solid fa-trash-can  m-1 tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Delete Group"></i>
                                            </button>
                                            <div class="edit-mode d-none d-flex justify-content-center">
                                                <button type="submit" class="btn btn-sm px-2 btn-success mx-1">
                                                    <div class="d-flex justify-content-center"><i role="button"
                                                            class="fa-solid fa-check text-white m-1"></i> Edit </div>
                                                </button>
                                                <button type="button" class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                    <div class="d-flex justify-content-center"> <i role="button"
                                                            class="fa-solid fa-xmark  text-white m-1"></i>Cancel </div>
                                                </button>
                                            </div>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-2 order-1 order-lg-2">
                <div class="d-none d-lg-block">
                    <div
                        class="bg-light d-flex justify-content-center flex-column align-items-center rounded-3 p-4 shadow-lg">
                        <button
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12"
                            id="addGroupModalBtn" data-bs-toggle="modal" data-bs-target="#addGroupModal"><span
                                class="d-flex align-items-center"><i
                                    class="fa-solid fa-user-group fa-3x text-dark"></i><i
                                    class="fa-solid fa-bold fa-plus text-dark fa-xl"></i></span><span
                                class="mt-2 text-dark fw-bold"> Add New Group </span></button>
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-users.php"
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12 mt-3"><i
                                class="fa-solid fa-user fa-3x signature-color"></i><span
                                class="mt-2 signature-color fw-bold"> Manage Users </span></a>
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-folders.php"
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12 mt-3"><i
                                class="fa-solid fa-folder fa-3x text-warning"></i><span
                                class="mt-2 text-warning fw-bold"> Manage Folders </span></a>
                    </div>
                </div>
                <div class="col-12 d-lg-none bg-white rounded-3 p-4 mb-4 shadow-lg">
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <button class="btn signature-btn p-3 w-100" id="addGroupModalBtnMobile"
                                data-bs-toggle="modal" data-bs-target="#addGroupModal">
                                <i class="fa-solid fa-user-group fa-lg text-dark"></i><i
                                    class="fa-solid fa-plus text-dark fa-2xs fw-bold"></i>
                                <span class="text-dark fw-bold">Add New Group</span>
                            </button>
                        </div>
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-users.php"
                                class="btn signature-btn p-3 w-100">
                                <i class="fa-solid fa-user me-1 fa-lg signature-color"></i>
                                <span class="signature-color fw-bold">Manage Users</span>
                            </a>
                        </div>
                        <div class="col-12 col-md-4">
                            <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-folders.php"
                                class="btn signature-btn p-3 w-100">
                                <i class="fa-solid fa-folder me-1 fa-lg text-warning"></i>
                                <span class="text-warning fw-bold">Manage Folders</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog"
        aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this group?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST">
                        <input type="hidden" name="groupIdToDelete" id="groupIdToDelete" />
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Show Member Modal -->
    <?php
    // Fetching users for each group
    $groups_result->data_seek(0);
    while ($group_row = $groups_result->fetch_assoc()) {
        $group_id = $group_row['group_id'];
        $user_group_sql = "SELECT users_groups.user_group_id,
            users_groups.role AS group_role,
            users.user_id,
            users.username,
            employees.employee_id,
            employees.first_name,
            employees.last_name
    FROM users_groups 
    JOIN users ON users_groups.user_id = users.user_id
    JOIN employees ON users.employee_id = employees.employee_id 
    WHERE users_groups.group_id = $group_id
    ";
        $user_group_result = $conn->query($user_group_sql);
        ?>
        <!-- Member Modal for each group -->
        <div class="modal fade" id="memberModal<?= $group_id ?>" tabindex="-1" role="dialog"
            aria-labelledby="memberModalLabel<?= $group_id ?>" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="memberModalLabel<?= $group_id ?>">Group Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive rounded-3 shadow-lg bg-light m-3">
                            <table class="table table-hover mb-0 pb-0">
                                <thead class="text-center">
                                    <tr>
                                        <th class="py-4 align-middle">Employee ID</th>
                                        <th class="py-4 align-middle">Name</th>
                                        <th class="py-4 align-middle">Access Level</th>
                                        <th class="py-4 align-middle">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user_row = $user_group_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="py-4 align-middle text-center"><?= $user_row['employee_id'] ?></td>
                                            <td class="py-4 align-middle text-center">
                                                <?= $user_row['first_name'] . " " . $user_row['last_name'] ?>
                                            </td>
                                            <td class="py-4 align-middle text-center">
                                                <?php
                                                $roleClass = match ($user_row['group_role']) {
                                                    'full control' => 'text-bg-danger text-capitalize',
                                                    'read' => 'text-bg-success text-capitalize',
                                                    'modify 1' => 'bg-warning text-capitalize',
                                                    'modify 2' => 'text-bg-primary text-capitalize',
                                                    default => 'text-bg-secondary text-capitalize',
                                                };
                                                ?>
                                                <span class="badge <?= $roleClass ?>">
                                                    <?= htmlspecialchars($user_row['group_role']) ?>
                                                </span>
                                            </td>

                                            <td class="py-4 align-middle text-center">
                                                <form method="POST">
                                                    <input type="hidden" name="userGroupIdToRemove"
                                                        value=<?= $user_row['user_group_id'] ?> />
                                                    <button class="btn btn-danger btn-sm"> Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <!-- Add Member to Group Modal -->
    <?php
    // Fetching groups
    $groups_result->data_seek(0);
    while ($group_row = $groups_result->fetch_assoc()) {
        $group_id = $group_row['group_id'];

        // Fetching users for each group
        $user_group_sql = "SELECT users.user_id, employees.employee_id, employees.first_name, employees.last_name
                           FROM users
                           JOIN employees ON users.employee_id = employees.employee_id
                           LEFT JOIN users_groups ON users.user_id = users_groups.user_id AND users_groups.group_id = $group_id
                           WHERE users_groups.group_id IS NULL";
        $user_group_result = $conn->query($user_group_sql);
        ?>
        <!-- Add Member to Group Modal for each group -->
        <div class="modal fade" id="addMemberToGroupModal<?= $group_id ?>" tabindex="-1" role="dialog"
            aria-labelledby="addMemberToGroupModalLabel<?= $group_id ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Member to Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addMemberForm<?= $group_id ?>" method="POST">
                            <!-- Hidden input field to store group_id -->
                            <input type="hidden" name="group_id" value="<?= $group_id ?>">
                            <!-- Hidden input field to store selected user IDs -->
                            <input type="hidden" name="selectedUsers" id="selectedUsers<?= $group_id ?>">

                            <div class="mb-3">
                                <label for="searchUsers" class="form-label">Search Users</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control" id="searchUsers<?= $group_id ?>"
                                        name="searchUsers" placeholder="Search Users">
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="mb-1">Users</p>
                                <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                                    <table class="table table-hover mb-0 pb-0">
                                        <thead class="text-center">
                                            <tr>
                                                <th class="py-3 align-middle"> Select</th>
                                                <th class="py-3 align-middle">Employee ID</th>
                                                <th class="py-3 align-middle">Name</th>
                                            </tr>
                                        </thead>
                                        <tbody id="userList<?= $group_id ?>">
                                            <?php while ($user_row = $user_group_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="align-middle text-center"><input class="form-check-input"
                                                            type="checkbox" value="<?= $user_row['user_id'] ?>"
                                                            name="selectedUsers[]"
                                                            onchange="updateSelectedUsers(<?= $group_id ?>, this)"></td>
                                                    <td class="py-4 align-middle text-center"><?= $user_row['employee_id'] ?>
                                                    </td>
                                                    <td class="py-4 align-middle text-center">
                                                        <?= $user_row['first_name'] . " " . $user_row['last_name'] ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="mt-4 p-3 background-color rounded-3 shadow-lg"
                                id="selectedUsersSection<?= $group_id ?>" style="display: none;">
                                <div class="d-flex justify-content-between">
                                    <p class="mb-1 signature-color fw-bold">Selected Users</p>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="role" name="role"
                                            value="full control">
                                        <label class="form-check-label fw-bold bg-danger px-2 text-white rounded-5 text-sm"
                                            for="role"> Full Control </label>
                                    </div>

                                </div>
                                <ul id="selectedUsersList<?= $group_id ?>" class="list-group"></ul>
                            </div>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-dark mt-3" onclick="submitForm(<?= $group_id ?>)"><i
                                        class="fa-solid fa-plus"></i> Add Member</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function updateSelectedUsers(group_id, checkbox) {
                var selectedUsersSection = document.getElementById('selectedUsersSection' + group_id);
                var selectedUsersList = document.getElementById('selectedUsersList' + group_id);
                if (checkbox.checked) {
                    if (selectedUsersSection.style.display === "none") {
                        selectedUsersSection.style.display = "block";
                    }
                    var li = document.createElement('li');
                    li.textContent = checkbox.parentNode.nextElementSibling.nextElementSibling.textContent; // Get the name of the selected user
                    li.className = 'list-group-item';
                    selectedUsersList.appendChild(li);
                } else {
                    var userToRemove = checkbox.parentNode.nextElementSibling.nextElementSibling.textContent;
                    var listItems = selectedUsersList.getElementsByTagName('li');
                    for (var i = 0; i < listItems.length; i++) {
                        if (listItems[i].textContent === userToRemove) {
                            selectedUsersList.removeChild(listItems[i]);
                            break;
                        }
                    }
                    if (selectedUsersList.childElementCount === 0) {
                        selectedUsersSection.style.display = "none";
                    }
                }
            }

            function submitForm(group_id) {
                var selectedUsers = [];
                var checkboxes = document.querySelectorAll('#userList' + group_id + ' input[type="checkbox"]');
                checkboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        selectedUsers.push(checkbox.value);
                    }
                });
                document.getElementById('selectedUsers' + group_id).value = JSON.stringify(selectedUsers);
                document.getElementById('addMemberForm' + group_id).submit();
            }

            // JavaScript for search functionality
            document.getElementById('searchUsers<?= $group_id ?>').addEventListener('input', function () {
                var input, filter, table, tr, td1, td2, i, txtValue1, txtValue2;
                input = document.getElementById('searchUsers<?= $group_id ?>');
                filter = input.value.toUpperCase();
                table = document.getElementById('userList<?= $group_id ?>');
                tr = table.getElementsByTagName('tr');
                for (i = 0; i < tr.length; i++) {
                    td1 = tr[i].getElementsByTagName('td')[1]; // Index 1 for the employee ID column
                    td2 = tr[i].getElementsByTagName('td')[2]; // Index 2 for the name column
                    if (td1 && td2) {
                        txtValue1 = td1.textContent || td1.innerText;
                        txtValue2 = td2.textContent || td2.innerText;
                        if (txtValue1.toUpperCase().indexOf(filter) > -1 || txtValue2.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = '';
                        } else {
                            tr[i].style.display = 'none';
                        }
                    }
                }
            });
        </script>
        <?php
    }
    ?>

    <!-- Add Group Modal-->
    <div class="modal fade" id="addGroupModal" tabindex="-1" role="dialog" aria-labelledby="addGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addGroupModalLabel">Add New Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-12 mt-2">
                                <label for="groupName" class="fw-bold">Group Name</label>
                                <input type="text" name="newGroupName" class="form-control" id="groupName">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="searchUsersForNewGroup" class="form-label">Search Users</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" class="form-control" id="searchUsersForNewGroup"
                                    name="searchUsersForNewGroup" placeholder="Search Users">
                            </div>
                            <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                                <table class="table table-hover mb-0 pb-0" id="newMemberList">
                                    <thead class="text-center">
                                        <tr>
                                            <th class="py-3 align-middle"> Select</th>
                                            <th class="py-3 align-middle">Employee ID</th>
                                            <th class="py-3 align-middle">Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="availableUserList">
                                        <?php
                                        $available_users_sql = "SELECT * FROM users JOIN employees ON users.employee_id = employees.employee_id WHERE user_id";
                                        $available_users_result = $conn->query($available_users_sql);
                                        while ($user_row = $available_users_result->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td class="align-middle text-center"><input class="form-check-input"
                                                        type="checkbox" value="<?= $user_row['user_id'] ?>"
                                                        name="selectedUsersToNewGroup[]"
                                                        onchange="updateSelectedMembers(this)">
                                                </td>
                                                <td class="py-4 align-middle text-center"><?= $user_row['employee_id'] ?>
                                                </td>
                                                <td class="py-4 align-middle text-center">
                                                    <?= $user_row['first_name'] . " " . $user_row['last_name'] ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-4 p-3 background-color rounded-3 shadow-lg" id="selectedUsersSection"
                            style="display: none;">
                            <p class="mb-1 signature-color fw-bold">Selected Users</p>
                            <ul id="selectedUsersList" class="list-group"></ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Group</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Show Folder Access Modal -->
    <?php
    // Fetching users for each group
    $groups_result->data_seek(0);
    while ($group_row = $groups_result->fetch_assoc()) {
        $group_id = $group_row['group_id'];
        $group_folder_sql = "SELECT *
                           FROM groups_folders 
                           JOIN folders ON groups_folders.folder_id = folders.folder_id
                           WHERE groups_folders.group_id = $group_id";
        $group_folder_result = $conn->query($group_folder_sql);
        ?>
        <!-- Member Modal for each group -->
        <div class="modal fade" id="folderModal<?= $group_id ?>" tabindex="-1" role="dialog"
            aria-labelledby="folderModalLabel<?= $group_id ?>" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="folderModalLabel<?= $group_id ?>">Folder Access</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive rounded-3 shadow-lg bg-light m-3">
                            <table class="table table-hover mb-0 pb-0">
                                <thead class="text-center">
                                    <tr>
                                        <th class="py-4 align-middle">Folder Name</th>
                                        <th class="py-4 align-middle">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($folder_row = $group_folder_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="py-4 align-middle text-center">
                                                <?= $folder_row['folder_name'] ?>
                                            </td>
                                            <td class="py-4 align-middle text-center">
                                                <form method="POST">
                                                    <input type="hidden" name="groupFolderIdToRemove"
                                                        value="<?= $folder_row['group_folder_id'] ?>" />
                                                    <button class="btn btn-danger btn-sm"> Remove Folder Access </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <script>
        function updateSelectedMembers(checkbox) {
            var selectedUsersSection = document.getElementById('selectedUsersSection');
            var selectedUsersList = document.getElementById('selectedUsersList');
            if (checkbox.checked) {
                if (selectedUsersSection.style.display === "none") {
                    selectedUsersSection.style.display = "block";
                }
                var li = document.createElement('li');
                li.textContent = checkbox.parentNode.nextElementSibling.nextElementSibling.textContent; // Get the name of the selected user
                li.className = 'list-group-item';
                selectedUsersList.appendChild(li);
            } else {
                var userToRemove = checkbox.parentNode.nextElementSibling.nextElementSibling.textContent;
                var listItems = selectedUsersList.getElementsByTagName('li');
                for (var i = 0; i < listItems.length; i++) {
                    if (listItems[i].textContent === userToRemove) {
                        selectedUsersList.removeChild(listItems[i]);
                        break;
                    }
                }
                if (selectedUsersList.childElementCount === 0) {
                    selectedUsersSection.style.display = "none";
                }
            }
        }

        // Javascript for search functionality
        document.getElementById('searchUsersForNewGroup').addEventListener('input', function () {
            var input, filter, table, tr, td1, td2, i, txtValue1, txtValue2;
            input = document.getElementById('searchUsersForNewGroup');
            filter = input.value.toUpperCase();
            table = document.getElementById('newMemberList');
            tr = table.getElementsByTagName('tr');
            for (i = 0; i < tr.length; i++) {
                td1 = tr[i].getElementsByTagName('td')[1]; // Index 1 for the employee ID column
                td2 = tr[i].getElementsByTagName('td')[2]; // Index 2 for the name column
                if (td1 && td2) {
                    txtValue1 = td1.textContent || td1.innerText;
                    txtValue2 = td2.textContent || td2.innerText;
                    if (txtValue1.toUpperCase().indexOf(filter) > -1 || txtValue2.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        })
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Edit button click event handler
            document.querySelectorAll('.edit-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    // Get the parent row
                    var row = this.closest('tr');

                    // Toggle edit mode
                    row.classList.toggle('editing');

                    // Toggle visibility of view and edit elements
                    row.querySelectorAll('.view-mode, .edit-mode').forEach(function (elem) {
                        elem.classList.toggle('d-none');
                    });
                });
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Button click event for the delete confirmation modal button
            document.querySelectorAll('#deleteGroupBtn').forEach(function (button) {
                button.addEventListener('click', function () {
                    var groupIdToDelete = button.getAttribute('data-group-id');

                    // Populate the Group Id to the delete confirmation modal
                    document.querySelector('#deleteConfirmationModal #groupIdToDelete').value = groupIdToDelete;
                })
            });
        });
    </script>

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t);
        })
    </script>
</body>

</html>