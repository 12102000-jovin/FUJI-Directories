<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ========================= E D I T  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["projectIdToEdit"])) {
    $projectIdToEdit = $_POST["projectIdToEdit"];
    $projectNoToEdit = $_POST["projectNoToEdit"];
    $quoteNoToEdit = empty($_POST["quoteNoToEdit"]) ? NULL : $_POST['quoteNoToEdit'];
    $currentToEdit = $_POST["currentToEdit"];
    $projectNameToEdit = $_POST["projectNameToEdit"];
    $projectTypeToEdit = $_POST["projectTypeToEdit"];
    $customerToEdit = $_POST["customerToEdit"];
    $paymentTermsToEdit = $_POST["paymentTermsToEdit"];
    $projectEngineerToEdit = empty($_POST['projectEngineerToEdit']) ? NULL : $_POST['projectEngineerToEdit'];
    $customerAddressToEdit = empty($_POST['customerAddressToEdit']) ? NULL : $_POST['customerAddressToEdit'];

    // SQL statement
    $edit_document_sql = "UPDATE projects SET 
        project_no = ?, quote_no = ?, `current` = ?, project_name = ?, 
        project_type = ?, customer = ?, payment_terms = ?, project_engineer = ?, 
        customer_address = ? WHERE project_id = ?";
    $edit_document_result = $conn->prepare($edit_document_sql);

    // Check of the statement was successful
    if ($edit_document_result === false) {
        die("Error preparing the statement: " . $conn->error);
    }

    // Bind parameters
    $edit_document_result->bind_param(
        "sssssssssi",
        $projectNoToEdit,
        $quoteNoToEdit,
        $currentToEdit,
        $projectNameToEdit,
        $projectTypeToEdit,
        $customerToEdit,
        $paymentTermsToEdit,
        $projectEngineerToEdit,
        $customerAddressToEdit,
        $projectIdToEdit,
    );
    if ($edit_document_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        echo "<script>alert('Document edited successfully');</script>";
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

?>

<form method="POST" id="editProjectForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="resultError"></p>
    <div class="row">
        <input type="hidden" name="projectIdToEdit" id="projectIdToEdit">
        <div class="form-group col-md-6">
            <label for="projectNoToEdit" class="fw-bold">Project No.</label>
            <input type="text" name="projectNoToEdit" class="form-control" id="projectNoToEdit" required>
            <div class="invalid-feedback">
                Please provide the Project No.
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="quoteNoToEdit" class="fw-bold">Quote No.</label>
            <input type="text" name="quoteNoToEdit" class="form-control" id="quoteNoToEdit">
            <div class="invalid-feedback">
                Please provide the Quote No.
            </div>
        </div>
        <div class="form-group col-12 mt-3">
            <label for="projectNameToEdit" class="fw-bold">Project Name</label>
            <textarea type="text" name="projectNameToEdit" class="form-control" id="projectNameToEdit"
                required></textarea>
            <div class="invalid-feedback">
                Please provide the project name.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="currentToEdit" class="fw-bold">Current</label>
            <select name="currentToEdit" id="currentToEdit" class="form-select" required>
                <option disabled selected hidden></option>
                <option value="Archived">Archived</option>
                <option value="Completed">Completed</option>
                <option value="By Others">By Others</option>
                <option value="In Progress">In Progress</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <div class="invalid-feedback">
                Please provide the current status.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="projectTypeToEdit" class="fw-bold">Project Type</label>
            <select name="projectTypeToEdit" id="projectTypeToEdit" class="form-select" required>
                <option disabled selected hidden></option>
                <option value="Local">Local</option>
                <option value="Sitework">Sitework</option>
                <option value="IOR & Commissioning">IOR & Commissioning</option>
                <option value="Export">Export</option>
                <option value="R&D">R&D</option>
                <option value="Service">Service</option>
                <option value="PDC - International">PDC - International</option>
                <option value="PDC - Local">PDC - Local</option>
            </select>
            <div class="invalid-feedback">
                Please provide the Project Type.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="customerToEdit" class="fw-bold">Customer</label>
            <input type="text" name="customerToEdit" id="customerToEdit" class="form-control" required>
            <div class="invalid-feedback">
                Please provide the customer.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="paymentTermsToEdit" class="fw-bold">Payment Terms</label>
            <select name="paymentTermsToEdit" id="paymentTermsToEdit" class="form-select" required>
                <option disabled selected hidden></option>
                <option value="COD">COD</option>
                <option value="0 Days">0 Days</option>
                <option value="30 Days">30 Days</option>
                <option value="60 Days">60 Days</option>
            </select>
            <div class="invalid-feedback">
                Please provide the payment terms.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="projectEngineerToEdit" class="fw-bold">Project Engineer</label>
            <div id="projectEngineerDropdownToEdit" class="dropdown">
                <button class="btn btn-dark dropdown-toggle" type="button" id="dropdownMenuButton"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    Select Project Engineer(s)
                </button>
                <ul class="dropdown-menu engineer-dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <!-- Search box to filter the dropdown list -->
                    <li>
                        <input type="text" id="searchEngineerToEdit" class="form-control"
                            placeholder="Search engineers..." />
                    </li>
                    <!-- List of engineers (PHP loop) -->
                    <?php
                    foreach ($employees as $row) {
                        echo '<li class="engineer-item">
                    <input type="checkbox" class="dropdown-item" value="' . $row['employee_id'] . '" id="engineer_' . $row['employee_id'] . '" name="project_engineer[]">
                    <label for="engineer_' . $row['employee_id'] . '">' .
                            htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                            htmlspecialchars($row['employee_id']) . ')</label>
                </li>';
                    }
                    ?>
                </ul>
            </div>
            <small id="selectedEngineerToEdit"></small>
        </div>
        <div class="form-group col-md-12 mt-3">
            <label for="customerAddressToEdit" class="fw-bold">Customer Address</label>
            <textarea type="text" name="customerAddressToEdit" class="form-control"
                id="customerAddressToEdit"></textarea>
        </div>
        <div class="d-flex justify-content-center mt-4 mb-4">
            <button class="btn btn-dark" name="editProject" type="submit" id="editProjectBtn">Edit Project</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editProjectForm = document.getElementById("editProjectForm");
        const projectEngineerDropdown = document.getElementById("projectEngineerDropdownToEdit");
        const projectNo = document.getElementById("projectNoToEdit");
        const quoteNo = document.getElementById("quoteNoToEdit");
        const projectId = document.getElementById("projectIdToEdit");
        const errorMessage = document.getElementById("resultError");
        const selectedEngineerText = document.getElementById("selectedEngineerToEdit");
        const checkboxes = projectEngineerDropdown.querySelectorAll('input[type="checkbox"]');
        const searchInput = document.getElementById("searchEngineerToEdit");
        const listItems = document.querySelectorAll('.engineer-item'); // Get all engineer list items

        // Function to check for duplicate documents
        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-project-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    projectNo: projectNo.value,
                    quoteNo: quoteNo.value,
                    projectId: projectId.value,
                })
            })
                .then(response => response.text())
                .then(data => {
                    console.log('Server Response:', data);
                    return data === '0'; // Return true if no duplicate (0), false if duplicate (1)
                });
        }

        // Form validation
        function validateForm() {
            return checkDuplicateDocument().then(isDuplicateValid => {
                return isDuplicateValid;
            });
        }

        // Handle form submission
        editProjectForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const selectedEngineers = [];
            const checkboxes = projectEngineerDropdown.querySelectorAll('input[type="checkbox"]:checked');

            checkboxes.forEach(checkbox => {
                selectedEngineers.push(checkbox.value);
            });

            if (selectedEngineers.length > 0) {
                // Create a hidden input field to pass the selected engineers
                const engineersInput = document.createElement('input');
                engineersInput.type = 'hidden';
                engineersInput.name = 'projectEngineerToEdit';  // Use a single field name
                engineersInput.value = selectedEngineers.join(',');  // Join selected engineers as a string
                editProjectForm.appendChild(engineersInput);
            }

            // Check validity of the form
            if (editProjectForm.checkValidity() === false) {
                editProjectForm.classList.add('was-validated');
            } else {
                // Perform duplicate document check
                validateForm().then(isValid => {
                    if (isValid) {
                        // Perform AJAX submission instead of standard form submission
                        fetch(editProjectForm.action, {
                            method: 'POST',
                            body: new FormData(editProjectForm)
                        })
                            .then(response => {
                                if (response.ok) {
                                    return response.text(); // or response.json() depending on your server response
                                } else {
                                    throw new Error('Network response was not ok');
                                }
                            })
                            .then(data => {
                                location.reload(); // Reload the page or update UI
                            })
                            .catch(error => {
                                errorMessage.classList.remove('d-none');
                                errorMessage.innerHTML = "An error occurred: " + error.message;
                            });
                    } else {
                        editProjectForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = "Duplicate Project Number or Quote Number found.";
                    }
                }).catch(error => {
                    errorMessage.classList.remove('d-none');
                    errorMessage.innerHTML = "An error occurred: " + error.message;
                });
            }
        });

        // Update the selected engineers' text when checkboxes change
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const selected = Array.from(checkboxes).filter(cb => cb.checked);

                // Get the names of the selected engineers from the associated labels
                const selectedNames = selected.map(cb => {
                    const label = document.querySelector(`label[for="${cb.id}"]`);
                    return label ? label.innerHTML : ''; // Get the innerHTML of the label
                }).filter(name => name !== ''); // Remove any empty names

                // Set the button text to show each name on a new line
                const buttonText = selectedNames.length > 0
                    ? selectedNames.join("<br>") // Join names with line breaks
                    : "Select Project Engineer(s)";

                // Show or hide the selected engineer's text based on the number of selections
                if (selectedNames.length > 0) {
                    selectedEngineerText.innerHTML = "Selected:<br>" + buttonText;
                    selectedEngineerText.style.display = 'block';  // Show the text
                } else {
                    selectedEngineerText.innerHTML = '';  // Clear the text
                    selectedEngineerText.style.display = 'none';  // Hide the text
                }
            });
        });

        // Search functionality for engineers
        searchInput.addEventListener('input', function () {
            const searchTerm = searchInput.value.toLowerCase();

            listItems.forEach(function (item) {
                const label = item.querySelector('label');
                const name = label.textContent.toLowerCase();

                if (name.includes(searchTerm)) {
                    item.style.display = ''; // Show the item
                } else {
                    item.style.display = 'none'; // Hide the item
                }
            });
        });
    });
</script>