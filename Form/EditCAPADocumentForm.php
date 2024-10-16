<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create new email sender object
$emailSender = new emailSender();

if (!$emailSender) {
    die("Email sender object is not initialized.");
}

// ========================= Get the employees ========================= 
$employees_sql = "SELECT employee_id, first_name, last_name, email FROM employees";
$employees_result = $conn->query($employees_sql);

// Fetch all results into an array
$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

// ========================= E D I T  D O C U M E N T ( OPEN FORM ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["capaIdToEdit"]) && isset($_POST["editCapaDocument"])) {
    $capaIdToEdit = $_POST["capaIdToEdit"];
    $capaDocumentId = $_POST["capaDocumentIdToEdit"];
    $dateRaised = $_POST["dateRaisedToEdit"];
    $capaDescription = $_POST["capaDescriptionToEdit"];
    $severity = $_POST["severityToEdit"];
    $raisedAgainst = $_POST["raisedAgainstToEdit"];
    $capaOwner = $_POST["capaOwnerToEdit"];
    $assignedTo = $_POST["assignedToToEdit"];
    $mainSourceType = $_POST["mainSourceTypeToEdit"];
    $productOrService = $_POST["productOrServiceToEdit"];
    $mainFaultCategory = $_POST["mainFaultCategoryToEdit"];
    $targetCloseDate = $_POST["targetCloseDateToEdit"];
    $capaOwnerEmail = $_POST["capaOwnerEmail"];

    // Prepare statement
    $edit_document_sql = "UPDATE capa SET capa_document_id = ?, date_raised = ?, capa_description = ?, severity = ?, raised_against = ?, capa_owner = ?, assigned_to = ?, main_source_type = ?, product_or_service = ?, main_fault_category = ?, target_close_date = ? WHERE capa_id = ?";
    $edit_document_result = $conn->prepare($edit_document_sql);

    // Check if the statement preparation was successful
    if ($edit_document_result === false) {
        die("Error preparing the statement: " . $conn->error);
    }

    // Bind parameters
    $edit_document_result->bind_param(
        "sssssssssssi",
        $capaDocumentId,
        $dateRaised,
        $capaDescription,
        $severity,
        $raisedAgainst,
        $capaOwner,
        $assignedTo,
        $mainSourceType,
        $productOrService,
        $mainFaultCategory,
        $targetCloseDate,
        $capaIdToEdit
    );

    // Execute and check for errors
    if ($edit_document_result->execute()) {
        // Build current URL
        $current_url = htmlspecialchars($_SERVER['PHP_SELF']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . urlencode($_SERVER['QUERY_STRING']);
        }

        // Fetch CAPA owner name
        $capa_owner_name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
        $capa_owner_name_result = $conn->prepare($capa_owner_name_sql);

        if ($capa_owner_name_result) {
            $capa_owner_name_result->bind_param("i", $capaOwner);
            $capa_owner_name_result->execute();
            $fullName = $capa_owner_name_result->get_result();

            if ($fullName && $fullName->num_rows > 0) {
                $employee = $fullName->fetch_assoc();
                $recipientName = $employee['first_name'] . ' ' . $employee['last_name'];

                // Fetch assigned employee name
                $assigned_to_name_sql = "SELECT first_name, last_name, email FROM employees WHERE employee_id = ?";
                $assigned_to_name_result = $conn->prepare($assigned_to_name_sql);

                if ($assigned_to_name_result) {
                    $assigned_to_name_result->bind_param("i", $assignedTo);
                    $assigned_to_name_result->execute();
                    $assignedNameResult = $assigned_to_name_result->get_result();

                    if ($assignedNameResult && $assignedNameResult->num_rows > 0) {
                        $assignedEmployee = $assignedNameResult->fetch_assoc();
                        $assignedRecipientName = $assignedEmployee['first_name'] . ' ' . $assignedEmployee['last_name'];
                        $assignedRecipientEmail = $assignedEmployee['email'];
                    } else {
                        // Handle case where assigned employee is not found
                        $assignedRecipientName = "Unknown";
                        $assignedRecipientEmail = ""; // Set to empty if not found
                        error_log("Assigned employee with ID $assignedTo not found.");
                    }

                    // Close the assigned_to statement
                    $assigned_to_name_result->close();
                } else {
                    error_log("Failed to prepare assigned_to statement: " . $conn->error);
                }

                // Send email to CAPA owner
                $emailSender->sendEmail(
                    $capaOwnerEmail, // Recipient email (CAPA owner)
                    $recipientName,   // Recipient name (CAPA owner)
                    'CAPA Document Edited!', // Subject
                    "
                    <p>Dear $recipientName,</p>
                    <p>This is a notification that the CAPA document <strong> $capaDocumentId </strong> has been edited</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>CAPA Document ID: </strong><b> $capaDocumentId </b></li>
                        <li><strong>Date Raised: </strong><b> $dateRaised</b></li>
                        <li><strong>Severity:</strong><b> $severity</b></li>
                        <li><strong>Raised Against: </strong><b>$raisedAgainst</b></li>
                        <li><strong>CAPA Owner:</strong><b> $recipientName</b></li>
                        <li><strong>Assigned To:</strong><b> $assignedRecipientName</b></li>
                        <li><strong>Target Closed Date:</strong><b> $targetCloseDate</b></li>
                    </ul>
                    <p>Please review the changes and take any necessary actions regarding this document.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,</p>
                "
                );

                // Send email to Assigned employee
                $emailSender->sendEmail(
                    $assignedRecipientEmail, // Recipient email (Assigned employee)
                    $assignedRecipientName,   // Recipient name (Assigned employee)
                    'CAPA Document Edited!',  // Subject
                    "
                    <p>Dear $assignedRecipientName,</p>
                    <p>This is a notification that the CAPA document <strong> $capaDocumentId </strong> has been edited</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>CAPA Document ID: </strong><b> $capaDocumentId </b></li>
                        <li><strong>Date Raised: </strong><b> $dateRaised</b></li>
                        <li><strong>Severity:</strong><b> $severity</b></li>
                        <li><strong>Raised Against: </strong><b>$raisedAgainst</b></li>
                        <li><strong>CAPA Owner:</strong><b> $recipientName</b></li>
                        <li><strong>Assigned To:</strong><b> $assignedRecipientName</b></li>
                        <li><strong>Target Closed Date:</strong><b> $targetCloseDate</b></li>
                    </ul>
                    <p>Please review the changes and take any necessary actions regarding this document.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,</p>
                "
                );

                // Redirect
                echo "<script>window.location.replace('" . $current_url . "');</script>";
                exit();
            }
        }
    } else {
        echo "Error updating record: " . $edit_document_result->error;
    }

}

// ========================= E D I T  D O C U M E N T ( CLOSE FORM ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["closeCAPAbtn"])) {
    $capaIdToEdit = $_POST["capaIdToEdit"];
    $dateClosed = $_POST["dateClosed"];
    $keyTakeaways = $_POST["keyTakeaways"];
    $additionalComments = $_POST["additionalComments"];
    $capaOwner = $_POST["capaOwnerToEdit"];
    $capaOwnerEmail = $_POST["capaOwnerEmail"];
    $assignedTo = $_POST["assignedTo"];

    $capaDocumentId = $_POST["capaDocumentIdToEdit"];
    $dateRaised = $_POST["dateRaisedToEdit"];
    $capaDescription = $_POST["capaDescriptionToEdit"];
    $severity = $_POST["severityToEdit"];
    $raisedAgainst = $_POST["raisedAgainstToEdit"];
    $assignedTo = $_POST["assignedToToEdit"];
    $mainSourceType = $_POST["mainSourceTypeToEdit"];
    $productOrService = $_POST["productOrServiceToEdit"];
    $mainFaultCategory = $_POST["mainFaultCategoryToEdit"];
    $targetCloseDate = $_POST["targetCloseDateToEdit"];

    // Prepare statement for closing CAPA
    $close_capa_sql = "UPDATE capa SET status = 'Closed', date_closed = ?, key_takeaways = ?, additional_comments = ? WHERE capa_id = ?";
    $close_capa_result = $conn->prepare($close_capa_sql);
    $close_capa_result->bind_param("sssi", $dateClosed, $keyTakeaways, $additionalComments, $capaIdToEdit);

    if ($close_capa_result->execute()) {
        // Build current URL
        $current_url = htmlspecialchars($_SERVER['PHP_SELF']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . urlencode($_SERVER['QUERY_STRING']);
        }

        // Fetch CAPA owner name
        $capa_owner_name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
        $capa_owner_name_result = $conn->prepare($capa_owner_name_sql);

        if ($capa_owner_name_result) {
            $capa_owner_name_result->bind_param("i", $capaOwner);
            $capa_owner_name_result->execute();
            $fullName = $capa_owner_name_result->get_result();

            if ($fullName && $fullName->num_rows > 0) {
                $employee = $fullName->fetch_assoc();
                $recipientName = $employee['first_name'] . ' ' . $employee['last_name'];

                // Fetch assigned employee name
                $assigned_to_name_sql = "SELECT first_name, last_name, email FROM employees WHERE employee_id = ?";
                $assigned_to_name_result = $conn->prepare($assigned_to_name_sql);

                if ($assigned_to_name_result) {
                    $assigned_to_name_result->bind_param("i", $assignedTo);
                    $assigned_to_name_result->execute();
                    $assignedNameResult = $assigned_to_name_result->get_result();

                    if ($assignedNameResult && $assignedNameResult->num_rows > 0) {
                        $assignedEmployee = $assignedNameResult->fetch_assoc();
                        $assignedRecipientName = $assignedEmployee['first_name'] . ' ' . $assignedEmployee['last_name'];
                        $assignedRecipientEmail = $assignedEmployee['email'];
                    } else {
                        // Handle case where assigned employee is not found
                        $assignedEmployee = "Unknown";
                        $assignedRecipientEmail = ""; // Set to empty if not found
                        error_log("Assigned employee with ID $assignedTo not found");
                    }

                    // Close the assigned_to statement
                    $assigned_to_name_result->close();
                } else {
                    error_log("Failed to prepared assigned_to statement: " . $conn->error);
                }

                // Send email to the CAPA owner
                $emailSender->sendEmail(
                    $capaOwnerEmail, // Recipient email (CAPA Owner)
                    $recipientName, // Recipient name (CAPA Owner)
                    'CAPA Document Closed!', // Subject
                    "
                    <p>Dear $recipientName, </p>
                    <p>This is a notification that the CAPA document <strong> $capaDocumentId </strong> has been closed!</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>CAPA Document ID: </strong><b> $capaDocumentId </b></li>
                        <li><strong>Date Raised: </strong><b> $dateRaised</b></li>
                        <li><strong>Severity: </strong><b> $severity </b></li>
                        <li><strong>Raised Against: </strong><b> $raisedAgainst </b></li>
                        <li><strong>CAPA Owner: </strong><b> $recipientName </b></li>
                        <li><strong>Assigned To: </strong><b> $assignedRecipientName </b></li>
                        <li><strong>Target Closed Date: </strong> <b> $targetCloseDate </b></li>
                        <li><strong>Date Closed: </strong> <b> $dateClosed </b></li>
                    </ul>
                    <p>Please review the changes and take any necessary actions regarding this document.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,</p>
                    "
                );

                // Send email to Assigned employee
                $emailSender->sendEmail(
                    $assignedRecipientEmail, // Recipient email (Assigned employee)
                    $assignedRecipientName, // Recipient name (Assigned employee)
                    'CAPA Document Closed!', // Subject
                    "
                    <p>Dear $assignedRecipientName,</p>
                    <p>This is a notification that the CAPA document <strong> $capaDocumentId </strong> has been closed!</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>CAPA Document ID: </strong><b> $capaDocumentId </b></li>
                        <li><strong>Date Raised: </strong><b> $dateRaised </b></li>
                        <li><strong>Severity: </strong><b> $severity </b></li>
                        <li><strong>Raised Against: </strong><b> $raisedAgainst </b></li>
                        <li><strong>CAPA Owner: </strong><b> $recipientName </b></li>
                        <li><strong>Assigned To: </strong><b> $assignedRecipientName </b></li>
                        <li><strong>Target Closed Date: </strong><b> $targetCloseDate </b></li>
                        <li><strong>Date Closed: </strong> <b> $dateClosed </b></li>
                    </ul>
                    <p> Please review the changes and take any necessary actions regarding this document.</p>
                    <p> This email is sent automatically. Please do not reply.</p>
                    <p> Best regards, </p>
                    "
                );
            }
        }


        // Redirect or show success message
        echo "<script>window.location.replace('" . htmlspecialchars($_SERVER['PHP_SELF']) . "');</script>";
        exit();
    } else {
        echo "Error closing document: " . $close_capa_result->error;
    }
}

// ========================= E D I T  C A P A  S T A T U S ( RE-OPEN CAPA ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["openCapaBtn"])) {
    $capaIdToEdit = $_POST["capaIdToEdit"];
    $dateClosed = $_POST["dateClosed"];
    $keyTakeaways = $_POST["keyTakeaways"];
    $additionalComments = $_POST["additionalComments"];
    $capaOwner = $_POST["capaOwnerToEdit"];
    $capaOwnerEmail = $_POST["capaOwnerEmail"];
    $assignedTo = $_POST["assignedTo"];

    $capaDocumentId = $_POST["capaDocumentIdToEdit"];
    $dateRaised = $_POST["dateRaisedToEdit"];
    $capaDescription = $_POST["capaDescriptionToEdit"];
    $severity = $_POST["severityToEdit"];
    $raisedAgainst = $_POST["raisedAgainstToEdit"];
    $assignedTo = $_POST["assignedToToEdit"];
    $mainSourceType = $_POST["mainSourceTypeToEdit"];
    $productOrService = $_POST["productOrServiceToEdit"];
    $mainFaultCategory = $_POST["mainFaultCategoryToEdit"];
    $targetCloseDate = $_POST["targetCloseDateToEdit"];

    $open_capa_sql = "UPDATE capa SET status = 'Open' , date_closed = NULL WHERE capa_id = ?";
    $open_capa_result = $conn->prepare($open_capa_sql);
    $open_capa_result->bind_param("i", $capaIdToEdit);


    if ($open_capa_result->execute()) {
        // Build current URL
        $current_url = htmlspecialchars($_SERVER['QUERY_STRING']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . urlencode($_SERVER['QUERY_STRING']);
        }

        // Fetch CAPA owner name 
        $capa_owner_name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
        $capa_owner_name_result = $conn->prepare($capa_owner_name_sql);

        if ($capa_owner_name_result) {
            $capa_owner_name_result->bind_param("i", $capaOwner);
            $capa_owner_name_result->execute();
            $fullName = $capa_owner_name_result->get_result();

            if ($fullName && $fullName->num_rows > 0) {
                $employee = $fullName->fetch_assoc();
                $recipientName = $employee['first_name'] . ' ' . $employee['last_name'];

                // Fetch assigned employee name
                $assigned_to_name_sql = "SELECT first_name, last_name, email FROM employees WHERE employee_id = ?";
                $assigned_to_name_result = $conn->prepare($assigned_to_name_sql);

                if ($assigned_to_name_result) {
                    $assigned_to_name_result->bind_param("i", $assignedTo);
                    $assigned_to_name_result->execute();
                    $assignedNameResult = $assigned_to_name_result->get_result();

                    if ($assignedNameResult && $assignedNameResult->num_rows > 0) {
                        $assignedEmployee = $assignedNameResult->fetch_assoc();
                        $assignedRecipientName = $assignedEmployee['first_name'] . ' ' . $assignedEmployee['last_name'];
                        $assignedRecipientEmail = $assignedEmployee['email'];
                    } else {
                        // Handle case where assigned employee is not found
                        $assignedEmployee = "Unknown";
                        $assignedRecipientEmail = ""; // Set to empty if not found
                        error_log("Assigned employee with ID $assignedTo not found");
                    }

                    // Close the assigned_to statement
                    $assigned_to_name_result->close();
                } else {
                    error_log("Failed to prepared assigned_to statement: " . $conn->error);
                }

                // Send email to the CAPA owner
                $emailSender->sendEmail(
                    $capaOwnerEmail, // Recipient email (CAPA Owner)
                    $recipientName, // Recipient name (CAPA Owner)
                    'CAPA Document Re-Opened!', // Subject
                    " 
                    <p> Dear $recipientName, </p>
                    <p> This is a notification that the CAPA document <strong> $capaDocumentId </strong> has been re-opened! </p>
                    <p> <strong>Details: </strong></p>
                    <ul>
                        <li><strong>CAPA Document ID: </strong><b> $capaDocumentId </b></li>
                        <li><strong>Date Raised: </strong><b> $dateRaised </b></li>
                        <li><strong>Severity: </strong><b> $severity </b></li>
                        <li><strong>Raised Against: </strong><b> $raisedAgainst </b></li>
                        <li><strong>CAPA Owner: </strong><b> $recipientName </b></li>
                        <li><strong>Assigned To: </strong><b> $assignedRecipientName </b></li>
                        <li><strong>Target Closed Date: </strong><b> $targetCloseDate </b></li>
                        <li><strong>Date Closed: </strong><b> $dateClosed </b></li>
                    </ul>
                    <p>Please review the changes and take any necessary actions regarding this document.</p>
                    <p>This email is sent automatically. Please do not reply.</p>
                    <p>Best regards,</p>
                    "
                );

                // Send email to Assigned employee
                $emailSender->sendEmail(
                    $assignedRecipientEmail, // Recipient email (Assigned employee)
                    $assignedRecipientName, // Recipient name (Assigned employee)
                    'CAPA Document Re-Opened!', // Subject
                    "
                    <p>Dear $assignedRecipientName,</p>
                    <p>This is a notification that the CAPA document <strong> $capaDocumentId </strong> has been re-opened! </p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>CAPA Document ID: </strong><b> $capaDocumentId </b></li>
                        <li><strong>Date Raised: </strong><b>$dateRaised</b></li>
                        <li><strong>Severity: </strong><b>$severity</b></li>
                        <li><strong>Raised Against: </strong><b> $raisedAgainst </b></li>
                        <li><strong>CAPA Owner: </strong><b> $recipientName </b></li>
                        <li><strong>Assigned To: </strong><b> $assignedRecipientName </b></li>
                        <li><strong>Target Closed Date: </strong><b> $targetCloseDate </b></li>
                        <li><strong>Date Closed: </strong><b> $dateClosed </b></li>
                    </ul>
                    <p> Please review the changes and take any necessary actions regarding this document.</p>
                    <p> This email is sent automatically. Please do not reply</p>
                    <p> Best regards, </p>
                    "
                );
            }
        }

        // Redirect or show success message
        echo "<script>window.location.replace('" . htmlspecialchars($_SERVER['PHP_SELF']) . "');</script>";
        exit();
    } else {
        echo "Error closing document: " . $open_capa_result->error;
    }
}

?>

<form method="POST" id="editCAPADocumentForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="result">
    </p>
    <div class="d-flex justify-content-center align-items-center">
        <button class="btn btn-secondary m-1 d-none" id="cancelOpenCapaBtn" data-bs-dismiss="modal"
            aria-label="Close">Cancel</button>
        <button class="btn signature-btn d-none" id="openCapaBtn" name="openCapaBtn"> Re-Open CAPA</button>
    </div>
    <div id="openForm">
        <div class="d-flex justify-content-center">
            <div class="d-grid grid-template-columns mt-3 mb-4 fw-bold text-center bg-danger text-white py-2 rounded-3"
                style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%;">
                <span></span>
                <span>CAPA Status: <span id="capaStatus" class="text-decoration-underline text-uppercase"></span></span>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-sm signature-btn text-white me-3" id="closeCAPAbtn">Close CAPA</button>
                </div>
            </div>
        </div>
        <div class="row">
            <input type="hidden" name="capaIdToEdit" id="capaIdToEdit">
            <div class="form-group col-md-6">
                <label for="capaDocumentIdToEdit" class="fw-bold"> CAPA Document ID</label>
                <input type="text" name="capaDocumentIdToEdit" class="form-control" id="capaDocumentIdToEdit" required>
                <div class="invalid-feedback">
                    Please provide CAPA Document ID
                </div>
            </div>
            <div class="form-group col-md-6 mt-md-0 mt-3">
                <label for="dateRaisedToEdit" class="fw-bold">Date Raised</label>
                <input type="date" name="dateRaisedToEdit" class="form-control" id="dateRaisedToEdit" required>
                <div class="invalid-feedback">
                    Please provide Date Raised
                </div>
            </div>
            <div class="form-group col-md-12 mt-3">
                <label for="capaDescriptionToEdit" class="fw-bold">CAPA Description</label>
                <textarea class="form-control" name="capaDescriptionToEdit" id="capaDescriptionToEdit" rows="4"
                    required></textarea>
                <div class="invalid-feedback">
                    Please provide CAPA Description
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="severityToEdit" class="fw-bold">Severity</label>
                <select class="form-select" name="severityToEdit" id="severityToEdit" required>
                    <option disabled selected hidden></option>
                    <option value="Observation">Observation</option>
                    <option value="Minor">Minor</option>
                    <option value="Major">Major</option>
                    <option value="Catastrophic">Catastrophic</option>
                </select>
                <div class="invalid-feedback">
                    Please provide Severity
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="raisedAgainstToEdit" class="fw-bold">Raised Against</label>
                <input type="text" name="raisedAgainstToEdit" class="form-control" id="raisedAgainstToEdit" required>
                <div class="invalid-feedback">
                    Please provide Raised Against
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="capaOwnerToEdit" class="fw-bold"> CAPA Owner</label>
                <select name="capaOwnerToEdit" class="form-select" id="capaOwnerToEdit" required
                    onchange="updateCapaOwnerEmailToEdit()">
                    <option disabled selected hidden></option>
                    <?php
                    foreach ($employees as $row) {
                        echo '<option value="' . $row['employee_id'] . '" data-email-owner="' . htmlspecialchars($row['email']) . '"> ' .
                            htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                            htmlspecialchars($row['employee_id']) . ')</option>';
                    }
                    ?>
                </select>
                <input type="text" name="capaOwnerEmail" id="capaOwnerEmailEdit" readonly>
                <div class="invalid-feedback">
                    Please provide CAPA Owner
                </div>
            </div>

            <div class="form-group col-md-6 mt-3">
                <label for="assignedToToEdit" class="fw-bold">Assigned To</label>
                <!-- <input type="text" name="assignedToToEdit" class="form-control" id="assignedToToEdit"> -->
                <select name="assignedToToEdit" class="form-select" id="assignedToToEdit" required
                    onchange="updateAssignedToEmailToEdit()">
                    <option disabled selected hidden></option>
                    <?php
                    foreach ($employees as $row) {
                        echo '<option value="' . $row['employee_id'] . '" data-assigned-to-email="' . htmlspecialchars($row['email']) . '"> ' .
                            htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                            htmlspecialchars($row['employee_id']) . ')</option>';
                    }
                    ?>
                </select>
                <input type="text" name="assignedToEmailEdit" id="assignedToEmailEdit" readonly>
                <div class="invalid-feedback">
                    Please provide the assigned to.
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="mainSourceTypeToEdit" class="fw-bold"> Main Source Type</label>
                <select class="form-select" name="mainSourceTypeToEdit" id="mainSourceTypeToEdit" required>
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
                    Please select a Main Source Type
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="productOrServiceToEdit" class="fw-bold">Product/Service</label>
                <input type="text" name="productOrServiceToEdit" class="form-control" id="productOrServiceToEdit"
                    required>
                <div class="invalid-feedback">
                    Please provide Product/Service
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="mainFaultCategoryToEdit" class="fw-bold">Main Fault Category</label>
                <input type="text" name="mainFaultCategoryToEdit" class="form-control" id="mainFaultCategoryToEdit"
                    required>
                <div class="invalid-feedback">
                    Please provide Main Fault Category
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="targetCloseDateToEdit" class="fw-bold">Target Close Date</label>
                <input type="date" name="targetCloseDateToEdit" class="form-control" id="targetCloseDateToEdit"
                    required>
                <div class="invalid-feedback">
                    Please provide Target Close Date
                </div>
            </div>

            <div class="d-flex justify-content-center mt-5 mb-4">
                <button class="btn btn-dark" name="editCapaDocument" type="submit">Edit Document</button>
            </div>
            <div id="processingMessage" class="alert alert-warning d-none" style="text-align: center;">
                <strong>Please do not close the window. Processing your request...</strong>
            </div>
        </div>
    </div>
    <div id="closeForm" class="d-none">
        <button class="btn btn-sm signature-btn text-white" id="backFormBtn"><i
                class="fa-solid fa-arrow-left fa-xs me-1"></i>Back</button>
        <div class="row mt-3">
            <div class="form-group col-md-12">
                <label for="dateClosed" class="fw-bold">Date Closed</label>
                <input type="date" name="dateClosed" class="form-control" id=dateClosed
                    value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group col-md-12 mt-3">
                <label for="keyTakeaways" class="fw-bold">Key Takeaways</label>
                <textarea class="form-control" name="keyTakeaways" id="keyTakeawaysToEdit" rows="4" required></textarea>
            </div>
            <div class="form-group col-md-12 mt-3">
                <label for="additionalComments" class="fw-bold">Additional Comments</label>
                <textarea class="form-control" name="additionalComments" id="additionalCommentsToEdit" rows="4"
                    required></textarea>
            </div>

            <div class="d-flex justify-content-center mt-5 mb-4">
                <button class="btn btn-dark" name="closeCAPAbtn" type="submit">Close CAPA</button>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const openForm = document.getElementById("openForm");
        const closeForm = document.getElementById("closeForm");
        const closeCAPAbtn = document.getElementById("closeCAPAbtn");
        const backFormBtn = document.getElementById("backFormBtn");
        const statusValue = document.getElementById("capaStatus").textContent.trim();
        const openCapaBtn = document.getElementById("openCapaBtn")

        if (closeCAPAbtn) {
            closeCAPAbtn.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();

                openForm.classList.add("d-none"); // Hide the open form
                closeForm.classList.remove("d-none"); // Show the close form
            });
        }

        if (backFormBtn) {
            backFormBtn.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();

                closeForm.classList.add("d-none"); // Hide the close form
                openForm.classList.remove("d-none"); // Show the open form
            });
        }
    });
</script>

<script>
    // Load saved zoom level from localStorage or use default
    let currentZoom = parseFloat(localStorage.getItem('zoomLevel')) || 1;

    // Apply the saved zoom level
    document.body.style.zoom = currentZoom;

    function zoom(factor) {
        currentZoom *= factor;
        document.body.style.zoom = currentZoom;

        // Save the new zoom level to localStorage
        localStorage.setItem('zoomLevel', currentZoom);
    }

    function resetZoom() {
        currentZoom = 1;
        document.body.style.zoom = currentZoom;

        // Remove the zoom level from localStorage
        localStorage.removeItem('zoomLevel');
    }

    // Optional: Reset zoom level on page load
    window.addEventListener('load', () => {
        document.body.style.zoom = currentZoom;
    });

</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editCAPADocumentForm = document.getElementById("editCAPADocumentForm");
        const capaDocumentId = document.getElementById("capaDocumentId");
        const errorMessage = document.getElementById("result");

        function checkDuplicateDocument() {
            return fetch('../AJAXphp/checkDuplicateDocument.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencode' },
                body: new URLSearchParams({})
            })
        }

            .then(response => response.text()) // Get the plain text response
            .then(data => {
                console.log('Server Response:', data); // Log response for debugging
                return data === '0'; // Return true id no duplicate (0), false if duplicate (1)
            })

        function validateForm() {
            return checkDuplicateDocument().then(isDuplicateValid => {
                return isDuplicateValid;
            })
        }

        editCAPADocumentForm.addEventListener('submit', function (event) {
            // Prevent default form submission
            event.preventDefault();
            event.stopPropagation();

            if (editCAPADocumentForm.checkValidity() === false) {
                // Add was-validated class to show validation feedback
                editCAPADocumentForm.classList.add('was-validated');
            } else {
                // Perform your duplicate document validation if the form is valid
                validateForm().then(isValid => {
                    if (isValid) {
                        // Now submit the form
                        editCAPADocumentForm.submit();
                        // Show the processing message
                        document.getElementById('processingMessage').classList.remove('d-none');

                        // Optionally disable the submit button to prevent multiple submissions
                        document.querySelector('button[type="submit"]').disabled = true;
                    } else {
                        // Add was-validated class and show alert for duplicate ID
                        editCAPADocumentForm.classList.add('was-validated');
                        errorMessage.classList.add('d-none');
                        errorMessage.innerHTML = "Duplicate document found.";
                    }
                })
            }
        })
    })
</script>

<script>
    function updateCapaOwnerEmailToEdit() {
        const select = document.getElementById('capaOwnerToEdit');
        const emailInput = document.getElementById('capaOwnerEmailEdit');
        const selectedOption = select.options[select.selectedIndex];

        // Set the hidden input value to the email of the selected employee
        emailInput.value = selectedOption.getAttribute('data-email-owner');
    }

    function updateAssignedToEmailToEdit() {
        const select = document.getElementById('assignedToToEdit');
        const emailInput = document.getElementById('assignedToEmailEdit');
        const selectedOption = select.options[select.selectedIndex];

        // Set the hidden input value to the email of the selected employee
        emailInput.value = selectedOption.getAttribute('data-assigned-to-email');
    }
</script>