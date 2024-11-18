<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ========================= Get the employees ========================= 
$employees_sql = "SELECT * FROM employees ";
$employees_result = $conn->query($employees_sql);

// Fetch all results into an array 
$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

// ========================= A D D  P R O J E C T  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["projectNo"])) {
    $projectNo = $_POST["projectNo"];
    $quoteNo = empty($_POST["quoteNo"]) ? NULL : $_POST['quoteNo'];
    $current = $_POST["current"];
    $projectName = $_POST["projectName"];
    $projectType = $_POST["projectType"];
    $customer = $_POST["customer"];
    $value = $_POST['value'];
    $variation = empty($_POST['variation']) ? NULL : $_POST['variation'];
    $estimatedDeliveryDate = $_POST['estimatedDeliveryDate'];
    $paymentTerms = $_POST['paymentTerms'];
    // Join multiple project engineers into a comma-separated string, or set to NULL if none selected
    $projectEngineer = empty($_POST['projectEngineer']) ? NULL : $_POST['projectEngineer'];
    $customerAddress = empty($_POST['customerAddress']) ? NULL : $_POST['customerAddress'];

    // SQL statement
    $add_project_sql = "INSERT INTO projects (
        project_no, 
        quote_no, 
        `current`, 
        project_name, 
        project_type, 
        customer, 
        `value`, 
        variation, 
        estimated_delivery_date, 
        payment_terms, 
        project_engineer, 
        customer_address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare statement
    if ($add_project_result = $conn->prepare($add_project_sql)) {
        // Bind parameters
        $add_project_result->bind_param(
            "ssssssssssss",
            $projectNo,
            $quoteNo,
            $current,
            $projectName,
            $projectType,
            $customer,
            $value,
            $variation,
            $estimatedDeliveryDate,
            $paymentTerms,
            $projectEngineer,
            $customerAddress
        );

        if ($add_project_result->execute()) {
            $current_url = $_SERVER['PHP_SELF'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $current_url .= '?' . $_SERVER['QUERY_STRING'];
            }
            echo "<script>alert('Document added successfully');</script>";
            echo "<script>window.location.replace('" . $current_url . "');</script>";
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
}

?>

<style>
    /* Style the dropdown menu to have a cleaner layout */
    .dropdown-menu {
        max-height: 300px;
        overflow-y: auto;
        padding: 0;
        margin: 0;
    }

    /* Style each list item for better alignment */
    .dropdown-menu li {
        display: flex;
        align-items: center;
        padding: 5px 10px;
        /* Add some padding for spacing */
    }

    /* Ensure the checkbox is compact and aligned next to the text */
    .dropdown-menu input[type="checkbox"] {
        margin-right: 10px;
        /* Space between checkbox and label */
        width: 20px;
        /* Limit checkbox width */
        height: 20px;
        /* Limit checkbox height */
    }

    /* Ensure labels take the remaining space and are aligned properly */
    .dropdown-menu label {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        /* Truncate long text if needed */
    }
</style>

<form method="POST" id="addProjectForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%" id="result">
    </p>
    <div class="row">
        <div class="form-group col-md-6">
            <label for="projectNo" class="fw-bold">Project No.</label>
            <input type="text" name="projectNo" class="form-control" id="projectNo" required>
            <div class="invalid-feedback">
                Please provide the Project No.
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="quoteNo" class="fw-bold">Quote No.</label>
            <input type="text" name="quoteNo" class="form-control" id="quoteNo">
            <div class="invalid-feedback">
                Please provide the Quote No.
            </div>
        </div>
        <div class="form-group col-12 mt-3">
            <label for="projectName" class="fw-bold">Project Name</label>
            <textarea type="text" name="projectName" class="form-control" id="projectName" required></textarea>
            <div class="invalid-feedback">
                Please provide the project name.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="current" class="fw-bold">Current</label>
            <select name="current" id="current" class="form-select" required>
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
            <label for="estimatedDeliveryDate" class="fw-bold">Estimated Delivery Date</label>
            <input type="date" name="estimatedDeliveryDate" id="estimatedDeliveryDate" class="form-control" required>
            <div class="invalid-feedback">
                Please provide the delivery date.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="projectType" class="fw-bold">Project Type</label>
            <select name="projectType" id="projectType" class="form-select" required>
                <option disabled selected hidden></option>
                <option value="Local">Local</option>
                <option value="Sitework">Sitework</option>
                <option value="IOR & Commissioning">IOR & Commissioning</option>
                <option value="Export">Export</option>
                <option value="R&D">R&D</option>
                <option value="Service">Service</option>
            </select>
            <div class="invalid-feedback">
                Please provide the Project Type.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="customer" class="fw-bold">Customer</label>
            <input type="text" name="customer" id="customer" class="form-control" required>
            <div class="invalid-feedback">
                Please provide the customer.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="value" class="fw-bold">Value</label>
            <div class="input-group">
                <span class="input-group-text rounded-start">$</span>
                <input type="number" name="value" id="value" class="form-control rounded-end" required>
                <div class="invalid-feedback">
                    Please provide the value.
                </div>
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="variation" class="fw-bold">Variation</label>
            <div class="input-group">
                <span class="input-group-text rounded-start">$</span>
                <input type="number" name="variation" id="variation" class="form-control rounded-end">
            </div>
        </div>

        <div class="form-group col-md-6 mt-3">
            <label for="paymentTerms" class="fw-bold">Payment Terms</label>
            <select name="paymentTerms" id="paymentTerms" class="form-select" required>
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
            <label for="projectEngineer" class="fw-bold">Project Engineer</label>
            <div id="projectEngineerDropdown" class="dropdown">
                <button class="btn btn-dark dropdown-toggle" type="button" id="dropdownMenuButton"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    Select Project Engineer(s)
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <!-- Search box to filter the dropdown list -->
                    <li>
                        <input type="text" id="searchEngineer" class="form-control" placeholder="Search engineers..." />
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
            <small id="selectedEngineer"></small>
        </div>


        <div class="form-group col-md-12 mt-3">
            <label for="customerAddress" class="fw-bold">Customer Address</label>
            <textarea type="text" name="customerAddress" class="form-control" id="customerAddress"></textarea>
        </div>
        <div class="d-flex justify-content-center mt-4 mb-4">
            <button class="btn btn-dark" name="addProject" type="submit" id="addProjectBtn">Add Project</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addProjectForm = document.getElementById("addProjectForm");
        const projectEngineerDropdown = document.getElementById("projectEngineerDropdown");
        const projectNo = document.getElementById("projectNo");
        const quoteNo = document.getElementById("quoteNo");
        const errorMessage = document.getElementById("result");

        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-project-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    projectNo: projectNo.value,
                    quoteNo: quoteNo.value
                })
            })
                .then(response => response.text())
                .then(data => {
                    console.log('Server Response:', data);
                    return data === '0'; // Return true if no duplicate (0), false if duplicate (1)
                });
        }

        function validateForm() {
            return checkDuplicateDocument().then(isDuplicateValid => {
                return isDuplicateValid;
            });
        }

        addProjectForm.addEventListener('submit', function (event) {
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
                engineersInput.name = 'projectEngineer';  // Use a single field name
                engineersInput.value = selectedEngineers.join(',');  // Join selected engineers as a string
                addProjectForm.appendChild(engineersInput);
            }

            console.log(selectedEngineers);

            // Check validity of the form
            if (addProjectForm.checkValidity() === false) {
                addProjectForm.classList.add('was-validated');
            } else {
                // Perform duplicate document check
                validateForm().then(isValid => {
                    if (isValid) {
                        // Perform AJAX submission instead of standard form submission
                        fetch(addProjectForm.action, {
                            method: 'POST',
                            body: new FormData(addProjectForm)
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
                        addProjectForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = "Duplicate Project Number or Quote Number found.";
                    }
                }).catch(error => {
                    errorMessage.classList.remove('d-none');
                    errorMessage.innerHTML = "An error occurred: " + error.message;
                });
            }
        });

        // Show selected engineers in the button text
        const checkboxes = projectEngineerDropdown.querySelectorAll('input[type="checkbox"]');
        const selectedEngineerText = document.getElementById("selectedEngineer");
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const selected = Array.from(checkboxes).filter(cb => cb.checked);

                // Get the names of the selected engineers from the innerHTML of the associated label
                const selectedNames = selected.map(cb => {
                    const label = cb.nextElementSibling; // Assuming label is right after checkbox
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
    });
</script>

<script>
    // Search project engineer input
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById("searchEngineer");
        const listItems = document.querySelectorAll('.engineer-item'); // Get all engineer list items

        // Filter the list when the user types in the search box
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