<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get all the locations
$location_sql = "SELECT * FROM location";
$location_result = $conn->query($location_sql);

// ========================= E D I T  C A B L E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cableIdToEdit'])) {
    $cableId = $_POST["cableIdToEdit"];
    $assetNo = null;  // Default assetNo is NULL

    // Check if 'assetSelectionRadioButtonToEdit' is 'yes' or 'no'
    if (isset($_POST['assetSelectionRadioButtonToEdit']) && $_POST['assetSelectionRadioButtonToEdit'] === 'yes') {
        $assetNo = "FE" . $_POST['assetNoToEdit'];  // Get the asset number from the form
    }

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

    $testFrequency = $_POST["testFrequencyToEdit"];
    $cablePurchaseDate = !empty($_POST["cablePurchaseDateToEdit"]) ? $_POST["cablePurchaseDateToEdit"] : null;
    $description = !empty($_POST["descriptionToEdit"]) ? $_POST["descriptionToEdit"] : null;

    $edit_cable_sql = "UPDATE cables SET location_id = ?, test_frequency = ?, `description` = ?, purchase_date = ?, asset_no = ? WHERE cable_id = ?";
    $edit_cable_result = $conn->prepare($edit_cable_sql);
    $edit_cable_result->bind_param("sssssi", $location, $testFrequency, $description, $cablePurchaseDate, $assetNo, $cableId);

    // Execute the prepared statement
    if ($edit_cable_result->execute()) {
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

<form method="POST" id="editCableForm" novalidate>
    <input type="hidden" name="cableIdToEdit" id="cableIdToEdit">
    <div class="row">
        <div class="form-group col-md-6">
            <label for="cableNo" class="fw-bold">Cable No.</label>
            <input type="text" name="cableNo" class="form-control" id="cableNoToEdit" value="" disabled>
            <div class="invalid-feedback">
                Please provide the Cable No.
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="location" class="fw-bold">Location</label>
            <select class="form-select" name="locationToEdit" id="locationToEdit" aria-label="location" required>
                <option disabled selected hidden> </option>
                <?php
                if ($location_result->num_rows > 0) {
                    while ($row = $location_result->fetch_assoc()) {
                        $locationId = $row['location_id'];
                        $locationName = $row['location_name'];
                        ?>
                        <option value="<?= $locationId ?>"><?= $locationName ?> </option>
                    <?php }
                }
                ?>
                <option value="Other">Other</option>
            </select>
            <div class="invalid-feedback">
                Please provide the location.
            </div>
        </div>

        <div class="form-group col-md-6 mt-3 d-none" id="otherLocationInputEdit">
            <label for="otherLocationInputEdit" class="fw-bold"> Other Location</label>
            <input type="text" class="form-control" name="otherLocationEdit" id="otherLocationEdit">
            <div class="invalid-feedback">
                Please provide the other location.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="testFrequency" class="fw-bold">Test Frequency</label>
            <select class="form-select" name="testFrequencyToEdit" id="testFrequencyToEdit" required>
                <option disabled selected hidden></option>
                <option value="3">3 Months</option>
                <option value="6">6 Months</option>
                <option value="12">12 Months</option>
                <option value="60">5 years</option>
            </select>
            <div class="invalid-feedback">
                Please provide the test frequency.
            </div>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="description" class="fw-bold">Description</label>
            <textarea name="descriptionToEdit" class="form-control" id="descriptionToEdit" rows="1"></textarea>
        </div>

        <div class="form-group col-md-6 mt-3">
            <label for="cablePurchaseDateToEdit" class="fw-bold">Cable Purchase Date</label>
            <input type="date" name="cablePurchaseDateToEdit" class="form-control" id="cablePurchaseDateToEdit">
        </div>

        <div class="row">
            <div class="form-group col-md-6 mt-3">
                <label for="assetSelectionRadioButtonToEdit" class="fw-bold">Is the cable part of an asset?</label><br>
                <input type="radio" id="showAssetYesToEdit" name="assetSelectionRadioButtonToEdit" value="yes"
                    class="form-check-input">
                <label for="showAssetYesToEdit" class="form-check-label">Yes</label>
                <input type="radio" id="showAssetNoToEdit" name="assetSelectionRadioButtonToEdit" value="no"
                    class="form-check-input ms-3">
                <label for="showAssetNoToEdit" class="form-check-label">No</label>
            </div>

            <div class="form-group col-md-6 mt-3 d-none" id="assetIdInputGroupToEdit">
                <label for="assetNoToEdit" class="fw-bold">FE Number</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start">FE</span>
                    <input type="text" min="0" step="any" class="form-control rounded-end" id="assetNoToEdit"
                        name="assetNoToEdit" aria-describedby="assetNoToEdit">
                    <div class="invalid-feedback">
                        Please provide the FE Number.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-4 mb-4">
            <button class="btn btn-dark" name="editCable" type="submit" id="edutCableBtn">Edit Cable</button>
        </div>
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
    $('#editCableModal').on('shown.bs.modal', function () {
        var assetNoToEditValue = document.getElementById("assetNoToEdit").value;
        var showAssetYesToEdit = document.getElementById("showAssetYesToEdit");
        var showAssetNoToEdit = document.getElementById("showAssetNoToEdit");
        var assetIdInputGroupToEdit = document.getElementById("assetIdInputGroupToEdit");
        var assetNoToEdit = document.getElementById("assetNoToEdit");

        // Function to set values based on assetNoToEditValue
        function checkAssetNo() {
            if (assetNoToEditValue) {
                // Preselect 'Yes' and make assetNoToEdit required
                showAssetYesToEdit.checked = true;
                assetIdInputGroupToEdit.classList.remove("d-none");
                assetNoToEdit.setAttribute("required", "true");
            } else {
                // Preselect 'No' and make assetNoToEdit not required
                showAssetNoToEdit.checked = true;
                assetIdInputGroupToEdit.classList.add("d-none");
                assetNoToEdit.removeAttribute("required");
            }
        }

        // Call the function to set values on page load
        checkAssetNo();

        // if the user manually selects a radio button, update the form accordingly
        showAssetYesToEdit.addEventListener("change", function () {
            assetIdInputGroupToEdit.classList.remove("d-none");
            assetNoToEdit.setAttribute("required", "true");
        });

        showAssetNoToEdit.addEventListener("change", function () {
            assetIdInputGroupToEdit.classList.add("d-none");
            assetNoToEdit.removeAttribute("required");
        });
    });
</script>