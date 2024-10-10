<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once("./../config.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get user's role from login session
$employeeId = $_SESSION['employee_id'];

// SQL Query to retrieve users details
$user_details_sql = "SELECT e.*, u.username, u.password, u.role
                    FROM employees e
                    JOIN users u ON e.employee_id = u.employee_id";
$user_details_result = $conn->query($user_details_sql);

// SQL Query to retrieve employee not in users
$employee_sql = "SELECT first_name, last_name, employee_id FROM employees WHERE employee_id NOT IN (SELECT employee_id FROM users)";
$employee_result = $conn->query($employee_sql);

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['username'])) {
    $employeeId = $_POST["employeeId"];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if the username already exists in the users table
    $check_existing_username_sql = "SELECT COUNT(*) AS count FROM users WHERE username = ?";
    $check_existing_username_result = $conn->prepare($check_existing_username_sql);
    $check_existing_username_result->bind_param("s", $username);
    $check_existing_username_result->execute();
    $existing_username_data = $check_existing_username_result->get_result()->fetch_assoc();

    if ($existing_username_data['count'] > 0) {
        $error_message = "Error: Username already exists.";
    } else {
        // Check if the employee ID already exists in the users table
        $check_existing_user_sql = "SELECT COUNT(*) AS count FROM users WHERE employee_id = ?";
        $check_existing_user_result = $conn->prepare($check_existing_user_sql);
        $check_existing_user_result->bind_param("s", $employeeId);
        $check_existing_user_result->execute();
        $existing_user_data = $check_existing_user_result->get_result()->fetch_assoc();

        if ($existing_user_data['count'] > 0) {
            $error_message = "Error: User with employee ID $employeeId already exists.";
        } else {
            // Proceed with inserting the new user
            $add_user_sql = "INSERT INTO users (employee_id, username, password, role) VALUES (?,?,?,?)";
            $add_user_result = $conn->prepare($add_user_sql);
            $add_user_result->bind_param("ssss", $employeeId, $username, $password, $role);

            // Execute the prepared statement 
            if ($add_user_result->execute()) {
                echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
                exit(); // Ensure script execution stops after redirection
            } else {
                $error_message = "Error: " . $add_user_result . "<br>" . $conn->error;
            }
        }
    }
}

// SQL Query to edit user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['employeeIdToEdit'])) {
    $employeeIdToEdit = $_POST['employeeIdToEdit'];
    $editUsername = $_POST['editUsername'];
    $editPassword = $_POST['editPassword'];
    $editRole = $_POST['editRole'];

    $edit_user_sql = "UPDATE users SET username = ?, password = ?, role= ? WHERE employee_id = ?";
    $edit_user_result = $conn->prepare($edit_user_sql);
    $edit_user_result->bind_param("sssi", $editUsername, $editPassword, $editRole, $employeeIdToEdit);

    // Execute prepared statement
    if ($edit_user_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
        exit();
    } else {
        echo "Error: " . $edit_user_result . "<br>" . $conn->error;
    }

    // Close Statement
    $edit_user_result->close();
}

// SQL Query to delete user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['employeeIdToDelete'])) {
    $employeeIdToDelete = $_POST['employeeIdToDelete'];

    // Check if the user is part of any group
    $check_user_group_sql = "SELECT * FROM users_groups
                            JOIN users ON users_groups.user_id
                            WHERE users.employee_id = ? '";
    $check_user_group_stmt = $conn->prepare($check_user_group_sql);
    $check_user_group_stmt->bind_param("i", $employeeIdToDelete);
    $check_user_group_stmt->execute();
    $result = $check_user_group_stmt->get_result();

    if ($result->num_rows > 0) {
        // User is part of a group, echo details (optional)
        while ($row = $result->fetch_assoc()) {
            echo "User ID: " . htmlspecialchars($row['user_id']) . "<br>";
            echo "Employee ID: " . htmlspecialchars($row['employee_id']) . "<br>";
            echo "User Group ID: " . htmlspecialchars($row['user_group_id']) . "<br>";
            echo "Group ID: " . htmlspecialchars($row['group_id']) . "<br>";
        }

        // Delete user from users_groups table
        $delete_user_group_sql = "DELETE users_groups FROM users_groups
                                  JOIN users ON users_groups.user_id = users.user_id
                                  WHERE users.employee_id = ?";
        $delete_user_group_stmt = $conn->prepare($delete_user_groups_sql);
        $delete_user_group_stmt->bind_param("i", $employeeIdToDelete);
        $delete_user_group_stmt->execute();
        $delete_user_group_stmt->close();
    }

    // Delete user from user table
    $delete_user_sql = "DELETE FROM users WHERE employee_id = ?";
    $delete_user_stmt = $conn->prepare($delete_user_sql);
    $delete_user_stmt->bind_param("i", $employeeIdToDelete);

    // Execute the prepared statement
    if ($delete_user_stmt->execute()) {
        echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        $error_message = "Error: " . $conn->error;
        echo "<div class='alert alert-danger'> $error_message </div>";
    }

    // Close the statements
    $check_user_group_stmt->close();
    $delete_user_stmt->close();
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Manage User</title>
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
                <li class="breadcrumb-item fw-bold signature-color">Manage Users</li>
            </ol>
        </nav>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-10 order-2 order-lg-1">
                <div class="d-md-none">
                    <?php while ($row = $user_details_result->fetch_assoc()): ?>
                        <div class="card mb-3 border-0 userCard">
                            <div
                                class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
                                <div class="bg-gradient shadow-lg rounded-circle mb-3"
                                    style="width: 100px; height: 100px; overflow: hidden;">
                                    <?php if (!empty($row['profile_image'])): ?>
                                        <img src="data:image/jpeg;base64,<?= $row['profile_image'] ?>" alt="Profile Image"
                                            class="profile-pic img-fluid rounded-circle"
                                            style="width: 100%; height: 100%; object-fit: cover;">F
                                    <?php else: ?>
                                        <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                            style="width: 100%; height: 100%;">
                                            <h3 class="p-0 m-0">
                                                <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                                            </h3>
                                        </div>
                                    <?php endif ?>
                                </div>
                                <h5 class="card-title fw-bold usernameCard">
                                    <?= $row['first_name'] . ' ' . $row['last_name'] ?>
                                </h5>
                                <h6 class="card-subtitle mb-3 text-muted userIdCard">Employee ID: <?= $row['employee_id'] ?>
                                </h6>
                                <div class="d-flex flex-wrap justify-content-center">
                                    <div class="col-md-4 mb-2 m-1">
                                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?= $row['emloyee_id'] ?>"
                                            target="_blank" class="btn btn-dark w-100"><small>Profile <i
                                                    class="fa-solid fa-up-right-from-square fa-sm"></i></small></a>
                                    </div>
                                    <div class="col-md-4 mb-2 m-1">
                                        <button class="btn text-white editUserModalBtn w-100"
                                            style="background-color: #043f9d" data-employee-id="<?= $row['employee_id'] ?>"
                                            data-username="<?= $row['username'] ?>" data-password="<?= $row['password'] ?>"
                                            data-first-name="<?= $row['first_name'] ?>"
                                            data-last-name="<?= $row['last_name'] ?>" data-role="<?= $row['role'] ?>">
                                            <small>Edit</small><i class="fa-regular fa-pen-to-square fa-sm mx-1"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-2 m-1">
                                        <button class="btn btn-danger w-100 deleteUserBtn" #043f9d
                                            data-employee-id="<?= $row['employee_id'] ?>">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="d-none d-md-block">
                    <div class="input-group mb-3 me-2">
                        <span class="input-group-text"> <i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" class="form-control" id="searchUser" name="searchUser"
                            placeholder="Search User" oninput="filterUsers(this.value)">
                    </div>
                    <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                        <table class="table table-hover mb-0 pb-0">
                            <thead class="table-primary">
                                <tr class="text-center">
                                    <th class="py-4 align-middle">Name</th>
                                    <th class="py-4 align-middle">Employee Id</th>
                                    <th class="py-4 align-middle">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $user_details_result->data_seek(0); ?>
                                <?php while ($row = $user_details_result->fetch_assoc()): ?>
                                    <tr class="text-center userData">
                                        <td class="py-4 align-middle userName">
                                            <?= $row['first_name'] . ' ' . $row['last_name'] ?>
                                        </td>
                                        <td class="py-4 align-middle userId"><?= $row['employee_id'] ?></td>
                                        <td class="py-4 align-middle">
                                            <button class="btn text-warning" type="button" data-bs-toggle="modal"
                                                data-bs-target="#showAccessModal<?= $row['employee_id'] ?>">
                                                <i class="fa-solid fa-key tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="User's Access"></i>
                                            </button>
                                            <button class="btn">
                                                <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?= $row['employee_id'] ?>"
                                                    target="_blank" class="tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="View Profile"><i
                                                        class="fa-solid fa-up-right-from-square fa-sm m-1 text-dark">
                                                    </i></a>
                                            </button>
                                            <button class="btn editUserModalBtn" data-bs-toggle="modal"
                                                data-bs-target="#editUserModal"
                                                data-employee-id="<?= $row['employee_id'] ?>"
                                                data-username="<?= $row['username'] ?>"
                                                data-password="<?= $row['password'] ?>"
                                                data-first-name="<?= $row['first_name'] ?>"
                                                data-last-name="<?= $row['last_name'] ?>" data-role="<?= $row['role'] ?>">
                                                <i class="fa-regular fa-pen-to-square fa-sm m-1 tooltips"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Edit User"></i>
                                            </button>
                                            <button class="deleteUserBtn btn" data-bs-toggle="modal"
                                                data-bs-target="#deleteConfirmationModal"
                                                data-employee-id="<?= $row['employee_id'] ?>">
                                                <i class="fa-solid fa-trash-can text-danger m-1 tooltips"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Delete User"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 order-1 order-lg-2">
                <div class="d-none d-lg-block">
                    <div
                        class="bg-light d-flex justify-content-center flex-column align-items-center rounded-3 p-4 shadow-lg">
                        <button
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12"
                            id="addUserModalBtn" data-bs-toggle="modal" data-bs-target="#addUserModal"><i
                                class="fa-solid fa-user-plus fa-3x signature-color"></i><span
                                class="mt-2 signature-color fw-bold">Add New User</span>
                        </button>
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-groups.php"
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12 mt-3"><i
                                class="fa-solid fa-user-group fa-3x text-dark"></i><span
                                class="mt-2 text-warning fw-bold">
                                Manage Groups</span></a>
                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-folders.php"
                            class="btn signature-btn p-3 col-10 d-flex flex-column justify-content-center align-items-center col-6 col-lg-12 mt-3"><i
                                class="fa-solid fa-folder fa-3x text-warning"></i><span
                                class="mt-2 text-warning fw-bold"> Manage Folders</span></a>
                    </div>
                </div>
                <div class="col-12 d-lg-none bg-white rounded-3 p-4 mb-4 shadow-lg">
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <button class="btn signature-btn p-3 w-100" id="addUserModalBtnMobile"
                                data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fa-solid fa-user-group me-1 fa-lg signature-color"></i>
                                <span class="signature-color fw-bold">Add New User</span>
                            </button>
                        </div>
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-groups.php"
                                class="btn signature-btn p-3 w-100">
                                <i class="fa-solid fa-user-group me-1 fa-lg text-dark"></i>
                                <span class="text-dark fw-bold">Manage Groups</span>
                            </a>
                        </div>
                        <div class="col-12 col-md-4">
                            <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/AccessPages/manage-folders.php"
                                class="btn signature-btn p-3 w-100"></a>
                            <i class="fa-solid fa-folder me-1 fa-lg text-warning"></i>
                            <span class="text-warning fw-bold">Manage Folders</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="d-md-none">
                    <div class="input-group mb-3 me-2">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" class="form-control" id="searchUser" name="searchUser"
                            placeholder="Search User" oninput="filterUserSmallScreen(this.value)">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $employees_result->data_seek(0);
    while ($employee_row = $employees_result->fetch_assoc()) {
        $employee_id = $employee_row['employee_id'];
        $employee_group_access_sql = "SELECT DISTINCT `groups`.group_name, folders.folder_name, `groups`.group_id, folders.folder_id, users_groups.role, employees.first_name, employees.last_name, employees.employee_id, users_groups.users_group_id
        FROM `groups`
        JOIN groups_folders ON `groups`.group_id = groups_folders.group_id
        JOIN folders ON folders.folder_id = groups_folders.folder_id
        JOIN users_groups ON users_groups.group_id = `groups`.group_id
        JOIN users ON users.user_id = users_groups.user_id
        JOIN employees ON employees.employee_id
        WHERE employees.employee_id = $employee_id";
        $employee_group_access_result = $conn->query($employee_group_access_sql);
        ?>
        <!-- User Access Modal -->
        <div class="modal fade" id="showAccessModal<?= $employee_id ?>" tabindex="-1" role="dialog"
            aria-labelledby="userAccessModalLabel<?= $employee_id ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">User Access</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        // Check if there are any results
                        if ($employee_group_access_result->num_rows > 0) {
                            $current_group_id = null;

                            // Initialize arrays to store unique group names and folder names
                            $unique_groups = [];
                            $unique_folders = [];
                            $userGroupIds = [];

                            // Fetch all rows from result set
                            while ($row = $employee_group_access_result->fetch_assoc()) {
                                $group_id = $row['group_id'];
                                $group_name = htmlspecialchars($row['group_name']);
                                $folder_id = $row['folder_id'];
                                $folder_name = htmlspecialchars($row['folder_name']);
                                $role = htmlspecialchars($row['role']);
                                $firstName = htmlspecialchars($row['first_name']);
                                $lastName = htmlspecialchars($row['last_name']);
                                $userAccessEmployeeId = htmlspecialchars($row['employee_id']);
                                $userGroupId = htmlspecialchars($row['user_group_id']);

                                // Collect unique group names and associated roles
                                if (!isset($unique_groups[$group_id])) {
                                    $unique_groups[$group_id] = [
                                        'name' => $group_name,
                                        'role' => $role
                                    ];
                                }

                                // Collect unique folder names
                                if (!isset($unique_folders[$folder_id])) {
                                    $unique_folders[$folder_id] = $folder_name;
                                }

                                // Collect user group IDs
                                $userGroupsIds[$group_id] = $userGroupId;
                            }

                            // Output unique group names and their roles
                            if (!empty($unique_groups)) {
                                echo "<h4 class='mt-4 mb-4 fw-bold signature-color text-center'> </h4>";
                                echo "<strong class='signature-color'>Group</strong>";
                                echo "<div class='mb-3'>";
                                echo "<div class='table-responsive rounded-3 shadow-lg bg-light m-0'>";
                                echo "<table class='table table-hover mb-0 pb-0'>";
                                echo "<thead>
                                    <tr>
                                        <th class='col-6 text-center'>Group</th>
                                        <th class='col-6 text-center'>Role</th>
                                    </tr>
                                </thead>";
                                echo "<tbody>";
                                    foreach ($unique_groups as $group_id => $group_data) {
                                        // Determine badge class based on role
                                        $badgeClass = match ($group_data['role']) {
                                            'admin' => 'text-bg-danger',
                                            'general' => 'text-bg-success',
                                            'restricted' => 'text-bg-dark',
                                        };

                                        $editSelect = "<select class='form-select edit-mode d-none' aria-label='editRole' name='editRole' id='editRole' style='min-width: 120px;' required>
                                        <option value='general'" . ($group_data['role'] === 'general' ? " selected" : "") . ">General</option>
                                        <option value='admin'" . ($group_data['role'] === 'admin' ? " selected" : "") . ">Admin</option>
                                        <option value='restricted'" . ($group_data['role'] === 'restricted' ? " selected" : "") . ">Restricted</option>
                                    </select>";
                                        echo "<tr>
                                        <td class='align-middle'>{$group_data['name']}</td>
                                        <td class='align-middle text-center'>  
                                        <form class='editGroupRoleForm'><div class='d-flex align-items-center justify-content-center'>
                                            $editSelect
                                            <span class='badge rounded-pill $badgeClass view-mode'>{$group_data['role']}</span>
                                            <i class='fa-regular fa-pen-to-square ms-2 signature-color tooltips editRoleBtn view-mode'
                                            data-bs-toggle='tooltip' data-bs-placement='top' title='Edit Role' role='button'></i>
                                                <div class='edit-mode d-none d-flex justify-content-center'>
                                                    <input type='hidden' name='userGroupIdToEdit' value='{$userGroupIds[$group_id]}'>
                                                    <button type='submit' class='btn btn-sm px-2 btn-success mx-1'>
                                                        <div class='d-flex justify-content-center'>
                                                            <i role='button' class='fa-solid fa-check text-white m-1'></i> Edit
                                                        </div>
                                                    </button>
                                                    <button type='button' class='btn btn-sm px-2 btn-danger mx-1 editRoleBtn'>
                                                        <div class='d-flex justify-content-center'>
                                                            <i role='button' class='fa-solid fa-xmark text-white m-1'></i> Cancel
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>     
                                        </td>
                                    </tr>";
                                    }
                                    echo "</tbody>";
                                    echo "</table>";
                                    echo "</div>";
                                    echo "</div>";
                            }

                            // Output unique folder names
                            if (!empty($unique_folders)) {
                                echo "<strong class='signature-color'> Folders: </strong>";
                                echo "<div class='mb-3>";
                                echo "<div class='table-reponsive rounded-3 shadow-lg'>";

                                // Create a container for the folder list
                                echo "<div class='list-group'>";
                                foreach ($unique_folders as $folder_id => $folder_name) {
                                    echo "<a href='#' class='list-group-item list-group-item-action' "
                                }
                            }
                        }

                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</body>

</html>