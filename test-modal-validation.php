<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

// Database connection
require_once 'db_connect.php';
require_once 'email_sender.php';

$emailSender = new emailSender();


// Send the email to the capa_owner
$emailSender->sendEmail(
    'jovin.hampton@smbeharwal.fujielectric.com', // Recipient email
    'Test Name', // Recipient name
    'CAPA Reminder: 30 Days Left', // Subject
    "Reminder: The CAPA document with ID  has 30 days left before its target close date." // Body
);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once("./../db_connect.php");

$config = include('./../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get user's role from login session
$employeeId = $_SESSION['employee_id'];

// SQL query to retrieve all visa status
$visa_sql = "SELECT * FROM visa";
$visa_result = $conn->query($visa_sql);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newVisa'])) {
    $newVisa = $_POST['newVisa'];

    // Check if the visa name already exists
    $check_visa_sql = "SELECT COUNT(*) FROM visa WHERE visa_name = ?";
    $check_visa_result = $conn->prepare($check_visa_sql);
    $check_visa_result->bind_param("s", $newVisa);
    $check_visa_result->execute();
    $check_visa_result->bind_result($visaCount);
    $check_visa_result->fetch();
    $check_visa_result->close();

    if ($visaCount > 0) {
        // Visa already exists
        echo "<script> alert('Visa already exist.')</script>";
    } else {
        // Add new visa 
        $add_visa_sql = "INSERT INTO visa (visa_name) VALUES (?)";
        $add_visa_result = $conn->prepare($add_visa_sql);
        $add_visa_result->bind_param("s", $newVisa);

        if ($add_visa_result->execute()) {
            echo '<script> window.location.replace("' . $_SERVER['PHP_SELF'] . '");</script>';
            exit();
        } else {
            echo "Error: " . $add_visa_sql . "<br>" . $conn->error;
        }
        $add_visa_result->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['visaIdToDelete'])) {
    $visaIdToDelete = $_POST['visaIdToDelete'];

    $delete_visa_sql = "DELETE FROM visa WHERE visa_id = ?";
    $delete_visa_result = $conn->prepare($delete_visa_sql);
    $delete_visa_result->bind_param("i", $visaIdToDelete);

    if ($delete_visa_result->execute()) {
        echo '<script>window.locationr.replace("' . $_SERVER['PHP_SELF'] . '"): </script>';
        exit();
    } else {
        echo "Error: " . $delete_visa_result . "<br>" . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['visaNameToEdit'])) {
    $visaNameToEdit = $_POST['visa'];

    $edit_visa_sql = "UPDATE visa SET visa_name = ? WHERE visa_id = ?";
    $edit_visa_result = $conn->prepare($edit_credential_sql);
    $edit_visa_result->bind_param("si", $visaNameToEdit, $visaIdToEdit);

    if ($edit_visa_result->execute()) {
        echo '<script>window.location.replace("' . $_SEVER['PHP_SELF'] . '");</script>';
        exit();
    } else {
        echo "Error; " . $edit_visa_result . "<br>" . $conn->error;
    }
}

?>

<!DOCTYPE html>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico">
    <style>
        .table thead th {
            background-color: : #043f9d;
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
                <li class="breadcrumb-item fw-bold signature-color">Manage Visa</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-start">
            <button class="btn btn-dark mb-2" data-bs-toggle="modal" data-bs-target="#addVisaModal"><i
                class="fa-solid fa-plus me-1"></i></button>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-hover mb-0 pb-0">

            </table>
        </div>
    </div>
</body>

</html>