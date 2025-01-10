<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

if (isset($_POST['project_details_id']) && isset($_POST['invoiced'])) {
    $projectDetailsId = $_POST['project_details_id'];
    $invoiced = $_POST['invoiced']; // 1 if checked, 0 otherwise

    $sql = "UPDATE project_details SET invoiced = ? WHERE project_details_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $invoiced, $projectDetailsId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
?>
