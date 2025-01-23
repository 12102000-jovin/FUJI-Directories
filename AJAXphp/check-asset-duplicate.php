<?php 
header('Content-Type: text/plain'); // Ensure plain text response

// Connect to the database
require_once("../db_connect.php");

// Check connection
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit();
}

// Get the asset id and the asset no from the request
$assetId = isset($_POST['assetId']) ? $_POST['assetId'] : '';
$assetNo = isset($_POST['assetNo']) ? $_POST['assetNo'] : '';

// Prepare SQL statement to check for duplicates
$check_document_sql = "SELECT COUNT(*) FROM assets WHERE asset_no = ? AND asset_id != ?";
$check_document_stmt = $conn->prepare($check_document_sql);
if (!$check_document_stmt) {
    echo "Prepare failed: " . $conn->error;
    exit();
}

$check_document_stmt->bind_param("si", $assetNo, $assetId);
$check_document_stmt->execute();
$check_document_stmt->bind_result($document_count);
$check_document_stmt->fetch();
$check_document_stmt->close();

// Return 1 if duplicate found, 0 if no duplicate
echo $document_count > 0 ? "1" : "0";

$conn->close();
?>