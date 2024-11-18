<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once("../../db_connect.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get user's role from login session
$employeeId = $_SESSION['employee_id'];

// SQL Query to retrieve all departments
$department_sql = 'SELECT * FROM department';
$department_result = $conn->query($department_sql);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newDepartment'])) {
    $newDepartment = $_POST['newDepartment'];

    // Check if the department name already exists
    $check_department_sql = "SELECT COUNT(*) FROM department WHERE department_name = ?";
    $check_department_result = $conn->prepare($check_department_sql);
    $check_department_result->bind_param("s", $newDepartment);
    $check_department_result->execute();
    $check_department_result->bind_result($departmentCount);
    $check_department_result->fetch();
    $check_department_result->close();

    if ($departmentCount > 0) {
        // Department already exists
        echo "<script> alert ('Department already exist.') </script>";
    } else {
        // Add the new department 
        $add_department_sql = "INSERT INTO department (department_name) VALUES (?)";
        $add_department_result = $conn->prepare($add_department_sql);
        $add_department_result->bind_param("s", $newDepartment);

        if ($add_department_result->execute()) {
            echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '"); </script>';
            exit();
        } else {
            echo "Error: " . $add_department_result . "<br>" . $conn->error;
        }
        $add_department_result->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['departmentIdToDelete'])) {
    $departmentIdToDelete = $_POST['departmentIdToDelete'];

    $delete_department_sql = "DELETE FROM department WHERE department_id = ?";
    $delete_department_result = $conn->prepare($delete_department_sql);
    $delete_department_result->bind_param("i", $departmentIdToDelete);

    if ($delete_department_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF '] . '") </script>';
        exit();
    } else {
        echo "Error: " . $delete_department_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["departmentNameToEdit"])) {
    $departmentNameToEdit = $_POST['departmentNameToEdit'];
    $departmentIdToEdit = $_POST['departmentIdToEdit'];

    $edit_department_sql = "UPDATE department SET department_name = ? WHERE department_id = ?";
    $edit_department_result = $conn->prepare($edit_department_sql);
    $edit_department_result->bind_param("si", $departmentNameToEdit, $departmentIdToEdit);

    if ($edit_department_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error: " . $edit_department_result . "<br>" . $conn->error;
    }
}

?>

<!DOCTYPE html>

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
                <li class="breadcrumb-item fw-bold signature-color">Manage Departments</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-start">
            <button class="btn btn-dark mb-2" data-bs-toggle="modal" data-bs-target="#addDepartmentModal"><i
                    class="fa-solid fa-plus me-1"></i>Add Department</button>
        </div>
        <?php if ($department_result->num_rows > 0) { ?>
            <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                <table class="table table-hover mb-0 pb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="py-4 align-middle">Department</th>
                            <th class="py-4 align-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $department_result->fetch_assoc()) { ?>
                            <tr>
                                <form method="POST">
                                    <td class="py-2 text-center align-middle">
                                        <span class="view-mode"><?php echo htmlspecialchars($row['department_name']) ?></span>
                                        <input type="hidden" name="departmentIdToEdit"
                                            value="<?php echo htmlspecialchars($row['department_id']); ?>">
                                        <input type="text" class="form-control edit-mode d-none mx-auto"
                                            name="departmentNameToEdit"
                                            value="<?php echo htmlspecialchars($row['department_name']) ?>">
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        <button class="btn edit-btn text-primary view-mode" type="button">
                                            <i class=" fa-regular fa-pen-to-square m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Edit Department"></i>
                                        </button>
                                        <button class="btn text-danger view-mode" id="deleteDepartmentBtn" type="button"
                                            data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                            data-department-id="<?php echo $row['department_id'] ?>"
                                            data-department-name="<?php echo $row['department_name'] ?>">
                                            <i class="fa-solid fa-trash-can m-1 tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Delete Department"></i>
                                        </button>
                                        <div class="edit-mode d-none d-flex justify-content-center">
                                            <button type="submit" class="btn btn-sm px-2 btn-success mx-1">
                                                <div class="d-flex justify-content-center">
                                                    <i role="button" class="fa-solid fa-check text-white m-1"></i>Edit
                                                </div>
                                            </button>
                                            <button type="button" class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                <div class="d-flex justify-content-center"> <i role="button"
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

<div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="addDepartmentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="newDepartment" class="form-label mb-0 fw-bold">New Department</label>
                        <input type="text" name="newDepartment" class="form-control" id="newDepartment" required>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-dark">Add Department</button>
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
            var departmentIdToDelete = button.getAttribute('data-department-id');
            var departmentNameToDelete = button.getAttribute('data-department-name');
            var departmentNameSpan = button.getElementById('departmentNameSpan');
            var departmentIdToDeleteInput = document.getElementById('departmentIdToDelete');

            departmentNameSpan.innerHTML = departmentNameToDelete;
            departmentIdToDeleteInput.value = departmentIdToDelete;
        })
    })
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Edit button click event handler
        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = this.closest('tr');
                row.classList.toggle('editing');

                row.querySelectorAll('.view-mode, .edit-mode').forEach(function (elem) {
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