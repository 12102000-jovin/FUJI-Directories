<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

if (isset($_POST['project_id'])) {
    $project_id = $_POST['project_id']; // Get project ID from AJAX request

    // Assuming you already have a database connection $conn
    $project_detail_sql = "SELECT * FROM project_details WHERE project_id = ?";
    $stmt = $conn->prepare($project_detail_sql);
    $stmt->bind_param("i", $project_id); // Bind the project ID as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize the counter before the loop
    $item_number = 1;

    // Loop through the result and create table rows
    $output = "";
    while ($row = $result->fetch_assoc()) {
        // Output table row with data from the database
        $output .= "<tr>
        <td class='text-center' style='max-width:50px'> 
            <button class='btn'><i class='fa-regular fa-pen-to-square'></i></button>
            <button class='deleteProjectDetailsBtn btn' data-project-id='" . htmlspecialchars($row['project_id']) . "' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'><i class='fa-regular fa-trash-can text-danger'></i></button>
        </td>
        <td class='text-center py-3'>" . htmlspecialchars($item_number) . "</td>
        <td class='text-center py-3'>" . htmlspecialchars($row['date']) . "</td>
        <td class='text-center py-3'>" . htmlspecialchars($row['description']) . "</td>
        <td class='text-center py-3'>$" . number_format($row['unit_price'], 2) . "</td>
        <td class='text-center py-3'>" . $row['quantity'] . "</td>
        <td class='text-center py-3'>$" . number_format($row['sub_total'], 2) . "</td>
    </tr>";

        $item_number += 1;
    }

    echo $output; // Return the generated table rows to the client
} else {
    echo "No project ID provided!";
}
?>