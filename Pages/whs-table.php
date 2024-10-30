<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require '../vendor/autoload.php'; // Include the composer autoload file

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");
require_once("../email_sender.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrive session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>WHS Table</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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

        .modal-backdrop.show {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            transform: scale(1.333);
            /* Scale to counteract the body's scale */
            transform-origin: top left;
        }
    </style>
</head>

<body class="background-color">
    <?php require("../Menu/DropdownNavMenu.php") ?>

    <div class="copntainer-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/qa-index.php">Quality
                            Assurances</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">WHS Table</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-end mb-3">
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
            </div>
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
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">Search
                            </button>
                            <button class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="d-flex justify-content-end align-items-center col-4 col-lg-7">
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                            class="fa-solid fa-plus"></i> Add WHS Document</button>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="py-4 align-middle text-center whsDocumentIdColumn" style="min-width:120px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                WHS ID <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center whsDescriptionColumn" style="min-width:300px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Description <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center involvedPersonColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Involved Person Name <i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center incidentDateColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Incident Date<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center departmentColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Department<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center trirColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                TRIR<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center nearMissColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Near Miss<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center firstAidGivenColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                First Aid Given<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center medicalTreatmentCaseColumn" style="min-width:100px">
                            <a class="text-decoration-none text-white" style="cursor:pointer">
                                Medical Treatment Case<i class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>


    <?php require_once("../logout.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>