<?php
header('Content-Type: text/plain');

// Connect to the database
require_once("../db_connect.php");

// Check connection
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit();
}

// Get the WHS Document ID and WHS ID from the request
$whsDocumentId = isset($_POST['whsDocumentId']) ? $_POST['whsDocumentId'] : '';
$whsId = isset($_POST['whsId']) ? $_POST['whsId'] : '';

// Prepare SQL statement to check for duplicates
$check_document_sql = "SELECT COUNT(*) FROM whs WHERE whs_document_id = ? AND whs_id != ?";
$check_document_stmt = $conn->prepare($check_document_sql);
if (!$check_document_stmt) {
    echo "Prepare failed: " . $conn->error;
    exit();
}

$check_document_stmt->bind_param("si", $whsDocumentId, $whsId);
$check_document_stmt->execute();
$check_document_stmt->bind_result($document_count);
$check_document_stmt->fetch();
$check_document_stmt->close();

// Return 1 if duplicate found, 0 if no duplicate
echo $document_count > 0 ? "1" : "0";

$conn->close();

?>