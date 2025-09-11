<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

if (isset($_POST['toggle_step']) && isset($_POST['project_details_id'])) {
    $project_details_id = $_POST['project_details_id'];
    $stepNumber = intval($_POST['toggle_step']); // 1 to 7

    // Map steps to their corresponding column names
    $stepColumns = [
        1 => 'drawing_issued_date',
        2 => 'programming_date',
        3 => 'ready_to_handover_date',
        4 => 'handed_over_to_electrical_date',
        5 => 'testing_date',
        6 => 'completed_date',
        7 => 'ready_for_delivery_date'
    ];

    if (!isset($stepColumns[$stepNumber])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid step']);
        exit;
    }

    $column = $stepColumns[$stepNumber];

    // Check current value
    $stmt = $conn->prepare("SELECT $column FROM project_details WHERE project_details_id = ?");
    $stmt->bind_param("i", $project_details_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!empty($row[$column])) {
        // Remove date (toggle off)
        $update_sql = "UPDATE project_details SET $column = NULL WHERE project_details_id = ?";
    } else {
        // Set today's date
        $update_sql = "UPDATE project_details SET $column = NOW() WHERE project_details_id = ?";
    }

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $project_details_id);
    $update_stmt->execute();

    $new_date = !empty($row[$column]) ? '' : date('d M Y');
    echo json_encode(['status' => 'success', 'new_date' => $new_date]);
    exit;
}
?>
