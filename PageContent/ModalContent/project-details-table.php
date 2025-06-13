<style>
    @media print {
        .hide-print {
            display: none;
        }

        .print-mode {
            min-width: 65px;
        }

        .column-print {
            display: block;
            column-count: 2 column-gap: 20px;
        }

        .print-table-head {
            background-color: white !important;
        }
    }
</style>


<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$loginEmployeeId = $_SESSION["employee_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["deleteProjectDetails"])) {
    $projectIdToBeDeleted = $_POST["projectIdToBeDeleted"];
    $projectDetailsIdToBeDeleted = $_POST["projectDetailsIdToBeDeleted"];

    //SQL to delete the project details
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
    $date = !empty($_POST["date"]) ? $_POST["date"] : null;
    $description = $_POST["description"];
    $unitPrice = $_POST["unitPrice"];
    $quantity = $_POST["quantity"];
    $subTotal = bcmul($quantity, $unitPrice, 2);
    $invoiced = 0;

    // Prepare SQL query
    $add_project_details_sql = "INSERT INTO project_details (`date`, `description`, unit_price, quantity, sub_total, project_id, invoiced) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $add_project_details_result = $conn->prepare($add_project_details_sql);

    // Bind parameters (s = string, d = double, i = integer)
    $add_project_details_result->bind_param("ssdidii", $date, $description, $unitPrice, $quantity, $subTotal, $projectId, $invoiced);

    // Execute the statement and handle result
    if ($add_project_details_result->execute()) {
        // Respond back with success
        echo "Project details added successfully.";
    } else {
        // Handle error
        echo "Error: " . $add_project_details_result->error . "<br>" . $conn->error;
    }

    // Close the prepared statement
    $add_project_details_result->close();
}
?>

<input type="hidden" id="loginEmployeeId" value="<?php echo $loginEmployeeId ?>">
<input type="hidden" id="userRole" value="<?php echo $role ?>">
<div class="row column-print">
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
        <h5 class="fw-bold signature-color mb-0">Customer</h5>
        <p id="projectCustomer"></p>
    </div>
</div>

<div class="rounded-3 mb-0" style="overflow-y: hidden;">
    <table class="table table-bordered table-hover mb-0 pb-0">
        <thead class="print-table-head">
            <tr>
                <?php if ($role == "full control" || $role == "modify 1") { ?>
                    <th class="py-3 align-middle text-center hide-print" style="min-width: 200px">Action</th>
                <?php } ?>
                <th class="py-3 align-middle text-center print-mode" style="min-width: 120px">Item No.</th>
                <th class="py-3 align-middle text-center" style="min-width: 240px">Description</th>
                <th class="py-3 align-middle text-center" style="min-width: 200px">
                    <div class="dropdown">
                        <a class="text-decoration-none text-white" data-bs-toggle="dropdown" style="cursor: pointer"
                            aria-expanded="false">
                            Estimated Delivery Date
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" id="toggleEditAllDeliveryDateBtn"> <i
                                        class="fa-solid fa-calendar-days"></i> Update All Estimated Delivery Date</a>
                            </li>
                        </ul>
                    </div>
                </th>
                <th class="py-3 align-middle text-center" style="min-width: 160px">Unit Price</th>
                <th class="py-3 align-middle text-center">Qty</th>
                <th class="py-3 align-middle text-center" style="min-width: 200px">Sub-Total</th>
                <th class="py-3 align-middle text-center">Invoiced</th>
                <th class="py-3 align-middle text-center">Approved By</th>
            </tr>
        </thead>
        <tbody id="projectDetailsTbody">

        </tbody>
    </table>
    <div class="row mt-3 m-0 mb-2 p-0">
        <div class="col-10 d-flex justify-content-end align-items-center">
            <h5 class="fw-bold m-0 p-0">Total Cost: </h5>
        </div>
        <div class="col-2 d-flex justify-content-end align-items-end">
            <h5 id="totalValue" class="fw-bold me-5 mb-0 p-0">$0.00 </h5>
        </div>
    </div>
    <div class="row m-0 mb-2 p-0">
        <div class="col-10 d-flex justify-content-end align-items-center">
            <h5 class="fw-bold m-0 p-0">10% Goods and Service Tax: </h5>
        </div>
        <div class="col-2 d-flex justify-content-end align-items-end">
            <h5 id="taxValue" class="fw-bold me-5 mb-0 p-0">$0.00 </h5>
        </div>
    </div>
    <div class="row m-0 p-0">
        <div class="col-10 d-flex justify-content-end align-items-center">
            <h5 class="fw-bold m-0 p-0">Total Amount Payable Including GST: </h5>
        </div>
        <div class="col-2 d-flex justify-content-end align-items-end">
            <h5 id="totalWithGstValue" class="fw-bold me-5 mb-0 p-0">$0.00 </h5>
        </div>
    </div>
</div>

<div class="hide-print">
    <div class="d-flex justify-content-center mb-2 mt-5" id="groupBtn">
        <button class="btn signature-btn print-button me-1" onclick="printPage()">Print</button>
        <button class="btn btn-secondary btn-sm me-1" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-dark btn-sm" id="addRowBtn">Add Project</button>
    </div>
</div>

<form method="POST" id="editAllDeliveryDateForm" class="mt-4 d-none">
    <input type="hidden" id="projectIdEditAllDate" name="projectIdEditAllDate">
    <div class="row align-items-center">
        <div class="col-3">
            <label for="newAllDeliveryDate" class="fw-bold">New Estimated Delivery Date</label>
        </div>
        <div class="col-7">
            <input type="date" class="form-control" name="newAllDeliveryDate" id="newAllDeliveryDate">
        </div>
        <div class="col-2">
            <button class="btn btn-success" id="editAllDeliveryDateBtn" type="button">Edit</button>
            <button class="btn btn-danger" id="cancelAllDeliveryDateBtn" type="button">Cancel</button>
        </div>
    </div>
</form>

<form method="POST" id="addProjectDetailsForm" class="mt-4 d-none" novalidate>
    <input type="hidden" id="projectId" name="projectIdToBeAdded">
    <div class="row">
        <div class="col-md-2">
            <label for="date" class="fw-bold">Date</label>
            <input type="date" class="form-control" name="date">
        </div>
        <div class="col-md-3">
            <label for="description" class="fw-bold">Description</label>
            <textarea class="form-control" name="description" rows="1" required></textarea>
            <div class="invalid-feedback">
                Please provide the project's description.
            </div>
        </div>
        <div class="col-md-3">
            <label for="unitPrice" class="fw-bold">Unit Price</label>
            <div class="input-group">
                <span class="input-group-text rounded-start">$</span>
                <input type="number" step="any" class="form-control rounded-end" name="unitPrice" required>
                <div class="invalid-feedback">
                    Please provide the unit price.
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <label for="quantity" class="fw-bold">Qty</label>
            <input type="number" min="0" class="form-control" name="quantity" required>
            <div class="invalid-feedback">
                Please provide the quantity.
            </div>
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
        // Function to calculate the total
        function calculateTotal(projectId) {
            let total = 0;

            // Iterate over each row in the table
            $('#projectDetailsTbody tr').each(function () {
                // Get the sub-total value using the `id` attribute (e.g., sub_total_123)
                const subTotal = parseFloat($(this).find('[id^="sub_total_"]').text().replace(/[^0-9.-]+/g, ""));
                if (!isNaN(subTotal)) {
                    total += subTotal;
                }
            });

            // Update the total in the DOM
            $("#totalValue").text("$" + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
            // Update the tax in the DOM
            $("#taxValue").text("$" + (total * 0.1).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
            // Update totalWithGst
            $("#totalWithGstValue").text("$" + ((total * 0.1) + total).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));

            // Use AJAX to insert the total into the projects table
            $.ajax({
                url: '../AJAXphp/update_project_details.php', // Update with your actual server path
                method: 'POST',
                data: {
                    action: 'update_project_value',
                    projectId: projectId,
                    totalValue: total
                },
                success: function (response) {
                    console.log('Total value updated successfully:', response);
                },
                error: function (xhr, status, error) {
                    console.error('Error updating total value:', error);
                }
            });
        }

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

                        // Recalculate total after updating table
                        calculateTotal(projectId);
                    },
                    error: function () {
                        alert("An error occurred while fetching project details.");
                    }
                });
            } else {
                console.log("No project ID provided in the modal input.");
            }
        });

        // Listen for form submission
        $('#addProjectDetailsForm').on('submit', function (e) {
            e.preventDefault(); // Prevent default form submission

            const form = this; // Reference to the form

            // Perform validation checks
            if (!form.checkValidity()) {
                $(form).addClass('was-validated'); // Add Bootstrap validation styles

                // Manually check invalid inputs and apply feedback
                $(form).find('input, textarea').each(function () {
                    if (!this.checkValidity()) {
                        $(this).addClass('is-invalid'); // Add invalid class for error display
                    } else {
                        $(this).removeClass('is-invalid'); // Remove invalid class if valid
                    }
                });
                return; // Stop further processing if validation fails
            }

            // Collect form values
            const projectId = $('#projectId').val();
            const date = $('input[name="date"]').val() || null;
            const description = $('textarea[name="description"]').val();
            const unitPrice = $('input[name="unitPrice"]').val();
            const quantity = $('input[name="quantity"]').val();
            const subTotal = unitPrice * quantity;

            // Perform AJAX to submit the form data
            $.ajax({
                url: '', // The same PHP file for processing the data
                method: 'POST',
                data: {
                    addProjectDetails: true,
                    projectIdToBeAdded: projectId,
                    date: date,
                    description: description,
                    unitPrice: unitPrice,
                    quantity: quantity,
                    subTotal: subTotal
                },
                success: function (response) {
                    // After successfully adding the project details, fetch the updated project details
                    fetchProjectDetails(projectId);

                    // Recalculate total after adding new row
                    calculateTotal(projectId);

                    // Hide the form and show the buttons again
                    $('#addProjectDetailsForm').addClass('d-none');
                    $('#groupBtn').removeClass('d-none');

                    // Reset validation state
                    form.classList.remove('was-validated'); // Clear validation
                    $(form).find('.is-invalid').removeClass('is-invalid'); // Clear error styles
                    form.reset(); // Reset form inputs
                },
                error: function () {
                    alert('An error occurred while adding project details.');
                }
            });
        });


        // Function to fetch project details using AJAX
        function fetchProjectDetails(projectId) {
            $.ajax({
                url: '../AJAXphp/fetch_project_details.php', // PHP file to fetch project details
                method: 'POST',
                data: {
                    project_id: projectId // Send the project ID to the server
                },
                success: function (response) {
                    // Update the project details table with the new data
                    $('#projectDetailsTbody').html(response);

                    // Recalculate total after updating table
                    calculateTotal(projectId);
                },
                error: function () {
                    alert("An error occurred while fetching project details.");
                }
            });
        }

        // Function to handle deleting project details
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

                            // Recalculate total after deleting row
                            calculateTotal(projectId);
                        },
                        error: function () {
                            alert('An error occurred while deleting the project.');
                        }
                    });
                }
            }
        });

        const editAllDeliveryDateBtn = document.getElementById('editAllDeliveryDateBtn');
        const newAllDeliveryDateInput = document.getElementById('newAllDeliveryDate');

        // Ensure the listener is added only once
        editAllDeliveryDateBtn.removeEventListener('click', handleEditDeliveryDateClick);
        editAllDeliveryDateBtn.addEventListener('click', handleEditDeliveryDateClick);

        function handleEditDeliveryDateClick() {
            const newDate = newAllDeliveryDateInput.value;
            const projectIdEditAllDate = document.getElementById('projectIdEditAllDate').value;

            console.log("Project ID before fetching details:", projectIdEditAllDate);

            console.log("New Date: ", newDate);

            fetch('../AJAXphp/update_project_details.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'update_all_dates', projectIdEditAllDate: projectIdEditAllDate, date: newDate })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("here we go", projectIdEditAllDate);
                        console.log("Date update success.");
                        alert("All dates updated successfully.");
                        editAllDeliveryDateForm.classList.add('d-none');
                        groupBtn.classList.remove("d-none");
                        fetchProjectDetails(projectIdEditAllDate);
                    } else {
                        alert("Error updating dates: " + (data.error || "Unknown error."));
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Failed to update dates. Please try again later.");
                });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addRowBtn = document.getElementById('addRowBtn');
        const cancelRowBtn = document.getElementById('cancelRowBtn');
        const groupBtn = document.getElementById('groupBtn');
        const addProjectDetailsForm = document.getElementById('addProjectDetailsForm');

        const toggleEditAllDeliveryDateBtn = document.getElementById('toggleEditAllDeliveryDateBtn');
        const cancelAllDeliveryDateBtn = document.getElementById('cancelAllDeliveryDateBtn');
        const editAllDeliveryDateForm = document.getElementById('editAllDeliveryDateForm');

        // Show "Add Row" form and hide "New Estimated Delivery Date" form
        addRowBtn.addEventListener("click", function () {
            addProjectDetailsForm.classList.remove("d-none");
            editAllDeliveryDateForm.classList.add("d-none"); // Ensure "New Estimated Delivery Date" form is hidden
            groupBtn.classList.add("d-none");
        });

        // Cancel "Add Row" and show buttons
        cancelRowBtn.addEventListener("click", function () {
            addProjectDetailsForm.classList.add('d-none');
            groupBtn.classList.remove("d-none");
        });

        // Show "New Estimated Delivery Date" form and hide "Add Row" form
        toggleEditAllDeliveryDateBtn.addEventListener("click", function () {
            editAllDeliveryDateForm.classList.remove("d-none");
            addProjectDetailsForm.classList.add("d-none"); // Ensure "Add Row" form is hidden
            groupBtn.classList.add("d-none");
        });

        // Cancel "New Estimated Delivery Date" and show buttons
        cancelAllDeliveryDateBtn.addEventListener("click", function () {
            editAllDeliveryDateForm.classList.add('d-none');
            groupBtn.classList.remove("d-none");
        });
    });

</script>

<script>
    $(document).ready(function () {
        // Function to reset the modal to its original form
        function resetModal() {
            // Clear inputs and reset default values
            $('#detailsModal').find('input, textarea').val('');

            // Reset any dynamically updated content (e.g., table body)
            $('#projectDetailsTbody').html('');

            // Reset form visibility and buttons
            $('#addProjectDetailsForm').addClass('d-none');
            $('#groupBtn').removeClass('d-none');
            $('#editAllDeliveryDateForm').addClass('d-none');

            // Reset displayed values (e.g., total, tax, totalWithGst)
            $("#totalValue").text('$0.00');
            $("#taxValue").text('$0.00');
            $("#totalWithGstValue").text('$0.00');
        }

        // Listen for the modal being hidden
        $('#detailsModal').on('hidden.bs.modal', function () {
            resetModal();
        });

    });

</script>
<script>
    // JavaScript function to print the page
    function printPage() {
        window.print();
    }
</script>
</body>