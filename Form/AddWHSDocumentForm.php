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

// ========================= SQL to get latest WHS ID =========================
$whs_document_id_sql = "SELECT MAX(whs_document_id) as latest_id FROM whs";
$whs_document_id_result = $conn->query($whs_document_id_sql);

if ($whs_document_id_result->num_rows > 0) {
    $row = $whs_document_id_result->fetch_assoc();
    $latest_id = $row['latest_id'];

    // Check if there's latest ID
    if ($latest_id) {
        // Extract the numeric part
        $number = (int) substr($latest_id, 4);
        $next_number = $number + 1;
    } else {
        $next_number = 1;
    }

    // Format the next ID
    $next_whs_id = "WHS-" . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    // echo "The next WHS Document ID is: " . $next_whs_id;
} else {
    echo "No records found";
}

// ========================= A D D  W H S  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['whsDocumentId'])) {
    $whsDocumentId = $_POST["whsDocumentId"];
    $involvedPersonName = $_POST['involvedPersonName'];
    $whsDescription = htmlspecialchars($_POST['whsDescription'], ENT_QUOTES, 'UTF-8');
    $incidentDate = $_POST["incidentDate"];
    $dateRaised = $_POST["dateRaised"];
    $dateClosed = $_POST["dateClosed"];
    $department = $_POST["department"];
    $status = "Open";
    $nearMiss = (int) $_POST["nearMiss"];
    $firstAidGiven = (int) $_POST["firstAidGiven"];
    $medicalTreatmentCase = (int) $_POST["medicalTreatmentCase"];
    $restrictedWorkCase = (int) $_POST["restrictedWorkCase"];
    $lostTimeCase = (int) $_POST["lostTimeCase"];
    $fiveDaysOff = (int) $_POST["fiveDaysOff"];
    $insuranceNotified = (int) $_POST["insuranceNotified"];
    $directorNotified = (int) $_POST["directorNotified"];
    $additionalComments = $_POST["additionalComments"];
    
    $recordableIncident = 0; // Default value
    
    // Check if any of the specified variables equals to 1
    if (
        $medicalTreatmentCase === 1 ||
        $restrictedWorkCase === 1 ||
        $lostTimeCase === 1 ||
        $fiveDaysOff === 1 ||
        $insuranceNotified === 1 ||
        $directorNotified === 1
    ) {
        $recordableIncident = 1;
    }

    $add_whs_document_sql = "INSERT INTO whs (whs_document_id, involved_person_name, description, incident_date, department, status, near_miss, first_aid_given, medical_treatment_case, recordable_incident, restricted_work_case, lost_time_case, five_days_off, insurance_notified, director_notified, date_raised, date_closed, additional_comments) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $add_whs_document_result = $conn->prepare($add_whs_document_sql);
    $add_whs_document_result->bind_param(
        "ssssssissiiiiiisss",
        $whsDocumentId,
        $involvedPersonName,
        $whsDescription,
        $incidentDate,
        $department,
        $status,
        $nearMiss,
        $firstAidGiven,
        $medicalTreatmentCase,
        $recordableIncident,
        $restrictedWorkCase,
        $lostTimeCase,
        $fiveDaysOff,
        $insuranceNotified,
        $directorNotified,
        $dateRaised,
        $dateClosed,
        $additionalComments
    );

    if ($add_whs_document_result->execute()) {
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

<form method="POST" id="addWHSDocumentForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%" id="result">
    </p>
    <div class="row">
        <div class="form-group col-md-6">
            <label for="whsDocumentId" class="fw-bold">WHS Document ID</label>
            <input type="text" name="whsDocumentId" class="form-control" id="whsDocumentId"
                value="<?php echo $next_whs_id ?>" required>
            <div class="invalid-feedback">
                Please provide WHS Document ID.
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="involvedPersonName" class="fw-bold">Involved Person Name</label>
            <select name="involvedPersonName" class="form-select" id="involvedPersonName" required
                onchange="updateInvolvedPersonNameEmail()">
                <option disabled selected hidden></option>
                <?php
                foreach ($employees as $row) {
                    echo '<option value="' . htmlspecialchars($row['employee_id']) . '" >' .
                        htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                        htmlspecialchars($row['employee_id']) . ')</option>';
                }
                ?>
            </select>
            <div class="invalid-feedback">
                Please provide the Involved Person Name.
            </div>
        </div>
        <div class="form-group col-md-12 mt-3">
            <label for="whsDescription" class="fw-bold">WHS Description</label>
            <textarea class="form-control" name="whsDescription" id="whsDescription" rows="4" required></textarea>
            <div class="invalid-feedback">
                Please provide the WHS Description.
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <label for="incidentDate" class="fw-bold"> Incident Date</label>
            <input type="date" name="incidentDate" class="form-control" id="incidentDate"
                value="<?php echo date('Y-m-d') ?>">
            <div class="invalid-feedback">
                Please provide the Incident Date
            </div>
        </div>

        <div class="form-group col-md-4 mt-3">
            <label for="dateRaised" class="fw-bold"> Date Raised</label>
            <input type="date" name="dateRaised" class="form-control" id="dateRaised"
                value="<?php echo date('Y-m-d') ?>">
            <div class="invalid-feedback">
                Please provide the Date Raised
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <label for="department" class="fw-bold">Department</label>
            <select class="form-select" aria-label="department" name="department" id="department" required>
                <option disabled selected hidden></option>
                <option value="Electrical">Electrical</option>
                <option value="Office">Office</option>
                <option value="Sheet Metal"> Sheet Metal</option>
                <option value="Site"> Site</option>
                <option value="Store"> Store</option>
            </select>
            <div class="invalid-feedback">
                Please provide the department.
            </div>
        </div>

        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="nearMiss" class="fw-bold">Near Miss</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="nearMiss" id="nearMissYes" value="1"
                        autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="nearMissYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="nearMiss" id="nearMissNo" value="0"
                        autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="nearMissNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="firstAidGiven" class="fw-bold">First Aid Given</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="firstAidGiven" id="firstAidGivenYes"
                        value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="firstAidGivenYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="firstAidGiven" id="firstAidGivenNo" value="0"
                        autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="firstAidGivenNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="medicalTreatmentCase" class="fw-bold">Medical Treatment Case</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="medicalTreatmentCase"
                        id="medicalTreatmentCaseYes" value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="medicalTreatmentCaseYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="medicalTreatmentCase"
                        id="medicalTreatmentCaseNo" value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="medicalTreatmentCaseNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
        <!-- <div class="form-group col-md-4 mt-4">
            <div class="d-flex flex-column">
                <label for="recordableIncident" class="fw-bold" style="font-size: 11px">Automated Notifiable/Recordable
                    Incident</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="recordableIncident"
                        id="recordableIncidentYes" value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="recordableIncidentYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="recordableIncident" id="recordableIncidentNo"
                        value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="recordableIncidentNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div> -->
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="restrictedWorkCase" class="fw-bold">Restricted Work Case</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="restrictedWorkCase"
                        id="restrictedWorkCaseYes" value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="restrictedWorkCaseYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="restrictedWorkCase" id="restrictedWorkCaseNo"
                        value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="restrictedWorkCaseNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="lostTimeCase" class="fw-bold">Lost Time Case</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="lostTimeCase" id="lostTimeCaseYes" value="1"
                        autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="lostTimeCaseYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="lostTimeCase" id="lostTimeCaseNo" value="0"
                        autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="lostTimeCaseNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="fiveDaysOff" class="fw-bold">More than 5 days off work</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="fiveDaysOff" id="fiveDaysOffYes" value="1"
                        autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="fiveDaysOffYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="fiveDaysOff" id="fiveDaysOffNo" value="0"
                        autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="fiveDaysOffNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="insuranceNotified" class="fw-bold">Insurance Notified</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="insuranceNotified" id="insuranceNotifiedYes"
                        value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="insuranceNotifiedYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="insuranceNotified" id="insuranceNotifiedNo"
                        value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="insuranceNotifiedNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="directorNotified" class="fw-bold">Director Notified</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="directorNotified" id="directorNotifiedYes"
                        value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="directorNotifiedYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="directorNotified" id="directorNotifiedNo"
                        value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="directorNotifiedNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-3 mb-3">
            <button class="btn btn-dark" name="addWHSDocument" type="submit" id="addWHSBtn">Add Document</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addWHSDocumentForm = document.getElementById("addWHSDocumentForm");
        const whsDocumentId = document.getElementById("whsDocumentId");
        const errorMessage = document.getElementById("result");

        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-whs-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ whsDocumentId: whsDocumentId.value })
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

        addWHSDocumentForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            // Check validity of the form
            if (addWHSDocumentForm.checkValidity() === false) {
                addWHSDocumentForm.classList.add('was-validated');
            } else {
                // Perform your duplicate document validation if the form is valid
                validateForm().then(isValid => {
                    if (isValid) {
                        // Show loading spinner
                        // loadingSpinner.classList.remove('d-none');
                        // addWhsBtn.disabled = true;

                        // Perform AJAX submission instead of standard form submission
                        fetch(addWHSDocumentForm.action, {
                            method: 'POST',
                            body: new FormData(addWHSDocumentForm)
                        })
                            .then(response => {
                                if (response.ok) {
                                    return response.text(); // or response.json() depending on your server response
                                } else {
                                    throw new Error('Network response was not ok');
                                }
                            })
                            .then(data => {
                                // Reload with query parameters preserved
                                const currentUrl = new URL(window.location.href);
                                const queryParams = currentUrl.search;
                                window.location.href = currentUrl.pathname + queryParams;
                            })
                            .catch(error => {
                                // loadingSpinner.classList.add('d-none'); // Hide loading spinner on error
                                errorMessage.classList.remove('d-none');
                                errorMessage.innerHTML = "An error occurred: " + error.message; // Show error message
                            });
                    } else {
                        addWHSDocumentForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = "Duplicate document found.";
                    }
                });
            }
        })
    })
</script>