<?php
ini_set('display_errors', '1');
ini_set('diaplay_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("./../db_connect");

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

// SQL QUERY to retrieve employee not in users
$employee_sql = "SELECT first_names, last_name, employee_id FROM employees NOT IN (SELECT employee_id FROM users)";
$employee_result = $conn->query($employee_sql);

// SQL QUERY to retrieve employee
$employees_sql = "SELECT * FROM employees";
$employees_result = $conn->query($employee_sql);

$error_message = ""; //Initialize error message variable

// SQL Query to add user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['username'])) {
    $employeeId = $_POST["employeeId"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST['role'];

    // Check if the username already exists in the users table
    $check_existing_username_sql = "SELECT COUNT (*) AS count FROM users WHERE username = ?";
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

    $edit_user_sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE employee_id = ?";
    $edit_user_result = $conn->prepare($edit_user_sql);
    $edit_user_result->bind_param("sssi", $editUsername, $editPassword, $editRole, $employeeIdToEdit);

    // Execute prepared statement
    if ($edit_user_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '"); </script>';
        exit();
    } else {
        echo "Error: " . $edit_user_result . "<br>" . $conn->error;
    }

    // Close Statement
    $edit_user_result->close();
}

// SQL Query to delete user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['employeeIdToDelete'])) {
    $employeeIdToEdit = $_POST['employeeIdToEdit'];
    $editUsername = $_POST['editUsername'];
    $editPassword = $_POST['editPassword'];
    $editRole = $_POST['editRole'];

    $edit_user_sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE employee_id = ?";
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

// SQL Query to delte user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['employeeIdToDelete'])) {
    $employeeIdToDelete = $_POST['employeeIdToDelete'];

    // Check if the user is part of any group
    $check_user_group_sql = "SELECT * FROM users_groups
                            JOIN users ON users_groups.user_id = users.user_id
                            WHERE users.employee_id = ?";
    $check_user_group_stmt = $conn->prepare($check_user_group_sql);
    $check_user_group_stmt->bind_param("i", $employeeIdToDelete);
    $check_user_group_stmt->execute();
    $result = $check_user_group_stmt->get_result();
}