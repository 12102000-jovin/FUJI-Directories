<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$assets_sql = "SELECT 
                    assets.*, 
                    location.location_name
                FROM assets
                LEFT JOIN location 
                ON assets.location_id = location.location_id";

$assets_result = $conn->query($assets_sql);
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
    <?php require("../Menu/DropdownNavMenu.php") ?>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/pj-index.php">Asset
                            Dashboard</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Asset Table
                    </li>
                </ol>
            </nav>
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
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                            class="fa-solid fa-plus"></i> Add Asset</button>
                </div>
            </div>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th style="max-width: 50px;"></th>
                        <th class="py-4 align-middle text-center">FE No.</th>
                        <th class="py-4 align-middle text-center">Asset Name</th>
                        <th class="py-4 align-middle text-center">Status</th>
                        <th class="py-4 align-middle text-center">Serial Number</th>
                        <th class="py-4 align-middle text-center">Asset Location</th>
                        <th class="py-4 align-middle text-center">Accounts</th>
                        <th class="py-4 align-middle text-center">Purchase Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assets_result->num_rows > 0) { ?>
                        <?php while ($row = $assets_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="align-middle text-center">
                                    <button class="btn" data-bs-toggle="modal" data-bs-target="#assetDetailsModal"
                                        data-asset-no="<?= $row["asset_no"] ?>">
                                        <i class="fa-solid fa-file-pen text-warning text-opacity-50"></i>
                                    </button>
                                </td>
                                <td class="py-3 align-middle text-center"><?= $row['asset_no'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['asset_name'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['status'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['serial_number'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['location_name'] ?></td>
                                <td class="py-3 align-middle text-center"><?= $row['accounts_asset'] ?></td>
                                <td class="py-3 align-middle text-center">
                                    <?= date('j F Y', strtotime($row['purchase_date'])) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" class="text-center py-3">No assets found</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- ================== Asset Table Details Modal ================== -->
    <div class="modal fade" id="assetDetailsModal" tabindex="-1" aria-labelledby="assetDetailsModal" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asset Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <?php require("../PageContent/ModalContent/asset-details-table.php") ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("../logout.php") ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('assetDetailsModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var assetNo = button.getAttribute('data-asset-no');
    
                // Update the modal's content with the extracted info
                var modalAssetNo = myModalEl.querySelector('#assetNo');

                modalAssetNo.value = assetNo;

            });
        });
    </script>
</body>

</html>