<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once("../db_connect.php");
require_once("../status_check.php");
require_once("../system_role_check.php");

// Get employee_id from the URL
if (isset($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];
}

$folder_name = "Human Resources";
require_once("../group_role_check.php");

$get_payroll_type_sql = "SELECT payroll_type FROM employees WHERE employee_id = ?";
$get_payroll_type_stmt = $conn->prepare($get_payroll_type_sql);
$get_payroll_type_stmt->bind_param("s", $employeeId);
$get_payroll_type_stmt->execute();
$get_payroll_type_stmt->bind_result($employee_payroll_type);
$get_payroll_type_stmt->fetch();
$get_payroll_type_stmt->close();

// Get login employee id from SESSION
$loginEmployeeId = $_SESSION["employee_id"];

if ($role === "general" && $employee_payroll_type === "salary" && $employeeId != $loginEmployeeId) {
    echo "<script>
    window.location.href = 'http://$serverAddress/$projectName/access_restricted.php';
  </script>";
}

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];


// SQL to get login employee details
$login_employee_details_sql = "SELECT * FROM employees WHERE employee_id = $loginEmployeeId";
$login_employee_details_result = $conn->query($login_employee_details_sql);

if ($login_employee_details_result->num_rows > 0) {
    while ($row = $login_employee_details_result->fetch_assoc()) {
        $loginEmployeeFirstName = $row["first_name"];
        $loginEmployeeLastName = $row["last_name"];
    }
}

// Get all the visa status
$visa_status_sql = "SELECT * FROM visa";
$visa_status_result = $conn->query($visa_status_sql);

// Get all the position
$position_sql = "SELECT * FROM position";
$position_result = $conn->query($position_sql);

// Get all the department
$department_sql = "SELECT * FROM department";
$department_result = $conn->query($department_sql);

// SQL to get the employee details
$employee_details_sql = "SELECT employees.*, visa.visa_name, position.position_name, department.department_name
FROM employees
LEFT JOIN visa ON employees.visa = visa.visa_id
LEFT JOIN position ON employees.position = position.position_id
LEFT JOIN department ON employees.department = department.department_id
WHERE employees.employee_id = $employeeId";
$employee_details_result = $conn->query($employee_details_sql);

$employee_start_date_sql = "SELECT start_date FROM employees WHERE employee_id = $employeeId";
$employee_start_date_result = $conn->query($employee_start_date_sql);

$employee_group_access_sql = "SELECT DISTINCT 
                                `groups`.group_name, 
                                folders.folder_name, 
                                `groups`.group_id, 
                                folders.folder_id
                              FROM `groups`
                              JOIN groups_folders ON `groups`.group_id = groups_folders.group_id
                              JOIN folders ON folders.folder_id = groups_folders.folder_id
                              JOIN users_groups ON users_groups.group_id = `groups`.group_id
                              JOIN users ON users.user_id = users_groups.user_id
                              JOIN employees ON employees.employee_id = users.employee_id
                              WHERE employees.employee_id = $employeeId";

$employee_group_access_result = $conn->query($employee_group_access_sql);

// SQL to get the performance review data
$performance_review_sql = "SELECT * FROM performance_review WHERE reviewee_employee_id = $employeeId";
$performance_review_result = $conn->query($performance_review_sql);
if ($performance_review_result->num_rows > 0) {
    while ($row = $performance_review_result->fetch_assoc()) {
        $reviewType = $row["review_type"];
        $reviewerEmployeeId = $row["reviewer_employee_id"];
        $revieweeEmployeeId = $row["reviewee_employee_id"];
        $reviewNotes = $row["review_notes"];
        $reviewDate = $row["review_date"];
    }
}

// Get the start date for the chart
if ($employee_start_date_result->num_rows > 0) {
    while ($row = $employee_start_date_result->fetch_assoc()) {
        $startDateChart = $row['start_date'];
    }
}

//  ========================= E M P L O Y E E S ( W A G E )  =========================
// SQL to get the wages data
$employee_wages_sql = "SELECT * FROM wages WHERE employee_id = $employeeId ORDER BY date ASC";
// Fetch wages data
$employee_wages_result = $conn->query($employee_wages_sql);

$wagesData = array(); // Array to store wages data

if ($employee_wages_result->num_rows > 0) {
    while ($row = $employee_wages_result->fetch_assoc()) {
        $wagesData[] = $row; // Store each row in the array
    }
}

// Chart data points
$wagesDataPoints = array();

foreach ($wagesData as $row) {
    $wagesDataPoints[] = array("y" => floatval($row['amount']), "label" => date("j F Y", strtotime($row['date'])));
}

//  ========================= E M P L O Y E E S ( S A L A R Y )  =========================
// SQL to get the salaries data
$employee_salaries_sql = "SELECT * FROM salaries WHERE employee_id = $employeeId ORDER BY date ASC";
// Fetch salaries data
$employee_salaries_result = $conn->query($employee_salaries_sql);

$salariesData = array(); // Array to store salaries data

if ($employee_salaries_result->num_rows > 0) {
    while ($row = $employee_salaries_result->fetch_assoc()) {
        $salariesData[] = $row; // Store each row in the array
    }
}

// Chart data points
$salariesDataPoints = array();

foreach ($salariesData as $row) {
    $salariesDataPoints[] = array("y" => floatval($row['amount']), "label" => date("j F Y", strtotime($row['date'])));
}

// Get current date in the desired format
$currentDate = date("Y-m-d");

// ========================= A D D   N E W   W A G E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newWage'])) {
    $newWage = $_POST["newWage"];
    $updateWageDate = $_POST["updateWageDate"];

    $add_wages_sql = "INSERT INTO wages (amount, date, employee_id) VALUES (?, ?, ?)";
    $add_wages_stmt = $conn->prepare($add_wages_sql);
    $add_wages_stmt->bind_param("sss", $newWage, $updateWageDate, $employeeId);

    // Execute the prepared statement
    if ($add_wages_stmt->execute()) {
        echo "New wage record inserted successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $add_wages_stmt . "<br>" . $conn->error;
    }
    // Close statement 
    $add_wages_stmt->close();
}

// ========================= E D I T   W A G E  =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editDate']) && isset($_POST['editWage'])) {
    $editDate = $_POST["editDate"];
    $editWage = $_POST["editWage"];
    $wagesId = $_POST['wages_id'];

    echo $editDate . "  " . $editWage . "  " . $wagesId;

    $edit_wages_sql = "UPDATE wages SET amount = ?,  date = ? WHERE wages_id = ?";
    $edit_wages_stmt = $conn->prepare($edit_wages_sql);
    $edit_wages_stmt->bind_param("ssi", $editWage, $editDate, $wagesId);

    // Execute the prepared statement
    if ($edit_wages_stmt->execute()) {
        echo "Wages Edited";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $edit_wages_stmt . "<br>" . $conn->error;
    }

    // Close Statement
    $edit_wages_stmt->close();
}

// ========================= D E L E T E   W A G E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['wageIdToDelete'])) {
    $wagesId = $_POST['wageIdToDelete'];

    echo $wagesId;

    $delete_wages_sql = "DELETE FROM wages WHERE wages_id = ?";
    $delete_wages_stmt = $conn->prepare($delete_wages_sql);
    $delete_wages_stmt->bind_param("i", $wagesId);

    // Execute the prepared statement
    if ($delete_wages_stmt->execute()) {
        echo "Wages Deleted";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $delete_wages_sql . "<br>" . $conn->error;
    }

    // Close Statement
    $delete_wages_stmt->close();
}

// ========================= A D D  N E W  S A L A R Y =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newSalary'])) {
    $newSalary = $_POST["newSalary"];
    $updateSalaryDate = $_POST["updateSalaryDate"];

    $add_salary_sql = "INSERT INTO salaries (amount, date, employee_id) VALUES (?, ?, ?)";
    $add_salary_stmt = $conn->prepare($add_salary_sql);
    $add_salary_stmt->bind_param("iss", $newSalary, $updateSalaryDate, $employeeId);

    echo $newSalary;
    // Execute the prepared statement
    if ($add_salary_stmt->execute()) {
        echo "New salary record inserted successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $add_salary_stmt . "<br>" . $conn->error;
    }
    // Close statement 
    $add_salary_stmt->close();
}

// ========================= E D I T  S A L A R Y =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editSalaryDate']) && isset($_POST['editSalary'])) {
    $editDate = $_POST["editSalaryDate"];
    $editSalary = $_POST["editSalary"];
    $salaryId = $_POST['salary_id'];

    $edit_salary_sql = "UPDATE salaries SET amount = ?,  date = ? WHERE salary_id = ?";
    $edit_salary_stmt = $conn->prepare($edit_salary_sql);
    $edit_salary_stmt->bind_param("ssi", $editSalary, $editDate, $salaryId);

    // Execute the prepared statement
    if ($edit_salary_stmt->execute()) {
        echo "Salary Edited";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $edit_salary_stmt . "<br>" . $conn->error;
    }

    // Close Statement
    $edit_salary_stmt->close();
}

// ========================= D E L E T E   S A L A R Y  =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salaryIdToDelete'])) {
    $salaryId = $_POST['salaryIdToDelete'];

    echo $salaryId;

    $delete_salary_sql = "DELETE FROM salaries WHERE salary_id = ?";
    $delete_salary_stmt = $conn->prepare($delete_salary_sql);
    $delete_salary_stmt->bind_param("i", $salaryId);

    // Execute the prepared statement
    if ($delete_salary_stmt->execute()) {
        echo "Salary Deleted";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $delete_salary_sql . "<br>" . $conn->error;
    }

    // Close Statement
    $delete_salary_stmt->close();
}

// ========================= D E L E T E / C H A N G E  P R O F I L E  I M A G E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['deleteProfileImage'])) {
        // Handle profile image deletion
        $profileImageToDeleteEmpId = $_POST['profileImageToDeleteEmpId'];
        $delete_profile_image_sql = "UPDATE employees SET profile_image = NULL WHERE employee_id = ?";
        $delete_profile_image_stmt = $conn->prepare($delete_profile_image_sql);
        $delete_profile_image_stmt->bind_param("i", $profileImageToDeleteEmpId);

        if ($delete_profile_image_stmt->execute()) {
            echo "Profile image deleted successfully.";
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $profileImageToDeleteEmpId);
            exit();
        } else {
            echo "Error deleting profile image: " . $conn->error;
        }
        $delete_profile_image_stmt->close();
    } elseif (isset($_POST['changeProfileImage'])) {
        // Handle profile image change
        $profileImageToDeleteEmpId = $_POST['profileImageToDeleteEmpId'];
        $profileImage = $_FILES['profileImageToEdit'];

        // Process the uploaded file
        $imageExtension = pathinfo($profileImage["name"], PATHINFO_EXTENSION);
        $newFileName = $profileImageToDeleteEmpId . '_profile.' . $imageExtension;
        $imagePath = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\" . $employeeId . "\\02 - Resume, ID and Qualifications\\" . $newFileName;

        if (move_uploaded_file($profileImage["tmp_name"], $imagePath)) {
            // Encode the image before insertion
            $encodedImage = base64_encode(file_get_contents($imagePath));

            // Update database with new image
            $sql = "UPDATE employees SET profile_image = ? WHERE employee_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $encodedImage, $profileImageToDeleteEmpId);

            if ($stmt->execute()) {
                echo "Profile image changed successfully.";
                header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $profileImageToDeleteEmpId);
                exit();
            } else {
                echo "Error updating profile image: " . $conn->error;
            }
            $stmt->close();
        } else {
            echo "File upload failed.";
        }
    }
}

// ========================= A D D  P O L I C I E S =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['policiesToSubmit'])) {
    $policiesToSubmit = $_POST['policiesToSubmit'];

    $folderName = $employeeId . "_policies";

    $directory = "../Policies/";

    // Check if the directory exists or create it if not
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true); // Create directory recursively if it doesn't exist
    }

    // Check if the folder already exists
    if (is_dir($directory . $folderName)) {
        echo "Folder already exists.";
    } else {
        // Create new folder
        if (mkdir($directory . $folderName)) {
            echo "Folder created successfully.";
        } else {
            echo "Failed to create folder.";
        }
    }
}

// =========================  A C T I V A T E  E M P L O Y E E ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['employeeIdToActivate'])) {
    $employeeIdToActivate = $_POST['employeeIdToActivate'];

    $activate_employee_sql = "UPDATE employees SET is_active = 1 WHERE employee_id = ?";
    $activate_employee_result = $conn->prepare($activate_employee_sql);
    $activate_employee_result->bind_param("i", $employeeIdToActivate);

    // Execute the prepared statement
    if ($activate_employee_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '?employee_id= ' . $employeeId . '");</script>';
        exit();
    } else {
        $error_message = "Error: " . $activate_employee_result . "<br>" . $conn->error;
    }
}

//  ========================= D E A C T I V A T E  E M P L O Y E E ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["employeeIdToDeactivate"])) {
    $employeeIdToDeactivate = $_POST['employeeIdToDeactivate'];

    $deactivate_employee_sql = "UPDATE employees SET is_active = 0 WHERE employee_id = ?";
    $deactivate_employee_result = $conn->prepare($deactivate_employee_sql);
    $deactivate_employee_result->bind_param("i", $employeeIdToDeactivate);

    // Execute the prepared statement
    if ($deactivate_employee_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '?employee_id= ' . $employeeId . '");</script>';
        exit();
    } else {
        $error_message = "Error: " . $deactivate_employee_result . "<br>" . $conn->error;
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (1st Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdFirstMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdFirstMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (3rd Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdThirdMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdThirdMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (6th Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdSixthMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdSixthMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (9th Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdNinthMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdNinthMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (12th Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdTwelfthMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdTwelfthMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Employees</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />

    <style>
        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !;
        }

        /* Style for checked state */
        .btn-check:checked+.btn-custom {
            background-color: #043f9d !;
            border-color: #043f9d !;
            color: white !important;
        }

        /* Optional: Adjust hover state if needed */
        .btn-custom:hover {
            background-color: #032b6b;
            border-color: #032b6b;
            color: white;
        }

        /* Remove watermark */
        .canvasjs-chart-credit {
            display: none !important;
        }

        /* Folder icon change when hover */
        .folder-icon {
            position: relative;
            cursor: pointer;
        }

        .folder-icon:hover .fa-folder {
            display: none !important;
        }

        .folder-icon:hover .fa-folder-open {
            display: inline-block !important;
        }

        .btn-check:checked+.btn-custom {
            background-color: #043f9d !important;
            border-color: #043f9d !important;
            color: white !important;
        }

        .btn-custom:hover {
            background-color: #032b6b;
            border-color: #032b6b;
            color: white;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;

            }

            .col-lg-6,
            .col-xl-3 {
                width: 48%;
                /* Adjust columns to fit better in print */
            }

            .show-print {
                display: block;
                column-count: 2;
                column-gap: 20px;
            }

            .hide-print {
                display: none;
            }

            h5 {
                font-size: 0.8rem;
            }

            small {
                font-size: 0.6rem;
            }

            p {
                font-size: 0.8rem;
            }

            .mt-print {
                padding-top: 100px;
            }

            .print-name {
                font-size: 24px;
            }

            .print-position {
                font-size: 16px;
            }

            .no-shadow-print {
                box-shadow: none !important;
            }

            .mt-print-contact {
                padding-top: 5px !important;
                padding-bottom: 0px !important;
                margin-bottom: 0 !important;
            }

            .mt-print-bank {
                padding-top: 0px !important;
                margin-top: 0 !important;
                padding-bottom: 0px !important;
                margin-bottom: 0 !important;
            }

            .address-print {
                width: 100%;
            }

            #chartContainer,
            #chartContainer2 {
                height: 150px !important;
                /* Reduce the height for printing */
                width: 100% !important;
                /* Ensure it takes the full width */
                box-shadow: none;
            }

            /* You can also adjust margins, padding, and other styles for print */
            .payRaiseHistoryPrint {
                margin: 0;
                padding: 0px;
                box-shadow: none;
            }

            .card {
                page-break-inside: avoid;
            }

            @page {
                margin-bottom: 0;
                margin-left: 0;
                margin-right: 0;
            }
        }
    </style>

    <script>
        // Capture scroll position before page refresh or redirection
        window.addEventListener('beforeunload', function () {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });
    </script>
    <script>
        window.onload = function () {
            try {
                var chart = new CanvasJS.Chart("chartContainer", {
                    animationEnabled: true,
                    axisX: {
                        titleFontFamily: "Avenir",
                        titleFontSize: 14,
                        titleFontWeight: "bold",
                        titleFontColor: "#555",
                        labelFontFamily: "Avenir",
                        labelFontSize: 12,
                        labelFontColor: "#555"
                    },
                    data: [{
                        type: "line",
                        color: "#043f9d",
                        markerColor: "#043f9d",
                        markerSize: 8,
                        dataPoints: <?php echo json_encode($wagesDataPoints, JSON_NUMERIC_CHECK); ?>
                    }]
                });

                var chart2 = new CanvasJS.Chart("chartContainer2", {
                    animationEnabled: true,
                    axisX: {
                        titleFontFamily: "Avenir",
                        titleFontSize: 14,
                        titleFontWeight: "bold",
                        titleFontColor: "#555",
                        labelFontFamily: "Avenir",
                        labelFontSize: 12,
                        labelFontColor: "#555"
                    },
                    data: [{
                        type: "line",
                        color: "#043f9d",
                        markerColor: "#043f9d",
                        markerSize: 8,
                        dataPoints: <?php echo json_encode($salariesDataPoints, JSON_NUMERIC_CHECK); ?>
                    }]
                });

                chart.render();
                chart2.render();

                // Listen for the print event to adjust the chart size
                window.addEventListener("beforeprint", function () {
                    chart.options.height = chart.container.clientHeight * 0.5;  // Reduce height by 50% for printing
                    chart2.options.height = chart2.container.clientHeight * 0.5;
                    chart.options.width = chart.container.clientWidth * 0.5;
                    chart2.options.width = chart2.container.clientWidth * 0.5;
                    chart.render();
                    chart2.render();
                });

                // Reset the chart size after printing
                window.addEventListener("afterprint", function () {
                    chart.options.height = null;  // Restore responsive behavior
                    chart2.options.height = null;  // Reset to original responsive state
                    chart.options.width = null;
                    chart2.options.width = null;
                    chart.render();
                    chart2.render();
                });

            } catch (error) {
                console.error('An error occurred while rendering the charts:', error);
            }
        }
    </script>

</head>

<body class="background-color">
    <?php require_once("../Menu/DropdownNavMenu.php") ?>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <nav aria-label="breadcrumb" class="mb-3 hide-print">
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/hr-index.php">HR
                        Dashboard</a></li>
                <li class="breadcrumb-item active" style="color:#043f9d" aria-current="page">
                    <a
                        href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/employee-list-index.php">All
                        Employees</a>
                </li>
                <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">
                    <?php echo $_GET['employee_id']; ?>
                </li>
            </ol>
        </nav>
        <div class="row">
            <div class="col-lg-8 show-print">
                <?php
                if ($employee_details_result->num_rows > 0) {
                    while ($row = $employee_details_result->fetch_assoc()) {
                        $profileImage = $row['profile_image'];
                        $firstName = $row['first_name'];
                        $lastName = $row['last_name'];
                        $nickname = $row['nickname'];
                        $visaStatus = $row['visa'];
                        $visaName = $row['visa_name'];
                        $visaExpiryDate = $row['visa_expiry_date'];
                        $employeeId = $row['employee_id'];
                        $address = $row['address'];
                        $phoneNumber = $row['phone_number'];
                        $vehicleNumberPlate = $row['plate_number'];
                        $emergencyContactName = $row['emergency_contact_name'];
                        $emergencyContactRelationship = $row['emergency_contact_relationship'];
                        $emergencyContact = $row['emergency_contact_phone_number'];
                        $plateNumber = $row['plate_number'];
                        $gender = $row['gender'];
                        $dob = $row['dob'];
                        $startDate = $row['start_date'];
                        $employmentType = $row['employment_type'];
                        $department = isset($row['department']) ? $row['department'] : 'N/A';
                        $departmentName = isset($row['department_name']) ? $row['department_name'] : 'N/A';
                        $section = $row['section'];
                        $position = $row['position'];
                        $positionName = $row['position_name'];
                        $payrollType = $row['payroll_type'];
                        $email = $row['email'];
                        $isActive = $row['is_active'];
                        $bankBuildingSociety = $row['bank_building_society'];
                        $bsb = $row['bsb'];
                        $accountNumber = $row['account_number'];
                        $superannuationFundName = $row['superannuation_fund_name'];
                        $uniqueSuperannuationIdentifier = $row['unique_superannuation_identifier'];
                        $superannuationMemberNumber = $row['superannuation_member_number'];
                        $taxFileNumber = $row['tax_file_number'];
                        $higherEducationLoanProgramme = $row['higher_education_loan_programme'];
                        $financialSupplementDebt = $row['financial_supplement_debt'];
                    }
                    ?>
                    <div class="row g-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="d-flex align-items-center flex-wrap">
                                <?php if (!empty($profileImage)) { ?>
                                    <!-- Profile image -->
                                    <div class="bg-gradient shadow-lg rounded-circle me-3"
                                        style="width: 100px; height: 100px; overflow: hidden;">
                                        <a data-bs-toggle="modal" data-bs-target="#profileImageModal" style="cursor: pointer">
                                            <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                                class="profile-pic img-fluid rounded-circle"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </a>
                                        <!-- Profile Image Modal -->
                                        <div class="modal fade" id="profileImageModal" tabindex="-1"
                                            aria-labelledby="profileImageModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteConfirmationLabel">Profile Image</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="d-flex justify-content-center">
                                                            <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>"
                                                                alt="Profile Image" class="img-fluid"
                                                                style="width: 50vh; height: 50vh">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <!-- Initials -->
                                    <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center me-3"
                                        style="width: 100px; height: 100px;">
                                        <h3 class="p-0 m-0">
                                            <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                        </h3>
                                    </div>
                                <?php } ?>

                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-start">
                                        <h5 class="card-title fw-bold text-start">
                                            <div class="d-flex align-items-center">
                                                <div class="print-name">
                                                    <?php echo (isset($firstName) && isset($lastName)) ? $firstName . " " . $lastName : "N/A"; ?>
                                                </div>
                                                <div class="hide-print ms-2">
                                                    <?php if ($isActive == 0) {
                                                        echo '<small><span class="badge rounded-pill bg-danger mb-1">Inactive</span></small>';
                                                    } else if ($isActive == 1) {
                                                        echo '<small><span class="badge rounded-pill bg-success mb-1">Active</span></small>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </h5>
                                    </div>
                                    <small class="text-start print-position">
                                        <?php echo (isset($positionName)) ? $positionName : "N/A" . " - " . ((isset($employeeId)) ? $employeeId : "N/A"); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="hide-print">
                                <div class="d-flex flex-sm-row align-items-center mt-4 mt-sm-0">
                                    <button class="btn btn-secondary me-2" onclick="window.print()">
                                        <i class="fa-solid fa-print"></i>
                                    </button>

                                    <?php if ($role === "admin") { ?>
                                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#editProfileModal"
                                            id="editProfileBtn">
                                            Edit Profile <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 mt-4 bg-white rounded shadow-lg no-shadow-print">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Personal Information</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>First Name</small>
                                    <h5 class="fw-bold"><?php echo (isset($firstName) ? $firstName : "N/A") ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Last Name</small>
                                    <h5 class="fw-bold"><?php echo (isset($lastName) ? $lastName : "N/A") ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Nickname</small>
                                    <h5 class="fw-bold">
                                        <?php echo ($nickname !== null && $nickname !== "" ? $nickname : "N/A"); ?>
                                    </h5>
                                </div>

                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Gender</small>
                                    <h5 class="fw-bold"><?php echo (isset($gender) ? $gender : "N/A") ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Date of Birth</small>
                                    <h5 class="fw-bold"><?php echo isset($dob) ? date("j F Y", strtotime($dob)) : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Visa Status</small>
                                    <h5 class="fw-bold"><?php echo isset($visaName) ? $visaName : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Visa Expiry Date</small>
                                    <?php
                                    if ($visaExpiryDate != null) {
                                        // Set the timezone to Sydney
                                        $timezone = new DateTimeZone('Australia/Sydney');

                                        // Create DateTime objects with the Sydney timezone
                                        $today = new DateTime('now', $timezone);
                                        $today->setTime(0, 0, 0);

                                        $expiryDate = new DateTime($visaExpiryDate, $timezone);
                                        $expiryDate->setTime(0, 0, 0);

                                        // Calculate the difference in days between today and the visa expiry date
                                        $interval = $today->diff($expiryDate);
                                        $daysDifference = $interval->format('%r%a');

                                        // Function to determine singular or plural "day"
                                        function dayText($days)
                                        {
                                            return abs($days) == 1 ? 'day' : 'days';
                                        }

                                        $visaExpiryDate = isset($visaExpiryDate) ? $visaExpiryDate : "N/A";

                                        // Check if the expiry date is less than 30 days from today
                                        if ($daysDifference == 0) {
                                            echo '<h5 class="fw-bold text-danger">' . $visaExpiryDate . '<i class="fa-solid fa-circle-exclamation fa-shake ms-1 tooltips" data-bs-toggle="tooltip" 
                                            data-bs-placement="top" title="Visa expired today"></i> </h5>';
                                        } else if ($daysDifference < 30 && $daysDifference >= 0) {
                                            echo '<h5 class="fw-bold text-danger">' . $visaExpiryDate . '<i class="fa-solid fa-circle-exclamation fa-shake ms-1 tooltips" data-bs-toggle="tooltip" 
                                            data-bs-placement="top" title="Visa expires in ' . $daysDifference . ' ' . dayText($daysDifference) . '"></i> </h5>';
                                        } else if ($daysDifference < 0) {
                                            echo '<h5 class="fw-bold text-danger">' . $visaExpiryDate . '<i class="fa-solid fa-circle-exclamation fa-shake ms-1 tooltips" data-bs-toggle="tooltip" 
                                            data-bs-placement="top" title="Visa expired ' . abs($daysDifference) . ' ' . dayText($daysDifference) . ' ago"></i> </h5>';
                                        } else {
                                            echo '<h5 class="fw-bold">' . $visaExpiryDate . '</h5>';
                                        }
                                    } else {
                                        echo '<h5 class="fw-bold"> N/A</h5>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 mt-4 bg-white rounded shadow-lg no-shadow-print mt-print-contact">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Contacts</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-12 d-flex flex-column address-print">
                                    <small>Address</small>
                                    <h5 class="fw-bold">
                                        <?php echo (isset($address) && $address !== "" ? $address : "N/A"); ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-4 d-flex flex-column">
                                    <small>Email</small>
                                    <h5 class="fw-bold text-break"><?php echo isset($email) ? $email : "N/A"; ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Phone Number</small>
                                    <h5 class="fw-bold"><?php echo isset($phoneNumber) ? $phoneNumber : "N/A"; ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Plate Number</small>
                                    <h5 class="fw-bold"><?php echo isset($plateNumber) ? $plateNumber : "N/A"; ?>
                                    </h5>
                                </div>
                            </div>

                            <p class="fw-bold signature-color mt-4">Emergency Contact</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Emergency Contact Name</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($emergencyContactName) ? $emergencyContactName : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Emergency Contact Relationship</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($emergencyContactRelationship) ? $emergencyContactRelationship : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Emergency Contact</small>
                                    <h5 class="fw-bold"><?php echo isset($emergencyContact) ? $emergencyContact : "N/A"; ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-print">
                        <div class="p-3 mt-4 bg-white rounded shadow-lg no-shadow-print">
                            <div class="p-3">
                                <p class="fw-bold signature-color">Employment Details</p>
                                <div class="row">
                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small> Employee Id </small>
                                        <h5 class="fw-bold"><?php echo isset($employeeId) ? $employeeId : "N/A"; ?></h5>
                                    </div>
                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small>Date Hired</small>
                                        <h5 class="fw-bold">
                                            <?php echo isset($startDate) ? date("j F Y", strtotime($startDate)) : "N/A"; ?>
                                        </h5>
                                    </div>
                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small>Time with Company</small>
                                        <?php
                                        if (isset($startDate)) {
                                            $startDateObj = new DateTime($startDate);
                                            $currentDateObj = new DateTime();
                                            $interval = $startDateObj->diff($currentDateObj);
                                            echo "<h5 class='fw-bold'> $interval->y  years, $interval->m  months, $interval->d  days </h5>";
                                        } else {
                                            echo "<h5 class='fw-bold'>N/A</h5>";
                                        }
                                        ?>
                                    </div>
                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small>Department</small>
                                        <h5 class="fw-bold"><?php echo isset($departmentName) ? $departmentName : "N/A"; ?>
                                        </h5>
                                    </div>
                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small>Section</small>
                                        <h5 class="fw-bold"><?php echo isset($section) ? $section : "N/A"; ?></h5>
                                    </div>
                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small>Employment Type</small>
                                        <h5 class="fw-bold"><?php echo isset($employmentType) ? $employmentType : "N/A"; ?>
                                        </h5>
                                    </div>

                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small>Position</small>
                                        <h5 class="fw-bold"><?php echo isset($positionName) ? $positionName : "N/A"; ?></h5>
                                    </div>

                                    <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                        <small>Payroll Type</small>
                                        <h5 class="fw-bold">
                                            <?php echo isset($payrollType) ? ucwords($payrollType) : "N/A"; ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 mt-4 bg-white rounded shadow-lg no-shadow-print mt-print-bank">
                        <div class="p-3">
                            <p class="fw-bold signature-color ">Banking, Super and Tax Details</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small> Banking/Building Society </small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($bankBuildingSociety) ? $bankBuildingSociety : "N/A" ?></h>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>BSB</small>
                                    <h5 class="fw-bold"><?php echo isset($bsb) ? $bsb : "N/A" ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Account Number</small>
                                    <h5 class="fw-bold"><?php echo isset($accountNumber) ? $accountNumber : "N/A" ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Unique Superannuation Identifier</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($uniqueSuperannuationIdentifier) ? $uniqueSuperannuationIdentifier : "N/A" ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Superannuation Fund Name</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($superannuationFundName) ? $superannuationFundName : "N/A" ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Superannuation Member Number</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($superannuationMemberNumber) ? $superannuationMemberNumber : "N/A" ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Tax File Number</small>
                                    <h5 class="fw-bold"><?php echo isset($taxFileNumber) ? $taxFileNumber : "N/A" ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Higher Education Loan Programme</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($higherEducationLoanProgramme) ? ($higherEducationLoanProgramme == 1 ? "Yes" : "No") : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Financial Supplement Debt</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($financialSupplementDebt) ? ($financialSupplementDebt == 1 ? "Yes" : "No") : "N/A" ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-lg-4">
                    <div class="card bg-white border-0 rounded shadow-lg mt-4 mt-lg-0">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Files</p>
                            <!-- 00 - Employee Documents -->
                            <div class="d-flex justify-content-center">
                                <div class="row col-12 p-2 background-color rounded shadow-sm">
                                    <div class="col-auto d-flex align-items-center">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=00 - Employee Documents"
                                                        target="_blank"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                            class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                    </a>
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                                <div class="d-flex justify-content-start">
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=00 - Employee Documents"
                                                        target="_blank"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                        00 - Employee Documents
                                                    </a>
                                                </div>
                                                <span>
                                                    <div class="d-flex align-items-center">
                                                        <small id="pay-review-directory-path" class="me-1 text-break"
                                                            style="color:#b1b1b1"><?php echo "$employeeId\00 - Employee Documents" ?></small>
                                                        <input type="hidden"
                                                            value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\00 - Employee Documents"; ?>">
                                                        <button id="copy-button" class="btn rounded btn-sm"
                                                            onclick="copyDirectoryPath(this)"><i
                                                                class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                            <small class="text-primary">Copy</small>
                                                        </button>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($employee_payroll_type === "wage") { ?>
                                <!-- 01 - Induction and Training Documents-->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <div class="col-auto d-flex align-items-center">
                                                <span class="folder-icon tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Open Folder">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=01 - Induction and Training Documents"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                                class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                        </a>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=01 - Induction and Training Documents"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            01 - Induction and Training Documents
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="pay-review-directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\01 - Induction and Training Documents" ?></small>
                                                            <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\01 - Induction and Training Documents" ?>">
                                                            <button id="copy-button" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- 02 - Resume, ID, and Qualifications -->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <form method="POST">
                                                        <input type="hidden" name="annualLeaveFolder">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=02 - Resume, ID and Qualifications"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                                class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                        </a>
                                                    </form>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=02 - Resume, ID and Qualifications"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            02 - Resume, ID and Qualifications
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="annual-leaves-directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\02 - Resume, ID and Qualifications" ?></small>
                                                            <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\02 - Resume, ID and Qualifications" ?>">
                                                            <button id="copy-button-annual" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- 03 - Accounts -->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <form method="POST">
                                                        <input type="hidden" name="annualLeaveFolder">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=03 - Accounts"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                                class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                        </a>
                                                    </form>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=03 - Accounts"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            03 - Accounts
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="annual-leaves-directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\03 - Accounts" ?></small>
                                                            <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\03 - Accounts" ?>">
                                                            <button id="copy-button-annual" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- 04 - Leave -->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=04 - Leave"
                                                        target="_blank"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                            class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                    </a>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=04 - Leave"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            04 - Leave
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\04 - Leave" ?></small>
                                                            <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\04 - Leave" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- 05 - HR Actions -->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=05 - HR Actions"
                                                        target="_blank"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                            class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                    </a>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=05 - HR Actions"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            05 - HR Actions
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\05 - HR Actions" ?></small>
                                                            <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\05 - HR Actions" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- 06 - Work Compensation -->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=06 - Work Compensation"
                                                        target="_blank"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                            class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                    </a>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=06 - Work Compensation"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            06 - Work Compensation
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\06 - Work Compensation" ?></small>
                                                            <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\06 - Work Compensation" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- 07 - Exit Information -->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=07 - Exit Information"
                                                        target="_blank"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                    </a>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=07 - Exit Information"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            07 - Exit Information
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\07 - Exit Information" ?></small>
                                                            <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\07 - Exit Information" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div
                        class="card bg-white border-0 rounded shadow-lg mt-4 payRaiseHistoryPrint <?php echo ($payrollType === "wage") ? 'd-block' : 'd-none'; ?>">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="fw-bold signature-color mb-0 d-flex align-items-center">
                                    Pay Raise History
                                    <span class="badge rounded-pill signature-btn mx-1">Wage</span>
                                    <span class="mx-2">|</span>
                                    <i class="fa-solid fa-eye text-danger me-1 showWagePayRaiseHistoryChartBtn"
                                        role="button"></i>
                                    <small class="pe-2 fw-bold text-decoration-underline showWagePayRaiseHistoryChartBtn">
                                        <a role="button" id="hideWagePayRaiseHistoryModalLabel">Show</a>
                                    </small>
                                </p>

                                <?php if ($role === "admin") { ?>
                                    <i id="payRaiseEditIconWage" role="button"
                                        class="fa-regular fa-pen-to-square signature-color" data-bs-toggle="modal"
                                        data-bs-target="#wagePayRaiseHistoryModal"></i>
                                <?php } ?>
                            </div>
                            <div class="px-4 py-2">
                                <div id="chartContainer" style="height: 300px; width: 100%;" class="d-block"></div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="card bg-white border-0 rounded shadow-lg mt-4 payRaiseHistoryPrint <?php echo ($payrollType === "salary") ? 'd-block' : 'd-none'; ?>">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="fw-bold signature-color mb-0 d-flex align-items-center">
                                    Pay Raise History
                                    <span class="badge rounded-pill signature-btn mx-1">Salary</span>
                                    <span class="mx-2">|</span>
                                    <i class="fa-solid fa-eye text-danger me-1 showSalaryPayRaiseHistoryChartBtn"
                                        role="button"></i>
                                    <small class="pe-2 fw-bold text-decoration-underline showSalaryPayRaiseHistoryChartBtn">
                                        <a role="button" id="hideSalaryPayRaiseHistoryModalLabel">Show</a>
                                    </small>
                                </p>

                                <?php if ($role === "admin") { ?>
                                    <i id="payRaiseEditIconSalary" role="button"
                                        class="fa-regular fa-pen-to-square signature-color" data-bs-toggle="modal"
                                        data-bs-target="#salaryPayRaiseHistoryModal"></i>
                                <?php } ?>
                            </div>
                            <div class="px-4 py-2">
                                <div id="chartContainer2" style="height: 300px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="hide-print">
                        <?php if (isset($employmentType) && $employmentType == "Casual") {
                            $today = new DateTime();
                            $firstMonthDueDate = date('Y-m-d', strtotime($startDate . ' +1 month'));
                            $thirdMonthDueDate = date('Y-m-d', strtotime($startDate . ' +3 month'));
                            $sixthMonthDueDate = date('Y-m-d', strtotime($startDate . ' +6 month'));
                            $ninthMonthDueDate = date('Y-m-d', strtotime($startDate . ' +9 month'));
                            $twelfthMonthDueDate = date('Y-m-d', strtotime($startDate . ' +12 month'));

                            // $reviewType = isset($reviewType) ? $reviewType : null;
                            $revieweeEmployeeId = isset($revieweeEmployeeId) ? $revieweeEmployeeId : null;
                            $reviewerEmployeeId = isset($reviewerEmployeeId) ? $reviewerEmployeeId : null;
                            // $reviewDate = isset($reviewDate) ? $reviewDate : null;
                            // $reviewNotes = isset($reviewNotes) ? $reviewNotes : null;
                            ?>
                            <div class="card bg-white border-0 rounded shadow-lg mt-4">
                                <div class="p-3">
                                    <p class="fw-bold signature-color">Performance Review</p>
                                    <!-- First Month Review -->
                                    <div class="d-flex align-items-center">
                                        <?php $hasFirstMonthReview = false;
                                        foreach ($performance_review_result as $row) {
                                            if ($row['review_type'] === "First Month Review") {
                                                $hasFirstMonthReview = true;
                                                break;
                                            }
                                        } ?>
                                        <?php
                                        $firstMonthDueDateFormat = new DateTime($firstMonthDueDate);
                                        $firstMonthInterval = $today->diff($firstMonthDueDateFormat);
                                        $firstMonthDaysDifference = $firstMonthInterval->format('%r%a');
                                        ?>
                                        <?php if ($hasFirstMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <i class="fa-solid fa-star fa-lg text-warning"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                        <?php } ?>
                                        <div class="ms-3">
                                            <div class="d-flex flex-column">
                                                <div class="fw-bold">1<sup>st</sup> Month Review
                                                    <?php if ($hasFirstMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                        <span class="badge rounded-pill bg-success">Done</span>
                                                    <?php } else if (!$hasFirstMonthReview && $revieweeEmployeeId != $employeeId && $firstMonthDaysDifference < 7 && $firstMonthDaysDifference >= 0) { ?>
                                                            <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                    <?php } else if (!$hasFirstMonthReview && $firstMonthDaysDifference < 0) { ?>
                                                                <span class="badge rounded-pill bg-danger">Past Due</span>
                                                    <?php } else {
                                                        } ?>
                                                </div>
                                                <div>
                                                    <small class="text-secondary">Due: <?php echo $firstMonthDueDate ?></small>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ((string) $loginEmployeeId != (string) $revieweeEmployeeId) { ?>
                                            <button class="btn ms-auto" data-bs-toggle="modal"
                                                data-bs-target="#firstMonthPerformanceReviewModal">
                                                <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                            </button>
                                        <?php } ?>
                                    </div>

                                    <!-- Third Month Review -->
                                    <hr />
                                    <div class="d-flex align-items-center">
                                        <?php $hasThirdMonthReview = false;
                                        foreach ($performance_review_result as $row) {
                                            if ($row['review_type'] === "Third Month Review") {
                                                $hasThirdMonthReview = true;
                                                break;
                                            }
                                        } ?>
                                        <?php
                                        $thirdMonthDueDateFormat = new DateTime($thirdMonthDueDate);
                                        $thirdMonthInterval = $today->diff($thirdMonthDueDateFormat);
                                        $thirdMonthDaysDifference = $thirdMonthInterval->format('%r%a');
                                        ?>
                                        <?php if ($hasThirdMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <i class="fa-solid fa-star fa-lg text-warning"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                        <?php } ?>
                                        <div class="ms-3">
                                            <div class="d-flex flex-column">
                                                <div class="fw-bold">3<sup>rd</sup> Month Review
                                                    <?php if ($hasThirdMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                        <span class="badge rounded-pill bg-success">Done</span>
                                                    <?php } else if (!$hasThirdMonthReview && $revieweeEmployeeId != $employeeId && $thirdMonthDaysDifference < 7 && $thirdMonthDaysDifference >= 0) { ?>
                                                            <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                    <?php } else if (!$hasThirdMonthReview && $thirdMonthDaysDifference < 0) { ?>
                                                                <span class="badge rounded-pill bg-danger">Past Due</span>
                                                    <?php } else {
                                                        } ?>
                                                </div>
                                                <div>
                                                    <small class="text-secondary">Due: <?php echo $thirdMonthDueDate ?></small>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ((string) $loginEmployeeId != (string) $revieweeEmployeeId) { ?>
                                            <button class="btn ms-auto" data-bs-toggle="modal"
                                                data-bs-target="#thirdMonthPerformanceReviewModal">
                                                <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                            </button>
                                        <?php } ?>
                                    </div>

                                    <!-- Sixth Month Review -->
                                    <hr />
                                    <div class="d-flex align-items-center">
                                        <?php $hasSixthMonthReview = false;
                                        foreach ($performance_review_result as $row) {
                                            if ($row['review_type'] === "Sixth Month Review") {
                                                $hasSixthMonthReview = true;
                                                break;
                                            }
                                        } ?>
                                        <?php
                                        $sixthMonthDueDateFormat = new DateTime($sixthMonthDueDate);
                                        $sixthMonthInterval = $today->diff($sixthMonthDueDateFormat);
                                        $sixthMonthDaysDifference = $sixthMonthInterval->format('%r%a');
                                        ?>
                                        <?php if ($hasSixthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <i class="fa-solid fa-star fa-lg text-warning"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                        <?php } ?>
                                        <div class="ms-3">
                                            <div class="d-flex flex-column">
                                                <div class="fw-bold">6<sup>th</sup> Month Review
                                                    <?php if ($hasSixthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                        <span class="badge rounded-pill bg-success">Done</span>
                                                    <?php } else if (!$hasSixthMonthReview && $revieweeEmployeeId != $employeeId && $sixthMonthDaysDifference < 7 && $sixthMonthDaysDifference >= 0) { ?>
                                                            <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                    <?php } else if (!$hasSixthMonthReview && $sixthMonthDaysDifference < 0) { ?>
                                                                <span class="badge rounded-pill bg-danger">Past Due</span>
                                                    <?php } else {
                                                        } ?>
                                                </div>
                                                <div>
                                                    <small class="text-secondary">Due: <?php echo $sixthMonthDueDate ?></small>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ((string) $loginEmployeeId != (string) $revieweeEmployeeId) { ?>
                                            <button class="btn ms-auto" data-bs-toggle="modal"
                                                data-bs-target="#sixthMonthPerformanceReviewModal">
                                                <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                            </button>
                                        <?php } ?>
                                    </div>

                                    <!-- Ninth Month Review -->
                                    <hr />
                                    <div class="d-flex align-items-center">
                                        <?php $hasNinthMonthReview = false;
                                        foreach ($performance_review_result as $row) {
                                            if ($row['review_type'] === "Ninth Month Review") {
                                                $hasNinthMonthReview = true;
                                                break;
                                            }
                                        } ?>
                                        <?php
                                        $ninthMonthDueDateFormat = new DateTime($ninthMonthDueDate);
                                        $ninthMonthInterval = $today->diff($ninthMonthDueDateFormat);
                                        $ninthMonthDaysDifference = $ninthMonthInterval->format('%r%a');
                                        ?>
                                        <?php if ($hasNinthMonthReview) { ?>
                                            <i class="fa-solid fa-star fa-lg text-warning"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                        <?php } ?>
                                        <div class="ms-3">
                                            <div class="d-flex flex-column">
                                                <div class="fw-bold">9<sup>th</sup> Month Review
                                                    <?php if ($hasNinthMonthReview) { ?>
                                                        <span class="badge rounded-pill bg-success">Done</span>
                                                    <?php } else if (!$hasNinthMonthReview && $revieweeEmployeeId != $employeeId && $reviewerEmployeeId != $loginEmployeeId && $ninthMonthDaysDifference < 7 && $ninthMonthDaysDifference >= 0) { ?>
                                                            <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                    <?php } else if (!$hasNinthMonthReview && $ninthMonthDaysDifference < 0) { ?>
                                                                <span class="badge rounded-pill bg-danger">Past Due</span>
                                                    <?php } else {
                                                        } ?>
                                                </div>
                                                <div>
                                                    <small class="text-secondary">Due: <?php echo $ninthMonthDueDate ?></small>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ((string) $loginEmployeeId != (string) $revieweeEmployeeId) { ?>
                                            <button class="btn ms-auto" data-bs-toggle="modal"
                                                data-bs-target="#ninthMonthPerformanceReviewModal">
                                                <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                            </button>
                                        <?php } ?>
                                    </div>

                                    <!-- Twelfth Month Review -->
                                    <hr />
                                    <div class="d-flex align-items-center">
                                        <?php $hasTwelfthMonthReview = false;
                                        foreach ($performance_review_result as $row) {
                                            if ($row['review_type'] === "Twelfth Month Review") {
                                                $hasTwelfthMonthReview = true;
                                                break;
                                            }
                                        } ?>
                                        <?php
                                        $twelfthMonthDueDateFormat = new DateTime($twelfthMonthDueDate);
                                        $twelfthMonthInterval = $today->diff($twelfthMonthDueDateFormat);
                                        $twelfthMonthDaysDifference = $twelfthMonthInterval->format('%r%a');
                                        ?>
                                        <?php if ($hasTwelfthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <i class="fa-solid fa-star fa-lg text-warning"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                        <?php } ?>
                                        <div class="ms-3">
                                            <div class="d-flex flex-column">
                                                <div class="fw-bold">12<sup>th</sup> Month Review
                                                    <?php if ($hasTwelfthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                        <span class="badge rounded-pill bg-success">Done</span>
                                                    <?php } else if (!$hasTwelfthMonthReview && $revieweeEmployeeId != $employeeId && $twelfthMonthDaysDifference < 7 && $twelfthMonthDaysDifference >= 0) { ?>
                                                            <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                    <?php } else if (!$hasTwelfthMonthReview && $twelfthMonthDaysDifference < 0) { ?>
                                                                <span class="badge rounded-pill bg-danger">Past Due</span>
                                                    <?php } else {
                                                        } ?>
                                                </div>
                                                <div>
                                                    <small class="text-secondary">Due:
                                                        <?php echo $twelfthMonthDueDate ?></small>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ((string) $loginEmployeeId != (string) $revieweeEmployeeId) { ?>
                                            <button class="btn ms-auto" data-bs-toggle="modal"
                                                data-bs-target="#twelfthMonthPerformanceReviewModal">
                                                <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                            </button>
                                        <?php } ?>
                                    </div>

                                    <!-- <?php echo $firstMonthDueDate . " " . $thirdMonthDueDate . " " . $sixthMonthDueDate . " " . $ninthMonthDueDate ?> -->

                                </div>
                            </div>
                        <?php } ?>

                        <!-- <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3">
                            <div class="d-flex justify-content-between">
                                <p class="fw-bold signature-color">Policies</p>
                                <p type="button"><i class="fa-solid fa-plus signature-color" data-bs-toggle="modal"
                                        data-bs-target="#addPoliciesModal"></i></p>
                            </div>
                            <a href="http://localhost/FUJI-Directories/CheckDirectory/Sample_Smoking_Policy.jpeg">
                                Smoking and Vaping Policy </a>
                        </div>
                    </div> -->
                        <div class="card bg-white border-0 rounded shadow-lg mt-4">
                            <div class="p-3">
                                <p class="fw-bold signature-color">Access</p>

                                <?php
                                // Check if there are any results
                                if ($employee_group_access_result->num_rows > 0) {
                                    $current_group_id = null;

                                    // Initialize arrays to store unique group names and folder names
                                    $unique_group_names = [];
                                    $unique_folders = [];

                                    // Fetch all rows from the result set
                                    while ($row = $employee_group_access_result->fetch_assoc()) {
                                        $group_id = $row['group_id'];
                                        $group_name = htmlspecialchars($row['group_name']);
                                        $folder_id = htmlspecialchars($row['folder_id']);
                                        $folder_name = htmlspecialchars($row['folder_name']);

                                        // Collect unique group names
                                        if (!isset($unique_group_names[$group_id])) {
                                            $unique_group_names[$group_id] = $group_name;
                                        }

                                        // Collect unique folder names
                                        $unique_folders[$folder_id] = $folder_name;
                                    }

                                    // Output unique group names
                                    if (!empty($unique_group_names)) {
                                        echo "<strong>Groups:</strong><br>";
                                        foreach ($unique_group_names as $group_id => $group_name) {
                                            echo "<p>$group_name</p>";
                                        }
                                        echo "<hr>";
                                    }

                                    // Output unique folder names
                                    if (!empty($unique_folders)) {
                                        echo "<strong>Folders:</strong><br>";
                                        foreach ($unique_folders as $folder_id => $folder_name) {
                                            echo "<p>$folder_name</p>";
                                        }
                                    }
                                } else {
                                    echo '<p>No group or folder access found.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Pay History Modal (Wage) ================== -->
                    <div class="modal fade" id="wagePayRaiseHistoryModal" tabindex="-2"
                        aria-labelledby="payRaiseHistoryModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold" id="payRaiseHistoryModalLabel">Pay Raise History
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-5">
                                    <div class="table-responsive border rounded-3">
                                        <table class="table table-hover mb-0 pb-0">
                                            <thead class="table-primary">
                                                <tr class="text-center">
                                                    <th class="py-3">Date</th>
                                                    <th class="py-3">Amount</th>
                                                    <th class="py-3">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($wagesData)) { ?>
                                                    <?php foreach ($wagesData as $row) { ?>
                                                        <tr class="text-center align-middle">
                                                            <form method="POST">
                                                                <!-- Hidden input for wages_id -->
                                                                <input type="hidden" name="wages_id"
                                                                    value="<?php echo $row['wages_id']; ?>">
                                                                <td class="align-middle col-md-6">
                                                                    <span
                                                                        class="view-mode"><?php echo date("j F Y", strtotime($row['date'])); ?></span>
                                                                    <input type="date" max="9999-12-31"
                                                                        class="form-control edit-mode d-none mx-auto"
                                                                        name="editDate"
                                                                        value="<?php echo date("Y-m-d", strtotime($row['date'])); ?>"
                                                                        style="width: 80%">
                                                                </td>
                                                                <td class="align-middle col-md-3">
                                                                    <span class="view-mode">$<?php echo $row['amount']; ?></span>
                                                                    <input type="text" class="form-control edit-mode d-none mx-auto"
                                                                        name="editWage" value="<?php echo $row['amount']; ?>">
                                                                </td>
                                                                <td class="align-middle">
                                                                    <!-- Edit form -->
                                                                    <div class="view-mode">
                                                                        <button type="button" class="btn btn-sm edit-btn p-0"><i
                                                                                class="fa-regular fa-pen-to-square signature-color m-1"></i></button>
                                                                        <div class="btn" id="#openDeleteConfirmation"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteConfirmationModal"
                                                                            data-wageamount="<?php echo $row['amount']; ?>"
                                                                            data-wagedate="<?php echo date("j F Y", strtotime($row['date'])); ?>"
                                                                            data-wages-id="<?php echo $row['wages_id']; ?>">
                                                                            <i class="fa-solid fa-trash-can text-danger m-1"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="edit-mode d-none d-flex justify-content-center">
                                                                        <button type="submit"
                                                                            class="btn btn-sm px-2 btn-success mx-1">
                                                                            <div class="d-flex justify-content-center"><i
                                                                                    role="button"
                                                                                    class="fa-solid fa-check text-white m-1"></i>
                                                                                Save </div>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                                            <div class="d-flex justify-content-center"> <i
                                                                                    role="button"
                                                                                    class="fa-solid fa-xmark  text-white m-1"></i>Cancel
                                                                            </div>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </form>

                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr class=" text-center align-middle">
                                                        <td colspan="3">No records found</td>
                                                    </tr>
                                                <?php } ?>

                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex flex-grow-1 mt-4">
                                        <form method="POST" class="col-md-12" id="addNewWageForm" novalidate>
                                            <div class="row g-3">
                                                <!-- New Wage Input -->
                                                <div class="col-6">
                                                    <label for="newWage" class="form-label fw-bold">New Wage</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" min="0" step="any"
                                                            class="form-control rounded-end" id="newWage" name="newWage"
                                                            placeholder="Enter new wage" required>
                                                        <div class="invalid-feedback">
                                                            Please provide new wage amount.
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Update Date Input -->
                                                <div class="col-6">
                                                    <label for="updateWageDate" class="form-label fw-bold">Update
                                                        Date</label>
                                                    <input type="date" max="9999-12-31" class="form-control"
                                                        id="updateWageDate" name="updateWageDate"
                                                        value="<?php echo date('Y-m-d'); ?>" required>
                                                    <div class="invalid-feedback">
                                                        Please provide the date of the wage update.
                                                    </div>
                                                </div>

                                                <!-- Submit Button -->
                                                <div class="col-12 text-center">
                                                    <button type="submit" class="btn btn-dark rounded">Update Wage</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Pay History Modal (Salary) ================== -->
                    <div class="modal fade" id="salaryPayRaiseHistoryModal" tabindex="-2"
                        aria-labelledby="payRaiseHistoryModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold" id="payRaiseHistoryModalLabel">Pay Raise History
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-5">
                                    <div class="table-responsive border rounded-3">
                                        <table class="table table-hover mb-0 pb-0">
                                            <thead class="table-primary">
                                                <tr class="text-center">
                                                    <th class="py-3">Date</th>
                                                    <th class="py-3">Amount</th>
                                                    <th class="py-3">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($salariesData)) { ?>
                                                    <?php foreach ($salariesData as $row) { ?>
                                                        <tr class="text-center align-middle">
                                                            <form method="POST">
                                                                <!-- Hidden input for salary_id -->
                                                                <input type="hidden" name="salary_id"
                                                                    value="<?php echo $row['salary_id']; ?>">
                                                                <td class="align-middle col-md-6">
                                                                    <span
                                                                        class="view-mode"><?php echo date("j F Y", strtotime($row['date'])); ?></span>
                                                                    <input type="date" max="9999-12-31"
                                                                        class="form-control edit-mode d-none mx-auto"
                                                                        name="editSalaryDate"
                                                                        value="<?php echo date("Y-m-d", strtotime($row['date'])); ?>"
                                                                        style="width: 80%">
                                                                </td>
                                                                <td class="align-middle col-md-3">
                                                                    <span class="view-mode">$<?php echo $row['amount']; ?></span>
                                                                    <input type="text" class="form-control edit-mode d-none mx-auto"
                                                                        name="editSalary" value="<?php echo $row['amount']; ?>">
                                                                </td>
                                                                <td class="align-middle">
                                                                    <!-- Edit form -->
                                                                    <div class="view-mode">
                                                                        <button type="button" class="btn btn-sm edit-btn p-0"><i
                                                                                class="fa-regular fa-pen-to-square signature-color m-1"></i></button>
                                                                        <div class="btn" id="#openDeleteConfirmation"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteConfirmationModalSalary"
                                                                            data-salaryamount="<?php echo $row['amount']; ?>"
                                                                            data-salarydate="<?php echo date("j F Y", strtotime($row['date'])); ?>"
                                                                            data-salary-id="<?php echo $row['salary_id']; ?>">
                                                                            <i class="fa-solid fa-trash-can text-danger m-1"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="edit-mode d-none d-flex justify-content-center">
                                                                        <button type="submit"
                                                                            class="btn btn-sm px-2 btn-success mx-1">
                                                                            <div class="d-flex justify-content-center"><i
                                                                                    role="button"
                                                                                    class="fa-solid fa-check text-white m-1"></i>
                                                                                Save </div>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                                            <div class="d-flex justify-content-center"> <i
                                                                                    role="button"
                                                                                    class="fa-solid fa-xmark text-white m-1"></i>Cancel
                                                                            </div>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </form>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr class=" text-center align-middle">
                                                        <td colspan="3">No records found</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex flex-grow-1 mt-4">
                                        <form method="POST" class="col-md-12" id="addNewSalaryForm" novalidate>
                                            <div class="row g-3">
                                                <!-- New Salary Input -->
                                                <div class="col-6">
                                                    <label for="newSalary" class="form-label fw-bold">New Salary</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" min="0" step="any"
                                                            class="form-control rounded-end" id="newSalary" name="newSalary"
                                                            placeholder="Enter new salary" required>
                                                        <div class="invalid-feedback">
                                                            Please provide new salary amount.
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Update Date Input -->
                                                <div class="col-6">
                                                    <label for="updateSalaryDate" class="form-label fw-bold">Update
                                                        Date</label>
                                                    <input type="date" max="9999-12-31" class="form-control"
                                                        id="updateSalaryDate" name="updateSalaryDate"
                                                        value="<?php echo date('Y-m-d'); ?>" required>
                                                    <div class="invalid-feedback">
                                                        Please provide the date of the salary update.
                                                    </div>
                                                </div>

                                                <!-- Submit Button -->
                                                <div class="col-12 text-center">
                                                    <button type="submit" class="btn btn-dark rounded">Update
                                                        Salary</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Delete Confirmation Modal (Wage) ================== -->
                    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1"
                        aria-labelledby="deleteConfirmationLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!-- Delete form -->
                                    Are you sure you want to delete the wage record for <b> <span id="wageDate"></span>
                                    </b>
                                    with an amount of <b> $<span id="wageAmount"></b></span>?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <!-- Add form submission for deletion here -->
                                    <form method="POST">
                                        <input type="hidden" name="wageIdToDelete" id="wageIdToDelete">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Delete Confirmation Modal (Salary) ================== -->
                    <div class="modal fade" id="deleteConfirmationModalSalary" tabindex="-1"
                        aria-labelledby="deleteConfirmationLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!-- Delete form -->
                                    Are you sure you want to delete the salary record for <b> <span id="salaryDate"></span>
                                    </b>
                                    with an amount of <b> $<span id="salaryAmount"></b></span>?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <!-- Add form submission for deletion here -->
                                    <form method="POST">
                                        <input type="hidden" name="salaryIdToDelete" id="salaryIdToDelete">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Edit Profile Modal ================== -->
                    <?php require_once("../Form/EditEmployeeDetailsForm.php") ?>

                    <!-- ================== Add Policies Modal ================== -->
                    <div class="modal fade" id="addPoliciesModal" tabindex="-1" aria-labelledby="addPoliciesModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addPoliciesModalLabel">Add Policies Document</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Drag and Drop area -->
                                    <div class="border border-dashed p-4 text-center" id="dropZone">
                                        <p class="mb-0">Drag & Drop your documents here or <br>
                                            <button class="btn btn-primary btn-sm mt-2"
                                                onclick="document.getElementById('fileInput').click()">Browse
                                                Files</button>
                                        </p>
                                    </div>
                                    <form method="POST">
                                        <input type="file" id="fileInput" name="policiesToSubmit" class="d-none" multiple />
                                        <!-- Display uploaded file names -->
                                        <div id="fileList" class="mt-3"></div>
                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-dark btn-sm"> Add
                                                Documents</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (First Month)================== -->
                    <div class="modal fade" id="firstMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="firstMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="firstMonthPerformanceReviewModalLabel">1st Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php
                                    $hasFirstMonthReview = false;
                                    $firstMonthReviewDate = '';
                                    $firstMonthReviewerEmployeeId = '';
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "First Month Review") {
                                            $hasFirstMonthReview = true;
                                            $firstMonthReviewDate = $row['review_date'];
                                            $firstMonthReviewNotes = $row['review_notes'];
                                            $firstMonthReviewerEmployeeId = $row['reviewer_employee_id'];
                                            break;
                                        }
                                    } ?>

                                    <?php
                                    $query = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("s", $firstMonthReviewerEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($firstMonthFirstName, $firstMonthLastName);
                                    $stmt->fetch();
                                    $stmt->close();
                                    ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasFirstMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong>
                                                    <?php echo htmlspecialchars($firstMonthReviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>

                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($firstMonthReviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdFirstMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="First Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" max="9999-12-31" class="form-control" id="reviewDate"
                                                    name="reviewDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>

                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <?php if (!$hasFirstMonthReview) { ?>
                                            <button type="submit" class="btn btn-dark">Submit Review</button>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (Third Month)================== -->
                    <div class="modal fade" id="thirdMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="thirdMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="thirdMonthPerformanceReviewModalLabel">3rd Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php $hasThirdMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Third Month Review") {
                                            $hasThirdMonthReview = true;
                                            $thirdMonthReviewDate = $row['review_date'];
                                            $thirdMonthReviewNotes = $row['review_notes'];
                                            $thirdMonthReviewerEmployeeId = $row['reviewer_employee_id'];
                                            break;
                                        }
                                    } ?>
                                    <?php
                                    $query = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("s", $thirdMonthReviewerEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($thirdMonthFirstName, $thirdMonthLastName);
                                    $stmt->fetch();
                                    $stmt->close();
                                    ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasThirdMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong>
                                                    <?php echo htmlspecialchars($thirdMonthReviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($thirdMonthReviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdThirdMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="Third Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" max="9999-12-31" class="form-control" id="reviewDate"
                                                    name="reviewDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>

                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <?php if (!$hasThirdMonthReview) { ?>
                                            <button type="submit" class="btn btn-dark">Submit Review</button>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (Sixth Month)================== -->
                    <div class="modal fade" id="sixthMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="sixthMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="sixthMonthPerformanceReviewModalLabel">6th Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php $hasSixthMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Sixth Month Review") {
                                            $hasSixthMonthReview = true;
                                            $sixthMonthReviewDate = $row['review_date'];
                                            $sixthMonthReviewNotes = $row['review_notes'];
                                            $sixthMonthReviewerEmployeeId = $row['reviewer_employee_id'];
                                            break;
                                        }
                                    } ?>
                                    <?php
                                    $query = "SELECT first_name, last_name FROm employees WHERE employee_id = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("s", $sixthMonthReviewerEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($sixthMonthFirstName, $sixthMonthLastName);
                                    $stmt->fetch();
                                    $stmt->close();
                                    ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                                <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                        <?php echo $firstName . " " . $lastName ?> </span></p>
                                                <?php if ($hasSixthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                    <div class="review-details">
                                                        <p><strong>Review Date:</strong>
                                                            <?php echo htmlspecialchars($sixthMonthReviewDate); ?>
                                                        </p>
                                                        <p><strong>Reviewee Employee ID:</strong>
                                                            <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                        <p><strong>Review Notes:</strong>
                                                            <?php echo htmlspecialchars($sixthMonthReviewNotes); ?></p>
                                                    </div>
                                                <?php } else { ?>
                                                    <input type="hidden" name="revieweeEmployeeIdSixthMonthReview"
                                                        value="<?php echo htmlspecialchars($employeeId); ?>" />
                                                    <input type="hidden" name="reviewerEmployeeId"
                                                        value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                                    <input type="hidden" name="reviewType" value="Sixth Month Review" />
                                                    <div class="mb-3">
                                                        <label for="reviewDate" class="form-label"><strong>Review
                                                                Date:</strong></label>
                                                        <input type="date" max="9999-12-31" class="form-control" id="reviewDate"
                                                            name="reviewDate" value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="reviewNotes" class="form-label"><strong>Review
                                                                Notes:</strong></label>
                                                        <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                            rows="4"></textarea>
                                                    </div>
                                                <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <?php if (!$hasSixthMonthReview) { ?>
                                            <button type="submit" class="btn btn-dark">Submit Review</button>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (Ninth Month)================== -->
                    <div class="modal fade" id="ninthMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="ninthMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="ninthMonthPerformanceReviewModalLabel">9th Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php $hasNinthMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Ninth Month Review") {
                                            $hasNinthMonthReview = true;
                                            $ninthMonthReviewDate = $row['review_date'];
                                            $ninthMonthReviewNotes = $row['review_notes'];
                                            $ninthMonthReviewerEmployeeId = $row['reviewer_employee_id'];
                                            break;
                                        }
                                    } ?>

                                    <?php
                                    $query = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("s", $ninthMonthReviewerEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($ninthMonthFirstName, $ninthMonthLastName);
                                    $stmt->close();
                                    ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasNinthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong>
                                                    <?php echo htmlspecialchars($ninthMonthReviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($ninthMonthReviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdNinthMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="Ninth Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" max="9999-12-31" class="form-control" id="reviewDate"
                                                    name="reviewDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>

                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <?php if (!$hasNinthMonthReview) { ?>
                                            <button type="submit" class="btn btn-dark">Submit Review</button>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (Twelfth Month)================== -->
                    <div class="modal fade" id="twelfthMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="twelfthMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="twelfthMonthPerformanceReviewModalLabel">12th Month Review
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php $hasTwelfthMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Twelfth Month Review") {
                                            $hasTwelfthMonthReview = true;
                                            $twelfthMonthReviewDate = $row['review_date'];
                                            $twelfthMonthReviewNotes = $row['review_notes'];
                                            $twelfthMonthReviewerEmployeeId = $row['reviewer_employee_id'];
                                            break;
                                        }
                                    } ?>

                                    <?php
                                    $query = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("s", $twelfthMonthReviewerEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($twelfthMonthFirstName, $twelfthMonthLastName);
                                    $stmt->fetch();
                                    $stmt->close();
                                    ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasTwelfthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong> <?php echo htmlspecialchars($reviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($reviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdTwelfthMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="Twelfth Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" max="9999-12-31" class="form-control" id="reviewDate"
                                                    name="reviewDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>

                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <?php if (!$hasTwelfthMonthReview) { ?>
                                            <button type="submit" class="btn btn-dark">Submit Review</button>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php require_once("../logout.php") ?>
            </div>
            <?php
                }
                ?>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var myModalEl = document.getElementById('deleteConfirmationModal');
                myModalEl.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget; // Button that triggered the modal
                    var wageDate = button.getAttribute('data-wagedate'); // Extract info from data-* attributes
                    var wageAmount = button.getAttribute('data-wageamount');

                    var wageIdToDelete = button.getAttribute('data-wages-id');

                    // Update the modal's content with the extracted info
                    var modalWageDate = myModalEl.querySelector('#wageDate');
                    var modalWageAmount = myModalEl.querySelector('#wageAmount');
                    var modalWageIdToDelete = myModalEl.querySelector('#wageIdToDelete');
                    modalWageDate.textContent = wageDate;
                    modalWageAmount.textContent = wageAmount;
                    modalWageIdToDelete.value = wageIdToDelete;
                });
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var myModalEl = document.getElementById('deleteConfirmationModalSalary');
                myModalEl.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget; // Button that triggered the modal
                    var salaryDate = button.getAttribute('data-salarydate'); // Extract info from data-* attributes
                    var salaryAmount = button.getAttribute('data-salaryamount');

                    var salaryIdToDelete = button.getAttribute('data-salary-id');

                    // Update the modal's content with the extracted info
                    var modalSalaryDate = myModalEl.querySelector('#salaryDate');
                    var modalSalaryAmount = myModalEl.querySelector('#salaryAmount');
                    var modalSalaryIdToDelete = myModalEl.querySelector('#salaryIdToDelete');
                    modalSalaryDate.textContent = salaryDate;
                    modalSalaryAmount.textContent = salaryAmount;
                    modalSalaryIdToDelete.value = salaryIdToDelete;
                });
            });
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Initialize Bootstrap modal
                var payRaiseHistoryModal = new bootstrap.Modal(document.getElementById('payRaiseHistoryModal'));

                // Pay Raise History edit icon click event
                document.querySelector('#payRaiseEditIcon').addEventListener('click', function () {
                    // Show the pay raise history modal
                    payRaiseHistoryModal.show();
                });
            });
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Edit button click event handler
                document.querySelectorAll('.edit-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle edit mode
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode, .edit-mode').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    });
                });
            });
        </script>

        <script>
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const fileList = document.getElementById('fileList');
            let selectedFiles = [];

            dropZone.addEventListener('dragover', function (event) {
                event.preventDefault();
                dropZone.classList.add('bg-light');
            });

            dropZone.addEventListener('dragleave', function () {
                dropZone.classList.remove('bg-light');
            });

            dropZone.addEventListener('drop', function (event) {
                event.preventDefault();
                dropZone.classList.remove('bg-light');
                const files = event.dataTransfer.files;
                handleFiles(files);
            });

            fileInput.addEventListener('change', function (event) {
                const files = event.target.files;
                handleFiles(files);
            });

            function handleFiles(files) {
                for (let i = 0; i < files.length; i++) {
                    addFile(files[i]);
                }
                renderFileList();
            }

            function addFile(file) {
                selectedFiles.push(file);
            }

            function removeFile(index) {
                selectedFiles.splice(index, 1);
                renderFileList();
            }

            function renderFileList() {
                fileList.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const listItem = document.createElement('div');
                    listItem.className = 'd-flex justify-content-between align-items-center border p-2 mb-2';
                    listItem.innerHTML = `
                    <span>${file.name}</span>
                    <button class="btn btn-danger btn-sm" onclick="removeFile(${index})">Remove</button>
                `;
                    fileList.appendChild(listItem);
                });
            }

            // Reset file input and selected files when the modal is closed
            document.getElementById('addPoliciesModal').addEventListener('hidden.bs.modal', function () {
                resetFileInput();
            });

            function resetFileInput() {
                fileInput.value = ''; // Clear the file input
                selectedFiles = []; // Clear the array of selected files
                renderFileList(); // Clear the file list display
            }
        </script>

        <script>
            $(document).ready(function () {
                // Event listener for modal close event
                $('#payRaiseHistoryModal').on('hidden.bs.modal', function () {
                    // For each row in the table
                    $('#payRaiseHistoryModal tbody tr').each(function () {
                        // Show view mode and hide edit mode
                        $(this).find('.view-mode').removeClass('d-none');
                        $(this).find('.edit-mode').addClass('d-none');
                    });
                });
            });
        </script>

        <script>
            function copyDirectoryPath(button) {
                var directoryPathElement = button.parentElement.querySelector('input').value;

                console.log(directoryPathElement);
                var textArea = document.createElement("textarea");

                // Place the directory path text inside the textarea
                textArea.textContent = directoryPathElement;


                // Ensure textarea is non-visible
                textArea.style.position = "fixed";
                textArea.style.opacity = 0;

                // Append the textarea to the document
                document.body.appendChild(textArea);

                // Select the text inside the textarea
                textArea.select();

                try {
                    // Execute the copy command
                    document.execCommand('copy');
                    console.log('Text copied successfully');

                    // Change button text to "Copied"
                    button.innerHTML = '<i class="fa-regular fa-check-circle text-success fa-xs"></i> <small class="text-success">Copied</small>';

                    // Reset button text after 2 seconds
                    setTimeout(function () {
                        button.innerHTML = '<i class="fa-regular fa-copy text-primary fa-xs"></i> <small class="text-primary">Copy</small>';
                    }, 2000); // 2000 milliseconds = 2 seconds

                } catch (err) {
                    console.error('Unable to copy text', err);
                }

                // Remove the textarea from the document
                document.body.removeChild(textArea);
            }

        </script>

        <script>
            // Enabling the tooltip
            const tooltips = document.querySelectorAll('.tooltips');
            tooltips.forEach(t => {
                new bootstrap.Tooltip(t);
            })
        </script>

        <script>
            // Restore scroll position after page reload
            window.addEventListener('load', function () {
                const scrollPosition = sessionStorage.getItem('scrollPosition');
                if (scrollPosition) {
                    window.scrollTo(0, scrollPosition);
                    sessionStorage.removeItem('scrollPosition'); // Remove after restoring
                }
            });
        </script>

        <!-- Toggle hide and show Wage Pay Raise History -->
        <script>
            // Get the elements
            const wagePayRaiseHistoryChart = document.getElementById('chartContainer');
            const hideWagePayRaiseHistoryModalLabel = document.getElementById('hideWagePayRaiseHistoryModalLabel');
            const showWagePayRaiseHistoryChartBtns = document.querySelectorAll('.showWagePayRaiseHistoryChartBtn');

            // Delay adding the d-none class by 40 milliseconds on page load
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () {
                    wagePayRaiseHistoryChart.classList.add('d-none');
                }, 40);
            });

            // Function to toggle the visibility of the wage pay raise history chart
            function toggleWagePayRaiseHistory() {
                if (wagePayRaiseHistoryChart.classList.contains('d-none')) {
                    // Show the chart
                    wagePayRaiseHistoryChart.classList.remove('d-none');
                    wagePayRaiseHistoryChart.classList.add('d-block');
                    hideWagePayRaiseHistoryModalLabel.innerHTML = "Hide";
                    showWagePayRaiseHistoryChartBtns[0].classList.remove('fa-eye');
                    showWagePayRaiseHistoryChartBtns[0].classList.add('fa-eye-slash');
                } else {
                    // Hide the chart
                    wagePayRaiseHistoryChart.classList.remove('d-block');
                    wagePayRaiseHistoryChart.classList.add('d-none');
                    hideWagePayRaiseHistoryModalLabel.innerHTML = "Show";
                    showWagePayRaiseHistoryChartBtns[0].classList.remove('fa-eye-slash');
                    showWagePayRaiseHistoryChartBtns[0].classList.add('fa-eye');
                }
            }

            // Add event listeners to each button
            showWagePayRaiseHistoryChartBtns.forEach(btn => {
                btn.addEventListener('click', toggleWagePayRaiseHistory);
            });
        </script>

        <!-- Toggle hide and show Salary Pay Raise History -->
        <script>
            // Get the Elements
            const salaryPayRaiseHistoryChart = document.getElementById('chartContainer2');
            const hideSalaryPayRaiseHistoryModalLabel = document.getElementById('hideSalaryPayRaiseHistoryModalLabel');
            const showSalaryPayRaiseHistoryChartBtns = document.querySelectorAll('.showSalaryPayRaiseHistoryChartBtn');

            // Delay adding the d-none class by 40 milliseconds on page load
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () {
                    salaryPayRaiseHistoryChart.classList.add('d-none')
                }, 40);
            })

            // Function to toggle the visibility of the salary pat raise history chart
            function toggleSalaryPayRaiseHistory() {
                if (salaryPayRaiseHistoryChart.classList.contains('d-none')) {
                    // Show the chart
                    salaryPayRaiseHistoryChart.classList.remove('d-none');
                    salaryPayRaiseHistoryChart.classList.add('d-block');
                    hideSalaryPayRaiseHistoryModalLabel.innerHTML = "Hide";
                    showSalaryPayRaiseHistoryChartBtns[0].classList.remove('fa-eye');
                    showSalaryPayRaiseHistoryChartBtns[0].classList.add('fa-eye-slash');
                } else {
                    // Hide the chart
                    salaryPayRaiseHistoryChart.classList.remove('d-block');
                    salaryPayRaiseHistoryChart.classList.add('d-none');
                    hideSalaryPayRaiseHistoryModalLabel.innerHTML = "Show";
                    showSalaryPayRaiseHistoryChartBtns[0].classList.remove('fa-eye-slash');
                    showSalaryPayRaiseHistoryChartBtns[0].classList.add('fa-eye');
                }
            }

            // Add event listeners to each button
            showSalaryPayRaiseHistoryChartBtns.forEach(btn => {
                btn.addEventListener('click', toggleSalaryPayRaiseHistory);
            })
        </script>
</body>

</html>