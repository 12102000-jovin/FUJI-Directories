<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// ========================= E D I T  D O C U M E N T  =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["qaIdToEdit"])) {
    $qaIdToEdit = $_POST["qaIdToEdit"];
    $qaDocumentToEdit = $_POST["qaDocumentToEdit"];
    $documentNameToEdit = $_POST["documentNameToEdit"];
    $documentDescriptionToEdit = $_POST["documentDescriptionToEdit"];
    $revNoToEdit = $_POST["revNoToEdit"];
    $wipDocLinkToEdit = $_POST["qaDocumentToEdit"];
    $departmentToEdit = $_POST["departmentToEdit"];
    $typeToEdit = $_POST["typeToEdit"];
    $ownerToEdit = $_POST["ownerToEdit"];
    $statusToEdit = $_POST["statusToEdit"];
    $approvedByToEdit = $_POST["approvedByToEdit"];
    $lastUpdatedToEdit = $_POST["lastUpdatedToEdit"];
    $ISO9001ToEdit = $_POST["iso9001ToEdit"];

    // Conditional for the revision_status based on the last_updated
    $timezone = new DateTimeZone('Australia/Sydney');
    $todayDateTime = new DateTime('now', $timezone);
    $lastUpdatedDateTime = new DateTime($lastUpdatedToEdit);
    $dateDifference = $lastUpdatedDateTime->diff($todayDateTime);

    if ($dateDifference->days < 250) {
        $revisionStatusToEdit = "Normal";
    } else if ($dateDifference->days < 350) {
        $revisionStatusToEdit = "Revision Required";
    } else {
        $revisionStatusToEdit = "Urgent Revision Required";
    }

    // Check if document with the same name already exists
    $check_document_sql = "SELECT COUNT(*) FROM quality_assurance WHERE qa_document = ? AND qa_id != ?";
    $check_document_stmt = $conn->prepare($check_document_sql);
    $check_document_stmt->bind_param("si", $qaDocumentToEdit, $qaIdToEdit);
    $check_document_stmt->execute();
    $check_document_stmt->bind_result($document_count);
    $check_document_stmt->fetch();
    $check_document_stmt->close();

    if ($document_count > 0) {
        // Document with the same name already exists, show an error message
        echo "<script> alert('A document with this name already exists. Please choose a different name.')</script>";
    } else {
        $edit_document_sql = "UPDATE quality_assurance SET qa_document = ?, document_name = ?, document_description = ?, rev_no = ?,  wip_doc_link = ?, 
    department = ?, type = ?, owner = ?, status = ?, approved_by = ?, last_updated = ?, revision_status = ?, iso_9001 = ? WHERE qa_id = ? ";
        $edit_document_result = $conn->prepare($edit_document_sql);
        $edit_document_result->bind_param(
            "sssssssssssssi",
            $qaDocumentToEdit,
            $documentNameToEdit,
            $documentDescriptionToEdit,
            $revNoToEdit,
            $wipDocLinkToEdit,
            $departmentToEdit,
            $typeToEdit,
            $ownerToEdit,
            $statusToEdit,
            $approvedByToEdit,
            $lastUpdatedToEdit,
            $revisionStatusToEdit,
            $ISO9001ToEdit,
            $qaIdToEdit
        );

        if ($edit_document_result->execute()) {
            // Build the current URL with query parameters
            $current_url = $_SERVER['PHP_SELF'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $current_url .= '?' . $_SERVER['QUERY_STRING'];
            }

            // Redirect to the same URL with parameters
            echo "<script>window.location.replace('" . $current_url . "');</script>";
            exit();
        } else {
            echo "Error updating record: " . $edit_document_result->error;
        }
    }
}
?>

<form method="POST" id="editQADocumentForm" novalidate>
    <div class="row">
        <input type="hidden" name="qaIdToEdit" id="qaIdToEdit">
        <div class="form-group col-md-6">
            <label for="qaDocumentToEdit" class="fw-bold">QA Document</label>
            <input type="text" name="qaDocumentToEdit" class="form-control" id="qaDocumentToEdit" required>
            <div class="invalid-feedback">
                Please provide a QA Document.
            </div>
        </div>
        <div class="form-group col-md-6">
            <label for="documentNameToEdit" class="fw-bold">Document Name</label>
            <input type="text" name="documentNameToEdit" class="form-control" id="documentNameToEdit" required>
            <div class="invalid-feedback">
                Please provide a document name.
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12 mt-3">
            <label for="documentDescriptionToEdit" class="fw-bold">Document Description</label>
            <textarea type="text" name="documentDescriptionToEdit" class="form-control" id="documentDescriptionToEdit"
                required> </textarea>
            <div class="invalid-feedback">
                Please provide a document description.
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6 mt-3">
            <label for="revNoToEdit" class="fw-bold">Rev No.</label>
            <!-- <input type="text" name="revNoToEdit" class="form-control" id="revNoToEdit" required> -->
            <select name="revNoToEdit" class="form-select" id="revNoToEdit" required>
                <option disabled selected hidden></option>
                <?php
                for ($i = 0; $i <= 99; $i++) {
                    // Format the number with leading zeros (e.g., 0 -> R00, 9 -> R09, 10 -> R10)
                    $rev = "R" . str_pad($i, 2, "0", STR_PAD_LEFT);

                    echo "<option value=\"$rev\"" . ($row['rev_no'] === $rev ? ' selected' : '') . ">$rev</option>";
                }
                ?>
            </select>
            <div class="invalid-feedback">
                Please provide a Rev No.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="departmentToEdit" class="fw-bold">Department</label>
            <select class="form-select" aria-label="departmentToEdit" name="departmentToEdit" id="departmentToEdit"
                required>
                <option disabled selected hidden></option>
                <option value="Accounts">Accounts</option>
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
                <option value="Sheet Metal">Sheet Metal</option>
                <option value="Special Projects">Special Projects</option>
                <option value="Work, Health and Safety">Work, Health and Safety</option>
                <option value="N/A">N/A</option>
            </select>
            <div class="invalid-feedback">
                Please provide the department.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="typeToEdit" class="fw-bold">Type</label>
            <select class="form-select" aria-label="type" name="typeToEdit" id="typeToEdit" required>
                <option disabled selected hidden></option>
                <option value="Additional Duties">Additional Duties</option>
                <option value="CAPA">CAPA</option>
                <option value="Employee Record">Employee Record</option>
                <option value="External Documents">External Documents</option>
                <option value="Form">Form</option>
                <option value="Internal Documents">Internal Documents</option>
                <option value="Job Description">Job Description</option>
                <option value="Manuals">Manuals</option>
                <option value="Policy">Policy</option>
                <option value="Process/Procedure">Process/Procedure</option>
                <option value="Quiz">Quiz</option>
                <option value="Risk Assessment">Risk Assessment</option>
                <option value="Work Instruction">Work Instruction</option>
                <option value="N/A">N/A</option>
            </select>
            <div class="invalid-feedback">
                Please provide the type.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="ownerToEdit" class="fw-bold">Owner</label>
            <select class="form-select" aria-label="ownerToEdit" name="ownerToEdit" id="ownerToEdit" required>
                <option disabled selected hidden></option>
                <option value="General Manager">General Manager</option>
                <option value="Engineering Manager">Engineering Manager</option>
                <option value="Electrical Department Manager">Electrical Department Manager</option>
                <option value="Sheet Metal Department Manager">Sheet Metal Department Manager
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
            <label for="statusToEdit" class="fw-bold">Status</label>
            <select class="form-select" aria-label="statusToEdit" name="statusToEdit" id="statusToEdit" required>
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
            <label for="approvedByToEdit" class="fw-bold">Approved By</label>
            <select class="form-select" aria-label="approvedByToEdit" name="approvedByToEdit" id="approvedByToEdit"
                required>
                <option disabled selected hidden></option>
                <option value="General Manager">General Manager</option>
                <option value="Engineering Manager">Engineering Manager</option>
                <option value="Electrical Department Manager">Electrical Department Manager</option>
                <option value="Sheet Metal Department Manager">Sheet Metal Department Manager
                </option>
                <option value="Operations Support Manager">Operations Support Manager</option>
                <option value="N/A">N/A</option>
            </select>
            <div class="invalid-feedback">
                Please provide the approver.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="lastUpdatedToEdit" class="fw-bold">Last Updated</label>
            <input type="date" max="9999-12-31" name="lastUpdatedToEdit" class="form-control" id="lastUpdatedToEdit" required>
            <div class="invalid-feedback">
                Please provide the last updated date.
            </div>
        </div>
        <!-- <div class="form-group col-md-6 mt-3">
            <label for="revisionStatusToEdit" class="fw-bold">Revision Status</label>
            <select class="form-select" aria-label="revisionStatusToEdit" name="revisionStatusToEdit"
                id="revisionStatusToEdit" >
                <option disabled selected hidden></option>
                <option value="Normal">Normal</option>
                <option value="Revision Required">Revision Required</option>
                <option value="Urgent Revision Required">Urgent Revision Required</option>
                <option value="N/A">N/A</option>
            </select>
            <div class="invalid-feedback">
                Please provide a revision status.
            </div>
        </div> -->
        <div class="form-group col-md-6 mt-3">
            <div class="d-flex flex-column">
                <label for="iso9001ToEdit" class="fw-bold">ISO 9001</label>
                <div class="btn-group col-3 col-md-2" role="group">
                    <input type="radio" class="btn-check" name="iso9001ToEdit" id="iso9001YesToEdit" value="1"
                        autocomplete="off" required>
                    <label class="btn btn-custom" for="iso9001YesToEdit"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check" name="iso9001ToEdit" id="iso9001NoToEdit" value="0"
                        autocomplete="off" required>
                    <label class="btn btn-custom" for="iso9001NoToEdit"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
                <div class="invalid-feedback" id="iso9001InvalidFeedback">
                    Please provide ISO 9001 status.
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-5 mb-4">
            <button class="btn btn-dark" name="editDocument" type="submit">Edit Document</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editQADocumentForm = document.getElementById("editQADocumentForm");
        const documentDescriptionToEdit = document.getElementById("documentDescriptionToEdit");
        const iso9001YesToEdit = document.getElementById("iso9001YesToEdit");
        const iso9001NoToEdit = document.getElementById("iso9001NoToEdit");
        const iso9001InvalidFeedback = document.getElementById("iso9001InvalidFeedback");

        documentDescriptionToEdit.value = "";

        function checkISO9001RadioSelection() {
            if (!iso9001YesToEdit.checked && !iso9001NoToEdit.checked) {
                iso9001InvalidFeedback.classList.remove("d-none");
                iso9001InvalidFeedback.classList.add("d-block");
                return false; // Indicates validation failure
            } else {
                iso9001InvalidFeedback.classList.remove("d-block");
                iso9001InvalidFeedback.classList.add("d-none");
                return true; // Indicates validation success
            }
        }

        function validateForm() {
            let isValid = true;

            // Check radio button selections
            if (!checkISO9001RadioSelection()) {
                isValid = false;
            }

            // Check if the form itself is valid (HTML5 validation)
            if (!editQADocumentForm.checkValidity()) {
                isValid = false;
            }
            return isValid;
        }

        editQADocumentForm.addEventListener('submit', function (event) {
            // Check form validity
            if (!validateForm()) {
                event.preventDefault();
                event.stopPropagation();
                // Add was-validated class to trigger Bootstrap validation styles
                editQADocumentForm.classList.add('was-validated');
            } else {
                editQADocumentForm.classList.remove('was-validated');
            }
        }, false);

        iso9001YesToEdit.addEventListener('change', checkISO9001RadioSelection);
        iso9001NoToEdit.addEventListener('change', checkISO9001RadioSelection);
    });
</script>