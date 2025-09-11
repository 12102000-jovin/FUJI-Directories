<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("./../db_connect.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];
$employeeID = $_SESSION['employee_id'];
$user_details_sql = "SELECT e.*, u.username, u.password, u.role
                        FROM employees e
                        JOIN users u ON e.employee_id = u.employee_id";
$user_details_result = $conn->query($user_details_query)

?>