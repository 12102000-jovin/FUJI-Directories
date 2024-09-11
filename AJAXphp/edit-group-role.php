<?php
header('Content-Type: application/json');

// Connect to the database
require_once("../db_connect.php");

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $editRole = $_POST['editRole'] ?? '';
    $userGroupIdToEdit = $_POST['userGroupIdToEdit'] ?? '';

    // Prepare and execute the update query
    $edit_group_role_sql = "UPDATE users_groups SET `role` = ? WHERE user_group_id = ?";
    $edit_group_role_result = $conn->prepare($edit_group_role_sql);
    $edit_group_role_result->bind_param("si", $editRole, $userGroupIdToEdit);
    $success = $edit_group_role_result->execute();

    // Return a JSON response
    if ($success) {
        echo json_encode(array('status' => 'success', 'message' => 'Role updated successfully'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Failed to update role'));
    }
} else {
    // Handle non-POST requests
    echo json_encode(array('status' => 'error', 'message' => 'Invalid request'));
}
?>
