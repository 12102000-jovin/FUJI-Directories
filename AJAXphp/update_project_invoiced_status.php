<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");


if (isset($_POST['project_details_id'], $_POST['invoiced'], $_POST['approvedBy'])) {

    $project_details_id = $_POST['project_details_id'];
    $invoiced = $_POST['invoiced'];
    $approvedBy = $_POST['approvedBy'] ?: NULL; // set to NULL if empty

    // Update project_details table
    $stmt = $conn->prepare("UPDATE project_details SET invoiced = ?, approved_by = ? WHERE project_details_id = ?");
    $stmt->bind_param("iii", $invoiced, $approvedBy, $project_details_id);
    $success = $stmt->execute();

    if ($success) {
        $approvedByName = '';
        if ($approvedBy) {
            $res = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE employee_id = ?");
            $res->bind_param("i", $approvedBy);
            $res->execute();
            $row = $res->get_result()->fetch_assoc();
            if ($row) {
                $approvedByName = $row['name'];
            }
        }

        echo json_encode([
            'success' => true,
            'approvedByName' => $approvedByName
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
?>