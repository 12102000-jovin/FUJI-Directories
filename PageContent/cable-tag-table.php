<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$loginEmployeeId = $_SESSION["employee_id"];

// Set the timezone to Australian Eastern Standard Time (AEST)
date_default_timezone_set('Australia/Sydney');

if (isset($_GET['action']) && $_GET['action'] == 'get_cable_tags') {
    $cableId = isset($_GET['cable_id']) ? $_GET['cable_id'] : '';

    if ($cableId) {

        // Query to get cable tags based on the provided cable_id
        $sql = "SELECT cable_tag_id, test_date, next_test_due, tester, result FROM cable_tags WHERE cable_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cableId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch results and output them as table rows
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['cable_tag_id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['test_date']) . '</td>';
                echo '<td>' . htmlspecialchars($row['next_test_due']) . '</td>';
                echo '<td>' . htmlspecialchars($row['tester']) . '</td>';
                echo '<td>' . htmlspecialchars($row['result']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5" class="text-center">No data found</td></tr>';
        }

        // Close the connection
        $stmt->close();
        $conn->close();
    }
}

// ===================== D E L E T E  C A B L E  T E S T  =====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["deleteCableTestTag"])) {
    $cableTagIdToBeDeleted = $_POST["cableTagIdToBeDeleted"];

    // SQL to delete cable tag
    $delete_sql = "DELETE FROM cable_tags WHERE cable_tag_id = ?";
    $delete_result = $conn->prepare($delete_sql);

    // Bind parameter
    $delete_result->bind_param("i", $cableTagIdToBeDeleted);

    if ($delete_result->execute()) {
        echo "Cable tag deleted successfully.";
    } else {
        echo "Error deleting cable tag: " . $delete_result->error;
    }
    $delete_result->close();
}

// ===================== A D D  C A B L E  T E S T  =====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["addCableTestTag"])) {
    // Get POST data
    $cableTagIdToBeAdded = $_POST["cableIdToBeAdded"];
    $cableTagNoToAdd = $_POST["cableTagNoToAdd"];
    $testDate = $_POST["testDate"];
    $tester = $_POST["tester"];
    $testResult = $_POST["testResult"];

    // Prepare the SQL query
    $add_cable_test_sql = "INSERT INTO cable_tags (cable_tag_no, test_date, tester, test_result, cable_id) VALUES (?, ?, ?, ?, ?)";
    $add_cable_test_result = $conn->prepare($add_cable_test_sql);
    $add_cable_test_result->bind_param("ssssi", $cableTagNoToAdd, $testDate, $tester, $testResult, $cableTagIdToBeAdded);

    // Execute the prepared statement
    if ($add_cable_test_result->execute()) {
        echo "Cable test tag added successfully."; // Success message
    } else {
        echo "Error adding cable test tag: " . $add_cable_test_result->error; // Error message
    }

    $add_cable_test_result->close();
}


//  ========================= SQL to get latest cable tag No =========================
$cable_tag_no_sql = "SELECT MAX(cable_tag_no) AS latest_cable_tag_no FROM cable_tags";
$cable_tag_no_result = $conn->query($cable_tag_no_sql);

if ($cable_tag_no_result->num_rows > 0) {
    $row = $cable_tag_no_result->fetch_assoc();
    $latest_cable_tag_no = $row['latest_cable_tag_no'];

    // Check if there's latest cable tag no
    if ($latest_cable_tag_no) {
        // Extract the numeric part
        $number = (int) substr($latest_cable_tag_no, 4); // Assuming 'CTAG' is 4 characters long
        $next_number = $number + 1; // Increment the number
    } else {
        // If no ID exists start with 1
        $next_number = 1;
    }

    // Format the next No
    $next_cable_tag_no = "TAG" . str_pad($next_number, 5, '0', STR_PAD_LEFT);
} else {
    echo "No records found";
}

// Get all employees 
$all_employees_sql = "SELECT * FROM employees";
$all_employees_result = $conn->query($all_employees_sql);

// Store the result in an array
$employees = [];
if ($all_employees_result->num_rows > 0) {
    while ($row = $all_employees_result->fetch_assoc()) {
        $employees[] = $row; // Store each row in the array
    }
}

?>

<p class="d-none" id="testTagCableId"></p>
<div id="cableTestTagTablePage">
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-dark" id="addCableTestTagBtn"><i class="fa-solid fa-plus me-1"></i>Add Cable Test
            Tag</button>
    </div>
    <div class="table-responsive rounded-3 shadow-lg mb-0">
        <table class="table table-bordered table-hover mb-0 pb-0" id="cableTestTagTable">
            <thead>
                <tr>
                    <th style="max-width: 50px;"></th>
                    <th class="py-4 align-middle text-center">Cable Tag Id</th>
                    <th class="py-4 align-middle text-center">Test Date</th>
                    <th class="py-4 align-middle text-center">Next Test Due</th>
                    <th class="py-4 align-middle text-center">Tester</th>
                    <th class="py-4 align-middle text-center">Result</th>
                </tr>
            </thead>
            <tbody id="cableTagsTbody">

            </tbody>
        </table>
    </div>

    <div id="barcodeContainerBg" style="display: none;">
        <div class="signature-bg-color pt-5 pb-1 mb-1 rounded-3">
            <!-- Barcode Container -->
            <div id="barcodeContainer" class="mb-5 text-center bg-white"
                style="display: none; margin: 0 auto; max-width: 200px;">
                <div style="transform: rotate(180deg);">
                    <h5 class="fw-bold mb-0 pb-0">TEST TAG</h5>
                    <div class="mx-2 mb-4">
                        <svg id="barcode" class="img-fluid"></svg>
                    </div>
                    <!-- <div class="mb-3"></div> -->
                    <img class="mb-5 img-fluid" src="../Images/FSMBE-Harwal-Logo.png" style="max-width: 180px;">
                </div>
                <p class="fw-bold" style="width: 100%;">---------------------------------------</p>

                <!-- Information & Caution Sections -->
                <div class="d-flex justify-content-center">
                    <div class="mt-4 p-1 border border-dark"
                        style="max-width: 190px; border-top: 2px solid black !important; border-left: 2px solid black !important; border-right: 2px solid black !important">
                        <h6 class="fw-bold mb-0 pb-0" style="font-size: 14px;">CAUTION</h6>
                        <small class="fw-bold text-justify"
                            style="font-family: 'IBM Plex Mono', monospace; font-size: 7px; line-height: 1; display: block;">
                            Ensure test is current before operating.
                        </small>
                        <small class="fw-bold text-justify"
                            style="font-family: 'IBM Plex Mono', monospace; font-size: 7px; line-height: 1; display: block;">
                            If appliance is defective in any way, please inform your safety officer immediately.
                        </small>
                    </div>
                </div>

                <!-- Div-based layout for Test Info -->
                <div class="d-flex justify-content-center pb-1">
                    <div class="row" style="max-width: 190px;">
                        <!-- Test Date and Next Test Due -->
                        <div class="col-6 p-2 border border-dark d-flex flex-column align-items-start"
                            style="border-left: 2px solid black !important;">
                            <small class="text-start fw-bold" style="font-size: 8px;">Test Date</small>
                            <div class="text-start fw-bold" style="font-size: 12px;" id="testDate"></div>
                        </div>
                        <div class="col-6 p-2 border border-dark d-flex flex-column align-items-start"
                            style="border-right: 2px solid black !important;">
                            <small class="text-start fw-bold" style="font-size: 8px;">Next Test Due</small>
                            <div class="text-start fw-bold" style="font-size: 12px;" id="nextTestDue"></div>
                        </div>

                        <!-- Tester and Cable Id -->
                        <div class="col-6 p-2 border border-dark d-flex flex-column align-items-start"
                            style="border-left: 2px solid black !important; border-bottom: 2px solid black !important;">
                            <small class="text-start fw-bold" style="font-size: 8px;">Tester</small>
                            <div class="text-start fw-bold" style="font-size: 10px;" id="tester"></div>
                        </div>
                        <div class="col-6 p-2 border border-dark d-flex flex-column align-items-start"
                            style="border-right: 2px solid black !important; border-bottom: 2px solid black !important;">
                            <small class="text-start fw-bold" style="font-size: 8px;">Cable No.</small>
                            <div class="text-start fw-bold" style="font-size: 12px;" id="testTagCableNo"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="cancelAndSaveImgBtn">
            <div class="d-flex justify-content-end align-items-center">
                <button class="btn btn-secondary me-1" id="closeImageBtn">Close Image Tag</button>
                <button onclick="saveAsImage2(this)" class="btn btn-dark">Save as PNG</button>
            </div>
        </div>
    </div>


</div>

<div id="addCableTestTagFormPage" class="d-none">
    <form method="POST" id="addCableTestTagForm" novalidate>
        <input type="hidden" id="cableIdToBeAdded" name="cableIdToBeAdded">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="cableTagNo" class="fw-bold">Cable No.</label>
                <input type="text" name="cableTagNoToAdd" class="form-control" id="cableTagNoToAdd"
                    value="<?php echo $next_cable_tag_no ?>" disabled>
                <div class="invalid-feedback">
                    Please provide the Cable Tag No.
                </div>
            </div>
            <div class="form-group col-md-6 mt-md-0 mt-3">
                <label for="testDate" class="fw-bold">Test Date</label>
                <input type="date" class="form-control" id="testDate" name="testDate"
                    value="<?php echo date('Y-m-d'); ?>" required>
                <div class="invalid-feedback">
                    Please provide the test date.
                </div>
            </div>
            <div class="form-group col-md-6 mt-3">
                <label for="tester" class="fw-bold">Tester</label>
                <select class="form-select" name="tester" aria-label="tester" required>
                    <option disabled selected hidden>
                        <?php
                        // Reset the result pointer to the start of the result set for the second loop
                        $all_employees_result->data_seek(0); // This resets the pointer
                        if ($all_employees_result->num_rows > 0) {
                            foreach ($employees as $row) {
                                $employeeId = $row['employee_id'];
                                $firstName = $row['first_name'];
                                $lastName = $row['last_name'];

                                // Check if the current employee matches the logged-in employee
                                $selected = ($employeeId == $loginEmployeeId) ? 'selected' : '';
                                ?>
                            <option value="<?= $employeeId ?>" <?= $selected ?>><?= $firstName ?>         <?= $lastName ?></option>
                            <?php
                            }
                        }
                        ?>
                    </option>
                </select>
                <div class="invalid-feedback">
                    Please provide the tester.
                </div>
            </div>

            <div class="form-group col-md-6 mt-3">
                <label for="testResult" class="fw-bold">Test Result</label>
                <select class="form-select" name="testResult" aria-label="testResult" required>
                    <option disabled selected hidden></option>
                    <option value="Passed">Passed</option>
                    <option value="Failed">Failed</option>
                </select>
                <div class="invalid-feedback">
                    Please provide the test result.
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-4"><button id="cancelAddCableTestTagBtn"
                class="btn btn-secondary me-1" type="button">Cancel</button>
            <button class="btn btn-dark" type="submit" id="addCableTestTagFormBtn"> Add Test Tag</button>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        // Listen for click on barcode icon
        $('#cableTestTagTable').on('click', '.barcode-icon', function () {
            // Get the cable tag details from the table row
            var cableTagNo = $(this).closest('tr').find('td:nth-child(2)').text().trim();
            var testDate = $(this).closest('tr').find('td:nth-child(3)').text().trim();
            var nextTestDue = $(this).closest('tr').find('td:nth-child(4)').text().trim();
            var tester = $(this).closest('tr').find('td:nth-child(5)').text().trim();

            // Extract first name and employee ID (assuming they are separated by a space)
            var nameParts = tester.split(' ');  // Split by space
            var firstName = nameParts[0];  // First name
            var employeeId = nameParts[nameParts.length - 1];  // Last part is the employee ID

            // Format tester as "FirstName (EmployeeID)"
            var formattedTester = "<div>" + firstName + " <span style='font-size: 8px; font-weight: bold;'>" + employeeId + "</span></div>";


            // Generate the barcode for the clicked cable tag number
            JsBarcode("#barcode", cableTagNo, {
                width: 1.8,            // Barcode width (adjust as needed)
                height: 50,            // Barcode height (in pixels)
                margin: 0,
                displayValue: true
            });

            // Populate the details inside the barcode container
            $('#testDate').text(testDate);            // Update Test Date
            $('#nextTestDue').text(nextTestDue);      // Update Next Test Due
            $('#tester').html(formattedTester);       // Update Tester with formatted name

            // Toggle the barcode container (show if hidden, hide if shown)
            $('#barcodeContainerBg').toggle();
            $('#barcodeContainer').toggle();
        });

        // Close button functionality
        $('#closeImageBtn').on('click', function () {
            $('#barcodeContainerBg').toggle();
            $('#barcodeContainer').toggle();
        })
    });

</script>

<script>
    $(document).ready(function () {
        // Listen for the modal being shown
        $('#cableTestTagModal').on('shown.bs.modal', function () {
            var cableId = $("#testTagCableId").text().trim();

            // Check if the cableId has a value
            if (cableId) {
                console.log("Cable ID:", cableId);
                $.ajax({
                    url: '../AJAXphp/fetch_cable_tags.php', // Replace with the actual PHP file URL
                    type: 'GET',
                    data: {
                        action: 'get_cable_tags',
                        cable_id: cableId
                    },
                    success: function (response) {
                        // Populate the table body with the response
                        $('#cableTagsTbody').html(response);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        $('#cableTagsTbody').html('<tr><td colspan="4" class="text-center">Error fetching data</td></tr>');
                    }
                });
            } else {
                console.warn("Cable ID is empty.");
                $('#cableTagsTbody').html('<tr><td colspan="4" class="text-center">No Cable ID provided</td></tr>');
            }
        });
    });
</script>

<script src="../html2canvas.min.js"></script>
<script>
    let selectedRow = null; // Variable to store the clicked row

    // Handle row click
    $('#cableTestTagTable tbody').on('click', 'tr', function () {
        // Highlight the selected row
        $('#cableTestTagTable tbody tr').removeClass('table-active'); // Remove highlight from other rows
        $(this).addClass('table-active'); // Add highlight to the clicked row

        // Store the selected row
        selectedRow = $(this); // Store reference to the clicked row
    });

    // Save button function
    function saveAsImage2() {
        if (!selectedRow) {
            alert('Please select a row first!'); // Ensure a row is selected
            return;
        }

        // Get the cable number from the selected row
        var cableTagId = selectedRow.find('td:nth-child(2)').text().trim();

        // Select the barcode container
        var elementToCapture = document.getElementById('barcodeContainer');

        // Set the file name using the cableTagId
        var fileName = cableTagId || "test";

        // Capture and download the image
        html2canvas(elementToCapture, {
            scale: 10,
            useCORS: true,
            logging: true
        }).then(function (canvas) {
            var imageData = canvas.toDataURL("image/png");
            var link = document.createElement('a');
            link.href = imageData;
            link.download = fileName + ".png";
            link.click();
        });
    }

</script>

<script>
    document.body.addEventListener('click', function (event) {
        const deleteButton = event.target.closest('.deleteCableTestTagBtn');
        if (deleteButton) {
            const cableTagId = deleteButton.getAttribute('data-cable-tag-id');

            if (confirm("Are you sure you want to delete this cable test?")) {
                $.ajax({
                    url: '', // Send request to the current PHP file
                    method: 'POST',
                    data: {
                        deleteCableTestTag: true,
                        cableTagIdToBeDeleted: cableTagId,
                    },
                    success: function (response) {
                        deleteButton.closest('tr').remove(); // Remove the row from the table
                        // Hide the barcode container and related buttons
                        $('#barcodeContainerBg').css('display', 'none'); // Use inline style for hiding
                        $('#barcodeContainer').css('display', 'none'); // Use inline style for hiding
                    },
                    error: function () {
                        alert('An error occured when deleting this cable test');
                    }
                })
            }
        }
    })
</script>

<script>
    document.body.addEventListener('click', function (event) {
        const editButton = event.target.closest('.editCableTestTagBtn');
        if (editButton) {
            const row = event.target.closest('tr');
            const cableTagId = editButton.getAttribute('data-cable-tag-id');

            const editCableTestTagRow = row.querySelector('td:first-child');
            editCableTestTagRow.innerHTML = `
        <div class="align-middle">
            <button class="btn btn-success saveBtn">Save</button>
            <button class="btn btn-danger cancelBtn">Cancel</button>
        </div>
    `;

            // Get employeeIdFromRow from the data-employee-id attribute of the clicked row
            const employeeIdFromRow = row.dataset.employeeId;

            // Loop through each editable td in the row
            row.querySelectorAll('td[id^="test_date_"], td[id^="tester_"], td[id^="test_result_"]').forEach(function (td) {
                let id = td.id;  // Keep the full id (e.g., test_date_123)
                const value = td.textContent.trim();

                if (id.startsWith("test_date")) {  // Correct condition check for test_date
                    td.innerHTML = `<input type="date" class="form-control" id="edit_${id}_${row.dataset.id}" value="${value}">`;
                } else if (id.startsWith("tester_")) {
                    td.innerHTML = ` 
                <select class="form-select" name="tester" aria-label="tester" required>
                    <option disabled selected hidden>Select Employee</option>
                    <?php
                    if ($all_employees_result->num_rows > 0) {
                        foreach ($employees as $employee) {
                            $employeeId = $employee['employee_id'];
                            $firstName = $employee['first_name'];
                            $lastName = $employee['last_name'];
                            ?>
                                    <option value="<?= $employeeId ?>" data-full-name="<?= $firstName ?>         <?= $lastName ?>">
                                        <?= $firstName ?>         <?= $lastName ?>
                                    </option>
                                    <?php
                        }
                    }
                    ?>
                </select>`;

                    // After the dropdown is created, preselect the employee
                    const selectElement = td.querySelector('select');
                    selectElement.value = employeeIdFromRow;  // Preselect the correct employee based on the row's data
                } else if (id.startsWith("test_result")) {
                    td.innerHTML = `
                <select class="form-select" id="edit_${id}_${row.dataset.id}">
                    <option value="Passed" ${value === "Passed" ? "selected" : ""}>Passed</option>
                    <option value="Failed" ${value === "Failed" ? "selected" : ""}>Failed</option>
                </select> 
            `;
                }
            });
        }
    });


    // Cancel button functionality to revert changes and fetch original values from the database
    document.body.addEventListener('click', function (event) {
        if (event.target && event.target.closest('.cancelBtn')) {
            const row = event.target.closest('tr');
            const cableId = row.dataset.cableId; // Assuming each row has a data attribute for cableId

            // Call the function to fetch the exact values from the database
            fetchCableTestTags(cableId).then(function (data) {
                // Update the row with the data fetched from the database
                row.querySelectorAll('td[id^="test_date_"], td[id^="tester_"], td[id^="test_result_"]').forEach(function (td) {
                    let input = td.querySelector('input');
                    let select = td.querySelector('select');

                    if (select && td.id.startsWith("tester_")) {
                        // For the tester field, use the fetched data to set the full name
                        const testerValue = data.tester; // Assuming 'tester' comes from the fetched data
                        td.textContent = testerValue; // Set the original value back to text
                    } else if (select && td.id.startsWith("test_result_")) {
                        // For the test_result field, use the fetched value for test result
                        const testResultValue = data.testResult; // Assuming 'testResult' comes from the fetched data
                        td.textContent = testResultValue; // Set the original value back to text
                    } else if (input) {
                        // For other fields, revert to the fetched value
                        const originalValue = data[input.name]; // Assuming the name of the input field matches the key in the fetched data
                        td.textContent = originalValue; // Set the original value back to text
                    } else {
                        // For fields that aren't select or input, just revert the text content
                        const originalValue = td.textContent.trim();
                        td.textContent = originalValue;
                    }
                });

                // Change the Save/Cancel buttons back to the Edit button
                const editCableTestTagRow = row.querySelector('td:first-child');
                editCableTestTagRow.innerHTML = `
                <button class="deleteCableTestTagBtn btn text-danger" data-cable-tag-id="${cableId}">
                    <i class="fa-regular fa-trash-can text-danger"></i>
                </button>
                <button class="btn editCableTestTagBtn signature-color"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn text-dark barcode-icon"><i class="fa-solid fa-barcode"></i></button>
            `;
            }).catch(function (error) {
                console.error('Error fetching cable test tags:', error);
            });
        }
    });


    // Save button functionality to submit updated data
    document.addEventListener('click', function (event) {
        if (event.target && event.target.closest('.saveBtn')) {
            const row = event.target.closest('tr');
            const cableTestTagId = row.dataset.cableTagId; // Dataset ID
            const cableId = row.dataset.cableId;

            // Collect updated values
            const updatedValues = {};
            row.querySelectorAll('td[id^="test_date_"], td[id^="tester_"], td[id^="test_result_"]').forEach(function (td) {
                const id = td.id; // Use full ID for uniqueness
                let newValue = '';

                // Handle both inputs and selects
                if (td.querySelector('input')) {
                    newValue = td.querySelector('input').value.trim(); // For input fields
                } else if (td.querySelector('select')) {
                    newValue = td.querySelector('select').value.trim(); // For select dropdowns
                }

                updatedValues[id] = newValue; // Store key-value pairs
            });

            // Send the updated data to the server via AJAX for saving
            const formData = new FormData();
            formData.append('cable_tag_id', cableTestTagId); // This will be passed in POST
            Object.keys(updatedValues).forEach(function (key) {
                formData.append(key, updatedValues[key]);
            });

            fetch('../AJAXphp/update_cable_tags.php', { // Corrected file path
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // If save is successful, update the table cells with new data
                        Object.keys(updatedValues).forEach(function (key) {
                            // Find the cell with the corresponding ID and update its content
                            const cell = row.querySelector(`#${key}`);
                            if (cell) {
                                cell.textContent = updatedValues[key]; // Update cell content with the new value
                            }
                        });


                        // Change the Save/Cancel buttons back to Edit button
                        const editCableTestTagRow = row.querySelector('td:first-child');
                        editCableTestTagRow.innerHTML = `
                            <button class="deleteCableTestTagBtn btn text-danger" data-cable-tag-id="' . htmlspecialchars($row['cable_tag_id']) . '">
                                <i class="fa-regular fa-trash-can text-danger"></i>
                            </button>
                            <button class="btn editCableTestTagBtn signature-color"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="btn text-dark barcode-icon"><i class="fa-solid fa-barcode"></i></button>
                        `;

                        alert('Cable test tag edited successfully!'); // Alert on success
                        // Hide the barcode container and related buttons
                        $('#barcodeContainerBg').css('display', 'none'); // Use inline style for hiding
                        $('#barcodeContainer').css('display', 'none'); // Use inline style for hiding
                        fetchCableTestTags(cableId);
                    } else {
                        alert("Error: " + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    });

</script>

<script>
    document.addEventListener('DOMContentLoaded', function (event) {
        const cableTestTagTablePage = document.getElementById('cableTestTagTablePage');
        const addCableTestTagFormPage = document.getElementById('addCableTestTagFormPage');
        const addCableTestTagBtn = document.getElementById('addCableTestTagBtn');
        const cancelAddCableTestTagBtn = document.getElementById('cancelAddCableTestTagBtn');

        addCableTestTagBtn.addEventListener("click", function () {
            addCableTestTagFormPage.classList.remove("d-none");
            cableTestTagTablePage.classList.add("d-none");
        })

        cancelAddCableTestTagBtn.addEventListener("click", function () {
            addCableTestTagFormPage.classList.add("d-none");
            cableTestTagTablePage.classList.remove("d-none");
        })
    })
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#addCableTestTagForm').on('submit', function (e) {
            e.preventDefault(); // Prevent form submission

            const form = this; // Reference to the form

            // Perform validation checks
            if (!form.checkValidity()) {
                form.classList.add('was-validated'); // Add Bootstrap validation style
                return; // Stop further processing if validation fails
            }

            // If all fields are valid, proceed with AJAX submission
            const cableTagIdToBeAdded = $('#cableIdToBeAdded').val().trim();
            const cableTagNoToAdd = $('#cableTagNoToAdd').val().trim();
            const testDate = $('input[name="testDate"]').val().trim();
            const tester = $('select[name="tester"]').val().trim();
            const testResult = $('select[name="testResult"]').val().trim();

            $.ajax({
                url: '', // Replace with your PHP file URL
                method: 'POST',
                data: {
                    addCableTestTag: true,
                    cableIdToBeAdded: cableTagIdToBeAdded,
                    cableTagNoToAdd: cableTagNoToAdd,
                    testDate: testDate,
                    tester: tester,
                    testResult: testResult
                },
                success: function () {
                    fetchCableTestTags(cableTagIdToBeAdded); // Refresh data
                    alert('Cable test tag added successfully!');
                    form.classList.remove('was-validated'); // Remove validation styles
                    form.reset(); // Clear form fields
                    addCableTestTagFormPage.classList.add("d-none");
                    cableTestTagTablePage.classList.remove("d-none");
                },
                error: function () {
                    alert('An error occurred while adding the cable test tag.');
                }
            });
        });
    });



    // Function to fetch cable details using AJAX
    function fetchCableTestTags(cableId) {
        $.ajax({
            url: '../AJAXphp/fetch_cable_tags.php', //PHP file to fetch cable tags
            method: 'GET',
            data: {
                action: 'get_cable_tags',  // Action to fetch cable tags
                cable_id: cableId // Send the cable ID to the server
            },
            success: function (response) {
                // Update the cable tag table with the new data
                $('#cableTagsTbody').html(response);
            },
            error: function () {
                alert("An error occurred while fetching cable test tag details.");
            }
        })
    }
</script>

<script>
    $(document).ready(function () {
        // Reset modal content and state on close
        $('#cableTestTagModal').on('hidden.bs.modal', function () {
            // Hide the barcode container and related buttons
            $('#barcodeContainerBg').css('display', 'none'); // Use inline style for hiding
            $('#barcodeContainer').css('display', 'none'); // Use inline style for hiding

            // Clear text content
            $('#testDate').text('');
            $('#nextTestDue').text('');
            $('#tester').text('');

            // Clear barcode source
            $('#barcode').attr('src', ''); // Reset barcode image

            // Reset form and table visibility
            $('#addCableTestTagFormPage').addClass('d-none'); // Hide form
            $('#cableTestTagTablePage').removeClass('d-none'); // Show table
        });
    });

</script>