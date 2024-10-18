<?php
header('Content-Type: application/json'); // Ensure JSON response

// Connect to the database
require_once("../db_connect.php");

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the employee ID and first aid allowance status from the POST data
    $employeeId = $_POST['employeeId']; // Ensure the name matches with the JS
    $firstAidAllowance = $_POST['first_aid_allowance']; // 0 or 1

    // Update the first_aid_allowance in the database
    $first_aid_allowance_update_sql = "UPDATE employees SET first_aid_allowance = ? WHERE employee_id = ?";
    $first_aid_allowance_update_stmt = $conn->prepare($first_aid_allowance_update_sql);
    $first_aid_allowance_update_stmt->bind_param("ii", $firstAidAllowance, $employeeId); // Both are integers

    if ($first_aid_allowance_update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update first aid allowance']);
    }

    $first_aid_allowance_update_stmt->close();
}
?>
