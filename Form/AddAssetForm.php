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

// ========================= A D D  A S S E T  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assetNo"])) {
    $assetNo = $_POST["assetNo"];

    // Validate and process location
    if (empty($_POST['location'])) {
        $errors['location'] = "Location is required";
    } else {
        $location = $_POST['location'];

        // Check for "Other" location
        if ($location === "Other") {
            if (empty($_POST['otherLocation'])) {
                $errors['location'] = "Other Location is required";
            } else {
                $otherLocation = $_POST['otherLocation'];

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
                    $insert_location_sql = "INSERT INTO `location`(location_name) VALUES (?)";
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
    }

    $assetNo = !empty($_POST["assetNo"]) ? "FE" . $_POST["assetNo"] : null;
    $departmentId = !empty($_POST["department"]) ? $_POST["department"] : null;
    $assetName = !empty($_POST["assetName"]) ? $_POST["assetName"] : null;
    $status = !empty($_POST["status"]) ? $_POST["status"] : null;
    $serialNumber = !empty($_POST["serialNumber"]) ? $_POST["serialNumber"] : null;
    $assetLocation = !empty($_POST["assetLocation"]) ? $_POST["assetLocation"] : null;
    $accountAsset = $_POST["accountAsset"];
    $whsAsset = $_POST["whsAsset"];
    $purchaseDate = !empty($_POST["purchaseDate"]) ? $_POST["purchaseDate"] : null;

    $add_asset_sql = "INSERT INTO assets (asset_no, asset_name, `status`, serial_number, location_id, accounts_asset, whs_asset, purchase_date, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $add_asset_result = $conn->prepare($add_asset_sql);
    $add_asset_result->bind_param("ssssiiisi", $assetNo, $assetName, $status, $serialNumber, $location, $accountAsset, $whsAsset, $purchaseDate, $departmentId);

    // Execute the prepared statement
    if ($add_asset_result->execute()) {
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

<form method="POST" id="addAssetForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%" id="result">
    </p>
    <div class="row">
        <div class="form-group col-md-6" id="assetIdInputGroup">
            <label for="assetNo" class="fw-bold">FE Number</label>
            <div class="input-group">
                <span class="input-group-text rounded-start">FE</span>
                <input type="number" min="0" step="any" class="form-control rounded-end" id="assetNoToAdd"
                    name="assetNo" aria-describedby="assetNo" required>
                <div class="invalid-feedback">
                    Please provide the FE Number.
                </div>
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="department" class="fw-bold">Department</label>
            <select name="department" id="department" class="form-select" required>
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
            <label for="assetName" class="fw-bold">Asset Name</label>
            <textarea class="form-control" name="assetName" required></textarea>
            <div class="invalid-feedback">
                Please provide the asset name.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="status" class="fw-bold">Status</label>
            <select name="status" class="form-select" required>
                <option disabled selected hidden></option>
                <option value="Current">Current</option>
                <option value="Obsolete">Obsolete</option>
                <option value="Disposed">Disposed</option>
            </select>
            <div class="invalid-feedback">
                Please provide the status.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="serialNumber" class="fw-bold">Serial Number</label>
            <input type="text" name="serialNumber" class="form-control">
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="purchaseDate" class="fw-bold">Purchase Date</label>
            <input type="date" name="purchaseDate" class="form-control">
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="location" class="fw-bold">Location</label>
            <select name="location" class="form-select" id="location" required>
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
        <div class="form-group col-md-6 mt-3 d-none" id="otherLocationInput">
            <label for="otherLocation" class="fw-bold">Other Location</label>
            <input class="form-control" type="text" name="otherLocation" id="otherLocation">
            <div class="invalid-feedback">
                Please provide the other location.
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6 mt-3">
            <div class="d-flex flex-column">
                <label for="accountAsset" class="fw-bold">Account Asset</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="accountAsset" id="accountAssetYes" value="1"
                        autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="accountAssetYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="accountAsset" id="accountAssetNo" value="0"
                        autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="accountAssetNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>


        <div class="form-group col-md-6 mt-3">
            <div class="d-flex flex-column">
                <label for="whsAsset" class="fw-bold">WHS Asset</label>
                <div class="btn-group col-3 col-md-4" role="group">
                    <input type="radio" class="btn-check btn-custom" name="whsAsset" id="whsAssetYes" value="1"
                        autocomplete="off" required>
                    <label class="btn btn-sm btn-custom" for="whsAssetYes"
                        style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                    <input type="radio" class="btn-check btn-custom" name="whsAsset" id="whsAssetNo" value="0"
                        autocomplete="off" checked required>
                    <label class="btn btn-sm btn-custom" for="whsAssetNo"
                        style="color:#043f9d; border: 1px solid #043f9d">No</label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-5 mb-3">
        <button class="btn btn-dark" name="addAsset" type="submit" id="addAssetBtn">Add Asset</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addAssetForm = document.getElementById("addAssetForm");
        const assetNo = document.getElementById('assetNoToAdd');

        // Listen for the blur event (when the user leaves the input field)
        assetNo.addEventListener('blur', function () {
            const assetNoValue = assetNo.value; // Get the value of the input when the user leaves it
            console.log('Asset Number: FE', assetNoValue); // You can use this value for validation or AJAX requests
        });
        const errorMessage = document.getElementById("result");

        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-asset-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    assetNo: `FE${assetNo.value}` // String interpolation
                })
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
            })
        }

        addAssetForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            // Check validity of the form
            if (addAssetForm.checkValidity() === false) {
                addAssetForm.classList.add('was-validated');
            } else {
                // Perform duplicate document check
                validateForm().then(isValid => {
                    if (isValid) {
                        // Perform AJAX submission instead of standard form submission
                        fetch(addAssetForm.action, {
                            method: 'POST',
                            body: new FormData(addAssetForm)
                        })
                            .then(response => {
                                if (response.ok) {
                                    return response.text();
                                } else {
                                    throw new Error('Network response was not ok');
                                }
                            })
                            .then(data => {
                                location.reload(); // Reload the page
                            })
                            .catch(error => {
                                errorMessage.classList.remove('d-none');
                                errorMessage.innerHTML = "An error occurred: " + error.message;
                            });
                    } else {
                        addAssetForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = "Duplicate Asset Number found."
                    }
                }).catch(error => {
                    errorMessage.classList.remove('d-none');
                    errorMessage.innerHTML = "An error occurred: " + error.message;
                });
            }
        })
    })

</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locationSelect = document.getElementById("location");
        const otherLocationInput = document.querySelector("#otherLocationInput");
        const otherLocation = document.querySelector("#otherLocation");

        console.log(locationSelect);
        console.log(otherLocationInput);
        console.log(otherLocation);

        function updateLocationFields() {
            const selectedOptionText = locationSelect.options[locationSelect.selectedIndex].text;

            if (selectedOptionText === "Other") {
                otherLocation.required = true;
                otherLocation.value = "";
                otherLocationInput.classList.remove("d-none");
                otherLocationInput.classList.add("d-block");
            } else {
                otherLocation.required = false;
                otherLocation.value = "";
                otherLocationInput.classList.add("d-none");
                otherLocationInput.classList.remove("d-block");
            }

            // Attach change event listener
            locationSelect.addEventListener('change', updateLocationFields);
        }
        //Initialize fields based on the currently selected option
        updateLocationFields();
    })
</script>