<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require '../vendor/autoload.php'; // Include the Composer autoload file

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");
require_once "../email_sender.php";

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Retrieve session data
$employee_id = $_SESSION['employee_id'] ?? '';
$username = $_SESSION['username'] ?? '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'capa_document_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET['recordsPerPage']) ? intval($_GET['recordsPerPage']) : 10; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; // Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query  

// Get search term 
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build base SQL query with role-based filtering
$whereClause = "(capa_document_id LIKE '%$searchTerm%')";

// SQL query to retrieve CAPA data
$capa_sql = "SELECT * FROM capa WHERE $whereClause
    ORDER BY $sort $order
    LIMIT $offset, $records_per_page";
$capa_result = $conn->query($capa_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM capa WHERE $whereClause";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Create new email sender object
$emailSender = new emailSender();


// ========================= D E L E T E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["capaIdToDelete"])) {
    $capaIdToDelete = $_POST["capaIdToDelete"];

    $delete_document_sql = "DELETE FROM capa WHERE capa_id = ?";
    $delete_document_result = $conn->prepare($delete_document_sql);
    $delete_document_result->bind_param("i", $capaIdToDelete);

    if ($delete_document_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();

    } else {
        echo "Error: " . $delete_document_result . "<br>" . $conn->error;
    }
    $delete_document_result->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>CAPA Table</title>
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

    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">CAPA Table</li>
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
                            class="fa-solid fa-plus"></i> Add CAPA Document</button>
                </div>
            </div>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-bordered table-hover mb-0 pb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="py-4 align-middle text-center" style="min-width:100px">CAPA ID</th>
                        <th class="py-4 align-middle text-center" style="min-width:100px">Date Raised</th>
                        <th class="py-4 align-middle text-center" style="min-width:300px">CAPA Description</th>
                        <th class="py-4 align-middle text-center" style="min-width:100px">Severity</th>
                        <th class="py-4 align-middle text-center">Raised Against</th>
                        <th class="py-4 align-middle text-center">CAPA Owner</th>
                        <th class="py-4 align-middle text-center">Status</th>
                        <th class="py-4 align-middle text-center">Assigned to</th>
                        <th class="py-4 align-middle text-center" style="min-width:200px">Main Source Type</th>
                        <th class="py-4 align-middle text-center">Product/Service</th>
                        <th class="py-4 align-middle text-center">Main Fault Category</th>
                        <th class="py-4 align-middle text-center" style="min-width:100px">Target Close Date</th>
                        <th class="py-4 align-middle text-center" style="min-width:200px">Days Left</th>
                        <th class="py-4 align-middle text-center" style="min-width:100px">Date Closed</th>
                        <th class="py-4 align-middle text-center" style="min-width:200px">Key Takeaways</th>
                        <th class="py-4 align-middle text-center" style="min-width:200px">Additional Comments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($capa_result->num_rows > 0) { ?>
                        <?php while ($row = $capa_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="py-2 align-middle text-center">
                                    <div class="d-flex">
                                        <button class="btn" data-bs-toggle="modal" data-bs-target="#editDocumentModal"
                                            data-capa-id="<?= $row['capa_id'] ?>"
                                            data-capa-document-id="<?= $row['capa_document_id'] ?>"
                                            data-date-raised="<?= $row['date_raised'] ?>"
                                            data-capa-description="<?= $row['capa_description'] ?>"
                                            data-severity="<?= $row['severity'] ?>" data-capa-status="<?= $row['status'] ?>"
                                            data-raised-against="<?= $row['raised_against'] ?>"
                                            data-capa-owner="<?= $row['capa_owner'] ?>"
                                            data-assigned-to="<?= $row['assigned_to'] ?>"
                                            data-main-source-type="<?= $row['main_source_type'] ?>"
                                            data-product-or-service="<?= $row['product_or_service'] ?>"
                                            data-main-fault-category="<?= $row['main_fault_category'] ?>"
                                            data-target-close-date="<?= $row['target_close_date'] ?>">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>

                                        <button class="btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                            data-capa-id="<?= $row["capa_id"] ?>"
                                            data-capa-document-id="<?= $row["capa_document_id"] ?>"><i
                                                class="fa-regular fa-trash-can text-danger"></i></button>
                                    </div>
                                </td>
                                <td class="py-2 align-middle text-center"><a href="#"><?= $row['capa_document_id'] ?></a></td>
                                <td class="py-2 align-middle text-center"><?= $row['date_raised'] ?></td>
                                <td class="py-2 align-middle"><?= $row['capa_description'] ?></td>
                                <td class="py-2 align-middle text-center 
                                <?php
                                if ($row['severity'] === "Major" || $row['severity'] === "Minor" || $row['severity'] === "Catastrophic") {
                                    echo "text-white";
                                } else {
                                    echo "text-dark";
                                }
                                ?>" <?php
                                if ($row['severity'] === "Major") {
                                    echo 'style="background-color: #ff7e04"';
                                } else if ($row["severity"] === "Minor") {
                                    echo 'style="background-color: #fcd968"';
                                } else if ($row["severity"] === "Catastrophic") {
                                    echo 'style="background-color: #dc3545"';
                                } else {
                                    echo 'style="background-color: #ffffff"';
                                }
                                ?>>
                                    <?= $row['severity'] ?>
                                </td>
                                <td class="py-2 align-middle text-center"><?= $row['raised_against'] ?></td>
                                <td class="py-2 align-middle text-center" <?= isset($row['capa_owner']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['capa_owner']) ? $row['capa_owner'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center"><?= $row['status'] ?></td>
                                <td class="py-2 align-middle text-center" <?= isset($row['assigned_to']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['assigned_to']) ? $row['assigned_to'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center"><?= $row['main_source_type'] ?></td>
                                <td class="py-2 align-middle text-center">
                                    <?= isset($row['product_or_service']) ? $row['product_or_service'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center" <?= isset($row['main_fault_category']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['main_fault_category']) ? $row['main_fault_category'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center"><?= $row['target_close_date'] ?></td>
                                <td class="py-2 align-middle text-center 
                                <?php
                                // Define the class based on the value of daysLeft
                                if (!empty($row['target_close_date'])) {
                                    $targetCloseDate = new DateTime($row['target_close_date']);
                                    $currentDate = new DateTime();
                                    $interval = $currentDate->diff($targetCloseDate);
                                    $daysLeft = $interval->format('%r%a');

                                    // Check if overdue
                                    if ($daysLeft <= 0) {
                                        echo 'text-white bg-danger'; // Red text class if overdue or 0 days left
                                    } else if ($daysLeft <= 30) {
                                        echo 'text-white bg-warning';
                                    }
                                }
                                ?>
                                ">
                                    <?php
                                    if (!empty($row['target_close_date'])) {
                                        // Output the message based on daysLeft
                                        if ($daysLeft < 0) {
                                            echo "Overdue By " . abs($daysLeft) . " days";
                                        } else {
                                            echo "$daysLeft" . " Days Remaining";
                                        }
                                    }
                                    ?>
                                </td>

                                <td class="py-2 align-middle text-center" <?= isset($row['date_closed']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['date_closed']) ? $row['date_closed'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center" <?= isset($row['key_takeaways']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['key_takeaways']) ? $row['key_takeaways'] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center" <?= isset($row['additional_comments']) ? "" : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'" ?>>
                                    <?= isset($row['additional_comments']) ? $row['key_takeaways'] : "N/A" ?>
                                    <?= $row['additional_comments'] ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="13" class="text-center">No records found</td>
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
                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="30" <?php echo $records_per_page == 30 ? 'selected' : ''; ?>>30</option>
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

        <!-- ================== Add CAPA Document Modal ================== -->
        <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModal"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add CAPA Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php require("../Form/AddCAPADocumentForm.php") ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== Edit CAPA Document Modal ================== -->
        <div class="modal fade" id="editDocumentModal" tab-index="-1" aria-labelledby="editDocumentModal"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit CAPA Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php require("../Form/EditCAPADocumentForm.php") ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== Delete CAPA Document Modal ================== -->
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
                        <p>Are you sure you want to delete <span class="fw-bold" id="capaDocumentToDelete"></span>
                            document?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <!-- Add form submission for the deletion here -->
                        <form method="POST">
                            <input type="hidden" name="capaIdToDelete" id="capaIdToDelete">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var capaId = button.getAttribute('data-capa-id'); // Extract info from data-* attributes
                var capaDocument = button.getAttribute('data-capa-document-id');

                // Update the modal's content with the extracted info
                var modalCAPAIdToDelete = myModalEl.querySelector('#capaIdToDelete');
                var modalCAPADocument = myModalEl.querySelector('#capaDocumentToDelete');
                modalCAPAIdToDelete.value = capaId;
                modalCAPADocument.textContent = capaDocument;
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editDocumentModal');

            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;

                // Extract data from the button attributes
                var capaId = button.getAttribute('data-capa-id');
                var capaDocumentId = button.getAttribute('data-capa-document-id');
                var dateRaised = button.getAttribute('data-date-raised');
                var capaDescription = button.getAttribute('data-capa-description');
                var severity = button.getAttribute('data-severity');
                var status = button.getAttribute('data-capa-status');
                var raisedAgainst = button.getAttribute('data-raised-against');
                var capaOwner = button.getAttribute('data-capa-owner');
                var assignedTo = button.getAttribute('data-assigned-to');
                var mainSourceType = button.getAttribute('data-main-source-type');
                var productOrService = button.getAttribute('data-product-or-service');
                var mainFaultCategory = button.getAttribute('data-main-fault-category');
                var targetCloseDate = button.getAttribute('data-target-close-date');

                // Update the modal's content with the extracted data
                var modalCapaId = myModalEl.querySelector('#capaIdToEdit');
                var modalCapaDocumentId = myModalEl.querySelector('#capaDocumentIdToEdit');
                var modalDateRaised = myModalEl.querySelector('#dateRaisedToEdit');
                var modalCapaDescription = myModalEl.querySelector('#capaDescriptionToEdit');
                var modalSeverity = myModalEl.querySelector('#severityToEdit');
                var modalStatus = myModalEl.querySelector('#capaStatus');
                var modalRaisedAgainst = myModalEl.querySelector('#raisedAgainstToEdit');
                var modalCapaOwner = myModalEl.querySelector('#capaOwnerToEdit');
                var modalAssignedTo = myModalEl.querySelector('#assignedToToEdit');
                var modalMainSourceType = myModalEl.querySelector('#mainSourceTypeToEdit');
                var modalProductOrService = myModalEl.querySelector('#productOrServiceToEdit');
                var modalMainFaultCategory = myModalEl.querySelector('#mainFaultCategoryToEdit');
                var modalTargetCloseDate = myModalEl.querySelector('#targetCloseDateToEdit');

                // Assign the extracted values to the modal input fields
                modalCapaId.value = capaId;
                modalCapaDocumentId.value = capaDocumentId;
                modalDateRaised.value = dateRaised;
                modalCapaDescription.value = capaDescription;
                modalSeverity.value = severity;
                modalStatus.innerHTML = status;
                modalRaisedAgainst.value = raisedAgainst;
                modalCapaOwner.value = capaOwner;
                modalAssignedTo.value = assignedTo;
                modalMainSourceType.value = mainSourceType;
                modalProductOrService.value = productOrService;
                modalMainFaultCategory.value = mainFaultCategory;
                modalTargetCloseDate.value = targetCloseDate;
            });
        });

    </script>
</body>

</html>