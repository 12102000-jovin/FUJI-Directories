<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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

if (($role === "general" || $role === "modify 1") && $employee_payroll_type === "salary" && $employeeId != $loginEmployeeId) {
    echo "<script>
    window.location.href = 'http://$serverAddress/$projectName/access_restricted.php';
    </script>";
    exit; // Ensure script execution stops after redirect
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
                                folders.folder_id,
                                users_groups.role
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

//  ========================= E M P L O Y E E S ( A L L O W A N C E S ) [ T O O L ]  =========================
// SQL to get the allowances data
$employee_tool_allowance_sql = "SELECT * FROM allowances WHERE allowance = 'Tool'";
$employee_tool_allowance_result = $conn->query($employee_tool_allowance_sql);

$toolAllowanceData = []; // Initialize an empty array to store results

if ($employee_tool_allowance_result->num_rows > 0) {
    while ($row = $employee_tool_allowance_result->fetch_assoc()) {
        $toolAllowanceData[] = $row;
    }
}

//  ========================= E M P L O Y E E S ( A L L O W A N C E S ) [ F I R S T  A I D ]  =========================
// SQL to get the allowances data
$employee_first_aid_allowance_sql = "SELECT * FROM allowances WHERE allowance = 'First Aid'";
$employee_first_aid_allowance_result = $conn->query($employee_first_aid_allowance_sql);

$firstAidAllowanceData = []; // Initialize an empty array to store results

if ($employee_first_aid_allowance_result->num_rows > 0) {
    while ($row = $employee_first_aid_allowance_result->fetch_assoc()) {
        $firstAidAllowanceData[] = $row;
    }
}


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

        // Fetch first name and last name from the database
        $empQuery = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
        $empStmt = $conn->prepare($empQuery);
        $empStmt->bind_param("i", $profileImageToDeleteEmpId);
        $empStmt->execute();
        $empStmt->bind_result($firstName, $lastName);
        $empStmt->fetch();
        $empStmt->close();

        // Ensure first and last names are not null
        $firstName = $firstName ?? 'Unknown';
        $lastName = $lastName ?? 'Unknown';

        // Process the uploaded file
        $imageExtension = pathinfo($profileImage["name"], PATHINFO_EXTENSION);
        $newFileName = "01 - Employee Photo (" . $profileImageToDeleteEmpId . " " . $firstName . " " . $lastName . ")." . $imageExtension;
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['policiesToSubmit']) && $_FILES['policiesToSubmit']['error'] === UPLOAD_ERR_OK) {
    // Sanitize and assign POST variables
    $empIdToAddPolicy = htmlspecialchars($_POST['empIdToAddPolicy']);
    $empNameToAddPolicy = htmlspecialchars($_POST['empNameToAddPolicy']);
    $selectedPolicyTypeToAdd = htmlspecialchars($_POST['selectedPolicyTypeToAdd']);
    $addPolicyDate = htmlspecialchars($_POST['addPolicyDate']);
    $policiesToSubmit = $_FILES['policiesToSubmit'];

    // Prepare and execute the query to get payroll type
    $payroll_type_sql = "SELECT payroll_type FROM employees WHERE employee_id = ?";
    if ($payroll_type_result = $conn->prepare($payroll_type_sql)) {
        $payroll_type_result->bind_param("s", $empIdToAddPolicy);
        $payroll_type_result->execute();
        $payroll_type_result->bind_result($emp_payroll_type);

        if ($payroll_type_result->fetch()) {
            if ($emp_payroll_type === "wage") {
                // Define policy folder path for wage staff
                $policyFolder = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$empIdToAddPolicy\\00 - Employee Documents\\02 - Policies\\";
            } else if ($emp_payroll_type === "salary") {
                // Define policy folder path for salary staff
                $policyFolder = "D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\$empIdToAddPolicy\\00 - Employee Documents\\02 - Policies\\";
            } else {
                echo "Invalid payroll type for the employee.";
                exit;
            }
        } else {
            echo "Employee not found.";
            exit;
        }

        // Close the prepared statement
        $payroll_type_result->close();
    } else {
        echo "Error preparing the query.";
        exit;
    }

    // Ensure the directory exists and has proper permissions
    if (!file_exists($policyFolder)) {
        if (!mkdir($policyFolder, 0777, true)) {
            echo "Failed to create directory. Please check folder permissions.";
            exit;
        }
    }

    // Retrieve file name and check for errors
    $fileName = $policiesToSubmit['name'];
    if ($policiesToSubmit['error'] === UPLOAD_ERR_OK) {
        // Construct the file name based on policy and employee info
        $formattedDate = date("Y-m-d", strtotime($addPolicyDate));
        $fileBaseName = $formattedDate . '-' . $selectedPolicyTypeToAdd . ' (' . $empIdToAddPolicy . ' ' . $empNameToAddPolicy . ') Signed';
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uploadedFilePath = $policyFolder . $fileBaseName . '.' . $fileExtension;  // Add extension to the filename

        // Move the uploaded file to the target directory
        if (move_uploaded_file($policiesToSubmit['tmp_name'], $uploadedFilePath)) {
            echo "<script> alert('Policy uploaded successfully.') </script>";
        } else {
            echo "Error uploading policy '$fileName'.<br>";
        }
    } else {
        echo "Error uploading policy '$fileName'.<br>";
    }
}

// ========================= A D D  M A C H I N E  C O M P E T E N C Y =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['machineCompetenciesToSubmit']) && $_FILES['machineCompetenciesToSubmit']['error'] === UPLOAD_ERR_OK) {
    // Sanitize and assign POST variables
    $empIdToAddMachineCompetency = htmlspecialchars($_POST['empIdToAddMachineCompetency']);
    $empNameToAddMachineCompetency = htmlspecialchars($_POST['empNameToAddMachineCompetency']);
    $selectedMachineCompetencyTypeToAdd = htmlspecialchars($_POST['selectedMachineCompetencyTypeToAdd']);
    $addMachineCompetencyDate = htmlspecialchars(($_POST['addMachineCompetencyDate']));
    $machineCompetenciesToSubmit = $_FILES['machineCompetenciesToSubmit'];

    // Prepare and execute the query to get the payroll type
    $payroll_type_sql = "SELECT payroll_type FROM employees WHERE employee_id = ?";
    if ($payroll_type_result = $conn->prepare($payroll_type_sql)) {
        $payroll_type_result->bind_param("s", $empIdToAddMachineCompetency);
        $payroll_type_result->execute();
        $payroll_type_result->bind_result($emp_payroll_type);

        if ($payroll_type_result->fetch()) {
            if ($emp_payroll_type === "wage") {
                // Define policy folder path for wage staff
                $machineCompetencyFolder = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$empIdToAddMachineCompetency\\01 - Induction and Training Documents\\";
            } else if ($emp_payroll_type === "salary") {
                // Define policy folder path for salary staff
                $machineCompetencyFolder = "D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\$empIdToAddMachineCompetency\\01 - Induction and Training Documents\\";
            } else {
                echo "Invalid payroll type for the employee.";
                exit;
            }
        } else {
            echo "Employee not found.";
            exit;
        }
        $payroll_type_result->close();
    }

    // Ensure the directory exists and has proper permissions
    if (!file_exists($machineCompetencyFolder)) {
        if (!mkdir($machineCompetencyFolder, 0777, true)) {
            echo "Failed to create directory. Please check folder permissions.";
            exit;
        }
    }

    // Retrieve file name and check for errors
    $fileName = $machineCompetenciesToSubmit['name'];
    if ($machineCompetenciesToSubmit['error'] === UPLOAD_ERR_OK) {
        // Construct the file name based on Machine Competency and employee info
        $formattedDate = date("Y-m-d", strtotime($addMachineCompetencyDate));
        $fileBaseName = $formattedDate . '-' . $selectedMachineCompetencyTypeToAdd . ' (' . $empIdToAddMachineCompetency . ' ' . $empNameToAddMachineCompetency . ') Signed';
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uploadedFilePath = $machineCompetencyFolder . $fileBaseName . '.' . $fileExtension;  // Add extension to the filename

        // Move the uploaded file to the target directory
        if (move_uploaded_file($machineCompetenciesToSubmit['tmp_name'], $uploadedFilePath)) {
        } else {
            echo "Error uploading machineCompetency '$fileName'.<br>";
        }
    } else {
        echo "Error uploading machineCompetency '$fileName'.<br>";
    }
}

// ========================= A D D  L E A V E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['leaveToSubmit'])) {
    // Sanitise and assign POST variables
    $empIdToAddLeave = htmlspecialchars($_POST['empIdToAddLeave']);
    $empNameToAddLeave = htmlspecialchars($_POST['empNameToAddLeave']);
    $selectedLeaveTypeToAdd = htmlspecialchars($_POST['selectedLeaveTypeToAdd']);
    $addLeaveDate = htmlspecialchars($_POST['addLeaveDate']);
    $leaveToSubmit = $_FILES['leaveToSubmit'];

    // Prepare and execute the query to get payroll type
    $payroll_type_sql = "SELECT payroll_type FROM employees WHERE employee_id = ?";
    if ($payroll_type_result = $conn->prepare($payroll_type_sql)) {
        $payroll_type_result->bind_param("s", $empIdToAddLeave);
        $payroll_type_result->execute();
        $payroll_type_result->bind_result($emp_payroll_type);

        if ($payroll_type_result->fetch()) {
            if ($emp_payroll_type === "wage") {
                if ($selectedLeaveTypeToAdd === "personalLeave" || $selectedLeaveTypeToAdd === "medicalCertificate") {
                    // Define leave folder path for wage staff
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$empIdToAddLeave\\04 - Leave\\00 - Personal Leave\\";
                } else if ($selectedLeaveTypeToAdd === "annualLeave") {
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$empIdToAddLeave\\04 - Leave\\01 - Annual Leave\\";
                } else if ($selectedLeaveTypeToAdd === "workingFromHome") {
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$empIdToAddLeave\\04 - Leave\\02 - Working from Home\\";
                } else if ($selectedLeaveTypeToAdd === "longServiceLeave") {
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$empIdToAddLeave\\04 - Leave\\03 - Long Service Leave\\";
                }
            } else if ($emp_payroll_type === "salary") {
                if ($selectedLeaveTypeToAdd === "personalLeave" || $selectedLeaveTypeToAdd === "medicalCertificate") {
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\$empIdToAddLeave\\04 - Leave\\00 - Personal Leave\\";
                } else if ($selectedLeaveTypeToAdd === "annualLeave") {
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\$empIdToAddLeave\\04 - Leave\\01 - Annual Leave\\";
                } else if ($selectedLeaveTypeToAdd === "workingFromHome") {
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\$empIdToAddLeave\\04 - Leave\\02 - Working from Home\\";
                } else if ($selectedLeaveTypeToAdd === "longServiceLeave") {
                    $leaveFolder = "D:\\FSMBEH-Data\\09 - HR\\05 - Salary Staff\\$empIdToAddLeave\\04 - Leave\\03 - Long Service Leave\\";
                }
            } else {
                echo "Invalid payroll type for the employee.";
                exit;
            }
        } else {
            echo "Employee not found.";
            exit;
        }
        $payroll_type_result->close();
    }



    // Ensure the directory exists and has proper permissions
    if (!file_exists($leaveFolder)) {
        if (!mkdir($leaveFolder, 0777, true)) {
            echo "Failed to create directory. Please check folder permissions.";
            exit;
        }
    }

    // Retrieve file name and check for errors
    $fileName = $leaveToSubmit['name'];
    if ($leaveToSubmit['error'] === UPLOAD_ERR_OK) {
        // Construct the file name based on Leave and employee info
        $formattedDate = date("Y-m-d", strtotime($addLeaveDate));

        // Set the file base name based on selected leave type
        if ($selectedLeaveTypeToAdd === "personalLeave" || $selectedLeaveTypeToAdd === "annualLeave" || $selectedLeaveTypeToAdd === "longServiceLeave") {
            // For personal, annual, or long service leave
            $fileBaseName = $formattedDate . '-09-HR-FO-007' . ' (' . $empIdToAddLeave . ' ' . $empNameToAddLeave . ') Signed';
        } elseif ($selectedLeaveTypeToAdd === "workingFromHome") {
            // For working from home leave
            $fileBaseName = $formattedDate . '-09-HR-FO-014' . ' (' . $empIdToAddLeave . ' ' . $empNameToAddLeave . ') Signed';
        } elseif ($selectedLeaveTypeToAdd === "medicalCertificate") {
            // For medical certificate leave
            $fileBaseName = $formattedDate . '-Doc-Cert (' . $empIdToAddLeave . ' ' . $empNameToAddLeave . ') Signed';
        } else {
            echo "Invalid leave type selected.";
            exit;
        }

        // Get file extension
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        // Define the full file path
        $uploadedFilePath = $leaveFolder . $fileBaseName . '.' . $fileExtension;  // Add extension to the filename

        // Move the uploaded file to the target directory
        if (move_uploaded_file($leaveToSubmit['tmp_name'], $uploadedFilePath)) {
            // File upload successful
        } else {
            echo "Error uploading leave '$fileName'.<br>";
        }
    } else {
        echo "Error uploading leave '$fileName'.<br>";
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
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId . '");</script>';
        exit();
    } else {
        $error_message = "Error: " . $activate_employee_result . "<br>" . $conn->error;
    }
}

//  ========================= D E A C T I V A T E  E M P L O Y E E ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["employeeIdToDeactivate"])) {
    $employeeIdToDeactivate = $_POST['employeeIdToDeactivate'];
    $lastDate = $_POST['lastDate'];

    $deactivate_employee_sql = "UPDATE employees SET is_active = 0, last_date = ? WHERE employee_id = ?";
    $deactivate_employee_result = $conn->prepare($deactivate_employee_sql);
    $deactivate_employee_result->bind_param("si", $lastDate, $employeeIdToDeactivate);

    // Execute the prepared statement
    if ($deactivate_employee_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId . '");</script>';
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

            /* Remove shadow on specific container */
            body .shadow-lg {
                box-shadow: none !important;
            }

            .hide-print {
                display: none !important;
            }

            .row-print-2col {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .row-print-2col>div {
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Create a class for long text fields */
            .long-text {
                grid-column: span 2 !important;
                /* Span across 2 columns */
            }

            .print-table {
                display: table !important;
                background: none !important;
                width: 100% !important;
                position: relative;
                margin: auto;
                margin-top: 20px;
            }

            .print-2col-wrapper {
                display: flex;
                flex-direction: row;
                gap: 20px;
                /* some space between columns */
            }

            .column-section {
                width: 50%;
                border: 2px solid black;
                border-radius: 10px;
                margin-top: 15px !important;
            }

            .column-section>div {
                margin: 0px !important;
            }

            h5 {
                font-size: 0.8rem !important;
            }

            small {
                font-size: 0.6rem !important;
            }

            #chartContainer,
            #chartContainer2 {
                page-break-before: always;
                /* Forces a page break before the chart */
                page-break-inside: avoid;
                /* Prevents splitting of the chart */
                margin: 0px !important;
                padding: 0px !important;
                height: 240px !important;
                width: 95% !important;
            }

            .currentWagePrint,
            .currentSalaryPrint {
                margin: 0px !important;
                display: table !important;
            }

            .chart-border {
                border: 2px solid black !important;
                border-top-left-radius: 10px;
                border-top-right-radius: 10px;
            }

            .card .table-padding {
                padding: 0px !important;
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
                        indexLabelFontSize: 12, // Font size for labels
                        indexLabelFontColor: "#555", // Label color
                        indexLabelPlacement: "outside", // Place label outside marker
                        indexLabel: "${y}", // Show Y values as labels
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
                        indexLabelFontSize: 12, // Font size for labels
                        indexLabelFontColor: "#555", // Label color
                        indexLabelPlacement: "outside", // Place label outside marker
                        indexLabel: "${y}", // Show Y values as labels
                        dataPoints: <?php echo json_encode($salariesDataPoints, JSON_NUMERIC_CHECK); ?>
                    }]
                });

                chart.render();
                chart2.render();

                // Listen for the print event to adjust the chart size
                window.addEventListener("beforeprint", function () {
                    chart.options.height = 250; // Fixed height in pixels
                    chart2.options.height = 250; // Fixed height in pixels
                    chart.options.width = 600;  // Fixed width in pixels
                    chart2.options.width = 600; // Fixed width in pixels
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
    <div class="hide-print">
        <?php require_once("../Menu/NavBar.php") ?>
    </div>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="hide-print">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a
                            href="http://<?php echo $serverAddress ?>/<?php echo $projectName ?>/Pages/index.php">Home</a>
                    </li>
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
        </div>
        <div class="row">
            <div class="col-lg-8">
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
                        $dietaryRestrictions = $row['dietary_restrictions'];
                        $lastDate = $row['last_date'];
                        $workShift = $row['work_shift'];
                        $lockerNumber = $row['locker_number'];
                        $permanentDate = $row['permanent_date'];
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
                        $personalEmail = $row['personal_email'];
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
                        $toolAllowance = $row['tool_allowance'];
                        $firstAidAllowance = $row['first_aid_allowance'];
                        $teamLeaderAllowance = $row['team_leader_allowance'];
                        $teamLeaderAllowanceCheck = $row['team_leader_allowance_check'];
                        $trainerAllowance = $row['trainer_allowance'];
                        $trainerAllowanceCheck = $row['trainer_allowance_check'];
                        $supervisorAllowance = $row['supervisor_allowance'];
                        $supervisorAllowanceCheck = $row['supervisor_allowance_check'];
                        $painterAllowance = $row['painter_allowance'];
                        $painterAllowanceCheck = $row['painter_allowance_check'];
                        $machineMaintenanceAllowance = $row['machine_maintenance_allowance'];
                        $machineMaintenanceAllowanceCheck = $row['machine_maintenance_allowance_check'];
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
                                    <div class="signature-bg-color shadow-sm rounded-circle text-white d-flex justify-content-center align-items-center me-3"
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
                                                <?php echo (isset($firstName) && isset($lastName)) ? $firstName . " " . $lastName : "N/A"; ?>
                                                <div class="ms-2 hide-print">
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
                                    <small class="text-start">
                                        <?php echo (isset($positionName)) ? $positionName : "N/A" . " - " . ((isset($employeeId)) ? $employeeId : "N/A"); ?>
                                    </small>
                                </div>
                            </div>

                            <div class="hide-print">
                                <div class="d-flex flex-sm-row align-items-center mt-4 mt-sm-0">
                                    <button class="btn btn-secondary me-2" onclick="toggleAndPrint()">
                                        <i class="fa-solid fa-print"></i>
                                    </button>

                                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#editProfileModal"
                                            id="editProfileBtn">
                                            Edit Profile <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="print-2col-wrapper">
                        <div class="column-section">
                            <div class="p-3 mt-4 bg-white rounded shadow-lg">
                                <div class="p-3">
                                    <p class="fw-bold signature-color">Personal Information</p>
                                    <div class="row row-print-2col">
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
                                            <h5 class="fw-bold">
                                                <?php echo isset($dob) ? date("j F Y", strtotime($dob)) : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column long-text">
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

                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Dietary Restrictions</small>
                                            <h5 class="fw-bold">
                                                <?php echo !empty($dietaryRestrictions) ? htmlspecialchars($dietaryRestrictions) : "N/A"; ?>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="column-section">
                            <div class="p-3 mt-4 bg-white rounded shadow-lg">
                                <div class="p-3">
                                    <p class="fw-bold signature-color">Contacts</p>
                                    <div class="row row-print-2col">
                                        <div class="col-lg-6 col-xl-12 d-flex flex-column long-text">
                                            <small>Address</small>
                                            <h5 class="fw-bold">
                                                <?php echo (isset($address) && $address !== "" ? $address : "N/A"); ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-4 col-xl-4 d-flex flex-column long-text">
                                            <small>Email</small>
                                            <h5 class="fw-bold text-break"><?php echo isset($email) ? $email : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-4 col-xl-4 d-flex flex-column long-text">
                                            <small>Personal Email</small>
                                            <h5 class="fw-bold text-break">
                                                <?php echo isset($personalEmail) && $personalEmail != NULL ? $personalEmail : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Phone Number</small>
                                            <h5 class="fw-bold"><?php echo isset($phoneNumber) ? $phoneNumber : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Plate Number</small>
                                            <h5 class="fw-bold"><?php echo isset($plateNumber) ? $plateNumber : "N/A"; ?>
                                            </h5>
                                        </div>
                                    </div>

                                    <p class="fw-bold signature-color mt-4 mt-emergency-contact">Emergency Contact</p>
                                    <div class="row row-print-2col">
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
                                            <h5 class="fw-bold">
                                                <?php echo isset($emergencyContact) ? $emergencyContact : "N/A"; ?>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="print-2col-wrapper">
                        <div class="column-section">
                            <div class="p-3 mt-4 bg-white rounded shadow-lg">
                                <div class="p-3">
                                    <p class="fw-bold signature-color">Employment Details</p>
                                    <div class="row row-print-2col">
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
                                            <small>Permanent Date</small>
                                            <h5 class="fw-bold">
                                                <?php echo isset($permanentDate) ? date("j F Y", strtotime($permanentDate)) : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Department</small>
                                            <h5 class="fw-bold">
                                                <?php echo isset($departmentName) ? $departmentName : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Section</small>
                                            <h5 class="fw-bold"><?php echo isset($section) ? $section : "N/A"; ?></h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Employment Type</small>
                                            <h5 class="fw-bold">
                                                <?php echo isset($employmentType) ? $employmentType : "N/A"; ?>
                                            </h5>
                                        </div>

                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Position</small>
                                            <h5 class="fw-bold"><?php echo isset($positionName) ? $positionName : "N/A"; ?>
                                            </h5>
                                        </div>

                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Payroll Type</small>
                                            <h5 class="fw-bold">
                                                <?php echo isset($payrollType) ? ucwords($payrollType) : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column <?php if (isset($lastDate)) {
                                            echo "bg-danger text-white";
                                        } ?> rounded-2">
                                            <small>Last Date</small>
                                            <h5 class="fw-bold rounded-2">
                                                <?php echo !empty($lastDate) ? date("j F Y", strtotime($lastDate)) : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Work Shift</small>
                                            <h5 class="fw-bold rounded-2">
                                                <?php echo isset($workShift) ? $workShift : "N/A"; ?>
                                            </h5>
                                        </div>
                                        <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                            <small>Locker Number</small>
                                            <h5 class="fw-bold rounded-2">
                                                <?php echo isset($lockerNumber) ? $lockerNumber : "N/A"; ?>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="column-section">
                            <div class="p-3 mt-4 bg-white rounded shadow-lg">
                                <div class="p-3">
                                    <p class="fw-bold signature-color">Banking, Super and Tax Details</p>
                                    <div class="row row-print-2col">
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
                                            <h5 class="fw-bold"><?php echo isset($accountNumber) ? $accountNumber : "N/A" ?>
                                            </h5>
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
                                            <h5 class="fw-bold"><?php echo isset($taxFileNumber) ? $taxFileNumber : "N/A" ?>
                                            </h5>
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
                    </div>
                </div>

                <div class="col-lg-4">
                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                        <div class="card bg-white border-0 rounded shadow-lg mt-4 mt-lg-0">
                            <div class="p-3 hide-print">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="fw-bold signature-color mb-0 pb-0">Files</p>
                                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                                        <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#uploadEmployeeFilesModal"><i
                                                class="fa-solid fa-plus me-1"></i>Upload</button>
                                    <?php } ?>
                                </div>
                                <!-- 00 - Employee Documents -->
                                <div class="d-flex justify-content-center">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <div class="col-auto d-flex align-items-center">
                                                <span class="folder-icon tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Open Folder">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=00 - Employee Documents&payrollType=<?= $payrollType ?>"
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=00 - Employee Documents&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            00 - Employee Documents
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="pay-review-directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\00 - Employee Documents" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\00 - Employee Documents"; ?>">
                                                            <button id="copy-button" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 01 - Induction and Training Documents-->
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="row col-12 p-2 background-color rounded shadow-sm">
                                        <div class="col-auto d-flex align-items-center">
                                            <div class="col-auto d-flex align-items-center">
                                                <span class="folder-icon tooltips" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Open Folder">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=01 - Induction and Training Documents&payrollType=<?= $payrollType ?>"
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=01 - Induction and Training Documents&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            01 - Induction and Training Documents
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="pay-review-directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\01 - Induction and Training Documents" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\01 - Induction and Training Documents" ?>">
                                                            <button id="copy-button" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=02 - Resume, ID and Qualifications&payrollType=<?= $payrollType ?>"
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=02 - Resume, ID and Qualifications&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            02 - Resume, ID and Qualifications
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="annual-leaves-directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\02 - Resume, ID and Qualifications" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\02 - Resume, ID and Qualifications" ?>">
                                                            <button id="copy-button-annual" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=03 - Accounts&payrollType=<?= $payrollType ?>"
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=03 - Accounts&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            03 - Accounts
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="annual-leaves-directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\03 - Accounts" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\03 - Accounts" ?>">
                                                            <button id="copy-button-annual" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
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
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=04 - Leave&payrollType=<?= $payrollType ?>"
                                                        target="_blank"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                            class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                    </a>
                                                </div>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=04 - Leave&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            04 - Leave
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\04 - Leave" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\04 - Leave" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
                                                        </div>
                                                    </span>
                                                </div>
                                                <?php if ($role === "full control") { ?>
                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                                                        data-bs-target="#addLeaveModal"><i
                                                            class="fa-solid fa-plus me-1"></i>Upload</button>
                                                <?php } ?>
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
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=05 - HR Actions&payrollType=<?= $payrollType ?>"
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=05 - HR Actions&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            05 - HR Actions
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\05 - HR Actions" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\05 - HR Actions" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
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
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=06 - Work Compensation&payrollType=<?= $payrollType ?>"
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=06 - Work Compensation&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            06 - Work Compensation
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\06 - Work Compensation" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\06 - Work Compensation" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
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
                                                    <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=07 - Exit Information&payrollType=<?= $payrollType ?>"
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
                                                        <a href="../open-folder.php?employee_id=<?= $employeeId ?>&folder=07 - Exit Information&payrollType=<?= $payrollType ?>"
                                                            target="_blank"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                                            07 - Exit Information
                                                        </a>
                                                    </div>
                                                    <span>
                                                        <div class="d-flex align-items-center">
                                                            <small id="directory-path" class="me-1 text-break"
                                                                style="color:#b1b1b1"><?php echo "$employeeId\07 - Exit Information" ?></small>
                                                            <!-- <input type="hidden"
                                                                value="<?php echo "D:\\FSMBEH-Data\\09 - HR\\04 - Wage Staff\\$employeeId\\07 - Exit Information" ?>">
                                                            <button id="copy-button-policies" class="btn rounded btn-sm"
                                                                onclick="copyDirectoryPath(this)"><i
                                                                    class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                                <small class="text-primary">Copy</small>
                                                            </button> -->
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php } ?>

                    <!-- ================= Pay Raise History Chart (Wage) ================= -->
                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                        <?php $latestWage = !empty($wagesData) ? $wagesData[array_key_last($wagesData)]['amount'] : 0; ?>
                        <div
                            class="card bg-white border-0 rounded shadow-lg mt-4 <?php echo ($payrollType === "wage") ? 'd-block' : 'd-none'; ?>">
                            <div class="p-3 chart-border">
                                <div class="hide-print">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="fw-bold signature-color mb-0 d-flex align-items-center">
                                            Pay Raise History
                                            <span class="badge rounded-pill signature-btn mx-1">Wage</span>
                                            <span class="mx-2 hideWageSalaryEdit">|</span>
                                            <i class="fa-solid fa-eye text-danger me-1 showWagePayRaiseHistoryChartBtn hideWageSalaryEdit"
                                                role="button"></i>
                                            <small
                                                class="pe-2 fw-bold text-decoration-underline showWagePayRaiseHistoryChartBtn hideWageSalaryEdit">
                                                <a role="button" id="hideWagePayRaiseHistoryModalLabel">Show</a>
                                            </small>
                                        </p>

                                        <?php if ($role === "full control" || $role === "modify 1") { ?>
                                            <i id="payRaiseEditIconWage" role="button"
                                                class="fa-regular fa-pen-to-square signature-color hideWageSalaryEdit"
                                                data-bs-toggle="modal" data-bs-target="#wagePayRaiseHistoryModal"></i>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="px-4 py-2">
                                    <div id="chartContainer" style="height: 300px; width: 100%;" class="d-block"></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- ================= Pay Raise History Chart (Salary) ================= -->
                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                        <?php $latestSalary = !empty($salariesData) ? $salariesData[array_key_last($salariesData)]['amount'] : 0; ?>
                        <div
                            class="card bg-white border-0 rounded shadow-lg mt-4 <?php echo ($payrollType === "salary") ? 'd-block' : 'd-none'; ?>">
                            <div class="p-3 chart-border">
                                <div class="hide-print">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="fw-bold signature-color mb-0 d-flex align-items-center">
                                            Pay Raise History
                                            <span class="badge rounded-pill signature-btn mx-1">Salary</span>
                                            <span class="mx-2 hideWageSalaryEdit">|</span>
                                            <i class="fa-solid fa-eye text-danger me-1 showSalaryPayRaiseHistoryChartBtn hideWageSalaryEdit"
                                                role="button"></i>
                                            <small
                                                class="pe-2 fw-bold text-decoration-underline showSalaryPayRaiseHistoryChartBtn hideWageSalaryEdit">
                                                <a role="button" id="hideSalaryPayRaiseHistoryModalLabel">Show</a>
                                            </small>
                                        </p>

                                        <?php if ($role === "full control") { ?>
                                            <i id="payRaiseEditIconSalary" role="button"
                                                class="fa-regular fa-pen-to-square signature-color hideWageSalaryEdit"
                                                data-bs-toggle="modal" data-bs-target="#salaryPayRaiseHistoryModal"></i>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="px-4 py-2">
                                    <div id="chartContainer2" style="height: 300px; width: 100%;" class="d-block"></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($payrollType === "wage") { ?>
                        <div class="currentWagePrint" style="display:none; width: 100%;">
                            <table class="table table-hover table-bordered"
                                style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black; border-top:none;">
                                <tr class="text-center">
                                    <td class="bg-dark text-white fw-bold col-5"
                                        style="border-width:2px; border-style:solid; border-top:none;">
                                        Current Wage
                                    </td>
                                    <td class="bg-dark text-white fw-bold col-5"
                                        style="border-width:2px; border-style:solid; border-top:none;">
                                        $<?php echo number_format($latestWage, 2); ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    <?php } else if ($payrollType === "salary") { ?>
                            <div class="currentSalaryPrint" style="display:none; width: 100%">
                                <table class="table table-hover table-bordered"
                                    style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black; border-top:none;">
                                    <tr class="text-center">
                                        <td class="bg-dark text-white fw-bold col-5"
                                            style="border-width:2px; border-style:solid; border-top:none;">Current Salary</td>
                                        <td class="bg-dark text-white fw-bold col-5"
                                            style="border-width:2px; border-style:solid; border-top:none;">
                                            $<?php echo number_format($latestSalary, 2); ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                    <?php } ?>
                    <!-- ======================================= A L L O W A N C E  T A B L E ( P R I N T) ======================================= -->
                    <?php if ($payrollType === "wage") { ?>
                        <div style="display: none">
                            <table class="table table-hover table-bordered mb-0 pb-0">
                                <p class="fw-bold signature-color">Allowances</p>
                                <thead class="table-primary">
                                    <tr class="text-center">
                                        <th class="py-3">Allowances</th>
                                        <th class="py-3">Amount</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    // Initialize total variable
                                    $totalAllowances = 0;

                                    // Tool Allowance
                                    $toolAmount = isset($toolAllowanceData[0]['amount']) && $toolAllowance == 1 ? $toolAllowanceData[0]['amount'] : 0;
                                    $totalAllowances += $toolAmount;
                                    ?>
                                    <tr class="text-center">
                                        <td class="col-5">Tool</td>
                                        <td class="col-5"><?php echo '$' . number_format($toolAmount, 2); ?></td>
                                    </tr>

                                    <?php
                                    // First Aid Allowance
                                    $firstAidAmount = isset($firstAidAllowanceData[0]['amount']) && $firstAidAllowance == 1 ? $firstAidAllowanceData[0]['amount'] : 0;
                                    $totalAllowances += $firstAidAmount;
                                    ?>
                                    <tr class="text-center">
                                        <td class="col-5">First Aid</td>
                                        <td class="col-5"><?php echo '$' . number_format($firstAidAmount, 2); ?></td>
                                    </tr>

                                    <?php
                                    // Team Leader Allowance
                                    $teamLeaderAmount = isset($teamLeaderAllowance) && $teamLeaderAllowanceCheck == 1 ? $teamLeaderAllowance : 0;
                                    $totalAllowances += $teamLeaderAmount;
                                    ?>
                                    <tr class="text-center">
                                        <td class="col-5">Team Leader</td>
                                        <td class="col-5"><?php echo '$' . number_format($teamLeaderAmount, 2); ?></td>
                                    </tr>

                                    <?php
                                    // Trainer Allowance
                                    $trainerAmount = isset($trainerAllowance) && $trainerAllowanceCheck == 1 ? $trainerAllowance : 0;
                                    $totalAllowances += $trainerAmount;
                                    ?>
                                    <tr class="text-center">
                                        <td class="col-5">Trainer</td>
                                        <td class="col-5"><?php echo '$' . number_format($trainerAmount, 2); ?></td>
                                    </tr>

                                    <?php
                                    // Supervisor Allowance
                                    $supervisorAmount = isset($supervisorAllowance) && $supervisorAllowanceCheck == 1 ? $supervisorAllowance : 0;
                                    $totalAllowances += $supervisorAmount;
                                    ?>
                                    <tr class="text-center">
                                        <td class="col-5">Supervisor</td>
                                        <td class="col-5"><?php echo '$' . number_format($supervisorAmount, 2); ?></td>
                                    </tr>

                                    <?php
                                    // Painter Allowance
                                    $painterAmount = isset($painterAllowance) && $painterAllowanceCheck == 1 ? $painterAllowance : 0;
                                    $totalAllowances += $painterAmount;
                                    ?>
                                    <tr class="text-center">
                                        <td class="col-5">Painter</td>
                                        <td class="col-5"><?php echo '$' . number_format($painterAmount, 2); ?></td>
                                    </tr>

                                    <?php
                                    // Machine Maintenance Allowance
                                    $machineMaintenanceAmount = isset($machineMaintenanceAllowance) && $machineMaintenanceAllowanceCheck == 1 ? $machineMaintenanceAllowance : 0;
                                    $totalAllowances += $machineMaintenanceAmount;
                                    ?>
                                    <tr class="text-center">
                                        <td class="col-5">Machine Maintenance</td>
                                        <td class="col-5"><?php echo '$' . number_format($machineMaintenanceAmount, 2); ?></td>
                                    </tr>
                                    <tr class="text-center">
                                        <td class="bg-dark text-white fw-bold">Total Allowances</td>
                                        <td class="bg-dark text-white fw-bold col-5">
                                            <?php echo '$' . number_format($totalAllowances, 2); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>

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
                    </div>

                    <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3 hide-print">
                            <p class="fw-bold signature-color">Access</p>
                            <?php
                            // Check if there are any results
                            if ($employee_group_access_result->num_rows > 0) {
                                $current_group_id = null;

                                // Initialize arrays to store unique group names, folder names, and roles
                                $unique_group_names = [];
                                $unique_folders = [];

                                // Fetch all rows from the result set
                                while ($row = $employee_group_access_result->fetch_assoc()) {
                                    $group_id = $row['group_id'];
                                    $group_name = htmlspecialchars($row['group_name']);
                                    $folder_id = htmlspecialchars($row['folder_id']);
                                    $folder_name = htmlspecialchars($row['folder_name']);
                                    $role_access = htmlspecialchars($row['role']); // Get role from the result set
                        
                                    // Capitalize the role
                                    $role_access = ucwords(strtolower($role_access)); // Capitalize the first letter of each word
                        
                                    // Collect unique group names and roles
                                    if (!isset($unique_group_names[$group_id])) {
                                        $unique_group_names[$group_id] = ['name' => $group_name, 'role' => $role_access];
                                    }

                                    // Collect unique folder names
                                    $unique_folders[$folder_id] = $folder_name;
                                }

                                // Output unique group names and roles in a table
                                if (!empty($unique_group_names)) {
                                    echo "<strong>Groups:</strong><br>";
                                    echo "<table class='table table-bordered'>";
                                    echo "<thead><tr><th>Group Name</th><th>Role</th></tr></thead>";
                                    echo "<tbody>";
                                    foreach ($unique_group_names as $group_id => $group_info) {
                                        echo "<tr><td>{$group_info['name']}</td><td>{$group_info['role']}</td></tr>";
                                    }
                                    echo "</tbody></table>";
                                    echo "<hr>";
                                }

                                // Output unique folder names
                                if (!empty($unique_folders)) {
                                    echo "<strong>Folders:</strong><br>";
                                    echo "<ul>";
                                    foreach ($unique_folders as $folder_id => $folder_name) {
                                        echo "<li>$folder_name</li>";
                                    }
                                    echo "</ul>";
                                }
                            } else {
                                echo '<p>No group or folder access found.</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3 table-padding">
                            <!-- Dropdown Toggle -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="fw-bold signature-color mb-0 pb-0" style="cursor: pointer;"
                                    data-bs-toggle="collapse" data-bs-target="#machineCompetencyContent"
                                    aria-expanded="false">
                                    Machine Competency
                                    <i class="fas fa-chevron-down hide-print"></i>
                                </p>

                                <div class="hide-print">
                                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                                        <button class="btn btn-success btn-sm fw-bold hide-print" data-bs-toggle="modal"
                                            data-bs-target="#addMachineCompetencyModal"><i
                                                class="fa-solid fa-plus me-1"></i>Upload</button>
                                    <?php } ?>
                                </div>
                            </div>
                            <!-- Collapsible Content -->
                            <div id="machineCompetencyContent" class="collapse print-table">
                                <?php require_once("../open-machine-competency-folder.php") ?>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3 table-padding">
                            <!-- Dropdown Toggle -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="fw-bold signature-color mb-0 pb-0" style="cursor: pointer;"
                                    data-bs-toggle="collapse" data-bs-target="#policiesContent" aria-expanded="false">
                                    Policies
                                    <i class="fas fa-chevron-down hide-print"></i>
                                </p>
                                <div class="hide-print">
                                    <?php if ($role === "full control" || $role === "modify 1") { ?>
                                        <button class="btn btn-success btn-sm fw-bold hide-print" data-bs-toggle="modal"
                                            data-bs-target="#addPoliciesModal"><i
                                                class="fa-solid fa-plus me-1"></i>Upload</button>
                                    <?php } ?>
                                </div>
                            </div>

                            <!-- Collapsible Content -->
                            <div id="policiesContent" class="collapse print-table">
                                <?php require_once("../open-policies-folder.php") ?>
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
                                    <?php
                                    // Initialise total wage with the latest wage amount
                                    $latestWage = !empty($wagesData) ? $wagesData[array_key_last($wagesData)]['amount'] : 0;

                                    ?>
                                    <div id="wageDetailTable">
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
                                                                        <span
                                                                            class="view-mode">$<?php echo $row['amount']; ?></span>
                                                                        <input type="text"
                                                                            class="form-control edit-mode d-none mx-auto"
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
                                                                                <i
                                                                                    class="fa-solid fa-trash-can text-danger m-1"></i>
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
                                                        <tr class="bg-dark">
                                                            <td colspan="2"
                                                                class="align-middle text-end bg-dark text-white fw-bold">
                                                                Current Wage </td>
                                                            <td class="align-middle text-center bg-dark text-white fw-bold">
                                                                $<?php echo number_format($latestWage, 2); ?></td>
                                                        </tr>
                                                    <?php } else { ?>
                                                        <tr class=" text-center align-middle">
                                                            <td colspan="3">No records found</td>
                                                        </tr>
                                                    <?php } ?>

                                                </tbody>
                                            </table>
                                        </div>

                                        <?php
                                        // Initialize total allowance
                                        $totalAllowance = 0;

                                        // Check if the tool allowance should be included
                                        $toolAllowanceChecked = isset($toolAllowance) && $toolAllowance == 1;

                                        // Check if the first aid allowance should be included
                                        $firstAidAllowanceChecked = isset($firstAidAllowance) && $firstAidAllowance == 1;

                                        // Check if the team leader allowance should be included
                                        $teamLeaderAllowanceChecked = isset($teamLeaderAllowanceCheck) && $teamLeaderAllowanceCheck == 1;

                                        // Check if the trainer allowance should be included
                                        $trainerAllowanceChecked = isset($trainerAllowanceCheck) && $trainerAllowanceCheck == 1;

                                        // Check if the supervisor allowance should be included
                                        $supervisorAllowanceChecked = isset($supervisorAllowanceCheck) && $supervisorAllowanceCheck == 1;

                                        // Check if the painter allowance should be included
                                        $painterAllowanceChecked = isset($painterAllowanceCheck) && $painterAllowanceCheck == 1;

                                        // Check if the machine maintenance allowance should be included
                                        $machineMaintenanceAllowanceChecked = isset($machineMaintenanceAllowanceCheck) && $machineMaintenanceAllowanceCheck == 1;


                                        // Add Tool Allowance if checked
                                        if ($toolAllowanceChecked) {
                                            $totalAllowance += isset($toolAllowanceData[0]['amount']) ? $toolAllowanceData[0]['amount'] : 0;
                                        }

                                        // Add First Aid Allowance if checked
                                        if ($firstAidAllowanceChecked) {
                                            $totalAllowance += isset($firstAidAllowanceData[0]['amount']) ? $firstAidAllowanceData[0]['amount'] : 0;
                                        }

                                        // Add Team Leader Allowance if checked
                                        if ($teamLeaderAllowanceChecked) {
                                            $totalAllowance += $teamLeaderAllowance;
                                        }

                                        // Add Trainer Allowance if checked
                                        if ($trainerAllowanceChecked) {
                                            $totalAllowance += $trainerAllowance;
                                        }

                                        // Add Supervisor Allowance if checked
                                        if ($supervisorAllowanceChecked) {
                                            $totalAllowance += $supervisorAllowance;
                                        }

                                        // Add Painter Allowance if checked
                                        if ($painterAllowanceChecked) {
                                            $totalAllowance += $painterAllowance;
                                        }

                                        // Add Machine Maintenance Allowance if checked
                                        if ($machineMaintenanceAllowanceChecked) {
                                            $totalAllowance += $machineMaintenanceAllowance;
                                        }
                                        ?>

                                        <div class="table-responsive rounded-3 shadow-lg bg-light mt-2">
                                            <table class="table m-0 p-0" data-bs-toggle="collapse"
                                                data-bs-target="#allowanceCollapse" aria-expanded="false"
                                                aria-controls="allowanceCollapse" style="cursor: pointer">
                                                <tr class="bg-dark rounded-3">
                                                    <td class="bg-dark"></td>
                                                    <td class="col-md-6 bg-dark text-white">
                                                        <div class="d-flex justify-content-end fw-bold">
                                                            Total Allowances
                                                        </div>
                                                    </td>
                                                    <td class="col-md-3 bg-dark">
                                                        <p class="mb-0 pb-0 fw-bold text-center text-white">
                                                            $<span id="currentTotalAllowance"
                                                                class="fw-bold"><?php echo number_format($totalAllowance, 2); ?></span>
                                                            <i class="fa-sharp fa-solid fa-caret-down"></i>
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div class="collapse" id="allowanceCollapse">
                                            <div class="table-responsive border rounded-3">
                                                <table class="table table-hover mb-0 pb-0">
                                                    <thead class="table-primary">
                                                        <tr class="text-center">
                                                            <th></th>
                                                            <th class="py-3">Allowances</th>
                                                            <th class="py-3">Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="text-center">
                                                            <td class="col-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    onchange="toolAllowanceCheckbox(this, <?php echo $employeeId ?>, <?php echo isset($toolAllowanceData[0]['amount']) ? $toolAllowanceData[0]['amount'] : 0; ?>)"
                                                                    <?php if (isset($toolAllowance) && $toolAllowance == 1) {
                                                                        echo 'checked';
                                                                    } ?>>
                                                            </td>
                                                            <td class="col-5">Tool</td>
                                                            <td class="col-5">
                                                                <?php echo isset($toolAllowanceData[0]['amount']) ? '$' . $toolAllowanceData[0]['amount'] : 'N/A'; ?>
                                                            </td>
                                                        </tr>
                                                        <tr class="text-center">
                                                            <td class="col-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="firstAidAllowanceCheckbox"
                                                                    onchange="firstAidAllowanceCheckbox(this, <?php echo $employeeId; ?>)"
                                                                    <?php echo isset($firstAidAllowance) && $firstAidAllowance == 1 ? 'checked' : ''; ?>>
                                                            </td>
                                                            <td class="col-5">First Aid</td>
                                                            <td class="col-5">
                                                                <?php echo isset($firstAidAllowanceData[0]['amount']) ? '$' . $firstAidAllowanceData[0]['amount'] : 'N/A'; ?>
                                                            </td>
                                                        </tr>
                                                        <tr class="text-center">
                                                            <td class="col-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="teamLeaderAllowanceCheckbox"
                                                                    onchange="teamLeaderAllowanceCheckbox(this, <?php echo $employeeId ?>)"
                                                                    <?php echo isset($teamLeaderAllowanceChecked) && $teamLeaderAllowanceChecked == 1 ? 'checked' : '' ?>>
                                                            </td>
                                                            <td class="col-5">Team Leader</td>
                                                            <td class="col-5">
                                                                <div
                                                                    class="view-mode-team-leader d-flex justify-content-center align-items-center">
                                                                    <p class="teamLeaderAllowanceAmount mt-1 mb-0 pb-0">
                                                                        $<?php echo isset($teamLeaderAllowance) ? number_format($teamLeaderAllowance, 2) : '00.00'; ?>
                                                                    </p>
                                                                    <i class="fa-regular fa-pen-to-square ms-2 signature-color tooltips"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                        title="Edit Team Leader Allowance" role="button"
                                                                        id="editTeamLeaderAllowanceBtn"></i>
                                                                </div>
                                                                <div
                                                                    class="d-flex align-items-center edit-mode-team-leader d-none">
                                                                    <input type="number" step="any"
                                                                        class="form-control mx-auto"
                                                                        name="teamLeaderAllowanceToEdit"
                                                                        value="<?php echo $teamLeaderAllowance ?>"
                                                                        style="width: 80%">
                                                                    <button class="btn btn-sm btn-success ms-1"
                                                                        id="saveTeamLeaderBtn"
                                                                        data-employee-id="<?php echo $employeeId ?>"
                                                                        data-team-leader-allowance>
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-check text-white m-1"></i>
                                                                            Save
                                                                        </div>
                                                                    </button>

                                                                    <button class="btn btn-sm btn-danger ms-1"
                                                                        id="cancelTeamLeaderEditBtn">
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-xmark text-white m-1"></i>
                                                                            Cancel
                                                                        </div>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        <tr class="text-center">
                                                            <td class="col-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="trainerAllowanceCheckbox"
                                                                    onchange="trainerAllowanceCheckbox(this, <?php echo $employeeId ?>)"
                                                                    <?php echo isset($trainerAllowanceChecked) && $trainerAllowanceChecked == 1 ? 'checked' : '' ?>>
                                                            </td>
                                                            <td class="col-5">Trainer</td>
                                                            <td class="col-5">
                                                                <div
                                                                    class="view-mode-trainer d-flex justify-content-center align-items-center">
                                                                    <p class="trainerAllowanceAmount mt-1 mb-0 pb-0">
                                                                        $<?php echo isset($trainerAllowance) ? number_format($trainerAllowance, 2) : '00.00'; ?>
                                                                    </p>
                                                                    <i class="fa-regular fa-pen-to-square ms-2 signature-color tooltips"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                        title="Edit Trainer Allowance" role="button"
                                                                        id="editTrainerAllowanceBtn"></i>
                                                                </div>
                                                                <div
                                                                    class="d-flex align-items-center edit-mode-trainer d-none">
                                                                    <input type="number" step="any"
                                                                        class="form-control mx-auto"
                                                                        name="trainerAllowanceToEdit"
                                                                        value="<?php echo $trainerAllowance ?>"
                                                                        style="width: 80%">
                                                                    <button class="btn btn-sm btn-success ms-1"
                                                                        id="saveTrainerBtn"
                                                                        data-employee-id="<?php echo $employeeId ?>"
                                                                        data-trainer-allowance>
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-check text-white m-1"></i>
                                                                            Save
                                                                        </div>
                                                                    </button>

                                                                    <button class="btn btn-sm btn-danger ms-1"
                                                                        id="cancelTrainerEditBtn">
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-xmark text-white m-1"></i>
                                                                            Cancel
                                                                        </div>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr class="text-center">
                                                            <td class="col-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="supervisorAllowanceCheckbox"
                                                                    onchange="supervisorAllowanceCheckbox(this, <?php echo $employeeId ?>)"
                                                                    <?php echo isset($supervisorAllowanceChecked) && $supervisorAllowanceChecked == 1 ? 'checked' : '' ?>>
                                                            </td>
                                                            <td class="col-5">Supervisor</td>
                                                            <td class="col-5">
                                                                <div
                                                                    class="view-mode-supervisor d-flex justify-content-center align-items-center">
                                                                    <p class="supervisorAllowanceAmount mt-1 mb-0 pb-0">
                                                                        $<?php echo isset($supervisorAllowance) ? number_format($supervisorAllowance, 2) : '00.00'; ?>
                                                                    </p>
                                                                    <i class="fa-regular fa-pen-to-square ms-2 signature-color tooltips"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                        title="Edit Supervisor Allowance" role="button"
                                                                        id="editSupervisorAllowanceBtn"></i>
                                                                </div>
                                                                <div
                                                                    class="d-flex align-items-center edit-mode-supervisor d-none">
                                                                    <input type="number" step="any"
                                                                        class="form-control mx-auto"
                                                                        name="supervisorAllowanceToEdit"
                                                                        value="<?php echo $supervisorAllowance ?>"
                                                                        style="width: 80%">
                                                                    <button class="btn btn-sm btn-success ms-1"
                                                                        id="saveSupervisorBtn"
                                                                        data-employee-id="<?php echo $employeeId ?>"
                                                                        data-supervisor-allowance>
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-check text-white m-1"></i>
                                                                            Save
                                                                        </div>
                                                                    </button>

                                                                    <button class="btn btn-sm btn-danger ms-1"
                                                                        id="cancelSupervisorEditBtn">
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-xmark text-white m-1"></i>
                                                                            Cancel
                                                                        </div>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr class="text-center">
                                                            <td class="col-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="painterAllowanceCheckbox"
                                                                    onchange="painterAllowanceCheckbox(this, <?php echo $employeeId ?>)"
                                                                    <?php echo isset($painterAllowanceChecked) && $painterAllowanceChecked == 1 ? 'checked' : '' ?>>
                                                            </td>
                                                            <td class="col-5">Painter</td>
                                                            <td class="col-5">
                                                                <div
                                                                    class="view-mode-painter d-flex justify-content-center align-items-center">
                                                                    <p class="painterAllowanceAmount mt-1 mb-0 pb-0">
                                                                        $<?php echo isset($painterAllowance) ? number_format($painterAllowance, 2) : '00.00'; ?>
                                                                    </p>
                                                                    <i class="fa-regular fa-pen-to-square ms-2 signature-color tooltips"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                        title="Edit Painter Allowance" role="button"
                                                                        id="editPainterAllowanceBtn"></i>
                                                                </div>
                                                                <div
                                                                    class="d-flex align-items-center edit-mode-painter d-none">
                                                                    <input type="number" step="any"
                                                                        class="form-control mx-auto"
                                                                        name="painterAllowanceToEdit"
                                                                        value="<?php echo $painterAllowance ?>"
                                                                        style="width: 80%">
                                                                    <button class="btn btn-sm btn-success ms-1"
                                                                        id="savePainterBtn"
                                                                        data-employee-id="<?php echo $employeeId ?>"
                                                                        data-painter-allowance>
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-check text-white m-1"></i>
                                                                            Save
                                                                        </div>
                                                                    </button>

                                                                    <button class="btn btn-sm btn-danger ms-1"
                                                                        id="cancelPainterEditBtn">
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-xmark text-white m-1"></i>
                                                                            Cancel
                                                                        </div>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr class="text-center">
                                                            <td class="col-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="machineMaintenanceAllowanceCheckbox"
                                                                    onchange="machineMaintenanceAllowanceCheckbox(this, <?php echo $employeeId ?>)"
                                                                    <?php echo isset($machineMaintenanceAllowanceChecked) && $machineMaintenanceAllowanceChecked == 1 ? 'checked' : '' ?>>
                                                            </td>
                                                            <td class="col-5">Machine Maintenance</td>
                                                            <td class="col-5">
                                                                <div
                                                                    class="view-mode-machine-maintenance d-flex justify-content-center align-items-center">
                                                                    <p
                                                                        class="machineMaintenanceAllowanceAmount mt-1 mb-0 pb-0">
                                                                        $<?php echo isset($machineMaintenanceAllowance) ? number_format($machineMaintenanceAllowance, 2) : '00.00'; ?>
                                                                    </p>
                                                                    <i class="fa-regular fa-pen-to-square ms-2 signature-color tooltips"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                        title="Edit Machine Maintenance Allowance"
                                                                        role="button"
                                                                        id="editMachineMaintenanceAllowanceBtn"></i>
                                                                </div>
                                                                <div
                                                                    class="d-flex align-items-center edit-mode-machine-maintenance d-none">
                                                                    <input type="number" step="any"
                                                                        class="form-control mx-auto"
                                                                        name="machineMaintenanceAllowanceToEdit"
                                                                        value="<?php echo $machineMaintenanceAllowance ?>"
                                                                        style="width: 80%">
                                                                    <button class="btn btn-sm btn-success ms-1"
                                                                        id="saveMachineMaintenanceBtn"
                                                                        data-employee-id="<?php echo $employeeId ?>"
                                                                        data-machine-maintenance-allowance>
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-check text-white m-1"></i>
                                                                            Save
                                                                        </div>
                                                                    </button>

                                                                    <button class="btn btn-sm btn-danger ms-1"
                                                                        id="cancelMachineMaintenanceEditBtn">
                                                                        <div class="d-flex justify-content-center"><i
                                                                                role="button"
                                                                                class="fa-solid fa-xmark text-white m-1"></i>
                                                                            Cancel
                                                                        </div>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-center">
                                            <button class="btn btn-secondary mt-4 me-1" data-bs-dismiss="modal"
                                                aria-label="Close">Close</button>
                                            <button class="btn btn-dark mt-4" id="showUpdateForm">Add Wage</button>
                                        </div>
                                    </div>

                                    <div class="d-none" id="updateWageForm">
                                        <button class="btn btn-sm btn-secondary" id="cancelUpdateWageBtn"> <i
                                                class="fa-solid fa-arrow-left me-1"></i>Cancel </button>
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
                                                        <label for="updateWageDate" class="form-label fw-bold">
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
                                                        <button type="submit" class="btn btn-dark rounded">Confirm</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
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
                                                    <label for="updateSalaryDate" class="form-label fw-bold">
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
                                                    <button type="submit" class="btn btn-dark rounded">Add
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
                                    <h5 class="modal-title" id="addPoliciesModalLabel">Add Policies</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-0 p-2 signature-bg-color text-white rounded-2 text-center fw-bold d-none mb-2"
                                        id="policyFileName"></p>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="empIdToAddPolicy" id="empIdToAddPolicy"
                                            value="<?php echo $employeeId ?>">
                                        <input type="hidden" name="empNameToAddPolicy" id="empNameToAddPolicy"
                                            value="<?php echo $firstName . " " . $lastName ?>">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label for="selectedPolicyTypeToAdd" class="fw-bold">Policy</label>
                                                <?php
                                                // Query for policy files that match the pattern
                                                $select_policy_file_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '09-HR-PO-%' ORDER BY qa_document";
                                                $select_policy_file_result = $conn->query($select_policy_file_sql);
                                                ?>
                                                <select class="form-select" name="selectedPolicyTypeToAdd"
                                                    id="selectedPolicyTypeToAdd" required>
                                                    <option value="">Select Policy</option> <!-- Default option -->
                                                    <?php if ($select_policy_file_result->num_rows > 0) {
                                                        while ($row = $select_policy_file_result->fetch_assoc()) { ?>
                                                            <option value="<?= htmlspecialchars($row['qa_document']) ?>">
                                                                <?= htmlspecialchars($row['qa_document']) . " (" . htmlspecialchars($row['document_name']) . ")" ?>
                                                            </option>
                                                        <?php }
                                                    } ?>
                                                </select>
                                            </div>

                                            <div class="form-group col-md-6 mt-md-0 mt-2">
                                                <label for="addPolicyDate" class="fw-bold">Date</label>
                                                <input type="date" class="form-control" name="addPolicyDate"
                                                    id="addPolicyDate" value="<?php echo date('Y-m-d') ?>" required>
                                            </div>
                                        </div>

                                        <!-- Drag and Drop area -->
                                        <div class="border rounded-2 p-4 text-center mt-3" id="dropZone">
                                            <p class="mb-0">Drag & Drop your documents here or <br>
                                                <button class="btn btn-primary btn-sm mt-2" type="button"
                                                    onclick="document.getElementById('fileInput').click()">Browse
                                                    Files</button>
                                            </p>
                                        </div>

                                        <input type="file" id="fileInput" name="policiesToSubmit" class="d-none" required />
                                        <!-- Display uploaded file names -->
                                        <div id="fileList" class="mt-3"></div>
                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-dark btn-sm"> Add
                                                Policies</button>
                                        </div>
                                    </form>
                                </div> <!-- End modal-body -->
                            </div>
                        </div>
                    </div>

                    <!-- ================== Add Machine Competency Modal ================== -->
                    <div class="modal fade" id="addMachineCompetencyModal" tabindex="-1"
                        aria-labelledby="addMachineCompetencyModal" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addMachineCompetencyModalLabel">Add Machine Competency</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-0 p-2 signature-bg-color text-white rounded-2 text-center fw-bold mb-2 d-none"
                                        id="machineCompetencyFileName"></p>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="empIdToAddMachineCompetency"
                                            id="empIdToAddMachineCompetency" value="<?php echo $employeeId ?>">
                                        <input type="hidden" name="empNameToAddMachineCompetency"
                                            id="empNameToAddMachineCompetency"
                                            value="<?php echo $firstName . " " . $lastName ?>">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label for="selectedMachineCompetencyTypeToAdd" class="fw-bold">Machine
                                                    Competency</label>
                                                <?php
                                                // Query for machine competency files that match the pattern
                                                $select_machine_competency_file_sql = "SELECT qa_document, document_name FROM quality_assurance WHERE qa_document LIKE '11-WH-WI-%' ORDER BY qa_document";
                                                $select_machine_competency_file_result = $conn->query($select_machine_competency_file_sql);
                                                ?>
                                                <select class="form-select" name="selectedMachineCompetencyTypeToAdd"
                                                    id="selectedMachineCompetencyTypeToAdd" required>
                                                    <option value="">Select Machine Competency</option>
                                                    <?php if ($select_machine_competency_file_result->num_rows > 0) {
                                                        while ($row = $select_machine_competency_file_result->fetch_assoc()) { ?>
                                                            <option value="<?= htmlspecialchars($row['qa_document']) ?>">
                                                                <?= htmlspecialchars($row['qa_document']) . " (" . htmlspecialchars($row['document_name']) . ")" ?>
                                                            </option>
                                                        <?php }
                                                    } ?>
                                                </select>
                                            </div>

                                            <div class="form-group col-md-6 mt-md-0 mt-2">
                                                <label for="addMachineCompetencyDate" class="fw-bold">Date</label>
                                                <input type="date" class="form-control" name="addMachineCompetencyDate"
                                                    id="addMachineCompetencyDate" value="<?php echo date('Y-m-d') ?>"
                                                    required>
                                            </div>
                                        </div>

                                        <!-- Drag and Drop area -->
                                        <div class="border rounded-2 p-4 text-center mt-3" id="machineCompetencyDropZone">
                                            <p class="mb-0">Drag & Drop your documents here or <br>
                                                <button class="btn btn-primary btn-sm mt-2" type="button"
                                                    onclick="document.getElementById('machineCompetencyFileInput').click()">Browse
                                                    Files</button>
                                                </button>
                                            </p>
                                        </div>

                                        <input type="file" id="machineCompetencyFileInput"
                                            name="machineCompetenciesToSubmit" class="d-none" required />
                                        <div id="machineCompetencyFileList" class="mt-3"></div>
                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-dark btn-sm">Add Machine
                                                Competency</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================== Add Leave Modal ================== -->
                    <div class="modal fade" id="addLeaveModal" tabindex="-1" aria-labelledby="addLeaveModal"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addLeaveModalLabel">Add Leave</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-0 p-2 signature-bg-color text-white rounded-2 text-center fw-bold mb-2 d-none"
                                        id="leaveFileName"></p>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="empIdToAddLeave" id="empIdToAddLeave"
                                            value="<?php echo $employeeId ?>">
                                        <input type="hidden" name="empNameToAddLeave" id="empNameToAddLeave"
                                            value="<?php echo $firstName . " " . $lastName ?>">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label for="selectedLeaveTypeToAdd" class="fw-bold">Leave Type</label>
                                                <select class="form-select" name="selectedLeaveTypeToAdd"
                                                    id="selectedLeaveTypeToAdd" required>
                                                    <option value="">Select Leave File Type</option>
                                                    <option value="medicalCertificate">Medical Certificate</option>
                                                    <option value="personalLeave">Personal Leave</option>
                                                    <option value="annualLeave">Annual Leave</option>
                                                    <option value="workingFromHome">Working From Home</option>
                                                    <option value="longServiceLeave">Long Service Leave</option>
                                                </select>
                                            </div>

                                            <div class="form-group col-md-6 mt-md-0 mt-2">
                                                <label for="addLeaveDate" class="fw-bold">Date</label>
                                                <input type="date" class="form-control" name="addLeaveDate"
                                                    id="addLeaveDate" value="<?php echo date('Y-m-d') ?>" required>
                                            </div>
                                        </div>

                                        <!-- Drag and Drop Zone -->
                                        <div class="border rounded-2 p-4 text-center mt-3" id="leaveDropZone">
                                            <p class="mb-0">Drag & Drop your documents here or <br>
                                                <button class="btn btn-primary btn-sm mt-2" type="button"
                                                    onclick="document.getElementById('leaveFileInput').click()">Browse
                                                    Files</button>
                                                </button>
                                            </p>
                                        </div>

                                        <input type="file" id="leaveFileInput" name="leaveToSubmit" class="d-none"
                                            required />
                                        <div id="leaveFileList" class="mt-3"></div>
                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-dark btn-sm">Add Leave</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================== Upload Employee Files Modal ================== -->
                    <div class="modal fade" id="uploadEmployeeFilesModal" tabindex="-1"
                        aria-labelledby="uploadEmployeeFilesModal" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="uploadEmployeeFilesModal">Upload Employee Files</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php require_once("../PageContent/ModalContent/upload-employee-files.php") ?>
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
                    <!-- ================== Deactivate Modal Form ================== -->
                    <div class="modal fade" id="deactivateFormModal" tabindex="-1"
                        aria-labelledby="deactivateFormModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Deactivate Employee</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="employeeIdToDeactivate" class="form-control"
                                            value="<?php echo $employeeId ?>">
                                        <label for="lastDate" class="fw-bold">Last Date </label>
                                        <input type="date" class="form-control" name="lastDate"
                                            value="<?php echo date('Y-m-d') ?>" required>
                                        <div class="d-flex justify-content-end mt-2">
                                            <button class="btn btn-danger">Deactivate Employee</button>
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
            <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

            <script>
                // Reload the page when wagePayRaiseHistoryModal is hidden
                $('#wagePayRaiseHistoryModal').on('hidden.bs.modal', function () {
                    location.reload(); // Refresh the page
                });

                // Override behavior to prevent wagePayRaiseHistoryModal from hiding when deleteConfirmationModal opens
                $('#deleteConfirmationModal').on('show.bs.modal', function (e) {
                    // Temporarily detach the event that hides wagePayRaiseHistoryModal
                    $('#wagePayRaiseHistoryModal').off('hidden.bs.modal');
                });

                // Restore reload functionality when deleteConfirmationModal is closed
                $('#deleteConfirmationModal').on('hidden.bs.modal', function () {
                    // Reattach the hidden event to wagePayRaiseHistoryModal with reload behavior
                    $('#wagePayRaiseHistoryModal').on('hidden.bs.modal', function () {
                        location.reload(); // Refresh the page
                    });
                });
            </script>

            <script>
                // Function to detect and prevent the default print action
                document.addEventListener('keydown', function (event) {
                    // Check if Ctrl+P or Cmd+P is pressed
                    if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
                        event.preventDefault(); // Prevent the print dialog
                        alert("Command + P / Ctrl + P is not allowed in this page.");
                    }
                });

                function toggleAndPrint() {
                    if (document.getElementById('chartContainer').classList.contains('d-none')) {
                        toggleWagePayRaiseHistory(); // Toggle the wagePayHistory first
                    }

                    if (document.getElementById('chartContainer2').classList.contains('d-none')) {
                        toggleSalaryPayRaiseHistory(); // Toggle the wagePayHistory first
                    }

                    // Delay the print slightly to ensure the toggle is complete
                    setTimeout(function () {
                        window.print(); // Show the print dialog after toggling
                    }, 300); // 300ms delay to ensure the toggle is visible before printing
                }
            </script>

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
                    updateFileInput(files);
                });

                fileInput.addEventListener('change', function (event) {
                    updateFileList(event.target.files);
                });

                function updateFileInput(files) {
                    const dataTransfer = new DataTransfer();

                    // Add existing files (if any) from the input
                    for (let i = 0; i < fileInput.files.length; i++) {
                        dataTransfer.items.add(fileInput.files[i]);
                    }

                    // Add new files from the drop event
                    for (let i = 0; i < files.length; i++) {
                        dataTransfer.items.add(files[i]);
                    }

                    fileInput.files = dataTransfer.files;
                    updateFileList(fileInput.files);
                }

                function updateFileList(files) {
                    fileList.innerHTML = ''; // Clear previous list

                    for (let i = 0; i < files.length; i++) {
                        const listItem = document.createElement('div');
                        listItem.className = 'd-flex justify-content-between align-items-center border p-2 mb-2';
                        listItem.innerHTML = `
                            <span>${files[i].name}</span>
                            <button class="btn btn-danger btn-sm" type="button" onclick="removeFile(${i})">Remove</button>
                        `;
                        fileList.appendChild(listItem);
                    }
                }

                function removeFile(index) {
                    const dataTransfer = new DataTransfer();

                    // Keep only the files except the one being removed
                    for (let i = 0; i < fileInput.files.length; i++) {
                        if (i !== index) {
                            dataTransfer.items.add(fileInput.files[i]);
                        }
                    }

                    fileInput.files = dataTransfer.files;
                    updateFileList(fileInput.files);
                }
            </script>

            <script>
                const machineCompetencyDropZone = document.getElementById('machineCompetencyDropZone');
                const machineCompetencyFileInput = document.getElementById('machineCompetencyFileInput');
                const machineCompetencyFileList = document.getElementById('machineCompetencyFileList');

                machineCompetencyDropZone.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    machineCompetencyDropZone.classList.add('bg-light');
                });

                machineCompetencyDropZone.addEventListener('dragleave', function () {
                    machineCompetencyDropZone.classList.remove('bg-light');
                });

                machineCompetencyDropZone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    machineCompetencyDropZone.classList.remove('bg-light');
                    const files = event.dataTransfer.files;
                    updateMachineCompetencyFileInput(files);
                });

                machineCompetencyFileInput.addEventListener('change', function (event) {
                    updateMachineCompetencyFileList(event.target.files);
                });

                function updateMachineCompetencyFileInput(files) {
                    const dataTransfer = new DataTransfer();

                    // Add existing files (if any)
                    for (let i = 0; i < machineCompetencyFileInput.files.length; i++) {
                        dataTransfer.items.add(machineCompetencyFileInput.files[i]);
                    }

                    // Add new files
                    for (let i = 0; i < files.length; i++) {
                        dataTransfer.items.add(files[i]);
                    }

                    machineCompetencyFileInput.files = dataTransfer.files;
                    updateMachineCompetencyFileList(machineCompetencyFileInput.files);
                }

                function updateMachineCompetencyFileList(files) {
                    machineCompetencyFileList.innerHTML = ''; // Clear previous list

                    for (let i = 0; i < files.length; i++) {
                        const listItem = document.createElement('div');
                        listItem.className = 'd-flex justify-content-between align-items-center border p-2 mb-2';
                        listItem.innerHTML = `
                            <span>${files[i].name}</span>
                            <button class="btn btn-danger btn-sm" type="button" onclick="removeMachineCompetencyFile(${i})">Remove</button>
                        `;
                        machineCompetencyFileList.appendChild(listItem);
                    }
                }

                function removeMachineCompetencyFile(index) {
                    const dataTransfer = new DataTransfer();

                    for (let i = 0; i < machineCompetencyFileInput.files.length; i++) {
                        if (i !== index) {
                            dataTransfer.items.add(machineCompetencyFileInput.files[i]);
                        }
                    }

                    machineCompetencyFileInput.files = dataTransfer.files;
                    updateMachineCompetencyFileList(machineCompetencyFileInput.files);
                }
            </script>

            <script>
                const leaveDropZone = document.getElementById('leaveDropZone');
                const leaveFileInput = document.getElementById('leaveFileInput');
                const leaveFileList = document.getElementById('leaveFileList');

                leaveDropZone.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    leaveDropZone.classList.add('bg-light');
                });

                leaveDropZone.addEventListener('dragleave', function () {
                    leaveDropZone.classList.remove('bg-light');
                });

                leaveDropZone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    leaveDropZone.classList.remove('bg-light');
                    const files = event.dataTransfer.files;
                    updateLeaveFileInput(files);
                });

                leaveFileInput.addEventListener('change', function (event) {
                    updateLeaveFileList(event.target.files);
                });

                function updateLeaveFileInput(files) {
                    const dataTransfer = new DataTransfer();

                    // Add existing files (if any)
                    for (let i = 0; i < leaveFileInput.files.length; i++) {
                        dataTransfer.items.add(leaveFileInput.files[i]);
                    }

                    // Add new files
                    for (let i = 0; i < files.length; i++) {
                        dataTransfer.items.add(files[i]);
                    }

                    leaveFileInput.files = dataTransfer.files;
                    updateLeaveFileList(leaveFileInput.files);
                }

                function updateLeaveFileList(files) {
                    leaveFileList.innerHTML = ''; // Clear previous list

                    for (let i = 0; i < files.length; i++) {
                        const listItem = document.createElement('div');
                        listItem.className = 'd-flex justify-content-between align-items-center border p-2 mb-2';
                        listItem.innerHTML = `
                            <span>${files[i].name}</span>
                            <button class="btn btn-danger btn-sm" type="button" onclick="removeLeaveFile(${i})">Remove</button>
                        `;
                        leaveFileList.appendChild(listItem);
                    }
                }

                function removeLeaveFile(index) {
                    const dataTransfer = new DataTransfer();

                    for (let i = 0; i < leaveFileInput.files.length; i++) {
                        if (i !== index) {
                            dataTransfer.items.add(leaveFileInput.files[i]);
                        }
                    }

                    leaveFileInput.files = dataTransfer.files;
                    updateLeaveFileList(leaveFileInput.files);
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

                // Function to toggle the visibility of the salary pay raise history chart
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

            <script>
                // Initialize totalAllowance from PHP
                let totalAllowance = 0;

                // Track the state of all checkboxes
                let toolAllowanceChecked = <?php echo isset($toolAllowance) && $toolAllowance == 1 ? 'true' : 'false'; ?>;
                let firstAidAllowanceChecked = <?php echo isset($firstAidAllowance) && $firstAidAllowance == 1 ? 'true' : 'false'; ?>;
                let teamLeaderAllowanceChecked = <?php echo isset($teamLeaderAllowanceChecked) && $teamLeaderAllowanceChecked == 1 ? 'true' : 'false'; ?>;
                let trainerAllowanceChecked = <?php echo isset($trainerAllowanceChecked) && $trainerAllowanceChecked == 1 ? 'true' : 'false'; ?>;
                let supervisorAllowanceChecked = <?php echo isset($supervisorAllowanceChecked) && $supervisorAllowanceChecked == 1 ? 'true' : 'false'; ?>;
                let painterAllowanceChecked = <?php echo isset($painterAllowanceChecked) && $painterAllowanceChecked == 1 ? 'true' : 'false'; ?>;
                let machineMaintenanceAllowanceChecked = <?php echo isset($machineMaintenanceAllowanceChecked) && $machineMaintenanceAllowanceChecked == 1 ? 'true' : 'false'; ?>;

                // Get the allowance amounts from PHP
                const toolAllowanceAmount = <?php echo isset($toolAllowanceData[0]['amount']) ? $toolAllowanceData[0]['amount'] : 0; ?>;
                const firstAidAllowanceAmount = <?php echo isset($firstAidAllowanceData[0]['amount']) ? $firstAidAllowanceData[0]['amount'] : 0; ?>;
                let teamLeaderAllowanceAmount = <?php echo isset($teamLeaderAllowance) ? $teamLeaderAllowance : 0; ?>; // Ensure this is set properly
                let trainerAllowanceAmount = <?php echo isset($trainerAllowance) ? $trainerAllowance : 0; ?>;
                let supervisorAllowanceAmount = <?php echo isset($supervisorAllowance) ? $supervisorAllowance : 0; ?>;
                let painterAllowanceAmount = <?php echo isset($painterAllowance) ? $painterAllowance : 0; ?>;
                let machineMaintenanceAllowanceAmount = <?php echo isset($machineMaintenanceAllowance) ? $machineMaintenanceAllowance : 0; ?>;

                // Function to handle the checkbox change for Tool Allowance
                function toolAllowanceCheckbox(checkbox, employeeId) {
                    toolAllowanceChecked = checkbox.checked;
                    const formData = new FormData();
                    formData.append('employeeId', employeeId);
                    formData.append('tool_allowance', toolAllowanceChecked ? 1 : 0);

                    fetch('../AJAXphp/update_tool_allowance.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("Tool allowance updated successfully.");
                                updateTotalAllowance();
                            } else {
                                console.error("Failed to update tool allowance.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating tool allowance:", error);
                        });
                }

                // Function to handle the checkbox change for First Aid Allowance
                function firstAidAllowanceCheckbox(checkbox, employeeId) {
                    firstAidAllowanceChecked = checkbox.checked;
                    const formData = new FormData();
                    formData.append('employeeId', employeeId);
                    formData.append('first_aid_allowance', firstAidAllowanceChecked ? 1 : 0);

                    fetch('../AJAXphp/update_first_aid_allowance.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("First aid allowance updated successfully.");
                                updateTotalAllowance();
                            } else {
                                console.error("Failed to update first aid allowance.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating first aid allowance:", error);
                        });
                }

                // Function to handle the checkbox change for Team Leader Allowance
                function teamLeaderAllowanceCheckbox(checkbox, employeeId) {
                    teamLeaderAllowanceChecked = checkbox.checked;
                    const formData = new FormData();
                    formData.append('employeeId', employeeId);
                    formData.append('team_leader_allowance_check', teamLeaderAllowanceChecked ? 1 : 0);

                    fetch('../AJAXphp/update_team_leader_allowance.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("Team Leader allowance updated successfully.");
                                updateTotalAllowance();
                            } else {
                                console.error("Failed to update team leader allowance.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating team leader allowance:", error);
                        });
                }

                // Function to handle the checkbox change for Trainer Allowance
                function trainerAllowanceCheckbox(checkbox, employeeId) {
                    trainerAllowanceChecked = checkbox.checked;
                    const formData = new FormData();
                    formData.append('employeeId', employeeId);
                    formData.append('trainer_allowance_check', trainerAllowanceChecked ? 1 : 0);

                    fetch('../AJAXphp/update_team_leader_allowance.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("Trainer allowance updated successfully.");
                                updateTotalAllowance();
                            } else {
                                console.error("Failed to update trainer allowance.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating trainer allowance:", error);
                        });
                }

                // Function to handle the checkbox change for Supervisor Allowance
                function supervisorAllowanceCheckbox(checkbox, employeeId) {
                    supervisorAllowanceChecked = checkbox.checked;
                    const formData = new FormData();
                    formData.append('employeeId', employeeId);
                    formData.append('supervisor_allowance_check', supervisorAllowanceChecked ? 1 : 0);

                    fetch('../AJAXphp/update_team_leader_allowance.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("Supervisor allowance updated successfully.");
                                updateTotalAllowance();
                            } else {
                                console.error("Failed to update supervisor allowance.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating supervisor allowance:", error);
                        });
                }

                // Function to handle the checkbox change for Painter Allowance
                function painterAllowanceCheckbox(checkbox, employeeId) {
                    painterAllowanceChecked = checkbox.checked;
                    const formData = new FormData();
                    formData.append('employeeId', employeeId);
                    formData.append('painter_allowance_check', painterAllowanceChecked ? 1 : 0);

                    fetch('../AJAXphp/update_team_leader_allowance.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("Painter allowance updated successfully.");
                                updateTotalAllowance();
                            } else {
                                console.error("Failed to update painter allowance.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating painter allowance:", error);
                        });
                }

                // Function to handle the checkbox change for Machine Maintenance Allowance
                function machineMaintenanceAllowanceCheckbox(checkbox, employeeId) {
                    machineMaintenanceAllowanceChecked = checkbox.checked;
                    const formData = new FormData();
                    formData.append('employeeId', employeeId);
                    formData.append('machine_maintenance_allowance_check', machineMaintenanceAllowanceChecked ? 1 : 0);

                    fetch('../AJAXphp/update_team_leader_allowance.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("Machine maintenance allowance updated successfully.");
                                updateTotalAllowance();
                            } else {
                                console.error("Failed to update machine maintenance allowance.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating machine maintenance allowance:", error);
                        });
                }

                // Function to update the total allowance based on the state of all checkboxes
                function updateTotalAllowance() {
                    totalAllowance = 0;

                    if (toolAllowanceChecked) {
                        totalAllowance += toolAllowanceAmount;
                    }
                    if (firstAidAllowanceChecked) {
                        totalAllowance += firstAidAllowanceAmount;
                    }
                    if (teamLeaderAllowanceChecked) {
                        totalAllowance += parseFloat(teamLeaderAllowanceAmount) || 0;
                    }
                    if (trainerAllowanceChecked) {
                        totalAllowance += parseFloat(trainerAllowanceAmount) || 0;
                    }
                    if (supervisorAllowanceChecked) {
                        totalAllowance += parseFloat(supervisorAllowanceAmount) || 0;
                    }
                    if (painterAllowanceChecked) {
                        totalAllowance += parseFloat(painterAllowanceAmount) || 0;
                    }
                    if (machineMaintenanceAllowanceChecked) {
                        totalAllowance += parseFloat(machineMaintenanceAllowanceAmount) || 0;
                    }

                    document.getElementById('currentTotalAllowance').innerText = `${totalAllowance.toFixed(2)}`;
                }

                document.addEventListener('DOMContentLoaded', updateTotalAllowance);
            </script>

            <script>
                document.getElementById('showUpdateForm').addEventListener('click', function () {
                    var form = document.getElementById('updateWageForm');
                    var button = document.getElementById('showUpdateForm');
                    var wageDetailTable = document.getElementById('wageDetailTable');
                    var cancelUpdateWageBtn = document.getElementById('cancelUpdateWageBtn');

                    // Toggle the form's visibility by toggling 'd-none'
                    form.classList.toggle('d-none');

                    // Change button text based on the form's visibility
                    if (form.classList.contains('d-none')) {
                        button.classList.remove('btn-danger');

                    } else {
                        wageDetailTable.classList.add('d-none');
                    }

                    cancelUpdateWageBtn.addEventListener('click', function () {
                        wageDetailTable.classList.remove('d-none');
                        form.classList.add('d-none');
                    })
                });
            </script>


            <!-- Tool Allowance -->
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Edit button click event handler
                    document.getElementById('editTeamLeaderAllowanceBtn').addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle edit mode
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-team-leader, .edit-mode-team-leader').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    });

                    // Cancel button click event handler
                    document.getElementById('cancelTeamLeaderEditBtn').addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle back to view mode (exit edit mode)
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-team-leader, .edit-mode-team-leader').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    })

                    // Edit team leader button
                    document.getElementById('saveTeamLeaderBtn').addEventListener('click', function () {
                        var button = document.getElementById('saveTeamLeaderBtn');
                        var employeeId = button.getAttribute('data-employee-id');
                        var teamLeaderAllowance = document.querySelector('input[name="teamLeaderAllowanceToEdit"]').value;

                        // Prepare the data to send
                        const formData = new FormData();
                        formData.append('employeeId', employeeId);
                        formData.append('team_leader_allowance', teamLeaderAllowance);

                        // Use fetch to send the data to the server
                        fetch('../AJAXphp/update_team_leader_allowance.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Team leader allowance rate updated successfully.");


                                    // Update the display of the team leader allowance in the modal
                                    const formattedAllowance = parseFloat(teamLeaderAllowance).toFixed(2); // Format to 2 decimal places
                                    document.querySelector('.teamLeaderAllowanceAmount').innerHTML = `$${formattedAllowance}`;

                                    // Optionally, hide the edit mode and show the view mode
                                    document.querySelector('.edit-mode-team-leader').classList.add('d-none');
                                    document.querySelector('.view-mode-team-leader').classList.remove('d-none');

                                    teamLeaderAllowanceAmount = teamLeaderAllowance;

                                    console.log(teamLeaderAllowanceAmount);

                                    // Update the total allowance display
                                    updateTotalAllowance();

                                } else {
                                    console.error("Failed to update team leader allowance rate.");
                                }
                            })
                            .catch(error => {
                                console.error("Error updating team leader allowance:", error);
                            });
                    });

                });
            </script>

            <!-- Trainer Allowance -->
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Edit button click event handler
                    document.getElementById('editTrainerAllowanceBtn').addEventListener('click', function () {
                        // Get the parent rows
                        var row = this.closest('tr');

                        // Toggle edit mode
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-trainer, .edit-mode-trainer').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    });

                    // Cancel button click event handler
                    document.getElementById('cancelTrainerEditBtn').addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle back to view mode (exit edit mode)
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-trainer, .edit-mode-trainer').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    })

                    // Edit trainer button
                    document.getElementById('saveTrainerBtn').addEventListener('click', function () {
                        var button = document.getElementById('saveTrainerBtn');
                        var employeeId = button.getAttribute('data-employee-id');
                        var trainerAllowance = document.querySelector('input[name="trainerAllowanceToEdit"]').value;

                        // Prepare the data to send
                        const formData = new FormData();
                        formData.append('employeeId', employeeId);
                        formData.append('trainer_allowance', trainerAllowance);

                        // Use fetch to send the data to the server
                        fetch('../AJAXphp/update_team_leader_allowance.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Team leader allowance rate updated successfully.");


                                    // Update the display of the trainer allowance in the modal
                                    const formattedAllowance = parseFloat(trainerAllowance).toFixed(2); // Format to 2 decimal places
                                    document.querySelector('.trainerAllowanceAmount').innerHTML = `$${formattedAllowance}`;

                                    // Optionally, hide the edit mode and show the view mode
                                    document.querySelector('.edit-mode-trainer').classList.add('d-none');
                                    document.querySelector('.view-mode-trainer').classList.remove('d-none');

                                    trainerAllowanceAmount = trainerAllowance;

                                    console.log(trainerAllowanceAmount);

                                    // Update the total allowance display
                                    updateTotalAllowance();

                                } else {
                                    console.error("Failed to update trainer allowance rate.");
                                }
                            })
                            .catch(error => {
                                console.error("Error updating trainer allowance:", error);
                            });
                    });
                });
            </script>

            <!-- Supervisor Allowance -->
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Edit button click event handler
                    document.getElementById('editSupervisorAllowanceBtn').addEventListener('click', function () {
                        // Get the parent rows
                        var row = this.closest('tr');

                        // Toggle edit mode
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-supervisor, .edit-mode-supervisor').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    });

                    // Cancel button click event handler
                    document.getElementById('cancelSupervisorEditBtn').addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle back to view mode (exit edit mode)
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-supervisor, .edit-mode-supervisor').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    })

                    // Edit supervisor button
                    document.getElementById('saveSupervisorBtn').addEventListener('click', function () {
                        var button = document.getElementById('saveSupervisorBtn');
                        var employeeId = button.getAttribute('data-employee-id');
                        var supervisorAllowance = document.querySelector('input[name="supervisorAllowanceToEdit"]').value;

                        // Prepare the data to send
                        const formData = new FormData();
                        formData.append('employeeId', employeeId);
                        formData.append('supervisor_allowance', supervisorAllowance);

                        // Use fetch to send the data to the server
                        fetch('../AJAXphp/update_team_leader_allowance.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Team leader allowance rate updated successfully.");


                                    // Update the display of the supervisor allowance in the modal
                                    const formattedAllowance = parseFloat(supervisorAllowance).toFixed(2); // Format to 2 decimal places
                                    document.querySelector('.supervisorAllowanceAmount').innerHTML = `$${formattedAllowance}`;

                                    // Optionally, hide the edit mode and show the view mode
                                    document.querySelector('.edit-mode-supervisor').classList.add('d-none');
                                    document.querySelector('.view-mode-supervisor').classList.remove('d-none');

                                    supervisorAllowanceAmount = supervisorAllowance;

                                    console.log(supervisorAllowanceAmount);

                                    // Update the total allowance display
                                    updateTotalAllowance();

                                } else {
                                    console.error("Failed to update supervisor allowance rate.");
                                }
                            })
                            .catch(error => {
                                console.error("Error updating supervisor allowance:", error);
                            });
                    });
                });
            </script>

            <!-- Painter Allowance -->
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Edit button click event handler
                    document.getElementById('editPainterAllowanceBtn').addEventListener('click', function () {
                        // Get the parent rows
                        var row = this.closest('tr');

                        // Toggle edit mode
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-painter, .edit-mode-painter').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    });

                    // Cancel button click event handler
                    document.getElementById('cancelPainterEditBtn').addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle back to view mode (exit edit mode)
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-painter, .edit-mode-painter').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    })

                    // Edit painter button
                    document.getElementById('savePainterBtn').addEventListener('click', function () {
                        var button = document.getElementById('savePainterBtn');
                        var employeeId = button.getAttribute('data-employee-id');
                        var painterAllowance = document.querySelector('input[name="painterAllowanceToEdit"]').value;

                        // Prepare the data to send
                        const formData = new FormData();
                        formData.append('employeeId', employeeId);
                        formData.append('painter_allowance', painterAllowance);

                        // Use fetch to send the data to the server
                        fetch('../AJAXphp/update_team_leader_allowance.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Team leader allowance rate updated successfully.");


                                    // Update the display of the painter allowance in the modal
                                    const formattedAllowance = parseFloat(painterAllowance).toFixed(2); // Format to 2 decimal places
                                    document.querySelector('.painterAllowanceAmount').innerHTML = `$${formattedAllowance}`;

                                    // Optionally, hide the edit mode and show the view mode
                                    document.querySelector('.edit-mode-painter').classList.add('d-none');
                                    document.querySelector('.view-mode-painter').classList.remove('d-none');

                                    painterAllowanceAmount = painterAllowance;

                                    console.log(painterAllowanceAmount);

                                    // Update the total allowance display
                                    updateTotalAllowance();

                                } else {
                                    console.error("Failed to update painter allowance rate.");
                                }
                            })
                            .catch(error => {
                                console.error("Error updating painter allowance:", error);
                            });
                    });
                });
            </script>

            <!-- Machine Maintenance Allowance -->
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Edit button click event handler
                    document.getElementById('editMachineMaintenanceAllowanceBtn').addEventListener('click', function () {
                        // Get the parent rows
                        var row = this.closest('tr');

                        // Toggle edit mode
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-machine-maintenance, .edit-mode-machine-maintenance').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    });

                    // Cancel button click event handler
                    document.getElementById('cancelMachineMaintenanceEditBtn').addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle back to view mode (exit edit mode)
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode-machine-maintenance, .edit-mode-machine-maintenance').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    })

                    // Edit machine maintenance button
                    document.getElementById('saveMachineMaintenanceBtn').addEventListener('click', function () {
                        var button = document.getElementById('saveMachineMaintenanceBtn');
                        var employeeId = button.getAttribute('data-employee-id');
                        var machineMaintenanceAllowance = document.querySelector('input[name="machineMaintenanceAllowanceToEdit"]').value;

                        // Prepare the data to send
                        const formData = new FormData();
                        formData.append('employeeId', employeeId);
                        formData.append('machine_maintenance_allowance', machineMaintenanceAllowance);

                        // Use fetch to send the data to the server
                        fetch('../AJAXphp/update_team_leader_allowance.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Team leader allowance rate updated successfully.");


                                    // Update the display of the machine maintenance allowance in the modal
                                    const formattedAllowance = parseFloat(machineMaintenanceAllowance).toFixed(2); // Format to 2 decimal places
                                    document.querySelector('.machineMaintenanceAllowanceAmount').innerHTML = `$${formattedAllowance}`;

                                    // Optionally, hide the edit mode and show the view mode
                                    document.querySelector('.edit-mode-machine-maintenance').classList.add('d-none');
                                    document.querySelector('.view-mode-machine-maintenance').classList.remove('d-none');

                                    machineMaintenanceAllowanceAmount = machineMaintenanceAllowance;

                                    console.log(machineMaintenanceAllowanceAmount);

                                    // Update the total allowance display
                                    updateTotalAllowance();

                                } else {
                                    console.error("Failed to update machine maintenance allowance rate.");
                                }
                            })
                            .catch(error => {
                                console.error("Error updating machine maintenance allowance:", error);
                            });
                    });
                });
            </script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    var toggleElement = document.querySelector("[data-bs-target='#policiesContent']");
                    var chevronIcon = toggleElement.querySelector("i");

                    toggleElement.addEventListener("click", function () {
                        setTimeout(() => {
                            if (toggleElement.getAttribute("aria-expanded") === "true") {
                                chevronIcon.classList.remove("fa-chevron-down");
                                chevronIcon.classList.add("fa-chevron-up");
                            } else {
                                chevronIcon.classList.remove("fa-chevron-up");
                                chevronIcon.classList.add("fa-chevron-down");
                            }
                        }, 200); // Small delay to allow Bootstrap collapse to update `aria-expanded`
                    });
                });
            </script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    var toggleElement = document.querySelector("[data-bs-target='#machineCompetencyContent']");
                    var chevronIcon = toggleElement.querySelector("i");

                    toggleElement.addEventListener("click", function () {
                        setTimeout(() => {
                            if (toggleElement.getAttribute("aria-expanded") === "true") {
                                chevronIcon.classList.remove("fa-chevron-down");
                                chevronIcon.classList.add("fa-chevron-up");
                            } else {
                                chevronIcon.classList.remove("fa-chevron-up");
                                chevronIcon.classList.add("fa-chevron-down");
                            }
                        }, 200); // Small delay to allow Bootstrap collapse to update `aria-expanded`
                    });
                });
            </script>
            <script>
                // Function to update the policyFileName
                function updatePolicyFileName() {
                    let EmpId = document.getElementById("empIdToAddPolicy").value;
                    let EmpName = document.getElementById("empNameToAddPolicy").value;
                    let policy = document.getElementById("selectedPolicyTypeToAdd").value;
                    let date = document.getElementById("addPolicyDate").value;
                    let fileNameDisplay = document.getElementById("policyFileName");

                    if (policy && date) {
                        fileNameDisplay.classList.remove("d-none");
                        fileNameDisplay.textContent = `${date}-${policy} (${EmpId} ${EmpName}) Signed`;
                    } else {
                        fileNameDisplay.textContent = "";
                    }
                }

                // Add event listeners to update on change
                document.getElementById("selectedPolicyTypeToAdd").addEventListener("change", updatePolicyFileName);
                document.getElementById("addPolicyDate").addEventListener("input", updatePolicyFileName);
            </script>

            <script>
                // Function to update the machineCompetencyName
                function updateMachineCompetencyFileName() {
                    let EmpId = document.getElementById("empIdToAddMachineCompetency").value;
                    let EmpName = document.getElementById("empNameToAddMachineCompetency").value;
                    let machineCompetency = document.getElementById("selectedMachineCompetencyTypeToAdd").value;
                    let date = document.getElementById("addMachineCompetencyDate").value;
                    let fileNameDisplay = document.getElementById("machineCompetencyFileName");

                    if (machineCompetency && date) {
                        fileNameDisplay.classList.remove("d-none");
                        fileNameDisplay.textContent = `${date}-${machineCompetency} (${EmpId} ${EmpName}) Signed`;
                    } else {
                        fileNameDisplay.textContent = "";
                    }
                }

                // Add event listeners to update on change
                document.getElementById("selectedMachineCompetencyTypeToAdd").addEventListener("change", updateMachineCompetencyFileName);
                document.getElementById("addMachineCompetencyDate").addEventListener("input", updateMachineCompetencyFileName);
            </script>

            <script>
                // Function to update the leaveName
                function updateLeaveFileName() {
                    let EmpId = document.getElementById("empIdToAddLeave").value;
                    let EmpName = document.getElementById("empNameToAddLeave").value;
                    let leave = document.getElementById("selectedLeaveTypeToAdd").value;
                    let date = document.getElementById("addLeaveDate").value;
                    let fileNameDisplay = document.getElementById("leaveFileName");

                    if (leave && date) {
                        fileNameDisplay.classList.remove("d-none");
                        let leaveFileName = "";
                        if (leave === "personalLeave" || leave === "annualLeave" || leave === "longServiceLeave") {
                            leaveFileName = "09-HR-F0-007";
                        } else if (leave === "medicalCertificate") {
                            leaveFileName = "Doc-Cert";
                        } else if (leave === "workingFromHome") {
                            leaveFileName = "09-HR-FO-014";
                        }

                        fileNameDisplay.textContent = `${date}-${leaveFileName} (${EmpId} ${EmpName}) Signed`;
                    } else {
                        fileNameDisplay.textContent = "";
                    }
                }

                // Add event listeners to update on change
                document.getElementById("selectedLeaveTypeToAdd").addEventListener("change", updateLeaveFileName);
                document.getElementById("addLeaveDate").addEventListener("input", updateLeaveFileName);
            </script>
</body>

</html>