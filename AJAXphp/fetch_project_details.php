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
    $counter = 0;
    while ($row = $result->fetch_assoc()) {
        $uniqueId = 'detailsRow' . $counter;

        // Output table row with data from the database
        $output .= "<tr data-project-id='" . htmlspecialchars($row['project_id']) . "' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'>
        <td class='align-middle text-center hide-print " . ($row['invoiced'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') . "'>
            <button class='btn editBtn'><i class='fa-regular fa-pen-to-square'></i></button>
            <button class='deleteProjectDetailsBtn btn' data-project-id='" . htmlspecialchars($row['project_id']) . "' data-project-details-id='" . htmlspecialchars($row['project_details_id']) . "'><i class='fa-regular fa-trash-can text-danger'></i></button>
            <button class='btn' data-bs-toggle='collapse' data-bs-target='#" . $uniqueId . "'>
                <i class='fa-solid fa-arrows-spin text-warning'></i>
            </button>
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
            $output .= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        }

        $steps = [
            ['label' => 'Drawing Issued', 'icon' => 'fa-pencil-ruler'],
            ['label' => 'Programming', 'icon' => 'fa-code'],
            ['label' => 'Ready to Handover', 'icon' => 'fa-box-open'],
            ['label' => 'Handed Over to Electrical', 'icon' => 'fa-bolt'],
            ['label' => 'Testing', 'icon' => 'fa-vial'],
            ['label' => 'Completed', 'icon' => 'fa-check-circle'],
            ['label' => 'Ready for Delivery', 'icon' => 'fa-truck']
        ];

        $output .= "</td></tr>
        <tr class='collapse bg-light' id='" . $uniqueId . "'>
            <td colspan='100%'>
                <div class='p-3'>
                    <div class='d-flex justify-content-between align-items-center stepper-container' style='position: relative;'>
        ";

        for ($i = 0; $i < count($steps); $i++) {
            $step = $steps[$i];


            // Only Drawing Issued toggle first
            $completed = !empty($row['drawing_issued_date']) && $i === 0 ? true : false;

            $completed = !empty($row['drawing_issued_date']) && $i === 0 ? true : false;

            $output .= "<div class='step text-center' data-step='" . ($i + 1) . "' data-project-details-id='{$row['project_details_id']}' style='cursor:pointer; flex:1;'>
                        <div class='step-circle " . ($completed ? 'text-success' : 'text-secondary') . "'>
                            <i class='fa {$step['icon']}'></i>
                        </div>
                        <div class='step-label mt-1' style='font-size: 12px;'>{$step['label']}</div>
                        <div class='text-secondary step-date' style='font-size: 8px;'>"
                . ($completed ? date('d M Y', strtotime($row['drawing_issued_date'])) : 'N/A') .
                "</div>
                
                            <button class='btn btn-sm btn-secondary p-1' style='font-size: 8px; cursor: pointer;'>Hold</button>
                            <div class='d-flex align-items-center mt-1'> 
                                <input type='text' class='form-control me-1' style='font-size: 8px;' />
                                <button class='btn btn-sm btn-success p-1 me-1' style='font-size: 8px; cursor: pointer;'><i class='fa-solid fa-check'></i> </button>
                                <button class='btn btn-sm btn-danger p-1' style='font-size: 8px; cursor: pointer;'><i class='fa-solid fa-xmark'></i> </button>
                            </div>
                        </div>";

            if ($i < count($steps) - 1) {
                $output .= "<div class='step-line' style='flex:1; height:2px; background:#ccc; margin:0 5px;'></div>";
            }
        }

        $output .= "
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

if (isset($_POST['toggle_step']) && isset($_POST['project_details_id'])) {
    $project_details_id = $_POST['project_details_id'];

    // Only for step 1 (Drawing Issued)
    $check_sql = "SELECT drawing_issued_date FROM project_details WHERE project_details_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $project_details_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!empty($row['drawing_issued_date'])) {
        // Remove date (toggle off)
        $update_sql = "UPDATE project_details SET drawing_issued_date = NULL WHERE project_details_id = ?";
    } else {
        // Set today's date
        $update_sql = "UPDATE project_details SET drawing_issued_date = NOW() WHERE project_details_id = ?";
    }

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $project_details_id);
    $update_stmt->execute();

    $new_date = !empty($row['drawing_issued_date']) ? '' : date('d M Y');
    echo json_encode(['status' => 'success', 'new_date' => $new_date]);
    exit; // IMPORTANT! stop further output
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
    document.addEventListener('click', function (event) {
        const stepCircle = event.target.closest('.step-circle');
        if (!stepCircle) return; // only respond to step-circle clicks
        const stepDiv = stepCircle.closest('.step');
        const project_details_id = stepDiv.dataset.projectDetailsId;
        const stepNumber = stepDiv.dataset.step;
        const dateDiv = stepDiv.querySelector('.step-date');

        // toggle UI immediately
        let isDone = stepCircle.classList.contains('text-success');
        let newDate = isDone ? '' : new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        stepCircle.classList.toggle('text-success', !isDone);
        stepCircle.classList.toggle('text-secondary', isDone);
        dateDiv.textContent = newDate || 'N/A';

        // send AJAX
        fetch('../AJAXphp/update_project_progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `toggle_step=${stepNumber}&project_details_id=${project_details_id}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') {
                    // revert UI
                    stepCircle.classList.toggle('text-success', isDone);
                    stepCircle.classList.toggle('text-secondary', !isDone);
                    dateDiv.textContent = isDone ? newDate : 'N/A';
                }
            })
            .catch(err => console.error(err));
    });

</script>