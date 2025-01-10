<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get all the location
$location_sql = "SELECT * FROM location";
$location_result = $conn->query($location_sql);

// ========================= SQL to get latest cable No =========================
$cable_no_sql = "SELECT MAX(cable_no) AS latest_no FROM cables";
$cable_no_result = $conn->query($cable_no_sql);

if ($cable_no_result->num_rows > 0) {
    $row = $cable_no_result->fetch_assoc();
    $latest_no = $row['latest_no'];

    // Check if there's latest No
    if ($latest_no) {
        // Extract the numeric part
        $number = (int) substr($latest_no, 2); // Assuming 'CN' is 2 characters long
        $next_number = $number + 1; // Increment the number
    } else {
        // If no ID exists, start with 1
        $next_number = 1;
    }

    // Format the next No
    $next_cable_no = "CN" . str_pad($next_number, 5, '0', STR_PAD_LEFT);
} else {
    echo "No records found";
}

// ========================= A D D  C A B L E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cableNo"])) {
    $cableNo = $_POST["cableNo"];

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


    $testFrequency = $_POST["testFrequency"];
    $description = !empty($_POST["description"]) ? $_POST["description"] : null;
    $cablePurchaseDate = !empty($_POST["cablePurchaseDate"]) ? $_POST["cablePurchaseDate"] : null;
    $assetNo = !empty($_POST["assetNo"]) ? $_POST["assetNo"] : null;

    $add_cable_sql = "INSERT INTO cables (cable_no, `location_id`, test_frequency, `description`, purchase_date, asset_no) VALUES (?, ?, ?, ?, ?, ?)";
    $add_cable_result = $conn->prepare($add_cable_sql);
    $add_cable_result->bind_param("siisss", $cableNo, $location, $testFrequency, $description, $cablePurchaseDate, $assetNo);

    // Execute the prepared statement
    if ($add_cable_result->execute()) {
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
<form method="POST" id="addCableForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%" id="result">
    </p>
    <div class="row">
        <div class="form-group col-md-6">
            <label for="cableNo" class="fw-bold">Cable No.</label>
            <input type="text" name="cableNo" class="form-control" id="cableNoToAdd"
                value="<?php echo $next_cable_no ?>" required>
            <div class="invalid-feedback">
                Please provide the Cable No.
            </div>
        </div>
        <div class="form-group col-md-6 mt-md-0 mt-3">
            <label for="location" class="fw-bold">Location</label>
            <select class="form-select" name="location" aria-label="location" id="location" required>
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

        <div class="form-group col-md-6 mt-3 d-none" id="otherLocationInput">
            <label for="otherLocation" class="fw-bold">Other Location</label>
            <input class="form-control" type="text" name="otherLocation" id="otherLocation">
            <div class="invalid-feedback">
                Please provide the other location.
            </div>
        </div>

        <div class="form-group col-md-6 mt-3">
            <label for="testFrequency" class="fw-bold">Test Frequency</label>
            <select class="form-select" name="testFrequency" id="testFrequency" required>
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
            <textarea name="description" class="form-control" id="description" rows="1"></textarea>
        </div>
        <div class="form-group col-md-6 mt-3">
            <label for="cablePurchaseDate" class="fw-bold">Cable Purchase Date</label>
            <input type="date" name="cablePurchaseDate" class="form-control" id="cablePurchaseDate">
        </div>
        <div class="row">
            <div class="form-group col-md-6 mt-3">
                <label for="assetSelectionRadioButton" class="fw-bold">Is the cable part of an asset?</label><br>
                <input type="radio" id="showAssetYes" name="assetSelectionRadioButton" value="yes"
                    class="form-check-input" required>
                <label for="showAssetYes" class="form-check-label">Yes</label>
                <input type="radio" id="showAssetNo" name="assetSelectionRadioButton" value="no"
                    class="form-check-input ms-3" required>
                <label for="showAssetNo" class="form-check-label">No</label>
            </div>

            <div class="form-group col-md-6 mt-3 d-none" id="assetIdInputGroup">
                <label for="assetNo" class="fw-bold">FE Number</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start">FE</span>
                    <input type="number" min="0" step="any" class="form-control rounded-end" id="assetNo" name="assetNo"
                        aria-describedby="assetNo">
                    <div class="invalid-feedback">
                        Please provide the FE Number.
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-4 mb-4">
            <button class="btn btn-dark" name="addCable" type="submit" id="addCableBtn">Add Cable</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addCableForm = document.getElementById("addCableForm");
        const cableNo = document.getElementById("cableNoToAdd");

        console.log(cableNo.value);
        const errorMessage = document.getElementById("result");

        function checkDuplicateDocument() {
            return fetch('../AJAXphp/check-cable-duplicate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    cableNo: cableNo.value
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
            });
        }

        addCableForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            // Check validity of the form
            if (addCableForm.checkValidity() === false) {
                addCableForm.classList.add('was-validated');
            } else {
                // Perform duplicate document check
                validateForm().then(isValid => {
                    if (isValid) {
                        //Perform AJAX submission instead of standard form submission
                        fetch(addCableForm.action, {
                            method: 'POST',
                            body: new FormData(addCableForm)
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
                        addCableForm.classList.add('was-validated');
                        errorMessage.classList.remove('d-none');
                        errorMessage.innerHTML = "Duplicate Cable Number found."
                    }
                }).catch(error => {
                    errorMessage.classList.remove('d-none');
                    errorMessage.innerHTML = "An error occurred: " + error.message;
                });
            }
        })
    });
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

<script>
    // Function to toggle based on radio button selection
    document.querySelectorAll('input[name="assetSelectionRadioButton"]').forEach((radio) => {
        radio.addEventListener("change", function () {
            var assetIdInputGroup = document.getElementById("assetIdInputGroup");
            var assetNumberInput = document.getElementById("assetNo");

            if (document.getElementById("showAssetYes").checked) {
                assetIdInputGroup.classList.remove("d-none");  // Show the input group
                assetNumberInput.setAttribute("required", "true");  // Make the input required
            } else if (document.getElementById("showAssetNo").checked) {
                assetIdInputGroup.classList.add("d-none");  // Hide the input group
                assetNumberInput.removeAttribute("required");  // Remove the required attribute
            }
        });
    });

</script>