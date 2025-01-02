<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once("../db_connect.php");

if (isset($_GET['action']) && $_GET['action'] == 'get_cable_tags') {
    $cableId = isset($_GET['cable_id']) ? trim($_GET['cable_id']) : '';

    if ($cableId) {
        $sql = "
            SELECT 
                cable_tags.cable_tag_id, 
                cable_tags.cable_tag_no, 
                cable_tags.test_date, 
                cable_tags.tester, 
                cable_tags.test_result,
                cables.test_frequency,
                cables.cable_id,
                employees.first_name, 
                employees.last_name,
                employees.employee_id
            FROM 
                cable_tags 
            JOIN 
                cables 
            ON 
                cable_tags.cable_id = cables.cable_id
            LEFT JOIN 
                employees 
            ON 
                cable_tags.tester = employees.employee_id
            WHERE 
                cable_tags.cable_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cableId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $testDate = $row['test_date']; // Test date from cable_tags
                $testFrequency = (int) $row['test_frequency']; // Test frequency in months from cables
                $firstName = $row['first_name']; // First name from employees
                $lastName = $row['last_name']; // Last name from employees
                $employeeId = $row['employee_id']; // Employee Id from employees
                $testResult = $row['test_result']; // Test result

                // Calculate the next test date
                $nextTestDate = date('Y-m-d', strtotime($testDate . " + $testFrequency months"));

                // Determine if bg-danger should be applied
                $cellClass = ($testResult === 'Failed') ? 'bg-danger bg-opacity-25' : 'bg-success bg-opacity-25';

                echo "<tr data-cable-tag-id='" . htmlspecialchars($row['cable_tag_id']) . "' data-cable-id='" . htmlspecialchars($row['cable_id']) . "' data-employee-id='" . htmlspecialchars($row['employee_id']) . "'>";
                echo '<td class="align-middle text-center ' . $cellClass . '">
                        <button class="deleteCableTestTagBtn btn text-danger" data-cable-tag-id="' . htmlspecialchars($row['cable_tag_id']) . '">
                            <i class="fa-regular fa-trash-can text-danger"></i>
                        </button>
                        <button class="editCableTestTagBtn btn signature-color" data-employee-id="'. htmlspecialchars($row['employee_id'] ). '" data-cable-tag-id="' . htmlspecialchars($row['cable_tag_id']) . '"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="btn text-dark barcode-icon"><i class="fa-solid fa-barcode"></i></button>
                      </td>';
                echo '<td class="align-middle text-center ' . $cellClass . '">' . htmlspecialchars($row['cable_tag_no']) . '</td>';
                echo '<td class="align-middle text-center ' . $cellClass . '" id="test_date_' . $row['cable_tag_id'] . '">' . htmlspecialchars($row['test_date']) . '</td>';
                echo '<td class="align-middle text-center ' . $cellClass . '">' . htmlspecialchars($nextTestDate) . '</td>';
                echo '<td class="align-middle text-center ' . $cellClass . '" id="tester_' . $row['cable_tag_id'] . '">' . htmlspecialchars($firstName . ' ' . $lastName . ' (' . $employeeId . ')') . '</td>'; // Displaying full name
                echo '<td class="align-middle text-center ' . $cellClass . '" id="test_result_' . $row['cable_tag_id'] . '">' . htmlspecialchars($testResult) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6" class="text-center">No data found</td></tr>';
        }


        $stmt->close();
        $conn->close();
    } else {
        echo '<tr><td colspan="5" class="text-center">Invalid Cable ID</td></tr>';
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>