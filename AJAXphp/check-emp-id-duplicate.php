<?php 
header('Content-Type: text/plain'); // Ensure plain text response for debugging

// Connect to the database
require_once("../db_connect.php");

// Check connection
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit();
}

// Get the employee id from the request
$employeeId = isset($_POST['employeeId']) ? $_POST['employeeId'] : '';

// Prepare SQL statement to check for duplicates
$check_employee_sql = "SELECT COUNT(*) FROM employees WHERE employee_id = ?";
$check_employee_stmt = $conn->prepare($check_employee_sql);
if (!$check_employee_stmt) {
    echo "Prepare failed: " . $conn->error;
    exit();
}

$check_employee_stmt->bind_param("i", $employeeId);
$check_employee_stmt->execute();
$check_employee_stmt->bind_result($employee_count);
$check_employee_stmt->fetch();
$check_employee_stmt->close();

// Output debugging information
echo $employee_count > 0 ? "Duplicate employee found. " : "No duplicate found.";

$conn->close();
