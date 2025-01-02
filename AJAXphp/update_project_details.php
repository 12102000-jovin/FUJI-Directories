<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_all_dates') {
        // Handle updating all dates
        $date = $_POST['date'];
        $project_id = $_POST['projectIdEditAllDate'];

        if (!$date) {
            echo json_encode(['success' => false, 'error' => 'Date is required.']);
            exit;
        }

        $update_sql = "UPDATE project_details SET `date` = ? WHERE project_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $date, $project_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    } elseif (isset($_POST['project_details_id'])) {
        // Handle updating individual row
        $project_details_id = $_POST['project_details_id'];
        $date = !empty($_POST["date"]) ? $_POST["date"] : null;
        $description = $_POST['description'];
        $unit_price = $_POST['unitprice'];
        $quantity = $_POST['quantity'];

        // Calculate subtotal
        $sub_total = $unit_price * $quantity;

        // Update the project details in the database
        $update_sql = "UPDATE project_details SET 
            `date` = ?, 
            `description` = ?, 
            unit_price = ?, 
            quantity = ?, 
            sub_total = ? 
            WHERE project_details_id = ?";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssdidi", $date, $description, $unit_price, $quantity, $sub_total, $project_details_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_project_value') {
        // Handle update all project value
        $project_id = $_POST['projectId'];
        $totalValue = $_POST['totalValue'];

        // Update project value based on the project id
        $update_sql = "UPDATE projects SET `value` = ? WHERE project_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("di", $totalValue, $project_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>