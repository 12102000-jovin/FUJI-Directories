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
    employees.first_name, 
    employees.last_name 
FROM 
    project_details 
LEFT JOIN 
    employees 
ON 
    project_details.approved_by = employees.employee_id 
WHERE 
    project_details.project_id = ?";

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
        $output .= "<tr data-project-id='" . htmlspecialchars($row['project_id']) . "' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'>
        <td class='align-middle text-center hide-print " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "'>
            <button class='btn editBtn'><i class='fa-regular fa-pen-to-square'></i></button>
            <button class='deleteProjectDetailsBtn btn' data-project-id='" . htmlspecialchars($row['project_id']) . "' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'><i class='fa-regular fa-trash-can text-danger'></i></button>
        </td>
        <td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='item_number_" . $row['project_details_id'] . "'>" . htmlspecialchars($item_number) . "</td>
        <td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='description_" . $row['project_details_id'] . "'>" . htmlspecialchars($row['description']) . "</td>
        <td class='align-middle text-center py-3 " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "' id='date_" . $row['project_details_id'] . "'>";

        if (!empty($row['date'])) {
            $formattedDate = date("d F Y", strtotime($row['date']));
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
            $output .= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        }

        $output .= "</td></tr>";

        $item_number += 1;
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
            row.querySelectorAll('td[id^="date_"], td[id^="description_"], td[id^="unitprice_"], td[id^="quantity_"]').forEach(function (td) {
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
            row.querySelectorAll('td[id^="date_"], td[id^="description_"], td[id^="unitprice_"], td[id^="quantity_"]').forEach(function (td) {
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
        if (event.target && event.target.classList.contains('form-check-input')) {
            if (requestInProgress) return; // Prevents further requests if already in progress

            requestInProgress = true;

            const checkbox = event.target;
            const row = checkbox.closest('tr');
            const projectDetailsId = row.dataset.projectDetailsId;
            const invoiced = checkbox.checked ? 1 : 0;
            const projectId = row.dataset.projectId;

            let approvedBy = document.getElementById('loginEmployeeId').value;

            // If the checkbox is unchecked, set approvedBy to null
            if (!checkbox.checked) {
                approvedBy = '';
            }

            console.log("Test", approvedBy);

            // Send AJAX request to update status
            fetch('../AJAXphp/update_project_invoiced_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `project_details_id=${encodeURIComponent(projectDetailsId)}&invoiced=${encodeURIComponent(invoiced)}&approvedBy=${encodeURIComponent(approvedBy)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Status updated successfully');

                        // Select all td elements within the row
                        const cells = row.querySelectorAll('td');

                        // Add or remove the classes based on the invoiced value
                        cells.forEach(cell => {
                            if (invoiced) {
                                cell.classList.add('bg-success', 'bg-opacity-10');
                                cell.classList.remove('bg-danger', 'bg-opacity-10');
                            } else {
                                cell.classList.remove('bg-success', 'bg-opacity-10');
                                cell.classList.add('bg-danger', 'bg-opacity-10');
                            }
                        });
                        requestInProgress = false;
                        fetchProjectDetails(projectId);

                    } else {
                        console.error('Failed to update status:', data.message);
                        // Optionally, revert the checkbox state and display an error message
                        checkbox.checked = !checkbox.checked;
                        alert('Failed to update status: ' + data.message); // Example error message
                        requestInProgress = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Optionally, revert the checkbox state
                    checkbox.checked = !checkbox.checked;
                });
        }
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
        let actionButtons = document.querySelectorAll(".btn.editBtn, .btn.deleteProjectDetailsBtn");

        if (userRole !== "full control") {
            // Disable checkboxes
            checkboxes.forEach(function (checkbox) {
                checkbox.disabled = true;
            });

            // Hide action buttons (edit and delete)
            actionButtons.forEach(function (button) {
                button.closest('td').style.display = 'none';
            });
        }
    }

    // Run the function on page load or after AJAX response
    disableCheckboxIfNotAdmin();
</script>