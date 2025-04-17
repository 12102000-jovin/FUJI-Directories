<?php

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newDepartment'])) {
    $newDepartment = $_POST['newDepartment'];

    $check_department_sql = "SELECT COUNT(*) FROM department WHERE department_name = ?";
    $check_department_result = $conn->prepare($check_department_sql);
    $check_department_result->bind_param("s", $newDepartment);
    $check_department_result->execute();
    $check_department_result->bind_result($departmentCount);
    $check_department_result->fetch();
    $check_department_result->close();

    if ($departmentCount > 0) {
        echo "<script> alert('Department already exist.')</script>";
    } else {
        $add_department_sql = "INSERT INTO department (department_name) VALUES (?)";
        $add_department_result = $conn->prepare($add_department_sql);
        $add_department_result->bind_param("s", $newDepartment);

        if ($add_department_result->execute()) {
            echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
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
        echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
        exit();
    } else {
        echo "Error: " . $delete_department_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['departmentNameToEdit'])) {
    $departmentNameToEdit = $_POST['departmentNameToEdit'];
    $departmentIdToEdit = $_POST['departmentIdToEdit'];

    $edit_department_sql = "UPDATE department SET department_name = ? WHERE department_id = ?";
    $edit_department_result = $conn->prepare($edit_department_sql);
    $edit_department_result->bind_param("si", $departmentNameToEdit, $departmentIdToEdit);

    if ($edit_department_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '"); </script>';
        exit();
    } else {
        echo "Error: " . $edit_department_result . "<br>" . $conn->error;
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

<div class="background-color">
    <div class="container-fluid">
        <div class="d-flex justify-content-end">
            <button class="btn btn-dark mb-2" data-bs-toggle="modal" data-bs-target="#addDepartmentModal"><i
                    class="fa-solid fa-plus me-1"></i>Add Department</button>
        </div>
        <?php if ($department_result->num_rows > 0) { ?>
            <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                <table class="table table-hover mb-0 pb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="py-4 align-middle">Department</th>
                            <th class="py-4 aling-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $department_result->fetch_assoc()) { ?>
                            <tr>
                                <form method="POST">
                                    <td class="py-2 text-center align-middle">
                                        <span class="view-mode"> <?php echo htmlspecialchars($row['department_name']); ?></span>
                                        <input type="hidden" name="departmentIdToEdit"
                                            value="<?php echo htmlspecialchars($row['department_id']); ?>">
                                        <input type="text" class="form-control edit-mode d-none mx-auto"
                                            name="departmentNameToEdit"
                                            value="<?php echo htmlspecialchars($row['department_name']); ?>">
                                    </td>
                                    <td class="py-2 text-center aling-middle"></td>
                                </form>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</div>