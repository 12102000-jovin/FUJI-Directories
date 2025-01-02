<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get all the location
$location_sql = "SELECT * FROM location";
$location_result = $conn->query($location_sql);

// ========================= E D I T  C A B L E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cableIdToEdit'])) {
    $cableId = $_POST["cableIdToEdit"];
    $location = $_POST["locationToEdit"];
    $testFrequency = $_POST["testFrequencyToEdit"];
    $description = !empty($_POST["descriptionToEdit"]) ? $_POST["descriptionToEdit"] : null;


    $edit_cable_sql = "UPDATE cables SET location_id = ?, test_frequency = ?, `description` = ? WHERE cable_id = ?";
    $edit_cable_result = $conn->prepare($edit_cable_sql);
    $edit_cable_result->bind_param("sssi", $location, $testFrequency, $description, $cableId);

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
            </select>
            <div class="invalid-feedback">
                Please provide the location.
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
        <div class="d-flex justify-content-center mt-4 mb-4">
            <button class="btn btn-dark" name="editCable" type="submit" id="edutCableBtn">Edit Cable</button>
        </div>
    </div>
</form>