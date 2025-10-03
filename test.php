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
$currentDate = date('Y-m-d');

$employees_sql = "SELECT employee_id, first_name, last_name, nickname, email FROM employees WHERE is_active = 1";
$employees_result = $conn->query($employees_sql);

$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

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
                            JOIN assets ON asset.asset_id = asset_details.asset_id";

if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_FILES['photos'])) {
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $photos = $_FILES['photos'];
    $messages = [];

    for ($i = 0; $i < count($photos['name']); $i++) {
        $fileName = basename($photos['name'][$i]);
        $targetFilePath = $uploadDir . $fileName;

        if ($photos['error'][$i] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($photos['tmp_name'][$i]);
            if (strpos($fileType, 'image/') === 0) {
                if (move_uploaded_file($photos['tmp_name'][$i], $targetFilePath)) {
                    $messages[] = "Uploaded";
                } else {
                    $messages[] = htmlspecialchars($fileName);
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['manualWorkInstructionDocument'])) {
    $assetNo = $_POST["assetNoManualWorkInstruction"];
    $manualWorkInstructionDocument = $_POST['manualWorkInstructionDocument'];

    $link_manual_work_instruction_sql = "UPDATE assets SET `manual` = ? WHERE asset_no = ?";
    $link_manual_work_instruction_result = $conn->prepare($delete_manual_work_instruction_sql);
    $link_manual_work_instruction_result->bind_param("ss", $manualWorkInstructionDocument, $asset_no);

    if ($link_manual_work_instruction_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        exit();
    } else {
        echo "Error updating record;";
    }
}

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['manualDocument'])) {
    $assetNo = $_POST["assetNoManual"];
    $manualDocument = $_POST["manualDocument"];

    $link_manual_sql = "UPDATE assets SET `handbook` = ? WHERE asset_no = ?";
    $link_annual_result = $conn->prepare($link_manual_sql);
    $link_manual_result->bind_param("ss", $manualDocument, $assetNo);

    if ($link_manual_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        exit();
    } else {
        $conn->error;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteManual'])) {
    $assetNo = $_GET['asset_no'];
    $null = null;

    $delete_manual_sql = "UPDATE assets SET `handbook` = ? WHERE asset_no = ?";
    $delete_manual_result = $conn->prepare($delete_manual_sql);
    $delete_manual_result->bind_param("ss", $null, $assetNo);

    if ($delete_manual_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['PHP_SELF'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        echo "<script> </script>";
        exit();
    } else {
        echo $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['assetIdToAddict'])) {
    $assetIdToAddIct = $_POST['assetIdToAddIct'];
    $operatingSystemDetails = isset($_POST['operatingSystemDetails']);
    $softwareDetails = isset($_POST['softwareDetails']);
    $hardwareDetails = isset($hardwareDetails);

    $update_ict_details_sql = "UPDATE assets SET `operating_system` = ?, `software` = ?, `hardware` = ?";
    $update_ict_details_result = $conn->prepare($update_ict_details_sql);
    $update_ict_details_result->bind_param("sssi", $operatingSystemDetails);

    if ($update_ict_details_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        } else {
            echo $conn->error;
        }
    }
}

if (isset($_GET['file'])) {
    $file = basename($_GET['file']);

    if (file_exists($filePath) && strtolower(pathinfo($filePath))){};
}
?>

<body class="background-color">
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb"></ol>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="addPhotoModal" tabindex="-1" aria-labelledby="addPhotoModal" aria-hidde="top">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <form id="photoUploadForm" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="photos" class="form-label fw-bold"></label>
                            <input type="file" class="form-control" id="photos" name="photos[]" multiple>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn signature-btn"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAssetDetailsModal" tabindex="-1" aria-hidden="true">
        
    </div>
</body>