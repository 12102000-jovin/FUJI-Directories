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
$employees_sql = "SELECT e.employee_id, e.first_name, e.last_name, e.email, u.employee_id FROM employees e JOIN users u WHERE e.employee_id = u.employee_id";
$employees_result = $conn->query($employees_sql);

// Fetch all results into an array
$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

// ========================= E D I T  D O C U M E N T ( OPEN FORM ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["capaIdToEdit"])) {
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["capaIdToEdit2"])) {
    $capaIdToEdit2 = $_POST["capaIdToEdit2"];
    $dateClosed = $_POST["dateClosed"];
    $keyTakeaways = $_POST["keyTakeaways"];
    $additionalComments = $_POST["additionalComments"];

    // Fetch CAPA details from the database using the provided ID
    $capa_details_sql = "SELECT capa_owner, assigned_to, capa_document_id, date_raised, severity, raised_against, target_close_date FROM capa WHERE capa_id = ?";
    $capa_details_stmt = $conn->prepare($capa_details_sql);

    if ($capa_details_stmt) {
        $capa_details_stmt->bind_param("i", $capaIdToEdit2);
        $capa_details_stmt->execute();
        $capa_details_result = $capa_details_stmt->get_result();

        if ($capa_details_result && $capa_details_result->num_rows > 0) {
            // Fetch CAPA details
            $capa_data = $capa_details_result->fetch_assoc();
            $capaOwner = $capa_data['capa_owner'];
            $assignedTo = $capa_data['assigned_to'];
            $capaDocumentId = $capa_data['capa_document_id'];
            $dateRaised = $capa_data['date_raised'];
            $severity = $capa_data['severity'];
            $raisedAgainst = $capa_data['raised_against'];
            $targetCloseDate = $capa_data['target_close_date'];
        } else {
            // Handle case where CAPA not found
            error_log("CAPA with ID $capaIdToEdit2 not found.");
            // You may want to redirect or show an error message here
            exit();
        }

        // Close the CAPA details statement
        $capa_details_stmt->close();
    } else {
        error_log("Failed to prepare CAPA details statement: " . $conn->error);
        // You may want to redirect or show an error message here
        exit();
    }

    // Prepare statement for closing CAPA
    $close_capa_sql = "UPDATE capa SET status = 'Closed', date_closed = ?, key_takeaways = ?, additional_comments = ? WHERE capa_id = ?";
    $close_capa_result = $conn->prepare($close_capa_sql);
    $close_capa_result->bind_param("sssi", $dateClosed, $keyTakeaways, $additionalComments, $capaIdToEdit2);

    if ($close_capa_result->execute()) {
        // Build current URL
        $current_url = htmlspecialchars($_SERVER['PHP_SELF']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . urlencode($_SERVER['QUERY_STRING']);
        }

        // Fetch CAPA owner name
        $capa_owner_name_sql = "SELECT first_name, last_name, email FROM employees WHERE employee_id = ?";
        $capa_owner_name_result = $conn->prepare($capa_owner_name_sql);

        if ($capa_owner_name_result) {
            $capa_owner_name_result->bind_param("i", $capaOwner);
            $capa_owner_name_result->execute();
            $fullName = $capa_owner_name_result->get_result();

            if ($fullName && $fullName->num_rows > 0) {
                $employee = $fullName->fetch_assoc();
                $recipientName = $employee['first_name'] . ' ' . $employee['last_name'];
                $capaOwnerEmail = $employee['email']; // Assuming you also want to send the email to the CAPA owner

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
                        error_log("Assigned employee with ID $assignedTo not found");
                    }

                    // Close the assigned_to statement
                    $assigned_to_name_result->close();
                } else {
                    error_log("Failed to prepare assigned_to statement: " . $conn->error);
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
                        <li><strong>Raised Against: </strong><b>$raisedAgainst </b></li>
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

                // Send email to the Assigned employee
                $emailSender->sendEmail(
                    $assignedRecipientEmail, // Recipient email (Assigned employee)
                    $assignedRecipientName,   // Recipient name (Assigned employee)
                    'CAPA Document Closed!',  // Subject
                    "
                    <p>Dear $assignedRecipientName,</p>
                    <p>This is a notification that the CAPA document <strong> $capaDocumentId </strong> has been closed!</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li><strong>CAPA Document ID: </strong><b> $capaDocumentId </b></li>
                        <li><strong>Date Raised: </strong><b> $dateRaised</b></li>
                        <li><strong>Severity: </strong><b> $severity </b></li>
                        <li><strong>Raised Against: </strong><b>$raisedAgainst </b></li>
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

                // Redirect
                echo "<script>window.location.replace('" . $current_url . "');</script>";
                exit();
            }
        }
    } else {
        echo "Error updating record: " . $close_capa_result->error;
    }
}

// ========================= E D I T  C A P A  S T A T U S ( RE-OPEN CAPA ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["capaIdToEdit3"])) {
    $capaIdToEdit3 = $_POST["capaIdToEdit3"];

    // Fetch CAPA details from the database using the provided ID
    $capa_details_sql = "SELECT capa_owner, assigned_to, capa_document_id, date_raised, severity, raised_against, target_close_date FROM capa WHERE capa_id = ?";
    $capa_details_stmt = $conn->prepare($capa_details_sql);

    if ($capa_details_stmt) {
        $capa_details_stmt->bind_param("i", $capaIdToEdit3);
        $capa_details_stmt->execute();
        $capa_details_result = $capa_details_stmt->get_result();

        if ($capa_details_result && $capa_details_result->num_rows > 0) {
            // Fetch CAPA details
            $capa_data = $capa_details_result->fetch_assoc();
            $capaOwner = $capa_data['capa_owner'];
            $assignedTo = $capa_data['assigned_to'];
            $capaDocumentId = $capa_data['capa_document_id'];
            $dateRaised = $capa_data['date_raised'];
            $severity = $capa_data['severity'];
            $raisedAgainst = $capa_data['raised_against'];
            $targetCloseDate = $capa_data['target_close_date'];
        } else {
            // Handle case where CAPA not found
            error_log("CAPA with ID $capaIdToEdit3 not found.");
            // You may want to redirect or show an error message here
            exit();
        }

        // Close the CAPA details statement
        $capa_details_stmt->close();
    } else {
        error_log("Failed to prepare CAPA details statement: " . $conn->error);
        // You may want to redirect or show an error message here
        exit();
    }

    $open_capa_sql = "UPDATE capa SET status = 'Open' , date_closed = NULL WHERE capa_id = ?";
    $open_capa_result = $conn->prepare($open_capa_sql);
    $open_capa_result->bind_param("i", $capaIdToEdit3);


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

<style>
    .overlay {
        position: fixed;
        /* Cover the entire viewport */
        top: 0;
        left: 0;
        width: 100%;
        /* Full width */
        height: 100%;
        /* Full height */
        background-color: rgba(0, 0, 0, 0.5);
        /* Semi-transparent background */
        display: flex;
        /* Center the content */
        justify-content: center;
        /* Center horizontally */
        align-items: center;
        /* Center vertically */
        z-index: 9999;
        /* Make sure it is on top of other content */
        text-align: center;
        /* Center text within the spinner */
    }

    #loadingSpinnerEdit,
    #loadingSpinnerClose,
    #loadingSpinnerReOpen {
        color: white;
        /* Change text color for visibility */
    }

    .loading-text {
        color: white;
        /* Set text color to white */
        font-size: 1.5rem;
        /* Increase font size for better visibility */
        margin-top: 10px;
        /* Add some space between the spinner and text */
    }
</style>

<!-- Form for editing CAPA Document -->
<form method="POST" id="editCAPADocumentForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="result"></p>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="resultError"></p>
    <!-- Open CAPA Form -->
    <div id="openForm">
        <div class="d-flex justify-content-center">
            <div class="d-grid grid-template-columns mt-3 mb-4 fw-bold text-center bg-danger text-white py-2 rounded-3"
                style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%;">
                <span></span>
                <span>CAPA Status: <span id="capaStatus" class="text-decoration-underline text-uppercase"></span></span>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-sm signature-btn text-white me-3" id="closeCAPAbtn"
                        name="closeCAPAbtn">Close
                        CAPA</button>
                </div>
            </div>
        </div>
        <div class="row">
            <input type="text" name="capaIdToEdit" id="capaIdToEdit">
            <div class="form-group col-md-6">
                <label for="capaDocumentIdToEdit" class="fw-bold"> CAPA Document ID</label>
                <input type="text" name="capaDocumentIdToEdit" class="form-control" id="capaDocumentIdToEdit" required>
                <div class="invalid-feedback">Please provide CAPA Document ID</div>
            </div>
            <div class="form-group col-md-6 mt-md-0 mt-3">
                <label for="dateRaisedToEdit" class="fw-bold">Date Raised</label>
                <input type="date" name="dateRaisedToEdit" class="form-control" id="dateRaisedToEdit" required>
                <div class="invalid-feedback">Please provide Date Raised</div>
            </div>
            <div class="form-group col-md-12 mt-3">
                <label for="capaDescriptionToEdit" class="fw-bold">CAPA Description</label>
                <textarea class="form-control" name="capaDescriptionToEdit" id="capaDescriptionToEdit" rows="4"
                    required></textarea>
                <div class="invalid-feedback">Please provide CAPA Description</div>
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
                <div class="invalid-feedback">Please provide Severity</div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="raisedAgainstToEdit" class="fw-bold">Raised Against</label>
                <input type="text" name="raisedAgainstToEdit" class="form-control" id="raisedAgainstToEdit" required>
                <div class="invalid-feedback">Please provide Raised Against</div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="capaOwnerToEdit" class="fw-bold"> CAPA Owner</label>
                <select name="capaOwnerToEdit" class="form-select" id="capaOwnerToEdit" required
                    onchange="updateCapaOwnerEmailToEdit()">
                    <option disabled selected hidden></option>
                    <?php foreach ($employees as $row): ?>
                        <option value="<?= $row['employee_id'] ?>"
                            data-email-owner="<?= htmlspecialchars($row['email']) ?>">
                            <?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' . htmlspecialchars($row['employee_id']) . ')' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="capaOwnerEmail" id="capaOwnerEmailEdit" readonly>
                <div class="invalid-feedback">Please provide CAPA Owner</div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="assignedToToEdit" class="fw-bold">Assigned To</label>
                <select name="assignedToToEdit" class="form-select" id="assignedToToEdit" required
                    onchange="updateAssignedToEmailToEdit()">
                    <option disabled selected hidden></option>
                    <?php foreach ($employees as $row): ?>
                        <option value="<?= $row['employee_id'] ?>"
                            data-assigned-to-email="<?= htmlspecialchars($row['email']) ?>">
                            <?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' . htmlspecialchars($row['employee_id']) . ')' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="assignedToEmailEdit" id="assignedToEmailEdit" readonly>
                <div class="invalid-feedback">Please provide the assigned to.</div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="mainSourceTypeToEdit" class="fw-bold"> Main Source Type</label>
                <select class="form-select" name="mainSourceTypeToEdit" id="mainSourceTypeToEdit" required>
                    <option disabled selected hidden></option>
                    <option value="Customer Complaint">Customer Complaint</option>
                    <option value="External Issue">External Issue</option>
                    <option value="Supplier Issue">Supplier Issue</option>
                    <option value="Internal Audit">Internal Audit</option>
                    <option value="External Audit">External Audit</option>
                    <option value="Internal Issue">Internal Issue</option>
                    <option value="Employee Suggestion">Employee Suggestion</option>
                    <option value="WHS Issue">WHS Issue</option>
                </select>
                <div class="invalid-feedback">Please select a Main Source Type</div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="productOrServiceToEdit" class="fw-bold">Product/Service</label>
                <input type="text" name="productOrServiceToEdit" class="form-control" id="productOrServiceToEdit"
                    required>
                <div class="invalid-feedback">Please provide Product/Service</div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="mainFaultCategoryToEdit" class="fw-bold">Main Fault Category</label>
                <input type="text" name="mainFaultCategoryToEdit" class="form-control" id="mainFaultCategoryToEdit"
                    required>
                <div class="invalid-feedback">Please provide Main Fault Category</div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="targetCloseDateToEdit" class="fw-bold">Target Close Date</label>
                <input type="date" name="targetCloseDateToEdit" class="form-control" id="targetCloseDateToEdit"
                    required>
                <div class="invalid-feedback">Please provide Target Close Date</div>
            </div>
            <div class="d-flex justify-content-center mt-5 mb-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-dark d-none ms-1" name="editCapaDocument" type="submit">Edit Document</button>
            </div>
        </div>
    </div>
    <!-- Loading Spinner -->
    <div id="loadingSpinnerEdit" class="d-none overlay">
        <div class="spinner-border me-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="loading-text mt-3 fw-bold">Please wait while the CAPA is being edited and notifications are sent to
            the
            assigned employee and the CAPA owner...</p>
    </div>
</form>

<!-- Form for closing CAPA -->
<form method="POST" id="closeCAPADocumentForm" class="d-none" novalidate>
    <button class="btn btn-sm signature-btn text-white" id="backFormBtn"><i
            class="fa-solid fa-arrow-left fa-xs me-1"></i>Back</button>
    <div class="row mt-3">
        <input type="text" name="capaIdToEdit2" id="capaIdToEdit2">
        <div class="form-group col-md-12">
            <label for="dateClosed" class="fw-bold">Date Closed</label>
            <input type="date" name="dateClosed" class="form-control" id="dateClosed"
                value="<?php echo date('Y-m-d'); ?>" required>
            <div class="invalid-feedback">Please provide Date Closed</div>
        </div>
        <div class="form-group col-md-12 mt-3">
            <label for="keyTakeaways" class="fw-bold">Key Takeaways</label>
            <textarea class="form-control" name="keyTakeaways" id="keyTakeawaysToEdit" rows="4"></textarea>
        </div>
        <div class="form-group col-md-12 mt-3">
            <label for="additionalComments" class="fw-bold">Additional Comments</label>
            <textarea class="form-control" name="additionalComments" id="additionalCommentsToEdit" rows="4"></textarea>
        </div>
        <div class="d-flex justify-content-center mt-5">
            <button class="btn btn-dark" name="closeCapaDocument" type="submit">Close Document</button>
        </div>
    </div>
    <!-- Loading Spinner -->
    <div id="loadingSpinnerClose" class="d-none overlay">
        <div class="spinner-border me-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="loading-text mt-3 fw-bold">Please wait while the CAPA is being closed and notifications are sent to
            the assigned employee and the CAPA owner...</p>
    </div>
</form>

<!-- Form for reopen CAPA -->
<div class="d-flex justify-content-center align-items-center">
    <button class="btn btn-secondary m-1 d-none" id="cancelOpenCapaBtn" data-bs-dismiss="modal"
        aria-label="Close">Cancel</button>
    <form method="POST" id="reOpenCapaForm">
        <input type="text" name="capaIdToEdit3" id="capaIdToEdit3">
        <button class="btn signature-btn d-none" id="openCapaBtn" name="openCapaBtn"> Re-Open
            CAPA</button>
    </form>
    <!-- Loading Spinner -->
    <div id="loadingSpinnerReOpen" class="d-none overlay">
        <div class="spinner-border me-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="loading-text mt-3 fw-bold">Please wait while the CAPA is being re-opened and notifications are sent to
            the
            assigned employee and the CAPA owner...</p>
    </div>
</div>

<!-- Additional Script for Form Switching -->
<script>
    document.getElementById('closeCAPAbtn').addEventListener('click', function () {
        document.getElementById('editCAPADocumentForm').classList.add('d-none');
        document.getElementById('closeCAPADocumentForm').classList.remove('d-none');
    });

    document.getElementById('backFormBtn').addEventListener('click', function (event) {
        event.preventDefault();
        document.getElementById('closeCAPADocumentForm').classList.add('d-none');
        document.getElementById('editCAPADocumentForm').classList.remove('d-none');
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Variables for editing CAPA document
        const editCAPADocumentForm = document.getElementById("editCAPADocumentForm");
        const capaDocumentId = document.getElementById("capaDocumentIdToEdit");
        const capaId = document.getElementById("capaIdToEdit");
        const errorMessage = document.getElementById("resultError");
        const loadingSpinnerEdit = document.getElementById("loadingSpinnerEdit");
        const loadingSpinnerClose = document.getElementById("loadingSpinnerClose");
        const loadingSpinnerReOpen = document.getElementById("loadingSpinnerReOpen");
        const editCapaDocumentBtn = document.getElementById("editCapaDocumentBtn");

        // Function to check for duplicate documents
        function checkDuplicateDocument() {
            console.log("This", capaDocumentId.value);
            console.log("This", capaId.value);
            return fetch('../AJAXphp/check-capa-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ capaDocumentId: capaDocumentId.value, capaId: capaId.value })
            })
                .then(response => response.text())
                .then(data => {
                    console.log('Server Response:', data); // Log response for debugging
                    return data === '0'; // Return true if no duplicate (0), false if duplicate (1)
                });
        }

        // Function to validate the form
        function validateForm() {
            return checkDuplicateDocument().then(isDuplicateValid => {
                return isDuplicateValid;
            });
        }

        // Event listener for editing CAPA document
        editCAPADocumentForm.addEventListener('submit', function (event) {
            // Prevent default form submission
            event.preventDefault();
            event.stopPropagation();

            // Clear previous error message
            errorMessage.classList.add('d-none');
            errorMessage.innerHTML = '';

            // Check validity of the form
            if (editCAPADocumentForm.checkValidity() === false) {
                editCAPADocumentForm.classList.add('was-validated');
            } else {
                // Perform duplicate document validation if the form is valid
                validateForm().then(isValid => {
                    if (isValid) {
                        // Show loading spinner
                        loadingSpinnerEdit.classList.remove('d-none');

                        // Perform AJAX submission instead of standard form submission
                        fetch(editCAPADocumentForm.action, {
                            method: 'POST',
                            body: new FormData(editCAPADocumentForm)
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.text(); // or response.json() depending on your server response
                            })
                            .then(data => {
                                loadingSpinnerEdit.classList.add('d-none'); // Hide loading spinner after submission
                                // Handle the server response (you may want to show a success message or update the UI)
                                location.reload();
                            })
                            .catch(error => {
                                // Handle error (display message to user)
                                loadingSpinnerEdit.classList.add('d-none');
                                errorMessage.classList.remove('d-none');
                                errorMessage.innerHTML = 'An error occurred. Please try again.';
                            });
                    } else {
                        editCAPADocumentForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = 'Duplicate document found.';
                    }
                });
            }
        });

        closeCAPADocumentForm.addEventListener('submit', function (event) {
            // Prevent default form submission
            event.preventDefault();
            event.stopPropagation();

            console.log('Form submitted'); // Debug log

            // Check validity of the form
            if (closeCAPADocumentForm.checkValidity() === false) {
                console.log('Form is invalid');
                closeCAPADocumentForm.classList.add('was-validated');
            } else {
                console.log('Form is valid'); // Debug log

                // Show loading spinner
                loadingSpinnerClose.classList.remove('d-none');
                console.log('Loading spinner shown');

                // Close the capa 
                fetch(closeCAPADocumentForm.action, {
                    method: 'POST',
                    body: new FormData(closeCAPADocumentForm)
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text(); // or response.json() depending on your server response
                    })
                    .then(data => {
                        loadingSpinnerClose.classList.add('d-none'); // Hide loading spinner after submission
                        console.log('Spinner hidden');
                        // Handle the server response (you may want to show a success message or update the UI)
                        location.reload();
                    })
                    .catch(error => {
                        loadingSpinnerClose.classList.add('d-none'); // Hide spinner on error
                        console.error('Error:', error); // Log error
                    });
            }
        });

        reOpenCapaForm.addEventListener('submit', function (event) {
            // Prevent default form submission
            event.preventDefault();
            event.stopPropagation();

            loadingSpinnerReOpen.classList.remove('d-none');

            fetch(reOpenCapaForm.action, {
                method: 'POST',
                body: new FormData(reOpenCapaForm)
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text(); // or response.json() depending on your server response
                })
                .then(data => {
                    loadingSpinnerReOpen.classList.add('d-none');
                    console.log('Spinner hidden');
                    // Handle the server response (you may want to show a success message or update the UI)
                    location.reload();
                })
                .catch(error => {
                    loadingSpinnerReOpen.classList.add('d-none'); // Hide spinner on error
                    console.error('Error:', error); // Log error
                });

        })

        // Functions to update emails based on selection
        function updateCapaOwnerEmailToEdit() {
            const select = document.getElementById('capaOwnerToEdit');
            const emailInput = document.getElementById('capaOwnerEmailEdit');
            const selectedOption = select.options[select.selectedIndex];
            emailInput.value = selectedOption.getAttribute('data-email-owner'); // Set email
        }

        function updateAssignedToEmailToEdit() {
            const select = document.getElementById('assignedToToEdit');
            const emailInput = document.getElementById('assignedToEmailEdit');
            const selectedOption = select.options[select.selectedIndex];
            emailInput.value = selectedOption.getAttribute('data-assigned-to-email'); // Set email
        }

        // Event listeners for email update functions
        document.getElementById('capaOwnerToEdit').addEventListener('change', updateCapaOwnerEmailToEdit);
        document.getElementById('assignedToToEdit').addEventListener('change', updateAssignedToEmailToEdit);
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Get the form and the edit button
        const form = document.getElementById("editCAPADocumentForm");
        const editButton = form.querySelector("button[name='editCapaDocument']");
        const closeCAPAbtn = form.querySelector("button[name='closeCAPAbtn']");
        const modal = document.getElementById("editDocumentModal"); // Replace with your modal's actual ID

        // Function to check if there is any input in the form
        function checkInput() {
            const inputs = form.querySelectorAll("input, textarea, select");
            let isFilled = false;

            // Check each input field for a value
            inputs.forEach(input => {
                if (input.value.trim() !== "") {
                    isFilled = true; // At least one input is filled
                }
            });

            // Show or hide the edit button based on input presence
            if (isFilled) {
                editButton.classList.remove("d-none"); // Show the button
                closeCAPAbtn.classList.add("d-none");
            } else {
                editButton.classList.add("d-none"); // Hide the button
                closeCAPAbtn.classList.remove("d-none");
            }
        }

        // Add event listeners to all input fields
        form.addEventListener("input", checkInput);

        // Reset form and button visibility when the modal is closed
        modal.addEventListener("hidden.bs.modal", function () {
            form.reset(); // Reset all form fields to their default values
            editButton.classList.add("d-none"); // Hide the edit button
            closeCAPAbtn.classList.remove("d-none"); // Show the close button
        });

        // Optionally trigger checkInput on page load
        checkInput();
    });
</script>