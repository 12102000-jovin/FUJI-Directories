<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once ("./../db_connect.php");

$config = include ('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get user's role from login session
$employeeId = $_SESSION['employee_id'];

// SQL Query to retrieve all visa status
$visa_sql = "SELECT * FROM visa";
$visa_result = $conn->query($visa_sql);

// ============================ A D D  V I S A ============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newVisa'])) {
    $newVisa = $_POST['newVisa'];

    // Check if the visa name already exists
    $check_visa_sql = "SELECT COUNT(*) FROM visa WHERE visa_name = ?";
    $check_visa_result = $conn->prepare($check_visa_sql);
    $check_visa_result->bind_param("s", $newVisa);
    $check_visa_result->execute();
    $check_visa_result->bind_result($visaCount);
    $check_visa_result->fetch();
    $check_visa_result->close();

    if ($visaCount > 0) {
        // Visa already exists
        echo "<script> alert('Visa already exist.')</script>";
    } else {
        // Add new visa
        $add_visa_sql = "INSERT INTO visa (visa_name) VALUES (?)";
        $add_visa_result = $conn->prepare($add_visa_sql);
        $add_visa_result->bind_param("s", $newVisa);

        if ($add_visa_result->execute()) {
            echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
            exit();
        } else {
            echo "Error: " . $add_visa_result . "<br>" . $conn->error;
        }
        $add_visa_result->close();
    }
}

// ============================ D E L E T E   V I S A ============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['visaIdToDelete'])) {
    $visaIdToDelete = $_POST['visaIdToDelete'];

    $delete_visa_sql = "DELETE FROM visa WHERE visa_id = ?";
    $delete_visa_result = $conn->prepare($delete_visa_sql);
    $delete_visa_result->bind_param("i", $visaIdToDelete);

    if ($delete_visa_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $delete_visa_result . "<br>" . $conn->error;
    }
}

// ============================ E D I T   V I S A ============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['visaNameToEdit'])) {
    $visaNameToEdit = $_POST['visaNameToEdit'];
    $visaIdToEdit = $_POST['visaIdToEdit'];

    $edit_visa_sql = "UPDATE visa SET visa_name = ? WHERE visa_id = ?";
    $edit_visa_result = $conn->prepare($edit_visa_sql);
    $edit_visa_result->bind_param("si", $visaNameToEdit, $visaIdToEdit);

    if ($edit_visa_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $edit_visa_result . "<br>" . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico">
    <style>
        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<body class="background-color">
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                </li>
                <li class="breadcrumb-item fw-bold signature-color">Manage Visa</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-start">
            <button class="btn btn-dark mb-2" data-bs-toggle="modal" data-bs-target="#addVisaModal"> <i
                    class="fa-solid fa-plus me-1"></i>Add Visa</button>
        </div>
        <?php if ($visa_result->num_rows > 0) { ?>
            <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                <table class="table table-hover mb-0 pb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="py-4 align-middle">Visa</th>
                            <th class="py-4 align-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $visa_result->fetch_assoc()) { ?>
                            <tr>
                                <form method="POST">
                                    <td class="py-2 text-center align-middle">
                                        <span class="view-mode"> <?php echo htmlspecialchars($row['visa_name']); ?></span>
                                        <input type="hidden" name="visaIdToEdit" value="<?php echo htmlspecialchars($row['visa_id']); ?>">
                                        <input type="text" class="form-control edit-mode d-none mx-auto" name="visaNameToEdit" value="<?php echo htmlspecialchars($row['visa_name']); ?>">
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        <button class="btn edit-btn text-primary view-mode" type="button">
                                            <i class="fa-regular fa-pen-to-square m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Edit Visa"></i>
                                        </button>
                                        <button class="btn text-danger view-mode" id="deleteVisaBtn" type="button"
                                            data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                            data-visa-id=<?php echo $row['visa_id'] ?>
                                            data-visa-name="<?php echo $row['visa_name'] ?>">
                                            <i class="fa-solid fa-trash-can m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Delete Visa"></i>
                                        </button>
                                        <div class="edit-mode d-none d-flex justify-content-center">
                                            <button type="submit" class="btn btn-sm px-2 btn-success mx-1">
                                                <div class="d-flex justify-content-center"><i role="button"
                                                        class="fa-solid fa-check text-white m-1"></i> Edit </div>
                                            </button>
                                            <button type="button" class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                <div class="d-flex justify-content-center"> <i role="button"
                                                        class="fa-solid fa-xmark text-white m-1"></i>Cancel </div>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</body>

<div class="modal fade" id="addVisaModal" tabindex="-1" role="dialog" aria-labelledby="addVisaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="addVisaModalLabel">Add Visa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="newVisa" class="form-label mb-0 fw-bold">New Visa</label>
                        <input type="text" name="newVisa" class="form-control" id="newVisa" required>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-dark">Add Visa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog"
    aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Delete Visa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="visaNameSpan" class="fw-bold"></span> visa?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal"> Cancel </button>
                <form method="POST">
                    <input type="hidden" id="visaIdToDeleteInput" name="visaIdToDelete">
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteModal = document.getElementById('deleteConfirmationModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var visaIdToDelete = button.getAttribute('data-visa-id');
            var visaNameToDelete = button.getAttribute('data-visa-name');
            var visaNameSpan = document.getElementById('visaNameSpan');
            var visaIdToDeleteInput = document.getElementById('visaIdToDeleteInput');

            visaNameSpan.innerHTML = visaNameToDelete;
            visaIdToDeleteInput.value = visaIdToDelete;
        })
    })
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Edit button click event handler
        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Get the parent row
                var row = this.closest('tr');
                // Toggle edit mode
                row.classList.toggle('editing');

                // Toggle visibility of view and edit elements
                row.querySelectorAll('.view-mode, .edit-mode').forEach(function (elem) {
                    elem.classList.toggle('d-none');
                })
            })
        })
    })
</script>

<script>
    // Enabling the tooltip
    const tooltips = document.querySelectorAll('.tooltips');
    tooltips.forEach(t => {
        new bootstrap.Tooltip(t);
    })
</script>

</html>