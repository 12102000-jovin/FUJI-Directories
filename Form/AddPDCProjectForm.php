<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["addPDCProject"])) {
    // Assign all POST variables
    $projectNo = !empty($_POST["projectNo"]) ? $_POST["projectNo"] : NULL;
    $fbn = $_POST["fbn"];
    $siteType = $_POST["siteType"];
    $status = $_POST["status"];
    $customer = $_POST["customer"];
    $entityPoNo = !empty($_POST["entityPoNo"]) ? $_POST["entityPoNo"] : NULL;
    $awsPoNo = !empty($_POST["awsPoNo"]) ? $_POST["awsPoNo"] : NULL;
    $poDate = !empty($_POST["poDate"]) ? date('Y-m-d', strtotime($_POST["poDate"])) : NULL;
    $version = $_POST["version"];
    $drawingStatus = $_POST["drawingStatus"];
    $quantity = !empty($_POST["quantity"]) ? (int) $_POST["quantity"] : NULL;
    $cost = !empty($_POST["cost"]) ? $_POST["cost"] : NULL;
    $serialNumber = !empty($_POST["serialNumber"]) ? htmlspecialchars($_POST["serialNumber"]) : NULL;
    $rosdPO = !empty($_POST["rosdPO"]) ? date('Y-m-d', strtotime($_POST["rosdPO"])) : NULL;
    $rosdForecast = !empty($_POST["rosdForecast"]) ? date('Y-m-d', strtotime($_POST["rosdForecast"])) : NULL;
    $resolved =  !empty($_POST["resolved"]) ? $_POST["resolved"] : NULL;
    $rosdChanged = !empty($_POST["rosdChanged"]) ? date('Y-m-d', strtotime($_POST["rosdChanged"])) : NULL;
    $rosdChangedConfirmation = !empty($_POST["rosdChangedConfirmation"]) ? $_POST["rosdChangedConfirmation"] : NULL;
    $rosdCorrectConfirmation = isset($_POST["rosdCorrectConfirmation"]) ? (int) $_POST["rosdCorrectConfirmation"] : NULL;

    // Calculate conflict
    if ($rosdPO !== null && $rosdForecast !== null && ($rosdPO === $rosdForecast)) {
        $conflict = 0;
    } else if ($rosdPO !== null && $rosdForecast !== null && ($rosdPO !== $rosdForecast)) {
        $conflict = 1;
    } else {
        $conflict = NULL;
    }

    $rosdCorrect = !empty($_POST["rosdCorrect"]) ? date('Y-m-d', strtotime($_POST["rosdCorrect"])) : NULL;
    $freightType = $_POST["freightType"];
    $estimatedDepartureDate = !empty($_POST["estimatedDepartureDate"]) ? date('Y-m-d', strtotime($_POST["estimatedDepartureDate"])) : NULL;
    $actualDepartureDate = !empty($_POST["actualDepartureDate"]) ? date('Y-m-d', strtotime($_POST["actualDepartureDate"])) : NULL;
    $actualDeliveredDate = !empty($_POST["actualDeliveredDate"]) ? date('Y-m-d', strtotime($_POST["actualDeliveredDate"])) : NULL;

    $add_pdc_project_sql = "INSERT INTO pdc_projects (
        project_no, 
        fbn, 
        site_type, 
        `status`, 
        customer,
        entity_po_no,
        aws_po_no,
        purchase_order_date,
        `version`,
        drawing_status,
        qty,
        cost,
        serial_numbers,
        rosd_po,
        rosd_forecast, 
        resolved,
        conflict,
        freight_type,
        rosd_change_approval,
        rosd_changed,
        estimated_departure_date,
        actual_departure_date,
        actual_delivered_date,
        approved
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($add_pdc_project_result = $conn->prepare($add_pdc_project_sql)) {
        $add_pdc_project_result->bind_param(
            "ssssssssssssssssisissssi",
            $projectNo,
            $fbn,
            $siteType,
            $status,
            $customer,
            $entityPoNo,
            $awsPoNo,
            $poDate,
            $version,
            $drawingStatus,
            $quantity,
            $cost,
            $serialNumber,
            $rosdPO,
            $rosdForecast,
            $resolved,
            $conflict,
            $freightType,
            $rosdChangedConfirmation,
            $rosdChanged,
            $estimatedDepartureDate,
            $actualDepartureDate,
            $actualDeliveredDate,
            $rosdCorrectConfirmation
        );

        if ($add_pdc_project_result->execute()) {
            $current_url = $_SERVER['PHP_SELF'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $current_url .= '?' . $_SERVER['QUERY_STRING'];
            }
            echo "<script>alert('Document added successfully');</script>";
            echo "<script>window.location.replace('" . $current_url . "')</script>";
        } else {
            echo "Error executing statement: " . $add_pdc_project_result->error;
        }
    } else {
        echo "Prepare failed: " . $conn->error;
    }
}

?>

<form method="POST" id="addPDCProjectForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%" id="result">
    </p>
    <div class="row align-items-stretch">
        <div class="col-md-6 d-flex flex-column">
            <h5 class="fw-bold signature-color">General Information</h5>
            <div class="p-3 border rounded-2 h-100 background-color">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="projectNo" class="fw-bold">Project No.</label>
                        <input type="text" name="projectNo" class="form-control" id="projectNo">
                    </div>
                    <div class="form-group col-md-6 mt-md-0 mt-3">
                        <label for="fbn" class="fw-bold">FBN</label>
                        <input type="text" name="fbn" class="form-control" id="fbn" required>
                        <div class="invalid-feedback fw-bold">
                            Please provide the FBN.
                        </div>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="siteType" class="fw-bold">Site Type</label>
                        <input type="text" name="siteType" class="form-control" id="siteType" required>
                        <div class="invalid-feedback fw-bold">
                            Please provide the site type.
                        </div>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="status" class="fw-bold">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="AWS Removed FF">AWS Removed FF</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Sheet Metal">Sheet Metal</option>
                            <option value="Sheet Metal Assembly">Sheet Metal Assembly</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Testing">Testing</option>
                            <option value="Crated">Crated</option>
                            <option value="In Transit">In Transit</option>
                            <option value="Delivered/Invoiced">Delivered/Invoiced</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="customer" class="fw-bold">Customer</label>
                        <select name="customer" id="customer" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="Amazon-AUS">Amazon-AUS</option>
                            <option value="FI-India">FI-India</option>
                            <option value="FI-Japan">FI-Japan</option>
                            <option value="FS-Indonesia">FS-Indonesia</option>
                            <option value="FS-Singapore">FS-Singapore</option>
                            <option value="Amazon-THA">Amazon-THA</option>
                            <option value="Amazon-NZ">Amazon-NZ</option>
                            <option value="Built">Built</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="entityPoNo" class="fw-bold">Entity's PO No.</label>
                        <input type="text" name="entityPoNo" id="entityPoNo" class="form-control">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="awsPoNo" class="fw-bold">AWS's PO No.</label>
                        <input type="text" name="awsPoNo" id="awsPoNo" class="form-control">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="poDate" class="fw-bold">PO Date</label>
                        <input type="date" name="poDate" id="poDate" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 d-flex flex-column">
            <h5 class="fw-bold signature-color mt-md-0 mt-3">PDC Type/Info</h5>
            <div class="p-3 border rounded-2 h-100 background-color">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="version" class="fw-bold">Version</label>
                        <select name="version" id="version" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="V1.0 630A TF Single Door (IEC)">V1.0 630A TF Single Door (IEC)</option>
                            <option value="V1.0 630A TF Double Door (IEC)">V1.0 630A TF Double Door (IEC)</option>
                            <option value="V1.0 630A TF Double Door (AUS)">V1.0 630A TF Double Door (AUS)</option>
                            <option value="V2.0 630A TF Double Door (IEC)">V2.0 630A TF Double Door (IEC)</option>
                            <option value="V2.0 250A TF Double Door (IEC)">V2.0 250A TF Double Door (IEC)</option>
                            <option value="V2.0 250A BF Double Door (IEC)">V2.0 250A BF Double Door (IEC)</option>
                            <option value="V3.0. 1000A TF (IEC)">V3.0. 1000A TF (IEC)</option>
                        </select>
                        <div class="invalid-feedback fw-bold">
                            Please provide the version.
                        </div>
                    </div>
                    <div class="form-group col-md-6 mt-md-0 mt-3">
                        <label for="drawingStatus" class="fw-bold">Drawing Status</label>
                        <select name="drawingStatus" id="drawingStatus" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="Drawing Created">Drawing Created</option>
                            <option value="Issued to Sheet Metal">Issued to Sheet Metal</option>
                            <option value="Submitted and Approved">Submitted and Approved</option>
                            <option value="As Built">As Built</option>
                            <option value="Sent to Customer & Complete">Sent to Customer & Complete</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="quantity" class="fw-bold">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" required>
                        <div class="invalid-feedback fw-bold">
                            Please provide the quantity.
                        </div>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="cost" class="fw-bold">Cost</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-start">$</span>
                            <input type="number" min="0" step="any" class="form-control rounded-end" id="cost"
                                name="cost">
                        </div>
                    </div>
                    <div class="form-group col-md-12 mt-3">
                        <label for="serialNumber" class="fw-bold">Serial Number</label>
                        <textarea type="text" name="serialNumber" class="form-control" rows="4"> </textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <h5 class="fw-bold signature-color mt-md-4 mt-3">Delivery Details</h5>
            <div class="p-3 border rounded-2 background-color">
                <p id="conflictBanner" class="text-center text-white bg-danger fw-bold rounded-2 px-2 mb-2 d-none">
                    Conflict</p>
                <div class="row">
                    <div class="form-group col-md-6 ">
                        <label for="rosdForecast" class="fw-bold">rOSD (Forecast)</label>
                        <input type="date" id="rosdForecast" name="rosdForecast" class="form-control" required>
                        <div class="invalid-feedback fw-bold">
                            Please provide the rOSD (Forecast).
                        </div>
                    </div>
                    <div class="form-group col-md-6 mt-md-0 mt-3">
                        <label for="rosdPO" class="fw-bold">rOSD (PO)</label>
                        <input type="date" id="rosdPO" name="rosdPO" class="form-control">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="resolved" class="fw-bold">Resolved</label>
                        <select type="select" id="resolved" name="resolved" class="form-select">
                            <option disabled selected hidden> </option>
                            <option value="PO">PO</option>
                            <option value="Forecast">Forecast</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="rosdResolved" class="fw-bold">rOSD (Resolved)</label>
                        <input type="date" id="rosdResolved" class="form-control" disabled>
                    </div>

                    <div class="px-2">
                        <hr class="mt-4">
                    </div>

                    <div class="col-md-6 d-flex align-items-center mt-3">
                        <p class="col-md-6 fw-bold me-4 mb-0">rOSD (Changed) Request? </p>
                        <div class="col-md-6 d-flex">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" value="1" name="rosdChangedConfirmation"
                                    id="rosdChangedYes" style="cursor: pointer">
                                <label class="form-check-label" for="rosdChangedYes" style="cursor: pointer">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check mx-4">
                                <input class="form-check-input" type="radio" value="0" name="rosdChangedConfirmation"
                                    id="rosdChangedNo" style="cursor:pointer">
                                <label class="form-check-label" for="rosdChangedNo" style="cursor: pointer">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-6 mt-3 d-none">
                        <label for="rosdChanged" class="fw-bold">rOSD (Changed)</label>
                        <input type="date" id="rosdChanged" name="rosdChanged" class="form-control">
                    </div>

                    <div class="col-md-6 d-flex align-items-center mt-3 d-none" id="approveConfirmation">
                        <p class="col-md-6 fw-bold me-4 mb-0">Approved?</p>
                        <div class="col-md-6 d-flex">
                            <div class="form-check">
                                <input class="form-check-input" value="1" type="radio" name="rosdCorrectConfirmation"
                                    id="rosdCorrectYes" style="cursor: pointer">
                                <label class="form-check-label" for="rosdCorrectYes" style="cursor: pointer">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check mx-4">
                                <input class="form-check-input" value="0" type="radio" name="rosdCorrectConfirmation"
                                    id="rosdCorrectNo" style="cursor: pointer">
                                <label class="form-check-label" for="rosdCorrectNo" style="cursor: pointer">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-6 mt-3 d-none">
                        <label for="rosdCorrect" class="fw-bold">rOSD (Correct)</label>
                        <input type="date" id="rosdCorrect" class="form-control">
                    </div>
                    <div class="px-2">
                        <hr class="mt-4">
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="freightType" class="fw-bold">Freight Type</label>
                        <select name="freightType" id="freightType" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="Air">Air</option>
                            <option value="Road">Road</option>
                            <option value="Sea">Sea</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="estimatedDepartureDate" class="fw-bold">Estimated Departure Date</label>
                        <input type="date" name="estimatedDepartureDate" id="estimatedDepartureDate"
                            class="form-control">
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="actualDepartureDate" class="fw-bold">Actual Departure Date</label>
                        <input type="date" name="actualDepartureDate" id="actualDepartureDate" class="form-control">
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="actualDeliveredDate" class="fw-bold">Actual Delivered Date</label>
                        <input type="date" name="actualDeliveredDate" id="actualDeliveredDate" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-4 mb-4">
            <button class="btn btn-dark" name="addPDCProject" type="submit" id="addPDCProjectBtn">Add PDC
                Project</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fbn = document.getElementById("fbn");
        const siteType = document.getElementById("siteType");
        const freightType = document.getElementById("freightType");

        fbn.addEventListener("input", function () {
            let fbnValue = fbn.value.trim();

            if (fbnValue.substring(0, 3).toUpperCase() === "SYD" || fbnValue.substring(0, 3).toUpperCase() === "MEL") {
                freightType.value = "Road";
            } else {
                freightType.value = "Sea";
            }

            // Check for hyphen-based format
            if (fbnValue.includes('R1')) {
                siteType.value = "RETRO";
            } else {
                // Match 4 consecutive uppercase letters
                let match = fbnValue.match(/[A-Z]{4}/);
                siteType.value = match ? match[0] : "";
            }
        })

        const rosdPO = document.getElementById("rosdPO");
        const rosdForecast = document.getElementById("rosdForecast");
        const conflictBanner = document.getElementById("conflictBanner");
        const resolved = document.getElementById("resolved");
        const rosdResolved = document.getElementById("rosdResolved");

        rosdPO.disabled = true;
        resolved.disabled = true;

        rosdForecast.addEventListener("input", function () {
            rosdPO.disabled = false;
            checkResolvedStatus(); // Check after rosdForecast changes
        });

        rosdPO.addEventListener("blur", function () {
            checkResolvedStatus();
        })

        rosdPO.addEventListener("blur", function () {
            if (rosdPO.value.trim() !== rosdForecast.value.trim()) {
                conflictBanner.classList.remove("d-none");
            } else {
                conflictBanner.classList.add("d-none");
             }
        });

        // Function to enable 'resolved' only when both fields have values
        function checkResolvedStatus() {
            if (rosdForecast.value.trim() !== "" && rosdPO.value.trim() !== "") {
                resolved.disabled = false;
            } else {
                resolved.disabled = true;
            }
        }

        resolved.addEventListener("change", function () {
            if (resolved.value === "PO") {
                rosdResolved.value = rosdPO.value;
            } else if (resolved.value === "Forecast") {
                rosdResolved.value = rosdForecast.value
            }
        })

        const rosdChanged = document.getElementById("rosdChanged");
        const rosdChangedYes = document.getElementById("rosdChangedYes");
        const rosdChangedNo = document.getElementById("rosdChangedNo");
        const rosdChangedDiv = document.getElementById("rosdChanged").closest(".form-group");
        const approveConfirmation = document.getElementById("approveConfirmation");
        const rosdCorrectDiv = document.getElementById("rosdCorrect").closest(".form-group");
        const rosdCorrect = document.getElementById("rosdCorrect");

        rosdChangedYes.addEventListener("change", function () {
            if (rosdChangedYes.checked) {
                rosdChangedDiv.classList.remove("d-none");
            }
        });

        rosdChangedNo.addEventListener("change", function () {
            if (rosdChangedNo.checked) {
                rosdChangedDiv.classList.add("d-none");
                rosdChanged.value = "";
            }
        });

        rosdChanged.addEventListener("blur", function () {
            if (rosdChanged.value.trim() !== "") {
                approveConfirmation.classList.remove("d-none");
                rosdCorrectDiv.classList.remove("d-none");
            }
        })

        rosdCorrectYes.addEventListener("change", function () {
            if (rosdCorrectYes.checked) {
                rosdCorrect.value = rosdChanged.value;
            }
        })

        rosdCorrectNo.addEventListener("change", function () {
            if (rosdCorrectNo.checked) {
                rosdCorrect.value = rosdResolved.value;
            }
        })

        const estimatedDepartureDate = document.getElementById("estimatedDepartureDate");

        freightType.addEventListener("change", function () {
            if (freightType.value = "Air") {
                const rosdDate = new Date(rosdCorrect.value);
                rosdDate.setDate(rosdDate.getDate() - 7);

                estimatedDepartureDate.value = rosdDate.toISOString().split("T")[0];
            }
        })
    })
</script>