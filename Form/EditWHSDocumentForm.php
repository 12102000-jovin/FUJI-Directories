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
        id="result">
    <div class="row">
        <input type="text" id="whsIdToEdit">
        <div class="form-group col-md-6">
            <label for="whsDocumentIdToEdit" class="fw-bold"> WHS Document ID</label>
            <input type="text" name="whsDocumentIdToEdit" class="form-control" id="whsDocumentIdToEdit">
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
                    echo '<option value="' . htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . '" > ' .
                        htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . ' (' .
                        htmlspecialchars($row['employee_id']) . ')</option>';
                }
                ?>
            </select>
        </div>
            <div class="form-group col-md-12 mt-3">
            <label for="whsDescriptionToEdit" class="fw-bold">WHS Description</label>
            <textarea class="form-control" name="whsDescriptionToEdit" id="whsDescriptionToEdit" rows="4" required></textarea>
            <div class="invalid-feedback">
                Please provide the WHS Description.
            </div>
        </div>
    </div>
    </p>
</form>