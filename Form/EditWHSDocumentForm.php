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

// ========================= E D I T  D O C U M E N T ( OPEN FORM ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["whsIdToEdit"])) {
    $whsIdToEdit = $_POST["whsIdToEdit"];
    $whsDocumentId = $_POST["whsDocumentIdToEdit"];
    $involvedPersonName = $_POST["involvedPersonNameToEdit"];
    $description = $_POST["whsDescriptionToEdit"];
    $incidentDate = $_POST["incidentDateToEdit"];
    $dateRaised = $_POST["dateRaisedToEdit"];
    $department = $_POST["departmentToEdit"];
    $nearMiss = $_POST["nearMissToEdit"];
    $firstAidGiven = $_POST["firstAidGivenToEdit"];
    $medicalTreatmentCase = $_POST["medicalTreatmentCaseToEdit"];
    $recordableIncident = $_POST["recordableIncidentToEdit"];
    $restrictedWorkCase = $_POST["restrictedWorkCaseToEdit"];
    $lostTimeCase = $_POST["lostTimeCaseToEdit"];
    $fiveDaysOff = $_POST["fiveDaysOffToEdit"];
    $insuranceNotified = $_POST["insuranceNotifiedToEdit"];
    $directorNotified = $_POST["directorNotifiedToEdit"];

    // Prepare statement
    $edit_document_sql = "UPDATE whs SET whs_document_id = ?, involved_person_name = ?, `description` = ?, incident_date = ?, date_raised = ?, department = ?, near_miss = ?, first_aid_given = ?, medical_treatment_case = ?, recordable_incident = ?, restricted_work_case = ?, lost_time_case = ?, five_days_off = ?, insurance_notified = ?, director_notified = ? WHERE whs_id = ?";
    $edit_document_result = $conn->prepare($edit_document_sql);

    // Check if the statement preparation was successful
    if ($edit_document_result === false) {
        die("Error preparing the statement: " . $conn->error);
    }

    // Bind parameters
    $edit_document_result->bind_param(
        "ssssssiiiiiiiisi",
        $whsDocumentId,
        $involvedPersonName,
        $description,
        $incidentDate,
        $dateRaised,
        $department,
        $nearMiss,
        $firstAidGiven,
        $medicalTreatmentCase,
        $recordableIncident,
        $restrictedWorkCase,
        $lostTimeCase,
        $fiveDaysOff,
        $insuranceNotified,
        $directorNotified,
        $whsIdToEdit
    );

    // Execute and check for errors
    if ($edit_document_result->execute()) {
        // Build current URL
        $current_url = htmlspecialchars($_SERVER['PHP_SELF']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . urlencode($_SERVER['QUERY_STRING']);
        }

        // Redirect
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    } else {
        echo "Error executing the statement: " . $edit_document_result->error;
    }
}

// ========================= E D I T  D O C U M E N T ( CLOSE FORM ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["whsIdToEdit2"])) {
    $whsIdToEdit2 = $_POST["whsIdToEdit2"];
    $dateClosed = $_POST["dateClosedToEdit"];
    $additionalComments = $_POST["additionalCommentsToEdit"];

    echo $whsIdToEdit2;
    echo $dateClosed;
    echo $additionalComments;

    // Prepare statement for closing WHS
    $close_whs_sql = "UPDATE whs SET status = 'Closed', date_closed = ?, additional_comments = ? WHERE whs_id = ?";
    $close_whs_result = $conn->prepare($close_whs_sql);
    $close_whs_result->bind_param("ssi", $dateClosed, $additionalComments, $whsIdToEdit2);

    if ($close_whs_result->execute()) {
        // Build current URL
        $current_url = htmlspecialchars($_SERVER['PHP_SELF']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . urlencode($_SERVER['QUERY_STRING']);
        }

        // Redirect
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    } else {
        echo "Error updating record: " . $close_whs_result->error;
    }
}

// ========================= E D I T  D O C U M E N T ( RE-OPEN WHS ) =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["whsIdToEdit3"])) {
    $whsIdToEdit3 = $_POST["whsIdToEdit3"];

    // Prepare statement for reopening WHS document
    $open_whs_sql = "UPDATE whs SET status = 'Open', date_closed = NULL WHERE whs_id = ?";
    $open_whs_result = $conn->prepare($open_whs_sql);

    // Bind parameters
    $open_whs_result->bind_param("i", $whsIdToEdit3);

    // Execute and check for errors
    if ($open_whs_result->execute()) {
        // Redirect on successful execution
        echo "<script>window.location.replace('" . htmlspecialchars($_SERVER['PHP_SELF']) . "');</script>";
        exit();
    } else {
        // Display error message if execution fails
        echo "Error reopening document: " . $open_whs_result->error;
    }
}



?>

<style>
    /* Style for checked state */
    .btn-check:checked+.btn-custom {
        background-color: #043f9d !important;
        border-color: #043f9d !important;
        color: white !important;
        /* Text color when selected */
    }

    /* Optional: Adjust hover state if needed */
    .btn-custom:hover {
        background-color: #032b6b;
        border-color: #032b6b;
        color: white;
    }
</style>

<form method="POST" id="editWHSDocumentForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width: 100%"
        id="documentCloseText"></p>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="resultError"></p>

    <!-- Open WHS Form -->
    <div id="openForm">
        <div class="d-flex justify-content-center">
            <div class="d-grid grid-template-columns mt-3 mb-4 fw-bold text-center bg-danger text-white py-2 rounded-3"
                style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%;">
                <span></span>
                <span>WHS Status: <span id="whsStatus" class="text-decoration-underline text-uppercase"></span></span>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-sm signature-btn text-white me-3" id="closeWHSbtn"
                        name="closeWHSbtn">Close
                        WHS</button>
                </div>
            </div>
        </div>
        <div class="row">
            <input type="text" name="whsIdToEdit" id="whsIdToEdit">
            <div class="form-group col-md-6">
                <label for="whsDocumentIdToEdit" class="fw-bold"> WHS Document ID</label>
                <input type="text" name="whsDocumentIdToEdit" class="form-control" id="whsDocumentIdToEdit" required>
                <div class="invalid-feedback">
                    Please provide WHS Document ID.
                </div>
            </div>
            <div class="form-group col-md-6 mt-md-0 mt-3">
                <label for="involvedPersonNameToEdit" class="fw-bold">Involved Person Name</label>
                <select name="involvedPersonNameToEdit" class="form-select" id="involvedPersonNameToEdit" required
                    onchange="updateInvolvedPersonNameEmail()">
                    <option disabled selected hidden></option>
                    <?php
                    foreach ($employees as $row) {
                        echo '<option value="' . htmlspecialchars($row['employee_id']) . '" > ' .
                            htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                            htmlspecialchars($row['employee_id']) . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-12 mt-3">
                <label for="whsDescriptionToEdit" class="fw-bold">WHS Description</label>
                <textarea class="form-control" name="whsDescriptionToEdit" id="whsDescriptionToEdit" rows="4"
                    required></textarea>
                <div class="invalid-feedback">
                    Please provide the WHS Description.
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <label for="incidentDateToEdit" class="fw-bold">Incident Date</label>
                <input type="date" name="incidentDateToEdit" class="form-control" id="incidentDateToEdit" required>
                <div class="invalid-feedback">
                    Please provide the Incident Date
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <label for="dateRaisedToEdit" class="fw-bold">Date Raised</label>
                <input type="date" name="dateRaisedToEdit" class="form-control" id="dateRaisedToEdit" required>
                <div class="invalid-feedback">
                    Please provide the Date Raised
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <label for="departmentToEdit" class="fw-bold">Department</label>
                <select class="form-select" aria-label="department" name="departmentToEdit" id="departmentToEdit"
                    required>
                    <option disabled selected hidden></option>
                    <option value="Electrical">Electrical</option>
                    <option value="Office">Office</option>
                    <option value="Sheet Metal"> Sheet Metal</option>
                    <option value="Site"> Site</option>
                    <option value="Store"> Store</option>
                </select>
                <div class="invalid-feedback">
                    Please provide the department
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="nearMissToEdit" class="fw-bold">Near Miss</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="nearMissToEdit" id="nearMissToEditYes"
                            value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="nearMissToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="nearMissToEdit" id="nearMissToEditNo"
                            value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="nearMissToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="firstAidGivenToEdit" class="fw-bold">First Aid Given</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="firstAidGivenToEdit"
                            id="firstAidGivenToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="firstAidGivenToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="firstAidGivenToEdit"
                            id="firstAidGivenToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="firstAidGivenToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="medicalTreatmentCaseToEdit" class="fw-bold">Medical Treatment Case</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="medicalTreatmentCaseToEdit"
                            id="medicalTreatmentCaseToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="medicalTreatmentCaseToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="medicalTreatmentCaseToEdit"
                            id="medicalTreatmentCaseToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="medicalTreatmentCaseToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-4">
                <div class="d-flex flex-column">
                    <label for="recordableIncidentToEdit" class="fw-bold" style="font-size: 11px">Automated
                        Notifiable/Recordable
                        Incident</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="recordableIncidentToEdit"
                            id="recordableIncidentToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="recordableIncidentToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="recordableIncidentToEdit"
                            id="recordableIncidentToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="recordableIncidentToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="restrictedWorkCaseToEdit" class="fw-bold">Restricted Work Case</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="restrictedWorkCaseToEdit"
                            id="restrictedWorkCaseToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="restrictedWorkCaseToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="restrictedWorkCaseToEdit"
                            id="restrictedWorkCaseToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="restrictedWorkCaseToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="lostTimeCaseToEdit" class="fw-bold">Lost Time Case</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="lostTimeCaseToEdit"
                            id="lostTimeCaseToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="lostTimeCaseToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="lostTimeCaseToEdit"
                            id="lostTimeCaseToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="lostTimeCaseToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="fiveDaysOffToEdit" class="fw-bold">More than 5 days off work</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="fiveDaysOffToEdit"
                            id="fiveDaysOffToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="fiveDaysOffToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="fiveDaysOffToEdit"
                            id="fiveDaysOffToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="fiveDaysOffToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="insuranceNotifiedToEdit" class="fw-bold">Insurance Notified</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="insuranceNotifiedToEdit"
                            id="insuranceNotifiedToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="insuranceNotifiedToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="insuranceNotifiedToEdit"
                            id="insuranceNotifiedToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="insuranceNotifiedToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-4 mt-3">
                <div class="d-flex flex-column">
                    <label for="directorNotifiedToEdit" class="fw-bold">Director Notified</label>
                    <div class="btn-group col-3 col-md-4" role="group">
                        <input type="radio" class="btn-check btn-custom" name="directorNotifiedToEdit"
                            id="directorNotifiedToEditYes" value="1" autocomplete="off" required>
                        <label class="btn btn-sm btn-custom" for="directorNotifiedToEditYes"
                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                        <input type="radio" class="btn-check btn-custom" name="directorNotifiedToEdit"
                            id="directorNotifiedToEditNo" value="0" autocomplete="off" checked required>
                        <label class="btn btn-sm btn-custom" for="directorNotifiedToEditNo"
                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-5 mb-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-dark d-none ms-1" name="editWhsDocument" type="submit">Edit Document</button>
            </div>
        </div>
    </div>
</form>

<!-- Form for closing WHS -->
<form method="POST" id="closeWHSDocumentForm" class="d-none" novalidate>
    <button class="btn btn-sm signature-btn text-white" id="backFormBtn">
        <i class="fa-solid fa-arrow-left fa-xs me-1"></i>Back
    </button>
    <div class="row mt-3">
        <input type="hidden" name="whsIdToEdit2" id="whsIdToEdit2">
        <div class="form-group col-md-12">
            <input type="date" name="dateClosedToEdit" class="form-control" id="dateClosedToEdit"
                value="<?php echo date('Y-m-d'); ?>" required>
            <div class="invalid-feedback">Please provide Date Closed</div>
        </div>
        <div class="form-group col-md-12 mt-3">
            <label for="additionalCommentsToEdit" class="fw-bold">Additional Comments</label>
            <textarea class="form-control" name="additionalCommentsToEdit" id="additionalCommentsToEdit"
                rows="4"></textarea>
        </div>
        <div class="d-flex justify-content-center mt-5">
            <button class="btn btn-dark" name="closeWHSDocument" type="submit">Close Document</button>
        </div>
    </div>
</form>

<!-- Form for reopen WHS -->
<div class="d-flex justify-content-center align-items-center">
    <button class="btn btn-secondary m-1 d-none" id="cancelOpenWhsBtn" data-bs-dismiss="modal"
        aria-label="Close">Cancel</button>
    <form method="POST" id="reOpenWhsForm">
        <input type="hidden" name="whsIdToEdit3" id="whsIdToEdit3">
        <button class="btn signature-btn d-none" id="openWhsBtn" name="openWhsBtn">Re-Open WHS </button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editWHSDocumentForm = document.getElementById("editWHSDocumentForm");
        const errorMessage = document.getElementById("resultError");
        const whsId = document.getElementById("whsIdToEdit");
        const whsDocumentId = document.getElementById("whsDocumentIdToEdit");

        // Function to check for duplicate documents
        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-whs-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ whsDocumentId: whsDocumentId.value, whsId: whsId.value })
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

        // Event listener for editing WHS document
        editWHSDocumentForm.addEventListener('submit', function (event) {
            // Prevent default form submission
            event.preventDefault();
            event.stopPropagation();

            // Clear previous error message
            errorMessage.classList.add('d-none');
            errorMessage.innerHTML = '';

            // Check validity of the form
            if (editWHSDocumentForm.checkValidity() === false) {
                editWHSDocumentForm.classList.add('was-validated');
            } else {
                // Perform duplicate document validation if the form is valid
                validateForm().then(isValid => {
                    if (isValid) {
                        // Show loading spinner
                        // loadingSpinnerEdit.classList.remove('d-none');

                        // Perform AJAX submission instead of standard form submission
                        fetch(editWHSDocumentForm.action, {
                            method: 'POST',
                            body: new FormData(editWHSDocumentForm)
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.text(); // or response.json() depending on your server response
                            })
                            .then(data => {
                                // loadingSpinnerEdit.classList.add('d-none'); // Hide loading spinner after submission
                                // Handle the server response (you may want to show a success message or update the UI)
                                location.reload();
                            })
                            .catch(error => {
                                // Handle error (display message to user)
                                // loadingSpinnerEdit.classList.add('d-none');
                                errorMessage.classList.remove('d-none');
                                errorMessage.innerHTML = 'An error occurred. Please try again.';
                            });
                    } else {
                        editWHSDocumentForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = 'Duplicate document found.';
                    }
                });
            }
        });
    })
</script>

<!-- Additional Script for Form Switching -->
<script>
    document.getElementById('editDocumentModal').addEventListener('hidden.bs.modal', function () {
        // Refresh the page when the modal is closed
        location.reload();
    })

    document.getElementById('closeWHSbtn').addEventListener('click', function () {
        document.getElementById('editWHSDocumentForm').classList.add('d-none');
        document.getElementById('closeWHSDocumentForm').classList.remove('d-none');
    });

    document.getElementById('backFormBtn').addEventListener('click', function (event) {
        event.preventDefault();
        document.getElementById('closeWHSDocumentForm').classList.add('d-none');
        document.getElementById('editWHSDocumentForm').classList.remove('d-none');
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editWHSDocumentForm = document.getElementById("editWHSDocumentForm");
        const editButton = editWHSDocumentForm.querySelector("button[name='editWhsDocument']");
        const closeWHSbtn = editWHSDocumentForm.querySelector("button[name='closeWHSbtn']");
        const modal = document.getElementById("editDocumentModal");

        // Function to check if there is any input in the editWHSDocumentForm
        function checkInput() {
            const inputs = editWHSDocumentForm.querySelectorAll("input, textarea, select");
            let isFilled = false;

            // Check each input field for a value
            inputs.forEach(input => {
                if (input.value.trim() !== "") {
                    isFilled = true; // At least one input is filled
                }
            });

            // Show or hide the edit button based on input presence
            if (isFilled) {
                editButton.classList.remove("d-none");
                closeWHSbtn.classList.add("d-none");
            } else {
                editButton.classList.add("d-none");
                closeWHSbtn.classList.remove("d-none");
            }
        }
        // Add event listeners to all input fields
        editWHSDocumentForm.addEventListener("input", checkInput);

        // Reset editWHSDocumentForm and button visibility when the modal is closed
        modal.addEventListener("hidden.bs.modal", function () {
            editWHSDocumentForm.reset(); // Reset all editWHSDocumentForm fields to their default values
            editButton.classList.add("d-none"); // Hide the edit button
            closeWHSbtn.classList.remove("d-none"); // Show the close button
        });
    });
</script>
