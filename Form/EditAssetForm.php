<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get all departments for select options
$department_sql = "SELECT department_id, department_name FROM department";
$department_result = $conn->query($department_sql);

// Fetch all results into an array
$departments = [];
while ($row = $department_result->fetch_assoc()) {
    $departments[] = $row;
}

// Get all locations for select options
$location_sql = "SELECT location_id, location_name FROM `location`";
$location_result = $conn->query($location_sql);

$locations = [];
while ($row = $location_result->fetch_assoc()) {
    $locations[] = $row;
}

// ========================= E D I T  A S S E T  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['assetNoToEdit'])) {

    // Validate and process locations
    if (empty($_POST['locationToEdit'])) {
        $errors['locationToEdit'] = "Location is required";
    } else {
        $location = $_POST['locationToEdit'];

        if (empty($_POST['otherLocationEdit'])) {
            $errors['location'] = "Other Location is required";
        } else {
            $otherLocation = $_POST['otherLocationEdit'];

            // Check if otherLocation already exists in the location table
            $check_location_sql = "SELECT location_id FROM `location` WHERE location_name = ?";
            $check_location_stmt = $conn->prepare($check_location_sql);
            $check_location_stmt->bind_param("s", $otherLocation);
            $check_location_stmt->execute();
            $check_location_stmt->bind_result($location_id);
            $check_location_stmt->fetch();
            $check_location_stmt->close();

            if ($location_id) {
                // `otherLocation` already exists, use the existing `location_id`
                $location = $location_id;
            } else {
                // `otherLocation` does not exist, insert it into the location table
                $insert_location_sql = "INSERT INTO `location` (location_name) VALUES (?)";
                $insert_location_stmt = $conn->prepare($insert_location_sql);
                $insert_location_stmt->bind_param("s", $otherLocation);

                if ($insert_location_stmt->execute()) {
                    // Get the auto-incremented ID of the newly inserted location
                    $location_id = $conn->insert_id;
                    $location = $location_id;
                } else {
                    echo "Error: " . $insert_location_stmt->error;
                    $insert_location_stmt->close();
                }
                $insert_location_stmt->close();
            }
        }
    }
    $assetId = $_POST["assetIdToEdit"];
    $assetNo = "FE" . $_POST["assetNoToEdit"];
    $department = $_POST["departmentToEdit"];
    $assetName = $_POST["assetNameToEdit"];
    $status = $_POST["statusToEdit"];
    $disposalDate = !empty($_POST["disposalDateToEdit"]) ? $_POST["disposalDateToEdit"] : null;
    $serialNumber = !empty($_POST["serialNumberToEdit"]) ? $_POST["serialNumberToEdit"] : null;
    $cost = !empty($_POST["costToEdit"]) ? $_POST["costToEdit"] : null;
    $purchaseDate = !empty($_POST["purchaseDateToEdit"]) ? $_POST["purchaseDateToEdit"] : null;
    $accountsAsset = $_POST["accountsAssetToEdit"];
    $whsAsset = $_POST["whsAssetToEdit"];
    $ictAsset = $_POST["ictAssetToEdit"];
    $notes = !empty($_POST["notesToEdit"]) ? $_POST["notesToEdit"] : null;
    $allocatedTo = !empty($_POST["allocatedToToEdit"]) ? $_POST["allocatedToToEdit"] : null;
    $depreciationTimeframe = !empty($_POST["depreciationTimeframeToEdit"]) ? $_POST["depreciationTimeframeToEdit"] : null;
    $depreciationPercentage = !empty($_POST["depreciationPercentageToEdit"]) ? $_POST["depreciationPercentageToEdit"] : null;

    $edit_asset_sql = "UPDATE assets SET 
        asset_no = ?, 
        department_id = ?, 
        asset_name = ?, 
        `status` = ?, 
        disposal_date = ?,
        serial_number = ?, 
        location_id = ?,
        purchase_date = ?, 
        cost = ?,
        notes = ?,
        allocated_to = ?,
        depreciation_timeframe = ?,
        depreciation_percentage = ?,
        accounts_asset = ?, 
        whs_asset = ?,
        ict_asset = ?
    WHERE asset_id = ?";

    $edit_asset_result = $conn->prepare($edit_asset_sql);
    $edit_asset_result->bind_param("sissssisissiiiiii", $assetNo, $department, $assetName, $status, $disposalDate, $serialNumber, $location, $purchaseDate, $cost, $notes, $allocatedTo, $depreciationTimeframe, $depreciationPercentage, $accountsAsset, $whsAsset, $ictAsset, $assetId);

    // Execute the prepared statement
    if ($edit_asset_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>

<head>
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
</head>

<form method="POST" id="editAssetForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="resultError"></p>
    <input type="hidden" name="assetIdToEdit" id="assetIdToEdit">
    <div class="row">
        <div class="form-group col-md-6">
            <label for="assetNoToEdit" class="fw-bold">FE Number</label>
            <div class="input-group">
                <span class="input-group-text rounded-start">FE</span>
                <input type="text" min="0" step="any" class="form-control rounded-end" id="assetNoToEdit"
                    name="assetNoToEdit" aria-describedby="assetNoToEdit" required>
                <div class="invalid-feedback">
                    Please provide the FE Number.
                </div>
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="departmentToEdit" class="fw-bold">Department</label>
            <select name="departmentToEdit" id="departmentToEdit" class="form-select" required>
                <option disabled selected hidden></option>
                <?php
                foreach ($departments as $row) {
                    echo '<option value="' . $row['department_id'] . '">' . htmlspecialchars($row['department_name']) . '</option>';
                }
                ?>
            </select>
            <div class="invalid-feedback">
                Please provide the department.
            </div>
        </div>
        <div class="form-group col-md-12 mt-3">
            <label for="assetNameToEdit" class="fw-bold">Asset Name</label>
            <textarea class="form-control" id="assetNameToEdit" name="assetNameToEdit" required></textarea>
            <div class="invalid-feedback">
                Please provide the asset name.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="statusToEdit" class="fw-bold">Status</label>
            <select name="statusToEdit" class="form-select" id="statusToEdit" required>
                <option disabled selected hidden></option>
                <option value="Current">Current</option>
                <option value="Obsolete">Obsolete</option>
                <option value="Disposed">Disposed</option>
                <option value="Out for Service / Calibration">Out for Service / Calibration</option>
            </select>
            <div class="invalid-feedback">
                Please provide the status.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3 d-none" id="disposalDateToEditInput">
            <label for="disposalDateToEdit" class="fw-bold">Disposal Date</label>
            <input type="date" name="disposalDateToEdit" class="form-control" id="disposalDateToEdit">
            <div class="invalid-feedback">
                Please provide the disposal date.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="serialNumberToEdit" class="fw-bold">Serial Number</label>
            <input type="text" name="serialNumberToEdit" id="serialNumberToEdit" class="form-control">
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="purchaseDateToEdit" class="fw-bold">Purchase Date</label>
            <input type="date" name="purchaseDateToEdit" id="purchaseDateToEdit" class="form-control">
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="costToEdit" class="fw-bold">Cost</label>
            <input type="number" step="any" name="costToEdit" id="costToEdit" class="form-control">
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="locationToEdit" class="fw-bold">Location</label>
            <select name="locationToEdit" class="form-select" id="locationToEdit" required>
                <option disabled selected hidden></option>
                <?php
                foreach ($locations as $row) {
                    echo '<option value="' . $row['location_id'] . '">' . htmlspecialchars($row['location_name']) . '</option>';
                }
                ?>
                <option value="Other">Other</option>
            </select>
            <div class="invalid-feedback">
                Please provide the location.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3 d-none" id="otherLocationInputEdit">
            <label for="otherLocationEdit" class="fw-bold">Other Location</label>
            <input class="form-control" type="text" name="otherLocationEdit" id="otherLocationEdit">
            <div class="invalid-feedback">
                Please provide the other location.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="notesToEdit" class="fw-bold">Notes</label>
            <textarea name="notesToEdit" class="form-control" rows="1" id="notesToEdit"></textarea>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="allocatedToToEdit" class="fw-bold">Allocated to</label>
            <select name="allocatedToToEdit" class="form-select" id="allocatedToToEdit">
                <option disabled selected hidden></option>
                <option value="">Not Assigned</option>
                <?php
                foreach ($employees as $row) {
                    echo '<option value="' . htmlspecialchars($row['employee_id']) . '" > ' . ' [' .
                        htmlspecialchars($row['employee_id']) . '] ' .
                        htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']);
                        if (!empty($row['nickname'])) {
                            echo ' (' . $row['nickname'] . ')';
                        }
                        '</option>';
                }
                ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="accountsAssetToEdit" class="fw-bold">Account Asset</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="accountsAssetToEdit"
                        id="accountsAssetToEditYes" value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="accountsAssetToEditYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="accountsAssetToEdit"
                        id="accountsAssetToEditNo" value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="accountsAssetToEditNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>

        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="whsAssetToEdit" class="fw-bold">WHS Asset</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="whsAssetToEdit" id="whsAssetToEditYes"
                        value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="whsAssetToEditYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="whsAssetToEdit" id="whsAssetToEditNo"
                        value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="whsAssetToEditNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>

        <div class="form-group col-md-4 mt-3">
            <div class="d-flex flex-column">
                <label for="ictAssetToEdit" class="fw-bold">ICT Asset</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="ictAssetToEdit" id="ictAssetToEditYes"
                        value="1" autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="ictAssetToEditYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="ictAssetToEdit" id="ictAssetToEditNo"
                        value="0" autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="ictAssetToEditNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>

        <?php if ($role === "full control") { ?>
            <div class="container">
                <hr class="mt-4">
            </div>

            <div class="form-group col-md-6 mt-3">
                <div class="d-flex flex-column">
                    <label for="depreciationTimeframeToEdit" class="fw-bold">Depreciation Timeframe</label>
                    <div class="input-group">
                        <input type="number" min="0" class="form-control" id="depreciationTimeframeToEdit"
                            name="depreciationTimeframeToEdit">
                        <span class="input-group-text">Months</span>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <div class="d-flex flex-column">
                    <label for="depreciationPercentageToEdit" class="fw-bold">Depreciation Percentage</label>
                    <div class="input-group">
                        <input type="number" min="0" class="form-control" id="depreciationPercentageToEdit"
                            name="depreciationPercentageToEdit">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="d-flex justify-content-center mt-5 mb-3">
        <button class="btn btn-dark" name="editAsset" type="submit" id="editAssetBtn">Edit Asset</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locationSelectEdit = document.getElementById("locationToEdit");
        const otherLocationInputEdit = document.querySelector("#otherLocationInputEdit");
        const otherLocationEdit = document.querySelector("#otherLocationEdit");

        function updateEditLocationFields() {
            const selectedOptionText = locationSelectEdit.options[locationSelectEdit.selectedIndex].text;

            if (selectedOptionText === "Other") {
                otherLocationEdit.required = true;
                otherLocationEdit.value = "";
                otherLocationInputEdit.classList.remove("d-none");
                otherLocationInputEdit.classList.add("d-block");
            } else {
                otherLocationEdit.required = false;
                otherLocationEdit.value = "";
                otherLocationInputEdit.classList.add("d-none");
                otherLocationInputEdit.classList.remove("d-block");
            }

            // Attach change event listener
            locationSelectEdit.addEventListener('change', updateEditLocationFields);
        }
        //Initialize fields based on the currently selected option
        updateEditLocationFields();
    })
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editAssetForm = document.getElementById("editAssetForm");
        const errorMessage = document.getElementById("resultError");
        const assetId = document.getElementById("assetIdToEdit");
        const assetNo = document.getElementById("assetNoToEdit");

        // Function to check for duplicaate documents
        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-asset-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ assetId: assetId.value, assetNo: "FE" + assetNo.value })
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
        editAssetForm.addEventListener('submit', function (event) {
            // Prevent default form submission
            event.preventDefault();
            event.stopPropagation();

            // Clear previous error message
            errorMessage.classList.add('d-none');
            errorMessage.innerHTML = '';

            // Check validity of the form
            if (editAssetForm.checkValidity() === false) {
                editAssetForm.classList.add('was-validated');
            } else {
                // Perform duplicate document validation if the form is valid
                validateForm().then(isValid => {
                    if (isValid) {
                        // Show loading spinner
                        // loadingSpinnerEdit.classList.remove('d-none');

                        // Perform AJAX submission instead of standard form submission
                        fetch(editAssetForm.action, {
                            method: 'POST',
                            body: new FormData(editAssetForm)
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
                        editAssetForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = 'Duplicate document found.';
                    }
                });
            }
        });
    })
</script>