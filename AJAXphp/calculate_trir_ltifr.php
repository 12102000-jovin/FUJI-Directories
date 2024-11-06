<?php

// Connect to the database
require_once("../db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trir']) && $_POST['trir'] === "trir") {
    // Capture input values
    $hoursWorked = $_POST['hoursWork'] ?? 0;
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';

    // Validate inputs
    if ($hoursWorked > 0 && !empty($startDate) && !empty($endDate)) {

        // Prepare and execute SQL query to count incidents between start and end dates
        $sql = "SELECT COUNT(*) AS ttm_incident_count FROM whs WHERE incident_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $ttm_incident_count = $row['ttm_incident_count'];

        // Calculate TRIR
        $trir = ($ttm_incident_count * 200000) / $hoursWorked;

        // Close the database connection
        $stmt->close();
        $conn->close();

        // Return result as JSON, including both TRIR and incident count
        echo json_encode([
            'success' => true,
            'trir' => number_format($trir, 2),
            'incident_count' => $ttm_incident_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ltifr']) && $_POST['ltifr'] === "ltifr") {
    // Capture input values
    $hoursWorked = $_POST['hoursWork'] ?? 0;
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';

    // Validate inputs
    if ($hoursWorked > 0 && !empty($startDate) && !empty($endDate)) {

        // Prepare and execute SQL query to count incidents between start and end dates
        $sql = "SELECT COUNT(*) AS ttm_incident_count FROM whs WHERE incident_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $ttm_incident_count = $row['ttm_incident_count'];

        // Calculate LTIFR
        $ltifr = ($ttm_incident_count / $hoursWorked) * 1000000;

        // Close the database connection
        $stmt->close();
        $conn->close();

        // Return result as JSON, including both LTIFR and incident count
        echo json_encode([
            'success' => true,
            'ltifr' => number_format($ltifr, 2),
            'incident_count' => $ttm_incident_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    }
}
?>