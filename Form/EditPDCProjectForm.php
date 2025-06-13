<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editPDCProjectForm'])) {
    $pdcProjectIdToEdit = !empty($_POST["pdcProjectIdToEdit"]) ? $_POST["pdcProjectIdToEdit"] : NULL;
    $projectNo = !empty($_POST["projectNoToEdit"]) ? $_POST["projectNoToEdit"] : NULL;
    $fbn = $_POST["fbnToEdit"];
    $siteType = $_POST["siteTypeToEdit"];
    $status = $_POST["statusToEdit"];
    $customer = $_POST["customerToEdit"];
    $entityPoNo = !empty($_POST["entityPoNoToEdit"]) ? $_POST["entityPoNoToEdit"] : NULL;
    $awsPoNo = !empty($_POST["awsPoNoToEdit"]) ? $_POST["awsPoNoToEdit"] : NULL;
    $poDate = !empty($_POST["poDateToEdit"]) ? date('Y-m-d', strtotime($_POST["poDateToEdit"])) : NULL;
    $version = $_POST["versionToEdit"];
    $drawingStatus = $_POST["drawingStatusToEdit"];
    $quantity = !empty($_POST["quantityToEdit"]) ? (int) $_POST["quantityToEdit"] : NULL;
    $cost = !empty($_POST["costToEdit"]) ? $_POST["costToEdit"] : NULL;
    $serialNumber = !empty($_POST["serialNumberToEdit"]) ? htmlspecialchars($_POST["serialNumberToEdit"]) : NULL;
    $rosdPO = !empty($_POST["rosdPOToEdit"]) ? date('Y-m-d', strtotime($_POST["rosdPOToEdit"])) : NULL;
    $rosdForecast = !empty($_POST["rosdForecastToEdit"]) ? date('Y-m-d', strtotime($_POST["rosdForecastToEdit"])) : NULL;
    $resolved = !empty($_POST["resolvedToEdit"]) ? $_POST["resolvedToEdit"] : NULL;
    $rosdChanged = !empty($_POST["rosdChangedToEdit"]) ? date('Y-m-d', strtotime($_POST["rosdChangedToEdit"])) : NULL;
    $rosdChangedConfirmation = isset($_POST["rosdChangedConfirmationToEdit"]) ? $_POST["rosdChangedConfirmationToEdit"] : NULL;
    $rosdCorrectConfirmation = isset($_POST["rosdCorrectConfirmationToEdit"]) ? $_POST["rosdCorrectConfirmationToEdit"] : NULL;
    $freightType = $_POST["freightTypeToEdit"];
    $estimatedDepartureDate = !empty($_POST["estimatedDepartureDateToEdit"]) ? date('Y-m-d', strtotime($_POST["estimatedDepartureDateToEdit"])) : NULL;
    $actualDepartureDate = !empty($_POST["actualDepartureDateToEdit"]) ? date('Y-m-d', strtotime($_POST["actualDepartureDateToEdit"])) : NULL;
    $actualDeliveredDate = !empty($_POST["actualDeliveredDateToEdit"]) ? date('Y-m-d', strtotime($_POST["actualDeliveredDateToEdit"])) : NULL;

    // Calculate conflict 
    if ($rosdPO !== null && $rosdForecast !== null && ($rosdPO === $rosdForecast)) {
        $conflict = 0;
    } else if ($rosdPO !== null && $rosdForecast !== null && ($rosdPO !== $rosdForecast)) {
        $conflict = 1;
    } else {
        $conflict = NULL;
    }

    $edit_pdc_project_sql = "UPDATE pdc_projects SET 
        project_no = ?, 
        fbn = ?, 
        site_type = ?, 
        `status` = ?, 
        customer = ?, 
        entity_po_no = ?, 
        aws_po_no = ?, 
        purchase_order_date = ?, 
        `version` = ?, 
        drawing_status = ?, 
        qty = ?, 
        cost = ?, 
        serial_numbers = ?, 
        rosd_po = ?, 
        rosd_forecast = ?, 
        resolved = ?, 
        conflict = ?, 
        freight_type = ?, 
        rosd_change_approval = ?, 
        rosd_changed = ?, 
        estimated_departure_date = ?, 
        actual_departure_date = ?, 
        actual_delivered_date = ?, 
        approved = ?
        WHERE pdc_project_id = ?";

    if ($edit_pdc_project_stmt = $conn->prepare($edit_pdc_project_sql)) {
        $edit_pdc_project_stmt->bind_param(
            "ssssssssssiissssisissssis",
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
            $rosdCorrectConfirmation,
            $pdcProjectIdToEdit
        );

        if ($edit_pdc_project_stmt->execute()) {
            $current_url = $_SERVER['PHP_SELF'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $current_url .= '?' . $_SERVER['QUERY_STRING'];
            }
            echo "<script>alert('Project updated successfully');</script>";
            echo "<script>window.location.replace('" . $current_url . "')</script>";
        } else {
            echo "Error executing statement: " . $edit_pdc_project_result->error;
        }
    } else {
        echo "Prepare failed: " . $conn->error;
    }
}

?>

<form method="POST" id="editPDCProjectForm" novalidate>
    <p class="error-message alert alert-danger text-center p-1 d-none" style="font-size: 1.5vh; width:100%;"
        id="resultError"></p>
    <div class="row align-items-stretch">
        <div class="col-md-6 d-flex flex-column">
            <input type="hidden" name="pdcProjectIdToEdit" id="pdcProjectIdToEdit">
            <h5 class="fw-bold signature-color">General Information</h5>
            <div class="p-3 border rounded-2 h-100 background-color">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="projectNoToEdit" class="fw-bold">Project No.</label>
                        <input type="text" name="projectNoToEdit" class="form-control" id="projectNoToEdit">
                    </div>
                    <div class="form-group col-md-6 mt-md-0 mt-3">
                        <label for="fbnToEdit" class="fw-bold">FBN</label>
                        <input type="text" name="fbnToEdit" class="form-control" id="fbnToEdit">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="siteTypeToEdit" class="fw-bold">Site Type</label>
                        <input type="text" name="siteTypeToEdit" class="form-control" id="siteTypeToEdit">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="statusToEdit" class="fw-bold">Status</label>
                        <select name="statusToEdit" id="statusToEdit" class="form-select">
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
                        <label for="customerToEdit" class="fw-bold">Customer</label>
                        <select name="customerToEdit" id="customerToEdit" class="form-select">
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
                        <label for="entityPoNoToEdit" class="fw-bold">Entity's PO No.</label>
                        <input type="text" name="entityPoNoToEdit" id="entityPoNoToEdit" class="form-control">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="awsPoNoToEdit" class="fw-bold">AWS's PO No.</label>
                        <input type="text" name="awsPoNoToEdit" id="awsPoNoToEdit" class="form-control">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="poDateToEdit" class="fw-bold">PO Date</label>
                        <input type="date" name="poDateToEdit" id="poDateToEdit" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 d-flex flex-column">
            <h5 class="fw-bold signature-color mt-md-0 mt-3">PDC Type/Info</h5>
            <div class="p-3 border rounded-2 h-100 background-color">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="versionToEdit" class="fw-bold">Version</label>
                        <select name="versionToEdit" id="versionToEdit" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="V1.0 630A TF Single Door (IEC)">V1.0 630A TF Single Door (IEC)</option>
                            <option value="V1.0 630A TF Double Door (IEC)">V1.0 630A TF Double Door (IEC)</option>
                            <option value="V1.0 630A TF Double Door (AUS)">V1.0 630A TF Double Door (AUS)</option>
                            <option value="V2.0 630A TF Double Door (IEC)">V2.0 630A TF Double Door (IEC)</option>
                            <option value="V2.0 630A BF Double Door (IEC)">V2.0 630A BF Double Door (IEC)</option>
                            <option value="V2.0 250A TF Double Door (IEC)">V2.0 250A TF Double Door (IEC)</option>
                            <option value="V2.0 250A BF Double Door (IEC)">V2.0 250A BF Double Door (IEC)</option>
                            <option value="V3.0. 1000A TF (IEC)">V3.0. 1000A TF (IEC)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mt-md-0 mt-3">
                        <label for="drawingStatusToEdit" class="fw-bold">Drawing Status</label>
                        <select name="drawingStatusToEdit" id="drawingStatusToEdit" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="Drawing Created">Drawing Created</option>
                            <option value="Issued to Sheet Metal">Issued to Sheet Metal</option>
                            <option value="Submitted and Approved">Submitted and Approved</option>
                            <option value="As Built">As Built</option>
                            <option value="Sent to Customer & Complete">Sent to Customer & Complete</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="quantityToEdit" class="fw-bold">Quantity</label>
                        <input type="number" name="quantityToEdit" id="quantityToEdit" class="form-control">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="costToEdit" class="fw-bold">Cost</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-start">$</span>
                            <input type="number" min="0" step="any" class="form-control rounded-end" id="costToEdit"
                                name="costToEdit">
                        </div>
                    </div>
                    <div class="form-group col-md-12 mt-3">
                        <label for="serialNumberToEdit" class="fw-bold">Serial Number</label>
                        <textarea type="text" name="serialNumberToEdit" class="form-control" rows="4"
                            id="serialNumberToEdit"> </textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <h5 class="fw-bold signature-color mt-md-4 mt-3">Delivery Details</h5>
            <p id="conflictBannerToEdit" class="text-center text-white bg-danger fw-bold rounded-2 px-2 mb-2 d-none">
                Conflict</p>
            <div class="p-3 border rounded-2 background-color">
                <div class="row">
                    <div class="form-group col-md-6 mt-md-0 mt-3">
                        <label for="rosdForecastToEdit" class="fw-bold">rOSD (Forecast)</label>
                        <input type="date" id="rosdForecastToEdit" name="rosdForecastToEdit" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="rosdPOToEdit" class="fw-bold">rOSD (PO)</label>
                        <input type="date" id="rosdPOToEdit" name="rosdPOToEdit" class="form-control">
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="resolvedToEdit" class="fw-bold">Resolved</label>
                        <select type="select" id="resolvedToEdit" name="resolvedToEdit" class="form-select">
                            <option disabled selected hidden> </option>
                            <option value="PO">PO</option>
                            <option value="Forecast">Forecast</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mt-3">
                        <label for="rosdResolvedToEdit" class="fw-bold">rOSD (Resolved)</label>
                        <input type="date" id="rosdResolvedToEdit" name="rosdResolvedToEdit" class="form-control"
                            readonly>
                    </div>
                    <div class="px-2">
                        <hr class="mt-4">
                    </div>

                    <div class="col-md-6 d-flex align-items-center mt-3">
                        <p class="col-md-6 fw-bold me-4 mb-0">rOSD (Changed) Request?</p>
                        <div class="col-md-6 d-flex">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" value="1"
                                    name="rosdChangedConfirmationToEdit" id="rosdChangedYesToEdit"
                                    style="cursor: pointer">
                                <label class="form-check-label" for="rosdChangedYesToEdit"
                                    style="cursor: pointer">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check mx-4">
                                <input class="form-check-input" type="radio" value="0"
                                    name="rosdChangedConfirmationToEdit" id="rosdChangedNoToEdit"
                                    style="cursor: pointer">
                                <label class="form-check-label" for="rosdChangedNoToEdit"
                                    style="cursor: pointer">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="rosdChangedToEdit" class="fw-bold">rOSD (Changed)</label>
                        <input type="date" id="rosdChangedToEdit" name="rosdChangedToEdit" class="form-control">
                    </div>

                    <div class="col-md-6 d-flex align-items-center mt-3" id="approveConfirmationToEdit">
                        <p class="col-md-6 fw-bold me-4 mb-0">Approved?</p>
                        <div class="col-md-6 d-flex">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" value="1"
                                    name="rosdCorrectConfirmationToEdit" id="rosdCorrectYesToEdit"
                                    style="cursor: pointer">
                                <label class="form-check-label" for="rosdCorrectYesToEdit" style="cursor:pointer">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check mx-4">
                                <input class="form-check-input" type="radio" value="0"
                                    name="rosdCorrectConfirmationToEdit" id="rosdCorrectNoToEdit"
                                    style="cursor: pointer;">
                                <label class="form-check-label" for="rosdCorrectNoToEdit" name="rosdCorrectNoToEdit"
                                    style="cursor:pointer">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="rosdCorrectToEdit" class="fw-bold">rOSD (Correct)</label>
                        <input type="date" id="rosdCorrectToEdit" class="form-control">
                    </div>

                    <div class="px-2">
                        <hr class="mt-4">
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="freightTypeToEdit" class="fw-bold">Freight Type</label>
                        <select name="freightTypeToEdit" id="freightTypeToEdit" class="form-select">
                            <option disabled selected hidden></option>
                            <option value="Air">Air</option>
                            <option value="Road">Road</option>
                            <option value="Sea">Sea</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="estimatedDepartureDateToEdit" class="fw-bold">Estimated Departure Date</label>
                        <input type="date" name="estimatedDepartureDateToEdit" id="estimatedDepartureDateToEdit"
                            class="form-control">
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="actualDepartmentDateToEdit" class="fw-bold">Actual Departure Date</label>
                        <input type="date" name="actualDepartureDateToEdit" id="actualDepartureDateToEdit"
                            class="form-control">
                    </div>

                    <div class="form-group col-md-6 mt-3">
                        <label for="actualDeliveredDateToEdit" class="fw-bold">Actual Delivered Date</label>
                        <input type="date" name="actualDeliveredDateToEdit" id="actualDeliveredDateToEdit"
                            class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-4 mb-4">
            <button class="btn btn-dark" name="editPDCProjectForm" type="submit" id="editPDCProjectBtn">Edit PDC
                Project</button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fbn = document.getElementById("fbnToEdit");
        const siteType = document.getElementById("siteTypeToEdit");
        const freightType = document.getElementById("freightTypeToEdit");

        fbn.addEventListener("input", function () {
            let fbnValue = fbn.value.trim();

            if (fbnValue.substring(0, 3).toUpperCase() === "SYD" || fbnValue.substring(0, 3).toUpperCase() === "MEL") {
                freightType.value = "Road";
            } else {
                freightType.value = "Sea";
            }

            // Check for hyphen-based format
            if (fbnValue.includes('-')) {
                siteType.value = "RETRO";
            } else {
                // Match 4 consecutive uppercase letters
                let match = fbnValue.match(/[A-Z]{4}/);
                siteType.value = match ? match[0] : "";
            }
        })

        const rosdPO = document.getElementById("rosdPOToEdit");
        const rosdForecast = document.getElementById("rosdForecastToEdit");
        const conflictBanner = document.getElementById("conflictBannerToEdit");
        const resolved = document.getElementById("resolvedToEdit");
        const roadResolved = document.getElementById("rosdResolvedToEdit")

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

        rosdForecast.addEventListener("blur", function () {
            if (rosdPO.value.trim() !== rosdForecast.value.trim()) {
                conflictBanner.classList.remove("d-none");
            } else {
                conflictBanner.classList.add("d-none");
            }
        });

        resolved.addEventListener("change", function () {
            if (resolved.value === "PO") {
                rosdResolved.value = rosdPO.value;
            } else if (resolved.value === "Forecast") {
                rosdResolved.value = rosdForecast.value
            }
        })
    })
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('editDocumentModal');

        editModal.addEventListener('shown.bs.modal', function () {
            const rosdCorrectYes = document.getElementById('rosdCorrectYesToEdit');
            const rosdCorrectNo = document.getElementById('rosdCorrectNoToEdit');
            const rosdCorrect = document.getElementById('rosdCorrectToEdit');
            const rosdResolved = document.getElementById('rosdResolvedToEdit');
            const rosdChanged = document.getElementById('rosdChangedToEdit');
            const rosdChangedDiv = document.getElementById("rosdChangedToEdit").closest(".form-group");
            const rosdCorrectDiv = document.getElementById("rosdCorrectToEdit").closest(".form-group");
            const approveConfirmation = document.getElementById("approveConfirmationToEdit");

            function updateRosdCorrect() {
                if (rosdCorrectYes && rosdCorrectYes.checked) {
                    rosdCorrect.value = rosdChanged.value;
                } else if (rosdCorrectNo && rosdCorrectNo.checked) {
                    rosdCorrect.value = rosdResolved.value;
                } else {
                    rosdCorrect.value = "";
                }
            }

            updateRosdCorrect(); // When modal shows

            // Also re-check if radio changes
            rosdCorrectYes.addEventListener('change', updateRosdCorrect);
            rosdCorrectNo.addEventListener('change', updateRosdCorrect);

            const rosdChangedYes = document.getElementById('rosdChangedYesToEdit');
            const rosdChangedNo = document.getElementById('rosdChangedNoToEdit');

            function updateRosdChanged() {
                if (rosdChangedNo.checked) {
                    rosdChangedDiv.classList.add("d-none");
                    rosdCorrectDiv.classList.add("d-none");
                    approveConfirmation.classList.add("d-none");

                    rosdChanged.value = "";
                    rosdCorrect.value = "";
                    document.getElementById('rosdCorrectNoToEdit').checked;
                } else if (rosdChangedYes.checked) {
                    rosdChangedDiv.classList.remove("d-none");
                    rosdCorrectDiv.classList.remove("d-none");
                    approveConfirmation.classList.remove("d-none");
                }
            }

            updateRosdChanged();
            rosdChangedNo.addEventListener('change', updateRosdChanged);
            rosdChangedYes.addEventListener('change', updateRosdChanged);
        });
    });
</script>