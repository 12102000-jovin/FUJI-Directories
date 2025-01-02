<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

// Connect to the database
require_once('../db_connect.php');
require_once('../status_check.php');

// $folder_name = "Project";
// require_once("../group_role_check.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'cable_no';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 20; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

// Search condition
$whereClause = "cables.cable_no LIKE '%$searchTerm%' OR cable_tags.cable_tag_no LIKE '%$searchTerm%'";

// SQL query with JOINs and GROUP BY
$cables_sql = "
    SELECT 
        cables.*, 
        location.location_name,
        MAX(cable_tags.test_date) AS last_date_tested,
        GROUP_CONCAT(cable_tags.cable_tag_no) AS cable_tag_nos
    FROM cables 
    LEFT JOIN cable_tags ON cables.cable_id = cable_tags.cable_id 
    LEFT JOIN location ON cables.location_id = location.location_id
    WHERE $whereClause 
    GROUP BY cables.cable_id ORDER BY $sort $order 
    LIMIT $offset, $records_per_page
";

// Get total number of records
$total_records_sql = " SELECT COUNT(DISTINCT cables.cable_id) AS total
FROM cables
LEFT JOIN cable_tags ON cables.cable_id = cable_tags.cable_id 
LEFT JOIN location ON cables.location_id = location.location_id
WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Execute the query
$cables_result = $conn->query($cables_sql);

// ========================= D E L E T E  C A B L E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cableIdToDelete"])) {
    $cableIdToDelete = $_POST["cableIdToDelete"];

    $delete_cable_sql = "DELETE FROM cables WHERE cable_id = ?";
    $delete_cable_result = $conn->prepare($delete_cable_sql);
    $delete_cable_result->bind_param("i", $cableIdToDelete);

    if ($delete_cable_result->execute()) {
        $current_url = $_SERVER['QUERY_STRING'];
        if (!empty($_SERVER['PHP_SELF'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error: " . $delete_cable_result . "<br>" . $conn->error;
    }
    $delete_cable_result->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Cable Table</title>
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
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/pj-index.php">Cable
                            Dashboard</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">Cable Table
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
                            class="fa-solid fa-plus"></i> Add Cable</button>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-lg bg-light mb-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th style="max-width: 50px;"></th>
                        <th class="py-4 align-middle text-center">Cable No.</th>
                        <th class="py-4 align-middle text-center">Description</th>
                        <th class="py-4 align-middle text-center">Location</th>
                        <th class="py-4 align-middle text-center">Test Frequency</th>
                        <th class="py-4 align-middle text-center">Last Date Tested</th>
                        <th class="py-4 align-middle text-center">Test Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cables_result->num_rows > 0) { ?>
                        <?php while ($row = $cables_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="align-middle text-center">
                                    <button class="btn text-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteConfirmationModal" data-cable-no="<?= $row['cable_no'] ?>"
                                        data-cable-id="<?= $row['cable_id'] ?>"><i class="fa-regular fa-trash-can"></i></button>
                                    <button class="btn text-signature" data-bs-toggle="modal" data-bs-target="#editCableModal"
                                        data-cable-id="<?= $row['cable_id'] ?>" data-cable-no="<?= $row['cable_no'] ?>"
                                        data-location="<?= $row['location_id'] ?>"
                                        data-test-frequency="<?= $row['test_frequency'] ?>"
                                        data-description="<?= $row['description'] ?>"><i
                                            class="fa-regular fa-pen-to-square"></i></button>
                                    <button class="btn text-warning" data-bs-toggle="modal" data-bs-target="#cableTestTagModal"
                                        data-cable-id="<?= $row['cable_id'] ?>" data-cable-no="<?= $row['cable_no'] ?>"> <i
                                            class="fa-solid fa-tag"></i></button>
                                    <button class="btn" data-bs-toggle="modal" data-bs-target="#barcodeModal"
                                        data-cable-no="<?= $row['cable_no'] ?>" data-location="<?= $row['location_name'] ?>"
                                        data-test-frequency="<?= $row['test_frequency'] ?>"><i
                                            class="fa-solid fa-barcode"></i></button>

                                </td>
                                <td class="py-3 align-middle text-center"><?= $row['cable_no'] ?></td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['description']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= isset($row['description']) ? htmlspecialchars($row['description']) : 'N/A' ?>
                                </td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= isset($row['location_name']) ? '' : 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' ?>">
                                    <?= $row['location_name'] === null ? 'N/A' : $row['location_name'] ?>
                                </td>
                                <td class="py-3 align-middle text-center">
                                    <?= $row['test_frequency'] === '60' ? '5 Years' : $row['test_frequency'] . ' Months' ?>
                                </td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= empty($row['last_date_tested']) ? 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' : '' ?>">
                                    <?php
                                    if ($row['last_date_tested']) {
                                        $lastDateTested = new DateTime($row['last_date_tested']);
                                        echo $lastDateTested->format('d F Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="py-3 align-middle text-center"
                                    style="<?= empty($row['last_date_tested']) ? 'background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold;' : '' ?>">
                                    <?php
                                    if ($row['last_date_tested']) {
                                        $lastDateTested = new DateTime($row['last_date_tested']);
                                        $testFrequency = $row['test_frequency'];

                                        // Add test frequency (in months) to last date tested
                                        $lastDateTested->modify("+$testFrequency months");
                                        echo $lastDateTested->format('d F Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
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
                        <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="40" <?php echo $records_per_page == 40 ? 'selected' : ''; ?>>40</option>
                        <option value="80" <?php echo $records_per_page == 80 ? 'selected' : ''; ?>>80</option>
                    </select>
                </form>

                <!-- Pagination controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
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
                                <a class="page-link"
                                    onclick="updatePage(<?php echo $i ?>); return false"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" onclick="updatePage(<?php echo $page + 1; ?>); return false;"
                                    aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- ================== Cable Test Tag Modal ================== -->
    <div class="modal fade" id="cableTestTagModal" tabindex="-1" aria-labelledby="cableTestTagModal" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cable Test Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <?php require("../PageContent/cable-tag-table.php") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Barcode Modal ================== -->
    <div class="modal fade" id="barcodeModal" tab-index="-1" aria-labelledby="barcodeModal" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cable Tag Barcode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body signature-bg-color">
                    <h1 id="cableNo" class="d-none"></h1>

                    <div class="d-flex justify-content-center">
                        <div class="d-flex justify-content-center col-md-6">
                            <div id="cableTag" class="m-2 p-0 bg-white">
                                <div class="d-flex flex-column justify-content-center align-items-center"
                                    style="max-width: 250px">
                                    <small id="cableLocation" class="fw-bold mb-2"
                                        style="transform: rotate(180deg); max-width: 210px"></small>
                                    <div style="transform: rotate(180deg); transform-origin: center;">
                                        <svg id="barcode1"></svg>
                                    </div>
                                    <h5 class="fw-bold mb-2" style="transform: rotate(180deg)">CABLE TAG</h5>
                                    <p class="text-center fw-bold" style="width:200px">
                                        ---------------------------------------</p>

                                    <h5 class="fw-bold mt-2 mb-0 pb-0">CABLE TAG</h5>
                                    <svg id="barcode2"></svg>
                                    <small
                                        style="font-family: 'IBM Plex Mono', monospace; font-size: 8px; text-align: center; display: block; line-height: 1; max-width: 195px"
                                        class="mt-2 fw-bold">
                                        All plug-in electrical equipment are required to be test-tagged according to
                                        AS/NZA
                                        3760 standards.
                                    </small>
                                    <img class="mt-2" src="../Images/FSMBE-Harwal-Logo.png" style="max-width: 180px">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary me-1" data-bs-dismiss="modal">Close</button>
                    <button onclick="saveAsImage()" class="btn btn-dark" data="">Save as PNG</button>
                </div>

            </div>
        </div>
    </div>

    <!-- ================== Add Document Modal ================== -->
    <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Cable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/AddCableForm.php") ?>
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
                    <p>Are you sure you want to delete <span class="fw-bold" id="cableNoToDelete"></span> cable?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="cableIdToDelete" id="cableIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================== Edit Cable Modal ================== -->
    <div class="modal fade" id="editCableModal" tab-index="-1" aria-labelledby="editCableModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Cable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php require_once("../Form/EditCableForm.php") ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

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
        function saveAsImage() {
            // Select the element to capture (you can change this selector based on what you want to capture)
            var elementToCapture = document.getElementById('cableTag');

            // Get the cable number from the modal
            var cableNo = document.getElementById('cableNo').textContent.trim();

            // Use html2canvas to convert the element to a canvas with a higher scale for better resolution
            html2canvas(elementToCapture, {
                scale: 10 // Increase scale for better resolution (higher scale = better resolution)
            }).then(function (canvas) {
                // Get the data URL of the canvas (in PNG format)
                var imageData = canvas.toDataURL("image/png");

                // Create a temporary link element to trigger the download
                var link = document.createElement('a');
                link.href = imageData;
                link.download = cableNo + '.png';  // The file name will be the cable number

                // Programmatically click the link to trigger the download
                link.click();
            });
        }

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('barcodeModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var cableNo = button.getAttribute('data-cable-no');

                var modalCableNo = myModalEl.querySelector('#cableNo');
                modalCableNo.textContent = cableNo; // Display the ID text

                // Generate the barcode using JsBarcode
                JsBarcode("#barcode1", cableNo, {
                    width: 1.8,            // Barcode width (adjust as needed)
                    height: 75,         // Barcode height (in pixels)
                    margin: 0,           // No margin
                    displayValue: true   // Display the value text below the barcode
                });


                // Generate the barcode using JsBarcode
                JsBarcode("#barcode2", cableNo, {
                    width: 1.8,            // Barcode width (adjust as needed)
                    height: 44,         // Barcode height (in pixels)
                    margin: 0,           // No margin
                    displayValue: true   // Display the value text below the barcode
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('barcodeModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableNo = button.getAttribute('data-cable-no');
                var location = button.getAttribute('data-location');
                var testFrequency = button.getAttribute('data-test-frequency');

                // Update the modal's content with the extracted info
                var modalCableLocation = myModalEl.querySelector('#cableLocation');

                modalCableLocation.textContent = location;

            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('cableTestTagModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableId = button.getAttribute('data-cable-id');
                var cableNo = button.getAttribute('data-cable-no');

                // Update the modal's content with the extracted info
                var modalCableId = myModalEl.querySelector('#testTagCableId');
                var modalCableIdToBeAdded = myModalEl.querySelector('#cableIdToBeAdded');
                var modalCableNo = myModalEl.querySelector('#testTagCableNo');

                modalCableId.textContent = cableId;
                modalCableIdToBeAdded.value = cableId;
                modalCableNo.textContent = cableNo;
            })
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableNo = button.getAttribute('data-cable-no');
                var cableId = button.getAttribute('data-cable-id');

                // Update the modal's content with the extracted info
                var modalCableNo = myModalEl.querySelector('#cableNoToDelete');
                var modalCableId = myModalEl.querySelector('#cableIdToDelete');

                modalCableNo.textContent = cableNo;
                modalCableId.value = cableId;
            })
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editCableModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var cableNo = button.getAttribute('data-cable-no');
                var cableId = button.getAttribute('data-cable-id');
                var location = button.getAttribute('data-location');
                var testFrequency = button.getAttribute('data-test-frequency');
                var description = button.getAttribute('data-description');

                // Update the modal's content with the extracted info
                var modalCableNo = myModalEl.querySelector('#cableNoToEdit');
                var modalCableId = myModalEl.querySelector('#cableIdToEdit');
                var modalLocation = myModalEl.querySelector('#locationToEdit');
                var modalTestFrequency = myModalEl.querySelector('#testFrequencyToEdit')
                var modalDescription = myModalEl.querySelector('#descriptionToEdit');

                modalCableNo.value = cableNo;
                modalCableId.value = cableId;
                modalLocation.value = location;
                modalTestFrequency.value = testFrequency;
                modalDescription.value = description;
            });
        })
    </script>

    <script>
        // Listen for the modal close event
        $('#cableTestTagModal').on('hidden.bs.modal', function () {
            // Reload the page and keep the parameters in the URL
            location.reload();  // This reloads the page
        });
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

</body>

</html>