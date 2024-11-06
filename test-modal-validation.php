<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

$fixed_allowance_sql = "SELECT * FROM allowances";
$fixed_allowance_result = $conn->query($fixed_allowance_sql);

if ($_SERVER["REQUEST_METHOD"] && isset($_POST["allowanceIdToEdit"])) {
    $allowanceIdToEdit = $_POST['allowanceIdToEdit'];
    $allowanceAmount = $_POST['allowanceAmountToEdit'];

    $edit_allowance_sql = "UPDATE allowances SET amount = ? WHERE allowance_id = ?";
    $edit_allowance_result = $conn->prepare($edit_allowance_sql);
    $edit_allowance_result->bind_param("si", $allowanceAmount, $allowanceIdToEdit);

    if ($edit_allowance_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '") </script>';
        exit();
    } else {
        echo "Error: " . $edit_allowance_result . "<br>" . $conn->error;
    }
}
?>

<head>
    <title>Allowances</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <li class="breadcrumb-item fw-bold signature-color">Manage Users</li>
            </ol>
        </nav>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-10 order-2 order-lg-1">
                <!-- Display user details in cards on small screens -->
                <div class="d-md-none">
                    <?php while ($row = $user_details_result->fetch_assoc()): ?>
                        <div class="card mb-3 border-0 userCard">
                            <div
                                class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
                                <div class="bg-gradient shadow-lg rounded-circle mb-3"
                                    style="width: 100px; height:100px; overflow: hidden;">
                                    <?php if (!empty($row['profile_image'])): ?>
                                        <img src="data:image/jpeg;base64,<?= $row['profile_image'] ?>" alt="Profile Image"
                                            class="profile-pic img-fluid rounded-circle"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                            style="width: 100%; height: 100%;">
                                            <h3 class="p-0 m-0">
                                                <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                                            </h3>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h5 class="card-title fw-bold usernameCard">
                                    <?= $row['first_name'] . ' ' . $row['last_name'] ?>
                                </h5>
                                <h6 class="card-subtitle mb-3 text-muted userIdCard">Employee ID: <?= $row['employee_id'] ?>
                                </h6>
                                <div class="d-flex flex-wrap justify-content-center">
                                    <div class="col-md-4 mb-2 m-1">
                                        <a href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/profile-page.php?employee_id=<?= $row['employee_id'] ?>"
                                            target="_blank" class="btn btn-dark w-100"><small>Profile <i
                                                    class="fa-solid fa-up-right-from-square fa-sm"></i></small></a>˝
                                    </div>
                                    <div class="col-md-4 mb-2 m-1">
                                        <button class="btn text-white editUserModalBtn w-100"
                                            style="background-color: #043f9d" data-employee-id="<?= $row['employee+id'] ?>"
                                            data-username="<?= $row['username'] ?>" data-password="<?= $row['password'] ?>"
                                            data-first-name="<?= $row['last_name'] ?>" data-role="<?= $row['role'] ?>">
                                            <small>Edit</small> <i class="fa-regular fa-pen-to-square fa-sm mx-1"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-2 m-1">
                                        <button class="btn btn-danger w-100 deleteUserBtn"
                                            data-employee-id="<?= $row['employee_id'] ?>">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</body>

<div class="modal fade" id="editAllowanceModal" tabindex="-1" role="dialog" aria-labelledby="editAllowanceLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPositionModalLabel">Edit Allowance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <input type="hidden" class="form-control" name="allowanceIdToEdit" id="allowanceIdToEditInput">
                        <label for="allowanceAmountInput" class="form-label mb-0 fw-bold">
                            < id="allowanceType" class="fw-bold"><span>Allowance</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text rounded-start">$</span>
                            <input type="number" min="0" step="any" class="form-control" name="allowanceAmountToEdit"
                                id="allowanceAmountInput">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-dark mt-2">Edit allowance rate</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editPositionModalLabel = document.getElementById('editAllowanceModal');
        editPositionModalLabel.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var allowanceIdToEdit = button.getAttribute('data-allowance-id');
            var allowanceType = button.getAttribute('data-allowance');
            var allowanceAmount = button.getAttribute('data-amount');

            var allowanceTypeSpan = document.getElementById('allowanceType');
            var allowanceIdToEditInput = document.getElementById('allowanceIdToEditInput');
            var allowanceAmountInput = document.getElementById('allowanceAmountInput');

            allowanceTypeSpan.innerHTML = allowanceType;
            allowanceIdToEditInput.value = allowanceIdToEdit;
            allowanceAmountInput.value = allowanceAmount;
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