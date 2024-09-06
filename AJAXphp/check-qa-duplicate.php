<?php
header('Content-Type: text/plain'); // Ensure plain text response for debugging

// Connect to the database
require_once ("../db_connect.php");

// Check connection
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit();
}

// Get the document name from the request
$qaDocument = isset($_POST['qaDocument']) ? $_POST['qaDocument'] : '';
$qaId = isset($_POST['qaId']) ? $_POST['qaId'] : '';

// Prepare SQL statement to check for duplicates
$check_document_sql = "SELECT COUNT(*) FROM quality_assurance WHERE qa_document = ? AND qa_id != ?";
$check_document_stmt = $conn->prepare($check_document_sql);
if (!$check_document_stmt) {
    echo "Prepare failed: " . $conn->error;
    exit();
}

$check_document_stmt->bind_param("si", $qaDocument, $qaId);
$check_document_stmt->execute();
$check_document_stmt->bind_result($document_count);
$check_document_stmt->fetch();
$check_document_stmt->close();

// Output debugging information
echo $document_count > 0 ? "Duplicate document found. " : "No duplicate found.";

$conn->close();

