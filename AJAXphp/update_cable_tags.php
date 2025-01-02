<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('../db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cable_tag_id'])) {
        // Retrieve cable tag ID from POST data
        $cableTagId = $_POST['cable_tag_id'];

        // Initialize the variables for the dynamic fields
        $testDate = '';
        $tester = '';
        $testResult = '';

        // Loop through POST data to get dynamic values (e.g., test_date_1, tester_1, test_result_1)
        foreach ($_POST as $key => $value) {
            // Extract dynamic keys using the suffix _1
            if (strpos($key, 'test_date_') === 0) {
                $testDate = $value;
            } elseif (strpos($key, 'tester_') === 0) {
                $tester = $value;
            } elseif (strpos($key, 'test_result_') === 0) {
                $testResult = $value;
            }
        }

        // Validate that test_date, tester, and test_result are set
        if (empty($testDate) || empty($tester) || empty($testResult)) {
            echo json_encode(['success' => false, 'error' => 'Required fields are missing']);
            exit;
        }

        // Update cable tag values in the database
        $update_sql = "UPDATE cable_tags SET
            test_date = ?, 
            tester = ?,
            test_result = ?
            WHERE cable_tag_id = ?";

        // Prepare the SQL statement
        if ($stmt = $conn->prepare($update_sql)) {
            // Bind the parameters, using 's' for strings and 'i' for integers
            $stmt->bind_param("sssi", $testDate, $tester, $testResult, $cableTagId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare SQL statement']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No cable tag ID provided']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
