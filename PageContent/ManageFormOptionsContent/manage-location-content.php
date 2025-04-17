<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("./../db_connect.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get user's role from login session
$employeeId = $_SESSION['employee_id'];

// SQL Query to retrieve all location
$location_sql = "SELECT * FROM location";
$location_result = $conn->query($location_sql);

// ============================ A D D  P O S I T I O N ============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newLocation'])) {
    $newLocation = $_POST['newLocation'];

    // Check if the location name already exists
    $check_location_sql = "SELECT COUNT(*) FROM `location` WHERE location_name = ?";
    $check_location_result = $conn->prepare($check_location_sql);
    $check_location_result->bind_param("s", $newLocation);
    $check_location_result->execute();
    $check_location_result->bind_result($locationCount);
    $check_location_result->fetch();
    $check_location_result->close();

    if ($locationCount > 0) {
        // Location already exists
        echo "<script> alert('Location already exist.')</script>";
    } else {
        // Add new location
        $add_location_sql = "INSERT INTO location (location_name) VALUES (?)";
        $add_location_result = $conn->prepare($add_location_sql);
        $add_location_result->bind_param("s", $newLocation);

        if ($add_location_result->execute()) {
            echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
            exit();
        } else {
            echo "Error: " . $add_location_result . "<br>" . $conn->error;
        }
        $add_location_result->close();
    }
}

// ============================ D E L E T E   P O S I T I O N ============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['locationIdToDelete'])) {
    $locationIdToDelete = $_POST['locationIdToDelete'];

    $delete_location_sql = "DELETE FROM location WHERE location_id = ?";
    $delete_location_result = $conn->prepare($delete_location_sql);
    $delete_location_result->bind_param("i", $locationIdToDelete);

    if ($delete_location_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $delete_location_result . "<br>" . $conn->error;
    }
}

// ============================ E D I T   P O S I T I O N ============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['locationNameToEdit'])) {
    $locationNameToEdit = $_POST['locationNameToEdit'];
    $locationIdToEdit = $_POST['locationIdToEdit'];

    $edit_location_sql = "UPDATE location SET location_name = ? WHERE location_id = ?";
    $edit_location_result = $conn->prepare($edit_location_sql);
    $edit_location_result->bind_param("si", $locationNameToEdit, $locationIdToEdit);

    if ($edit_location_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $edit_location_result . "<br>" . $conn->error;
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
        <!-- <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                </li>
                <li class="breadcrumb-item fw-bold signature-color">Manage Location</li>
            </ol>
        </nav> -->
        <div class="d-flex justify-content-end">
            <button class="btn btn-dark mb-2" data-bs-toggle="modal" data-bs-target="#addLocationModal"> <i
                    class="fa-solid fa-plus me-1"></i>Add Location</button>
        </div>

        <?php if ($location_result->num_rows > 0) { ?>
            <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                <table class="table table-hover mb-0 pb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="py-4 align-middle">Location</th>
                            <th class="py-4 align-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $location_result->fetch_assoc()) { ?>
                            <tr>
                                <form method="POST">
                                    <td class="py-2 text-center align-middle">
                                        <span class="view-mode"> <?php echo htmlspecialchars($row['location_name']); ?></span>
                                        <input type="hidden" name="locationIdToEdit"
                                            value="<?php echo htmlspecialchars($row['location_id']); ?>">
                                        <input type="text" class="form-control edit-mode d-none mx-auto"
                                            name="locationNameToEdit"
                                            value="<?php echo htmlspecialchars($row['location_name']); ?>">
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        <button class="btn edit-btn text-primary view-mode" type="button">
                                            <i class=" fa-regular fa-pen-to-square m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Edit Location"></i>
                                        </button>
                                        <button class="btn text-danger view-mode" id="deleteLocationBtn" type="button"
                                            data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                            data-location-id=<?php echo $row['location_id'] ?>
                                            data-location-name="<?php echo $row['location_name'] ?>">
                                            <i class="fa-solid fa-trash-can m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Delete Location"></i>
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

<div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog" aria-labelledby="addLocationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLocationModalLabel">Add Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="newLocation" class="form-label mb-0 fw-bold">New Location</label>
                        <input type="text" name="newLocation" class="form-control" id="newLocation" required>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-dark">Add Location</button>
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
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Delete Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="locationNameSpan" class="fw-bold"></span> location?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal"> Cancel </button>
                <form method="POST">
                    <input type="hidden" id="locationIdToDeleteInput" name="locationIdToDelete">
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
            var locationIdToDelete = button.getAttribute('data-location-id');
            var locationNameToDelete = button.getAttribute('data-location-name');
            var locationNameSpan = document.getElementById('locationNameSpan');
            var locationIdToDeleteInput = document.getElementById('locationIdToDeleteInput');

            locationNameSpan.innerHTML = locationNameToDelete;
            locationIdToDeleteInput.value = locationIdToDelete;
        })
    })
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
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