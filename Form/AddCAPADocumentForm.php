<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create new email sender object
$emailSender = new emailSender();

// ========================= Get the employees ========================= 
$employees_sql = "SELECT employee_id, first_name, last_name, email FROM employees";
$employees_result = $conn->query($employees_sql);

// Fetch all results into an array
$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

// ========================= SQL to get latest CAPA ID =========================
$capa_document_id_sql = "SELECT MAX(capa_document_id) AS latest_id FROM capa";
$capa_document_id_result = $conn->query($capa_document_id_sql);

if ($capa_document_id_result->num_rows > 0) {
    $row = $capa_document_id_result->fetch_assoc();
    $latest_id = $row['latest_id'];

    // Check if there's a latest ID
    if ($latest_id) {
        // Extract the numeric part
        $number = (int) substr($latest_id, 5); // Assuming 'CAPA-' is 5 characters long
        $next_number = $number + 1; // Increment the number
    } else {
        // If no ID exists, start with 1
        $next_number = 1;
    }

    // Format the next ID
    $next_capa_id = "CAPA-" . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    // echo "The next CAPA Document ID is: " . $next_capa_id;
} else {
    echo "No records found";
}

// ========================= A D D  C A P A  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["capaDocumentId"])) {
    $capaDocumentId = $_POST["capaDocumentId"];
    $dateRaised = $_POST["dateRaised"];
    $capaDescription = $_POST["capaDescription"];
    $severity = $_POST["severity"];
    $raisedAgainst = $_POST["raisedAgainst"];
    $capaOwner = $_POST["capaOwner"];
    $status = $_POST["status"];
    $assignedTo = $_POST["assignedTo"];
    $mainSourceType = $_POST["mainSourceType"];
    $productOrService = $_POST["productOrService"];
    $mainFaultCategory = $_POST["mainFaultCategory"];
    $targetCloseDate = $_POST["targetCloseDate"];
    $capaOwnerEmail = $_POST["capaOwnerEmail"];
    $assignedToEmail = $_POST["assignedToEmail"];

    $add_capa_document_sql = "INSERT INTO capa (capa_document_id, date_raised, capa_description, severity, raised_against, capa_owner, status, assigned_to, main_source_type, product_or_service, main_fault_category, target_close_date) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $add_capa_document_result = $conn->prepare($add_capa_document_sql);
    $add_capa_document_result->bind_param("ssssssssssss", $capaDocumentId, $dateRaised, $capaDescription, $severity, $raisedAgainst, $capaOwner, $status, $assignedTo, $mainSourceType, $productOrService, $mainFaultCategory, $targetCloseDate);

    // Execute the prepared statement 
    if ($add_capa_document_result->execute()) {
        // Build the current URL with query parameters
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }

        // Existing code for fetching CAPA owner name
        $capa_owner_name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
        $capa_owner_name_result = $conn->prepare($capa_owner_name_sql);

        if ($capa_owner_name_result) {
            $capa_owner_name_result->bind_param("i", $capaOwner);
            $capa_owner_name_result->execute();
            $fullName = $capa_owner_name_result->get_result();

            if ($fullName && $fullName->num_rows > 0) {
                $employee = $fullName->fetch_assoc();
                $recipientName = $employee['first_name'] . ' ' . $employee['last_name'];

                // Code for fetching assigned employee name
                $assigned_to_name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                $assigned_to_name_result = $conn->prepare($assigned_to_name_sql);

                if ($assigned_to_name_result) {
                    $assigned_to_name_result->bind_param("i", $assignedTo);
                    $assigned_to_name_result->execute();
                    $assignedNameResult = $assigned_to_name_result->get_result();

                    if ($assignedNameResult && $assignedNameResult->num_rows > 0) {
                        $assignedEmployee = $assignedNameResult->fetch_assoc();
                        $assignedRecipientName = $assignedEmployee['first_name'] . ' ' . $assignedEmployee['last_name'];
                    } else {
                        // Handle the case where the assigned employee is not found
                        $assignedRecipientName = "Unknown"; // or any default value
                        error_log("Assigned employee with ID $assignedTo not found.");
                    }

                    // Close the assigned_to statement
                    $assigned_to_name_result->close();
                } else {
                    error_log("Failed to prepare assigned_to statement: " . $conn->error);
                }

                // Send the email with the full name of the CAPA owner and assigned employee
                $emailSender->sendEmail(
                    $capaOwnerEmail,
                    $recipientName,
                    'New CAPA Document Added',
                    "
            <p>Dear $recipientName,</p>
            <p>This is a reminder that a new CAPA document has been added.</p>
            <p><strong>Details:</strong></p>
            <ul>
                <li><strong>CAPA Document ID:</strong> <b>$capaDocumentId</b></li>
                <li><strong>Date Raised:</strong> <b>$dateRaised</b></li>
                <li><strong>Severity:</strong> <b>$severity</b></li>
                <li><strong>Raised Against:</strong> <b>$raisedAgainst</b></li>
                <li><strong>Capa Owner:</strong> <b>$recipientName</b></li>
                <li><strong>Assigned To:</strong> <b>$assignedRecipientName</b></li> <!-- Updated to display the assigned person's name -->
                <li><strong>Target Closed Date:</strong> <b>$targetCloseDate</b></li>
            </ul>
            <p>Please take the necessary actions regarding this document.</p>
            <p>This email is sent automatically. Please do not reply.</p>
            <p>Best regards,<br></p>
            "
                );
            } else {
                error_log("Employee with ID $capaOwner not found.");
            }

            // Close the CAPA owner statement
            $capa_owner_name_result->close();
        } else {
            error_log("Failed to prepare CAPA owner statement: " . $conn->error);
        }


        $assigned_to_name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
        $assigned_to_name_result = $conn->prepare($assigned_to_name_sql);

        if ($assigned_to_name_result) {
            $assigned_to_name_result->bind_param("i", $assignedTo);

            // Execute the prepared statement
            $assigned_to_name_result->execute();

            // Get the result set from the executed statement
            $fullName = $assigned_to_name_result->get_result();

            if ($fullName && $fullName->num_rows > 0) {
                $employee = $fullName->fetch_assoc();

                // Combine first_name and last_name to form the $recipientName
                $recipientName = $employee['first_name'] . ' ' . $employee['last_name'];

                // Code for fetching capa owner employee name
                $capa_owner_name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                $capa_owner_name_result = $conn->prepare($assigned_to_name_sql);

                if ($capa_owner_name_result) {
                    $capa_owner_name_result->bind_param("i", $capaOwner);
                    $capa_owner_name_result->execute();
                    $capaOwnerNameResult = $capa_owner_name_result->get_result();

                    if ($capaOwnerNameResult && $capaOwnerNameResult->num_rows > 0) {
                        $capaOwnerEmployee = $capaOwnerNameResult->fetch_assoc();
                        $capaOwnerName = $capaOwnerEmployee['first_name'] . ' ' . $capaOwnerEmployee['last_name'];
                    } else {
                        // Handle the case there the capa owner employee is not found
                        $capaOwnerName = "Unknown";
                        error_log("CAPA owner employee with ID $capaOwner not found.");
                    }

                    // Close the capa_owner statement
                    $capa_owner_name_result->close();
                } else {
                    error_log("Failed to prepare capa_owner to statement: " . $conn->error);
                }

                // Send the email with the full name of the assigned to
                $emailSender->sendEmail(
                    $assignedToEmail, // Recipient email
                    $recipientName, // Recipient name
                    'A new CAPA has been assigned to you!', // Subject
                    "
                    <p>Dear $recipientName,</p>
                    
                    <p>This is a reminder that the CAPA document with ID <b>$capaDocumentId</b> has been assigned to you.</p>
                    
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>CAPA Document ID:</strong> <b>$capaDocumentId</b></li>
                        <li><strong>Date Raised:</strong> <b>$dateRaised</b></li>
                        <li><strong>Target Closed Date:</strong> <b>$targetCloseDate</b></li>
                        <li><strong>CAPA Owner: </strong><b>$capaOwnerName</b></li> 
                    </ul>
        
                    <p>Please take the necessary actions regarding this document.</p>
        
                    <p>This email is sent automatically. Please do not reply.</p>
        
                    <p>Best regards,<br></p>
                    "
                );
            }
        }

        echo "<script>alert('Document added successfully')</script>";
        // Redirect to the same URL with parameters
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    } else {
        // Improved error reporting
        echo "Error updating record: " . $conn->error;
    }
}

?>

<form method="POST" id="addCAPADocumentForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="result">
    </p>
    <div class="row">
        <div class="form-group col-md-6">
            <label for="capaDocumentId" class="fw-bold">CAPA Document ID</label>
            <input type="text" name="capaDocumentId" class="form-control" id="capaDocumentId"
                value="<?php echo $next_capa_id ?>" required>
            <div class="invalid-feedback">
                Please provide CAPA Document ID.
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="dateRaised" class="fw-bold">Date Raised</label>
            <input type="date" name="dateRaised" class="form-control" id="dateRaised"
                value="<?php echo date('Y-m-d'); ?>">
            <div class="invalid-feedback" required>
                Please provide the date raised.
            </div>
        </div>
        <div class="form-group col-md-12 mt-3">
            <label for="capaDescription" class="fw-bold">CAPA Description</label>
            <textarea class="form-control" name="capaDescription" id="capaDescription" rows="4" required></textarea>
            <div class="invalid-feedback">
                Please provide the CAPA Description.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="severity" class="fw-bold">Severity</label>
            <select class="form-select" name="severity" aria-label="severity" required>
                <option disabled selected hidden></option>
                <option value="Observation">Observation</option>
                <option value="Minor">Minor</option>
                <option value="Major">Major</option>
                <option value="Catastrophic">Catastrophic</option>
            </select>
            <div class="invalid-feedback">
                Please provide the severity.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="raisedAgainst" class="fw-bold">Raised Against</label>
            <input type="text" name="raisedAgainst" class="form-control" required>
            <div class="invalid-feedback">
                Please provide the raised against.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="capaOwner" class="fw-bold">CAPA Owner</label>
            <select name="capaOwner" class="form-select" id="capaOwner" required onchange="updateCapaOwnerEmail()">
                <option disabled selected hidden></option>
                <?php
                foreach ($employees as $row) {
                    echo '<option value="' . $row['employee_id'] . '" data-owner-email="' . htmlspecialchars($row['email']) . '"> ' .
                        htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                        htmlspecialchars($row['employee_id']) . ')</option>';
                }
                ?>
            </select>
            <input type="text" name="capaOwnerEmail" id="capaOwnerEmail" readonly>
            <div class="invalid-feedback">
                Please provide the CAPA owner.
            </div>
        </div>

        <!-- <div class="form-group col-md-6 mt-3">
            <label for="status" class="fw-bold">Status</label>
            <select class="form-select" name="status" aria-label="status">
                <option disabled selected hidden></option>
                <option value="Open">Open</option>
                <option value="Closed">Closed</option>
                <option value="Rejected">Rejected</option>
                <option value="Requires MN Review">Requires MN Review</option>
            </select>
        </div> -->
        <input type="hidden" name="status" value="Open">
        <div class="form-group col-md-6 mt-3">
            <label for="assignedTo" class="fw-bold">Assigned To</label>
            <select name="assignedTo" class="form-select" id="assignedTo" required onchange="updateAssignedToEmail()">
                <option disabled selected hidden></option>
                <?php
                foreach ($employees as $row) {
                    echo '<option value="' . $row['employee_id'] . '" data-assigned-to-email="' . htmlspecialchars($row['email']) . '"> ' .
                        htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                        htmlspecialchars($row['employee_id']) . ')</option>';
                }
                ?>
            </select>
            <!-- <input type="text" name="assignedTo" class="form-control" required> -->
            <input type="text" name="assignedToEmail" id="assignedToEmail" readonly>
            <div class="invalid-feedback">
                Please provide the assigned to.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="mainSourceType" class="fw-bold">Main Source Type</label>
            <select class="form-select" name="mainSourceType" aria-label="mainSourceType" required>
                <option disabled selected hidden></option>
                <option value="Customer Complaint">Customer Complaint</option>
                <option value="Customer Compliment">Customer Compliment</option>
                <option value="Supplier Issue">Supplier Issue</option>
                <option value="Internal Audit">Internal Audit</option>
                <option value="External Audit">External Audit</option>
                <option value="Internal Issue">Internal Issue</option>
                <option value="Employee Suggestion">Employee Suggestion</option>
                <option value="WHS Issue">WHS Issue</option>
            </select>
            <div class="invalid-feedback">
                Please provide the main source type.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="productOrService" class="fw-bold">Product/Service</label>
            <input type="text" name="productOrService" class="form-control" required>
            <div class="invalid-feedback">
                Please provide the product/service.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="mainFaultCategory" class="fw-bold">Main Fault Category</label>
            <!-- <select class="form-select" name="mainFaultCategory" aria-label="mainFaultCategory">
                <option disabled selected hidden></option>
                <option value="Process/Procedure">Process/Procedure</option>
                <option value="Equipment/Facilities">Equipment/Facilities</option>
                <option value="Documentation">Documentation</option>
                <option value="Technology">Technology</option>
                <option value="WHS">WHS</option>
                <option value="Supplier Issue">Supplier Issue</option>
                <option value="Defective Product">Defective Product</option>
                <option value="Product Return">Product Return</option>
                <option value="Environmental Factor">Environmental Factor</option>
                <option value="Logistics">Logistics</option>
                <option value="HR and People">HR and People</option>
                <option value="Training">Training</option>
                <option value="Communication">Communication</option>
                <option value="Human Error">Human Error</option>
            </select> -->
            <input type="text" name="mainFaultCategory" class="form-control" required>
            <div class="invalid-feedback">
                Please provide the main fault category.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="targetCloseDate" class="fw-bold">Target Close Date</label>
            <input type="date" name="targetCloseDate" class="form-control" id="targetCloseDate" required>
            <div class="invalid-feedback">
                Please provide the target close date.
            </div>
        </div>

        <div class="d-flex justify-content-center mt-5 mb-4">
            <button class="btn btn-dark" name="addCapaDocument" type="submit">Add Document</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addCAPADocumentForm = document.getElementById("addCAPADocumentForm");
        const capaDocumentId = document.getElementById("capaDocumentId");
        const errorMessage = document.getElementById("result");

        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-capa-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ capaDocumentId: capaDocumentId.value })
            })
                .then(response => response.text()) // Get the plain text response
                .then(data => {
                    console.log('Server Response:', data); // Log response for debugging
                    return data === '0'; // Return true id no duplicate (0), false if duplicate (1)
                })
        }

        function validateForm() {
            return checkDuplicateDocument().then(isDuplicateValid => {
                return isDuplicateValid;
            });
        }

        addCAPADocumentForm.addEventListener('submit', function (event) {
            // Prevent default form submission
            event.preventDefault();
            event.stopPropagation();

            // Fetch values dynamically within the submit event handler
            const dateRaised = document.getElementById("dateRaised").value;
            const targetCloseDate = document.getElementById("targetCloseDate").value;

            // Convert date strings to Date objects for proper comparison 
            const raisedDate = new Date(dateRaised);
            const closeDate = new Date(targetCloseDate);

            // Clear previous error message
            errorMessage.classList.add('d-none');
            errorMessage.innerHTML = '';

            if (closeDate < raisedDate) {
                errorMessage.classList.remove('d-none');
                errorMessage.innerHTML = "Target date should be after date raised.";
                return; // Stop execution if date validation fails
            }

            // Check validity of the form
            if (addCAPADocumentForm.checkValidity() === false) {
                // Add was-validated class to show validation feedback
                addCAPADocumentForm.classList.add('was-validated');
            } else {
                // Perform your duplicate document validation if the form is valid
                validateForm().then(isValid => {
                    if (isValid) {
                        // Now submit the form
                        addCAPADocumentForm.submit();
                    } else {
                        // Add was-validated class and show alert for duplicate ID
                        addCAPADocumentForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = "Duplicate document found.";
                    }
                })
            }
        })
    })
</script>

<script>
    function updateCapaOwnerEmail() {
        const select = document.getElementById('capaOwner');
        const emailInput = document.getElementById('capaOwnerEmail');
        const selectedOption = select.options[select.selectedIndex];

        // Set the hidden input value to the email of the selected employee
        emailInput.value = selectedOption.getAttribute('data-owner-email');
    }

    function updateAssignedToEmail() {
        const select = document.getElementById('assignedTo');
        const emailInput = document.getElementById('assignedToEmail');
        const selectedOption = select.options[select.selectedIndex];

        // Set the hidden input value to the email of the selected employee
        emailInput.value = selectedOption.getAttribute('data-assigned-to-email');
    }
</script>
