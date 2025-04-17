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
    $edit_allowance_result = $conn->prepare($edit_allowance_sql );
    $edit_allowance_result->bind_param("si", $allowanceAmount, $allowanceIdToEdit);

    if ($edit_allowance_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '")</script>';
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

<body style="background-color: #eef3f9">
    <div class="container-fluid">
        <!-- <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                </li>
                <li class="breadcrumb-item fw-bold signature-color">Manage Allowances</li>
            </ol>
        </nav> -->
        <?php if ($fixed_allowance_result->num_rows > 0): ?>
            <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
                <table class="table table-hover mb-0 pb-0">
                    <thead>
                        <tr class="text-center">
                            <th class="py-4 align-middle">
                                Allowances
                            </th>
                            <th class="py-4 align-middle"> Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $fixed_allowance_result->fetch_assoc()): ?>
                            <tr>
                                <td class="py-4 text-center align-middle">
                                    <?= $row["allowance"] ?>
                                </td>
                                <td class="py-4 text-center align-middle fw-bold">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <strong class="mt-1">$<?php echo $row["amount"] ?></strong>
                                        <button class="btn m-1" type="button" data-bs-toggle="modal" data-bs-target="#editAllowanceModal"
                                            data-allowance-id=<?php echo $row['allowance_id'] ?>
                                            data-allowance="<?php echo $row['allowance'] ?>"
                                            data-amount="<?php echo $row['amount'] ?>">
                                            <i class="fa-solid text-primary fa-pen-to-square tooltips"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Edit Allowance Rate"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>

<div class="modal fade" id="editAllowanceModal" tabindex="-1" role="dialog" aria-labelledby="editAllowanceLabel" aria-hidden="true">
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
                        <label for="allowanceAmountInput" class="form-label mb-0 fw-bold"><span id="allowanceType" class="fw-bold">  </span> Allowance</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-start">$</span>
                            <input type="number" min="0" step="any" class="form-control" name="allowanceAmountToEdit" id="allowanceAmountInput">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-dark mt-2">Edit Allowance Rate</button>
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
            var button = event.relatedTarget; // Button that trigger the modal
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