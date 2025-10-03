<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

if (isset($_POST['project_id'])) {
    $project_id = $_POST['project_id']; // Get project ID from AJAX request

    // Assuming you already have a database connection $conn
    $project_detail_sql = "SELECT 
        project_details.*, 
        employees.first_name AS approved_first_name, 
        employees.last_name AS approved_last_name,
        e2.first_name AS drawing_issued_first_name,
        e2.last_name AS drawing_issued_last_name,
        e2.employee_id AS drawing_issued_employee_id,  -- Add this
        e3.first_name AS programming_first_name,
        e3.last_name AS programming_last_name,
        e3.employee_id AS programming_employee_id,     -- Add this
        e4.first_name AS ready_to_handover_first_name,
        e4.last_name AS ready_to_handover_last_name,
        e4.employee_id AS ready_to_handover_employee_id, -- Add this
        e5.first_name AS handed_over_to_electrical_first_name,
        e5.last_name AS handed_over_to_electrical_last_name,
        e5.employee_id AS handed_over_to_electrical_employee_id, -- Add this
        e6.first_name AS testing_first_name,
        e6.last_name AS testing_last_name,
        e6.employee_id AS testing_employee_id,         -- Add this
        e7.first_name AS completed_first_name,
        e7.last_name AS completed_last_name,
        e7.employee_id AS completed_employee_id,       -- Add this
        e8.first_name AS ready_first_name,
        e8.last_name AS ready_last_name,
        e8.employee_id AS ready_employee_id           -- Add this
    FROM project_details 
    LEFT JOIN employees ON project_details.approved_by = employees.employee_id
    LEFT JOIN employees e2 ON project_details.drawing_issued_by = e2.employee_id
    LEFT JOIN employees e3 ON project_details.programming_by = e3.employee_id
    LEFT JOIN employees e4 ON project_details.ready_to_handover_by = e4.employee_id
    LEFT JOIN employees e5 ON project_details.handed_over_to_electrical_by = e5.employee_id
    LEFT JOIN employees e6 ON project_details.testing_by = e6.employee_id
    LEFT JOIN employees e7 ON project_details.completed_by = e7.employee_id
    LEFT JOIN employees e8 ON project_details.ready_by = e8.employee_id
    WHERE project_details.project_id = ?";

    $stmt = $conn->prepare($project_detail_sql);
    $stmt->bind_param("i", $project_id); // Bind the project ID as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize the counter before the loop
    $item_number = 1;

    // Loop through the result and create table rows
    $output = "";
    $counter = 0;
    while ($row = $result->fetch_assoc()) {
        $uniqueId = 'detailsRow' . $counter;

        $steps = [
            ['label' => 'Drawing Issued', 'dateField' => 'drawing_issued_date'],
            ['label' => 'Programming', 'dateField' => 'programming_date'],
            ['label' => 'Ready to Handover', 'dateField' => 'ready_to_handover_date'],
            ['label' => 'Handed Over to Electrical', 'dateField' => 'handed_over_to_electrical_date'],
            ['label' => 'Testing', 'dateField' => 'testing_date'],
            ['label' => 'Completed', 'dateField' => 'completed_date'],
            ['label' => 'Ready', 'dateField' => 'ready_date']
        ];

        $currentStepLabel = "Not Started";
        $latestCompletedIndex = null;

        // Loop to find the last completed step
        foreach ($steps as $i => $step) {
            if (!empty($row[$step['dateField']])) {
                $latestCompletedIndex = $i;
                $currentStepLabel = $step['label'];
            }
        }

        // If no step completed yet
        if ($latestCompletedIndex === null) {
            $currentStepLabel = "Not Started";
        }

        // If all steps completed
        if ($latestCompletedIndex === count($steps) - 1) {
            $currentStepLabel = "Done";
        }

        // Output table row with data from the database
        $output .= "<tr data-project-id='" . htmlspecialchars($row['project_id']) . "' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'>
        <td class='align-middle text-center hide-print " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "'>
            <button class='btn editBtn'><i class='fa-regular fa-pen-to-square'></i></button>
            <button class='deleteProjectDetailsBtn btn' data-project-id='" . htmlspecialchars($row['project_id']) . "' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'><i class='fa-regular fa-trash-can text-danger'></i></button>
            <button class='btn' data-bs-toggle='collapse' data-bs-target='#" . $uniqueId . "'>
                <i class='fa-solid fa-arrows-spin text-warning'></i>
            </button>
        </td>
        <td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='item_number_" . $row['project_details_id'] . "' data-original-item-number='" . htmlspecialchars($item_number) . "'>" . htmlspecialchars($item_number) . "</td>
        <td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='description_" . $row['project_details_id'] . "'>" . htmlspecialchars($row['description']) . "</td>";

        $output .= "<td class='align-middle text-center py-3 "
            . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10')
            . "' id='current_step_" . $row['project_details_id'] . "'>"
            . htmlspecialchars($currentStepLabel) .
            "</td>";

        $output .= "<td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='date_" . $row['project_details_id'] . "'>";

        if (!empty($row['date'])) {
            $formattedDate = date("d F Y", strtotime($row['date']));
            $output .= htmlspecialchars($formattedDate);
        } else {
            $output .= "<span style='color: red; font-weight: bold;'>N/A</span>";
        }

        $output .= "</td>";

        // Revised delivery date column
        $output .= "<td class='align-middle text-center py-3 "
            . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10')
            . "' id='revisedDeliveryDate_" . $row['project_details_id'] . "'>";

        if (!empty($row['revised_delivery_date'])) {
            $formattedDate = date("d F Y", strtotime($row['revised_delivery_date']));
            $output .= htmlspecialchars($formattedDate);
        } else {
            $output .= "<span style='color: red; font-weight: bold;'>N/A</span>";
        }

        $output .= "</td>";

        // Format unit price with negative values in red
        $unitPrice = $row['unit_price'];
        $output .= "<td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='unitprice_" . $row['project_details_id'] . "'>";

        if ($unitPrice < 0) {
            $output .= "<span style='color: red;'>-" . "$" . number_format(abs($unitPrice), 2) . "</span>";
        } else {
            $output .= "$" . number_format($unitPrice, 2);
        }

        $output .= "</td>";

        $output .= "<td class='align-middle text-center py-3  " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='quantity_" . $row['project_details_id'] . "'>" . $row['quantity'] . "</td>
    <td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='sub_total_" . $row['project_details_id'] . "'>";

        // Format sub_total with negative values in red
        $subTotal = $row['sub_total'];
        if ($subTotal < 0) {
            $output .= "<span style='color: red;'>-" . "$" . number_format(abs($subTotal), 2) . "</span>";
        } else {
            $output .= "$" . number_format($subTotal, 2);
        }

        $output .= "</td>";

        $output .= "<td class='py-3 align-middle text-center  " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "'>
        <input type='checkbox' class='form-check-input' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'
        " . ($row['invoiced'] == 1 ? 'checked' : '') . " 
        style='transform: scale(1.5);'>
    </td>";

        // Add the "approved_by" column at the end
        $output .= "<td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='approved_by_" . $row['project_details_id'] . "'>";

        // Check if 'approved_by' is NULL or empty and display 'N/A' if so
        if (empty($row['approved_by'])) {
            $output .= "<span style='color: red; font-weight: bold;'>N/A</span>";
        } else {
            $output .= htmlspecialchars($row['approved_first_name'] . ' ' . $row['approved_last_name']);
        }

        $steps = [
            [
                'label' => 'Drawing Issued',
                'icon' => 'fa-palette',
                'dateField' => 'drawing_issued_date',
                'firstNameField' => 'drawing_issued_first_name',
                'lastNameField' => 'drawing_issued_last_name',
                'employeeIdField' => 'drawing_issued_employee_id'
            ],
            [
                'label' => 'Programming',
                'icon' => 'fa-pencil-ruler',
                'dateField' => 'programming_date',
                'firstNameField' => 'programming_first_name',
                'lastNameField' => 'programming_last_name',
                'employeeIdField' => 'programming_employee_id'
            ],
            [
                'label' => 'Ready to Handover',
                'icon' => 'fa-box-open',
                'dateField' => 'ready_to_handover_date',
                'firstNameField' => 'ready_to_handover_first_name',
                'lastNameField' => 'ready_to_handover_last_name',
                'employeeIdField' => 'ready_to_handover_employee_id'
            ],
            [
                'label' => 'Handed Over to Electrical',
                'icon' => 'fa-bolt',
                'dateField' => 'handed_over_to_electrical_date',
                'firstNameField' => 'handed_over_to_electrical_first_name',
                'lastNameField' => 'handed_over_to_electrical_last_name',
                'employeeIdField' => 'handed_over_to_electrical_employee_id'
            ],
            [
                'label' => 'Testing',
                'icon' => 'fa-charging-station',
                'dateField' => 'testing_date',
                'firstNameField' => 'testing_first_name',
                'lastNameField' => 'testing_last_name',
                'employeeIdField' => 'testing_employee_id'
            ],
            [
                'label' => 'Completed',
                'icon' => 'fa-check-circle',
                'dateField' => 'completed_date',
                'firstNameField' => 'completed_first_name',
                'lastNameField' => 'completed_last_name',
                'employeeIdField' => 'completed_employee_id'
            ],
            [
                'label' => 'Ready for Delivery',
                'icon' => 'fa-truck',
                'dateField' => 'ready_date',
                'firstNameField' => 'ready_first_name',
                'lastNameField' => 'ready_last_name',
                'employeeIdField' => 'ready_employee_id'
            ]
        ];

        $output .= "</td></tr>
        <tr class='collapse bg-light' id='" . $uniqueId . "'>
            <td colspan='100%'>
                <div class='p-3'>
                    <div class='d-flex justify-content-between align-items-center stepper-container " . (!empty($row['hold_date']) ? 'held' : '') . "' style='position: relative;'>
                                 
        ";

        $latestStepIndex = null;
        // Find the latest active step (first not completed)
        foreach ($steps as $i => $step) {
            if (empty($row[$step['dateField']])) {
                $latestStepIndex = $i;
                break;
            }
        }

        foreach ($steps as $i => $step) {
            $completed = !empty($row[$step['dateField']]);
            $stepInfo = 'N/A';
            $employeeId = '';

            if ($completed) {
                $date = date('d M Y', strtotime($row[$step['dateField']]));
                $name = !empty($row[$step['firstNameField']]) && !empty($row[$step['lastNameField']])
                    ? $row[$step['firstNameField']] . ' ' . $row[$step['lastNameField']]
                    : 'N/A';
                $stepInfo = $date . ' - ' . $name;
                $employeeId = !empty($row[$step['employeeIdField']]) ? $row[$step['employeeIdField']] : '';
            }

            // Check if this step is held
            $isHeld = ($latestStepIndex === $i && !empty($row['hold_date']));
            $holdNote = $isHeld ? $row['hold_note'] : '';
            $output .= "<div class='step text-center' 
            data-step='" . ($i + 1) . "' 
            data-project-details-id='{$row['project_details_id']}' 
            data-employee-id='" . htmlspecialchars($employeeId) . "' 
            style='cursor:pointer; flex:1;'>
        <div class='step-circle " . ($completed ? 'text-success' : 'text-secondary') . "'>
            <i class='fa {$step['icon']}'></i>
        </div>
        <div class='step-label fw-bold mt-1 " . ($completed ? 'text-success' : 'text-secondary') . "' style='font-size: 12px;'>{$step['label']}</div>
        <div class='step-date' style='font-size: 8px;'>" . htmlspecialchars($stepInfo) . "</div>";

            // Show Hold / Unhold button only for the latest active step
            if ($latestStepIndex === $i) {
                if ($isHeld) {
                    // Get hold info
                    $holdDateFormatted = date('d M Y', strtotime($row['hold_date']));
                    $holdNote = htmlspecialchars($row['hold_note']);
                    $holdById = $row['hold_by'];

                    // Fetch employee name from your employees table
                    $empQuery = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                    $stmt = $conn->prepare($empQuery);
                    $stmt->bind_param("i", $holdById);
                    $stmt->execute();
                    $stmt->bind_result($firstName, $lastName);
                    $stmt->fetch();
                    $stmt->close();

                    $holdByName = htmlspecialchars(trim($firstName . ' ' . $lastName) ?: 'Unknown');

                    $output .= "
                    <div class='hold-display'>
                        <span class='text-danger fw-bold' style='font-size: 10px; margin: 0;'>
                            Hold: \"{$holdNote}\"
                            <small>by {$holdByName} ({$holdDateFormatted})</small>
                        </span> <br>
                        <button class='btn btn-sm btn-warning p-1 unholdBtn' 
                            data-project-details-id='{$row['project_details_id']}' 
                            style='font-size: 8px; margin-top:2px;'>Unhold</button>
                    </div>";
                } else {
                    $output .= "
                    <button class='btn btn-sm btn-secondary p-1 holdBtn' 
                        data-step-index='{$i}' 
                        data-project-details-id='{$row['project_details_id']}' 
                        style='font-size: 8px; cursor: pointer;'>
                        Hold
                    </button>
                    <div class='hold-input d-none mt-1'>
                        <textarea rows='1' type='text' class='form-control form-control-sm noteInput mb-1' style='font-size: 10px;' placeholder='Enter note...'></textarea>
                        <div>
                            <button class='btn btn-sm btn-success p-1 confirmHold' style='font-size: 8px;'><i class='fa-solid fa-check'></i></button>
                            <button class='btn btn-sm btn-danger p-1 cancelHold' style='font-size: 8px;'><i class='fa-solid fa-xmark'></i></button>
                        </div>
                    </div>";
                }
            }

            $output .= "</div>";

            if ($i < count($steps) - 1) {
                $output .= "<div class='step-line' style='flex:1; height:2px; background:#ccc; margin:0 5px;'></div>";
            }
        }

        $output .= "<button class='ms-4 btn btn-sm btn-info fw-bold editProcessBtn' 
        data-project-details-id='" . $row['project_details_id'] . "' 
        style='font-size: 10px; margin-left:5px;'>Edit</button>
        </div>
    </div>
</td>
</tr>";

        $item_number += 1;
        $counter++;
    }

    echo $output; // Return the generated table rows to the client
} else {
    echo "No project ID provided!";
}
?>

<script>
    // Function to calculate the total
    function calculateTotal(projectId) {
        let total = 0;

        // Iterate over each row in the table
        $('#projectDetailsTbody tr').each(function () {
            // Get the sub-total value using the `id` attribute (e.g., sub_total_123)
            const subTotal = parseFloat($(this).find('[id^="sub_total_"]').text().replace(/[^0-9.-]+/g, ""));
            if (!isNaN(subTotal)) {
                total += subTotal;
            }
        });

        // Update the total in the DOM
        $("#totalValue").text("$" + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        // Update the tax in the DOM
        $("#taxValue").text("$" + (total * 0.1).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        // Update totalWithGst
        $("#totalWithGstValue").text("$" + ((total * 0.1) + total).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));

        // Use AJAX to insert the total into the projects table
        $.ajax({
            url: '../AJAXphp/update_project_details.php', // Update with your actual server path
            method: 'POST',
            data: {
                action: 'update_project_value',
                projectId: projectId,
                totalValue: total
            },
            success: function (response) {
                console.log('Total value updated successfully:', response);
            },
            error: function (xhr, status, error) {
                console.error('Error updating total value:', error);
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (event.target && event.target.closest('.editBtn')) {
            const row = event.target.closest('tr');

            // Get the edit and delete buttons cell
            const editDeleteCell = row.querySelector('td:first-child');

            // Replace the edit button with Save and Cancel buttons
            editDeleteCell.innerHTML = `
        <div class="align-middle"> 
            <button class="btn btn-success saveBtn">Save</button>
            <button class="btn btn-danger cancelBtn">Cancel</button>
        </div>
    `;

            // Loop through each editable td in the row, excluding the "item_number"
            row.querySelectorAll('td[id^="date_"],td[id^="revisedDeliveryDate_"], td[id^="description_"], td[id^="unitprice_"], td[id^="quantity_"]').forEach(function (td) {
                let id = td.id.split('_')[0]; // Extract the main column name (date, description, unitprice, etc.)
                const value = td.textContent.trim();

                // Remove the "$" symbol and handle numeric fields
                if (id === "unitprice" || id === "sub_total") {
                    // Use 'text' type to allow formatting with commas
                    let numericValue = value.replace('$', '').replace(/,/g, '').trim(); // Remove $ and commas
                    td.innerHTML = `<input type="text" class="form-control" value="${parseFloat(numericValue).toLocaleString()}" id="edit_${id}_${row.dataset.id}" required 
            oninput="this.value = this.value.replace(/[^0-9,.]/g, '')">`; // Allow only numbers and commas
                } else if (id === "date") {
                    if (value === "N/A") {
                        // If N/A, provide empty date input
                        td.innerHTML = `<input type="date" class="form-control" value="" id="edit_${id}_${row.dataset.id}">`;
                    } else {
                        // Try to parse the date
                        let dateValue = new Date(value);
                        if (!isNaN(dateValue)) {
                            let offset = dateValue.getTimezoneOffset() * 60000;
                            let adjustedDate = new Date(dateValue.getTime() - offset);
                            let isoDate = adjustedDate.toISOString().split('T')[0];
                            td.innerHTML = `<input type="date" class="form-control" value="${isoDate}" id="edit_${id}_${row.dataset.id}">`;
                        } else {
                            // If somehow invalid, fallback to empty
                            td.innerHTML = `<input type="date" class="form-control" value="" id="edit_${id}_${row.dataset.id}">`;
                        }
                    }
                } else if (id === "revisedDeliveryDate") {
                    if (value === "N/A") {
                        // If N/A, provide empty date input
                        td.innerHTML = `<input type="date" class="form-control" value="" id="edit_${id}_${row.dataset.id}">`;
                    } else {
                        // Try to parse the date
                        let dateValue = new Date(value);
                        if (!isNaN(dateValue)) {
                            let offset = dateValue.getTimezoneOffset() * 60000;
                            let adjustedDate = new Date(dateValue.getTime() - offset);
                            let isoDate = adjustedDate.toISOString().split('T')[0];
                            td.innerHTML = `<input type="date" class="form-control" value="${isoDate}" id="edit_${id}_${row.dataset.id}">`;
                        } else {
                            // If somehow invalid, fallback to empty
                            td.innerHTML = `<input type="date" class="form-control" value="" id="edit_${id}_${row.dataset.id}">`;
                        }
                    }
                }

                else if (id === "quantity") {
                    // Use 'text' to allow numeric formatting
                    td.innerHTML = `<input type="text" class="form-control" value="${value}" id="edit_${id}_${row.dataset.id}" required 
            oninput="this.value = this.value.replace(/[^0-9]/g, '')">`; // Allow only whole numbers
                } else {
                    // For other fields, create an <input type="text">
                    td.innerHTML = `<input type="text" class="form-control" value="${value}" id="edit_${id}_${row.dataset.id}" required>`;
                }
            });

            // Change the Edit button to a Save button
            const editBtn = row.querySelector('.editBtn');
            editBtn.innerHTML = '<i class="fa-regular fa-save"></i>';
            editBtn.classList.remove('editBtn');
            editBtn.classList.add('saveBtn');
        }
    });

    // Cancel button functionality to revert changes
    document.addEventListener('click', function (event) {
        if (event.target && event.target.closest('.cancelBtn')) {
            const row = event.target.closest('tr');
            const projectId = row.dataset.projectId;

            // Call the fetchProjectDetails function to reload the project details
            fetchProjectDetails(projectId);

            // Change the Save/Cancel buttons back to Edit button
            const editDeleteCell = row.querySelector('td:first-child');
            editDeleteCell.innerHTML = `
            <button class="btn editBtn"><i class="fa-regular fa-pen-to-square"></i></button>
            <button class="deleteProjectDetailsBtn btn" data-project-id="${row.dataset.projectId}" data-project-details-id="${row.dataset.projectDetailsId}"><i class="fa-regular fa-trash-can text-danger"></i></button>
        `;
        }
    });

    // Save button functionality to submit updated data
    document.addEventListener('click', function (event) {
        if (event.target && event.target.closest('.saveBtn')) {
            const row = event.target.closest('tr');
            const projectDetailsId = row.dataset.projectDetailsId;
            const projectId = row.dataset.projectId;

            // Flag to track if there's any error
            let hasError = false;

            // Get the updated values from the input fields
            const updatedValues = {};
            row.querySelectorAll('td[id^="date_"], td[id^="revisedDeliveryDate"], td[id^="description_"], td[id^="unitprice_"], td[id^="quantity_"]').forEach(function (td) {
                const id = td.id.split('_')[0];
                const newValue = td.querySelector('input').value.trim();

                // Check if the description, unitprice, or quantity is empty or null
                if ((id === "description" || id === "unitprice" || id === "quantity") && (!newValue || newValue === "")) {
                    hasError = true;
                    // Apply Bootstrap error class to highlight the input
                    td.querySelector('input').classList.add('is-invalid');
                } else {
                    // Remove error class if value is valid
                    td.querySelector('input').classList.remove('is-invalid');
                    td.querySelector('input').classList.add('is-valid');
                }

                // For unitprice, remove the dollar sign and ensure it's a numeric value
                if (id === "unitprice" && !isNaN(parseFloat(newValue.replace('$', '').replace(',', '')))) {
                    updatedValues[id] = parseFloat(newValue.replace('$', '').replace(',', '')).toFixed(2);
                } else if (id !== "unitprice") {
                    updatedValues[id] = newValue;
                }
            });

            // If there's any error, stop the form submission
            if (hasError) {
                return; // Stop the form submission
            }

            // Send the updated data to the server via AJAX for saving
            const formData = new FormData();
            formData.append('project_details_id', projectDetailsId);
            Object.keys(updatedValues).forEach(function (key) {
                formData.append(key, updatedValues[key]);
            });

            fetch('../AJAXphp/update_project_details.php', { // Your PHP update script
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // If save is successful, update the table cells with new data
                        Object.keys(updatedValues).forEach(function (key) {
                            row.querySelector(`#${key}_${projectDetailsId}`).textContent = updatedValues[key];
                        });

                        // Reapply formatting for unit price
                        const unitPrice = updatedValues['unitprice'];
                        row.querySelector(`#unitprice_${projectDetailsId}`).textContent = `$${parseFloat(unitPrice).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                        // Update sub-total
                        const quantity = parseInt(row.querySelector(`#quantity_${projectDetailsId}`).textContent.trim(), 10);
                        const subTotal = (parseFloat(unitPrice) * quantity).toFixed(2);
                        row.querySelector(`#sub_total_${projectDetailsId}`).textContent = `$${parseFloat(subTotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                        // Update total
                        calculateTotal(projectId);

                        // Fetch updated project details after saving
                        fetchProjectDetails(projectId);

                        // Change the Save/Cancel buttons back to Edit button
                        const editDeleteCell = row.querySelector('td:first-child');
                        editDeleteCell.innerHTML = `
                        <button class="btn editBtn"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="deleteProjectDetailsBtn btn" data-project-id="${row.dataset.projectId}" data-project-details-id="${row.dataset.projectDetailsId}"><i class="fa-regular fa-trash-can text-danger"></i></button>
                    `;
                    } else {
                        alert('Failed to update the project details!');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    });
</script>

<script>
    let requestInProgress = false;
    document.addEventListener('change', function (event) {
        if (!event.target.classList.contains('form-check-input')) return;

        if (requestInProgress) return;
        requestInProgress = true;

        const checkbox = event.target;
        const row = checkbox.closest('tr');
        const projectDetailsId = row.dataset.projectDetailsId;
        const invoiced = checkbox.checked ? 1 : 0;
        const projectId = row.dataset.projectId;

        let approvedBy = checkbox.checked ? document.getElementById('loginEmployeeId').value : '';

        fetch('../AJAXphp/update_project_invoiced_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `project_details_id=${encodeURIComponent(projectDetailsId)}&invoiced=${encodeURIComponent(invoiced)}&approvedBy=${encodeURIComponent(approvedBy)}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update row colors
                    row.querySelectorAll('td').forEach(cell => {
                        cell.classList.toggle('bg-success', invoiced);
                        cell.classList.toggle('bg-danger', !invoiced);
                        cell.classList.add('bg-opacity-10');
                    });

                    // Update approved_by column
                    const approvedByTd = row.querySelector(`#approved_by_${projectDetailsId}`);
                    if (invoiced && data.approvedByName) {
                        approvedByTd.innerHTML = `<span>${data.approvedByName}</span>`;
                    } else {
                        approvedByTd.innerHTML = `<span style="color:red; font-weight:bold;">N/A</span>`;
                    }

                } else {
                    checkbox.checked = !checkbox.checked;
                    alert('Failed to update status: ' + data.message);
                }
                requestInProgress = false;
            })
            .catch(err => {
                console.error(err);
                checkbox.checked = !checkbox.checked;
                requestInProgress = false;
            });
    });
</script>

<script>
    // Function to fetch project details using AJAX
    function fetchProjectDetails(projectId) {
        $.ajax({
            url: '../AJAXphp/fetch_project_details.php', // PHP file to fetch project details
            method: 'POST',
            data: {
                project_id: projectId // Send the project ID to the server
            },
            success: function (response) {
                // Update the project details table with the new data
                $('#projectDetailsTbody').html(response);

                // Recalculate total after updating table
                calculateTotal(projectId);
            },
            error: function () {
                alert("An error occurred while fetching project details.");
            }
        });
    }
</script>

<script>
    function disableCheckboxIfNotAdmin() {
        let userRole = document.getElementById("userRole").value;
        let checkboxes = document.querySelectorAll(".form-check-input");

        // Disable checkboxes for non-full control roles
        if (userRole !== "full control") {
            checkboxes.forEach(function (checkbox) {
                checkbox.disabled = true;
            });
        }

        // Loop through each row
        document.querySelectorAll("tr").forEach(function (row) {
            const td = row.querySelector("td.hide-print");
            const editBtn = td?.querySelector(".btn.editBtn");
            const deleteBtn = td?.querySelector(".btn.deleteProjectDetailsBtn");

            if (!td) return; // skip if no action td

            if (userRole === "full control") {
                td.style.display = "";
                if (editBtn) editBtn.style.display = "";
                if (deleteBtn) deleteBtn.style.display = "";
            } else if (userRole === "modify 1" || userRole === "modify 2") {
                td.style.display = "";
                if (editBtn) editBtn.style.display = "";
                if (deleteBtn) deleteBtn.style.display = "none";
            } else {
                td.style.display = "none"; // hide the whole <td>
            }
        });
    }

    disableCheckboxIfNotAdmin();
</script>

<script>
    document.addEventListener("click", function (e) {
        const step = e.target.closest(".step");
        if (!step) return;

        const stepperContainer = step.closest(".stepper-container");
        const projectDetailsId = step.dataset.projectDetailsId;
        const employeeId = document.getElementById("loginEmployeeId").value;

        // --- Prevent toggle if clicking inside hold input area ---
        if (e.target.closest(".hold-input") &&
            !e.target.closest(".confirmHold") &&
            !e.target.closest(".cancelHold")) {
            return; // typing in input, do nothing
        }

        // Check if the stepper is on hold
        const isHeld = stepperContainer.classList.contains("held");

        // --- Hold / Unhold button actions ---
        if (e.target.closest(".holdBtn")) {
            step.querySelector(".hold-input").classList.remove("d-none");
            e.target.classList.add("d-none");
            return;
        }

        if (e.target.closest(".cancelHold")) {
            step.querySelector(".hold-input").classList.add("d-none");
            step.querySelector(".holdBtn").classList.remove("d-none");
            return;
        }

        if (e.target.closest(".confirmHold")) {
            const note = step.querySelector(".noteInput").value.trim();
            if (!note) { alert("Please enter a note"); return; }

            fetch("../AJAXphp/update_project_progress.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `project_details_id=${projectDetailsId}&note=${encodeURIComponent(note)}&employee_id=${employeeId}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update UI directly
                        step.querySelector(".hold-input").remove();
                        step.querySelector(".holdBtn")?.remove();

                        const holdDiv = document.createElement("div");
                        holdDiv.classList.add("hold-display");
                        holdDiv.innerHTML = `
                <span class='text-danger fw-bold' style='font-size: 10px; margin: 0;'>
                    Hold: "${note}"
                    <small>by ${data.employee_name} (${data.date})</small>
                </span> <br>
                <button class='btn btn-sm btn-warning p-1 unholdBtn' 
                    data-project-details-id='${projectDetailsId}' 
                    style='font-size: 8px; margin-top:2px;'>Unhold</button>
            `;
                        step.appendChild(holdDiv);

                        const stepperContainer = step.closest(".stepper-container");
                        stepperContainer.classList.add("held");
                        // Set the hold employee ID data attribute
                        stepperContainer.dataset.holdByEmployeeId = employeeId;
                    } else {
                        alert("Failed to save hold note");
                    }
                });
            return;
        }

        if (e.target.closest(".unholdBtn")) {
            const stepperContainer = e.target.closest(".stepper-container");
            const holdByEmployeeId = stepperContainer.dataset.holdByEmployeeId || '';
            const currentEmployeeId = document.getElementById("loginEmployeeId").value;

            // Check if current user is the one who placed the hold
            if (holdByEmployeeId && holdByEmployeeId !== currentEmployeeId) {
                alert("Only the person who placed this hold can remove it.");
                return; // Stop execution here
            }

            fetch("../AJAXphp/update_project_progress.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `project_details_id=${projectDetailsId}&unhold=1&employee_id=${currentEmployeeId}` // FIX: changed employeeId to currentEmployeeId
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Remove hold display
                        step.querySelector(".hold-display")?.remove();

                        // Add Hold button again
                        const holdDiv = document.createElement("div");
                        holdDiv.innerHTML = `
                <button class='btn btn-sm btn-secondary p-1 holdBtn' 
                    data-step-index='${step.dataset.step - 1}' 
                    data-project-details-id='${projectDetailsId}' 
                    style='font-size: 8px; cursor: pointer;'>
                    Hold
                </button>
                <div class='hold-input d-none mt-1'>
                    <textarea rows='1' type='text' class='form-control form-control-sm noteInput mb-1' style='font-size: 10px;' placeholder='Enter note...'></textarea>
                    <div>
                        <button class='btn btn-sm btn-success p-1 confirmHold' style='font-size: 8px;'><i class='fa-solid fa-check'></i></button>
                        <button class='btn btn-sm btn-danger p-1 cancelHold' style='font-size: 8px;'><i class='fa-solid fa-xmark'></i></button>
                    </div>
                </div>
            `;
                        step.appendChild(holdDiv);

                        step.closest(".stepper-container").classList.remove("held");
                    } else {
                        alert("Failed to unhold step: " + (data.error || 'Unknown error'));
                    }
                });
            return;
        }

        // --- Prevent step toggle if container is held ---
        if (isHeld &&
            !e.target.closest(".unholdBtn") &&
            !e.target.closest(".confirmHold") &&
            !e.target.closest(".cancelHold")) {
            alert("This project is on hold. No actions allowed until unhold.");
            return;
        }

        // --- Sequential step toggle logic ---
        const allSteps = Array.from(stepperContainer.querySelectorAll(".step"));
        const stepNumber = parseInt(step.dataset.step);
        const isDone = step.querySelector(".step-circle").classList.contains("text-success");

        if (!isDone) {
            // Toggling ON - check previous steps
            if (stepNumber > 1 && !allSteps[stepNumber - 2].querySelector(".step-circle").classList.contains("text-success")) {
                alert("Complete previous process first.");
                return;
            }
        } else {
            // Toggling OFF - check authorization and later steps
            const stepEmployeeId = step.dataset.employeeId;

            // Check if current user is the one who completed this step
            if (stepEmployeeId !== employeeId) {
                alert("Only the person who completed this step can uncheck it.");
                return;
            }

            // Check if later steps are completed
            if (allSteps.slice(stepNumber).some(s => s.querySelector(".step-circle").classList.contains("text-success"))) {
                alert("Unable to uncheck this process because a later process is already completed.");
                return;
            }
        }

        // Toggle step UI
        step.querySelector(".step-circle").classList.toggle("text-success", !isDone);
        step.querySelector(".step-circle").classList.toggle("text-secondary", isDone);
        step.querySelector(".step-label").classList.toggle("text-success", !isDone);
        step.querySelector(".step-label").classList.toggle("text-secondary", isDone);
        
        // IMMEDIATELY update the employee ID in the DOM
        if (!isDone) {
            // Toggling ON - set current user as the employee
            step.dataset.employeeId = employeeId;
        } else {
            // Toggling OFF - clear the employee ID
            step.dataset.employeeId = '';
        }

        step.querySelector(".step-date").textContent = !isDone
            ? new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
            : 'N/A';

        fetch("../AJAXphp/update_project_progress.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `toggle_step=${stepNumber}&project_details_id=${projectDetailsId}&employee_id=${employeeId}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    step.querySelector(".step-date").textContent = data.new_date;
                    const hasData = data.new_date !== 'N/A';
                    step.querySelector(".step-circle").classList.toggle("text-success", hasData);
                    step.querySelector(".step-circle").classList.toggle("text-secondary", !hasData);
                    refreshStepsUI(stepperContainer);

                    // Trigger current step update
                    updateCurrentStepUI(projectDetailsId, stepperContainer);
                }
            })
            .catch(err => console.error(err));
    });

    // --- Helper function to refresh stepper UI and Hold button ---
    function refreshStepsUI(stepperContainer) {
        const allSteps = Array.from(stepperContainer.querySelectorAll(".step"));

        // Remove existing hold buttons and hold-inputs
        allSteps.forEach(s => {
            const holdBtn = s.querySelector(".holdBtn");
            if (holdBtn) holdBtn.remove();
            const holdInput = s.querySelector(".hold-input");
            if (holdInput) holdInput.remove();
        });

        // Check if any step is on hold
        const isAnyStepOnHold = allSteps.some(s => s.dataset.holdDate && s.dataset.holdDate !== "");

        if (isAnyStepOnHold) {
            // If process is on hold, don't show any hold button
            return;
        }

        // Find the latest completed step
        let latestCompletedIndex = -1;
        allSteps.forEach((s, i) => {
            if (s.querySelector(".step-circle").classList.contains("text-success")) {
                latestCompletedIndex = i;
            }
        });

        const nextStepIndex = latestCompletedIndex + 1;
        if (nextStepIndex < allSteps.length) {
            const nextStep = allSteps[nextStepIndex];
            // Add Hold button + input dynamically
            const holdDiv = document.createElement("div");
            holdDiv.innerHTML = `
            <button class='btn btn-sm btn-secondary p-1 holdBtn' style='font-size: 8px; cursor: pointer;'>Hold</button>
            <div class='hold-input d-none mt-1'>
                <textarea type='text' class='form-control form-control-sm noteInput mb-1' style='font-size: 10px;' placeholder='Enter note...'></textarea>
                <div>
                    <button class='btn btn-sm btn-success p-1 confirmHold' style='font-size: 8px;'><i class='fa-solid fa-check'></i></button>
                    <button class='btn btn-sm btn-danger p-1 cancelHold' style='font-size: 8px;'><i class='fa-solid fa-xmark'></i></button>
                </div>
            </div>
        `;
            nextStep.appendChild(holdDiv);
        }
    }
</script>

<script>
    document.addEventListener("click", function (e) {
        const editBtn = e.target.closest(".editProcessBtn");
        if (editBtn) {
            const stepperContainer = editBtn.closest(".stepper-container");
            const steps = Array.from(stepperContainer.querySelectorAll(".step"));

            // Check if at least one step is completed
            const hasCompletedStep = steps.some(step => step.querySelector(".step-circle").classList.contains("text-success"));
            if (!hasCompletedStep) {
                alert("No process have been completed yet.");
                return; // do not show edit table
            }

            if (stepperContainer.parentNode.querySelector(".edit-step-table")) return; // only one table
            editBtn.style.display = 'none';

            let tableHTML = `<table class="table table-bordered table-sm mb-2 mt-4 align-middle">
        <thead>
            <tr>
                <th>Process</th>
                <th>Current</th>
                <th>Edit Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>`;

            steps.forEach(step => {
                const stepLabel = step.querySelector(".step-label").textContent;
                const stepDate = step.querySelector(".step-date").textContent;
                const stepNumber = parseInt(step.dataset.step, 10);
                const isCompleted = step.querySelector(".step-circle").classList.contains("text-success");

                if (isCompleted) {
                    let isoDate = '';
                    if (stepDate && stepDate !== 'N/A') {
                        const parts = stepDate.split(' ')[0].split('/');
                        if (parts.length === 3) isoDate = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    }

                    tableHTML += `<tr>
                <td>${stepLabel}</td>
                <td>${stepDate}</td>
                <td><input type="date" class="form-control form-control-sm editStepDate" value="${isoDate}"></td>
                <td><button class="btn btn-sm btn-success saveStepBtn" data-project-details-id="${editBtn.dataset.projectDetailsId}" data-step-number="${stepNumber}">Save</button></td>
            </tr>`;
                }
            });

            tableHTML += `</tbody></table>
        <div class='d-flex justify-content-center'> 
        <button class="btn btn-sm btn-danger cancelProcessDatesBtn">Cancel</button> </div>`;

            stepperContainer.style.display = 'none';
            const editDiv = document.createElement("div");
            editDiv.classList.add("edit-step-table");
            editDiv.innerHTML = tableHTML;
            stepperContainer.parentNode.appendChild(editDiv);
        }

        // Cancel editing
        if (e.target.closest(".cancelProcessDatesBtn")) {
            const editDiv = e.target.closest(".edit-step-table");
            if (!editDiv) return;
            const stepperContainer = editDiv.previousElementSibling;
            if (stepperContainer) stepperContainer.style.display = '';
            editDiv.remove();
            const editBtn = stepperContainer.querySelector(".editProcessBtn");
            if (editBtn) editBtn.style.display = '';
        }

        // Save single step
        if (e.target.closest(".saveStepBtn")) {
            const btn = e.target.closest(".saveStepBtn");
            const projectDetailsId = btn.dataset.projectDetailsId;
            const stepNumber = parseInt(btn.dataset.stepNumber, 10);
            const input = btn.closest("tr").querySelector(".editStepDate");
            const dateValue = input.value ? input.value : null;
            const employeeId = document.getElementById("loginEmployeeId").value; // keep as string for leading zeros

            fetch("../AJAXphp/update_project_progress.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    projectDetailsId,
                    steps: [{ stepIndex: stepNumber, date: dateValue, employeeId }]
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const stepperContainer = btn.closest(".edit-step-table").previousElementSibling;
                        const stepDivs = stepperContainer.querySelectorAll(".step");
                        stepDivs.forEach(stepDiv => {
                            if (parseInt(stepDiv.dataset.step, 10) === stepNumber) {
                                stepDiv.querySelector(".step-date").textContent = data.new_date;
                                updateCurrentStepUI(projectDetailsId, stepperContainer);
                            }
                        });
                        btn.closest("tr").remove();
                        const editDiv = document.querySelector(".edit-step-table");
                        if (editDiv && editDiv.querySelectorAll("tr").length === 1) {
                            stepperContainer.style.display = '';
                            editDiv.remove();
                            const editBtn = stepperContainer.querySelector(".editProcessBtn");
                            if (editBtn) editBtn.style.display = '';
                        }
                    } else {
                        alert("Failed to update step date");
                        console.error(data);
                    }
                })
                .catch(err => {
                    console.error("AJAX error:", err);
                    alert("Error updating step date");
                });
        }
    });
</script>

<script>
    function updateCurrentStepUI(projectDetailsId, stepsContainer) {
        const steps = Array.from(stepsContainer.querySelectorAll('.step'));
        let latestCompleted = -1; // start at -1 to represent “not started”

        steps.forEach((step, idx) => {
            const isDone = step.querySelector('.step-circle').classList.contains('text-success');
            if (isDone) latestCompleted = idx;
        });

        let label = "Not Started";
        if (latestCompleted >= 0) {
            // Show one step behind the current WIP
            if (latestCompleted < steps.length - 1) {
                label = steps[latestCompleted].querySelector('.step-label').textContent;
            } else {
                label = "Done";
            }
        }

        const td = document.getElementById(`current_step_${projectDetailsId}`);
        if (td) td.textContent = label;
    }
</script>