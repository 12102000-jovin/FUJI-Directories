<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("./../db_connect.php");

$config = include('./../config.php');
$serverAddress = $config('server_address');
$projectName = $config('project_name');

$employeeId = $_SESSION['employee_id'];

$position_sql = "SELECT * FROM position";
$position_result = $conn->query($position_sql);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newPosition'])) {
    $newPosition = $_POST['newPosition'];

    $check_position_sql = "SELECT COUNT(*) FROM position WHERE position_name = ?";
    $check_position_result = $conn->prepare($check_position_sql);
    $check_position_result->bind_param("s", $newPosition);
    $check_position_result->execute();
    $check_position_result->bind_result($positionCount);
    $check_position_result->fetch();
    $check_position_result->close();

    if ($positionCount > 0) {
        echo "<script> alert('Position already exist.') </script>";
    } else {
        $add_position_sql = "INSERT INTO position (position_name) VALUES (?)";
        $add_position_result = $conn->prepare($add_position_sql);
        $add_position_result->bind_param("s", $newPosition);

        if ($add_position_result->execute()) {
            echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '") </script>';
            exit();
        } else {
            echo "Error: " . $add_position_result . "<br>" . $conn->error;
        }
        $add_position_result->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['positionIdToDelete'])) {
    $positionIdToDelete = $_POST['positionIdToDelete'];

    $delete_position_sql = "DELETE FROM position WHERE position_id = ?";
    $delete_position_result = $conn->prepare($delete_position_sql);
    $delete_position_result->bind_param("i", $positionIdToDelete);

    if ($delete_position_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
        exit();
    } else {
        echo "Error: " . $delete_position_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['positionNAmeToEdit'])) {
    $positionNameToEdit = $_POST['positionNameToEdit'];
    $positionIdToEdit = $_POST['positionIdToEdit'];

    $edit_position_sql = "UPDATE position SET position_name = ? WHERE position_id = ?";
    $edit_position_result = $conn->prepare($edit_position_sql);
    $edit_position_result->bind_param("si", $positionNameToEdit, $positionIdToEdit);

    if ($edit_position_result->execute()) {
        echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
        exit();
    } else {
        echo "Error: " . $edit_position_result . "<br>" . $conn->error;
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
        <div class="d-flex justify-content-end">
            <button class="btn btn-dark mb-2" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                <i class="fa-solid fa-plus me-1"></i> Add Position
            </button>
        </div>
        <?php if ($position_result->num_rows > 0) { ?>
            <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                <table class="table table-hover mb-0 pb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="py-4 align-middle">Position</th>
                            <th class="py-4 align-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $position_result->fetch_assoc()) { ?>
                            <tr>
                                <form method="POST">
                                    <td class="py-2 text-center align-middle">
                                        <span class="view-mode"><?php echo htmlspecialchars($row['position_name']); ?></span>
                                        <input type="hidden" name="positionIdToEdit"
                                            value="<?php echo htmlspecialchars($row['position_name']) ?>">
                                        <input type="text" class="form-control edit-mode d-none mx-auto"
                                            name="positionNameToEdit">
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        <button class="btn edit-btn text-primary view-mode" type="button">
                                            <i class="fa-regular fa-pen-to-square m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Edit Position"></i>
                                        </button>
                                        <button class="btn text-danger view-mode" id="deletePositionBtn" type="button"
                                            data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                            data-position-id="<?php echo $row['position_id'] ?>"
                                            data-position-name="<?php echo $row['position_name'] ?>">
                                            <i class="fa-solid fa-trash-can m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Edit Position"></i>
                                        </button>
                                        <div class="edit-mode d-none d-flex justify-content-center">
                                            <button type="submit" class="btn btn-sm px-2 btn-success mx-1">
                                                <div class="d-flex justify-content-center"><i role="button"
                                                        class="fa-solid fa-check text-white m-1"></i>Edit</div>
                                            </button>
                                            <button type="button" class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                <div class="d-flex justify-content-center"><i role="button"
                                                        class="fa-solid fa-xmark text-white m-1"></i>Cancel</div>
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

<div class="modal fade" id="addPositionModal" tabindex="-1" role="dialog" aria-labelledby="addPositionModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPositionModalLabel">Add Position</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="newPosition" class="form-label mb-0 fw-bold">New Position</label>
                        <input type="text" name="newPosition" class="form-control" id="newPosition">
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-dark">Add Position</button>
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
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Delete Position</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="newPosition" class="form-label mb-0 fw-bold">New Position</label>
                        <input type="text" name="newPosition" class="form-control" id="newPosition">
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-dark">Add Position</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteModal = document.getElementById('deleteConfirmationModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var positionIdToDelete = button.getAttribute('data-position-id');
            var positionNameToDelete = button.getAttribute('data-position-name');
            var positionNameSpan = document.getElementById('positionNameSpan');
            var positionIdToDeleteInput = document.getElementById("positionIdToDeleteInput");

            positionNameSpan.innerHTML = positionNameToDelete;
            positionIdToDeleteInput.value = positionIdToDelete;
        })
    })
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = this.closest('tr');
                row.classList.toggle('editing');

                row.querySelectorAll('.view-mode', 'edit-mode').forEach(function (elem) {
                    elem.classList.toggle('d-none');
                })
            })
        })
    })
</script>

<script>
    const tooltips = document.querySelectorAll('.tooltips');
    tooltips.forEach(t => {
        new bootstrap.Tooltip(t);
    })
</script>