<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("./../db_connect.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get user's role from login session
$employeeId = $_SESSION['employee_id'];

// SQL Query to retrieve all departments
$department_sql = "SELECT * FROM department";
$department_result = $conn->query($department_sql);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newDepartment'])) {
    $newDepartment = $_POST['newDepartment'];

    // Check if the department name already exists
    $check_department_sql = "SELECT COUNT(*) FROM department WHERE department_name = ?";
    $check_department_result = $conn->prepare($check_department_sql);
    $check_department_result->bind_param("s", $newDepartment);
    $check_department_result->execute();
    $check_department_result->bind_result($departmentCount);
    $check_department_result->fetch();
    $check_department_result->close();

    if ($departmentCount > 0) {
        // Department already exists 
        echo "<script> alert('Department already exist.') </script>";
    } else {
        // Add the new department
        $add_department_sql = "INSERT INTO department (department_name) VALUES (?)";
        $add_department_result = $conn->prepare($add_department_sql);
        $add_department_result->bind_param("s", $newDepartment);

        if ($add_department_result->execute()) {
            echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
            exit();
        } else {
            echo "Error: " . $add_department_result . "<br>" . $conn->error;
        }
        $add_department_result->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" &&  isset($_POST['departmentIdToDelete'])) {
    $departmentIdToDelete = $_POST['departmentIdToDelete'];

    $delete_department_sql = "DELETE FROM department WHERE department_id = ?";
    $delete_department_result = $conn->prepare($delete_department_sql);
    $delete_department_result->bind_param("i", $departmentIdToDelete);

    if ($delete_department_result->execute()) {
        echo '<script>window.locaiton.replace("' . $_SERVER['PHP_SELF']. '")</script>';
    }
}
?>