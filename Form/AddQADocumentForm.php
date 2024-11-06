<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ========================= A D D   D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["addDocument"])) {
    $qaDocument = $_POST["qaDocument"];
    $documentName = $_POST["documentName"];
    $documentDescription = $_POST["documentDescription"];
    $revNo = $_POST["revNo"];
    $wipDocLink = $_POST["qaDocument"];
    $department = $_POST["department"];
    $type = $_POST["type"];
    $owner = $_POST["owner"];
    $status = $_POST["status"];
    $approvedBy = $_POST["approvedBy"];
    $lastUpdated = $_POST["lastUpdated"];
    $ISO9001 = $_POST["iso9001"];

    // Conditional for the revision_status based on the last_updated
    $timezone = new DateTimeZone('Australia/Sydney');
    $todayDateTime = new DateTime('now', $timezone);
    $lastUpdatedDateTime = new DateTime($lastUpdated);
    $dateDifference = $lastUpdatedDateTime->diff($todayDateTime);

    if ($dateDifference->days < 250) {
        $revisionStatus = "Normal";
    } else if ($dateDifference->days < 350) {
        $revisionStatus = "Revision Required";
    } else {
        $revisionStatus = "Urgent Revision Required";
    }

    // Check if document with the same name already exists
    $check_document_sql = "SELECT COUNT(*) FROM quality_assurance WHERE qa_document = ?";
    $check_document_stmt = $conn->prepare($check_document_sql);
    $check_document_stmt->bind_param("s", $qaDocument);
    $check_document_stmt->execute();
    $check_document_stmt->bind_result($document_count);
    $check_document_stmt->fetch();
    $check_document_stmt->close();

    if ($document_count > 0) {
        // Document with the same name already exists, show an error message
        echo "<script> alert('A document with this name already exists. Please choose a different name.')</script>";
    } else {
        // No duplicate found, proceed with the insertion
        $add_document_sql = "INSERT INTO quality_assurance (qa_document, document_name, document_description, rev_no, wip_doc_link, department, type, owner, status, approved_by, last_updated, revision_status, iso_9001) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $add_document_result = $conn->prepare($add_document_sql);
        $add_document_result->bind_param("ssssssssssssi", $qaDocument, $documentName, $documentDescription, $revNo, $wipDocLink, $department, $type, $owner, $status, $approvedBy, $lastUpdated, $revisionStatus, $ISO9001);

        // Execute the prepared statement
        if ($add_document_result->execute()) {
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
}
?>

<form method="POST" class="d-none" id="addQADocumentForm" novalidate>
    <div class="row">
        <div class="form-group col-md-6">
            <div class="d-flex">
                <label for="fullyConstructedDocumentName" class="fw-bold">QA Document</label>
                <a class="ms-2 text-decoration-underline editDocumentNameButton" role="button">Edit</a>
            </div>
            <input type="text" name="qaDocument" class="form-control" id="fullyConstructedDocumentName" readonly>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="documentName" class="fw-bold">Document Name</label>
            <input type="text" name="documentName" class="form-control" id="documentName" required>
            <div class="invalid-feedback">
                Please provide a document name.
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12 mt-3">
            <label for="documentDescription" class="fw-bold">Document Description</label>
            <textarea type="text" name="documentDescription" class="form-control" id="documentDescription"
                required> </textarea>
            <div class="invalid-feedback">
                Please provide a document description.
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6 mt-3">
            <label for="revNo" class="fw-bold">Rev No.</label>
            <!-- <input type="text" name="revNo" class="form-control" id="revNo" required> -->
            <select name="revNo" class="form-select" id="revNo" required>
                <option disabled selected hidden></option>
                <?php
                for ($i = 0; $i <= 99; $i++) {
                    // Format the number with leading zeros (e.g., 0 -> R00, 9 -> R09, 10 -> R10)
                    $rev = "R" . str_pad($i, 2, "0", STR_PAD_LEFT);

                    // Check if the current value is R00 and set it as selected
                    $selected = ($rev === 'R00') ? 'selected' : '';

                    echo "<option value=\"$rev\" $selected>$rev</option>";
                }
                ?>
            </select>
            <div class="invalid-feedback">
                Please provide a Rev No.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <div class="d-flex">
                <label for="department" class="fw-bold">Department</label>
                <a class="ms-2 text-decoration-underline editDocumentNameButton" role="button">Edit</a>
            </div>
            <input type="text" name="department" class="form-control" id="departmentToSubmit" readonly>
        </div>
        <div class="form-group col-md-6 mt-3">
            <div class="d-flex">
                <label for="type" class="fw-bold">Type</label>
                <a class="ms-2 text-decoration-underline editDocumentNameButton" role="button">Edit</a>
            </div>
            <input type="text" name="type" class="form-control" id="typeToSubmit" readonly>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="owner" class="fw-bold">Owner</label>
            <select class="form-select" aria-label="owner" name="owner" id="owner" required>
                <option disabled selected hidden></option>
                <option value="Accounts Manager">Accounts Manager</option>
                <option value="General Manager">General Manager</option>
                <option value="Engineering Manager">Engineering Manager</option>
                <option value="Electrical Manager">Electrical Manager</option>
                <option value="Sheet Metal Manager">Sheet Metal Manager
                </option>
                <option value="Operations Support Manager">Operations Support Manager</option>
                <option value="QA Officer">QA Officer</option>
                <option value="QA Officer">HR Officer</option>
                <option value="WHS Committee">WHS Committee</option>
                <option value="Risk Assessment Committee">Risk Assessment Committee</option>
                <option value="N/A">N/A</option>
            </select>
            <div class="invalid-feedback">
                Please provide the owner.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="status" class="fw-bold">Status</label>
            <select class="form-select" aria-label="status" name="status" required>
                <option disabled selected hidden></option>
                <option value="Approved">Approved</option>
                <option value="Need to review">Need to review</option>
                <option value="Not approved yet">Not approved yet</option>
                <option value="In progress">In progress</option>
                <option value="Pending approval">Pending approval</option>
                <option value="To be created">To be created</option>
                <option value="Revision/Creation requested">Revision/Creation requested</option>
                <option value="N/A">N/A</option>
            </select>
            <div class="invalid-feedback">
                Please provide a status.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="approvedBy" class="fw-bold">Approved By</label>
            <select class="form-select" aria-label="approvedBy" name="approvedBy" required>
                <option disabled selected hidden></option>
                <option value="General Manager">General Manager</option>
                <option value="Engineering Manager">Engineering Manager</option>
                <option value="Electrical Manager">Electrical Manager</option>
                <option value="Sheet Metal Manager">Sheet Metal Manager
                </option>
                <option value="Operations Support Manager">Operations Support Manager</option>
                <option value="QA Officer">QA Officer</option>
                <option value="QA Officer">HR Officer</option>
                <option value="WHS Committee">WHS Committee</option>
                <option value="Risk Assessment Committee">Risk Assessment Committee</option>
                <option value="N/A">N/A</option>
            </select>
            <div class="invalid-feedback">
                Please provide the approver.
            </div>
        </div>
        <?php $today = date('Y-m-d'); ?>
        <div class="form-group col-md-6 mt-3">
            <label for="lastUpdated" class="fw-bold">Last Updated</label>
            <input type="date" max="9999-12-31" name="lastUpdated" class="form-control" id="lastUpdated"
                value="<?php echo $today; ?>" required>
            <div class="invalid-feedback">
                Please provide the last updated date.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <div class="d-flex flex-column">
                <label for="iso9001" class="fw-bold">ISO 9001</label>
                <div class="btn-group col-3 col-md-2" role="group">
                    <input type="radio" class="btn-check" name="iso9001" id="iso9001Yes" value="1" autocomplete="off"
                        required>
                    <label class="btn btn-custom" for="iso9001Yes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>
                    <input type="radio" class="btn-check" name="iso9001" id="iso9001No" value="0" autocomplete="off"
                        checked required>
                    <label class="btn btn-custom" for="iso9001No"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
                <div class="invalid-feedback" id="iso9001InvalidFeedback">
                    Please provide ISO 9001 status.
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-5 mb-4">
            <button class="btn btn-dark" name="addDocument" type="submit">Add Document</button>
        </div>
    </div>
</form>

<div id="QANameConstructionForm">
    <div class="d-flex justify-content-center mt-4 mb-4 fw-bold text-center signature-bg-color text-white py-2 rounded-3 d-none"
        id="constructedQANameContainer">
        <span class="d-none" id="constructedDocumentPrefix"></span>
        <span id="constructedType"></span>
        <span id="constructedDocumentCode"></span>
    </div>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="result"></p>
    <div class="form-group col-md-12">
        <label for="department" class="fw-bold">Department</label>
        <select class="form-select" aria-label="department" name="department" id="selectedDepartment">
            <option disabled selected hidden></option>
            <option value="Accounts">Accounts</option>
            <option value="Company Compliance">Company Compliance</option>
            <option value="Electrical">Electrical</option>
            <option value="Engineering">Engineering</option>
            <option value="Estimating">Estimating</option>
            <option value="Human Resources">Human Resources</option>
            <option value="Management">Management</option>
            <option value="Operations Support">Operations Support</option>
            <option value="Projects">Projects</option>
            <option value="Quality Assurance">Quality Assurance</option>
            <option value="Quality Control">Quality Control</option>
            <option value="Research & Development">Research & Development</option>
            <option value="Sheet Metal"> Sheet Metal</option>
            <option value="Special Projects">Special Projects</option>
            <option value="Work, Health and Safety">Work, Health and Safety</option>
        </select>
    </div>

    <div class="form-group col-md-12 mt-3 d-none" id="selectTypeFormGroup">
        <label for="type" class="fw-bold">Type</label>
        <select class="form-select" aria-label="type" name="type" id="selectedType">
            <option disabled selected hidden></option>
            <option value="Additional Duties">Additional Duties</option>
            <option value="CAPA">CAPA</option>
            <option value="Employee Record">Employee Record</option>
            <option value="External Documents">External Documents</option>
            <option value="Form">Form</option>
            <option value="Internal Documents">Internal Documents</option>
            <option value="Job Description">Job Description</option>
            <option value="Manuals">Manuals</option>
            <option value="Manufacturing Process">Manufacturing Process</option>
            <option value="Policy">Policy</option>
            <option value="Process/Procedure">Process/Procedure</option>
            <option value="Quiz">Quiz</option>
            <option value="Risk Assessment">Risk Assessment</option>
            <option value="Work Instruction">Work Instruction</option>
        </select>
    </div>

    <div class="form-group col-md-12 mt-3 d-none" id="documentCodeFormGroup">
        <label for="documentCode" class="fw-bold">Document Code</label>
        <input type="text" class="form-control" id="documentCode">
    </div>

    <div class="d-flex justify-content-center mt-3 d-none" id="confirmDocumentNameBtn">
        <button class="btn signature-btn"> Confirm Document Name </button>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const department = document.getElementById("selectedDepartment");
        const type = document.getElementById("selectedType");
        const documentCode = document.getElementById("documentCode");
        const selectTypeFormGroup = document.getElementById("selectTypeFormGroup");
        const documentCodeFormGroup = document.getElementById("documentCodeFormGroup");
        const constructedDocumentPrefix = document.getElementById("constructedDocumentPrefix");
        const constructedDocumentCode = document.getElementById("constructedDocumentCode");
        const constructedType = document.getElementById("constructedType");
        const fullyConstructedDocumentName = document.getElementById("fullyConstructedDocumentName");
        const addQADocumentForm = document.getElementById("addQADocumentForm");
        const constructedQANameContainer = document.getElementById("constructedQANameContainer");
        const confirmDocumentNameBtn = document.getElementById("confirmDocumentNameBtn");
        const QANameConstructionForm = document.getElementById("QANameConstructionForm");
        const editDocumentNameButton = document.querySelectorAll(".editDocumentNameButton");
        const departmentToSubmit = document.getElementById("departmentToSubmit");
        const typeToSubmit = document.getElementById("typeToSubmit");
        const iso9001Yes = document.getElementById("iso9001Yes");
        const iso9001No = document.getElementById("iso9001No");
        const iso9001InvalidFeedback = document.getElementById("iso9001InvalidFeedback");
        const ownerSelect = document.getElementById("owner");

        const departmentMap = {
            "Quality Assurance": "00-QA",
            "Management": "01-MN",
            "Estimating": "02-ES",
            "Accounts": "03-AC",
            "Projects": "04-PJ",
            "Engineering": "05-EN",
            "Electrical": "06-EL",
            "Sheet Metal": "07-SM",
            "Operations Support": "08-OS",
            "Human Resources": "09-HR",
            "Research & Development": "10-RD",
            "Work, Health and Safety": "11-WH",
            "Quality Control": "12-QC",
            "Special Projects": "15-SP",
            "Company Compliance": "16-CC",
        };

        const typeMap = {
            "CAPA": "-CP",
            "External Documents": "-ED",
            "Employee Record": "-ER",
            "Form": "-FO",
            "Internal Documents": "-ID",
            "Process/Procedure": "-PR",
            "Quiz": "-QZ",
            "Policy": "-PO",
            "Manuals": "-MA",
            "Manufacturing Process": "-MP",
            "Work Instruction": "-WI",
            "Additional Duties": "-AD",
            "Job Description": "-JD",
            "Risk Assessment": "-RA"
        };

        function updateFullyConstructedDocumentName() {
            const fullDocumentName = constructedDocumentPrefix.textContent + constructedType.textContent + constructedDocumentCode.textContent;
            fullyConstructedDocumentName.value = fullDocumentName;
            constructedQANameContainer.textContent = fullDocumentName; // Update the container as well
            departmentToSubmit.value = department.options[department.selectedIndex].text;
            typeToSubmit.value = type.options[type.selectedIndex].text;
        }

        function updateOwnerField() {
            const selectedDepartment = department.options[department.selectedIndex].text;
            if (selectedDepartment === "Accounts") {
                ownerSelect.value = "Accounts Manager";
            } else if (selectedDepartment === "Operations Support" || selectedDepartment === "Human Resources") {
                ownerSelect.value = "Operations Support Manager";
            } else if (selectedDepartment === "Sheet Metal") {
                ownerSelect.value = "Sheet Metal Manager";
            } else if (selectedDepartment === "Electrical") {
                ownerSelect.value = "Electrical Manager";
            } else if (selectedDepartment === "Work, Health and Safety") { 
                ownerSelect.value = "WHS Committee";
            } else if (selectedDepartment === "Engineering" ) {
                ownerSelect.value = "Engineering Manager";
            } else {
                ownerSelect.value = "General Manager";
            }
        }

        department.addEventListener('change', function () {
            const selectedDepartment = department.options[department.selectedIndex].text;
            constructedDocumentPrefix.textContent = departmentMap[selectedDepartment] || "";
            constructedQANameContainer.classList.remove("d-none");
            selectTypeFormGroup.classList.remove("d-none");
            selectTypeFormGroup.classList.add("d-block");
            updateFullyConstructedDocumentName();
            updateOwnerField() 
        });

        type.addEventListener('change', function () {
            const selectedType = type.options[type.selectedIndex].text;
            constructedType.textContent = typeMap[selectedType] || "";
            documentCodeFormGroup.classList.remove("d-none");
            documentCodeFormGroup.classList.add("d-block");
            updateFullyConstructedDocumentName(); xww
        });

        documentCode.addEventListener('input', function () {
            constructedDocumentCode.textContent = "-" + documentCode.value.trim();
            confirmDocumentNameBtn.classList.remove("d-none");
            updateFullyConstructedDocumentName();
        });

        confirmDocumentNameBtn.addEventListener('click', function () {
            document.getElementById('result').classList.add("d-none");
            const qaDocument = constructedQANameContainer.textContent.replace(/\s+/g, '');

            if (!qaDocument) {
                document.getElementById('result').innerHTML = "Please enter a document name.";
                return;
            }

            fetch('../AJAXphp/check-qa-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ qaDocument: qaDocument })
            })
                .then(response => response.text())
                .then(data => {
                    console.log('Server Response:', data);
                    if (data.includes("Duplicate document found.")) {
                        document.getElementById('result').innerHTML = data;
                        document.getElementById('result').classList.remove("d-none");
                        document.getElementById('result').classList.add("d-block");
                    } else if (data.includes("No duplicate found.")) {
                        addQADocumentForm.classList.remove("d-none");
                        QANameConstructionForm.classList.add("d-none");
                        document.getElementById('result').innerHTML = "";
                    } else {
                        console.error('Unexpected response:', data);
                        document.getElementById('result').innerHTML = "Unexpected response from the server.";
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.getElementById('result').innerHTML = "An error occurred while processing the request.";
                });
        });

        editDocumentNameButton.forEach(button => {
            button.addEventListener('click', function () {
                addQADocumentForm.classList.add("d-none");
                QANameConstructionForm.classList.remove("d-none");
            });
        });

        documentDescription.value = "";

        function checkISO9001RadioSelection() {
            if (!iso9001Yes.checked && !iso9001No.checked) {
                iso9001InvalidFeedback.classList.remove("d-none");
                iso9001InvalidFeedback.classList.add("d-block");
                return false;
            } else {
                iso9001InvalidFeedback.classList.add("d-none");
                return true;
            }
        }

        function validateForm() {
            let isValid = true;

            if (!checkISO9001RadioSelection()) {
                isValid = false;
            }

            if (!addQADocumentForm.checkValidity()) {
                isValid = false;
            }

            return isValid;
        }

        addQADocumentForm.addEventListener('submit', function (event) {
            if (!validateForm()) {
                event.preventDefault();
                event.stopPropagation();
                addQADocumentForm.classList.add('was-validated');
            } else {
                addQADocumentForm.classList.remove('was-validated');
            }

            iso9001Yes.addEventListener('change', checkISO9001RadioSelection);
            iso9001No.addEventListener('change', checkISO9001RadioSelection);
        }, false);
    });
</script>

<script>
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Optional: Prevents default behavior of Enter key (like form submission)

            // Get the div element
            var confirmButtonDiv = document.getElementById('confirmDocumentNameBtn');

            // Check if the div does not contain the 'd-none' class
            if (!confirmButtonDiv.classList.contains('d-none')) {
                // Find the button inside the div and simulate a click
                var button = confirmButtonDiv.querySelector('button');
                if (button) {
                    button.click();
                }
            }
        }
    });
</script>