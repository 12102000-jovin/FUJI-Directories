<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

// Connect to the database 
require_once('../db_connect.php');
require_once('../status_check.php');

$folder_name = "Asset";
require_once("../group_role_check.php");

date_default_timezone_set('Australia/Sydney');
$currentDate = date('Y-m-d'); // Current date in Sydney

$asset_no = isset($_GET["asset_no"]) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET["asset_no"]) : null;

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

$asset_details_sql = "SELECT assets.*, department.department_id, department.department_name, location.location_id, location.location_name FROM assets JOIN department ON assets.department_id = department.department_id JOIN `location` ON location.location_id = assets.location_id WHERE asset_no = '$asset_no'";
$asset_details_result = $conn->query($asset_details_sql);

$asset_cable_sql = "
    SELECT 
        cables.*, 
        location.*, 
        cable_tags.test_date, 
        DATE_ADD(MAX(cable_tags.test_date), INTERVAL cables.test_frequency MONTH) AS next_test_due
    FROM 
        cables 
    LEFT JOIN 
        location ON location.location_id = cables.location_id 
    LEFT JOIN 
        cable_tags ON cable_tags.cable_id = cables.cable_id
    WHERE 
        cables.asset_no = '$asset_no'
        AND (cables.location_id IS NULL OR location.location_id IS NOT NULL)
    GROUP BY 
        cables.cable_no
    ORDER BY 
        MAX(cable_tags.test_date) DESC
";


$asset_cable_result = $conn->query($asset_cable_sql);


$asset_maintenance_sql = "SELECT asset_details.*, assets.* FROM asset_details 
                            JOIN assets ON assets.asset_id = asset_details.asset_id WHERE assets.asset_no = '$asset_no' AND categories = 'Maintenance' ORDER BY performed_date DESC";
$asset_maintenance_result = $conn->query($asset_maintenance_sql);

$asset_repair_sql = "SELECT asset_details.*, assets.* FROM asset_details 
                            JOIN assets ON assets.asset_id = asset_details.asset_id WHERE assets.asset_no = '$asset_no' AND categories = 'Repair' ORDER BY performed_date DESC";
$asset_repair_result = $conn->query($asset_repair_sql);

$asset_calibration_sql = "SELECT asset_details.*, assets.* FROM asset_details 
                            JOIN assets ON assets.asset_id = asset_details.asset_id WHERE assets.asset_no = '$asset_no' AND categories = 'Calibration' ORDER BY performed_date DESC";
$asset_calibration_result = $conn->query($asset_calibration_sql);

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photos'])) {
    $uploadDir = 'D:/FSMBEH-Data/00 - QA/04 - Assets/' . $asset_no . '/00 - Photos/';

    // Create the directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $photos = $_FILES['photos'];
    $messages = [];

    for ($i = 0; $i < count($photos['name']); $i++) {
        $fileName = basename($photos['name'][$i]);
        $targetFilePath = $uploadDir . $fileName;

        // Check for errors
        if ($photos['error'][$i] === UPLOAD_ERR_OK) {
            // Validate file type (only images)
            $fileType = mime_content_type($photos['tmp_name'][$i]);
            if (strpos($fileType, 'image/') === 0) {
                // Move file to upload directory
                if (move_uploaded_file($photos['tmp_name'][$i], $targetFilePath)) {
                    $messages[] = "Uploaded: " . htmlspecialchars($fileName);
                } else {
                    $messages[] = "Failed to upload: " . htmlspecialchars($fileName);
                }
            } else {
                $messages[] = "Invalid file type: " . htmlspecialchars($fileName);
            }
        } else {
            $messages[] = "Error uploading: " . htmlspecialchars($fileName);
        }
    }
}

// Check if the request is a POST for deleting a photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $file_name = $_POST['file'] ?? '';
    $image_folder = 'D:/FSMBEH-Data/00 - QA/04 - Assets/' . $asset_no . '/00 - Photos/';
    $file_path = $image_folder . basename($file_name);

    // Check if the file exists and delete it
    if (file_exists($file_path) && unlink($file_path)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'File not found or cannot be deleted.']);
    }
    exit; // Ensure that no further code is executed after the response
}

// Add asset details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assetIdToAdd'])) {
    $assetIdToAdd = $_POST['assetIdToAdd'];
    $categories = $_POST['categoriesToAdd'];
    $performedDate = $_POST['performedDate'];
    $dueDate = isset($_POST['dueDate']) && !empty($_POST['dueDate']) ? $_POST['dueDate'] : null;
    $source = $_POST['source'];
    $description = $_POST['description'];

    // Determine the folder path based on the category
    switch ($categories) {
        case 'Manual':
            $folderPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/01 - Manual";
            break;
        case 'Warranty':
            $folderPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/02 - Warranty";
            break;
        case 'Calibration':
            $folderPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/03 - Calibration Certificates";
            break;
        case 'Maintenance':
            $folderPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/04 - Maintenance Logs";
            break;
        case 'Repair':
            $folderPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/05 - Service and Repairs Certificates";
            break;
        default:
            $folderPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/Other"; // Default folder
            break;
    }

    // Create the folder if it doesn't exist
    if (!file_exists($folderPath)) {
        mkdir($folderPath, 0777, true);
    }

    // Process the uploaded files
    if (isset($_FILES['assetDetailsFiles']) && !empty($_FILES['assetDetailsFiles']['name'][0])) {
        $files = $_FILES['assetDetailsFiles'];

        // Loop through files
        foreach ($files['name'] as $key => $fileName) {
            $fileTmpName = $files['tmp_name'][$key];

            // Format the maintenance date to 'd-m-Y' or any format you prefer
            $formattedDate = date('d-m-Y', strtotime($performedDate));

            // Get file extension
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

            // Base new file name
            $baseFileName = $asset_no . '-' . $formattedDate;

            // Check if a file with the same name already exists
            $newFileName = $baseFileName . '.' . $fileExtension;
            $counter = 1;

            // If the file exists, append (1), (2), etc. until a unique name is found
            while (file_exists($folderPath . '/' . $newFileName)) {
                $newFileName = $baseFileName . "($counter)." . $fileExtension;
                $counter++;
            }

            // Set the destination path with the new file name
            $fileDestination = $folderPath . '/' . $newFileName;

            // Move the uploaded file to the target folder
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                echo "File uploaded successfully: $newFileName <br>";
            } else {
                echo "Error uploading file: $fileName <br>";
            }
        }
    }

    // Only insert into the database if the category is not 'Warranty'
    if ($categories !== 'Warranty' || $categories !== 'Manual') {
        // Insert asset details into the database
        $add_asset_details_sql = "INSERT INTO asset_details (performed_date, due_date, categories, source, description, asset_id) VALUES (?, ?, ?, ?, ?, ?)";
        $add_asset_details_result = $conn->prepare($add_asset_details_sql);
        $add_asset_details_result->bind_param("sssssi", $performedDate, $dueDate, $categories, $source, $description, $assetIdToAdd);

        // Execute the prepared statement
        if ($add_asset_details_result->execute()) {
            $current_url = $_SERVER['PHP_SELF'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $current_url .= '?' . $_SERVER['QUERY_STRING'];
            }
            echo "<script>alert('Document added successfully');</script>";
            echo "<script>window.location.replace('" . $current_url . "');</script>";
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    } else {
        // If the category is 'Warranty', only file upload happens and no database entry
        echo "<script>alert('Files uploaded successfully.ßß');</script>";
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        echo "<script>window.location.replace('" . $current_url . "');</script>";
        exit();
    }
}


?>

<!DOCTYPE html>
<html>

<head>
    <title>Asset Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        table thead tr.custom-table {
            background-color: red !important;
        }

        .table thead th {
            background-color: black;
            color: white;
            border: 1px solid black !important;
        }

        .photo-container {
            position: relative;
            display: inline-block;
        }

        .photo-container .delete-btn {
            display: none;
        }

        .photo-container:hover .delete-btn {
            display: block;
        }

        .btn-outline-dark:hover {
            color: white;
        }

        .btn-outline-dark {
            color: black;
        }

        @media (max-width: 768px) {

            .asset-photo {
                width: 100px !important;
                height: 100px !important;
            }

            .addPhotoSmallIcon {
                display: none;
            }

            .addPhotoSmall {
                font-size: 11px;
                margin-bottom: 0px;
            }

            .smallScreenText {
                font-size: 8px;
            }

            .delete-btn {
                font-size: 11px;
            }
        }
    </style>
</head>

<body class="background-color">
    <?php require("../Menu/NavBar.php") ?>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/asset-table.php">Asset
                            Table</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">
                        <?php echo $asset_no ?>
                    </li>
                </ol>
            </nav>
        </div>

        <?php
        $image_folder = "/assets/$asset_no/00 - Photos"; // Public URL path
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $photo_found = false; // Initialize the flag here
        ?>

        <div class="d-flex flex-wrap">
            <div class="d-flex flex-wrap">
                <?php
                $photo_found = false; // Initialize photo_found
                $image_dir = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/00 - Photos"; // Path to the actual folder on the server
                $public_url_base = "http://192.168.0.7/assets/$asset_no/00%20-%20Photos"; // Public URL base for assets
                
                if (is_dir($image_dir)) {
                    $files = scandir($image_dir); // Get all files in the folder
                    foreach ($files as $file) {
                        $file_path = $public_url_base . '/' . urlencode($file); // Construct public URL path
                        $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                        // Check if the file is an image
                        if (in_array($file_extension, $allowed_extensions)) {
                            echo '
                    <div class="photo-container position-relative me-1 rounded-2">
                        <img src="' . htmlspecialchars($file_path) . '" 
                             alt="' . htmlspecialchars($file) . '" 
                             width="200" 
                             height="200" 
                             class="rounded-2 asset-photo" 
                             data-bs-toggle="modal" 
                             data-bs-target="#imageModal" 
                             data-bs-image="' . htmlspecialchars($file_path) . '">
                        <button class="btn btn-danger btn-sm delete-btn position-absolute top-0 end-0 m-1" 
                                data-file="' . htmlspecialchars($file) . '">
                            &times;
                        </button>
                    </div>';
                            $photo_found = true; // Set flag to true if an image is found
                        }
                    }
                }

                // If no photos were found, display the Asset No. image container
                if (!$photo_found) {
                    echo '<div class="me-1 rounded-2 signature-bg-color asset-photo" style="width: 200px; height: 200px; display: flex; align-items: center; justify-content: center;">
                <span class="fw-bold text-white text-nowrap smallScreenText"> No Photo - ' . htmlspecialchars($asset_no) . '</span>
            </div>';
                }
                ?>
            </div>

            <!-- Add More Photos Button -->
            <button
                class="btn d-flex flex-column align-items-center justify-content-center bg-secondary bg-opacity-10 asset-photo"
                style="width: 200px; height: 200px;" data-bs-toggle="modal" data-bs-target="#addPhotoModal">
                <h6 class="addPhotoSmall"><i class="fa-solid fa-plus me-1 "></i>Add Photo</h6>
                <i class="fa-regular fa-images addPhotoSmallIcon" style="font-size: 50px;"></i>
            </button>
        </div>


        <?php if ($asset_details_result->num_rows > 0) { ?>
            <?php while ($row = $asset_details_result->fetch_assoc()) { ?>
                <div class="d-flex align-items-center p-3 bg-white shadow-lg rounded-3 mt-4">
                    <div class="row w-100">
                        <div class="col-md-2">
                            <small>Asset No.</small>
                            <h4 class="fw-bold signature-color"><?php echo $asset_no ? $asset_no : "N/A"; ?></h4>
                        </div>

                        <div class="col-md-6">
                            <small>Asset Name</small>
                            <h4 class="fw-bold signature-color">
                                <?= !empty($row['asset_name']) ? htmlspecialchars($row['asset_name']) : "N/A" ?>
                            </h4>
                        </div>

                        <div class="col-md-4">
                            <small>Serial Number</small>
                            <h4 class="fw-bold signature-color">
                                <?= !empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : "N/A" ?>
                            </h4>
                        </div>

                        <div class="col-md-2 mt-0 mt-md-3">
                            <small>Department</small>
                            <h4 class="fw-bold signature-color">
                                <?= !empty($row['department_name']) ? htmlspecialchars($row['department_name']) : "N/A" ?>
                            </h4>
                        </div>

                        <div class="col-md-3 mt-0 mt-md-3">
                            <small>Purchase Date</small>
                            <h4 class="fw-bold signature-color">
                                <?php
                                $purchase_date = !empty($row['purchase_date']) ? $row['purchase_date'] : null;
                                if (!$purchase_date) {
                                    echo "N/A";
                                } else {
                                    $date = DateTime::createFromFormat('Y-m-d', $purchase_date);
                                    if ($date) {
                                        echo $date->format('d F Y');  // Format as 12 January 2021
                                    } else {
                                        echo "Invalid date format";
                                    }
                                }
                                ?>
                            </h4>
                        </div>

                        <div class="col-md-3 mt-0 mt-md-3">
                            <small>Location</small>
                            <h4 class="fw-bold signature-color">
                                <?= !empty($row['location_name']) ? htmlspecialchars($row['location_name']) : "N/A" ?>
                            </h4>
                        </div>

                        <div class="col-md-3 mt-0 mt-md-3">
                            <small>Status</small>
                            <h4 class="fw-bold signature-color">
                                <?= !empty($row['status']) ? htmlspecialchars($row['status']) : "N/A" ?>
                            </h4>
                        </div>
                    </div>
                </div>
                <div class="row g-3"> <!-- Use g-3 for gap between columns -->
                    <div class="col-md-4">
                        <div class="p-3 shadow-lg rounded-3 mt-4 signature-bg-color text-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold mb-0 pb-0">Maintenance</h5>
                                <div>
                                    <button class="btn btn-sm btn-info">
                                        <?php
                                        $subFolder = '04 - Maintenance Logs';
                                        $encodedSubFolder = urlencode($subFolder);
                                        ?>
                                        <a href="../open-asset-folder.php?folder=<?php echo urlencode($asset_no) ?>&sub_folder=<?php echo $encodedSubFolder ?>"
                                            target="_blank">
                                            <i class="fa-solid fa-folder"></i>
                                        </a>
                                    </button>

                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#assetDetailsHistoryModal" data-details-type="Maintenance"
                                        data-asset-id="<?php echo $row["asset_id"] ?>"><i
                                            class="fa-solid fa-clock-rotate-left fa-sm"></i></button>
                                    <button class="btn btn-sm btn-dark" data-bs-target="#addAssetDetailsModal"
                                        data-bs-toggle="modal" data-details-type="Maintenance"
                                        data-asset-id="<?php echo $row["asset_id"] ?>"><i
                                            class="fa-solid fa-plus fa-sm"></i></button>
                                </div>
                            </div>
                            <?php if ($asset_maintenance_result->num_rows > 0) { ?>
                                <ul class="list-group mt-3">
                                    <?php
                                    // Fetch the first maintenance record (latest)
                                    $maintenance_row = $asset_maintenance_result->fetch_assoc();
                                    ?>
                                    <li class="list-group-item">
                                        <strong>Maintenance Date:</strong>
                                        <?php
                                        // Format the performed_date
                                        $performed_date = $maintenance_row['performed_date'];
                                        if (!empty($performed_date)) {
                                            $date = DateTime::createFromFormat('Y-m-d', $performed_date);
                                            echo $date ? $date->format('d F Y') : "Invalid date format";
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                        <br>
                                        <strong>Due Date:</strong> <?php
                                        // Format the due_date
                                        $due_date = $maintenance_row['due_date'];
                                        if (!empty($due_date)) {
                                            $date = DateTime::createFromFormat('Y-m-d', $due_date);
                                            echo $date ? $date->format('d F Y') : "Invalid date format";
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                    </li>
                                </ul>
                            <?php } else { ?>
                                <div class="alert alert-warning mt-3 mt-md-4 mb-0" role="alert">
                                    No maintenance records available.
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 shadow-lg rounded-3 mt-0 mt-md-4 signature-bg-color text-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold mb-0 pb-0">Repair</h5>
                                <div>
                                    <button class="btn btn-sm btn-info">
                                        <?php
                                        $subFolder = '05 - Service and Repairs Certificates';
                                        $encodedSubFolder = urlencode($subFolder);
                                        ?>
                                        <a href="../open-asset-folder.php?folder=<?php echo urlencode($asset_no) ?>&sub_folder=<?php echo $encodedSubFolder ?>"
                                            target="_blank">
                                            <i class="fa-solid fa-folder"></i>
                                        </a>
                                    </button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#assetDetailsHistoryModal" data-details-type="Repair"
                                        data-asset-id="<?php echo $row["asset_id"] ?>"><i
                                            class="fa-solid fa-clock-rotate-left fa-sm"></i></button>
                                    <button class="btn btn-sm btn-dark" data-bs-target="#addAssetDetailsModal"
                                        data-bs-toggle="modal" data-details-type="Repair"
                                        data-asset-id="<?php echo $row["asset_id"] ?>"><i
                                            class="fa-solid fa-plus fa-sm"></i></button>
                                </div>
                            </div>
                            <?php if ($asset_repair_result->num_rows > 0) { ?>
                                <ul class="list-group mt-3">
                                    <?php
                                    // Fetch the first repair record (latest)
                                    $repair_row = $asset_repair_result->fetch_assoc();
                                    ?>
                                    <li class="list-group-item">
                                        <strong>Repair Date:</strong>
                                        <?php
                                        // Format the performed_date
                                        $performed_date = $repair_row['performed_date'];
                                        if (!empty($performed_date)) {
                                            $date = DateTime::createFromFormat('Y-m-d', $performed_date);
                                            echo $date ? $date->format('d F Y') : "Invalid date format";
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                        <br>
                                        <strong>Due Date:</strong> <?php
                                        // Format the performed_date
                                        $due_date = $repair_row['due_date'];
                                        if (!empty($due_date)) {
                                            $date = DateTime::createFromFormat('Y-m-d', $due_date);
                                            echo $date ? $date->format('d F Y') : "Invalid date format";
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                    </li>
                                </ul>
                            <?php } else { ?>
                                <div class="alert alert-warning mt-3 mt-md-4 mb-0" role="alert">
                                    No repair records available.
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 shadow-lg rounded-3 mt-0 mt-md-4 signature-bg-color text-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold mb-0 pb-0">Calibration</h5>
                                <div>
                                    <button class="btn btn-sm btn-info">
                                        <?php
                                        $subFolder = '03 - Calibration Certificates';
                                        $encodedSubFolder = urlencode($subFolder);
                                        ?>
                                        <a href="../open-asset-folder.php?folder=<?php echo urlencode($asset_no) ?>&sub_folder=<?php echo $encodedSubFolder ?>"
                                            target="_blank">
                                            <i class="fa-solid fa-folder"></i>
                                        </a>
                                    </button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#assetDetailsHistoryModal" data-details-type="Calibration"
                                        data-asset-id="<?php echo $row["asset_id"] ?>"><i
                                            class="fa-solid fa-clock-rotate-left fa-sm"></i></button>
                                    <button class="btn btn-sm btn-dark" data-bs-target="#addAssetDetailsModal"
                                        data-bs-toggle="modal" data-details-type="Calibration"
                                        data-asset-id="<?php echo $row["asset_id"] ?>"><i
                                            class="fa-solid fa-plus fa-sm"></i></button>
                                </div>
                            </div>
                            <?php if ($asset_calibration_result->num_rows > 0) { ?>
                                <ul class="list-group mt-3">
                                    <?php
                                    // Fetch the first calibration record (latest)
                                    $calibration_row = $asset_calibration_result->fetch_assoc();
                                    ?>
                                    <li class="list-group-item">
                                        <strong>Calibration Date:</strong>
                                        <?php
                                        // Format the performed_date
                                        $performed_date = $calibration_row['performed_date'];
                                        if (!empty($performed_date)) {
                                            $date = DateTime::createFromFormat('Y-m-d', $performed_date);
                                            echo $date ? $date->format('d F Y') : "Invalid date format";
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                        <br>
                                        <strong>Due Date:</strong> <?php
                                        // Format the performed_date
                                        $due_date = $calibration_row['due_date'];
                                        if (!empty($due_date)) {
                                            $date = DateTime::createFromFormat('Y-m-d', $due_date);
                                            echo $date ? $date->format('d F Y') : "Invalid date format";
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                    </li>
                                </ul>
                            <?php } else { ?>
                                <div class="alert alert-warning mt-3 mt-md-4 mb-0 mb-0" role="alert">
                                    No calibration records available.
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 shadow-lg rounded-3 mt-0 mt-md-4 signature-bg-color text-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold mb-0 pb-0">Warranty</h5>
                                <button class="btn btn-sm btn-dark" data-bs-toggle="modal"
                                    data-bs-target="#addAssetDetailsModal" data-details-type="Warranty"
                                    data-asset-id="<?php echo htmlspecialchars($row["asset_id"]); ?>">
                                    <i class="fa-solid fa-plus fa-sm"></i>
                                </button>
                            </div>

                            <?php
                            $directoryPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/02 - Warranty";

                            // Check if the directory exists
                            if (is_dir($directoryPath)) {
                                $files = scandir($directoryPath); // Get all files and directories in the specified folder
                                $files = array_diff($files, ['.', '..']); // Remove '.' and '..' entries
                    
                                if (count($files) > 0) {
                                    // Display the list of files
                                    echo '<div class="bg-white p-3 rounded-3 mt-4">';
                                    foreach ($files as $file) {
                                        $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                                        // Determine the icon based on file extension
                                        if ($fileExtension === 'pdf') {
                                            $icon = 'fa-file-pdf text-danger';
                                        } elseif ($fileExtension === 'doc' || $fileExtension === 'docx') {
                                            $icon = 'fa-file-word text-primary';
                                        } elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                            $icon = 'fa-file-image text-success';
                                        } else {
                                            $icon = 'fa-file';
                                        }

                                        // Output the file name with the icon
                                        echo '<a class="text-decoration-none" href="../open-asset-details-file.php?asset_no=' . urlencode($asset_no) . '&folder=02 - Warranty&file=' . urlencode($file) . '">';
                                        echo '<i class="fa-solid ' . $icon . '"></i> ' . htmlspecialchars($file) . '</a> </br>';
                                    }
                                    echo '</div>';
                                } else {
                                    // If no files exist
                                    echo '<div class="alert alert-warning mt-3 mt-md-4 mb-0" role="alert">No warranty records available.</div>';
                                }
                            } else {
                                // If directory does not exist
                                echo '<div class="alert alert-danger mt-3 mt-md-4 mb-0" role="alert">The warranty directory does not exist.</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 shadow-lg rounded-3 mt-0 mt-md-4 signature-bg-color text-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold mb-0 pb-0">Manual</h5>
                                <button class="btn btn-sm btn-dark" data-bs-toggle="modal"
                                    data-bs-target="#addAssetDetailsModal" data-details-type="Manual"
                                    data-asset-id="<?php echo htmlspecialchars($row["asset_id"]); ?>">
                                    <i class="fa-solid fa-plus fa-sm"></i>
                                </button>
                            </div>

                            <?php
                            $directoryPath = "D:/FSMBEH-Data/00 - QA/04 - Assets/$asset_no/01 - Manual";

                            // Check if the directory exists
                            if (is_dir($directoryPath)) {
                                $files = scandir($directoryPath); // Get all files and directories in the specified folder
                                $files = array_diff($files, ['.', '..']); // Remove '.' and '..' entries
                    
                                if (count($files) > 0) {
                                    // Display the list of files
                                    echo '<div class="bg-white p-3 rounded-3 mt-4">';
                                    foreach ($files as $file) {
                                        $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                                        // Determine the icon based on file extension
                                        if ($fileExtension === 'pdf') {
                                            $icon = 'fa-file-pdf text-danger';
                                        } elseif ($fileExtension === 'doc' || $fileExtension === 'docx') {
                                            $icon = 'fa-file-word text-primary';
                                        } elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                            $icon = 'fa-file-image text-success';
                                        } else {
                                            $icon = 'fa-file';
                                        }

                                        // Output the file name with the icon
                                        echo '<a class="text-decoration-none" href="../open-asset-details-file.php?asset_no=' . urlencode($asset_no) . '&folder=01 - Manual&file=' . urlencode($file) . '">';
                                        echo '<i class="fa-solid ' . $icon . '"></i> ' . htmlspecialchars($file) . '</a> </br>';
                                    }
                                    echo '</div>';
                                } else {
                                    // If no files exist
                                    echo '<div class="alert alert-warning mt-3 mt-md-4 mb-0" role="alert">No manual records available.</div>';
                                }
                            } else {
                                // If directory does not exist
                                echo '<div class="alert alert-danger mt-3 mt-md-4 mb-0" role="alert">The manual directory does not exist.</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 shadow-lg rounded-3 mt-0 mt-md-4 signature-bg-color text-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold mt-1 mb-1 pb-0">Test & Tag</h5>
                            </div>

                            <?php if ($asset_cable_result->num_rows > 0) { ?>
                                <div class="rounded-3 mb-0 mt-3 mt-md-4" style="overflow-y: hidden;">
                                    <table class="table table-bordered table-hover mb-0 pb-0">
                                        <thead>
                                            <tr class="custom-table">
                                                <th class="py-2 align-middle text-center">Cable No.</th>
                                                <th class="py-2 align-middle text-center">Test Frequency</th>
                                                <th class="py-2 align-middle text-center">Next Test Due</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $asset_cable_result->fetch_assoc()) {
                                                // Format the next_test_due if it's not null or empty
                                                $next_test_due = $row['next_test_due'] ? date('d F Y', strtotime($row['next_test_due'])) : null;
                                                // Check if next_test_due is null or empty
                                                $style = $next_test_due ? '' : "style='background: repeating-linear-gradient(45deg, #c8c8c8, #c8c8c8 10px, #b3b3b3 10px, #b3b3b3 20px); color: white; font-weight: bold'";
                                                ?>
                                                <tr>
                                                    <td class='align-middle text-center py-2'>
                                                        <a href='cable-table.php?search=<?= urlencode($row['cable_no']) ?>'
                                                            target='_blank'>
                                                            <?= htmlspecialchars($row['cable_no']) ?>
                                                        </a>
                                                    </td>
                                                    <td class='align-middle text-center py-2'>
                                                        <?= htmlspecialchars($row['test_frequency'] === '60' ? '5 Years' : $row['test_frequency'] . ' Months') ?>
                                                    </td>
                                                    <td class='align-middle text-center py-2' <?= $style ?>>
                                                        <?= $next_test_due ? htmlspecialchars($next_test_due) : 'N/A' ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="alert alert-warning mt-3 mt-md-4 mb-0" role="alert">
                                    No test & tag records found for this asset.
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <!-- ======================== A D D  P H O T O  M O D A L ======================== -->
    <div class="modal fade" id="addPhotoModal" tabindex="-1" aria-labelledby="addPhotoModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="photoUploadForm" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="photos" class="form-label fw-bold">Choose Photos</label>
                            <input type="file" class="form-control" id="photos" name="photos[]" accept="image/*"
                                multiple>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn signature-btn">Upload Photo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================== A D D  A S S E T S  D E T A I L S  M O D A L ======================== -->
    <div class="modal fade" id="addAssetDetailsModal" tabindex="-1" aria-labelledby="addAssetDetailsModal"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add <span class="detailsTypeText"> </span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="assetIdToAdd" id="assetIdToAdd">
                        <input type="hidden" name="categoriesToAdd" id="categoriesToAdd">

                        <div class="row">
                            <div class="form-group col-md-6 mt-md-0 mt-3">
                                <label for="performedDate" class="fw-bold">Date of <span
                                        class="detailsTypeText"></span></label>
                                <input type="date" name="performedDate" id="performedDate" class="form-control"
                                    value="<?php echo $currentDate; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide the <span class="detailsTypeText"></span>.
                                </div>
                            </div>
                            <div class="form-group col-md-6 mt-md-0 mt-3">
                                <label for="dueDate" class="fw-bold">Due Date</label>
                                <input type="date" name="dueDate" id="dueDate" class="form-control" required>
                                <div class="invalid-feedback">
                                    Please provide the due date.
                                </div>
                                <div class="mb-0 pb-0">
                                    <button class="badge rounded-pill btn btn-sm btn-outline-dark" type="button"
                                        id="addSixMonthBtn">+ 6
                                        Months</button>
                                    <button class="badge rounded-pill btn btn-sm btn-outline-dark" type="button"
                                        id="addOneYearBtn">+ 1
                                        year</button>
                                    <button class="badge rounded-pill btn btn-sm btn-outline-dark" type="button"
                                        id="addFiveYearBtn">+ 5
                                        years</button>
                                </div>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="source" class="fw-bold">Source</label>
                                <select name="source" class="form-select">
                                    <option value="Internal">Internal</option>
                                    <option value="External">External</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="description" class="fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="1"></textarea>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="assetDetailsFiles" class="fw-bold">Choose Files</label>
                                <input type="file" class="form-control" id="assetDetailsFiles"
                                    name="assetDetailsFiles[]" multiple>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            <button class="btn btn-dark">Add <span class="detailsTypeText"></span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ======================== A S S E T S  D E T A I L S  H I S T O R Y  M O D A L ======================== -->
    <div class="modal fade" id="assetDetailsHistoryModal" tabindex="-1" aria-labelledby="assetDetailsHistoryModal"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="historyDetailsTypeText"></span> History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="rounded-3 mb-0" style="overflow-y: hidden;">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th class="align-middle text-center">Action</th>
                                    <th class="align-middle text-center">Performed Date</th>
                                    <th class='align-middle text-center due-date-column' id="dueDateHeader"
                                        width='200px'>Due Date</th>
                                    <th class="align-middle text-center">Source</th>
                                    <th class="align-middle text-center">Description</th>
                                </tr>
                            </thead>
                            <tbody id="detailsHistoryTableBody">
                                <!-- AJAX Fetched Data Here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================  Modal for Displaying Larger Image ======================== -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Asset Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="modalImage" src="" alt="Asset Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deleteButtons = document.querySelectorAll('.delete-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const fileName = this.dataset.file;
                    const container = this.closest('.photo-container');

                    if (confirm('Are you sure you want to delete this photo?')) {
                        // Send the request to the same PHP file to delete the image
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('file', fileName);

                        fetch(window.location.href, { // Send request to the same page
                            method: 'POST',
                            body: formData,
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    container.remove(); // Remove photo from UI
                                    alert('Photo deleted successfully.');
                                } else {
                                    alert('Failed to delete photo.');
                                }
                            })
                            .catch(err => {
                                console.error('Error:', err);
                                alert('Error deleting photo.');
                            });
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('addAssetDetailsModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the Modal

                var detailsTypeText = button.getAttribute('data-details-type');
                var assetId = button.getAttribute('data-asset-id');

                // Set the details type text
                var modalDetailsTypeElements = myModalEl.querySelectorAll('.detailsTypeText');
                var modalAssetId = myModalEl.querySelector('#assetIdToAdd');
                var modalCategoriesToAdd = myModalEl.querySelector('#categoriesToAdd');

                // Update text content for all elements with the 'detailsTypeText' class
                modalDetailsTypeElements.forEach(function (element) {
                    element.textContent = detailsTypeText;
                });

                // Set the asset ID
                modalAssetId.value = assetId;
                modalCategoriesToAdd.value = detailsTypeText;

                // Handle "Warranty" specific conditions
                if (detailsTypeText === "Warranty" || detailsTypeText === "Manual") {
                    // Hide all elements except the Choose Files field
                    var fieldsToHide = myModalEl.querySelectorAll('.form-group');
                    fieldsToHide.forEach(function (field) {
                        if (!field.querySelector('[id="assetDetailsFiles"]')) {
                            field.style.display = 'none'; // Hide the field
                        }
                    });

                    // Make Choose Files required
                    var chooseFileInput = myModalEl.querySelector('#assetDetailsFiles');
                    chooseFileInput.setAttribute('required', 'required');

                    // Also hide the Due Date field for Warranty
                    var dueDateField = myModalEl.querySelector('[name="dueDate"]').closest('.form-group');
                    dueDateField.style.display = 'none'; // Hide the Due Date field
                    var dueDateInput = myModalEl.querySelector('[name="dueDate"]');
                    dueDateInput.removeAttribute('required'); // Remove required from Due Date

                    // Make the "Choose Files" field full width (col-md-12)
                    var chooseFilesField = myModalEl.querySelector('[id="assetDetailsFiles"]').closest('.form-group');
                    chooseFilesField.classList.add('col-md-12'); // Add col-md-12 class for full width
                } else if (detailsTypeText === "Repair") {
                    // Handle "Repair" specific conditions
                    // Hide Due Date field
                    var dueDateField = myModalEl.querySelector('[name="dueDate"]').closest('.form-group');
                    dueDateField.style.display = 'none'; // Hide the Due Date field

                    // Make the Due Date field not required
                    var dueDateInput = myModalEl.querySelector('[name="dueDate"]');
                    dueDateInput.removeAttribute('required');

                    // Adjust the Source field margin classes
                    var sourceField = myModalEl.querySelector('[name="source"]').closest('.form-group');
                    sourceField.classList.add('mt-3');  // Remove mt-3
                    sourceField.classList.remove('mt-md-0', 'mt-3'); // Add mt-md-0 and mt-3
                } else {
                    // For other details types (not Warranty or Repair), show everything
                    var fieldsToShow = myModalEl.querySelectorAll('.form-group');
                    fieldsToShow.forEach(function (field) {
                        field.style.display = ''; // Ensure the field is visible
                    });

                    // Remove the "required" attribute from Choose Files
                    var chooseFileInput = myModalEl.querySelector('#assetDetailsFiles');
                    chooseFileInput.removeAttribute('required');

                    // Ensure Due Date field is visible and required
                    var dueDateField = myModalEl.querySelector('[name="dueDate"]').closest('.form-group');
                    dueDateField.style.display = ''; // Show Due Date field
                    var dueDateInput = myModalEl.querySelector('[name="dueDate"]');
                    dueDateInput.setAttribute('required', 'required'); // Add required to Due Date

                    // Reset "Choose Files" field to default col-md-6 if not warranty
                    var chooseFilesField = myModalEl.querySelector('[id="assetDetailsFiles"]').closest('.form-group');
                    chooseFilesField.classList.remove('col-md-12'); // Remove col-md-12 class
                    chooseFilesField.classList.add('col-md-6'); // Set back to default col-md-6

                }
            });
        });

    </script>

    <script>
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-AU', options);
        }

        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('assetDetailsHistoryModal');

            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the Modal
                var historyDetailsType = button.getAttribute('data-details-type'); // Get type
                var assetId = button.getAttribute('data-asset-id'); // Get asset ID

                // Set the details type text
                var modalDetailsTypeElements = myModalEl.querySelectorAll('.historyDetailsTypeText');
                modalDetailsTypeElements.forEach(function (element) {
                    element.textContent = historyDetailsType;
                });

                // Clear previous table data
                var tableBody = myModalEl.querySelector('#detailsHistoryTableBody');

                // Fetch data using AJAX
                fetch('../AJAXphp/fetch_asset_details.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        historyDetailsType: historyDetailsType,
                        assetId: assetId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        tableBody.innerHTML = ''; // Clear loading message

                        if (data.length === 0) {
                            tableBody.innerHTML = '<tr><td class="text-center" colspan="5">No records found</td></tr>';
                        } else {
                            data.forEach(item => {
                                // Add table rows with condition for the Due Date column
                                tableBody.innerHTML += `
                            <tr>
                                <td class='align-middle text-center' width='80px'>
                                    <button class="btn deleteBtn" data-asset-details-id="${item.asset_details_id}">
                                        <i class="fa-regular fa-trash-can text-danger"></i>
                                    </button>
                                </td>
                                <td class='align-middle text-center' width='200px'>${formatDate(item.performed_date)}</td>
                                ${historyDetailsType === 'Repair' ? '' : `<td class='align-middle text-center due-date-column' width='200px'>${formatDate(item.due_date)}</td>`}
                                <td class='align-middle text-center' width='100px'>${item.source}</td>
                                <td class='align-middle text-center due-date-column'>${item.description || "N/A"}</td>
                            </tr>`;
                            });

                            // Attach click event listener for delete buttons inside the modal
                            myModalEl.querySelectorAll('.deleteBtn').forEach(button => {
                                button.addEventListener('click', function () {
                                    // Get the asset_details_id from the clicked button
                                    const assetDetailsId = this.getAttribute('data-asset-details-id');
                                    console.log('Asset Details ID:', assetDetailsId);  // Log the asset ID

                                    // Confirm with the user before deleting
                                    if (confirm('Are you sure you want to delete this asset?')) {
                                        // Send a request to delete the asset from the server
                                        fetch('../AJAXphp/fetch_asset_details.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({ asset_details_id: assetDetailsId }), // Send the asset ID in the request body
                                        })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    // If the asset was successfully deleted, remove the button's row from the UI
                                                    this.closest('tr').remove(); // Assumes the button is inside a table row <tr>
                                                    alert('Asset deleted successfully!');
                                                } else {
                                                    alert('Error deleting asset.');
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error:', error);
                                                alert('Error deleting asset.');
                                            });
                                    }
                                });
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        tableBody.innerHTML = '<tr><td colspan="5">Error loading data</td></tr>';
                    });
            });
        });

    </script>


    <script>
        // Function to update due date
        function updateDueDate(timeToAdd) {
            // Get the performed date from the hidden input field
            var performedDate = document.getElementById('performedDate').value;

            // Convert the performed date to a Date object
            var date = new Date(performedDate);

            // Add time (months for 6 months, years for 1 year and 5 years)
            if (timeToAdd === '6months') {
                date.setMonth(date.getMonth() + 6);
            } else if (timeToAdd === '1year') {
                date.setFullYear(date.getFullYear() + 1);
            } else if (timeToAdd === '5years') {
                date.setFullYear(date.getFullYear() + 5);
            }

            // Format the new due date (YYYY-MM-DD)
            var dueDate = date.toISOString().split('T')[0];

            // Set the new due date in the due date input field
            document.getElementById('dueDate').value = dueDate;
        }

        // Add event listeners to each button
        document.getElementById('addSixMonthBtn').addEventListener('click', function () {
            updateDueDate('6months');
        });

        document.getElementById('addOneYearBtn').addEventListener('click', function () {
            updateDueDate('1year');
        });

        document.getElementById('addFiveYearBtn').addEventListener('click', function () {
            updateDueDate('5years');
        });
    </script>

    <script>
        // JavaScript to update the modal with the clicked image's source
        const images = document.querySelectorAll('.asset-photo');
        images.forEach(image => {
            image.addEventListener('click', function () {
                const imageUrl = this.getAttribute('data-bs-image');
                document.getElementById('modalImage').src = imageUrl;
            });
        });
    </script>
    <script>
        // Select all buttons with the class 'btn'
        const buttons = document.querySelectorAll('.deleteBtn');

        // Loop through each button and add an event listener
        buttons.forEach(button => {
            button.addEventListener('click', function () {
                // Log the value of the 'data-asset-details-id' attribute
                const assetDetailsId = this.getAttribute('data-asset-details-id');
                console.log('Asset Details ID:', assetDetailsId);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('assetDetailsHistoryModal');

            // When the modal is closed, reload the page with the current URL parameters
            myModalEl.addEventListener('hidden.bs.modal', function () {
                // Get the current URL and reload it with the current parameters
                window.location.href = window.location.href;
            });
        });

    </script>
</body>

</html>