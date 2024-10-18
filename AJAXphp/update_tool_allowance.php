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
    // Get the employee ID and tool allowance status from the POST data
    $employeeId = $_POST['employeeId']; // Ensure the name matches with the JS
    $toolAllowance = $_POST['tool_allowance']; // 0 or 1

    // Update the tool_allowance in the database
    $tool_allowance_update_sql = "UPDATE employees SET tool_allowance = ? WHERE employee_id = ?";
    $tool_allowance_update_stmt = $conn->prepare($tool_allowance_update_sql);
    $tool_allowance_update_stmt->bind_param("ii", $toolAllowance, $employeeId); // Both are integers

    if ($tool_allowance_update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update tool allowance']);
    }

    $tool_allowance_update_stmt->close();
}
?>
