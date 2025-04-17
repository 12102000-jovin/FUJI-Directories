<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

$folder_name = "Asset";
require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'asset_no';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 30; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Search condition
$whereClause = "(asset_no LIKE '%$searchTerm%')";

$assets_sql = "SELECT 
                    assets.*, 
                    location.location_name,
                    department.department_name,
                    latest_cal.latest_calibration
                FROM assets
                LEFT JOIN location 
                    ON assets.location_id = location.location_id
                LEFT JOIN department
                    ON assets.department_id = department.department_id
                LEFT JOIN (
                    SELECT asset_id, MAX(due_date) AS latest_calibration
                    FROM asset_details
                    WHERE categories = 'Calibration'
                    GROUP BY asset_id
                ) AS latest_cal
                    ON assets.asset_id = latest_cal.asset_id
                WHERE $whereClause 
                ORDER BY $sort $order 
                LIMIT $offset, $records_per_page";


$assets_result = $conn->query($assets_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM assets WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// ========================= D E L E T E  C A B L E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assetIdToDelete"])) {
    $assetIdToDelete = $_POST["assetIdToDelete"];

    $delete_asset_sql = "DELETE FROM assets WHERE asset_id = ?";
    $delete_asset_result = $conn->prepare($delete_asset_sql);
    $delete_asset_result->bind_param("i", $assetIdToDelete);

    if ($delete_asset_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error: " . $delete_asset_result . "<br>" . $conn->error;
    }
    $delete_asset_result->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Asset Table</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
    <style>
        .table-responsive {
            transform: scale(0.75);
            transform-origin: top left;
            width: 133.3%;
        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
            color: white;
        }

        .pagination .page-link {
            color: black
        }
    </style>
</head>

<body class="background-color">
    <?php require("../Menu/NavBar.php") ?>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <!-- <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Asset Table
                    </li>
                </ol>
            </nav> -->
            <!-- <div class="d-flex justify-content-end mb-3">
                <div class="d-flex align-items-start me-2 mt-0 pt-0">
                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#filterColumnModal">
                        <i class="fa-solid fa-sliders me-1"></i>Filter Column</button>
                </div>
                <div class="btn-group shadow-lg" role="group" aria-label="Zoom Controls">
                    <button class="btn btn-sm btn-light" style="cursor:pointer" onclick="zoom(0.8)"><i
                            class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <button class="btn btn-sm btn-light" style="cursor:pointer" onclick="zoom(1.2)"><i
                            class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <button class="btn btn-sm btn-danger" style="cursor:pointer" onclick="resetZoom()"><small
                            class="fw-bold">Reset</small></button>
                </div>
            </div> -->
        </div>

        <div class="row mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="col-8 col-lg-5">
                    <form method="GET" id="searchForm">
                        <div class="d-flex align-items-center">
                            <div class="input-group me-2">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="searchDocuments" name="search"
                                    placeholder="Search Documents" value="<?php echo htmlspecialchars($searchTerm) ?>">
                            </div>
                            <button class="btn" type="submit"
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">
                                Search
                            </button>
                            <button class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="d-flex justify-content-end align-items-center col-4 col-lg-7">
                    <a class="btn btn-primary me-2" type="button" data-bs-toggle="modal"
                        data-bs-target="#assetDashboardModal"> <i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addAssetModal"> <i
                            class="fa-solid fa-plus"></i> Add Asset</button>
                </div>
            </div>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th style="max-width: 50px;"></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('department_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Department <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('asset_no', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> FE No. <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('asset_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer">Asset Name <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"> <a
                                onclick="updateSort('status', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Status <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('serial_number', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Serial Number <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('location_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer"> Asset Location <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center">
                            <as onclick="updateSort('accounts_asset', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer">Accounts <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></as>
                        </th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('whs_asset', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="curdor: pointer">WHS <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('purchase_date', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Purchase Date <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                        <th class="py-4 align-middle text-center"><a
                                onclick="updateSort('latest_calibration', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor:pointer"> Next Calibration Due <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assets_result->num_rows > 0) { ?>
                        <?php while ($row = $assets_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="align-middle text-center">
                                    <button class="btn text-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteConfirmationModal" data-asset-id="<?= $row["asset_id"] ?>"
                                        data-asset-no="<?= $row["asset_no"] ?>">
                                        <i class="fa-regular fa-trash-can text-danger"></i>
                                    </button>
                                    <button class="btn" data-bs-toggle="modal" data-bs-target="#editAssetModal"
                                        data-asset-id="<?= $row["asset_id"] ?>" data-asset-no="<?= $row["asset_no"] ?>"
                                        data-department-id="<?= $row["department_id"] ?>"
                                        data-asset-name="<?= $row["asset_name"] ?>" data-status="<?= $row["status"] ?>"
                                        data-serial-number="<?= $row["serial_number"] ?>"
                                        data-location="<?= $row["location_id"] ?>"
                                        data-accounts-asset="<?= $row["accounts_asset"] ?>"
                                        data-whs-asset="<?= $row["whs_asset"] ?>"
                                        data-purchase-date="<?= $row["purchase_date"] ?>">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <a class="btn text-warning" href="asset-details-page.php?asset_no=<?= $row['asset_no'] ?>">
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                    </a>
                                </td>
                                <td class="py-3 align-middle text-center"><?= $row['department_name'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['asset_no'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['asset_name'] ?></td>
                                <td class="py-3 align-middle text-center 
                                    <?php
                                    if ($row['status'] === "Current") {
                                        echo "bg-success text-white";
                                    } else if ($row['status'] === "Obsolete") {
                                        echo "bg-danger bg-opacity-75 text-white";
                                    } else if ($row['status'] === "Disposed") {
                                        echo "bg-danger text-white";
                                    }
                                    ?>
                                "><?= $row['status'] ?></td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['serial_number']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['serial_number']) ? htmlspecialchars($row['serial_number']) : 'N/A' ?>
                                </td>
                                <td class="py-3 align-middle text-center"><?= $row['location_name'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['accounts_asset'] == 1 ? 'Yes' : 'No' ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['whs_asset'] == 1 ? 'Yes' : 'No' ?></td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['purchase_date']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['purchase_date']) && $row['purchase_date'] ? date('j F Y', strtotime($row['purchase_date'])) : 'N/A' ?>
                                </td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['latest_calibration']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['latest_calibration']) && $row['latest_calibration']
                                        ? date('j F Y', strtotime($row['latest_calibration']))
                                        : 'N/A' ?>
                                </td>

                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="10" class="text-center py-3">No assets found</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-end mt-3 pe-2">
                <div class="d-flex align-items-center me-2">
                    <p>Rows Per Page: </p>
                </div>

                <form method="GET" class="me-2">
                    <select class="form-select" name="recordsPerPage" id="recordsPerPage"
                        onchange="updateURLWithRecordsPerPage()">
                        <option value="30" <?php echo $records_per_page == 30 ? 'selected' : ''; ?>>30</option>
                        <option value="40" <?php echo $records_per_page == 40 ? 'selected' : ''; ?>>40</option>
                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                    </select>
                </form>

                <!-- Pagination controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- First Page Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(1); return false;" aria-label="First"
                                    style="cursor: pointer">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $page - 1 ?>); return false;"
                                    aria-label="Previous" style="cursor: pointer">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        if ($page === 1 || ($page === $total_pages)) {
                            $page_range = 2;
                        } else {
                            $page_range = 3;
                        }

                        $start_page = max(1, $page - floor($page_range / 2)); // Calculate start page
                        $end_page = min($total_pages, $start_page + $page_range - 1); // Calculate end page
                        
                        // Adjust start page if it goes below 1
                        if ($end_page - $start_page < $page_range - 1) {
                            $start_page = max(1, $end_page - $page_range + 1);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>" style="cursor: pointer">
                                <a class="page-link" onclick="updatePage(<?php echo $i ?>); return false;">
                                    <?php echo $i; ?> </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $page + 1; ?>); return false;"
                                    aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Last Page Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $total_pages; ?>); return false;"
                                    aria-label="Last" style="cursor: pointer">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- ================== Add Asset Modal ================== -->
    <div class="modal fade" id="addAssetModal" tabindex="-1" aria-labelledby="addAssetModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/AddAssetForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Edit Asset Modal ================== -->
    <div class="modal fade" id="editAssetModal" tabindex="-1" aria-labelledby="editAssetModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/EditAssetForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Delete Confirmation Modal ================== -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span class="fw-bold" id="assetNoToDelete"></span> asset?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="assetIdToDelete" id="assetIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Asset Dashboard Modal ================== -->
    <div class="modal fade" id="assetDashboardModal" tab-index="-1" aria-labelledby="assetDashboardModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assetDashboardModalLabel">Asset Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body background-color">
                    <?php require_once("../PageContent/asset-index-content.php") ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editAssetModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var assetNo = button.getAttribute('data-asset-no');

                // Update the modal's content with the extracted info
                var modalAssetNo = myModalEl.querySelector('#assetNo');

                modalAssetNo.value = assetNo;

            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editAssetModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal

                var assetId = button.getAttribute('data-asset-id');
                var assetNo = button.getAttribute('data-asset-no');
                var department = button.getAttribute('data-department-id');
                var assetName = button.getAttribute('data-asset-name');
                var status = button.getAttribute('data-status');
                var serialNumber = button.getAttribute('data-serial-number');
                var purchaseDate = button.getAttribute('data-purchase-date');
                var location = button.getAttribute('data-location');
                var accountsAsset = button.getAttribute('data-accounts-asset');
                var whsAsset = button.getAttribute('data-whs-asset');


                // Update the modal's content with the extracted info
                var modalAssetId = myModalEl.querySelector('#assetIdToEdit');
                var modalAssetNo = myModalEl.querySelector('#assetNoToEdit');
                var modalDepartment = myModalEl.querySelector('#departmentToEdit');
                var modalAssetName = myModalEl.querySelector('#assetNameToEdit');
                var modalStatus = myModalEl.querySelector('#statusToEdit');
                var modalSerialNumber = myModalEl.querySelector('#serialNumberToEdit');
                var modalPurchaseDate = myModalEl.querySelector('#purchaseDateToEdit');
                var modalLocation = myModalEl.querySelector('#locationToEdit');

                modalAssetId.value = assetId
                modalAssetNo.value = assetNo.startsWith("FE") ? assetNo.substring(2) : assetNo;
                modalDepartment.value = department;
                modalAssetName.value = assetName;
                modalStatus.value = status;
                modalSerialNumber.value = serialNumber;
                modalPurchaseDate.value = purchaseDate;
                modalLocation.value = location;

                if (accountsAsset === "1") {
                    document.getElementById("accountsAssetToEditYes").checked = true;
                } else if (accountsAsset === "0") {
                    document.getElementById("accountsAssetToEditNo").checked = true;
                }

                if (whsAsset === "1") {
                    document.getElementById("whsAssetToEditYes").checked = true;
                } else if (whsAsset === "0") {
                    document.getElementById("whsAssetToEditNo").checked = true;
                }
            });
        })
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal

                var assetId = button.getAttribute('data-asset-id');
                var assetNo = button.getAttribute('data-asset-no');

                // Update the modal's content with the extracted info
                var modalAssetId = myModalEl.querySelector('#assetIdToDelete');
                var modalAssetNo = myModalEl.querySelector('#assetNoToDelete');

                modalAssetId.value = assetId;
                modalAssetNo.textContent = assetNo;
            });
        })
    </script>

    <script>
        function clearURLParameters() {
            // Use the URL API to manipulate the URL
            const url = new URL(window.location.href);
            url.search = ''; // Clear the query string

            // Reload the page with the updated URL 
            window.location.href = url.href;
        }
    </script>
    <script>
        function updateURLWithRecordsPerPage() {
            const selectElement = document.getElementById('recordsPerPage');
            const recordsPerPage = selectElement.value;
            const url = new URL(window.location.href);
            url.searchParams.set('recordsPerPage', recordsPerPage);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }
    </script>
    <script>
        function updateSort(sort, order) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
            window.location.href = url.toString();
        }
    </script>
    <script>
        function updatePage(page) {
            // Check if page number is valid
            if (page < 1) return;

            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
    </script>
</body>

</html>