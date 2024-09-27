<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// ========================= SQL to get lates CAPA ID =========================
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
        Duplicate document found.</p>
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
            <input type="date" name="dateRaised" class="form-control" value="<?php echo date('Y-m-d'); ?>">
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
            <input type="text" name="capaOwner" class="form-control" required>
            <div class="invalid-feedback">
                Please provide the capa owner.
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
            <input type="text" name="assignedTo" class="form-control" required>
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
            <input type="date" name="targetCloseDate" class="form-control" required>
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
                .then(response => response.text())  // Get the plain text response
                .then(data => {
                    console.log('Server Response:', data);  // Log response for debugging
                    return data === '0'; // Return true if no duplicate (0), false if duplicate (1)
                });
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
                    }
                });
            }
        }, false);

    });
</script>