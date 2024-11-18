<?php
header('Content-Type: text/plain');

// Connect to the database
require_once("../db_connect.php");

// Check connection
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit();
}

// Get the project number and quote number
$projectNo = isset($_POST['projectNo']) ? $_POST['projectNo'] : '';
$quoteNo = isset($_POST['quoteNo']) ? $_POST['quoteNo'] : '';
$projectId = isset(($_POST['projectId'])) ? $_POST['projectId'] : '';

// Prepare SQL statement to check for duplicates
$check_project_sql = "SELECT COUNT(*) FROM projects WHERE (project_no = ? OR quote_no = ?) AND project_id != ?";
$check_project_result = $conn->prepare($check_project_sql);
if (!$check_project_result) {
    echo "Prepare failed: " . $conn->error;
    exit();
}

// Bind only the required parameters for projectNo and quoteNo
$check_project_result->bind_param("ssi", $projectNo, $quoteNo, $projectId);
$check_project_result->execute();
$check_project_result->bind_result($document_count);
$check_project_result->fetch();
$check_project_result->close();

// Return 1 if duplicate found (count > 0), 0 if no duplicate
echo $document_count > 0 ? "1" : "0";

$conn->close();
?>
