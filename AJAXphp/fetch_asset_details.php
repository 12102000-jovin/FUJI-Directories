<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

if (isset($_POST['asset_no'])) {
    $asset_no = $_POST['asset_no']; // Get asset ID from AJAX request

    // Remove the "FE" prefix (if it exists) and print the number only
    $asset_no_number = substr($asset_no, 2); // Remove the first 2 characters (the "FE" prefix)

    $asset_no_with_prefix = 'FE' . $asset_no_number;

    // Assuming you already have a database connection $conn
    $asset_sql = "SELECT 
        cables.*, 
        location.location_name
    FROM cables 
    LEFT JOIN location ON cables.location_id = location.location_id
    WHERE asset_no = ?";

    $stmt = $conn->prepare($asset_sql);
    $stmt->bind_param("i", $asset_no_number); // Bind the number part as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    // Loop through the result and create table rows
    $output = "";
    while ($row = $result->fetch_assoc()) {
        $description = isset($row['description']) ? htmlspecialchars($row['description']) : 'N/A';
        $descriptionStyle = isset($row['description']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;';

        $output .= "<tr>
            <td class='align-middle text-center py-2'>
                <a href='cable-table.php?search=" . urlencode($row['cable_no']) . "' target='_blank'>" . htmlspecialchars($row['cable_no']) . "</a>
            </td>
            <td class='align-middle text-center py-2' style='" . $descriptionStyle . "'>" . $description . "</td>
            <td class='align-middle text-center py-2'>" . htmlspecialchars($row['location_name']) . "</td>
            <td class='align-middle text-center py-2'>" . htmlspecialchars($row['test_frequency'] === '60' ? '5 Years' : $row['test_frequency'] . ' Months') . "</td>
        </tr>";
    }

    echo $output; // Return the generated table rows to the client
} else {
    echo "No asset ID provided!";
}
?>