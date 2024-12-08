<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["deleteProjectDetails"])) {
    $projectIdToBeDeleted = $_POST["projectIdToBeDeleted"];
    $projectDetailsIdToBeDeleted = $_POST["projectDetailsIdToBeDeleted"];

    // Example SQL to delete the project details
    $delete_sql = "DELETE FROM project_details WHERE project_id = ? AND project_details_id = ?";
    $delete_result = $conn->prepare($delete_sql);

    // Bind parameter
    $delete_result->bind_param("ii", $projectIdToBeDeleted, $projectDetailsIdToBeDeleted);

    if ($delete_result->execute()) {
        echo "Project details deleted successfully.";
    } else {
        echo "Error: " . $delete_result->error;
    }

    $delete_result->close();
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["addProjectDetails"])) {
    // Collect form data
    $projectId = $_POST["projectIdToBeAdded"];
    $date = $_POST["date"];
    $description = $_POST["description"];
    $unitPrice = $_POST["unitPrice"];
    $quantity = $_POST["quantity"];
    $subTotal = $quantity * $unitPrice;

    var_dump($date, $description, $unitPrice, $quantity, $subTotal, $projectId);

    // Prepare SQL query
    $add_project_details_sql = "INSERT INTO project_details (`date`, `description`, unit_price, quantity, sub_total, project_id) VALUES (?, ?, ?, ?, ?, ?)";
    $add_project_details_result = $conn->prepare($add_project_details_sql);

    // Bind parameters (s = string, d = double, i = integer)
    $add_project_details_result->bind_param("ssdidi", $date, $description, $unitPrice, $quantity, $subTotal, $projectId);

    // Execute the statement and handle result
    if ($add_project_details_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        // Improved error handling to output the specific error message
        echo "Error: " . $add_project_details_result->error . "<br>" . $conn->error;
    }

    // Close the prepared statement
    $add_project_details_result->close();
}
?>


<div class="row">
    <div class="col-md-6">
        <h5 class="fw-bold signature-color mb-0">Project Name</h5>
        <p id="projectName"></p>
    </div>
    <div class="col-md-6">
        <h5 class="fw-bold signature-color mb-0">Project No</h5>
        <p id="projectNo"></p>
    </div>
    <div class="col-md-6">
        <h5 class="fw-bold signature-color mb-0">Quote No</h5>
        <p id="quoteNo"></p>
    </div>
    <div class="col-md-6">
        <h5 class="fw-bold signature-color mb-0">Value</h5>
        <p id="value"></p>
    </div>
</div>

<div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
    <table class="table table-bordered table-hover mb-0 pb-0">
        <thead>
            <tr>
                <th class="py-3 align-middle text-center" style="max-width: 10px"></th>
                <th class="py-3 align-middle text-center" style="max-width: 40px">Item No.</th>
                <th class="py-3 align-middle text-center" style="min-width: 80px">Date</th>
                <th class="py-3 align-middle text-center" style="min-width: 240px">Description</th>
                <th class="py-3 align-middle text-center">Unit Price</th>
                <th class="py-3 align-middle text-center">Qty</th>
                <th class="py-3 align-middle text-center">Sub-Total</th>
            </tr>
        </thead>
        <tbody id="projectDetailsTbody">

        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mb-2" id="groupBtn">
    <button class="btn btn-secondary btn-sm me-1" data-bs-dismiss="modal">Close</button>
    <button class="btn btn-dark btn-sm" id="addRowBtn">Add Row</button>
</div>

<form method="POST" id="addProjectDetailsForm" class="mt-4 d-none">
    <input type="hidden" id="projectId" name="projectIdToBeAdded">
    <div class="row">
        <div class="col-md-2">
            <label for="date" class="fw-bold">Date</label>
            <input type="date" class="form-control" name="date">
        </div>
        <div class="col-md-3">
            <label for="description" class="fw-bold">Description</label>
            <textarea class="form-control" name="description" rows="1"> </textarea>
        </div>
        <div class="col-md-3">
            <label for="unitPrice" class="fw-bold">Unit Price</label>
            <div class="input-group">
                <span class="input-group-text rounded-start">$</span>
                <input type="number" min="0" step="any" class="form-control rounded-end" name="unitPrice">
            </div>
        </div>
        <div class="col-md-2">
            <label for="quantity" class="fw-bold">Qty</label>
            <input type="number" min="0" class="form-control" name="quantity">
        </div>
        <div class="col-md-2 mt-3 mt-md-0 d-flex justify-content-center align-items-end">
            <span></span>
            <button name="addProjectDetails" class="btn btn-dark" type="submit">Add</button>
            <button class="btn btn-danger ms-1" type="button" id="cancelRowBtn">Cancel</button>
        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        // Listen for the modal being shown
        $('#detailsModal').on('shown.bs.modal', function () {
            var projectId = $("#projectId").val(); // Get the project ID from the input field

            // Check if the projectId has a value
            if (projectId) {
                $.ajax({
                    url: "../AJAXphp/fetch_project_details.php", // Replace with your PHP script path
                    method: "POST",
                    data: {
                        project_id: projectId // Send the project ID to the server
                    },
                    success: function (response) {
                        // Assuming 'response' contains the data from the database
                        $("#projectDetailsTbody").html(response); // Update the table with the new data
                    },
                    error: function () {
                        alert("An error occurred while fetching project details.");
                    }
                });
            } else {
                console.log("No project ID provided in the modal input.");
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addRowBtn = document.getElementById('addRowBtn');
        const cancelRowBtn = document.getElementById('cancelRowBtn');
        const groupBtn = document.getElementById('groupBtn');
        const addProjectDetailsForm = document.getElementById('addProjectDetailsForm');

        addRowBtn.addEventListener("click", function () {
            addProjectDetailsForm.classList.remove("d-none");
            groupBtn.classList.add("d-none");
        });

        cancelRowBtn.addEventListener("click", function () {
            addProjectDetailsForm.classList.add('d-none');
            groupBtn.classList.remove("d-none");
        })

        // Add event listener for deleteProjectDetailsBtn
        document.body.addEventListener('click', function (event) {
            if (event.target.closest('.deleteProjectDetailsBtn')) {
                console.log("here");
            }
        });
    })
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (event) {
            const deleteButton = event.target.closest('.deleteProjectDetailsBtn');
            if (deleteButton) {
                const projectId = deleteButton.getAttribute('data-project-id');
                const projectDetailsId = deleteButton.getAttribute('data-project-details-id');

                if (confirm("Are you sure you want to delete this project?")) {
                    $.ajax({
                        url: '', // Send the request to the current PHP file
                        method: 'POST',
                        data: {
                            deleteProjectDetails: true,
                            projectIdToBeDeleted: projectId,
                            projectDetailsIdToBeDeleted: projectDetailsId
                        },
                        success: function (response) {
                            deleteButton.closest('tr').remove(); // Remove the row from the table
                        },
                        error: function () {
                            alert('An error occurred while deleting the project.');
                        }
                    });
                }
            }
        });
    });
</script>