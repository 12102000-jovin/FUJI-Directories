<?php

// Connect to the database
require_once("../db_connect.php");

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Extract parameters
$historyDetailsType = $data['historyDetailsType'] ?? '';
$assetId = $data['assetId'] ?? '';
$assetDetailsId = $data['asset_details_id'] ?? ''; // For deletion

// Check if either 'historyDetailsType' or 'assetId' is missing, but only if 'assetDetailsId' is not set
if (!$assetDetailsId && (!$historyDetailsType || !$assetId)) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Handle Deletion if 'assetDetailsId' exists
if ($assetDetailsId) {
    // Handle Deletion
    $deleteQuery = "DELETE FROM asset_details WHERE asset_details_id = '$assetDetailsId'";

    if ($conn->query($deleteQuery) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Asset detail deleted successfully']);
        exit;
    } else {
        echo json_encode(['error' => 'Error deleting asset detail']);
        exit;
    }
}

// Query handling for fetching asset details
$query = "";

if ($historyDetailsType === 'Maintenance') {
    $query = "SELECT asset_details.*, assets.* FROM asset_details 
              JOIN assets ON assets.asset_id = asset_details.asset_id 
              WHERE assets.asset_id = '$assetId' AND categories = 'Maintenance' 
              ORDER BY performed_date DESC";
} elseif ($historyDetailsType === 'Repair') {
    $query = "SELECT asset_details.*, assets.* FROM asset_details 
              JOIN assets ON assets.asset_id = asset_details.asset_id 
              WHERE assets.asset_id = '$assetId' AND categories = 'Repair' 
              ORDER BY performed_date DESC";
} elseif ($historyDetailsType === 'Calibration') {
    $query = "SELECT asset_details.*, assets.* FROM asset_details 
              JOIN assets ON assets.asset_id = asset_details.asset_id 
              WHERE assets.asset_id = '$assetId' AND categories = 'Calibration' 
              ORDER BY performed_date DESC";
}

$result = $conn->query($query);
$data = [];
 
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'asset_details_id' => $row['asset_details_id'],
            'performed_date' => $row['performed_date'],
            'due_date' => $row['due_date'] ?? 'N/A',
            'description' => $row['description'] ?? 'N/A',
            'source' => $row['source'] ?? 'N/A',
        ];
    }
}

echo json_encode($data);

?>
