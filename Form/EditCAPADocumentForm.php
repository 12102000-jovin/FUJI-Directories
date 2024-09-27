<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>


<form method="POST" id="editCAPADocumentForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="result">
        Duplicate document found.</p>

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
                <input type="text" name="dateRaisedToEdit" class="form-control" id="dateRaisedToEdit" required>
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
                <input type="text" name="capaOwnerToEdit" class="form-control" id="capaOwnerToEdit" required>
                <div class="invalid-feedback">
                    Please provide CAPA Owner
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="assignedToToEdit" class="fw-bold">Assigned To</label>
                <input type="text" name="assignedToToEdit" class="form-control" id="assignedToToEdit">
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
        </div>
    </div>
    <div id="closeForm" class="d-none">
        <button class="btn btn-sm signature-btn text-white" id="backFormBtn"><i
                class="fa-solid fa-arrow-left fa-xs me-1"></i>Back</button>
        <div class="row mt-3">
            <div class="form-group col-md-12">
                <label for="dateClosed" class="fw-bold">Date Closed</label>
                <input type="date" name="dateClosed" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group col-md-12 mt-3">
                <label for="keyTakeaways" class="fw-bold">Key Takeaways</label>
                <textarea class="form-control" name="keyTakeaways" id="keyTakeaways" rows="4" required></textarea>
            </div>
            <div class="form-group col-md-12 mt-3">
                <label for="additionalComments" class="fw-bold">Additional Comments</label>
                <textarea class="form-control" name="additionalComments" id="additionalComments" rows="4"
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