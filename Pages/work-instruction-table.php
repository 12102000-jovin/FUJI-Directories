<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

date_default_timezone_set('Australia/Sydney');

$folder_name = "Human Resources";
require_once("../group_role_check.php");

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'employee_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 30;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$employees_sql = "SELECT employee_id, first_name, last_name FROM employees";
$employees_result = $conn->query($employees_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Work Instruction Table</title>
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
        <div class="row mb-3 mt-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
                <div class="col-12 col-sm-8 col-lg-5 d-flex justify-content-between align-items-center mb-3 mb-sm-0">
                    <form method="GET" id="searchForm" class="d-flex align-items-center w-100">
                        <div class="d-flex align-items-center">
                            <div class="input-group me-2 flex-grow-1">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="searchDocuments" name="search"
                                    placeholder="Search Documents"
                                    value="<?php echo htmlspecialchars(($searchTerm)) ?>">
                            </div>
                            <button class="btn" type="submit"
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">Search
                            </button>
                            <div class="btn btn-danger ms-2">
                                <a class="dropdown-item" href="#" onclick="clearURLParameters()">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <?php if ($role === "full control" || $role === "modify 1") { ?>
                            <th style="min-width: 50px;"> </th>
                        <?php } ?>
                        <th class="py-4 align-middle text-center" style="cursor: pointer;">
                            <a onclick="updateSort('employee_id', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">Employee Id <i
                                    class="fa-solid fa-sort fa-md ms-1"></i> </a>
                        </th>
                        <th class="py-4 align-middle text-center" style="cursor: pointer;">
                            <a onclick="updateSort('first_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white">First Name <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                        <th class="py-4 align-middle text-center" style="cursor: pointer;">
                            <a onclick="updateSort('last_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text=decoration-none text-white">Last Name <i
                                    class="fa-solid fa-sort fa-md ms-1"></i></a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employees_result->num_rows > 0) { ?>
                        <?php while ($row = $employees_result->fetch_assoc()) { ?>
                            <tr>
                                <td></td>
                                <td class="py-3 align-middle text-center">
                                    <?= $row["employee_id"] ?>
                                </td>
                                <td class="py-3 align-middle text-center">
                                    <?= $row["first_name"] ?>
                                </td>
                                <td class="py-3 align-middle text-center">
                                    <?= $row["last_name"] ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>